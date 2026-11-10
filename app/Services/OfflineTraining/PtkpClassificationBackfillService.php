<?php

namespace App\Services\OfflineTraining;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Services\Record\PtkpExcelSchema;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PtkpClassificationBackfillService
{
    public const STATUS_MATCHED        = 'MATCHED';
    public const STATUS_NEEDS_BACKFILL = 'NEEDS_BACKFILL';
    public const STATUS_NOT_FOUND      = 'NOT_FOUND';
    public const STATUS_DUPLICATE      = 'DUPLICATE';
    public const STATUS_CONFLICT       = 'CONFLICT';
    public const STATUS_INVALID        = 'INVALID';

    /**
     * Run the dry-run audit comparing database participants against the source Excel.
     *
     * @param string|null $excelPath
     * @param int|null $limit
     * @param callable|null $progressCallback
     * @return array{
     *     stats: array<string, int>,
     *     audit_records: array<int, array<string, mixed>>,
     *     conflicts: array<int, array<string, mixed>>,
     *     sample_backfill: array<int, array<string, mixed>>,
     *     excel_stats: array{total_rows: int, valid_rows: int, path: string}
     * }
     */
    public function runAudit(
        ?string $excelPath = null,
        ?int $limit = null,
        ?callable $progressCallback = null
    ): array {
        $sourcePath = $excelPath ?: base_path('docs/PTKP_OWL_Import_Preview_5000 3.xlsx');

        if (!file_exists($sourcePath)) {
            throw new \InvalidArgumentException("Source Excel file not found: {$sourcePath}");
        }

        // Step 1: Read and index the Excel file
        $excelData = $this->loadAndIndexExcelSource($sourcePath);
        $excelIndex = $excelData['index'];

        // Step 2: Query database participants
        $query = OfflineTrainingUser::query()
            ->with(['training' => function ($q) {
                $q->select([
                    'id', 'course_name', 'sub_course_name', 'batch',
                    'main_classification', 'sub_classification', 'start_date', 'finish_date', 'venue'
                ]);
            }])
            ->whereNull('deleted_at');

        if ($limit !== null && $limit > 0) {
            $query->limit($limit);
        }

        $participants = $query->get();

        $stats = [
            'total_db_scanned'    => $participants->count(),
            'total_excel_scanned' => $excelData['valid_rows'],
            'matched'             => 0,
            'needs_backfill'      => 0,
            'not_found'           => 0,
            'duplicate'           => 0,
            'conflict'            => 0,
            'invalid'             => 0,
        ];

        $auditRecords = [];
        $conflicts = [];
        $sampleBackfill = [];

        foreach ($participants as $index => $participant) {
            if ($progressCallback !== null) {
                $progressCallback($index + 1, $participants->count());
            }

            $auditItem = $this->evaluateParticipant($participant, $excelIndex);
            $status = $auditItem['recovery_status'];

            if ($status === self::STATUS_MATCHED) {
                $stats['matched']++;
            } elseif ($status === self::STATUS_NEEDS_BACKFILL) {
                $stats['needs_backfill']++;
                if (count($sampleBackfill) < 25) {
                    $sampleBackfill[] = $auditItem;
                }
            } elseif ($status === self::STATUS_NOT_FOUND) {
                $stats['not_found']++;
            } elseif ($status === self::STATUS_DUPLICATE) {
                $stats['duplicate']++;
            } elseif ($status === self::STATUS_CONFLICT) {
                $stats['conflict']++;
                $conflicts[] = $auditItem;
            } elseif ($status === self::STATUS_INVALID) {
                $stats['invalid']++;
            }

            $auditRecords[] = $auditItem;
        }

        return [
            'stats'           => $stats,
            'audit_records'   => $auditRecords,
            'conflicts'       => $conflicts,
            'sample_backfill' => $sampleBackfill,
            'excel_stats'     => [
                'total_rows' => $excelData['total_rows'],
                'valid_rows' => $excelData['valid_rows'],
                'path'       => $sourcePath,
            ],
        ];
    }

