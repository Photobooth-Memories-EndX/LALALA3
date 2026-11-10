<?php

namespace App\Services\Manage;

use Illuminate\Support\Facades\DB;

class RecordDetailService
{
    /**
     * Resolve the 18 Detail View fields for a single learning record.
     *
     * @param string $source 'online', 'offline', 'kp-owl', 'non-kp-owl', 'content', 'playlist'
     * @param string|int $id
     * @return array
     */
    public function getDetail(string $source, $id): array
    {
        $normalized = $this->normalizeSourceAndId($source, $id);
        $sourceType = $normalized['source_type'];
        $recordId = $normalized['id'];

        if ($sourceType === 'offline') {
            return $this->getOfflineDetail($recordId);
        }

        return $this->getOnlineDetail($recordId, $sourceType);
    }

    /**
     * Normalize source and ID, handling composite formats like 'offline:123' or 'online:content:456'.
     */
    protected function normalizeSourceAndId(string $source, $id): array
    {
        $src = strtolower(trim($source));

        if (is_string($id) && strpos($id, ':') !== false) {
            $parts = explode(':', $id);
            $cleanId = end($parts);
            if (in_array('offline', $parts, true)) {
                $src = 'offline';
            } elseif (in_array('online', $parts, true) || in_array('content', $parts, true) || in_array('playlist', $parts, true)) {
                $src = in_array('playlist', $parts, true) ? 'playlist' : 'online';
            }
            $id = $cleanId;
        }

        $isOffline = in_array($src, ['offline', 'non-kp-owl', 'non_kp_owl', 'non kp owl'], true);

        return [
            'source_type' => $isOffline ? 'offline' : ($src === 'playlist' ? 'playlist' : 'online'),
            'id'          => $id,
        ];
    }

