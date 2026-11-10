<?php

namespace App\Services\Manage\KpOwl;

use App\Support\DurationFormatter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class KpOwlDetailService
{
    /**
     * Resolve the detailed view data for a single KP OWL record.
     *
     * @param string $sourceType 'content' or 'playlist'
     * @param int|string $id
     * @return array
     */
    public function getDetail(string $sourceType, $id): array
    {
        $type = strtolower(trim($sourceType));

        if ($type === 'playlist') {
            return $this->getPlaylistDetail($id);
        }

        return $this->getContentDetail($id);
    }

    /**
     * Resolve standalone content learning record details.
     */
    protected function getContentDetail($id): array
    {
        $record = DB::table('content_users as cu')
            ->join('users as u', 'cu.user_id', '=', 'u.id')
            ->leftJoin('content as c', 'cu.content_id', '=', 'c.id')
            ->leftJoin('classification as cl', 'c.classification_id', '=', 'cl.id')
            ->where('cu.id', $id)
            ->select([
                'cu.id',
                'cu.user_id',
                'cu.content_id',
                'cu.finish',
                'cu.progress',
                'cu.status as requirement_status',
                'cu.learning_hours',
                'cu.current_time',
                'cu.actual_watch_seconds',
                'cu.afk_duration_seconds',
                'cu.created_at',
                'cu.completed_at',
                'cu.updated_at',
                'u.id_emp as employee_id',
                'u.name as employee_name',
                'u.jobgrade as grade',
                'u.jobposition as job_position',
                'u.team',
                'u.department',
                'u.company_name as company',
                'c.title as course_title',
                'c.type as content_type',
                'c.duration as content_duration',
                'c.author as instructor',
                'cl.classification_name',
            ])
            ->first();

        if (!$record) {
            return ['success' => false, 'message' => 'Learning record not found.'];
        }

        return $this->formatOnlineRecord($record, 'content');
    }

    /**
     * Resolve playlist learning record details.
     */
    protected function getPlaylistDetail($id): array
    {
        // $id could be playlist_content_users.id or trainingplan_id
        $record = DB::table('playlist_content_users as pcu')
            ->join('users as u', 'pcu.user_id', '=', 'u.id')
            ->leftJoin('trainingplan as tp', 'pcu.trainingplan_id', '=', 'tp.id')
            ->leftJoin('content as c', 'pcu.content_id', '=', 'c.id')
            ->leftJoin('classification as cl', 'c.classification_id', '=', 'cl.id')
            ->where('pcu.id', $id)
            ->select([
                'pcu.id',
                'pcu.user_id',
                'pcu.content_id',
                'pcu.trainingplan_id',
                'pcu.status as requirement_status',
                'pcu.created_at',
                'pcu.completed_at',
                'pcu.updated_at',
                'u.id_emp as employee_id',
                'u.name as employee_name',
                'u.jobgrade as grade',
                'u.jobposition as job_position',
                'u.team',
                'u.department',
                'u.company_name as company',
                'tp.title as course_title',
                'tp.total_duration_seconds as playlist_duration_seconds',
                'c.author as instructor',
                'cl.classification_name',
            ])
            ->first();

        if (!$record) {
            return ['success' => false, 'message' => 'Playlist record not found.'];
        }

        // Aggregate stats across all contents in this playlist for this user
        $pcuAgg = DB::table('playlist_content_users')
            ->where('user_id', $record->user_id)
            ->where('trainingplan_id', $record->trainingplan_id)
            ->selectRaw('
                COUNT(id) as total_modules,
                SUM(CASE WHEN finish = "1" OR progress >= 100 THEN 1 ELSE 0 END) as finished_modules,
                AVG(COALESCE(progress, 0)) as avg_progress,
                SUM(COALESCE(learning_hour, 0)) as total_lh,
                SUM(COALESCE(actual_watch_seconds, 0)) as total_watch,
                SUM(COALESCE(current_time, 0)) as total_curr,
                SUM(COALESCE(duration_seconds, 0)) as total_duration,
                MIN(created_at) as earliest_start,
                MAX(CASE WHEN finish = "1" OR progress >= 100 THEN completed_at ELSE NULL END) as latest_complete
            ')
            ->first();

        $record->finish = ($pcuAgg->total_modules > 0 && $pcuAgg->finished_modules == $pcuAgg->total_modules) ? '1' : '0';
        $record->progress = round((float) ($pcuAgg->avg_progress ?? 0));
        $record->learning_hours = (float) ($pcuAgg->total_lh ?? 0);
        $record->actual_watch_seconds = (int) ($pcuAgg->total_watch ?? 0);
        $record->current_time = (int) ($pcuAgg->total_curr ?? 0);
        $record->duration_seconds = (int) ($pcuAgg->total_duration ?? 0);
        $record->created_at = $pcuAgg->earliest_start ?? $record->created_at;
        $record->completed_at = $record->finish === '1' ? $pcuAgg->latest_complete : null;
        $record->content_type = 'Playlist';

        return $this->formatOnlineRecord($record, 'playlist');
    }

    /**
     * Format record data into the canonical 17 Detail View fields + section groups.
     */
    protected function formatOnlineRecord(object $record, string $sourceType): array
    {
        $cleanEmpId = trim((string) ($record->employee_id ?: '-'));
        if ($cleanEmpId !== '-') {
            $cleanEmpId = preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', $cleanEmpId);
        }

        $progressNum = (float) ($record->progress ?? 0);
        $isFinished = ($record->finish === '1' || $progressNum >= 100);

        // Course Duration
        $durationSeconds = (int) ($record->duration_seconds ?? 0);
        if ($durationSeconds <= 0 && !empty($record->content_duration)) {
            $durationSeconds = DurationFormatter::toSeconds($record->content_duration) ?? 0;
        }
        $courseDurationFmt = $durationSeconds > 0 ? DurationFormatter::fromSeconds($durationSeconds) : '-';

        // Engagement Seconds & Recognized Duration
        $actualWatch = (int) ($record->actual_watch_seconds ?? 0);
        $currentTime = (int) ($record->current_time ?? 0);
        $learningHours = (float) ($record->learning_hours ?? 0);

        if ($actualWatch > 0) {
            $actualSeconds = $actualWatch;
        } elseif ($currentTime > 0) {
            $actualSeconds = $currentTime;
        } elseif ($isFinished && $durationSeconds > 0) {
            $actualSeconds = $durationSeconds;
        } elseif ($progressNum > 0 && $durationSeconds > 0) {
            $actualSeconds = (int) round(($progressNum / 100) * $durationSeconds);
        } elseif ($learningHours > 0) {
            $actualSeconds = (int) round($learningHours * 3600);
        } else {
            $actualSeconds = 0;
        }

        $maxCap = $durationSeconds > 0 ? ($durationSeconds * 2) : 0;
        $recSecs = ($maxCap > 0) ? min($actualSeconds, $maxCap) : $actualSeconds;
        $recognizedDurationFmt = $recSecs > 0 ? DurationFormatter::fromSeconds($recSecs) : '-';
        $engagementTimeFmt = $actualSeconds > 0 ? DurationFormatter::fromSeconds($actualSeconds) : '-';

        $learningHoursFmt = $learningHours > 0
            ? (($learningHours == (int) $learningHours) ? (int) $learningHours . ' h' : rtrim(rtrim(number_format($learningHours, 2, '.', ''), '0'), '.') . ' h')
            : '0 h';

        // Dates & Times
        $startDate = $record->created_at ? Carbon::parse($record->created_at)->format('Y-m-d') : '-';
        $startTime = $record->created_at ? Carbon::parse($record->created_at)->format('H:i:s') : '-';
        $endDate = ($isFinished && $record->completed_at) ? Carbon::parse($record->completed_at)->format('Y-m-d') : '-';
        $endTime = ($isFinished && $record->completed_at) ? Carbon::parse($record->completed_at)->format('H:i:s') : '-';

        // Assessment Score & Status
        $assessment = $this->resolveAssessment((int) $record->user_id, $sourceType, $record->content_id ?? null, $record->trainingplan_id ?? null);

        // Certificate Details
        $certAvailability = $isFinished ? 'Available' : '-';
        $certInstitution = $isFinished ? 'PT Krakatau Posco' : '-';
        $certIssuedDate = ($isFinished && $endDate !== '-') ? $endDate : '-';
        $certExpiredDate = '-';

        // 17 SPECIFIED DETAIL VIEW FIELDS
        $detailFields = [
            'grade'                    => $record->grade ?: '-',
            'job_position'             => $record->job_position ?: '-',
            'sub_course_title'         => '-',
            'level'                    => '-',
            'batch'                    => '-',
            'sub_provider'             => '-',
            'instructor'               => $record->instructor ?: '-',
            'attendance_reason'        => '-',
            'bond_status'              => 'No',
            'bond_duration'            => '-',
            'bond_end_date'            => '-',
            'bond_amount'              => '-',
            'l2_status'                => $assessment['l2_status'],
            'l3_status'                => '-',
            'certificate_institution'  => $certInstitution,
            'certificate_issued_date'  => $certIssuedDate,
            'certificate_expired_date' => $certExpiredDate,
        ];

        return array_merge([
            'success'     => true,
            'source_type' => $sourceType,
            'source'      => 'KP OWL',
            'employee'    => [
                'id_emp'       => $cleanEmpId,
                'name'         => $record->employee_name ?: '-',
                'grade'        => $record->grade ?: '-',
                'job_position' => $record->job_position ?: '-',
                'team'         => $record->team ?: '-',
                'department'   => $record->department ?: '-',
                'company'      => $record->company ?: 'PT Krakatau Posco',
            ],
            'learning'    => [
                'title'                    => $record->course_title ?: 'Untitled',
                'sub_course_title'         => '-',
                'type'                     => $sourceType === 'playlist' ? 'Playlist' : ($record->content_type ?: 'Document'),
                'category'                 => $sourceType === 'playlist' ? 'Playlist' : 'All Content',
                'main_classification'      => $record->classification_name ?: 'Unclassified',
                'sub_classification'       => '-',
                'level'                    => '-',
                'batch'                    => '-',
                'delivery_method'          => 'Online',
                'provider'                 => 'Internal',
                'sub_provider'             => '-',
                'provider_name'            => 'KP-OWL',
                'venue'                    => 'Online LMS',
                'instructor'               => $record->instructor ?: '-',
                'course_duration'          => $courseDurationFmt,
                'total_recognized_duration'=> $recognizedDurationFmt,
                'learning_hours'           => $learningHoursFmt,
                'total_engagement_time'    => $engagementTimeFmt,
                'progress'                 => $progressNum,
                'progress_status'          => $isFinished ? 'Finished' : ($progressNum > 0 ? 'Ongoing' : 'Not Started'),
                'requirement'              => strtolower((string) ($record->requirement_status ?? '')) === 'mandatory' ? 'Mandatory' : 'Non-Mandatory',
            ],
            'attendance'  => [
                'attendance'        => ($progressNum > 0 || $isFinished) ? 'Yes' : 'No',
                'attendance_reason' => '-',
                'start_date'        => $startDate,
                'end_date'          => $endDate,
                'start_time'        => $startTime,
                'end_time'          => $endTime,
                'period'            => ($startDate !== '-' && $endDate !== '-') ? "{$startDate} - {$endDate}" : ($startDate !== '-' ? $startDate : '-'),
            ],
            'bond'        => [
                'status'   => 'No',
                'duration' => '-',
                'end_date' => '-',
                'amount'   => '-',
            ],
            'assessment'  => [
                'score_l1'  => '-',
                'score_l2'  => $assessment['score_l2'],
                'l2_status' => $assessment['l2_status'],
                'score_l3'  => '-',
                'l3_status' => '-',
            ],
            'certificate' => [
                'availability' => $certAvailability,
                'institution'  => $certInstitution,
                'issued_date'  => $certIssuedDate,
                'expired_date' => $certExpiredDate,
            ],
        ], $detailFields);
    }

    /**
     * Resolve Assessment L2 score and status for user and content/playlist.
     */
    protected function resolveAssessment(int $userId, string $sourceType, ?int $contentId, ?int $trainingplanId): array
    {
        $q = DB::table('assesment_results as ar')
            ->join('assesments as a', 'a.id', '=', 'ar.assesment_id')
            ->where('ar.user_id', $userId)
            ->where('ar.final', 1);

        if ($sourceType === 'playlist') {
            $q->where(function ($sub) use ($contentId, $trainingplanId) {
                if ($trainingplanId) {
                    $sub->where('a.trainingplan_id', $trainingplanId);
                }
                if ($contentId) {
                    $sub->orWhere('a.content_id', $contentId);
                }
            });
        } elseif ($contentId) {
            $q->where('a.content_id', $contentId);
        } else {
            return ['score_l2' => '-', 'l2_status' => '-'];
        }

        $res = $q->orderByDesc('ar.score')->select(['ar.score', 'ar.pass'])->first();
        if (!$res) {
            return ['score_l2' => '-', 'l2_status' => '-'];
        }

        $score = (float) $res->score;
        $pass = ($res->pass == 1 || $score >= 70);

        return [
            'score_l2'  => number_format($score, 1),
            'l2_status' => $pass ? 'Passed' : 'Failed',
        ];
    }
}
