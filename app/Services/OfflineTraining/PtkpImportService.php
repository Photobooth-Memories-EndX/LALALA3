<?php

namespace App\Services\OfflineTraining;

use App\Models\ImportBatch;
use App\Models\ImportBatchRow;
use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Services\ImportLog\ImportLogService;
use App\Services\Record\PtkpExcelSchema;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
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

        $highestRow = $worksheet->getHighestDataRow();
        $startDataRow = $formatInfo['data_start_row'];

        $rawRows = [];
        $uniqueEmployeeIds = [];
        $uniqueCourses = [];

        for ($r = $startDataRow; $r <= $highestRow; $r++) {
            $rowValues = $this->readRowValues($worksheet, $r);
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

        $userLookup = $this->validationService->batchLookupUsers(array_keys($uniqueEmployeeIds));
        $existingTrainings = $this->lookupExistingTrainings(array_keys($uniqueCourses));
        $existingParticipantMap = $this->lookupExistingParticipants(array_values($existingTrainings));

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
        $seenInFileParticipants = [];

        foreach ($rawRows as $item) {
            $rNum = $item['excel_row'];
            $normalized = $this->validationService->normalizeRowData($item['values']);
            $normalized['excel_row'] = $rNum;

            $matchResult = $this->validationService->matchUser($normalized['employee_id'], $userLookup);
            $user = $matchResult['user'];

            if ($user) {
                $normalized['user_id'] = $user->id;
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
            $partKey = $normalized['user_id']
                ? ('uid:' . $normalized['user_id'])
                : ('emp:' . strtolower($normalized['employee_id'] ?: ''));

            $isDuplicate = false;
            $duplicateReason = '';

            if (isset($seenInFileParticipants[$fp][$partKey])) {
                $isDuplicate = true;
                $duplicateReason = 'Duplicate participant for this training inside file (skipped)';
            } else {
                $seenInFileParticipants[$fp][$partKey] = true;
            }

            if (!$isDuplicate && $isExistingTraining) {
                $tId = $existingTrainings[$fp]->id;
                if (isset($existingParticipantMap["{$tId}:{$partKey}"])) {
                    $isDuplicate = true;
                    $duplicateReason = 'Participant already enrolled in existing training in database (skipped)';
                }
            }

            $eval = $this->validationService->evaluateRowStatus(
                $normalized,
                $user,
                ['is_duplicate' => $isDuplicate, 'duplicate_reason' => $duplicateReason],
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
                'excel_row'           => $rNum,
                'no'                  => $normalized['row_no'] ?? ($rNum - $startDataRow + 1),
                'status'              => $eval['status'],
                'is_blocking'         => $eval['is_blocking'],
                'company_mismatch'    => $eval['company_mismatch'],
                'messages'            => $eval['messages'],
                'employee_id'         => $normalized['employee_id'],
                'name'                => $normalized['name'],
                'company'             => $normalized['company'],
                'course_name'         => $normalized['course_name'],
                'type'                => $normalized['type'],
                'pic'                 => $normalized['pic'],
                'data'                => $normalized,
                'error_code'          => $isDuplicate ? 'DUPLICATE' : ($eval['is_blocking'] ? 'INVALID' : null),
                'sanitized_data'      => [
                    'employee_id'    => $normalized['employee_id'],
                    'name'           => $normalized['name'],
                    'course_name'    => $normalized['course_name'],
                    'learning_hours' => $normalized['learning_hours'],
                    'start_date'     => $normalized['start_date'],
                    'finish_date'    => $normalized['finish_date'],
                    'type'           => $normalized['type'],
                    'venue'          => $normalized['venue'],
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

    protected function readRowValues(Worksheet $worksheet, int $row): array
    {
        $columns = PtkpExcelSchema::columns();
        $values = [];

        foreach ($columns as $idx => $col) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx);
            $val = $worksheet->getCell("{$colLetter}{$row}")->getValue();
            $values[$col['key']] = $val !== null ? trim((string) $val) : null;
        }

        return $values;
    }

    protected function isRowEntirelyBlank(array $rowValues): bool
    {
        foreach ($rowValues as $k => $v) {
            if ($k === 'row_no' || $k === 'status' || $k === 'validation_status') {
                continue;
            }
            if ($v !== null && trim((string) $v) !== '') {
                return false;
            }
        }
        return true;
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
}