    /**
     * Resolve offline record details (from offline_training_users JOIN offline_trainings).
     */
    protected function getOfflineDetail($id): array
    {
        $record = DB::table('offline_training_users as otu')
            ->leftJoin('offline_trainings as ot', 'otu.offline_training_id', '=', 'ot.id')
            ->where('otu.id', $id)
            ->select([
                'otu.id',
                'otu.user_id',
                'otu.employee_id',
                'otu.name as employee_name',
                'otu.grade',
                'otu.job_position',
                'otu.team',
                'otu.department',
                'otu.company',
                'otu.attendance',
                'otu.attendance_reason',
                'otu.progress',
                'otu.bond_status',
                'otu.bond_duration',
                'otu.bond_end_date',
                'otu.bond_amount_idr',
                'otu.l1_program',
                'otu.l2_numerical',
                'otu.l2_verbal',
                'otu.l3_numerical',
                'otu.l3_verbal',
                'otu.assessment_score',
                'otu.pic as otu_pic',
                'otu.certificate',
                'ot.id as training_id',
                'ot.course_name',
                'ot.title as training_title',
                'ot.sub_course_name',
                'ot.type',
                'ot.category',
                'ot.level',
                'ot.batch',
                'ot.provider',
                'ot.sub_provider',
                'ot.provider_name',
                'ot.instructor',
                'ot.created_by',
            ])
            ->first();

        // Fallback: check if $id was an offline_training_id
        if (!$record) {
            $record = DB::table('offline_training_users as otu')
                ->leftJoin('offline_trainings as ot', 'otu.offline_training_id', '=', 'ot.id')
                ->where('otu.offline_training_id', $id)
                ->select([
                    'otu.id',
                    'otu.user_id',
                    'otu.employee_id',
                    'otu.name as employee_name',
                    'otu.grade',
                    'otu.job_position',
                    'otu.team',
                    'otu.department',
                    'otu.company',
                    'otu.attendance',
                    'otu.attendance_reason',
                    'otu.progress',
                    'otu.bond_status',
                    'otu.bond_duration',
                    'otu.bond_end_date',
                    'otu.bond_amount_idr',
                    'otu.l1_program',
                    'otu.l2_numerical',
                    'otu.l2_verbal',
                    'otu.l3_numerical',
                    'otu.l3_verbal',
                    'otu.assessment_score',
                    'otu.pic as otu_pic',
                    'otu.certificate',
                    'ot.id as training_id',
                    'ot.course_name',
                    'ot.title as training_title',
                    'ot.sub_course_name',
                    'ot.type',
                    'ot.category',
                    'ot.level',
                    'ot.batch',
                    'ot.provider',
                    'ot.sub_provider',
                    'ot.provider_name',
                    'ot.instructor',
                    'ot.created_by',
                ])
                ->first();
        }

        if (!$record) {
            // Check offline_trainings directly
            $training = DB::table('offline_trainings')->where('id', $id)->first();
            if ($training) {
                return $this->formatOfflineTrainingFallback($training);
            }

            return $this->emptyDetail('Non KP OWL');
        }

        // Clean Employee ID
        $cleanEmpId = trim((string) ($record->employee_id ?: '-'));
        if ($cleanEmpId !== '-') {
            $cleanEmpId = preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', $cleanEmpId);
        }

        // Service Bond formatting
        $bondRaw = strtolower(trim((string) $record->bond_status));
        $hasBond = in_array($bondRaw, ['yes', '1', 'true', 'ya'], true);
        $bondStatus = $hasBond ? 'Yes' : 'No';

        $bondDuration = '-';
        if ($hasBond && !empty($record->bond_duration)) {
            $bondDuration = is_numeric($record->bond_duration) ? $record->bond_duration . ' Months' : $record->bond_duration;
        }

        $bondEndDate = '-';
        if ($hasBond && !empty($record->bond_end_date) && $record->bond_end_date !== '-') {
            $bondEndDate = date('Y-m-d', strtotime($record->bond_end_date));
        }

        $bondAmount = '-';
        if ($hasBond && $record->bond_amount_idr !== null && (float) $record->bond_amount_idr > 0) {
            $bondAmount = 'Rp ' . number_format((float) $record->bond_amount_idr, 0, ',', '.');
        }

        // L2 Status
        $l2 = trim((string) ($record->l2_verbal ?? ''));
        if ($l2 === '') {
            $scoreL2 = $record->assessment_score ?? $record->l2_numerical;
            if (is_numeric($scoreL2)) {
                $l2 = ((float) $scoreL2 >= 70) ? 'Pass' : (((float) $scoreL2 > 0) ? 'Fail' : '-');
            } else {
                $l2 = '-';
            }
        }

        // L3 Status
        $l3 = trim((string) ($record->l3_verbal ?? ''));
        if ($l3 === '') {
            if (is_numeric($record->l3_numerical)) {
                $l3 = ((float) $record->l3_numerical >= 70) ? 'Pass' : (((float) $record->l3_numerical > 0) ? 'Fail' : '-');
            } else {
                $l3 = '-';
            }
        }

        // Certificate lookup
        $cert = DB::table('certificates as cert')
            ->leftJoin('certificate_settings as cs', 'cert.certificate_setting_id', '=', 'cs.id')
            ->where('cert.offline_training_user_id', $record->id)
            ->select('cert.issued_at', 'cs.issuer_name')
            ->first();

        $certInstitution = $cert?->issuer_name ?: ($record->certificate ?: ($record->sub_provider ?: ($record->provider_name ?: '-')));
        $certIssuedDate = $cert?->issued_at ? date('Y-m-d', strtotime($cert->issued_at)) : '-';
        $certExpiredDate = '-';

        // PIC
        $pic = !empty($record->otu_pic) ? $record->otu_pic : null;
        if (!$pic && !empty($record->created_by)) {
            $pic = DB::table('users')->where('id', $record->created_by)->value('name');
        }

        return [
            'success'                  => true,
            'source'                   => 'Non KP OWL',
            'course_title'             => $record->course_name ?: ($record->training_title ?: '-'),
            'employee_id'              => $cleanEmpId,
            'employee_name'            => $record->employee_name ?: '-',
            'grade'                    => $record->grade ?: '-',
            'job_position'             => $record->job_position ?: '-',
            'sub_course_title'         => $record->sub_course_name ?: '-',
            'level'                    => $record->level ?: '-',
            'batch'                    => $record->batch ?: '-',
            'sub_provider'             => $record->sub_provider ?: '-',
            'instructor'               => $record->instructor ?: '-',
            'attendance_reason'        => $record->attendance_reason ?: '-',
            'bond_status'              => $bondStatus,
            'bond_duration'            => $bondDuration,
            'bond_end_date'            => $bondEndDate,
            'bond_amount'              => $bondAmount,
            'l2_status'                => $l2,
            'l3_status'                => $l3,
            'certificate_institution'  => $certInstitution ?: '-',
            'certificate_issued_date'  => $certIssuedDate,
            'certificate_expired_date' => $certExpiredDate,
            'pic'                      => $pic ?: '-',
        ];
    }

