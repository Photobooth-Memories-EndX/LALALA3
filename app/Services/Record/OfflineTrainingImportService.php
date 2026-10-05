<?php

namespace App\Services\Record;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OfflineTrainingImportService
{
    public const CACHE_PREFIX = 'offline_import_';
    public const CACHE_TTL_SECONDS = 3600; // 1 hour

    /**
     * Store uploaded file in temporary storage and create token.
     */
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

        $tempFileName = "offline_import_{$token}.{$extension}";
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

    /**
     * Retrieve and validate temporary file metadata for a specific user.
     */
    public function getValidTemporaryMetadata(string $token, int $userId): array
    {
        $metadata = Cache::get(self::CACHE_PREFIX . $token);
        if (!$metadata || !is_array($metadata)) {
            throw new \RuntimeException('Import session expired or invalid. Please upload the file again.');
        }

        if ((int) ($metadata['user_id'] ?? 0) !== $userId) {
            throw new \RuntimeException('Unauthorized access to import session.');
        }

        if (!isset($metadata['temp_path']) || !File::exists($metadata['temp_path'])) {
            throw new \RuntimeException('Temporary import file not found. Please upload again.');
        }

        return $metadata;
    }

    /**
     * Clean up temporary file and cache.
     */
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

    /**
     * Parse workbook, perform Phase A Header Analysis & Phase B Row Validation,
     * detect duplicates, match participants, and generate preview payload.
     */
    public function parseAndPreview(string $token, int $userId): array
    {
        $metadata = $this->getValidTemporaryMetadata($token, $userId);
        $filePath = $metadata['temp_path'];

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(false);
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getSheetByName(OfflineTrainingExcelSchema::DATA_SHEET_TITLE)
            ?: $spreadsheet->getSheet(0);

        // Phase A: Header Analysis & Format Detection
        $headerMapping = $this->resolveHeaderMapping($worksheet);
        $headerCheck = $headerMapping['header_check'];

        $highestRow = $worksheet->getHighestDataRow();
        $startDataRow = $headerMapping['data_start_row'];

        // Collect all NIPs and participant names to batch-lookup LMS users
        $rawRows = [];
        $uniqueNips = [];
        $uniqueNames = [];
        $uniqueCourses = [];

        for ($r = $startDataRow; $r <= $highestRow; $r++) {
            $rowValues = $this->readRowValues($worksheet, $r, $headerMapping['column_positions']);
            if ($this->isRowEntirelyBlank($rowValues)) {
                continue;
            }

            $rawRows[] = [
                'excel_row' => $r,
                'values'    => $rowValues,
            ];

            $nip = trim((string) ($rowValues['employee_id'] ?? ''));
            if ($nip !== '') {
                $uniqueNips[$nip] = true;
            }

            $pName = trim((string) ($rowValues['name'] ?? ''));
            if ($pName !== '') {
                $uniqueNames[$pName] = true;
            }

            $course = trim((string) ($rowValues['course_name'] ?? ''));
            if ($course !== '') {
                $uniqueCourses[$course] = true;
            }
        }

        // Batch lookup LMS users by NIP and by Name
        $userMapByNip = [];
        $usersMapByName = [];

        if (!empty($uniqueNips)) {
            $users = User::whereIn('id_emp', array_keys($uniqueNips))
                ->select(['id', 'name', 'id_emp'])
                ->get();
            foreach ($users as $u) {
                $userMapByNip[strtolower(trim($u->id_emp))] = $u;
                $unpadded = ltrim(trim($u->id_emp), '0');
                if ($unpadded !== '' && $unpadded !== trim($u->id_emp)) {
                    $userMapByNip[strtolower($unpadded)] = $u;
                }
            }
        }

        if (!empty($uniqueNames)) {
            $nameUsers = User::where(function ($q) use ($uniqueNames) {
                foreach (array_keys($uniqueNames) as $nm) {
                    $q->orWhereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($nm))]);
                }
            })->select(['id', 'name', 'id_emp'])->get();

            foreach ($nameUsers as $u) {
                $normDbName = mb_strtolower(trim(preg_replace('/\s+/', ' ', $u->name)));
                $usersMapByName[$normDbName][] = $u;
            }
        }

        // Batch load potential existing OfflineTrainings
        $existingTrainings = [];
        if (!empty($uniqueCourses)) {
            $dbTrainings = OfflineTraining::whereIn('course_name', array_keys($uniqueCourses))->get();
            foreach ($dbTrainings as $t) {
                $fp = $this->buildTrainingFingerprint([
                    'course_name' => $t->course_name,
                    'batch'       => $t->batch,
                    'start_date'  => $t->start_date ? $t->start_date->format('Y-m-d') : null,
                    'finish_date' => $t->finish_date ? $t->finish_date->format('Y-m-d') : null,
                    'type'        => $t->type,
                    'venue'       => $t->venue,
                ]);
                $existingTrainings[$fp] = $t;
            }
        }

        // Cache existing participant records to detect DB duplicates
        $existingParticipantMap = [];
        if (!empty($existingTrainings)) {
            $trainingIds = collect($existingTrainings)->pluck('id')->all();
            $dbParticipants = OfflineTrainingUser::whereIn('offline_training_id', $trainingIds)
                ->select(['offline_training_id', 'user_id', 'employee_id'])
                ->get();
            foreach ($dbParticipants as $p) {
                if ($p->user_id) {
                    $existingParticipantMap[$p->offline_training_id . ':uid:' . $p->user_id] = true;
                }
                if ($p->employee_id) {
                    $existingParticipantMap[$p->offline_training_id . ':emp:' . strtolower(trim($p->employee_id))] = true;
                }
            }
        }

        // Parse and classify every row
        $parsedRows = [];
        $stats = [
            'total_rows'       => count($rawRows),
            'valid'            => 0,
            'warning'          => 0,
            'invalid'          => 0,
            'duplicate'        => 0,
            'new_trainings'    => 0,
            'reused_trainings' => 0,
            'unique_events'    => 0,
        ];

        $seenInFileParticipants = [];
        $fileTrainingFingerprints = [];

        foreach ($rawRows as $item) {
            $rNum = $item['excel_row'];
            $vals = $item['values'];

            $normalized = $this->normalizeRowData($vals);
            // Phase B: Row data validation with exact cell types & coordinates
            $validation = $this->validateRowData($normalized, $vals, $rNum);

            // Participant / User lookup hierarchy
            $employeeId = $normalized['employee_id'];
            $participantName = $normalized['name'];
            $userMatch = $this->matchParticipantUser($employeeId, $participantName, $userMapByNip, $usersMapByName);

            $matchedUser = $userMatch['user'];
            $normalized['user_id'] = $userMatch['user_id'];

            // Training fingerprint
            $fp = $this->buildTrainingFingerprint($normalized);
            $isExistingTraining = isset($existingTrainings[$fp]);
            if (!isset($fileTrainingFingerprints[$fp])) {
                $fileTrainingFingerprints[$fp] = [
                    'is_existing' => $isExistingTraining,
                    'training'    => $isExistingTraining ? $existingTrainings[$fp] : null,
                ];
                if ($isExistingTraining) {
                    $stats['reused_trainings']++;
                } else {
                    $stats['new_trainings']++;
                }
                $stats['unique_events']++;
            }

            // Duplicate detection
            $isDuplicate = false;
            $duplicateReason = '';

            $partKey = $normalized['user_id'] ? ('uid:' . $normalized['user_id']) : ('emp:' . strtolower($employeeId ?: ''));

            // Duplicate in same file
            if (isset($seenInFileParticipants[$fp][$partKey])) {
                $isDuplicate = true;
                $duplicateReason = 'Duplicate participant for this training inside file (skipped)';
            } else {
                $seenInFileParticipants[$fp][$partKey] = true;
            }

            // Duplicate in DB
            if (!$isDuplicate && $isExistingTraining) {
                $existingTrainingId = $existingTrainings[$fp]->id;
                $dbKey = $existingTrainingId . ':' . $partKey;
                if (isset($existingParticipantMap[$dbKey])) {
                    $isDuplicate = true;
                    $duplicateReason = 'Participant already enrolled in existing training in database (skipped)';
                }
            }

            // Status determination
            $rowStatus = 'valid';
            $messages = [];

            if (!empty($validation['errors'])) {
                $rowStatus = 'invalid';
                $messages = array_merge($messages, $validation['errors']);
                if (!empty($userMatch['warning'])) {
                    $messages[] = $userMatch['warning'];
                }
                $stats['invalid']++;
            } elseif ($isDuplicate) {
                $rowStatus = 'duplicate';
                $messages[] = $duplicateReason;
                $stats['duplicate']++;
            } elseif (!empty($validation['warnings']) || !empty($userMatch['warning'])) {
                $rowStatus = 'warning';
                if (!empty($userMatch['warning'])) {
                    $messages[] = $userMatch['warning'];
                }
                $messages = array_merge($messages, $validation['warnings']);
                $stats['warning']++;
                $stats['valid']++; // warnings are still importable
            } else {
                $stats['valid']++;
            }

            $parsedRows[] = [
                'excel_row'          => $rNum,
                'status'             => $rowStatus,
                'messages'           => $messages,
                'is_duplicate'       => $isDuplicate,
                'user_matched'       => (bool) $matchedUser,
                'user_match_type'    => $userMatch['match_type'] ?? null,
                'training_exists'    => $isExistingTraining,
                'training_fp'        => $fp,
                'data'               => $normalized,
            ];
        }

        // Cache summary and metadata for confirm execution
        $metadata['stats'] = $stats;
        $metadata['header_check'] = $headerCheck;
        $metadata['format'] = $headerMapping['format'];
        $metadata['unknown_headers'] = $headerMapping['unknown_headers'];
        Cache::put(self::CACHE_PREFIX . $token, $metadata, self::CACHE_TTL_SECONDS);

        $isHeaderValid = (bool) ($headerCheck['can_proceed'] ?? false);
        $importableCount = (int) ($stats['valid'] ?? 0);
        $duplicateCount = (int) ($stats['duplicate'] ?? 0);
        $invalidCount = (int) ($stats['invalid'] ?? 0);
        $totalRows = (int) ($stats['total_rows'] ?? 0);

        if (!$isHeaderValid) {
            $previewState = 'HEADER_ERROR';
        } elseif ($invalidCount > 0) {
            $previewState = 'DATA_ERROR';
        } elseif ($totalRows === 0) {
            $previewState = 'EMPTY_FILE';
        } elseif ($importableCount === 0 && $duplicateCount > 0) {
            $previewState = 'NO_NEW_RECORDS';
        } elseif ($duplicateCount > 0 && $importableCount > 0) {
            $previewState = 'READY_WITH_DUPLICATES';
        } else {
            $previewState = 'READY';
        }

        $canConfirm = ($previewState === 'READY' || $previewState === 'READY_WITH_DUPLICATES');

        return [
            'token'            => $token,
            'filename'         => $metadata['original_filename'],
            'format'           => $headerMapping['format'],
            'header_check'     => $headerCheck,
            'can_confirm'      => $canConfirm,
            'preview_state'    => $previewState,
            'importable_count' => $importableCount,
            'stats'            => $stats,
            'unknown_headers'  => $headerMapping['unknown_headers'],
            'preview_rows'     => array_slice($parsedRows, 0, 100),
            'total_preview'    => count($parsedRows),
        ];
    }

    /**
     * Execute full import inside database transactions.
     * Strictly blocked if template header analysis failed.
     */
    public function executeImport(string $token, int $userId): array
    {
        $metadata = $this->getValidTemporaryMetadata($token, $userId);
        $filePath = $metadata['temp_path'];

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(false);
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getSheetByName(OfflineTrainingExcelSchema::DATA_SHEET_TITLE)
            ?: $spreadsheet->getSheet(0);

        $headerMapping = $this->resolveHeaderMapping($worksheet);
        if (!$headerMapping['can_proceed']) {
            $issueMessages = array_column($headerMapping['header_check']['issues'], 'message');
            throw new \InvalidArgumentException('Import cannot proceed due to header validation errors: ' . implode('; ', $issueMessages));
        }

        $highestRow = $worksheet->getHighestDataRow();
        $startDataRow = $headerMapping['data_start_row'];

        $rawRows = [];
        $uniqueNips = [];
        $uniqueNames = [];
        $uniqueCourses = [];

        for ($r = $startDataRow; $r <= $highestRow; $r++) {
            $rowValues = $this->readRowValues($worksheet, $r, $headerMapping['column_positions']);
            if ($this->isRowEntirelyBlank($rowValues)) {
                continue;
            }
            $rawRows[] = [
                'excel_row' => $r,
                'values'    => $rowValues,
            ];
            $nip = trim((string) ($rowValues['employee_id'] ?? ''));
            if ($nip !== '') {
                $uniqueNips[$nip] = true;
            }
            $pName = trim((string) ($rowValues['name'] ?? ''));
            if ($pName !== '') {
                $uniqueNames[$pName] = true;
            }
            $course = trim((string) ($rowValues['course_name'] ?? ''));
            if ($course !== '') {
                $uniqueCourses[$course] = true;
            }
        }

        // Batch users
        $userMapByNip = [];
        $usersMapByName = [];

        if (!empty($uniqueNips)) {
            $users = User::whereIn('id_emp', array_keys($uniqueNips))
                ->select(['id', 'name', 'id_emp'])
                ->get();
            foreach ($users as $u) {
                $userMapByNip[strtolower(trim($u->id_emp))] = $u;
                $unpadded = ltrim(trim($u->id_emp), '0');
                if ($unpadded !== '' && $unpadded !== trim($u->id_emp)) {
                    $userMapByNip[strtolower($unpadded)] = $u;
                }
            }
        }

        if (!empty($uniqueNames)) {
            $nameUsers = User::where(function ($q) use ($uniqueNames) {
                foreach (array_keys($uniqueNames) as $nm) {
                    $q->orWhereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($nm))]);
                }
            })->select(['id', 'name', 'id_emp'])->get();

            foreach ($nameUsers as $u) {
                $normDbName = mb_strtolower(trim(preg_replace('/\s+/', ' ', $u->name)));
                $usersMapByName[$normDbName][] = $u;
            }
        }

        // Batch trainings
        $existingTrainings = [];
        if (!empty($uniqueCourses)) {
            $dbTrainings = OfflineTraining::whereIn('course_name', array_keys($uniqueCourses))->get();
            foreach ($dbTrainings as $t) {
                $fp = $this->buildTrainingFingerprint([
                    'course_name' => $t->course_name,
                    'batch'       => $t->batch,
                    'start_date'  => $t->start_date ? $t->start_date->format('Y-m-d') : null,
                    'finish_date' => $t->finish_date ? $t->finish_date->format('Y-m-d') : null,
                    'type'        => $t->type,
                    'venue'       => $t->venue,
                ]);
                $existingTrainings[$fp] = $t;
            }
        }

        // DB participant duplicate map
        $existingParticipantMap = [];
        if (!empty($existingTrainings)) {
            $trainingIds = collect($existingTrainings)->pluck('id')->all();
            $dbParticipants = OfflineTrainingUser::whereIn('offline_training_id', $trainingIds)
                ->select(['offline_training_id', 'user_id', 'employee_id'])
                ->get();
            foreach ($dbParticipants as $p) {
                if ($p->user_id) {
                    $existingParticipantMap[$p->offline_training_id . ':uid:' . $p->user_id] = true;
                }
                if ($p->employee_id) {
                    $existingParticipantMap[$p->offline_training_id . ':emp:' . strtolower(trim($p->employee_id))] = true;
                }
            }
        }

        $importedParticipants = 0;
        $skippedDuplicates = 0;
        $invalidRows = 0;
        $createdTrainings = 0;
        $reusedTrainings = 0;

        $groupedByTraining = [];
        $seenInFileParticipants = [];

        foreach ($rawRows as $item) {
            $rNum = $item['excel_row'];
            $vals = $item['values'];
            $normalized = $this->normalizeRowData($vals);
            $validation = $this->validateRowData($normalized, $vals, $rNum);

            if (!empty($validation['errors'])) {
                $invalidRows++;
                continue;
            }

            $employeeId = $normalized['employee_id'];
            $participantName = $normalized['name'];
            $userMatch = $this->matchParticipantUser($employeeId, $participantName, $userMapByNip, $usersMapByName);
            $normalized['user_id'] = $userMatch['user_id'];

            $fp = $this->buildTrainingFingerprint($normalized);
            $isExistingTraining = isset($existingTrainings[$fp]);
            $partKey = $normalized['user_id'] ? ('uid:' . $normalized['user_id']) : ('emp:' . strtolower($employeeId ?: ''));

            // Check duplicate in same file
            if (isset($seenInFileParticipants[$fp][$partKey])) {
                $skippedDuplicates++;
                continue;
            }
            $seenInFileParticipants[$fp][$partKey] = true;

            // Check duplicate in DB
            if ($isExistingTraining) {
                $dbKey = $existingTrainings[$fp]->id . ':' . $partKey;
                if (isset($existingParticipantMap[$dbKey])) {
                    $skippedDuplicates++;
                    continue;
                }
            }

            if (!isset($groupedByTraining[$fp])) {
                $groupedByTraining[$fp] = [
                    'training_data' => $normalized,
                    'participants'  => [],
                ];
            }

            $groupedByTraining[$fp]['participants'][] = $normalized;
        }

        // DB Transaction for persistence
        DB::transaction(function () use (
            $groupedByTraining,
            &$existingTrainings,
            $userId,
            &$createdTrainings,
            &$reusedTrainings,
            &$importedParticipants
        ) {
            $now = now();

            foreach ($groupedByTraining as $fp => $group) {
                $tData = $group['training_data'];
                $participants = $group['participants'];

                // Get or create training
                if (isset($existingTrainings[$fp])) {
                    $training = $existingTrainings[$fp];
                    $reusedTrainings++;
                } else {
                    $training = OfflineTraining::create([
                        'course_name'           => $tData['course_name'],
                        'sub_course_name'       => $tData['sub_course_name'],
                        'type'                  => $tData['type'],
                        'category'              => $tData['category'],
                        'level'                 => $tData['level'],
                        'batch'                 => $tData['batch'],
                        'main_classification'   => $tData['main_classification'],
                        'sub_classification'    => $tData['sub_classification'],
                        'counted_corporate_kpi' => $tData['counted_corporate_kpi'],
                        'sub_corporate_kpi'     => $tData['sub_corporate_kpi'],
                        'corporate_kpi_year'    => $tData['corporate_kpi_year'] ?? (!empty($tData['start_date']) ? (int) substr((string) $tData['start_date'], 0, 4) : null),
                        'delivery_method'       => $tData['delivery_method'],
                        'sub_delivery_method'   => $tData['sub_delivery_method'],
                        'provider'              => $tData['provider'],
                        'sub_provider'          => $tData['sub_provider'],
                        'provider_name'         => $tData['provider_name'],
                        'venue'                 => $tData['venue'],
                        'instructor'            => $tData['instructor'],
                        'duration_seconds'      => $tData['duration_seconds'],
                        'learning_hours'        => $tData['learning_hours'],
                        'start_date'            => $tData['start_date'],
                        'finish_date'           => $tData['finish_date'],
                        'created_by'            => $userId,
                    ]);
                    $existingTrainings[$fp] = $training;
                    $createdTrainings++;

                    // Auto-register legitimate options from import data
                    app(OfflineOptionService::class)->ensureOptionsFromTraining($tData, 'import', $userId);
                }

                // Prepare participant records
                $participantInserts = [];
                foreach ($participants as $p) {
                    $participantInserts[] = [
                        'offline_training_id' => $training->id,
                        'user_id'             => $p['user_id'],
                        'employee_id'         => $p['employee_id'],
                        'name'                => $p['name'],
                        'grade'               => $p['grade'],
                        'job_position'        => $p['job_position'],
                        'team'                => $p['team'],
                        'department'          => $p['department'],
                        'company'             => $p['company'],
                        'learning_hours'      => $p['learning_hours'],
                        'progress'            => $p['progress'],
                        'assessment_score'    => $p['assessment_score'],
                        'certificate'         => $p['certificate'],
                        'attendance'          => $p['attendance'],
                        'attendance_reason'   => $p['attendance_reason'],
                        'bond_status'         => $p['bond_status'],
                        'bond_end_date'       => $p['bond_end_date'],
                        'bond_amount'         => $p['bond_amount'],
                        'l1_program'          => $p['l1_program'],
                        'l2_numerical'        => $p['l2_numerical'],
                        'l2_verbal'           => $p['l2_verbal'],
                        'l3_numerical'        => $p['l3_numerical'],
                        'l3_verbal'           => $p['l3_verbal'],
                        'pic'                 => $p['pic'],
                        'created_at'          => $now,
                        'updated_at'          => $now,
                    ];
                }

                // Chunk insert participants for high throughput
                foreach (array_chunk($participantInserts, 250) as $chunk) {
                    OfflineTrainingUser::insert($chunk);
                    $importedParticipants += count($chunk);
                }
            }
        });

        // Cleanup temporary files
        $this->cleanupTemporaryFile($token, $userId);

        return [
            'success'             => true,
            'rows_processed'      => count($rawRows),
            'imported'            => $importedParticipants,
            'skipped_duplicates'  => $skippedDuplicates,
            'invalid'             => $invalidRows,
            'trainings_created'   => $createdTrainings,
            'trainings_reused'    => $reusedTrainings,
        ];
    }

    /**
     * Resolves column positions for grouped two-row or single-row headers.
     * Delegates to OfflineTrainingExcelSchema for automatic detection and per-column matching.
     */
    public function resolveHeaderMapping(Worksheet $worksheet): array
    {
        $formatInfo = OfflineTrainingExcelSchema::detectTemplateFormat($worksheet);
        $headerCheck = OfflineTrainingExcelSchema::analyzeHeaders(
            $formatInfo['found_headers'],
            $formatInfo['format']
        );

        return [
            'format'           => $formatInfo['format'],
            'header_row'       => $formatInfo['header_row'],
            'data_start_row'   => $formatInfo['data_start_row'],
            'group_row'        => $formatInfo['group_row'],
            'column_positions' => $headerCheck['column_positions'],
            'column_keys'      => $headerCheck['column_keys'],
            'header_check'     => $headerCheck,
            'can_proceed'      => $headerCheck['can_proceed'],
            'unknown_headers'  => array_column($headerCheck['issues'], 'message'),
        ];
    }

    /**
     * Match participant against LMS users according to strict safety hierarchy:
     * 1. NIP / id_emp exact match
     * 2. Normalized NIP match (trimmed, leading zeros stripped)
     * 3. Normalized exact name match (only if unique candidate)
     * 4. Safe typo tolerance for name when NIP is confirmed
     * 5. Flag ambiguous matches when multiple candidates share the same name without NIP
     */
    public function matchParticipantUser(?string $employeeId, ?string $participantName, array $userMapByNip, array $usersMapByName): array
    {
        $employeeId = trim((string) $employeeId);
        $participantName = trim((string) $participantName);

        $matchedUser = null;
        $matchType = null;
        $warningMessage = null;

        // 1. Employee ID / NIP Match (Highest Priority)
        if ($employeeId !== '') {
            $nipKey = strtolower($employeeId);
            $unpaddedNipKey = strtolower(ltrim($employeeId, '0'));

            if (isset($userMapByNip[$nipKey])) {
                $matchedUser = $userMapByNip[$nipKey];
                $matchType = 'nip_exact';
            } elseif ($unpaddedNipKey !== '' && isset($userMapByNip[$unpaddedNipKey])) {
                $matchedUser = $userMapByNip[$unpaddedNipKey];
                $matchType = 'nip_normalized';
            }

            if ($matchedUser) {
                // Verify Name correspondence
                if ($participantName !== '') {
                    $normRowName = mb_strtolower(trim(preg_replace('/\s+/', ' ', $participantName)));
                    $normDbName = mb_strtolower(trim(preg_replace('/\s+/', ' ', $matchedUser->name)));

                    if ($normRowName !== $normDbName) {
                        $lev = levenshtein($normRowName, $normDbName);
                        if ($lev <= 2) {
                            $matchType = 'nip_with_name_normalized';
                        } else {
                            $warningMessage = "NIP matches user '{$matchedUser->name}', but Excel participant name is '{$participantName}'.";
                        }
                    }
                }

                return [
                    'user'       => $matchedUser,
                    'user_id'    => $matchedUser->id,
                    'match_type' => $matchType,
                    'warning'    => $warningMessage,
                ];
            }
        }

        // 2. Participant Name Match (Only when NIP is empty or not found in LMS)
        if ($participantName !== '') {
            $normRowName = mb_strtolower(trim(preg_replace('/\s+/', ' ', $participantName)));
            $candidates = $usersMapByName[$normRowName] ?? [];

            if (count($candidates) === 1) {
                $matchedUser = $candidates[0];
                return [
                    'user'       => $matchedUser,
                    'user_id'    => $matchedUser->id,
                    'match_type' => 'name_unique_match',
                    'warning'    => $employeeId === '' ? null : "NIP '{$employeeId}' not found in LMS. Linked by unique participant name.",
                ];
            }

            if (count($candidates) > 1) {
                // Ambiguous candidate match: Multiple users found with the exact same name! DO NOT GUESS!
                return [
                    'user'       => null,
                    'user_id'    => null,
                    'match_type' => 'ambiguous_name',
                    'warning'    => "AMBIGUOUS PARTICIPANT MATCH: Multiple LMS users found with name '{$participantName}'. Please provide Employee ID (NIP) to identify participant.",
                ];
            }
        }

        return [
            'user'       => null,
            'user_id'    => null,
            'match_type' => 'not_found',
            'warning'    => $employeeId ? "LMS user account not found for Employee ID '{$employeeId}'. Will import with user_id NULL." : null,
        ];
    }

    /**
     * Validate that minimum required headers exist.
     */
    protected function validateRequiredHeaders(array $columnKeys): void
    {
        $required = OfflineTrainingExcelSchema::requiredDatabaseKeys();
        $missing = [];

        foreach ($required as $field) {
            if (!in_array($field, $columnKeys, true)) {
                $missing[] = match ($field) {
                    'course_name' => 'Course Name',
                    'employee_id' => 'ID (Employee ID / NIP)',
                    'name'        => 'Name',
                    default       => $field,
                };
            }
        }

        if (!empty($missing)) {
            throw new \InvalidArgumentException('Import cannot continue. Missing required columns: ' . implode(', ', $missing) . '.');
        }
    }

    /**
     * Read cell values for mapped columns in a specific row.
     */
    protected function readRowValues(Worksheet $worksheet, int $row, array $columnPositions): array
    {
        $values = [];
        foreach ($columnPositions as $key => $colLetter) {
            $cell = $worksheet->getCell($colLetter . $row);
            $val = $cell->getValue();

            // Handle date cell formatting from PhpSpreadsheet
            if ($val !== null && in_array($key, ['start_date', 'finish_date', 'bond_end_date'], true)) {
                if (is_numeric($val)) {
                    try {
                        $dt = ExcelDate::excelToDateTimeObject($val);
                        $val = $dt->format('Y-m-d');
                    } catch (\Throwable $e) {
                        // fallback to string below
                    }
                }
            }

            // Handle duration time fraction from PhpSpreadsheet
            if ($val !== null && $key === 'duration_seconds' && is_numeric($val) && $val < 1 && $val > 0) {
                // Excel time value fraction of a day (e.g. 0.041666 = 1 hour)
                $val = (int) round($val * 86400);
            }

            $values[$key] = $val;
        }

        return $values;
    }

    /**
     * Check if all cells in a row are blank.
     */
    protected function isRowEntirelyBlank(array $values): bool
    {
        foreach ($values as $v) {
            if ($v !== null && trim((string) $v) !== '') {
                return false;
            }
        }
        return true;
    }

    /**
     * Normalize raw cell data into strict canonical database formats.
     */
    public function normalizeRowData(array $raw): array
    {
        return [
            // Training Information
            'course_name'           => $this->normalizeString($raw['course_name'] ?? null),
            'sub_course_name'       => $this->normalizeString($raw['sub_course_name'] ?? null),
            'type'                  => $this->normalizeString($raw['type'] ?? null),
            'category'              => $this->normalizeString($raw['category'] ?? null),
            'level'                 => $this->normalizeString($raw['level'] ?? null),
            'batch'                 => $this->normalizeString($raw['batch'] ?? null),
            'main_classification'   => $this->normalizeString($raw['main_classification'] ?? null),
            'sub_classification'    => $this->normalizeString($raw['sub_classification'] ?? null),
            'counted_corporate_kpi' => $this->normalizeBoolean($raw['counted_corporate_kpi'] ?? null),
            'sub_corporate_kpi'     => $this->normalizeString($raw['sub_corporate_kpi'] ?? null),
            'corporate_kpi_year'    => !empty($raw['corporate_kpi_year'])
                ? (int) $raw['corporate_kpi_year']
                : (!empty($raw['start_date']) ? (int) substr((string) $raw['start_date'], 0, 4) : null),
            'delivery_method'       => $this->normalizeString($raw['delivery_method'] ?? null),
            'sub_delivery_method'   => $this->normalizeString($raw['sub_delivery_method'] ?? null),
            'provider'              => $this->normalizeString($raw['provider'] ?? null),
            'sub_provider'          => $this->normalizeString($raw['sub_provider'] ?? null),
            'provider_name'         => $this->normalizeString($raw['provider_name'] ?? null),
            'venue'                 => $this->normalizeString($raw['venue'] ?? null),
            'instructor'            => $this->normalizeString($raw['instructor'] ?? null),
            'duration_seconds'      => $this->normalizeDuration($raw['duration_seconds'] ?? null),
            'learning_hours'        => $this->normalizeLearningHours($raw['learning_hours'] ?? null),
            'start_date'            => $this->normalizeDate($raw['start_date'] ?? null),
            'finish_date'           => $this->normalizeDate($raw['finish_date'] ?? null),

            // Job Information
            'employee_id'           => $this->normalizeString($raw['employee_id'] ?? null),
            'name'                  => $this->normalizeString($raw['name'] ?? null),
            'grade'                 => $this->normalizeString($raw['grade'] ?? null),
            'job_position'          => $this->normalizeString($raw['job_position'] ?? null),
            'team'                  => $this->normalizeString($raw['team'] ?? null),
            'department'            => $this->normalizeString($raw['department'] ?? null),
            'company'               => $this->normalizeString($raw['company'] ?? null),

            // Participant Learning Record
            'progress'              => $this->normalizeProgress($raw['progress'] ?? null),
            'assessment_score'      => $this->normalizeAssessmentScore($raw['assessment_score'] ?? null),
            'certificate'           => $this->normalizeString($raw['certificate'] ?? null),
            'attendance'            => $this->normalizeBoolean($raw['attendance'] ?? null),
            'attendance_reason'     => $this->normalizeString($raw['attendance_reason'] ?? null),

            // Obligation
            'bond_status'           => $this->normalizeString($raw['bond_status'] ?? null),
            'bond_end_date'         => $this->normalizeDate($raw['bond_end_date'] ?? null),
            'bond_amount'           => $this->normalizeMoney($raw['bond_amount'] ?? null),

            // Kirkpatrick Evaluations
            'l1_program'            => $this->normalizeString($raw['l1_program'] ?? null),
            'l2_numerical'          => $this->normalizeDecimal($raw['l2_numerical'] ?? null, 4),
            'l2_verbal'             => $this->normalizeString($raw['l2_verbal'] ?? null),
            'l3_numerical'          => $this->normalizeDecimal($raw['l3_numerical'] ?? null, 4),
            'l3_verbal'             => $this->normalizeString($raw['l3_verbal'] ?? null),
            'pic'                   => $this->normalizeString($raw['pic'] ?? null),
        ];
    }

    /**
     * Validate row data against business integrity and strict type rules (Phase B).
     */
    public function validateRowData(array $row, array $raw = [], int $excelRow = 0): array
    {
        $errors = [];
        $warnings = [];
        $rowPrefix = $excelRow > 0 ? "Excel Row: {$excelRow} | " : "";

        // Required checks
        if (!$row['course_name']) {
            $errors[] = "{$rowPrefix}Column: Course Name | Expected: Course Name is required | Status: INVALID VALUE";
        }
        if (!$row['employee_id']) {
            $errors[] = "{$rowPrefix}Column: ID | Expected: Employee ID (NIP) is required | Status: INVALID VALUE";
        }
        if (!$row['name']) {
            $errors[] = "{$rowPrefix}Column: Name | Expected: Participant Name is required | Status: INVALID VALUE";
        }

        // Assessment Score type & bounds validation
        $rawScore = $raw['assessment_score'] ?? null;
        if ($rawScore !== null && trim((string) $rawScore) !== '' && !in_array(trim((string) $rawScore), ['-', 'N/A', 'n/a', 'NA'], true)) {
            $cleanScore = str_replace(',', '.', trim((string) $rawScore));
            if (!is_numeric($cleanScore)) {
                $errors[] = "{$rowPrefix}Column: Assessment Score | Value: '{$rawScore}' | Expected: Numeric value | Status: INVALID VALUE";
            } elseif ((float) $cleanScore < 0 || (float) $cleanScore > 100) {
                $errors[] = "{$rowPrefix}Column: Assessment Score | Value: '{$rawScore}' | Expected: Numeric value between 0 and 100 | Status: INVALID VALUE";
            }
        }

        // Progress bounds and numeric validation
        $rawProg = $raw['progress'] ?? null;
        if ($rawProg !== null && trim((string) $rawProg) !== '' && !in_array(trim((string) $rawProg), ['-', 'N/A', 'n/a', 'NA'], true)) {
            if ($row['progress'] === null) {
                $errors[] = "{$rowPrefix}Column: Progress | Value: '{$rawProg}' | Expected: Numeric percentage (0-100) | Status: INVALID VALUE";
            } elseif ($row['progress'] < 0 || $row['progress'] > 100) {
                $errors[] = "{$rowPrefix}Column: Progress | Value: '{$rawProg}' | Expected: Progress must be between 0% and 100% | Status: INVALID VALUE";
            }
        }

        // Learning Hours numeric validation
        $rawHours = $raw['learning_hours'] ?? null;
        if ($rawHours !== null && trim((string) $rawHours) !== '' && !in_array(trim((string) $rawHours), ['-', 'N/A', 'n/a', 'NA'], true)) {
            if ($row['learning_hours'] === null) {
                $errors[] = "{$rowPrefix}Column: Learning (Hours) | Value: '{$rawHours}' | Expected: Numeric hours value (e.g. 8.0) | Status: INVALID VALUE";
            }
        }

        // Duration validation
        $rawDur = $raw['duration_seconds'] ?? null;
        if ($rawDur !== null && trim((string) $rawDur) !== '' && !in_array(trim((string) $rawDur), ['-', 'N/A', 'n/a', 'NA'], true)) {
            if ($row['duration_seconds'] === null) {
                $errors[] = "{$rowPrefix}Column: Duration | Value: '{$rawDur}' | Expected: Valid duration (HH:MM:SS or hours) | Status: INVALID VALUE";
            }
        }

        // Dates validation
        $rawStart = $raw['start_date'] ?? null;
        if ($rawStart !== null && trim((string) $rawStart) !== '' && !in_array(trim((string) $rawStart), ['-', 'N/A', 'n/a', 'NA'], true)) {
            if ($row['start_date'] === null) {
                $errors[] = "{$rowPrefix}Column: Start Date | Value: '{$rawStart}' | Expected: Valid date format (YYYY-MM-DD) | Status: INVALID VALUE";
            }
        }

        $rawFinish = $raw['finish_date'] ?? null;
        if ($rawFinish !== null && trim((string) $rawFinish) !== '' && !in_array(trim((string) $rawFinish), ['-', 'N/A', 'n/a', 'NA'], true)) {
            if ($row['finish_date'] === null) {
                $errors[] = "{$rowPrefix}Column: Finish Date | Value: '{$rawFinish}' | Expected: Valid date format (YYYY-MM-DD) | Status: INVALID VALUE";
            }
        }

        if ($row['start_date'] && $row['finish_date']) {
            if ($row['finish_date'] < $row['start_date']) {
                $errors[] = "{$rowPrefix}Column: Finish Date | Value: '{$row['finish_date']}' | Expected: Finish date cannot be earlier than start date ({$row['start_date']}) | Status: INVALID VALUE";
            }
        }

        // Bond validation
        $rawBondAmt = $raw['bond_amount'] ?? null;
        if ($rawBondAmt !== null && trim((string) $rawBondAmt) !== '' && !in_array(trim((string) $rawBondAmt), ['-', 'N/A', 'n/a', 'NA'], true)) {
            if ($row['bond_amount'] === null) {
                $errors[] = "{$rowPrefix}Column: Bond Amount | Value: '{$rawBondAmt}' | Expected: Numeric currency amount | Status: INVALID VALUE";
            }
        }

        $rawBondEnd = $raw['bond_end_date'] ?? null;
        if ($rawBondEnd !== null && trim((string) $rawBondEnd) !== '' && !in_array(trim((string) $rawBondEnd), ['-', 'N/A', 'n/a', 'NA'], true)) {
            if ($row['bond_end_date'] === null) {
                $errors[] = "{$rowPrefix}Column: Bond End Date | Value: '{$rawBondEnd}' | Expected: Valid date format (YYYY-MM-DD) | Status: INVALID VALUE";
            }
        }

        // L2 Numerical
        $rawL2Num = $raw['l2_numerical'] ?? null;
        if ($rawL2Num !== null && trim((string) $rawL2Num) !== '' && !in_array(trim((string) $rawL2Num), ['-', 'N/A', 'n/a', 'NA'], true)) {
            if ($row['l2_numerical'] === null) {
                $errors[] = "{$rowPrefix}Column: Numerical (L2) | Value: '{$rawL2Num}' | Expected: Numeric score (0-100) | Status: INVALID VALUE";
            }
        }

        // L3 Numerical
        $rawL3Num = $raw['l3_numerical'] ?? null;
        if ($rawL3Num !== null && trim((string) $rawL3Num) !== '' && !in_array(trim((string) $rawL3Num), ['-', 'N/A', 'n/a', 'NA'], true)) {
            if ($row['l3_numerical'] === null) {
                $errors[] = "{$rowPrefix}Column: Numerical (L3) | Value: '{$rawL3Num}' | Expected: Numeric score (0-100) | Status: INVALID VALUE";
            }
        }

        // Boolean Fields
        $rawAtt = $raw['attendance'] ?? null;
        if ($rawAtt !== null && trim((string) $rawAtt) !== '' && !in_array(trim((string) $rawAtt), ['-', 'N/A', 'n/a', 'NA'], true)) {
            if ($row['attendance'] === null) {
                $errors[] = "{$rowPrefix}Column: Attendance Yes/No | Value: '{$rawAtt}' | Expected: Yes or No boolean | Status: INVALID VALUE";
            }
        }

        $rawKpi = $raw['counted_corporate_kpi'] ?? null;
        if ($rawKpi !== null && trim((string) $rawKpi) !== '' && !in_array(trim((string) $rawKpi), ['-', 'N/A', 'n/a', 'NA'], true)) {
            if ($row['counted_corporate_kpi'] === null) {
                $errors[] = "{$rowPrefix}Column: Counted as '26 Corporate KPI | Value: '{$rawKpi}' | Expected: Yes or No boolean | Status: INVALID VALUE";
            }
        }

        // Non-fatal warnings
        if ($row['start_date'] === null && $row['finish_date'] === null) {
            $warnings[] = "Training dates are blank.";
        }
        if ($row['learning_hours'] === null) {
            $warnings[] = "Learning hours are blank.";
        }

        return [
            'errors'   => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Build stable canonical fingerprint for grouping identical training events.
     */
    public function buildTrainingFingerprint(array $data): string
    {
        $course = strtolower(trim($data['course_name'] ?? ''));
        $batch = strtolower(trim($data['batch'] ?? ''));
        $startDate = $data['start_date'] ?? '';
        $finishDate = $data['finish_date'] ?? '';
        $type = strtolower(trim($data['type'] ?? ''));
        $venue = strtolower(trim($data['venue'] ?? ''));

        return md5("{$course}|{$batch}|{$startDate}|{$finishDate}|{$type}|{$venue}");
    }

    // =========================================================================
    // FIELD NORMALIZERS
    // =========================================================================

    public function cleanNullableValue($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $str = trim((string) $value);
        if ($str === '' || in_array($str, ['-', '--', '---', 'N/A', 'n/a', 'NA', 'None', 'null', 'NULL'], true)) {
            return null;
        }
        return $str;
    }

    public function normalizeString($value): ?string
    {
        return $this->cleanNullableValue($value);
    }

    public function normalizeBoolean($value): ?bool
    {
        $clean = $this->cleanNullableValue($value);
        if ($clean === null) {
            return null;
        }

        $str = strtolower($clean);

        if (in_array($str, ['yes', 'y', '1', 'true', 'ya'], true)) {
            return true;
        }
        if (in_array($str, ['no', 'n', '0', 'false', 'tidak'], true)) {
            return false;
        }

        return null;
    }

    public function normalizeProgress($value): ?float
    {
        $clean = $this->cleanNullableValue($value);
        if ($clean === null) {
            return null;
        }

        $str = str_replace(['%', ' '], '', $clean);

        if (!is_numeric($str)) return null;

        $num = (float) $str;
        if ($num > 0 && $num <= 1.0 && str_contains($str, '.')) {
            $num = $num * 100.0;
        }

        return round($num, 4);
    }

    public function normalizeDuration($value): ?int
    {
        $clean = $this->cleanNullableValue($value);
        if ($clean === null) {
            return null;
        }

        $str = $clean;

        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $str, $matches)) {
            $h = (int) $matches[1];
            $m = (int) $matches[2];
            $s = isset($matches[3]) ? (int) $matches[3] : 0;
            return ($h * 3600) + ($m * 60) + $s;
        }

        if (is_numeric($str)) {
            $num = (float) $str;
            if ($num > 0 && $num <= 24) {
                return (int) round($num * 3600);
            }
            return (int) round($num);
        }

        return null;
    }

    public function normalizeLearningHours($value): ?float
    {
        $clean = $this->cleanNullableValue($value);
        if ($clean === null) {
            return null;
        }

        $str = str_replace(['hours', 'hour', 'jam', ' '], '', strtolower($clean));
        $str = str_replace(',', '.', $str);

        if (!is_numeric($str)) return null;

        return round((float) $str, 4);
    }

    public function normalizeDate($value): ?string
    {
        if ($value === null) return null;
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $clean = $this->cleanNullableValue($value);
        if ($clean === null) {
            return null;
        }

        if (is_numeric($clean) && (float) $clean > 1000) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $clean)->format('Y-m-d');
            } catch (\Throwable $e) {
                // fallback
            }
        }

        try {
            return Carbon::parse($clean)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function normalizeMoney($value): ?float
    {
        $clean = $this->cleanNullableValue($value);
        if ($clean === null) {
            return null;
        }

        $str = preg_replace('/^(?:rp|idr|usd|\$)\.?\s*/i', '', $clean);
        $str = trim($str);

        if (str_contains($str, '.') && str_contains($str, ',')) {
            if (strrpos($str, '.') > strrpos($str, ',')) {
                $str = str_replace(',', '', $str);
            } else {
                $str = str_replace('.', '', $str);
                $str = str_replace(',', '.', $str);
            }
        } elseif (str_contains($str, '.') && !str_contains($str, ',')) {
            $parts = explode('.', $str);
            if (count($parts) > 1 && strlen(end($parts)) === 3) {
                $str = str_replace('.', '', $str);
            }
        } elseif (str_contains($str, ',') && !str_contains($str, '.')) {
            $str = str_replace(',', '', $str);
        }

        if (!is_numeric($str)) return null;

        return round((float) $str, 2);
    }

    public function normalizeAssessmentScore($value): ?float
    {
        $clean = $this->cleanNullableValue($value);
        if ($clean === null) {
            return null;
        }

        $str = str_replace(',', '.', $clean);

        if (!is_numeric($str)) return null;

        return round((float) $str, 2);
    }

    public function normalizeDecimal($value, int $decimals = 4): ?float
    {
        $clean = $this->cleanNullableValue($value);
        if ($clean === null) {
            return null;
        }

        $str = str_replace(',', '.', $clean);

        if (!is_numeric($str)) return null;

        return round((float) $str, $decimals);
    }
}
