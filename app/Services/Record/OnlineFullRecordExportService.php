<?php

namespace App\Services\Record;

use App\Services\Learning\LearningDatatableService;
use App\Services\Learning\LearningExportContext;
use App\Services\Learning\LearningExportService;
use App\Support\DurationFormatter;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class OnlineFullRecordExportService
{
    /**
     * Canonical 46-column reporting headings for KP OWL (Online) Full Record Export,
     * matching the All Sources (AllRecordExport) contract on Manage Record -> Export All.
     */
    public static function headings(): array
    {
        return [
            'No',
            'Source',
            'Employee ID',
            'Employee Name',
            'Status',
            'Grade',
            'Job Position',
            'Team',
            'Department',
            'Company',
            'Learning Title',
            'Sub Title',
            'Type',
            'Category',
            'Level',
            'Batch',
            'Main Classification',
            'Sub Classification',
            'Corporate KPI',
            'Sub Corporate KPI',
            'Delivery Method',
            'Sub Delivery Method',
            'Provider',
            'Sub Provider',
            'Provider Name',
            'Venue',
            'Instructor',
            'Duration',
            'Training Hours',
            'Start Date',
            'Finish Date',
            'Participant Hours',
            'Progress',
            'Assessment Score',
            'Certificate',
            'Attendance',
            'Attendance Reason',
            'Bond Status',
            'Bond End Date',
            'Bond Amount',
            'L1 Program',
            'L2 Numerical',
            'L2 Verbal',
            'L3 Numerical',
            'L3 Verbal',
            'PIC',
        ];
    }

    /**
     * Standardized column widths for columns A to AT (46 columns).
     */
    public static function columnWidths(): array
    {
        return [
            'A'  => 6,   // No
            'B'  => 14,  // Source
            'C'  => 16,  // Employee ID
            'D'  => 30,  // Employee Name
            'E'  => 14,  // Status
            'F'  => 10,  // Grade
            'G'  => 25,  // Job Position
            'H'  => 20,  // Team
            'I'  => 25,  // Department
            'J'  => 25,  // Company
            'K'  => 35,  // Learning Title
            'L'  => 25,  // Sub Title
            'M'  => 15,  // Type
            'N'  => 24,  // Category (widened for "Playlist: <Title>")
            'O'  => 12,  // Level
            'P'  => 12,  // Batch
            'Q'  => 25,  // Main Classification
            'R'  => 25,  // Sub Classification
            'S'  => 15,  // Corporate KPI
            'T'  => 22,  // Sub Corporate KPI
            'U'  => 18,  // Delivery Method
            'V'  => 20,  // Sub Delivery Method
            'W'  => 15,  // Provider
            'X'  => 18,  // Sub Provider
            'Y'  => 25,  // Provider Name
            'Z'  => 20,  // Venue
            'AA' => 22,  // Instructor
            'AB' => 14,  // Duration
            'AC' => 16,  // Training Hours
            'AD' => 14,  // Start Date
            'AE' => 14,  // Finish Date
            'AF' => 18,  // Participant Hours
            'AG' => 12,  // Progress
            'AH' => 18,  // Assessment Score
            'AI' => 16,  // Certificate
            'AJ' => 16,  // Attendance
            'AK' => 25,  // Attendance Reason
            'AL' => 14,  // Bond Status
            'AM' => 15,  // Bond End Date
            'AN' => 16,  // Bond Amount
            'AO' => 14,  // L1 Program
            'AP' => 15,  // L2 Numerical
            'AQ' => 14,  // L2 Verbal
            'AR' => 15,  // L3 Numerical
            'AS' => 14,  // L3 Verbal
            'AT' => 14,  // PIC
        ];
    }

    /**
     * Build the filtered database query for Online Full Record Export.
     */
    public function buildQuery(array $filters = []): Builder
    {
        $query = LearningDatatableService::buildBaseQuery(
            $filters,
            (string) ($filters['type'] ?? ''),
            (string) ($filters['slug'] ?? '')
        );

        $query->addSelect([
            'c.author as content_author',
            'c.status as content_status',
            'c.learning_hours as content_training_hours',
            'tp.learning_hours as playlist_training_hours',
        ]);

        // Additional filter for team if provided
        $team = $filters['team'] ?? ($filters['teams'] ?? 'all');
        if (is_array($team) && !empty($team)) {
            $query->whereIn('u.team', $team);
        } elseif (is_string($team) && filled($team) && $team !== 'all') {
            $query->where('u.team', trim($team));
        }

        // Additional filter for division if provided
        $division = $filters['division'] ?? ($filters['divisions'] ?? 'all');
        if (is_array($division) && !empty($division)) {
            $query->whereIn('u.division', $division);
        } elseif (is_string($division) && filled($division) && $division !== 'all') {
            $query->where('u.division', trim($division));
        }

        if (empty($filters['sort'])) {
            $query->orderBy('unified.created_at', 'desc')
                  ->orderBy('unified.source_type', 'desc')
                  ->orderBy('unified.source_id', 'desc');
        }

        return $query;
    }

    /**
     * Get a dedicated unbuffered PDO connection for zero-memory, O(N) linear streaming.
     */
    public function getStreamPdo(): \PDO
    {
        $dbConfig = config('database.connections.' . config('database.default'));
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $dbConfig['host'] ?? '127.0.0.1',
            $dbConfig['port'] ?? '3306',
            $dbConfig['database'],
            $dbConfig['charset'] ?? 'utf8mb4'
        );

        return new \PDO(
            $dsn,
            $dbConfig['username'],
            $dbConfig['password'],
            [
                \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
                \PDO::ATTR_ERRMODE                  => \PDO::ERRMODE_EXCEPTION,
            ]
        );
    }

    /**
     * Build batch resolution context for Assessment Score, Certificate, and Category.
     */
    public function buildBatchContext(iterable $rows): LearningExportContext
    {
        return LearningExportService::buildBatchContext($rows);
    }

    /**
     * Map a raw online record row into the canonical 46-column array.
     */
    public function mapRow(object|array $row, int $rowNumber, ?LearningExportContext $context = null): array
    {
        if (is_array($row)) {
            $row = (object) $row;
        }

        // 1. Employee Identification
        $idEmp = trim((string) ($row->user_id_emp ?? ($row->id_emp ?? ($row->employee_id ?? ($row->user->id_emp ?? '')))));
        if ($idEmp === '') {
            $idEmp = '-';
        }

        $name = trim((string) ($row->employee_name ?? ($row->user_name ?? ($row->name ?? ($row->user->name ?? '')))));
        if ($name === '') {
            $name = '-';
        }

        $department = trim((string) ($row->user_department ?? ($row->department ?? ($row->user->department ?? ''))));
        $team = trim((string) ($row->user_team ?? ($row->team ?? ($row->user->team ?? ''))));
        $company = trim((string) ($row->user_company ?? ($row->company_name ?? ($row->user->company_name ?? ''))));

        // 2. Progress & Completion
        $progress = (int) ($row->progress ?? 0);
        $finish = (int) ($row->finish ?? 0);
        $isCompleted = ($finish === 1) || ($progress >= 100);

        $statusStr = 'Not Started';
        if ($isCompleted) {
            $statusStr = 'Finished';
        } elseif ($progress > 0) {
            $statusStr = 'Ongoing';
        }

        // 3. Learning Title & Requirement
        $title = trim((string) ($row->content_title ?? ($row->title ?? '')));
        if ($title === '') {
            $title = trim((string) ($row->playlist_title ?? ''));
        }
        if ($title === '') {
            $title = '-';
        }
        $requirement = \App\Models\Content::mapRequirement($row->content_status ?? ($row->status ?? null));

        // 4. Type (Video / Document / Playlist)
        $rawType = strtolower(trim((string) ($row->content_type ?? ($row->type ?? ''))));
        $type = in_array($rawType, ['video', 'youtube', 'mooc'], true) ? 'Video' : ($rawType ? ucfirst($rawType) : 'Document');
        if (($row->source_type ?? '') === 'playlist' && empty($row->content_id)) {
            $type = 'Playlist';
        }

        // 5. Category (Content for standalone; Playlist: <Title> for items in a playlist)
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

        // 6. Classification
        $classification = trim((string) ($row->classification_name ?? ($row->classification ?? '')));
        if ($classification === '') {
            $classification = 'Unclassified';
        }

        // 7. Instructor
        $instructor = trim((string) ($row->content_author ?? ($row->author ?? '')));

        // 8. Duration (HH:MM:SS)
        $rawDuration = $row->content_duration ?? ($row->duration ?? null);
        $durationFormatted = DurationFormatter::format($rawDuration, '');

        // 9. Training Hours
        $trainingHoursVal = (float) ($row->content_training_hours ?? ($row->playlist_training_hours ?? 0));
        $trainingHoursStr = $trainingHoursVal > 0 ? number_format($trainingHoursVal, 2) : '';

        // 10. Start Date & Finish Date
        $startDate = '';
        if (!empty($row->created_at)) {
            try {
                $startDate = Carbon::parse($row->created_at)->format('Y-m-d');
            } catch (\Throwable $e) {
                $startDate = (string) $row->created_at;
            }
        }

        $finishDate = '';
        if ($isCompleted) {
            $rawFinish = $row->completed_at ?? ($row->updated_at ?? null);
            if (!empty($rawFinish)) {
                try {
                    $finishDate = Carbon::parse($rawFinish)->format('Y-m-d');
                } catch (\Throwable $e) {
                    $finishDate = (string) $rawFinish;
                }
            } elseif (!empty($row->created_at)) {
                try {
                    $finishDate = Carbon::parse($row->created_at)->format('Y-m-d');
                } catch (\Throwable $e) {
                    $finishDate = (string) $row->created_at;
                }
            }
        }

        // 11. Participant Hours
        $participantHours = (float) ($row->learning_hours ?? 0);
        $participantHoursStr = number_format(round($participantHours, 2), 2);

        // 12. Assessment Score
        if ($context !== null) {
            $score = $context->resolveScore($row);
        } else {
            $score = 'Not Applicable';
        }

        // 13. Certificate Eligibility
        if ($context !== null) {
            $certificate = $context->resolveCertificate($row);
        } else {
            $certificate = 'Not Available';
        }

        // 14. Attendance
        $attendance = $isCompleted ? 'Yes' : 'No';

        // Canonical 46 columns matching AllRecordExport
        return [
            $rowNumber,               // 1: No
            'KP OWL',                 // 2: Source
            $idEmp,                   // 3: Employee ID
            $name,                    // 4: Employee Name
            $statusStr,               // 5: Status
            '',                       // 6: Grade
            '',                       // 7: Job Position
            $team,                    // 8: Team
            $department,              // 9: Department
            $company,                 // 10: Company
            $title,                   // 11: Learning Title
            $requirement,             // 12: Sub Title / Requirement
            $type,                    // 13: Type
            $category,                // 14: Category
            '',                       // 15: Level
            '',                       // 16: Batch
            $classification,          // 17: Main Classification
            '',                       // 18: Sub Classification
            '',                       // 19: Corporate KPI
            '',                       // 20: Sub Corporate KPI
            '',                       // 21: Delivery Method
            '',                       // 22: Sub Delivery Method
            '',                       // 23: Provider
            '',                       // 24: Sub Provider
            '',                       // 25: Provider Name
            '',                       // 26: Venue
            $instructor,              // 27: Instructor
            $durationFormatted,       // 28: Duration
            $trainingHoursStr,        // 29: Training Hours
            $startDate,               // 30: Start Date
            $finishDate,              // 31: Finish Date
            $participantHoursStr,     // 32: Participant Hours
            $progress . '%',          // 33: Progress
            $score,                   // 34: Assessment Score
            $certificate,             // 35: Certificate
            $attendance,              // 36: Attendance
            '',                       // 37: Attendance Reason
            '',                       // 38: Bond Status
            '',                       // 39: Bond End Date
            '',                       // 40: Bond Amount
            '',                       // 41: L1 Program
            '',                       // 42: L2 Numerical
            '',                       // 43: L2 Verbal
            '',                       // 44: L3 Numerical
            '',                       // 45: L3 Verbal
            '',                       // 46: PIC
        ];
    }
}
