<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Services\EngagementTime\EngagementTimeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class EngagementTimeController extends Controller
{
    /**
     * Maximum allowed delta in a single heartbeat to prevent artificial inflation.
     */
    public const MAX_DELTA_SECONDS = 120;

    /**
     * Heartbeat idempotency cache TTL in minutes.
     */
    public const IDEMPOTENCY_TTL_MINUTES = 30;

    /**
     * Record a heartbeat of active engagement time strictly for the two authorized target learning items.
     *
     * Persists accumulated engagement time to:
     * - `content_users.afk_duration_seconds` for standalone content.
     * - `playlist_content_users.afk_duration_seconds` for playlist child content.
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $userId = (int) Auth::id();
        if (!$userId) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'target_type'     => 'required|in:content,playlist',
            'content_id'      => 'required|integer|min:1',
            'trainingplan_id' => 'required_if:target_type,playlist|nullable|integer|min:1',
            'delta_seconds'   => 'required|integer|min:1|max:' . self::MAX_DELTA_SECONDS,
            'heartbeat_id'    => 'required|string|max:64',
        ]);

        $targetType = $validated['target_type'];
        $contentId  = (int) $validated['content_id'];
        $delta      = (int) $validated['delta_seconds'];
        $heartbeatId = $validated['heartbeat_id'];

        $content = Content::where('id', $contentId)->where('active', 'Active')->first();
        if (!$content) {
            return response()->json([
                'success' => false,
                'message' => 'Target content not found or inactive.',
            ], 404);
        }

        $trainingplan = null;

        if ($targetType === 'content') {
            // Strict Scope Restriction: Must match authorized content target
            if (!EngagementTimeService::isTargetContent($content)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Total engagement time tracking is not enabled for this content.',
                ], 403);
            }

            if (!$content->isAccessibleByDeadline()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Content is not accessible due to deadline restrictions.',
                ], 403);
            }
        } else {
            $tpId = (int) ($validated['trainingplan_id'] ?? 0);
            $trainingplan = TrainingPlan::where('id', $tpId)->where('active', 'Active')->first();

            if (!$trainingplan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Target playlist not found or inactive.',
                ], 404);
            }

            // Strict Scope Restriction: Must match authorized playlist and child content target
            if (!EngagementTimeService::isTargetPlaylistContent($trainingplan, $content)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Total engagement time tracking is not enabled for this playlist item.',
                ], 403);
            }

            if (!$trainingplan->isAccessibleByDeadline()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Playlist is not accessible due to deadline restrictions.',
                ], 403);
            }

            if (!$content->isAccessibleByDeadline()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Content is not accessible due to deadline restrictions.',
                ], 403);
            }

            // Validate playlist content relationship
            $belongsToPlaylist = TrainingPlanDetail::where('trainingplan_id', $trainingplan->id)
                ->where('content_id', $content->id)
                ->exists();

            if (!$belongsToPlaylist) {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected content does not belong to this playlist.',
                ], 422);
            }
        }

        // Idempotency check: Prevents duplicate heartbeat application
        $scopeKey = $targetType === 'playlist' ? "p_{$trainingplan->id}_c_{$content->id}" : "c_{$content->id}";
        $cacheKey = "engagement_hb_{$userId}_{$scopeKey}_{$heartbeatId}";

        $isFirstAttempt = Cache::add($cacheKey, true, now()->addMinutes(self::IDEMPOTENCY_TTL_MINUTES));

        if (!$isFirstAttempt) {
            // Heartbeat was already processed; return current saved total without incrementing
            $currentTotal = $this->getCurrentEngagementSeconds($targetType, $userId, $content->id, $trainingplan?->id);

            return response()->json([
                'success'                  => true,
                'idempotent'               => true,
                'total_engagement_seconds' => $currentTotal,
                'afk_duration_seconds'     => $currentTotal,
            ]);
        }

        // Atomic update via DB transaction
        $totalSeconds = 0;

        try {
            DB::transaction(function () use ($targetType, $userId, $content, $trainingplan, $delta, &$totalSeconds) {
                if ($targetType === 'content') {
                    $record = ContentUser::firstOrCreate(
                        [
                            'user_id'    => $userId,
                            'content_id' => $content->id,
                        ],
                        [
                            'status'     => $content->status ?? 'non mandatory',
                        ]
                    );

                    DB::table('content_users')
                        ->where('id', $record->id)
                        ->increment('afk_duration_seconds', $delta, ['updated_at' => now()]);

                    $totalSeconds = (int) DB::table('content_users')
                        ->where('id', $record->id)
                        ->value('afk_duration_seconds');
                } else {
                    $record = PlaylistContentUser::firstOrCreate(
                        [
                            'user_id'         => $userId,
                            'trainingplan_id' => $trainingplan->id,
                            'content_id'      => $content->id,
                        ],
                        [
                            'status'          => $content->status ?? 'non mandatory',
                        ]
                    );

                    DB::table('playlist_content_users')
                        ->where('id', $record->id)
                        ->increment('afk_duration_seconds', $delta, ['updated_at' => now()]);

                    $totalSeconds = (int) DB::table('playlist_content_users')
                        ->where('id', $record->id)
                        ->value('afk_duration_seconds');
                }
            });
        } catch (\Throwable $e) {
            Cache::forget($cacheKey);
            throw $e;
        }

        return response()->json([
            'success'                  => true,
            'total_engagement_seconds' => $totalSeconds,
            'afk_duration_seconds'     => $totalSeconds,
        ]);
    }

    /**
     * Retrieve the current accumulated engagement seconds for a user and learning item.
     */
    protected function getCurrentEngagementSeconds(string $targetType, int $userId, int $contentId, ?int $trainingPlanId): int
    {
        if ($targetType === 'content') {
            return (int) (DB::table('content_users')
                ->where('user_id', $userId)
                ->where('content_id', $contentId)
                ->value('afk_duration_seconds') ?? 0);
        }

        return (int) (DB::table('playlist_content_users')
            ->where('user_id', $userId)
            ->where('trainingplan_id', $trainingPlanId)
            ->where('content_id', $contentId)
            ->value('afk_duration_seconds') ?? 0);
    }
}