    /**
     * Evaluate a single participant against Excel index and determine classification recovery status.
     *
     * @param OfflineTrainingUser $p
     * @param array<string, array<int, array<string, mixed>>> $excelIndex
     * @return array<string, mixed>
     */
    public function evaluateParticipant(OfflineTrainingUser $p, array $excelIndex): array
    {
        $training = $p->training;
        $courseTitle = $training ? (string) $training->course_name : '';
        $batch = $training ? (string) $training->batch : '';

        // Existing effective classifications (participant-level first, then parent fallback)
        $rawPartMain = $p->getRawOriginal('main_classification');
        $rawPartSub  = $p->getRawOriginal('sub_classification');

        $existingMain = (!empty($rawPartMain)) ? trim((string) $rawPartMain) : ($training ? trim((string) $training->main_classification) : '');
        $existingSub  = (!empty($rawPartSub)) ? trim((string) $rawPartSub) : ($training ? trim((string) $training->sub_classification) : '');

        $normEmpId = self::normalizeEmployeeId((string) $p->employee_id);
        $normCourse = strtolower(trim($courseTitle));
        $normBatch = preg_replace('/^batch\s*/i', '', strtolower(trim($batch)));

        // Primary lookup: employee_id + course + batch
        $keyExact = "{$normEmpId}|{$normCourse}|{$normBatch}";
        $matches = $excelIndex[$keyExact] ?? [];

        // Secondary lookup: employee_id + course (if batch normalized differently)
        if (empty($matches)) {
            $prefix = "{$normEmpId}|{$normCourse}|";
            foreach ($excelIndex as $k => $rows) {
                if (str_starts_with($k, $prefix)) {
                    $matches = array_merge($matches, $rows);
                }
            }
        }

        // Case: No match in Excel
        if (empty($matches)) {
            return [
                'employee_id'               => $p->employee_id,
                'employee_name'             => $p->name,
                'offline_training_id'       => $training ? $training->id : null,
                'offline_training_user_id'  => $p->id,
                'course_title'              => $courseTitle,
                'batch'                     => $batch,
                'excel_source_main'         => null,
                'excel_source_sub'          => null,
                'existing_db_main'          => $existingMain,
                'existing_db_sub'           => $existingSub,
                'proposed_main'             => $existingMain,
                'proposed_sub'              => $existingSub,
                'recovery_status'           => self::STATUS_NOT_FOUND,
                'reason'                    => 'No matching record found in Excel source for Employee ID and Course.',
                'excel_source_rows'         => [],
            ];
        }

        // Case: Multiple matches in Excel
        if (count($matches) > 1) {
            $firstMain = $matches[0]['main_classification'];
            $firstSub  = $matches[0]['sub_classification'];
            $hasConflict = false;

            foreach ($matches as $m) {
                if ($m['main_classification'] !== $firstMain || $m['sub_classification'] !== $firstSub) {
                    $hasConflict = true;
                    break;
                }
            }

            if ($hasConflict) {
                $sourceRowsList = array_map(fn($m) => "Row {$m['row']}: [{$m['main_classification']} / {$m['sub_classification']}]", $matches);
                return [
                    'employee_id'               => $p->employee_id,
                    'employee_name'             => $p->name,
                    'offline_training_id'       => $training ? $training->id : null,
                    'offline_training_user_id'  => $p->id,
                    'course_title'              => $courseTitle,
                    'batch'                     => $batch,
                    'excel_source_main'         => 'CONFLICT',
                    'excel_source_sub'          => 'CONFLICT',
                    'existing_db_main'          => $existingMain,
                    'existing_db_sub'           => $existingSub,
                    'proposed_main'             => $existingMain,
                    'proposed_sub'              => $existingSub,
                    'recovery_status'           => self::STATUS_CONFLICT,
                    'reason'                    => 'Conflicting classifications in Excel: ' . implode('; ', $sourceRowsList) . '. Excluded from auto-recovery.',
                    'excel_source_rows'         => array_column($matches, 'row'),
                ];
            }
        }

        $sourceRow  = $matches[0];
        $sourceMain = trim((string) $sourceRow['main_classification']);
        $sourceSub  = trim((string) $sourceRow['sub_classification']);

        // Case: Invalid or empty classification in Excel
        if ($sourceMain === '') {
            return [
                'employee_id'               => $p->employee_id,
                'employee_name'             => $p->name,
                'offline_training_id'       => $training ? $training->id : null,
                'offline_training_user_id'  => $p->id,
                'course_title'              => $courseTitle,
                'batch'                     => $batch,
                'excel_source_main'         => '',
                'excel_source_sub'          => $sourceSub,
                'existing_db_main'          => $existingMain,
                'existing_db_sub'           => $existingSub,
                'proposed_main'             => $existingMain,
                'proposed_sub'              => $existingSub,
                'recovery_status'           => self::STATUS_INVALID,
                'reason'                    => "Excel source row {$sourceRow['row']} has empty main_classification. Excluded from auto-recovery.",
                'excel_source_rows'         => array_column($matches, 'row'),
            ];
        }

        // Case: Matched (existing DB already matches Excel)
        if ($existingMain === $sourceMain && $existingSub === $sourceSub) {
            return [
                'employee_id'               => $p->employee_id,
                'employee_name'             => $p->name,
                'offline_training_id'       => $training ? $training->id : null,
                'offline_training_user_id'  => $p->id,
                'course_title'              => $courseTitle,
                'batch'                     => $batch,
                'excel_source_main'         => $sourceMain,
                'excel_source_sub'          => $sourceSub,
                'existing_db_main'          => $existingMain,
                'existing_db_sub'           => $existingSub,
                'proposed_main'             => $existingMain,
                'proposed_sub'              => $existingSub,
                'recovery_status'           => self::STATUS_MATCHED,
                'reason'                    => "Existing effective classification already matches Excel source (Row {$sourceRow['row']}).",
                'excel_source_rows'         => array_column($matches, 'row'),
            ];
        }

        // Case: Needs Backfill (Source has distinct valid classification)
        return [
            'employee_id'               => $p->employee_id,
            'employee_name'             => $p->name,
            'offline_training_id'       => $training ? $training->id : null,
            'offline_training_user_id'  => $p->id,
            'course_title'              => $courseTitle,
            'batch'                     => $batch,
            'excel_source_main'         => $sourceMain,
            'excel_source_sub'          => $sourceSub,
            'existing_db_main'          => $existingMain,
            'existing_db_sub'           => $existingSub,
            'proposed_main'             => $sourceMain,
            'proposed_sub'              => $sourceSub,
            'recovery_status'           => self::STATUS_NEEDS_BACKFILL,
            'reason'                    => "Inherited training classification '{$existingMain}' differs from individual participant classification in Excel (Row {$sourceRow['row']}: '{$sourceMain}').",
            'excel_source_rows'         => array_column($matches, 'row'),
        ];
    }

