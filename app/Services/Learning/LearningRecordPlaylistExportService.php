<?php

namespace App\Services\Learning;

use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlanDetail;
use App\Support\ContentTypeHelper;
use App\Support\DurationFormatter;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LearningRecordPlaylistExportService
{
    /**
     * Fetch children contents for given playlist IDs in batch (Zero N+1).
     *
     * @param int $userId
     * @param array<int> $playlistIds
     * @return Collection<int, Collection<int, array>> Keyed by trainingplan_id
     */
    public function getPlaylistChildrenBatch(int $userId, array $playlistIds): Collection
    {
        $playlistIds = array_values(array_filter(array_unique(array_map('intval', $playlistIds))));
        if (empty($playlistIds)) {
            return collect();
        }

        // 1. Eager load TrainingPlanDetail with content and classification
        $details = TrainingPlanDetail::with([
            'content' => fn ($q) => $q->withTrashed(),
            'content.classification',
        ])
        ->whereIn('trainingplan_id', $playlistIds)
        ->orderByRaw('CAST(content_order AS UNSIGNED) ASC')
        ->get();

        $contentIds = $details->pluck('content_id')->filter()->unique()->values()->all();

        // 2. Eager load PlaylistContentUser records for user
        $pcuMap = !empty($contentIds)
            ? PlaylistContentUser::where('user_id', $userId)
                ->whereIn('trainingplan_id', $playlistIds)
                ->whereIn('content_id', $contentIds)
                ->get()
                ->keyBy(fn ($item) => $item->trainingplan_id . '_' . $item->content_id)
            : collect();

        // 3. Eager load historical ContentUser records for user as fallback
        $cuMap = !empty($contentIds)
            ? ContentUser::where('user_id', $userId)
                ->whereIn('content_id', $contentIds)
                ->get()
                ->keyBy('content_id')
            : collect();

        // 4. Batch query assessment scores if any
        $scoresMap = !empty($contentIds)
            ? DB::table('assesment_results as r')
                ->join('assesments as a', 'a.id', '=', 'r.assesment_id')
                ->where('r.user_id', $userId)
                ->where('r.final', 1)
                ->where('a.status', '!=', 'NOT')
                ->whereIn('a.content_id', $contentIds)
                ->select(
                    'a.content_id',
                    DB::raw('MAX(r.score) as max_score')
                )
                ->groupBy('a.content_id')
                ->pluck('max_score', 'a.content_id')
            : collect();

        // 5. Map details grouped by playlist_id
        $result = collect();

        foreach ($details->groupBy('trainingplan_id') as $planId => $planDetails) {
            $children = collect();

            foreach ($planDetails as $d) {
                $content = $d->content;
                if (!$content) {
                    continue;
                }

                $cid = $d->content_id;
                $pcu = $pcuMap->get($planId . '_' . $cid);
                $cu = $cuMap->get($cid);

                $durationSeconds = DurationFormatter::toSeconds($content->duration ?? '') ?? 0;

                // Resolve consumption and progress following LearningRecordLearningHourService
                if ($pcu) {
                    $pcuDuration = (int) ($pcu->duration_seconds ?? 0);
                    if ($pcuDuration <= 0) {
                        $pcuDuration = $durationSeconds;
                    }
                    $pcuActualWatch = (int) ($pcu->actual_watch_seconds ?? 0);
                    $pcuCurrentTime = (int) ($pcu->current_time ?? 0);
                    $pcuProgress = (int) ($pcu->progress ?? 0);
                    $pcuFinished = ((string) $pcu->finish === '1' || $pcuProgress >= 100);

                    $moduleSec = 0;
                    if ($pcuActualWatch > 0) {
                        $moduleSec = $pcuActualWatch;
                    } elseif ($pcuCurrentTime > 0) {
                        $moduleSec = $pcuCurrentTime;
                    } elseif ($pcuFinished && $pcuDuration > 0) {
                        $moduleSec = $pcuDuration;
                    } elseif ($pcuProgress > 0 && $pcuDuration > 0) {
                        $moduleSec = (int) round(($pcuProgress / 100) * $pcuDuration);
                    } elseif ((float) ($pcu->learning_hour ?? 0) > 0) {
                        $moduleSec = (int) round((float) $pcu->learning_hour * 3600);
                    }

                    if ($content->max_actual_watch_seconds !== null) {
                        $moduleSec = min($moduleSec, $content->max_actual_watch_seconds);
                    }

                    $childProgress = $pcuFinished ? 100 : $pcuProgress;
                    $startDt = $pcu->created_at;
                    $finishDt = $pcuFinished ? ($pcu->completed_at ?? $pcu->updated_at) : null;
                } elseif ($cu) {
                    $cuDuration = (int) ($cu->duration_seconds ?? 0);
                    if ($cuDuration <= 0) {
                        $cuDuration = $durationSeconds;
                    }
                    $cuActualWatch = (int) ($cu->actual_watch_seconds ?? 0);
                    $cuCurrentTime = (int) ($cu->current_time ?? 0);
                    $cuProgress = (int) ($cu->progress ?? 0);
                    $cuFinished = ((string) $cu->finish === '1' || $cuProgress >= 100);

                    $moduleSec = 0;
                    if ($cuActualWatch > 0) {
                        $moduleSec = $cuActualWatch;
                    } elseif ($cuCurrentTime > 0) {
                        $moduleSec = $cuCurrentTime;
                    } elseif ($cuFinished && $cuDuration > 0) {
                        $moduleSec = $cuDuration;
                    } elseif ($cuProgress > 0 && $cuDuration > 0) {
                        $moduleSec = (int) round(($cuProgress / 100) * $cuDuration);
                    } elseif ((float) ($cu->learning_hours ?? 0) > 0) {
                        $moduleSec = (int) round((float) $cu->learning_hours * 3600);
                    }

                    if ($content->max_actual_watch_seconds !== null) {
                        $moduleSec = min($moduleSec, $content->max_actual_watch_seconds);
                    }

                    $childProgress = $cuFinished ? 100 : $cuProgress;
                    $startDt = $cu->created_at;
                    $finishDt = $cuFinished ? ($cu->completed_at ?? $cu->updated_at) : null;
                } else {
                    // Fallback: playlist completed in Learning Record
                    $moduleSec = $durationSeconds;
                    $childProgress = 100;
                    $startDt = null;
                    $finishDt = null;
                }

                $childHoursVal = round($moduleSec / 3600, 4);
                $durationFormatted = DurationFormatter::fromSeconds($durationSeconds);

                $childRecognizedSec = ($durationSeconds > 0) ? min($moduleSec, $durationSeconds * 2) : $moduleSec;
                $recognizedDurationFormatted = DurationFormatter::fromSeconds($childRecognizedSec);

                $learningHourFormatted = $childHoursVal > 0 ? (number_format($childHoursVal, 2) . ' h') : '-';
                $attendance = ($childProgress >= 100) ? 'Yes' : 'No';

                // Format dates & times
                $startStr = $startDt ? Carbon::parse($startDt)->format('d/m/y H:i:s') : '';
                $finishStr = $finishDt ? Carbon::parse($finishDt)->format('d/m/y H:i:s') : '';
                $startDateOnly = $startDt ? Carbon::parse($startDt)->format('Y-m-d') : '-';
                $endDateOnly = $finishDt ? Carbon::parse($finishDt)->format('Y-m-d') : '-';
                $startTimeOnly = $startDt ? Carbon::parse($startDt)->format('H:i:s') : '-';
                $endTimeOnly = $finishDt ? Carbon::parse($finishDt)->format('H:i:s') : '-';

                if ($startStr !== '' && $finishStr !== '') {
                    $dateTimeKp = "Start: {$startStr}\nFinish: {$finishStr}";
                } elseif ($finishStr !== '') {
                    $dateTimeKp = "Finish: {$finishStr}";
                } elseif ($startStr !== '') {
                    $dateTimeKp = "Start: {$startStr}";
                } else {
                    $dateTimeKp = '-';
                }

                $dateDisplayAll = $finishDt
                    ? Carbon::parse($finishDt)->format('d M Y')
                    : ($startDt ? Carbon::parse($startDt)->format('d M Y') : '—');

                $score = $scoresMap->get($cid);
                $hasScore = ($score !== null && $score !== '');
                $scoreDisplay = $hasScore ? (float) $score : '-';
                $assessmentStatus = $hasScore
                    ? (((float) $score >= 70) ? 'Passed' : 'Failed')
                    : '-';
                $completionStatus = $childProgress >= 100 ? 'Complete' : 'Ongoing';
                $requirement = \App\Models\Content::mapRequirement($content->status ?? ($cu?->status ?? null));

                $children->push([
                    'content_id'          => $cid,
                    'title'               => $content->title ?? '-',
                    'requirement'         => $requirement,
                    'type'                => ContentTypeHelper::label($content->type) ?: 'Content',
                    'category'            => 'Playlist',
                    'classification'      => $content->classification?->classification_name ?? '-',
                    'duration'            => $durationFormatted,
                    'duration_seconds'    => $durationSeconds,
                    'recognized_duration' => $recognizedDurationFormatted,
                    'recognized_duration_seconds' => $childRecognizedSec,
                    'total_recognized_duration'   => $recognizedDurationFormatted,
                    'learning_hour'       => $learningHourFormatted,
                    'learning_hour_value' => $childHoursVal,
                    'learning_hours'      => $childHoursVal,
                    'progress'            => $childProgress,
                    'progress_formatted'  => $childProgress . '%',
                    'completion_status'   => $completionStatus,
                    'attendance'          => $attendance,
                    'start_date'          => $startDateOnly,
                    'end_date'            => $endDateOnly,
                    'start_time'          => $startTimeOnly,
                    'end_time'            => $endTimeOnly,
                    'date_time_kp'        => $dateTimeKp,
                    'date_display_all'    => $dateDisplayAll,
                    'assessment_status'   => $assessmentStatus,
                    'score'               => $scoreDisplay,
                    'certificate'         => '-',
                ]);
            }

            $result->put((int) $planId, $children);
        }

        return $result;
    }
}
