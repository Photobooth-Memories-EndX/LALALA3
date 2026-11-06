<?php

namespace App\Services\OfflineTraining;

use App\Models\ImportBatch;
use App\Models\ImportBatchRow;
use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Services\ImportLog\ImportLogService;
use App\Services\OfflineTraining\ExcelDateTimeParser;
use App\Services\Record\PtkpExcelSchema;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;
use Throwable;

class PtkpImportService
{
    public const CACHE_PREFIX = 'ptkp_import_';
    public const CACHE_TTL_SECONDS = 3600; // 1 hour

    protected PtkpValidationService $validationService;
    protected PtkpDatabaseWriter $databaseWriter;
    protected ImportLogService $logService;

    public function __construct(
        PtkpValidationService $validationService,
        PtkpDatabaseWriter $databaseWriter,
        ImportLogService $logService
    ) {
        $this->validationService = $validationService;
        $this->databaseWriter = $databaseWriter;
        $this->logService = $logService;
    }

    public function storeTemporaryFile(UploadedFile $file, int $userId): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['xlsx', 'xls'], true)) {
            throw new \InvalidArgumentException('Unsupported file format. Please upload an .xlsx or .xls spreadsheet.');
        }

        $token = bin2hex(random_bytes(16));
        $tempDir = storage_path('app/temp');
        if (!File::isDirectory($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        $tempFileName = "ptkp_import_{$token}.{$extension}";
        $tempFilePath = "{$tempDir}/{$tempFileName}";
        $file->move($tempDir, $tempFileName);

        $metadata = [
            'token'             => $token,
            'user_id'           => $userId,
            'original_filename' => $file->getClientOriginalName(),
            'temp_path'         => $tempFilePath,
            'created_at'        => now()->toDateTimeString(),
        ];

        Cache::put(self::CACHE_PREFIX . $token, $metadata, self::CACHE_TTL_SECONDS);

        return $metadata;
    }

    public function getValidTemporaryMetadata(string $token, int $userId): array
    {
        $metadata = Cache::get(self::CACHE_PREFIX . $token);
        if (!$metadata || !is_array($metadata)) {
            throw new RuntimeException('Import session expired or invalid. Please upload the file again.');
        }

        if ((int) ($metadata['user_id'] ?? 0) !== $userId) {
            throw new RuntimeException('Unauthorized access to import session.');
        }

        if (!isset($metadata['temp_path']) || !File::exists($metadata['temp_path'])) {
            throw new RuntimeException('Temporary import file not found. Please upload again.');
        }

        return $metadata;
    }

    public function cleanupTemporaryFile(string $token, int $userId): void
    {
        $metadata = Cache::get(self::CACHE_PREFIX . $token);
        if ($metadata && is_array($metadata) && (int) ($metadata['user_id'] ?? 0) === $userId) {
            if (isset($metadata['temp_path']) && File::exists($metadata['temp_path'])) {
                File::delete($metadata['temp_path']);
            }
            Cache::forget(self::CACHE_PREFIX . $token);
        }
    }

    public function parseAndPreview(string $token, int $userId): array
    {
        $metadata = $this->getValidTemporaryMetadata($token, $userId);
        $reader = IOFactory::createReaderForFile($metadata['temp_path']);
        $reader->setReadDataOnly(false);
        $spreadsheet = $reader->load($metadata['temp_path']);

        $worksheet = $spreadsheet->getSheetByName(PtkpExcelSchema::DATA_SHEET_TITLE)
            ?: $spreadsheet->getSheet(0);

        $evaluated = $this->parseAndEvaluateWorkbook($worksheet);

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $worksheet);
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }

        $metadata['stats'] = $evaluated['stats'];
        $metadata['header_check'] = $evaluated['header_check'];
        $metadata['can_confirm'] = $evaluated['can_confirm'];
        Cache::put(self::CACHE_PREFIX . $token, $metadata, self::CACHE_TTL_SECONDS);

        return [
            'token'            => $token,
            'filename'         => $metadata['original_filename'],
            'format'           => $evaluated['format_info']['format'],
            'header_check'     => $evaluated['header_check'],
            'can_confirm'      => $evaluated['can_confirm'],
            'stats'            => $evaluated['stats'],
            'rows'             => $evaluated['parsed_rows'],
            'total_rows'       => count($evaluated['parsed_rows']),
            'importable_count' => $evaluated['stats']['importable_count'],
        ];
    }

    public function executeImport(string $token, int $userId): array
    {
        $metadata = $this->getValidTemporaryMetadata($token, $userId);

        $lockKey = "ptkp_import_lock_{$token}";
        $lock = Cache::lock($lockKey, 60);
        if (!$lock->get()) {
            throw new RuntimeException('PTKP import execution is currently in progress. Please wait.');
        }

        if (!empty($metadata['executed_batch_uuid'])) {
            $lock->release();
            throw new RuntimeException('This import session has already been processed.');
        }

        $filePath = $metadata['temp_path'];
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(false);
        $spreadsheet = $reader->load($filePath);

        $worksheet = $spreadsheet->getSheetByName(PtkpExcelSchema::DATA_SHEET_TITLE)
            ?: $spreadsheet->getSheet(0);

        $evaluated = $this->parseAndEvaluateWorkbook($worksheet);

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $worksheet);
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }

        if (!$evaluated['header_check']['can_proceed']) {
            $lock->release();
            throw new \InvalidArgumentException('Import cannot proceed due to header validation errors.');
        }

        $batch = $this->logService->createBatch(
            sourceType: ImportBatch::SOURCE_PTKP,
            tempFilePath: $filePath,
            originalFilename: $metadata['original_filename'],
            userId: $userId
        );

        $metadata['executed_batch_uuid'] = $batch->batch_uuid;
        Cache::put(self::CACHE_PREFIX . $token, $metadata, self::CACHE_TTL_SECONDS);

        Log::info('Executing PTKP import batch', [
            'batch_uuid'        => $batch->batch_uuid,
            'filename'          => $metadata['original_filename'],
            'total_rows'        => count($evaluated['all_rows']),
            'grouped_trainings' => count($evaluated['grouped_by_training']),
            'user_id'           => $userId,
        ]);

        try {
            $persistResult = $this->databaseWriter->persistTrainingsAndParticipants(
                $evaluated['grouped_by_training'],
                $evaluated['existing_trainings'],
                $userId
            );

            $batchRows = [];
            foreach ($evaluated['all_rows'] as $r) {
                $rNum = $r['excel_row'];
                $persisted = $persistResult['participant_map'][$rNum] ?? null;

                $action = ImportBatchRow::ACTION_SKIPPED;
                $tId = $r['matched_training_id'];
                $pId = null;

                if ($persisted) {
                    $action = ImportBatchRow::ACTION_IMPORTED;
                    $tId = $persisted['offline_training_id'];
                    $pId = $persisted['offline_training_user_id'];
                }

                $batchRows[] = [
                    'row_number'               => $rNum,
                    'sheet_name'               => PtkpExcelSchema::DATA_SHEET_TITLE,
                    'employee_id'              => $r['employee_id'],
                    'employee_name'            => $r['name'],
                    'course_name'              => $r['course_name'],
                    'validation_status'        => $r['status'],
                    'action'                   => $action,
                    'offline_training_id'      => $tId,
                    'offline_training_user_id' => $pId,
                    'error_code'               => $r['error_code'],
                    'messages'                 => $r['messages'],
                    'row_data'                 => $r['sanitized_data'],
                ];
            }

            $this->logService->insertBatchRows($batch, $batchRows);

            $stats = $evaluated['stats'];
            $this->logService->completeBatch($batch, [
                'total_rows'        => count($evaluated['all_rows']),
                'valid_rows'        => $stats['valid'],
                'warning_rows'      => $stats['warning'] + $stats['invalid_company'],
                'duplicate_rows'    => $stats['duplicate'],
                'invalid_rows'      => $stats['invalid'],
                'imported_rows'     => $persistResult['imported'],
                'skipped_rows'      => $stats['duplicate'] + $stats['invalid'],
                'failed_rows'       => 0,
                'trainings_created' => $persistResult['trainings_created'],
                'trainings_reused'  => $persistResult['trainings_reused'],
            ]);
        } catch (Throwable $e) {
            $this->logService->failBatch($batch, $e, count($evaluated['all_rows']));
            throw $e;
        } finally {
            $lock->release();
        }

        $this->cleanupTemporaryFile($token, $userId);

        return [
            'success'                  => true,
            'batch_uuid'               => $batch->batch_uuid,
            'total_rows_processed'     => count($evaluated['all_rows']),
            'imported'                 => $persistResult['imported'],
            'skipped_duplicates'       => $evaluated['stats']['duplicate'],
            'skipped_invalid'          => $evaluated['stats']['invalid'],
            'warnings_imported'        => $evaluated['stats']['warning'],
            'invalid_company_imported' => $evaluated['stats']['invalid_company'],
            'trainings_created'        => $persistResult['trainings_created'],
            'trainings_reused'         => $persistResult['trainings_reused'],
        ];
    }

    protected function parseAndEvaluateWorkbook(Worksheet $worksheet): array
    {
        $formatInfo = PtkpExcelSchema::detectTemplateFormat($worksheet);
        $headerCheck = PtkpExcelSchema::analyzeHeaders(
            $formatInfo['found_headers'],
            $formatInfo['format']
        );
        $columnPositions = $headerCheck['column_positions'] ?? [];
        $startDataRow = $formatInfo['data_start_row'];
        $highestRow = $this->findTrueLastDataRow($worksheet, $startDataRow, $columnPositions);

        $rawRows = [];
        $uniqueEmployeeIds = [];
        $uniqueCourses = [];

        for ($r = $startDataRow; $r <= $highestRow; $r++) {
            $rowValues = $this->readRowValues($worksheet, $r, $columnPositions);
            if ($this->isRowEntirelyBlank($rowValues)) {
                continue;
            }
            $rawRows[] = ['excel_row' => $r, 'values' => $rowValues];
            $empId = trim((string) ($rowValues['employee_id'] ?? ''));
            if ($empId !== '') {
                $uniqueEmployeeIds[$empId] = true;
            }
            $course = trim((string) ($rowValues['course_name'] ?? ''));
            if ($course !== '') {
                $uniqueCourses[$course] = true;
            }
        }

        Log::info('PTKP Import workbook parsed', [
            'sheet'             => $worksheet->getTitle(),
            'format'            => $formatInfo['format'],
            'data_start_row'    => $startDataRow,
            'highest_data_row'  => $highestRow,
            'effective_rows'    => count($rawRows),
            'unique_employees'  => count($uniqueEmployeeIds),
            'unique_courses'    => count($uniqueCourses),
            'header_can_proceed'=> $headerCheck['can_proceed'],
        ]);

        $userLookup = $this->validationService->batchLookupUsers(array_keys($uniqueEmployeeIds));
        $existingTrainings = $this->lookupExistingTrainings(array_keys($uniqueCourses));
        $existingParticipantMap = $this->lookupExistingParticipants(array_values($existingTrainings));
        $existingDbRecords = $this->lookupExistingParticipantRecords(array_keys($uniqueEmployeeIds), array_keys($uniqueCourses));

        $stats = [
            'total_rows'       => count($rawRows),
            'valid'            => 0,
            'warning'          => 0,
            'duplicate'        => 0,
            'invalid'          => 0,
            'invalid_company'  => 0,
            'importable_count' => 0,
        ];

        $parsedRows = [];
        $allRows = [];
        $groupedByTraining = [];
        $seenInFileByHash = [];

        foreach ($rawRows as $item) {
            $rNum = $item['excel_row'];
            $normalized = $this->validationService->normalizeRowData($item['values']);
            $normalized['excel_row'] = $rNum;

            $matchResult = $this->validationService->matchUser($normalized['employee_id'], $userLookup);
            $user = $matchResult['user'];

            if ($user) {
                $normalized['user_id'] = $user->id;
                $normalized['employee_id'] = $user->id_emp;
                $normalized['name'] = $user->name;
                $normalized['grade'] = $user->jobgrade;
                $normalized['job_position'] = $user->jobposition;
                $normalized['team'] = $user->team;
                $normalized['department'] = $user->department;
            } else {
                $normalized['user_id'] = null;
            }

            $fp = $this->validationService->buildTrainingFingerprint($normalized);
            $isExistingTraining = isset($existingTrainings[$fp]);

            $rowHash = $this->validationService->buildFullRowHash($normalized);

            $isDuplicate = false;
            $duplicateSource = null;
            $duplicateType = null;
            $duplicateReason = '';
            $matchedRecordId = null;
            $originalExcelRow = null;
            $matchedFields = [];

            // 1. Layer B: Check duplicate within uploaded Excel (Strict full row comparison)
            if (isset($seenInFileByHash[$rowHash])) {
                foreach ($seenInFileByHash[$rowHash] as $prevItem) {
                    $cmp = $this->validationService->compareFullRowValues($normalized, $prevItem['data'], false);
                    if ($cmp['is_match']) {
                        $isDuplicate = true;
                        $duplicateSource = 'file';
                        $duplicateType = 'FULL_42_COLUMN_MATCH';
                        $originalExcelRow = $prevItem['excel_row'];
                        $matchedFields = array_keys($cmp['matched_fields']);
                        $duplicateReason = "Duplicate: Matches an identical record in the uploaded file (Row {$originalExcelRow}) across all 42 canonical columns. It will be skipped during import.";
                        break;
                    }
                }
            }

            // 2. Layer A: Check duplicate against existing database records
            if (!$isDuplicate) {
                // Exact 40-column hash match against stored database records
                $rowHash40 = $this->validationService->buildFullRowHash($normalized, true);
                if (isset($existingDbRecords['by_hash'][$rowHash40])) {
                    foreach ($existingDbRecords['by_hash'][$rowHash40] as $dbItem) {
                        $cmp = $this->validationService->compareFullRowValues($normalized, $dbItem, true);
                        if ($cmp['is_match']) {
                            $isDuplicate = true;
                            $duplicateSource = 'database';
                            $duplicateType = 'DATABASE_40_COLUMN_MATCH';
                            $matchedRecordId = $dbItem['_otu_id'] ?? ($dbItem['_offline_training_id'] ?? null);
                            $matchedFields = array_keys($cmp['matched_fields']);
                            $duplicateReason = "Duplicate: Matches an existing training record in database (ID {$matchedRecordId}) across all 40 stored comparison columns (progress_status and month are unverified in DB). It will be skipped during import.";
                            break;
                        }
                    }
                }

                // Check by employee for records where optional fields might differ
                if (!$isDuplicate) {
                    $empKey = mb_strtolower(trim((string) $normalized['employee_id']), 'UTF-8');
                    if (isset($existingDbRecords['by_employee'][$empKey])) {
                        foreach ($existingDbRecords['by_employee'][$empKey] as $dbItem) {
                            $cmp = $this->validationService->compareFullRowValues($normalized, $dbItem, true);
                            if ($cmp['is_match']) {
                                $isDuplicate = true;
                                $duplicateSource = 'database';
                                $duplicateType = 'DATABASE_40_COLUMN_MATCH';
                                $matchedRecordId = $dbItem['_otu_id'] ?? ($dbItem['_offline_training_id'] ?? null);
                                $matchedFields = array_keys($cmp['matched_fields']);
                                $duplicateReason = "Duplicate: Matches an existing training record in database (ID {$matchedRecordId}) across all 40 stored comparison columns (progress_status and month are unverified in DB). It will be skipped during import.";
                                break;
                            }
                        }
                    }
                }
            }

            if (!$isDuplicate) {
                $seenInFileByHash[$rowHash][] = [
                    'excel_row' => $rNum,
                    'data'      => $normalized,
                ];
            }

            $eval = $this->validationService->evaluateRowStatus(
                $normalized,
                $user,
                [
                    'is_duplicate'        => $isDuplicate,
                    'duplicate_reason'    => $duplicateReason,
                    'duplicate_source'    => $duplicateSource,
                    'duplicate_type'      => $duplicateType,
                    'matched_record_id'   => $matchedRecordId,
                    'original_excel_row'  => $originalExcelRow,
                    'matched_fields'      => $matchedFields,
                ],
                $matchResult
            );

            switch ($eval['status']) {
                case PtkpValidationService::STATUS_VALID:
                    $stats['valid']++;
                    $stats['importable_count']++;
                    break;
                case PtkpValidationService::STATUS_WARNING:
                    $stats['warning']++;
                    $stats['importable_count']++;
                    break;
                case PtkpValidationService::STATUS_INVALID_COMPANY:
                    $stats['invalid_company']++;
                    $stats['importable_count']++;
                    break;
                case PtkpValidationService::STATUS_DUPLICATE:
                    $stats['duplicate']++;
                    break;
                case PtkpValidationService::STATUS_INVALID:
                default:
                    $stats['invalid']++;
                    break;
            }

            $rowRecord = [
                'excel_row'             => $rNum,
                'no'                    => $normalized['row_no'] ?? ($rNum - $startDataRow + 1),
                'status'                => $eval['status'],
                'is_blocking'           => $eval['is_blocking'],
                'company_mismatch'      => $eval['company_mismatch'],
                'messages'              => $eval['messages'],
                'empty_warning_fields'  => $eval['empty_warning_fields'] ?? [],
                'empty_warning_details' => $eval['empty_warning_details'] ?? [],
                'warning_reasons'       => $eval['warning_reasons'] ?? [],
                'warning_message'       => $eval['warning_message'] ?? null,
                'employee_id'           => $normalized['employee_id'],
                'name'                => $normalized['name'],
                'company'             => $normalized['company'],
                'course_name'         => $normalized['course_name'],
                'type'                => $normalized['type'],
                'pic'                 => $normalized['pic'],
                'duplicate_source'    => $duplicateSource,
                'duplicate_type'      => $duplicateType,
                'matched_record_id'   => $matchedRecordId,
                'original_excel_row'  => $originalExcelRow,
                'matched_fields'      => $matchedFields,
                'data'                => $normalized,
                'error_code'          => $isDuplicate ? 'DUPLICATE' : ($eval['is_blocking'] ? 'INVALID' : null),
                'sanitized_data'      => [
                    'employee_id'         => $normalized['employee_id'],
                    'name'                => $normalized['name'],
                    'course_name'         => $normalized['course_name'],
                    'main_classification' => $normalized['main_classification'],
                    'sub_classification'  => $normalized['sub_classification'],
                    'learning_hours'      => $normalized['learning_hours'],
                    'start_date'          => $normalized['start_date'],
                    'finish_date'         => $normalized['finish_date'],
                    'type'                => $normalized['type'],
                    'venue'               => $normalized['venue'],
                ],
                'matched_training_id' => $isExistingTraining ? $existingTrainings[$fp]->id : null,
            ];

            $parsedRows[] = $rowRecord;
            $allRows[] = $rowRecord;

            if ($eval['status'] !== PtkpValidationService::STATUS_INVALID && $eval['status'] !== PtkpValidationService::STATUS_DUPLICATE) {
                if (!isset($groupedByTraining[$fp])) {
                    $groupedByTraining[$fp] = [
                        'training_data' => $normalized,
                        'participants'  => [],
                    ];
                }
                $groupedByTraining[$fp]['participants'][] = $normalized;
            }
        }

        $canConfirm = $headerCheck['can_proceed'] && ($stats['importable_count'] > 0);

        return [
            'format_info'         => $formatInfo,
            'header_check'        => $headerCheck,
            'stats'               => $stats,
            'parsed_rows'         => $parsedRows,
            'all_rows'            => $allRows,
            'grouped_by_training' => $groupedByTraining,
            'existing_trainings'  => $existingTrainings,
            'can_confirm'         => $canConfirm,
        ];
    }

    protected function readRowValues(Worksheet $worksheet, int $row, array $columnPositions = []): array
    {
        $columns = PtkpExcelSchema::columns();
        $values = [];
        $rawInputs = [];

        foreach ($columns as $idx => $col) {
            $key = $col['key'];
            $colLetter = $columnPositions[$key] ?? \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx);
            $cell = $worksheet->getCell("{$colLetter}{$row}");

            try {
                $val = $cell->getCalculatedValue();
            } catch (\Throwable $e) {
                $val = $cell->getValue();
            }

            // Capture raw input value before type casting/date normalization
            $rawInputs[$key] = is_scalar($val) ? trim((string) $val) : $val;

            // 1. Employee ID leading zero preservation from formatted value
            if ($key === 'employee_id' && $val !== null) {
                try {
                    $formatted = $cell->getFormattedValue();
                    if (is_string($formatted) && str_starts_with($formatted, '0') && strlen($formatted) > strlen((string) $val)) {
                        $val = $formatted;
                        $rawInputs[$key] = $formatted;
                    }
                } catch (\Throwable $e) {
                    // Ignore formatting exceptions
                }
            }

            // 2. Convert Excel date serial numbers or date objects to Y-m-d format
            if ($val !== null && in_array($key, ['start_date', 'finish_date', 'end_date', 'bond_end_date', 'certificate_issued_date', 'certificate_expired_date'], true)) {
                $dateResult = ExcelDateTimeParser::parseDateResult($val);
                if ($dateResult['valid'] && $dateResult['value'] !== null) {
                    $val = $dateResult['value'];
                } elseif ($dateResult['is_empty']) {
                    $val = null;
                } else {
                    $val = is_scalar($val) ? trim((string) $val) : 'Invalid Date';
                }
            }

            // 3. Convert Excel time serial numbers or fractional day to HH:mm:ss format
            if ($val !== null && in_array($key, ['start_time', 'end_time'], true)) {
                $timeResult = ExcelDateTimeParser::parseTimeResult($val);
                if ($timeResult['valid'] && $timeResult['value'] !== null) {
                    $val = $timeResult['value'];
                } elseif ($timeResult['is_empty']) {
                    $val = null;
                } else {
                    $val = is_scalar($val) ? trim((string) $val) : 'Invalid Time';
                }
            }

            if ($val instanceof \DateTimeInterface) {
                $values[$key] = $val->format('Y-m-d H:i:s');
            } elseif (is_scalar($val)) {
                $values[$key] = trim((string) $val);
            } else {
                $values[$key] = $val;
            }
        }

        $values['raw_inputs'] = $rawInputs;

        return $values;
    }

    protected function isRowEntirelyBlank(array $rowValues): bool
    {
        foreach ($rowValues as $k => $v) {
            if ($k === 'row_no' || $k === 'status' || $k === 'validation_status' || $k === 'raw_inputs') {
                continue;
            }
            if ($v !== null && trim((string) $v) !== '') {
                return false;
            }
        }
        return true;
    }

    /**
     * Efficiently determine the true last non-empty data row in the worksheet,
     * ignoring any trailing formatted or empty styled cells up to Excel maximum row.
     */
    protected function findTrueLastDataRow(Worksheet $worksheet, int $startDataRow, array $columnPositions): int
    {
        $coords = $worksheet->getCellCollection()->getCoordinates();
        if (empty($coords)) {
            return 0;
        }

        $rowNumbers = [];
        foreach ($coords as $coord) {
            sscanf($coord, '%[A-Z]%d', $col, $row);
            $rowNumbers[$row] = true;
        }
        unset($coords);
        krsort($rowNumbers);

        foreach ($rowNumbers as $r => $flag) {
            if ($r < $startDataRow) {
                break;
            }
            $rowValues = $this->readRowValues($worksheet, $r, $columnPositions);
            if (!$this->isRowEntirelyBlank($rowValues)) {
                return $r;
            }
        }

        return 0;
    }

    protected function lookupExistingTrainings(array $courses): array
    {
        if (empty($courses)) {
            return [];
        }

        $trainings = OfflineTraining::whereIn('course_name', $courses)->get();
        $map = [];

        foreach ($trainings as $t) {
            $fp = $this->validationService->buildTrainingFingerprint([
                'course_name' => $t->course_name,
                'batch'       => $t->batch,
                'start_date'  => $t->start_date ? $t->start_date->format('Y-m-d') : null,
                'finish_date' => $t->finish_date ? $t->finish_date->format('Y-m-d') : null,
                'type'        => $t->type,
                'venue'       => $t->venue,
            ]);
            $map[$fp] = $t;
        }

        return $map;
    }

    protected function lookupExistingParticipants(array $trainings): array
    {
        if (empty($trainings)) {
            return [];
        }

        $ids = collect($trainings)->pluck('id')->all();
        $participants = OfflineTrainingUser::whereIn('offline_training_id', $ids)
            ->get(['offline_training_id', 'user_id', 'employee_id']);

        $map = [];
        foreach ($participants as $p) {
            if ($p->user_id) {
                $map["{$p->offline_training_id}:uid:{$p->user_id}"] = true;
            }
            if ($p->employee_id) {
                $map["{$p->offline_training_id}:emp:" . strtolower(trim($p->employee_id))] = true;
            }
        }

        return $map;
    }

    /**
     * Bulk lookup existing database records for the given employees and courses.
     * Returns indexed arrays by hash and by employee for fast O(1) comparison.
     */
    protected function lookupExistingParticipantRecords(array $employeeIds, array $courses = []): array
    {
        if (empty($employeeIds)) {
            return ['by_hash' => [], 'by_employee' => []];
        }

        $query = DB::table('offline_training_users')
            ->join('offline_trainings', 'offline_training_users.offline_training_id', '=', 'offline_trainings.id')
            ->whereNull('offline_training_users.deleted_at')
            ->whereNull('offline_trainings.deleted_at')
            ->whereIn('offline_training_users.employee_id', $employeeIds);

        if (!empty($courses)) {
            $query->whereIn('offline_trainings.course_name', $courses);
        }

        $records = $query->select([
            'offline_training_users.id as otu_id',
            'offline_training_users.offline_training_id',
            'offline_training_users.user_id',
            'offline_training_users.employee_id',
            'offline_training_users.name',
            'offline_training_users.grade',
            'offline_training_users.job_position',
            'offline_training_users.team',
            'offline_training_users.department',
            'offline_training_users.company',
            'offline_training_users.main_classification as user_main_classification',
            'offline_training_users.sub_classification as user_sub_classification',
            'offline_training_users.learning_hours as user_learning_hours',
            'offline_training_users.start_date as user_start_date',
            'offline_training_users.end_date as user_end_date',
            'offline_training_users.start_time as user_start_time',
            'offline_training_users.end_time as user_end_time',
            'offline_training_users.progress',
            'offline_training_users.assessment_score',
            'offline_training_users.l2_numerical',
            'offline_training_users.l2_verbal',
            'offline_training_users.l3_numerical',
            'offline_training_users.l3_verbal',
            'offline_training_users.certificate',
            'offline_training_users.certificate_issued_date',
            'offline_training_users.certificate_expired_date',
            'offline_training_users.attendance',
            'offline_training_users.attendance_reason',
            'offline_training_users.bond_status',
            'offline_training_users.bond_duration',
            'offline_training_users.bond_end_date',
            'offline_training_users.bond_amount_idr',
            'offline_training_users.l1_program',
            'offline_training_users.pic',
            // Training attributes
            'offline_trainings.course_name',
            'offline_trainings.sub_course_name',
            'offline_trainings.type',
            'offline_trainings.category',
            'offline_trainings.level',
            'offline_trainings.batch',
            'offline_trainings.main_classification as training_main_classification',
            'offline_trainings.sub_classification as training_sub_classification',
            'offline_trainings.delivery_method',
            'offline_trainings.sub_delivery_method',
            'offline_trainings.provider',
            'offline_trainings.sub_provider',
            'offline_trainings.provider_name',
            'offline_trainings.venue',
            'offline_trainings.instructor',
            'offline_trainings.duration_seconds',
            'offline_trainings.requirement',
            'offline_trainings.start_date as training_start_date',
            'offline_trainings.finish_date as training_finish_date',
            'offline_trainings.learning_hours as training_learning_hours',
            'offline_trainings.start_time as training_start_time',
            'offline_trainings.end_time as training_end_time',
        ])->get();

        $byHash = [];
        $byEmployee = [];

        foreach ($records as $r) {
            $startDate = $r->user_start_date ?: $r->training_start_date;

            $normalizedDb = [
                // Employee Information (7)
                'employee_id'         => $r->employee_id,
                'name'                => $r->name,
                'grade'               => $r->grade,
                'job_position'        => $r->job_position,
                'team'                => $r->team,
                'department'          => $r->department,
                'company'             => $r->company,

                // Training Information (6)
                'course_name'         => $r->course_name,
                'sub_course_name'     => $r->sub_course_name,
                'type'                => $r->type,
                'category'            => $r->category,
                'level'               => $r->level,
                'batch'               => $r->batch,

                // Classification (2)
                'main_classification' => $r->user_main_classification ?: $r->training_main_classification,
                'sub_classification'  => $r->user_sub_classification ?: $r->training_sub_classification,

                // Delivery & Provider (5)
                'delivery_method'     => $r->delivery_method,
                'sub_delivery_method' => $r->sub_delivery_method,
                'provider_name'       => $r->provider_name,
                'venue'               => $r->venue,
                'instructor'          => $r->instructor,

                // Learning Details (9)
                'course_duration'     => $r->duration_seconds,
                'learning_hours'      => $r->user_learning_hours ?? $r->training_learning_hours,
                'progress'            => $r->progress,
                'progress_status'     => null,
                'start_date'          => $startDate,
                'end_date'            => $r->user_end_date ?: $r->training_finish_date,
                'finish_date'         => $r->user_end_date ?: $r->training_finish_date,
                'start_time'          => $r->user_start_time ?: $r->training_start_time,
                'end_time'            => $r->user_end_time ?: $r->training_end_time,
                'month'               => null,

                // Assessment, Attendance & Bond (12)
                'requirement'         => $r->requirement,
                'attendance'          => $r->attendance,
                'attendance_reason'   => $r->attendance_reason,
                'bond_status'         => $r->bond_status,
                'bond_duration'       => $r->bond_duration,
                'bond_end_date'       => $r->bond_end_date,
                'bond_amount'         => $r->bond_amount_idr,
                'bond_amount_idr'     => $r->bond_amount_idr,
                'l1_program'          => $r->l1_program,
                'assessment_score'    => $r->assessment_score ?? $r->l2_numerical,
                'l2_verbal'           => $r->l2_verbal,
                'l3_numerical'        => $r->l3_numerical,
                'l3_verbal'           => $r->l3_verbal,

                // Person in Charge (1)
                'pic'                 => $r->pic,

                // Internal metadata
                '_otu_id'              => $r->otu_id,
                '_offline_training_id' => $r->offline_training_id,
            ];

            // Use 40 stored columns hash for fast O(1) matching against database records
            $hash40 = $this->validationService->buildFullRowHash($normalizedDb, true);
            $byHash[$hash40][] = $normalizedDb;

            $empKey = mb_strtolower(trim((string) $r->employee_id), 'UTF-8');
            $byEmployee[$empKey][] = $normalizedDb;
        }

        return [
            'by_hash'     => $byHash,
            'by_employee' => $byEmployee,
        ];
    }
}
