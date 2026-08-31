<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Support\DurationFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlaylistProgressController extends Controller
{
    /**
     * Update or record playlist-specific content learning progress.
     *
     * Persists authoritative learning progress strictly to `playlist_content_users`.
     * Does NOT touch `content_users`.
     */
    public function updateProgress(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'trainingplan_id'      => 'required|integer|exists:trainingplan,id',
            'content_id'           => 'required|integer|exists:content,id',
            'type'                 => 'required|in:document,video',
            'last_page'            => 'nullable|integer|min:1',
            'total_pages'          => 'nullable|integer|min:1|max:100000',
            'current_time'         => 'nullable|numeric|min:0',
            'duration_seconds'     => 'nullable|numeric|min:0',
            'active_page'          => 'nullable|integer|min:1',
            'completed_page'       => 'nullable|integer|min:1',
            'page_elapsed_seconds' => 'nullable|numeric|min:0|max:300',
            'status'               => 'nullable|in:in_progress,paused,completed',
        ]);

        $userId = (int) Auth::id();
        if (!$userId) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // 1. Verify Playlist exists, is Active, and accessible
        $trainingplan = TrainingPlan::where('id', $validated['trainingplan_id'])
            ->where('active', 'Active')
            ->first();

        if (!$trainingplan) {
            return response()->json([
                'message' => 'Playlist not found or is currently inactive.',
            ], 404);
        }

        if (!$trainingplan->isAccessibleByDeadline()) {
            return response()->json([
                'message' => 'Playlist is not accessible due to deadline restrictions.',
            ], 403);
        }

        // 2. Verify Content exists, is Active, and accessible
        $content = Content::where('id', $validated['content_id'])
            ->where('active', 'Active')
            ->first();

        if (!$content) {
            return response()->json([
                'message' => 'Content not found or is currently inactive.',
            ], 404);
        }

        if (!$content->isAccessibleByDeadline()) {
            return response()->json([
                'message' => 'Content is not accessible due to deadline restrictions.',
            ], 403);
        }

        // 3. Verify Content belongs to the specified Playlist
        $belongsToPlaylist = TrainingPlanDetail::where('trainingplan_id', $trainingplan->id)
            ->where('content_id', $content->id)
            ->exists();

        if (!$belongsToPlaylist) {
            return response()->json([
                'message' => 'The selected content does not belong to this playlist.',
                'errors'  => [
                    'content_id' => ['The selected content is not part of the specified playlist.'],
                ],
            ], 422);
        }

        // 4. Lazy creation of PlaylistContentUser record
        $playlistContentUser = PlaylistContentUser::firstOrCreate([
            'user_id'         => $userId,
            'trainingplan_id' => $trainingplan->id,
            'content_id'      => $content->id,
        ]);

        $previousProgress = (int) $playlistContentUser->progress;
        $computed = null;
        $pageCompleted = null;
        $remainingSeconds = null;
        $allowedPage = null;

        // 5. Progress calculation
        if ($validated['type'] === 'document') {
            $totalPages = (int) ($validated['total_pages'] ?? $playlistContentUser->total_pages ?? 0);

            if ($totalPages > 0) {
                $playlistContentUser->total_pages = $totalPages;
            }

            if ($content->type === 'Document' && (int) $content->page_duration > 0 && $totalPages > 0) {
                $pageDuration = max(1, min(300, (int) $content->page_duration));
                $officialDuration = $totalPages * $pageDuration;
                $officialDurationValue = DurationFormatter::fromSeconds($officialDuration);

                $playlistContentUser->duration_seconds = $officialDuration;

                if ($content->duration !== $officialDurationValue) {
                    $content->duration = $officialDurationValue;
                    $content->save();
                }

                $completedPages = min((int) ($playlistContentUser->last_page ?? 0), $totalPages);
                if ($completedPages <= 0 && $previousProgress > 0) {
                    $completedPages = min($totalPages, (int) floor(($previousProgress * $totalPages) / 100));
                    $playlistContentUser->last_page = $completedPages;
                }
                $allowedPage = min($totalPages, $completedPages + 1);
                $timerKey = 'learning.playlist_doc_page_timer.' . $trainingplan->id . '.' . $content->id;

                if ((int) $playlistContentUser->finish === 1 || $previousProgress >= 100) {
                    $completedPages = $totalPages;
                    $playlistContentUser->last_page = $totalPages;
                    $playlistContentUser->current_time = $officialDuration;
                    $computed = 100;
                    $pageCompleted = true;
                    $allowedPage = $totalPages;
                    if ($request->hasSession()) {
                        $request->session()->forget($timerKey);
                    }
                } else {
                    $activePage = min((int) ($validated['active_page'] ?? 0), $totalPages);
                    $completedPage = min((int) ($validated['completed_page'] ?? 0), $totalPages);

                    if ($activePage === $allowedPage && $activePage > $completedPages) {
                        $timer = $request->hasSession() ? $request->session()->get($timerKey) : null;

                        if (!is_array($timer) || (int) ($timer['page'] ?? 0) !== $activePage) {
                            if ($request->hasSession()) {
                                $request->session()->put($timerKey, [
                                    'page'       => $activePage,
                                    'started_at' => microtime(true),
                                ]);
                            }
                        }
                    }

                    if ($completedPage > 0) {
                        if ($completedPage <= $completedPages) {
                            $pageCompleted = true;
                        } elseif ($completedPage === $allowedPage) {
                            $timer = $request->hasSession() ? $request->session()->get($timerKey, []) : [];
                            $serverElapsed = (int) ($timer['page'] ?? 0) === $completedPage
                                ? max(0, microtime(true) - (float) ($timer['started_at'] ?? microtime(true)))
                                : 0;
                            $clientElapsed = (float) ($validated['page_elapsed_seconds'] ?? 0);

                            if ($serverElapsed + .25 >= $pageDuration && $clientElapsed >= $pageDuration) {
                                $completedPages = $completedPage;
                                $playlistContentUser->last_page = $completedPages;
                                $playlistContentUser->current_time = min($officialDuration, $completedPages * $pageDuration);
                                $computed = ($completedPages / $totalPages) * 100;
                                $pageCompleted = true;
                                $allowedPage = min($totalPages, $completedPages + 1);
                                if ($request->hasSession()) {
                                    $request->session()->forget($timerKey);
                                }
                            } else {
                                $pageCompleted = false;
                                $remainingSeconds = max(1, (int) ceil(max(
                                    $pageDuration - $serverElapsed,
                                    $pageDuration - $clientElapsed
                                )));
                            }
                        } else {
                            $pageCompleted = false;
                            $remainingSeconds = $pageDuration;
                        }
                    }

                    if ($computed === null && $completedPages > 0) {
                        $computed = ($completedPages / $totalPages) * 100;
                    }
                }
            } elseif ($content->type === 'Document' && (int) $content->page_duration === 0) {
                // Document Scroll Mode
                $lastPage = (int) ($validated['last_page'] ?? $validated['completed_page'] ?? $validated['active_page'] ?? 0);
                $currentHighest = (int) ($playlistContentUser->last_page ?? 0);

                if ($lastPage > 0) {
                    $newLastPage = $totalPages > 0 ? min(max($lastPage, $currentHighest), $totalPages) : max($lastPage, $currentHighest);
                    $playlistContentUser->last_page = $newLastPage;
                    $pageCompleted = true;
                    $allowedPage = $totalPages > 0 ? min($totalPages, $newLastPage + 1) : $newLastPage;

                    if ($totalPages > 0) {
                        $computed = ($newLastPage / $totalPages) * 100;
                    }
                } elseif ($totalPages > 0 && $currentHighest > 0) {
                    $computed = ($currentHighest / $totalPages) * 100;
                }
            } else {
                // Fallback Document Logic
                $lastPage = (int) ($validated['last_page'] ?? 0);

                if ($lastPage > 0) {
                    $playlistContentUser->last_page = $totalPages > 0 ? min($lastPage, $totalPages) : $lastPage;

                    if ($totalPages > 0) {
                        $computed = ($playlistContentUser->last_page / $totalPages) * 100;
                    }
                }
            }
        } else {
            // Video Mode
            $duration    = (float) ($validated['duration_seconds'] ?? $playlistContentUser->duration_seconds ?? 0);
            $currentTime = $validated['current_time'] ?? null;

            if ($duration > 0) {
                $playlistContentUser->duration_seconds = (int) round($duration);
            }

            if ($currentTime !== null) {
                $currentTime = (float) $currentTime;

                if ($duration > 0) {
                    $currentTime = min($currentTime, $duration);
                    $computed = ($currentTime / $duration) * 100;
                }

                $playlistContentUser->current_time = (int) round($currentTime);
            }
        }

        // Monotonic rule: progress never decreases
        $progress = $previousProgress;

        if ($computed !== null) {
            $progress = max($previousProgress, max(0, min(100, (int) round($computed))));
        }

        // Video completed status with >= 90% threshold
        if ($validated['type'] === 'video'
            && ($validated['status'] ?? null) === 'completed'
            && $computed !== null
            && $computed >= 90) {
            $progress = 100;
        }

        $playlistContentUser->progress = $progress;

        if ($progress >= 100) {
            $playlistContentUser->finish = '1';
            $playlistContentUser->learning_status = 'completed';
        } elseif (($validated['status'] ?? null) === 'paused') {
            $playlistContentUser->learning_status = 'paused';
        } elseif (($validated['status'] ?? null) === 'in_progress' || $computed !== null) {
            $playlistContentUser->learning_status = 'in_progress';
        }

        $playlistContentUser->save();

        return response()->json([
            'success'           => true,
            'progress'          => (int) $playlistContentUser->progress,
            'status'            => $playlistContentUser->learning_status,
            'finish'            => (int) $playlistContentUser->finish,
            'current_time'      => (int) $playlistContentUser->current_time,
            'last_page'         => $playlistContentUser->last_page,
            'page_completed'    => $pageCompleted,
            'remaining_seconds' => $remainingSeconds,
            'allowed_page'      => $allowedPage,
            'duration_seconds'  => $playlistContentUser->duration_seconds,
            'trainingplan_id'   => (int) $playlistContentUser->trainingplan_id,
            'content_id'        => (int) $playlistContentUser->content_id,
        ]);
    }
}