    /**
     * Resolve online record details (content_users or playlist_content_users).
     */
    protected function getOnlineDetail($id, string $sourceType): array
    {
        // 1. Check content_users first if sourceType is not strictly 'playlist'
        if ($sourceType !== 'playlist') {
            $cu = DB::table('content_users as cu')
                ->join('users as u', 'cu.user_id', '=', 'u.id')
                ->leftJoin('content as c', 'cu.content_id', '=', 'c.id')
                ->where('cu.id', $id)
                ->select([
                    'cu.id',
                    'cu.user_id',
                    'cu.content_id',
                    'cu.progress',
                    'cu.finish',
                    'cu.completed_at',
                    'u.id_emp as employee_id',
                    'u.name as employee_name',
                    'u.jobgrade as grade',
                    'u.jobposition as job_position',
                    'c.title as course_title',
                    'c.author as instructor',
                ])
                ->first();

            if ($cu) {
                return $this->formatContentUserDetail($cu);
            }
        }

        // 2. Check playlist_content_users
        $pcu = DB::table('playlist_content_users as pcu')
            ->join('users as u', 'pcu.user_id', '=', 'u.id')
            ->leftJoin('content as c', 'pcu.content_id', '=', 'c.id')
            ->leftJoin('trainingplan as tp', 'pcu.trainingplan_id', '=', 'tp.id')
            ->where('pcu.id', $id)
            ->select([
                'pcu.id',
                'pcu.user_id',
                'pcu.content_id',
                'pcu.trainingplan_id',
                'pcu.progress',
                'pcu.finish',
                'pcu.completed_at',
                'u.id_emp as employee_id',
                'u.name as employee_name',
                'u.jobgrade as grade',
                'u.jobposition as job_position',
                'c.title as content_title',
                'tp.title as playlist_title',
                DB::raw("NULL as playlist_instructor"),
                'c.author as content_author',
            ])
            ->first();

        if ($pcu) {
            return $this->formatPlaylistUserDetail($pcu);
        }

        return $this->emptyDetail('KP OWL');
    }

    protected function formatContentUserDetail($cu): array
    {
        $cleanEmpId = trim((string) ($cu->employee_id ?: '-'));
        if ($cleanEmpId !== '-') {
            $cleanEmpId = preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', $cleanEmpId);
        }

        // Assessment L2 Status lookup
        $ar = DB::table('assesment_results as ar')
            ->join('assesments as a', 'a.id', '=', 'ar.assesment_id')
            ->where('ar.user_id', $cu->user_id)
            ->where('a.content_id', $cu->content_id)
            ->where('ar.final', 1)
            ->orderByDesc('ar.score')
            ->select('ar.score', 'ar.pass')
            ->first();

        $l2Status = '-';
        if ($ar) {
            $l2Status = ($ar->pass == 1 || (float)$ar->score >= 70) ? 'Pass' : 'Fail';
        }

        // Certificate lookup
        $cert = DB::table('certificates as cert')
            ->leftJoin('certificate_settings as cs', 'cert.certificate_setting_id', '=', 'cs.id')
            ->where('cert.user_id', $cu->user_id)
            ->where(function ($q) use ($cu) {
                $q->where('cert.content_user_id', $cu->id)
                  ->orWhere(function ($sub) use ($cu) {
                      $sub->where('cert.source_id', $cu->content_id)->where('cert.certificate_type', 'content');
                  });
            })
            ->select('cert.issued_at', 'cs.issuer_name')
            ->first();

        $isFinished = ($cu->finish == '1' || (float) $cu->progress >= 100);
        $certInstitution = $cert?->issuer_name ?: ($isFinished ? 'PT Krakatau Posco' : '-');
        $certIssuedDate = $cert?->issued_at ? date('Y-m-d', strtotime($cert->issued_at)) : ($cu->completed_at ? date('Y-m-d', strtotime($cu->completed_at)) : '-');
        $certExpiredDate = '-';

        return [
            'success'                  => true,
            'source'                   => 'KP OWL',
            'course_title'             => $cu->course_title ?: 'Online Content',
            'employee_id'              => $cleanEmpId,
            'employee_name'            => $cu->employee_name ?: '-',
            'grade'                    => $cu->grade ?: '-',
            'job_position'             => $cu->job_position ?: '-',
            'sub_course_title'         => '-',
            'level'                    => '-',
            'batch'                    => '-',
            'sub_provider'             => '-',
            'instructor'               => $cu->instructor ?: '-',
            'attendance_reason'        => '-',
            'bond_status'              => 'No',
            'bond_duration'            => '-',
            'bond_end_date'            => '-',
            'bond_amount'              => '-',
            'l2_status'                => $l2Status,
            'l3_status'                => '-',
            'certificate_institution'  => $certInstitution,
            'certificate_issued_date'  => $certIssuedDate,
            'certificate_expired_date' => $certExpiredDate,
            'pic'                      => '-',
        ];
    }

