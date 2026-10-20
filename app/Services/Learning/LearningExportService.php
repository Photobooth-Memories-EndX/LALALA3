<?php

namespace App\Services\Learning;

use App\Models\Assesment;
use App\Models\CertificateSetting;
use App\Models\User;
use App\Support\DurationFormatter;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LearningExportService
{
    /**
     * Standardized Excel Headings for Manage Learning exports.
     * Preserves employee fields (NIP, Name, Department, Team) while standardizing
     * all course, progress, date, score, certificate, and attendance columns.
     */
    public static function headings(): array
    {
        return [
            'No',
            'Employee ID / NIP',
            'Employee Name',
            'Department',
            'Team',
            'Course',
            'Type',
            'Category',
            'Classification',
            'Duration',
            'Learning Hour',
            'Learning Progress',
            'Start Date',
            'Finish Date',
            'Assessment Score',
            'Certificate',
            'Attendance',
        ];
    }

    /**
     * Standardized column widths for columns A to Q.
     */
    public static function columnWidths(): array
    {
        return [
            'A' => 6,   // No
            'B' => 18,  // Employee ID / NIP
            'C' => 28,  // Employee Name
            'D' => 22,  // Department
            'E' => 20,  // Team
            'F' => 45,  // Course
            'G' => 14,  // Type
            'H' => 40,  // Category (widened for "Playlist: <Title>")
            'I' => 28,  // Classification
            'J' => 14,  // Duration
            'K' => 16,  // Learning Hour
            'L' => 18,  // Learning Progress
            'M' => 18,  // Start Date
            'N' => 18,  // Finish Date
            'O' => 18,  // Assessment Score
            'P' => 16,  // Certificate
            'Q' => 16,  // Attendance
        ];
    }

    /**
     * Build batch resolution context for Category, Assessment Score, and Certificate Eligibility.
     * Resolves metadata across batch without N+1 queries.
     *
     * @param iterable $rows
     * @return LearningExportContext
     */
    public static function buildBatchContext(iterable $rows): LearningExportContext
    {
        $context = new LearningExportContext();

        $userIds = [];
        $contentIds = [];
        $tpIds = [];
        $rowsArray = [];

        foreach ($rows as $row) {
            if (is_array($row)) {
                $row = (object) $row;
            }
            $rowsArray[] = $row;

            $uId = (int) ($row->user_id ?? ($row->user->id ?? 0));
            if ($uId > 0) {
                $userIds[$uId] = $uId;
            }

            $cId = (int) ($row->content_id ?? ($row->content->id ?? 0));
            if ($cId > 0) {
                $contentIds[$cId] = $cId;
            }

            $tpId = (int) ($row->trainingplan_id ?? ($row->trainingPlan->id ?? 0));
            if ($tpId > 0) {
                $tpIds[$tpId] = $tpId;
            }
        }

        if (empty($rowsArray)) {
            return $context;
        }

        // 1. Resolve Category for every row in the batch
        foreach ($rowsArray as $row) {
            $key = $context->rowKey($row);
            $isPlaylist = (($row->source_type ?? '') === 'playlist')
                || (!empty($row->trainingplan_id))
                || (($row->category_source ?? '') === 'Playlist');

            if ($isPlaylist) {
                $title = trim((string) ($row->playlist_title ?? ($row->trainingplan->title ?? ($row->trainingPlan->title ?? ''))));
                $context->setCategory($key, $title !== '' ? 'Playlist: ' . $title : 'Playlist');
            } else {
                $context->setCategory($key, 'Content');
            }
        }

        // 2. Load active CertificateSettings for contentIds & tpIds
        $settings = collect();
        if (!empty($contentIds) || !empty($tpIds)) {
            $settings = CertificateSetting::where('is_active', 1)
                ->where(function ($q) use ($contentIds, $tpIds) {
                    if (!empty($contentIds)) {
                        $q->where(fn ($w) => $w->where('certificate_type', 'content')->whereIn('content_id', array_values($contentIds)));
                    }
                    if (!empty($tpIds)) {
                        $q->orWhere(fn ($w) => $w->where('certificate_type', 'playlist')->whereIn('trainingplan_id', array_values($tpIds)));
                    }
                })
                ->with(['content:id,title', 'trainingPlan.trainingplandetail', 'assessment:id,title'])
                ->get();
        }

        $contentSettings = $settings->where('certificate_type', 'content')->keyBy('content_id');
        $playlistSettings = $settings->where('certificate_type', 'playlist')->keyBy('trainingplan_id');

        // Extract playlist contents from settings to include in assessment loading
        $playlistContentIdsMap = [];
        $allPlaylistContentIds = collect();
        foreach ($playlistSettings as $pSetting) {
            $pPlanId = (int) $pSetting->trainingplan_id;
            $pCids = $pSetting->trainingPlan
                ? $pSetting->trainingPlan->trainingplandetail->pluck('content_id')->filter()->unique()->values()
                : collect();
            $playlistContentIdsMap[$pPlanId] = $pCids;
            $allPlaylistContentIds = $allPlaylistContentIds->merge($pCids);
        }
        $allPlaylistContentIds = $allPlaylistContentIds->filter()->unique()->values();

        // 3. Load active assessments for all relevant contents and playlists
        $allAssessmentContentIds = collect($contentIds)->merge($allPlaylistContentIds)->filter()->unique()->values();
        $allAssessmentTpIds = collect($tpIds)->merge($playlistSettings->pluck('trainingplan_id'))->filter()->unique()->values();

        $assessments = collect();
        if ($allAssessmentContentIds->isNotEmpty() || $allAssessmentTpIds->isNotEmpty()) {
            $assessments = Assesment::where('status', '!=', 'NOT')
                ->where(function ($q) use ($allAssessmentContentIds, $allAssessmentTpIds) {
                    if ($allAssessmentContentIds->isNotEmpty()) {
                        $q->whereIn('content_id', $allAssessmentContentIds);
                    }
                    if ($allAssessmentTpIds->isNotEmpty()) {
                        $q->orWhereIn('trainingplan_id', $allAssessmentTpIds);
                    }
                })
                ->get(['id', 'content_id', 'trainingplan_id', 'type', 'assasment_content_for', 'passing_grade', 'status', 'fromdate', 'todate']);
        }

        $standaloneContentAssessments = [];
        $playlistContentAssessments = [];
        $playlistAssessments = [];

        foreach ($assessments as $a) {
            $cId = (int) ($a->content_id ?? 0);
            $tpId = (int) ($a->trainingplan_id ?? 0);
            $contentFor = strtolower(trim((string) ($a->assasment_content_for ?? 'content')));
            $type = strtolower(trim((string) ($a->type ?? '')));

            if ($contentFor === 'playlist' || ($tpId > 0 && $cId > 0)) {
                $playlistContentAssessments["{$tpId}_{$cId}"][] = $a;
            } elseif ($tpId > 0 && ($type === 'trainingplan' || $cId === 0)) {
                $playlistAssessments[$tpId][] = $a;
            } elseif ($cId > 0) {
                $standaloneContentAssessments[$cId][] = $a;
            }
        }

        // 4. Load final assessment results for userIds on these assessments
        $assessmentIds = $assessments->pluck('id')->filter()->unique()->values()->all();
        $userResults = []; // key: "{$userId}_{$assessmentId}" => max_score

        if (!empty($userIds) && !empty($assessmentIds)) {
            $results = DB::table('assesment_results')
                ->select('user_id', 'assesment_id', DB::raw('MAX(score) as max_score'))
                ->where('final', 1)
                ->whereIn('user_id', array_values($userIds))
                ->whereIn('assesment_id', $assessmentIds)
                ->groupBy('user_id', 'assesment_id')
                ->get();

            foreach ($results as $res) {
                $userResults["{$res->user_id}_{$res->assesment_id}"] = $res->max_score;
            }
        }

        // 5. Resolve Assessment Score for each row
        foreach ($rowsArray as $row) {
            $key = $context->rowKey($row);

            if (isset($row->assessment_score) && $row->assessment_score !== null && $row->assessment_score !== '') {
                $context->setScore($key, $context->formatScoreValue($row->assessment_score));
                continue;
            }
            if (isset($row->score) && $row->score !== null && $row->score !== '') {
                $context->setScore($key, $context->formatScoreValue($row->score));
                continue;
            }

            $uId = (int) ($row->user_id ?? ($row->user->id ?? 0));
            $cId = (int) ($row->content_id ?? ($row->content->id ?? 0));
            $tpId = (int) ($row->trainingplan_id ?? ($row->trainingPlan->id ?? 0));
            $isPlaylist = (($row->source_type ?? '') === 'playlist') || ($tpId > 0);

            $matchedAssessments = [];
            if ($isPlaylist && $tpId > 0) {
                if (isset($playlistContentAssessments["{$tpId}_{$cId}"])) {
                    $matchedAssessments = $playlistContentAssessments["{$tpId}_{$cId}"];
                } elseif (isset($playlistAssessments[$tpId])) {
                    $matchedAssessments = $playlistAssessments[$tpId];
                }
            } else {
                if (isset($standaloneContentAssessments[$cId])) {
                    $matchedAssessments = $standaloneContentAssessments[$cId];
                }
            }

            if (empty($matchedAssessments)) {
                $context->setScore($key, 'Not Applicable');
            } else {
                $bestScore = null;
                $hasTaken = false;

                foreach ($matchedAssessments as $ass) {
                    $scoreKey = "{$uId}_{$ass->id}";
                    if (array_key_exists($scoreKey, $userResults)) {
                        $hasTaken = true;
                        $score = $userResults[$scoreKey];
                        if ($bestScore === null || (float) $score > (float) $bestScore) {
                            $bestScore = $score;
                        }
                    }
                }

                if ($hasTaken && $bestScore !== null) {
                    $context->setScore($key, $context->formatScoreValue($bestScore));
                } else {
                    $context->setScore($key, 'Not Taken');
                }
            }
        }

        // 6. Bulk Evaluate Certificate Eligibility (Zero N+1)
        $assessmentsByContent = $assessments->filter(fn ($a) => !empty($a->content_id))->groupBy('content_id');
        $assessmentsByPlaylist = $assessments->filter(fn ($a) => !empty($a->trainingplan_id) && $a->type === 'trainingplan')->groupBy('trainingplan_id');

        // Pre-fetch ContentUser progress for active content settings
        $contentUserMap = [];
        $activeContentSettingIds = $contentSettings->pluck('content_id')->filter()->unique()->values()->all();
        if (!empty($activeContentSettingIds) && !empty($userIds)) {
            $cuRows = DB::table('content_users')
                ->whereIn('content_id', $activeContentSettingIds)
                ->whereIn('user_id', array_values($userIds))
                ->select('user_id', 'content_id', 'progress', 'finish')
                ->get();
            foreach ($cuRows as $cu) {
                $contentUserMap["{$cu->user_id}_{$cu->content_id}"] = $cu;
            }
        }

        // Pre-fetch Playlist completions count for active playlist settings
        $playlistCompletionsMap = [];
        if ($playlistSettings->isNotEmpty() && !empty($userIds)) {
            foreach ($playlistSettings as $pSetting) {
                $pId = (int) $pSetting->trainingplan_id;
                $pCids = $playlistContentIdsMap[$pId] ?? collect();
                if ($pCids->isNotEmpty()) {
                    $minProg = (int) $pSetting->minimum_progress;
                    $playlistCompletionsMap[$pId] = DB::table('playlist_content_users')
                        ->where('trainingplan_id', $pId)
                        ->whereIn('user_id', array_values($userIds))
                        ->whereIn('content_id', $pCids->all())
                        ->where(function ($q) use ($minProg) {
                            $q->where('finish', '1')
                              ->orWhere('progress', '>=', $minProg);
                        })
                        ->select('user_id', DB::raw('COUNT(DISTINCT content_id) as count'))
                        ->groupBy('user_id')
                        ->pluck('count', 'user_id')
                        ->all();
                }
            }
        }

        // In-memory row certificate evaluation
        foreach ($rowsArray as $row) {
            $key = $context->rowKey($row);

            if (isset($row->certificate) && $row->certificate !== null && $row->certificate !== '') {
                $context->setCertificate($key, $context->resolveCertificate($row));
                continue;
            }

            $uId = (int) ($row->user_id ?? ($row->user->id ?? 0));
            $cId = (int) ($row->content_id ?? ($row->content->id ?? 0));
            $tpId = (int) ($row->trainingplan_id ?? ($row->trainingPlan->id ?? 0));
            $isPlaylist = (($row->source_type ?? '') === 'playlist') || ($tpId > 0);

            $targetSetting = null;
            if ($isPlaylist && $tpId > 0) {
                $targetSetting = $playlistSettings->get($tpId) ?? $contentSettings->get($cId);
            } else {
                $targetSetting = $contentSettings->get($cId);
            }

            if (!$targetSetting) {
                $context->setCertificate($key, 'Not Available');
                continue;
            }

            try {
                if ($targetSetting->certificate_type === 'content') {
                    $cu = $contentUserMap["{$uId}_{$cId}"] ?? null;
                    if (!$cu || (int) $cu->progress < (int) $targetSetting->minimum_progress) {
                        $context->setCertificate($key, 'Not Available');
                        continue;
                    }

                    $cAssessments = $assessmentsByContent->get($cId, collect())
                        ->filter(fn ($a) => ($a->assasment_content_for ?? 'content') === 'content');

                    if (!self::checkAssessmentGate($targetSetting, $cAssessments, $uId, $userResults)) {
                        $context->setCertificate($key, 'Not Available');
                        continue;
                    }

                    $context->setCertificate($key, 'Available');
                } elseif ($targetSetting->certificate_type === 'playlist') {
                    $pId = (int) $targetSetting->trainingplan_id;
                    $pCids = $playlistContentIdsMap[$pId] ?? collect();

                    if ($pCids->isEmpty()) {
                        $context->setCertificate($key, 'Not Available');
                        continue;
                    }

                    $completedCount = $playlistCompletionsMap[$pId][$uId] ?? 0;
                    if ($completedCount !== $pCids->count()) {
                        $context->setCertificate($key, 'Not Available');
                        continue;
                    }

                    $passedAllAssessments = true;
                    foreach ($pCids as $pcId) {
                        $cAssessments = $assessmentsByContent->get($pcId, collect())
                            ->filter(fn ($a) => ($a->assasment_content_for ?? 'content') === 'playlist' && (int) $a->trainingplan_id === $pId);

                        if ($cAssessments->isNotEmpty() && !self::checkAssessmentGate($targetSetting, $cAssessments, $uId, $userResults)) {
                            $passedAllAssessments = false;
                            break;
                        }
                    }

                    if (!$passedAllAssessments) {
                        $context->setCertificate($key, 'Not Available');
                        continue;
                    }

                    $tpAssessments = $assessmentsByPlaylist->get($pId, collect());
                    if (!self::checkAssessmentGate($targetSetting, $tpAssessments, $uId, $userResults)) {
                        $context->setCertificate($key, 'Not Available');
                        continue;
                    }

                    $context->setCertificate($key, 'Available');
                } elseif ($targetSetting->certificate_type === 'global') {
                    if ($targetSetting->assessment_id) {
                        $scoreKey = "{$uId}_{$targetSetting->assessment_id}";
                        $minScore = (float) ($targetSetting->minimum_score ?? 80);
                        if (array_key_exists($scoreKey, $userResults) && (float) $userResults[$scoreKey] >= $minScore) {
                            $context->setCertificate($key, 'Available');
                        } else {
                            $context->setCertificate($key, 'Not Available');
                        }
                    } else {
                        $context->setCertificate($key, 'Not Available');
                    }
                } else {
                    $context->setCertificate($key, 'Not Available');
                }
            } catch (\Throwable $e) {
                $context->setCertificate($key, 'Not Available');
            }
        }

        return $context;
    }

    /**
     * Check whether an assessment group for an entity is active (status OK & not expired).
     */
    protected static function isAssessmentActive(Collection $assessments): bool
    {
        if ($assessments->isEmpty()) {
            return false;
        }

        $latest = $assessments->sortByDesc('id')->first();
        if (!$latest || strtoupper(trim((string) ($latest->status ?? ''))) === 'NOT') {
            return false;
        }

        $now = now();
        foreach ($assessments as $assessment) {
            if (strtoupper(trim((string) ($assessment->status ?? ''))) !== 'OK') {
                continue;
            }

            if (!empty($assessment->todate) && $now->gt(Carbon::parse($assessment->todate))) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * Gate check: User must pass at least one active assessment in the group.
     */
    protected static function checkAssessmentGate(?CertificateSetting $setting, Collection $assessments, int $userId, array $userResults): bool
    {
        if ($assessments->isEmpty()) {
            return true;
        }

        if (!self::isAssessmentActive($assessments)) {
            return true;
        }

        foreach ($assessments as $assessment) {
            $key = "{$userId}_{$assessment->id}";
            if (array_key_exists($key, $userResults)) {
                $minScore = (float) ($setting?->minimum_score ?? $assessment->passing_grade ?? 80);
                if ((float) $userResults[$key] >= $minScore) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Map a raw record row (object or array) into the standardized 17-column format.
     */
    public static function mapRow(object|array $row, int $rowNumber, ?LearningExportContext $context = null): array
    {
        if (is_array($row)) {
            $row = (object) $row;
        }

        // 1. Employee Identification (preserve leading zero for NIP)
        $idEmp = trim((string) ($row->user_id_emp ?? ($row->id_emp ?? ($row->employee_id ?? ($row->user->id_emp ?? '')))));
        if ($idEmp === '') {
            $idEmp = '-';
        }

        $name = trim((string) ($row->employee_name ?? ($row->user_name ?? ($row->name ?? ($row->user->name ?? '')))));
        if ($name === '') {
            $name = '-';
        }

        $department = trim((string) ($row->user_department ?? ($row->department ?? ($row->user->department ?? ''))));
        if ($department === '') {
            $department = '-';
        }

        $team = trim((string) ($row->user_team ?? ($row->team ?? ($row->user->team ?? ''))));
        if ($team === '') {
            $team = '-';
        }

        // 2. Course Title (Always content title, never renamed to playlist title)
        $course = trim((string) ($row->content_title ?? ($row->title ?? ($row->content->title ?? ''))));
        if ($course === '') {
            $course = '-';
        }

        // 3. Content Type (Document / Video)
        $rawType = strtolower(trim((string) ($row->content_type ?? ($row->type ?? ($row->content->type ?? '')))));
        $type = in_array($rawType, ['video', 'youtube', 'mooc'], true) ? 'Video' : 'Document';

        // 4. Category (Content for standalone, Playlist: <Name> for items inside a playlist)
        if ($context !== null) {
            $category = $context->resolveCategory($row);
        } else {
            $isPlaylist = (($row->source_type ?? '') === 'playlist')
                || (!empty($row->trainingplan_id))
                || (($row->category_source ?? '') === 'Playlist');

            if ($isPlaylist) {
                $playlistTitle = trim((string) ($row->playlist_title ?? ($row->trainingplan->title ?? ($row->trainingPlan->title ?? ''))));
                $category = $playlistTitle !== '' ? 'Playlist: ' . $playlistTitle : 'Playlist';
            } else {
                $category = 'Content';
            }
        }

        // 5. Classification
        $classification = trim((string) ($row->classification_name ?? ($row->classification ?? ($row->content->classification->classification_name ?? ''))));
        if ($classification === '') {
            $classification = '-';
        }

        // 6. Duration (HH:MM:SS)
        $rawDuration = $row->content_duration ?? ($row->duration ?? ($row->content->duration ?? null));
        $durationFormatted = DurationFormatter::format($rawDuration, '-');

        // 7. Progress & Completion Resolver
        $progress = (int) ($row->progress ?? 0);
        $finish = (int) ($row->finish ?? 0);
        $isCompleted = ($finish === 1) || ($progress >= 100);
        $progressStr = $progress . '%';

        // 8. Learning Hour
        $learningHour = 0.00;
        if (isset($row->learning_hours) && (float) $row->learning_hours > 0) {
            $learningHour = (float) $row->learning_hours;
        } elseif (isset($row->learning_hour) && (float) $row->learning_hour > 0) {
            $learningHour = (float) $row->learning_hour;
        } else {
            $durationSeconds = DurationFormatter::toSeconds($rawDuration) ?? 0;
            if ($durationSeconds > 0) {
                if ($isCompleted) {
                    $learningHour = $durationSeconds / 3600;
                } elseif ($progress > 0) {
                    $learningHour = (($progress / 100) * $durationSeconds) / 3600;
                }
            }
        }
        $learningHourFormatted = number_format(round($learningHour, 2), 2);

        // 9. Start Date & Finish Date
        $startDate = '-';
        if (!empty($row->created_at)) {
            try {
                $startDate = Carbon::parse($row->created_at)->format('Y-m-d H:i');
            } catch (\Throwable $e) {
                $startDate = (string) $row->created_at;
            }
        }

        $finishDate = '-';
        if ($isCompleted) {
            $rawFinish = $row->completed_at ?? ($row->updated_at ?? null);
            if (!empty($rawFinish)) {
                try {
                    $finishDate = Carbon::parse($rawFinish)->format('Y-m-d H:i');
                } catch (\Throwable $e) {
                    $finishDate = (string) $rawFinish;
                }
            } elseif (!empty($row->created_at)) {
                try {
                    $finishDate = Carbon::parse($row->created_at)->format('Y-m-d H:i');
                } catch (\Throwable $e) {
                    $finishDate = (string) $row->created_at;
                }
            }
        }

        // 10. Assessment Score
        if ($context !== null) {
            $score = $context->resolveScore($row);
        } else {
            if (isset($row->assessment_score) && $row->assessment_score !== null && $row->assessment_score !== '') {
                $score = is_numeric($row->assessment_score)
                    ? ((float) $row->assessment_score == (int) $row->assessment_score ? (string) (int) $row->assessment_score : number_format((float) $row->assessment_score, 2))
                    : (string) $row->assessment_score;
            } elseif (isset($row->score) && $row->score !== null && $row->score !== '') {
                $score = is_numeric($row->score)
                    ? ((float) $row->score == (int) $row->score ? (string) (int) $row->score : number_format((float) $row->score, 2))
                    : (string) $row->score;
            } else {
                $score = '-';
            }
        }

        // 11. Certificate
        if ($context !== null) {
            $certificate = $context->resolveCertificate($row);
        } else {
            if (isset($row->certificate) && $row->certificate !== null && $row->certificate !== '') {
                $certVal = trim((string) $row->certificate);
                if (in_array(strtolower($certVal), ['ready', 'available', 'yes', '1', 'true'], true)) {
                    $certificate = 'Available';
                } elseif (in_array(strtolower($certVal), ['not available', 'no', '0', 'false', 'unavailable'], true)) {
                    $certificate = 'Not Available';
                } else {
                    $certificate = $certVal;
                }
            } else {
                $certificate = '-';
            }
        }

        // 12. Attendance (Derived for online records; preserved for offline training)
        if (isset($row->source) && $row->source === 'offline') {
            if ($row->attendance !== null && $row->attendance !== '') {
                $attendance = is_bool($row->attendance) || is_numeric($row->attendance)
                    ? ((bool) $row->attendance ? 'Present' : 'Absent')
                    : (string) $row->attendance;
            } else {
                $attendance = '-';
            }
        } else {
            // Online (content or playlist)
            $attendance = $isCompleted ? 'Present' : 'Not Completed';
        }

        return [
            $rowNumber,
            $idEmp,
            $name,
            $department,
            $team,
            $course,
            $type,
            $category,
            $classification,
            $durationFormatted,
            $learningHourFormatted,
            $progressStr,
            $startDate,
            $finishDate,
            $score,
            $certificate,
            $attendance,
        ];
    }

    /**
     * Standardized styles for Maatwebsite Excel exports.
     */
    public static function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '000000'],
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D9EAF7'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    /**
     * Standardized AfterSheet event handler for Maatwebsite Excel exports.
     */
    public static function afterSheetEvent(AfterSheet $event): void
    {
        $sheet = $event->sheet->getDelegate();
        $highestRow = max(1, $sheet->getHighestRow());
        $highestColumn = $sheet->getHighestColumn();

        // 1. Freeze top header row
        $sheet->freezePane('A2');

        // 2. Set AutoFilter on header row
        $sheet->setAutoFilter("A1:{$highestColumn}" . max(1, $highestRow));

        // 3. Thin light border across all cells
        $sheet->getStyle("A1:{$highestColumn}{$highestRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D1D5DB'],
                ],
            ],
        ]);

        // 4. Alignments
        // No (col A): Center
        $sheet->getStyle("A2:A{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        // NIP (col B): Center as text
        $sheet->getStyle("B2:B{$highestRow}")->getNumberFormat()->setFormatCode('@');
        $sheet->getStyle("B2:B{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        // Employee Name (col C): Left wrap text
        $sheet->getStyle("C2:C{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(true);
        // Course Title (col F): Left wrap text
        $sheet->getStyle("F2:F{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(true);
        // Type (col G): Center
        $sheet->getStyle("G2:G{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        // Category (col H): Left wrap text
        $sheet->getStyle("H2:H{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(true);
        // Classification (col I): Left
        $sheet->getStyle("I2:I{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        // Duration (col J): Center
        $sheet->getStyle("J2:J{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        // Learning Hour (col K): Right
        $sheet->getStyle("K2:K{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        // Progress (col L), Start Date (col M), Finish Date (col N), Score (col O), Certificate (col P), Attendance (col Q): Center
        $sheet->getStyle("L2:Q{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }
}
