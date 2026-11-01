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
     * Canonical 52-Column Excel Headings for Manage Learning exports.
     * Guaranteed exact order and names across Content Overview, Playlist, and Category exports.
     */
    public static function headings(): array
    {
        return [
            'NO',
            'Source',
            'Employee ID',
            'Employee Name',
            'Grade',
            'Job Position',
            'Team',
            'Department',
            'Company',
            'Course Title',
            'Sub Course Title',
            'Type',
            'Category',
            'Level',
            'Batch',
            'Main Classification',
            'Sub Classification',
            'Delivery Method',
            'Sub Delivery Method',
            'Provider',
            'Sub Provider',
            'Provider Name',
            'Venue',
            'Instructor',
            'Course Duration',
            'Total Recognized Duration',
            'Learning Hours',
            '% Progress',
            'Progress Status',
            'Start Date',
            'End Date',
            'Start Time',
            'End Time',
            'Record Month',
            'Requirement',
            'Attendance',
            'Attendance Reason',
            'Bond Status',
            'Bond Duration',
            'Bond End Date',
            'Bond Amount',
            'Score (L1) - Course',
            'Score (L2)',
            'L2 Status',
            'Score (L3)',
            'L3 Status',
            'Certificate Availability',
            'Certificate Institution',
            'Certificate Issued Date',
            'Certificate Expired Date',
            'Person in Charge (PIC)',
            'Total Engagement Time (LH)',
        ];
    }

    /**
     * Standardized column widths for columns A to AZ (52 canonical columns).
     */
    public static function columnWidths(): array
    {
        return [
            'A'  => 6,   // NO
            'B'  => 14,  // Source
            'C'  => 16,  // Employee ID
            'D'  => 30,  // Employee Name
            'E'  => 12,  // Grade
            'F'  => 25,  // Job Position
            'G'  => 20,  // Team
            'H'  => 25,  // Department
            'I'  => 25,  // Company
            'J'  => 35,  // Course Title
            'K'  => 25,  // Sub Course Title
            'L'  => 15,  // Type
            'M'  => 18,  // Category
            'N'  => 12,  // Level
            'O'  => 12,  // Batch
            'P'  => 25,  // Main Classification
            'Q'  => 25,  // Sub Classification
            'R'  => 18,  // Delivery Method
            'S'  => 20,  // Sub Delivery Method
            'T'  => 16,  // Provider
            'U'  => 18,  // Sub Provider
            'V'  => 25,  // Provider Name
            'W'  => 20,  // Venue
            'X'  => 22,  // Instructor
            'Y'  => 16,  // Course Duration
            'Z'  => 22,  // Total Recognized Duration
            'AA' => 16,  // Learning Hours
            'AB' => 14,  // % Progress
            'AC' => 16,  // Progress Status
            'AD' => 14,  // Start Date
            'AE' => 14,  // End Date
            'AF' => 14,  // Start Time
            'AG' => 14,  // End Time
            'AH' => 16,  // Record Month
            'AI' => 15,  // Requirement
            'AJ' => 14,  // Attendance
            'AK' => 25,  // Attendance Reason
            'AL' => 14,  // Bond Status
            'AM' => 16,  // Bond Duration
            'AN' => 16,  // Bond End Date
            'AO' => 18,  // Bond Amount
            'AP' => 20,  // Score (L1) - Course
            'AQ' => 15,  // Score (L2)
            'AR' => 15,  // L2 Status
            'AS' => 15,  // Score (L3)
            'AT' => 15,  // L3 Status
            'AU' => 22,  // Certificate Availability
            'AV' => 25,  // Certificate Institution
            'AW' => 20,  // Certificate Issued Date
            'AX' => 20,  // Certificate Expired Date
            'AY' => 25,  // Person in Charge (PIC)
            'AZ' => 25,  // Total Engagement Time (LH)
        ];
    }

    /**
     * Format numerical learning hours into standard format (e.g. "2 h" or "1.5 h").
     */
    public static function formatHours($val): string
    {
        if ($val === null || $val === '' || $val === '-') {
            return '-';
        }
        $hours = (float) $val;
        if ($hours <= 0) {
            return '-';
        }
        return ($hours == (int) $hours)
            ? (int) $hours . ' h'
            : rtrim(rtrim(number_format($hours, 2, '.', ''), '0'), '.') . ' h';
    }

    /**
     * Build batch resolution context for Category, Assessment Score, and Certificate Eligibility.
     * Resolves metadata across batch without N+1 queries.
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
     * Map a raw record row (object or array) into the standardized 52-column canonical format.
     */
    public static function mapRow(object|array $row, int $rowNumber, ?LearningExportContext $context = null): array
    {
        if (is_array($row)) {
            $row = (object) $row;
        }

        $isPlaylist = (($row->source_type ?? '') === 'playlist')
            || (!empty($row->trainingplan_id))
            || (($row->category_source ?? '') === 'Playlist');

        // 1. Employee Identification
        $rawEmp = $row->id_emp ?? ($row->user_id_emp ?? ($row->employee_id ?? ($row->user->id_emp ?? '-')));
        $cleanEmpId = ($rawEmp !== '-' && $rawEmp !== null && $rawEmp !== '')
            ? preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', trim((string) $rawEmp))
            : '-';

        $name = trim((string) ($row->employee_name ?? ($row->user_name ?? ($row->name ?? ($row->user->name ?? '-')))));
        if ($name === '') {
            $name = '-';
        }

        $grade = trim((string) ($row->user_jobgrade ?? ($row->jobgrade ?? ($row->user->jobgrade ?? '-'))));
        if ($grade === '') {
            $grade = '-';
        }

        $jobPosition = trim((string) ($row->user_jobposition ?? ($row->jobposition ?? ($row->user->jobposition ?? '-'))));
        if ($jobPosition === '') {
            $jobPosition = '-';
        }

        $team = trim((string) ($row->user_team ?? ($row->team ?? ($row->user->team ?? '-'))));
        if ($team === '') {
            $team = '-';
        }

        $department = trim((string) ($row->user_department ?? ($row->department ?? ($row->user->department ?? '-'))));
        if ($department === '') {
            $department = '-';
        }

        $company = trim((string) ($row->user_company ?? ($row->company ?? ($row->company_name ?? ($row->user->company_name ?? 'PT Krakatau Posco')))));
        if ($company === '') {
            $company = 'PT Krakatau Posco';
        }

        // 2. Titles: Course Title & Sub Course Title
        $contentTitle = trim((string) ($row->content_title ?? ($row->title ?? ($row->content->title ?? ''))));
        $playlistTitle = trim((string) ($row->playlist_title ?? ($row->trainingplan->title ?? ($row->trainingPlan->title ?? ''))));

        if ($contentTitle !== '') {
            $courseTitle = $contentTitle;
            $subCourseTitle = ($isPlaylist && $playlistTitle !== '') ? $playlistTitle : '-';
        } elseif ($playlistTitle !== '') {
            $courseTitle = $playlistTitle;
            $subCourseTitle = '-';
        } else {
            $courseTitle = 'Untitled Content';
            $subCourseTitle = '-';
        }

        // 3. Type & Category
        $rawType = strtolower(trim((string) ($row->content_type ?? ($row->type ?? ($row->content->type ?? '')))));
        if ($isPlaylist && $contentTitle === '') {
            $type = 'Playlist';
        } else {
            $type = in_array($rawType, ['video', 'youtube', 'mooc'], true) ? 'Video' : 'Document';
        }

        if ($context !== null) {
            $category = $context->resolveCategory($row);
        } else {
            if ($isPlaylist) {
                $category = $playlistTitle !== '' ? 'Playlist: ' . $playlistTitle : 'Playlist';
            } else {
                $category = 'Content';
            }
        }

        // 4. Main Classification
        $mainClass = trim((string) ($row->classification_name ?? ($row->classification ?? ($row->content->classification->classification_name ?? ''))));
        if ($mainClass === '') {
            $mainClass = 'Unclassified';
        }

        // 5. Durations & Learning Hours
        $rawDuration = $row->content_duration ?? ($row->duration ?? ($row->content->duration ?? null));
        $durationFormatted = DurationFormatter::format($rawDuration, '-');

        $recognizedService = app(\App\Services\Learning\LearningRecordRecognizedDurationService::class);
        $recSec = $recognizedService->resolveFromRow($row);
        $recognizedFormatted = $recognizedService->formatSeconds($recSec);

        $lHoursVal = $row->content_training_hours ?? ($row->learning_hours ?? ($row->learning_hour ?? null));
        if ($lHoursVal === null || $lHoursVal === '' || (float) $lHoursVal <= 0) {
            if ($isPlaylist && $contentTitle === '') {
                $lHoursVal = $row->playlist_training_hours ?? null;
            }
        }
        $formattedLHours = self::formatHours($lHoursVal);

        // 6. Progress & Status
        $progressNum = (float) ($row->progress ?? 0);
        $isFinished = ((string) ($row->finish ?? '0') === '1' || $progressNum >= 100);
        $progressStatus = $isFinished ? 'Finished' : ($progressNum > 0 ? 'Ongoing' : 'Not Started');
        $progressDecimal = round($progressNum / 100, 4);

        // 7. Dates & Times
        $createdAt = !empty($row->created_at) ? strtotime($row->created_at) : null;
        $completedAt = (!empty($row->completed_at) && $isFinished) ? strtotime($row->completed_at) : ($isFinished && !empty($row->updated_at) ? strtotime($row->updated_at) : null);

        $startDateStr = $createdAt ? date('Y-m-d', $createdAt) : '-';
        $endDateStr = $completedAt ? date('Y-m-d', $completedAt) : '-';
        $startTimeStr = $createdAt ? date('H:i:s', $createdAt) : '-';
        $endTimeStr = $completedAt ? date('H:i:s', $completedAt) : '-';
        $recordMonthStr = ($completedAt && $isFinished) ? date('F', $completedAt) : '-';

        // 8. Attendance & Requirement
        $rawStatus = strtolower((string) ($row->status ?? 'non mandatory'));
        $requirement = in_array($rawStatus, ['mandatory'], true) ? 'Mandatory' : 'Non-Mandatory';
        $attendanceStr = ($isFinished || $progressNum > 0) ? 'Yes' : 'No';

        // 9. Score (L2)
        if ($context !== null) {
            $resolvedScore = $context->resolveScore($row);
            $scoreL2Val = is_numeric($resolvedScore)
                ? ((float) $resolvedScore == (int) $resolvedScore ? (int) $resolvedScore : number_format((float) $resolvedScore, 1))
                : '-';
        } elseif (isset($row->score_l2) && is_numeric($row->score_l2)) {
            $scoreL2Val = (int) round((float) $row->score_l2);
        } elseif (isset($row->score) && is_numeric($row->score)) {
            $scoreL2Val = (int) round((float) $row->score);
        } else {
            $scoreL2Val = '-';
        }

        // 10. Certificate
        $certAvailability = $isFinished ? 'Available' : '-';
        $certInstitution = $isFinished ? 'PT Krakatau Posco' : '-';
        $certIssuedDate = ($isFinished && $endDateStr !== '-') ? $endDateStr : '-';

        // 11. Engagement Time
        $afkSec = (int) ($row->afk_duration_seconds ?? 0);
        $afkFormatted = $afkSec > 0 ? DurationFormatter::fromSeconds($afkSec) : (isset($row->afk_duration_seconds) && $row->afk_duration_seconds !== null ? '00:00:00' : '-');

        $instructor = trim((string) ($row->content_author ?? ($row->author ?? ($row->content->author ?? '-'))));
        if ($instructor === '') {
            $instructor = '-';
        }

        return [
            $rowNumber,                                                       // 1. NO
            'KP OWL',                                                         // 2. Source
            $cleanEmpId,                                                      // 3. Employee ID
            $name,                                                            // 4. Employee Name
            $grade,                                                           // 5. Grade
            $jobPosition,                                                     // 6. Job Position
            $team,                                                            // 7. Team
            $department,                                                      // 8. Department
            $company,                                                         // 9. Company
            $courseTitle,                                                     // 10. Course Title
            $subCourseTitle,                                                  // 11. Sub Course Title
            $type,                                                            // 12. Type
            $category,                                                        // 13. Category
            '-',                                                              // 14. Level
            '-',                                                              // 15. Batch
            $mainClass,                                                       // 16. Main Classification
            '-',                                                              // 17. Sub Classification
            'Online',                                                         // 18. Delivery Method
            $isPlaylist ? 'Playlist / Path' : 'E-learning',                   // 19. Sub Delivery Method
            'Internal',                                                       // 20. Provider
            '-',                                                              // 21. Sub Provider
            'KP-OWL',                                                         // 22. Provider Name
            'Online LMS',                                                     // 23. Venue
            $instructor,                                                      // 24. Instructor
            $durationFormatted,                                               // 25. Course Duration
            $recognizedFormatted,                                             // 26. Total Recognized Duration
            $formattedLHours,                                                 // 27. Learning Hours
            $progressDecimal,                                                 // 28. % Progress
            $progressStatus,                                                  // 29. Progress Status
            $startDateStr,                                                    // 30. Start Date
            $endDateStr,                                                      // 31. End Date
            $startTimeStr,                                                    // 32. Start Time
            $endTimeStr,                                                      // 33. End Time
            $recordMonthStr,                                                  // 34. Record Month
            $requirement,                                                     // 35. Requirement
            $attendanceStr,                                                   // 36. Attendance
            '-',                                                              // 37. Attendance Reason
            'No',                                                             // 38. Bond Status
            '-',                                                              // 39. Bond Duration
            '-',                                                              // 40. Bond End Date
            '-',                                                              // 41. Bond Amount
            '-',                                                              // 42. Score (L1) - Course
            $scoreL2Val,                                                      // 43. Score (L2)
            '-',                                                              // 44. L2 Status
            '-',                                                              // 45. Score (L3)
            '-',                                                              // 46. L3 Status
            $certAvailability,                                                // 47. Certificate Availability
            $certInstitution,                                                 // 48. Certificate Institution
            $certIssuedDate,                                                  // 49. Certificate Issued Date
            '-',                                                              // 50. Certificate Expired Date
            '-',                                                              // 51. Person in Charge (PIC)
            $afkFormatted,                                                    // 52. Total Engagement Time (LH)
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
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '002B49'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
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

        // 0. Set header row height to 28
        $sheet->getRowDimension(1)->setRowHeight(28);

        // 1. Freeze top header row
        $sheet->freezePane('A2');

        // 2. Set AutoFilter on header row
        $sheet->setAutoFilter("A1:{$highestColumn}" . max(1, $highestRow));

        // 3. Header styling (navy fill, bold white text, center, wrapText)
        $sheet->getStyle("A1:{$highestColumn}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '002B49'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        // 4. Thin light border across all cells
        $sheet->getStyle("A1:{$highestColumn}{$highestRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D1D5DB'],
                ],
            ],
        ]);

        // 5. Alignments and number formats across 52 columns
        // Center alignment by default
        $sheet->getStyle("A2:{$highestColumn}{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Text format for Employee ID (Col C)
        $sheet->getStyle("C2:C{$highestRow}")->getNumberFormat()->setFormatCode('@');

        // Left alignment for Names and Titles
        $sheet->getStyle("D2:D{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(true); // Employee Name
        $sheet->getStyle("F2:I{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // Job, Team, Dept, Comp
        $sheet->getStyle("J2:K{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(true); // Course & Sub Course Titles
        $sheet->getStyle("P2:Q{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // Classifications

        // % Progress (Col AB): percentage format
        $sheet->getStyle("AB2:AB{$highestRow}")->getNumberFormat()->setFormatCode('0%');
    }
}
