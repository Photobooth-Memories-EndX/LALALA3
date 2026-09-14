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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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
            'delta_seconds'        => 'nullable|numeric|min:0|max:120',
            'heartbeat_id'         => 'nullable|string|max:64',
            'afk_seconds'          => 'nullable|numeric|min:0',
            'afk_duration_seconds' => 'nullable|numeric|min:0',
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
        $playlistContentUser->setRelation('content', $content);

        $previousProgress = (int) $playlistContentUser->progress;
        $computed = null;
        $pageCompleted = null;
        $remainingSeconds = null;
        $allowedPage = null;
        $deltaToApply = 0;

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

            // Actual watch time tracking (MVP Hardened)
            $deltaSeconds = $validated['delta_seconds'] ?? null;
            $deltaToApply = 0;
            if ($deltaSeconds !== null && (float) $deltaSeconds > 0) {
                $heartbeatId = $validated['heartbeat_id'] ?? null;
                $userId = (int) Auth::id();
                $tpId = (int) $trainingplan->id;
                $contentId = (int) $content->id;

                // 1. Idempotency: verify heartbeat_id via Cache (TTL: 10 mins)
                $hbCacheKey = $heartbeatId !== null ? "playlist_video_hb:{$userId}:{$tpId}:{$contentId}:{$heartbeatId}" : null;
                $sessionKey = 'learning.playlist_video_last_hb.' . $trainingplan->id . '.' . $content->id;
                $lastHeartbeatSession = $request->hasSession() ? $request->session()->get($sessionKey) : null;

                $isDuplicate = false;
                if ($hbCacheKey !== null && Cache::has($hbCacheKey)) {
                    $isDuplicate = true;
                } elseif ($heartbeatId !== null && is_array($lastHeartbeatSession) && ($lastHeartbeatSession['id'] ?? null) === $heartbeatId) {
                    $isDuplicate = true;
                }

                if (!$isDuplicate) {
                    // 2. Server elapsed-time validation
                    $nowTimestamp = (float) (now()->getPreciseTimestamp(3) / 1000);
                    $timeCacheKey = "playlist_video_last_hb_time:{$userId}:{$tpId}:{$contentId}";
                    $lastHeartbeatTime = Cache::get($timeCacheKey);

                    if ($lastHeartbeatTime !== null) {
                        $serverElapsed = max(0.0, $nowTimestamp - (float) $lastHeartbeatTime);
                        if ($serverElapsed <= 300.0) {
                            $tolerance = $serverElapsed < 5.0 ? 2.0 : 4.0;
                            $maxAllowedDelta = min(30.0, max(1.0, $serverElapsed + $tolerance));
                        } else {
                            // Reconnected after long pause (> 5 min)
                            $maxAllowedDelta = 30.0;
                        }
                    } else {
                        // First heartbeat of tracking session
                        $maxAllowedDelta = 30.0;
                    }

                    $clampedDelta = min((float) $deltaSeconds, $maxAllowedDelta);
                    $deltaToApply = (int) round($clampedDelta);

                    if ($hbCacheKey !== null) {
                        Cache::put($hbCacheKey, true, now()->addMinutes(10));
                    }
                    Cache::put($timeCacheKey, $nowTimestamp, now()->addMinutes(30));

                    if ($request->hasSession()) {
                        $request->session()->put($sessionKey, [
                            'id' => $heartbeatId,
                            'time' => $nowTimestamp,
                        ]);
                    }
                }
            }
        }

        // Atomic update and concurrency protection via DB transaction and lockForUpdate
        DB::transaction(function () use (
            $playlistContentUser,
            $validated,
            $computed,
            $previousProgress,
            $deltaToApply
        ) {
            $record = PlaylistContentUser::where('id', $playlistContentUser->id)->lockForUpdate()->first();
            if (!$record) {
                return;
            }

            if ($validated['type'] === 'video') {
                $recordDuration = (float) ($validated['duration_seconds'] ?? $record->duration_seconds ?? 0);
                if ($recordDuration > 0) {
                    $record->duration_seconds = (int) round($recordDuration);
                }

                $recordCurrentTime = $validated['current_time'] ?? null;
                if ($recordCurrentTime !== null) {
                    $recordCurrentTime = (float) $recordCurrentTime;
                    if ($recordDuration > 0) {
                        $recordCurrentTime = min($recordCurrentTime, $recordDuration);
                    }
                    $record->current_time = (int) round($recordCurrentTime);
                }

                if ($deltaToApply > 0) {
                    $currentActualWatch = (int) ($record->actual_watch_seconds ?? 0);
                    // Legacy baseline logic (safe & conservative, at most once)
                    if ($currentActualWatch === 0 && !$record->wasRecentlyCreated && (int) $record->getOriginal('current_time') > 0) {
                        $maxBaseline = $recordDuration > 0 ? (int) round($recordDuration) : (int) $record->getOriginal('current_time');
                        $currentActualWatch = min((int) $record->getOriginal('current_time'), $maxBaseline);
                    }

                    $record->actual_watch_seconds = $currentActualWatch + $deltaToApply;
                }
            } else {
                // Document sync
                if ($playlistContentUser->current_time !== null) {
                    $record->current_time = (int) $playlistContentUser->current_time;
                }
                if ($playlistContentUser->duration_seconds !== null) {
                    $record->duration_seconds = (int) $playlistContentUser->duration_seconds;
                }
                if ($playlistContentUser->last_page !== null) {
                    $record->last_page = (int) $playlistContentUser->last_page;
                }
                if ($playlistContentUser->total_pages !== null) {
                    $record->total_pages = (int) $playlistContentUser->total_pages;
                }
            }

            // Monotonic rule: progress never decreases
            $lockedPrevProgress = (int) $record->progress;
            $progress = $lockedPrevProgress;
            if ($computed !== null) {
                $progress = max($lockedPrevProgress, max(0, min(100, (int) round($computed))));
            }

            if ($validated['type'] === 'video'
                && ($validated['status'] ?? null) === 'completed'
                && $computed !== null
                && $computed >= 90) {
                $progress = 100;
            }

            $record->progress = $progress;

            if ($progress >= 100) {
                $record->finish = '1';
                $record->learning_status = 'completed';
            } elseif (($validated['status'] ?? null) === 'paused') {
                $record->learning_status = 'paused';
            } elseif (($validated['status'] ?? null) === 'in_progress' || $computed !== null) {
                $record->learning_status = 'in_progress';
            }

            $afkDuration = $validated['afk_duration_seconds'] ?? $validated['afk_seconds'] ?? null;
            if ($afkDuration !== null && (float) $afkDuration > 0) {
                $record->afk_duration_seconds = max((int) ($record->afk_duration_seconds ?? 0), (int) round((float) $afkDuration));
            }

            $record->save();

            $playlistContentUser->refresh();
        });

        return response()->json([
            'success'              => true,
            'progress'             => (int) $playlistContentUser->progress,
            'status'               => $playlistContentUser->learning_status,
            'finish'               => (int) $playlistContentUser->finish,
            'current_time'         => (int) $playlistContentUser->current_time,
            'actual_watch_seconds' => (int) ($playlistContentUser->actual_watch_seconds ?? 0),
            'learning_hour'        => (float) $playlistContentUser->learning_hour,
            'last_page'            => $playlistContentUser->last_page,
            'page_completed'       => $pageCompleted,
            'remaining_seconds'    => $remainingSeconds,
            'allowed_page'         => $allowedPage,
            'duration_seconds'     => $playlistContentUser->duration_seconds,
            'afk_duration_seconds' => (int) ($playlistContentUser->afk_duration_seconds ?? 0),
            'trainingplan_id'      => (int) $playlistContentUser->trainingplan_id,
            'content_id'           => (int) $playlistContentUser->content_id,
        ]);
    }
}