    /**
     * Load source Excel and build an index for fast lookups.
     *
     * @param string $filePath
     * @return array{total_rows: int, valid_rows: int, index: array<string, array<int, array<string, mixed>>>}
     */
    public function loadAndIndexExcelSource(string $filePath): array
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getSheetByName(PtkpExcelSchema::DATA_SHEET_TITLE) ?: $spreadsheet->getSheet(0);

        $highestRow = $sheet->getHighestRow();
        $excelRows = [];
        $index = [];

        for ($r = 2; $r <= $highestRow; $r++) {
            $rawEmpId = trim((string) $sheet->getCell("B{$r}")->getValue());
            $course   = trim((string) $sheet->getCell("I{$r}")->getValue());

            if ($rawEmpId === '' || $course === '') {
                continue;
            }

            $normEmpId = self::normalizeEmployeeId($rawEmpId);
            $batch     = trim((string) $sheet->getCell("N{$r}")->getValue());
            $normBatch = preg_replace('/^batch\s*/i', '', strtolower(trim($batch)));
            $normCourse = strtolower(trim($course));

            $record = [
                'row'                 => $r,
                'employee_id'         => $normEmpId,
                'raw_emp_id'          => $rawEmpId,
                'name'                => trim((string) $sheet->getCell("C{$r}")->getValue()),
                'course'              => $course,
                'batch'               => $batch,
                'main_classification' => trim((string) $sheet->getCell("O{$r}")->getValue()),
                'sub_classification'  => trim((string) $sheet->getCell("P{$r}")->getValue()),
                'venue'               => trim((string) $sheet->getCell("V{$r}")->getValue()),
                'start_date'          => trim((string) $sheet->getCell("Z{$r}")->getValue()),
            ];

            $excelRows[] = $record;

            $key = "{$normEmpId}|{$normCourse}|{$normBatch}";
            $index[$key][] = $record;
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $sheet);

        return [
            'total_rows' => $highestRow,
            'valid_rows' => count($excelRows),
            'index'      => $index,
        ];
    }

    /**
     * Export audit records to CSV.
     *
     * @param array<int, array<string, mixed>> $auditRecords
     * @param string $outputPath
     * @return string
     */
    public function exportAuditToCsv(array $auditRecords, string $outputPath): string
    {
        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $fp = fopen($outputPath, 'w');
        if (!$fp) {
            throw new \RuntimeException("Cannot open file for writing: {$outputPath}");
        }

        // Add UTF-8 BOM for Excel compatibility
        fputs($fp, "\xEF\xBB\xBF");

        // Headers
        fputcsv($fp, [
            'Recovery Status',
            'Employee ID',
            'Employee Name',
            'Offline Training ID',
            'Offline Training User ID',
            'Course Title',
            'Batch',
            'Excel Source Main Classification',
            'Excel Source Sub Classification',
            'Existing DB Main Classification',
            'Existing DB Sub Classification',
            'Proposed Main Classification',
            'Proposed Sub Classification',
            'Excel Source Rows',
            'Audit Reason',
        ]);

        foreach ($auditRecords as $r) {
            fputcsv($fp, [
                $r['recovery_status'],
                $r['employee_id'],
                $r['employee_name'],
                $r['offline_training_id'],
                $r['offline_training_user_id'],
                $r['course_title'],
                $r['batch'],
                $r['excel_source_main'],
                $r['excel_source_sub'],
                $r['existing_db_main'],
                $r['existing_db_sub'],
                $r['proposed_main'],
                $r['proposed_sub'],
                is_array($r['excel_source_rows']) ? implode(', ', $r['excel_source_rows']) : '',
                $r['reason'],
            ]);
        }

        fclose($fp);
        return $outputPath;
    }

    /**
     * Normalize employee ID by removing suffix notation and zero-padding purely numeric IDs < 6 digits.
     *
     * @param string $id
     * @return string
     */
    public static function normalizeEmployeeId(string $id): string
    {
        $clean = preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', trim($id));
        if (ctype_digit($clean) && strlen($clean) < 6) {
            return str_pad($clean, 6, '0', STR_PAD_LEFT);
        }
        return $clean;
    }
}