    protected function formatPlaylistUserDetail($pcu): array
    {
        $cleanEmpId = trim((string) ($pcu->employee_id ?: '-'));
        if ($cleanEmpId !== '-') {
            $cleanEmpId = preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', $cleanEmpId);
        }

        $ar = DB::table('assesment_results as ar')
            ->join('assesments as a', 'a.id', '=', 'ar.assesment_id')
            ->where('ar.user_id', $pcu->user_id)
            ->where(function ($q) use ($pcu) {
                if ($pcu->trainingplan_id) {
                    $q->where('a.trainingplan_id', $pcu->trainingplan_id);
                }
                if ($pcu->content_id) {
                    $q->orWhere('a.content_id', $pcu->content_id);
                }
            })
            ->where('ar.final', 1)
            ->orderByDesc('ar.score')
            ->select('ar.score', 'ar.pass')
            ->first();

        $l2Status = '-';
        if ($ar) {
            $l2Status = ($ar->pass == 1 || (float)$ar->score >= 70) ? 'Pass' : 'Fail';
        }

        $cert = DB::table('certificates as cert')
            ->leftJoin('certificate_settings as cs', 'cert.certificate_setting_id', '=', 'cs.id')
            ->where('cert.user_id', $pcu->user_id)
            ->where(function ($q) use ($pcu) {
                if ($pcu->trainingplan_id) {
                    $q->where('cert.source_id', $pcu->trainingplan_id)->where('cert.certificate_type', 'playlist');
                }
                if ($pcu->content_id) {
                    $q->orWhere(function ($sub) use ($pcu) {
                        $sub->where('cert.source_id', $pcu->content_id)->where('cert.certificate_type', 'content');
                    });
                }
            })
            ->select('cert.issued_at', 'cs.issuer_name')
            ->first();

        $isFinished = ($pcu->finish == '1' || (float) $pcu->progress >= 100);
        $certInstitution = $cert?->issuer_name ?: ($isFinished ? 'PT Krakatau Posco' : '-');
        $certIssuedDate = $cert?->issued_at ? date('Y-m-d', strtotime($cert->issued_at)) : ($pcu->completed_at ? date('Y-m-d', strtotime($pcu->completed_at)) : '-');
        $certExpiredDate = '-';

        return [
            'success'                  => true,
            'source'                   => 'KP OWL',
            'course_title'             => $pcu->playlist_title ?: ($pcu->content_title ?: 'Playlist'),
            'employee_id'              => $cleanEmpId,
            'employee_name'            => $pcu->employee_name ?: '-',
            'grade'                    => $pcu->grade ?: '-',
            'job_position'             => $pcu->job_position ?: '-',
            'sub_course_title'         => '-',
            'level'                    => '-',
            'batch'                    => '-',
            'sub_provider'             => '-',
            'instructor'               => $pcu->playlist_instructor ?: ($pcu->content_author ?: '-'),
            'attendance_reason'        => '-',
            'bond_status'              => 'No',
            'bond_duration'            => '-',
            'bond_end_date'            => '-',
            'bond_amount'              => '-',
            'l2_status'                => $l2Status,
            'l3_status'                => '-',
            'certificate_institution'  => $certInstitution,
            'certificate_issued_date'  => $certIssuedDate,
            'certificate_expired_date' => $certExpiredDate,
            'pic'                      => '-',
        ];
    }

    protected function formatOfflineTrainingFallback($training): array
    {
        return $this->emptyDetail('Non KP OWL', [
            'success'          => true,
            'course_title'     => $training->course_name ?: ($training->title ?: 'Offline Training'),
            'sub_course_title' => $training->sub_course_name ?: '-',
            'level'            => $training->level ?: '-',
            'batch'            => $training->batch ?: '-',
            'sub_provider'     => $training->sub_provider ?: '-',
            'instructor'       => $training->instructor ?: '-',
        ]);
    }

    protected function emptyDetail(string $source, array $overrides = []): array
    {
        return array_merge([
            'success'                  => false,
            'source'                   => $source,
            'course_title'             => '-',
            'employee_id'              => '-',
            'employee_name'            => '-',
            'grade'                    => '-',
            'job_position'             => '-',
            'sub_course_title'         => '-',
            'level'                    => '-',
            'batch'                    => '-',
            'sub_provider'             => '-',
            'instructor'               => '-',
            'attendance_reason'        => '-',
            'bond_status'              => 'No',
            'bond_duration'            => '-',
            'bond_end_date'            => '-',
            'bond_amount'              => '-',
            'l2_status'                => '-',
            'l3_status'                => '-',
            'certificate_institution'  => '-',
            'certificate_issued_date'  => '-',
            'certificate_expired_date' => '-',
            'pic'                      => '-',
        ], $overrides);
    }
}
