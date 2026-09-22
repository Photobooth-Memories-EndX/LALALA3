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
     * Parse workbook, validate rows, detect duplicates, and generate preview payload.
     */
    public function parseAndPreview(string $token, int $userId): array
    {
        $metadata = $this->getValidTemporaryMetadata($token, $userId);
        $filePath = $metadata['temp_path'];

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(false);
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();

        $headerMapping = $this->resolveHeaderMapping($worksheet);
        $this->validateRequiredHeaders($headerMapping['column_keys']);

        $highestRow = $worksheet->getHighestDataRow();
        $startDataRow = $headerMapping['data_start_row'];

        // Collect all NIPs to batch-lookup LMS users
        $rawRows = [];
        $uniqueNips = [];
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

            $course = trim((string) ($rowValues['course_name'] ?? ''));
            if ($course !== '') {
                $uniqueCourses[$course] = true;
            }
        }

        // Batch lookup LMS users
        $userMap = [];
        if (!empty($uniqueNips)) {
            $users = User::whereIn('id_emp', array_keys($uniqueNips))
                ->select(['id', 'name', 'id_emp'])
                ->get();
            foreach ($users as $u) {
                $userMap[strtolower(trim($u->id_emp))] = $u;
            }
        }

        // Batch load potential existing OfflineTrainings
        $existingTrainings = [];
        if (!empty($uniqueCourses)) {
            $dbTrainings = OfflineTraining::whereIn('course_name', array_keys($uniqueCourses))
                ->get();
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
        ];

        $seenInFileParticipants = [];
        $fileTrainingFingerprints = [];

        foreach ($rawRows as $item) {
            $rNum = $item['excel_row'];
            $vals = $item['values'];

            $normalized = $this->normalizeRowData($vals);
            $validation = $this->validateRowData($normalized);

            // User lookup
            $employeeId = $normalized['employee_id'];
            $matchedUser = null;
            if ($employeeId && isset($userMap[strtolower($employeeId)])) {
                $matchedUser = $userMap[strtolower($employeeId)];
                $normalized['user_id'] = $matchedUser->id;
            } else {
                $normalized['user_id'] = null;
            }

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
                $stats['invalid']++;
            } elseif ($isDuplicate) {
                $rowStatus = 'duplicate';
                $messages[] = $duplicateReason;
                $stats['duplicate']++;
            } elseif (!empty($validation['warnings']) || !$matchedUser) {
                $rowStatus = 'warning';
                if (!$matchedUser && $employeeId) {
                    $messages[] = 'LMS user account not found. Will import with user_id NULL.';
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
                'training_exists'    => $isExistingTraining,
                'training_fp'        => $fp,
                'data'               => $normalized,
            ];
        }

        // Cache summary and metadata for confirm execution
        $metadata['stats'] = $stats;
        $metadata['unknown_headers'] = $headerMapping['unknown_headers'];
        Cache::put(self::CACHE_PREFIX . $token, $metadata, self::CACHE_TTL_SECONDS);

        return [
            'token'           => $token,
            'filename'        => $metadata['original_filename'],
            'stats'           => $stats,
            'unknown_headers' => $headerMapping['unknown_headers'],
            'preview_rows'    => array_slice($parsedRows, 0, 100), // First 100 for fast UI rendering
            'total_preview'   => count($parsedRows),
        ];
    }

    /**
     * Execute full import inside database transactions.
     */
    public function executeImport(string $token, int $userId): array
    {
        $metadata = $this->getValidTemporaryMetadata($token, $userId);
        $filePath = $metadata['temp_path'];

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(false);
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();

        $headerMapping = $this->resolveHeaderMapping($worksheet);
        $highestRow = $worksheet->getHighestDataRow();
        $startDataRow = $headerMapping['data_start_row'];

        $rawRows = [];
        $uniqueNips = [];
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
            $course = trim((string) ($rowValues['course_name'] ?? ''));
            if ($course !== '') {
                $uniqueCourses[$course] = true;
            }
        }

        // Batch users
        $userMap = [];
        if (!empty($uniqueNips)) {
            $users = User::whereIn('id_emp', array_keys($uniqueNips))
                ->select(['id', 'name', 'id_emp'])
                ->get();
            foreach ($users as $u) {
                $userMap[strtolower(trim($u->id_emp))] = $u;
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
            $vals = $item['values'];
            $normalized = $this->normalizeRowData($vals);
            $validation = $this->validateRowData($normalized);

            if (!empty($validation['errors'])) {
                $invalidRows++;
                continue;
            }

            $employeeId = $normalized['employee_id'];
            if ($employeeId && isset($userMap[strtolower($employeeId)])) {
                $normalized['user_id'] = $userMap[strtolower($employeeId)]->id;
            } else {
                $normalized['user_id'] = null;
            }

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
     */
    public function resolveHeaderMapping(Worksheet $worksheet): array
    {
        $highestColumn = $worksheet->getHighestDataColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

        // Check Row 1 and Row 2
        $row1Cells = [];
        $row2Cells = [];

        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $colLetter = Coordinate::stringFromColumnIndex($col);
            $row1Cells[$colLetter] = trim((string) $worksheet->getCell($colLetter . '1')->getValue());
            $row2Cells[$colLetter] = trim((string) $worksheet->getCell($colLetter . '2')->getValue());
        }

        // Determine if Row 1 is a grouped header (HRDC parent category groups)
        $isGrouped = false;
        $row1Text = strtolower(implode(' ', $row1Cells));
        if (str_contains($row1Text, 'job information') || str_contains($row1Text, 'training information')) {
            $isGrouped = true;
        }

        $columnPositions = [];
        $columnKeys = [];
        $unknownHeaders = [];

        if ($isGrouped) {
            $currentGroup = '';
            // Propagate merged parent groups across columns
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $colLetter = Coordinate::stringFromColumnIndex($col);
                $r1 = $row1Cells[$colLetter];
                $r2 = $row2Cells[$colLetter];

                if ($r1 !== '') {
                    $currentGroup = $r1;
                }

                $mappedKey = $this->matchGroupedHeader($currentGroup, $r2);
                if ($mappedKey) {
                    $columnPositions[$mappedKey] = $colLetter;
                    $columnKeys[] = $mappedKey;
                } elseif ($r2 !== '' && !in_array(strtolower($r2), ['no', 'month', 'bulan'], true)) {
                    $unknownHeaders[] = ($currentGroup ? "{$currentGroup} > " : '') . $r2;
                }
            }

            return [
                'data_start_row'   => 3,
                'column_positions' => $columnPositions,
                'column_keys'      => $columnKeys,
                'unknown_headers'  => $unknownHeaders,
            ];
        }

        // Fallback: Single-row header on Row 1
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $colLetter = Coordinate::stringFromColumnIndex($col);
            $header = $row1Cells[$colLetter];
            $mappedKey = $this->matchSingleHeader($header);

            if ($mappedKey) {
                $columnPositions[$mappedKey] = $colLetter;
                $columnKeys[] = $mappedKey;
            } elseif ($header !== '' && !in_array(strtolower($header), ['no', 'month', 'bulan'], true)) {
                $unknownHeaders[] = $header;
            }
        }

        return [
            'data_start_row'   => 2,
            'column_positions' => $columnPositions,
            'column_keys'      => $columnKeys,
            'unknown_headers'  => $unknownHeaders,
        ];
    }

    /**
     * Match grouped header (Parent Group + Child Column).
     */
    protected function matchGroupedHeader(string $group, string $child): ?string
    {
        $g = str_replace(['_', '-'], ' ', strtolower(trim($group)));
        $c = str_replace(['_', '-'], ' ', strtolower(trim($child)));

        if ($c === '' || $c === 'no') {
            return null;
        }

        // 1. Job Information
        if (str_contains($g, 'job')) {
            if ($c === 'id' || str_contains($c, 'employee id') || str_contains($c, 'nip')) return 'employee_id';
            if ($c === 'name' || str_contains($c, 'nama')) return 'name';
            if ($c === 'grade') return 'grade';
            if ($c === 'job position' || str_contains($c, 'position') || str_contains($c, 'jabatan')) return 'job_position';
            if ($c === 'team' || str_contains($c, 'unit')) return 'team';
            if ($c === 'department' || str_contains($c, 'dept')) return 'department';
            if ($c === 'company' || str_contains($c, 'perusahaan')) return 'company';
        }

        // 2. Training Information
        if (str_contains($g, 'training')) {
            if (str_contains($c, 'sub course') || $c === 'sub course name') return 'sub_course_name';
            if ($c === 'course name' || str_contains($c, 'course name') || str_contains($c, 'nama training') || $c === 'course') return 'course_name';
            if ($c === 'type' || str_contains($c, 'tipe')) return 'type';
            if ($c === 'category' || str_contains($c, 'kategori')) return 'category';
            if ($c === 'level') return 'level';
            if ($c === 'batch') return 'batch';
            if ($c === 'main classification' || $c === 'classification') return 'main_classification';
            if ($c === 'sub classification') return 'sub_classification';
            if (str_contains($c, 'corporate kpi') && !str_contains($c, 'sub')) return 'counted_corporate_kpi';
            if (str_contains($c, 'sub') && str_contains($c, 'corporate kpi')) return 'sub_corporate_kpi';
            if ($c === 'delivery method' || (str_contains($c, 'delivery') && !str_contains($c, 'sub'))) return 'delivery_method';
            if (str_contains($c, 'sub') && str_contains($c, 'delivery')) return 'sub_delivery_method';
            if ($c === 'provider' && !str_contains($c, 'name') && !str_contains($c, 'sub')) return 'provider';
            if (str_contains($c, 'sub') && str_contains($c, 'provider')) return 'sub_provider';
            if (str_contains($c, 'provider name')) return 'provider_name';
            if ($c === 'duration' || str_contains($c, 'durasi')) return 'duration_seconds';
            if (str_contains($c, 'learning') && str_contains($c, 'hour')) return 'learning_hours';
            if ($c === 'progress') return 'progress';
            if ($c === 'start date' || str_contains($c, 'start')) return 'start_date';
            if ($c === 'finish date' || str_contains($c, 'finish') || str_contains($c, 'end date')) return 'finish_date';
            if ($c === 'month' || str_contains($c, 'bulan')) return null; // Month is derived, never stored
            if ($c === 'venue' || str_contains($c, 'lokasi')) return 'venue';
            if ($c === 'instructor' || str_contains($c, 'trainer') || str_contains($c, 'instruktur')) return 'instructor';
            if (str_contains($c, 'assessment') || $c === 'score' || str_contains($c, 'nilai')) return 'assessment_score';
            if ($c === 'certificate' || str_contains($c, 'sertifikat')) return 'certificate';
        }

        // 3. Attendance & Progress
        if (str_contains($g, 'attendance')) {
            if ($c === 'progress') return 'progress';
            if (str_contains($c, 'assessment') || $c === 'score' || str_contains($c, 'nilai')) return 'assessment_score';
            if ($c === 'certificate' || str_contains($c, 'sertifikat')) return 'certificate';
            if ($c === 'month' || str_contains($c, 'bulan')) return null;
            if (str_contains($c, 'yes') || str_contains($c, 'attendance') || $c === 'status') return 'attendance';
            if (str_contains($c, 'reason') || str_contains($c, 'alasan')) return 'attendance_reason';
        }

        // 4. Obligation / Bond
        if (str_contains($g, 'obligation') || str_contains($g, 'bond')) {
            if (str_contains($c, 'status')) return 'bond_status';
            if (str_contains($c, 'date') || str_contains($c, 'end')) return 'bond_end_date';
            if (str_contains($c, 'amount') || str_contains($c, 'biaya') || str_contains($c, 'nilai')) return 'bond_amount';
        }

        // 5. L1
        if (str_contains($g, 'l1') || $g === 'l1') {
            if (str_contains($c, 'program')) return 'l1_program';
        }

        // 6. L2 (Post Test)
        if (str_contains($g, 'l2') || str_contains($g, 'post test')) {
            if (str_contains($c, 'numerical') || str_contains($c, 'numeric')) return 'l2_numerical';
            if (str_contains($c, 'verbal')) return 'l2_verbal';
        }

        // 7. L3 (Project)
        if (str_contains($g, 'l3') || str_contains($g, 'project')) {
            if (str_contains($c, 'numerical') || str_contains($c, 'numeric')) return 'l3_numerical';
            if (str_contains($c, 'verbal')) return 'l3_verbal';
            if ($c === 'pic' || str_contains($c, 'pic')) return 'pic';
        }

        // Fallback to single header matching
        return $this->matchSingleHeader($child);
    }

    /**
     * Match single-row header (flattened names).
     */
    protected function matchSingleHeader(string $header): ?string
    {
        $h = str_replace(['_', '-'], ' ', strtolower(trim($header)));

        if ($h === '' || $h === 'no' || $h === 'month') {
            return null;
        }

        // Explicit L2 vs L3 disambiguation in single header
        if (str_contains($h, 'l2') && str_contains($h, 'numeric')) return 'l2_numerical';
        if (str_contains($h, 'l2') && str_contains($h, 'verbal')) return 'l2_verbal';
        if (str_contains($h, 'l3') && str_contains($h, 'numeric')) return 'l3_numerical';
        if (str_contains($h, 'l3') && str_contains($h, 'verbal')) return 'l3_verbal';
        if ($h === 'pic' || (str_contains($h, 'l3') && str_contains($h, 'pic'))) return 'pic';

        // L1
        if (str_contains($h, 'l1') || $h === 'program') return 'l1_program';

        // Bond
        if (str_contains($h, 'bond') && str_contains($h, 'status')) return 'bond_status';
        if (str_contains($h, 'bond') && (str_contains($h, 'date') || str_contains($h, 'end'))) return 'bond_end_date';
        if (str_contains($h, 'bond') && (str_contains($h, 'amount') || str_contains($h, 'biaya'))) return 'bond_amount';

        // Attendance
        if (str_contains($h, 'attendance') && (str_contains($h, 'yes') || str_contains($h, 'status') || $h === 'attendance')) return 'attendance';
        if (str_contains($h, 'attendance') && str_contains($h, 'reason') || $h === 'reason') return 'attendance_reason';

        // Job Information
        if ($h === 'id' || $h === 'employee id' || $h === 'nip') return 'employee_id';
        if ($h === 'name' || $h === 'employee name' || $h === 'nama') return 'name';
        if ($h === 'grade') return 'grade';
        if ($h === 'job position' || $h === 'position' || $h === 'jabatan') return 'job_position';
        if ($h === 'team' || $h === 'unit') return 'team';
        if ($h === 'department' || $h === 'dept') return 'department';
        if ($h === 'company') return 'company';

        // Training Information
        if (str_contains($h, 'sub course') || $h === 'sub course name') return 'sub_course_name';
        if ($h === 'course name' || $h === 'course' || $h === 'nama course' || $h === 'training name') return 'course_name';
        if ($h === 'type' || $h === 'training type') return 'type';
        if ($h === 'category') return 'category';
        if ($h === 'level') return 'level';
        if ($h === 'batch') return 'batch';
        if ($h === 'main classification' || $h === 'classification') return 'main_classification';
        if ($h === 'sub classification') return 'sub_classification';
        if (str_contains($h, 'corporate kpi') && !str_contains($h, 'sub')) return 'counted_corporate_kpi';
        if (str_contains($h, 'sub') && str_contains($h, 'corporate kpi')) return 'sub_corporate_kpi';
        if ($h === 'delivery method' || (str_contains($h, 'delivery') && !str_contains($h, 'sub'))) return 'delivery_method';
        if (str_contains($h, 'sub') && str_contains($h, 'delivery')) return 'sub_delivery_method';
        if ($h === 'provider' && !str_contains($h, 'name') && !str_contains($h, 'sub')) return 'provider';
        if (str_contains($h, 'sub') && str_contains($h, 'provider')) return 'sub_provider';
        if (str_contains($h, 'provider name')) return 'provider_name';
        if ($h === 'duration' || str_contains($h, 'durasi')) return 'duration_seconds';
        if (str_contains($h, 'learning') && str_contains($h, 'hour')) return 'learning_hours';
        if ($h === 'progress') return 'progress';
        if ($h === 'start date' || $h === 'start') return 'start_date';
        if ($h === 'finish date' || $h === 'finish' || $h === 'end date') return 'finish_date';
        if ($h === 'venue' || $h === 'lokasi') return 'venue';
        if ($h === 'instructor' || $h === 'trainer') return 'instructor';
        if (str_contains($h, 'assessment') || $h === 'score') return 'assessment_score';
        if ($h === 'certificate') return 'certificate';

        return null;
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
     * Validate row data against business integrity rules.
     */
    public function validateRowData(array $row): array
    {
        $errors = [];
        $warnings = [];

        // Required checks
        if (!$row['course_name']) {
            $errors[] = 'Course Name is required.';
        }
        if (!$row['employee_id']) {
            $errors[] = 'Employee ID (NIP) is required.';
        }
        if (!$row['name']) {
            $errors[] = 'Participant Name is required.';
        }

        // Progress bounds
        if ($row['progress'] !== null) {
            if ($row['progress'] < 0 || $row['progress'] > 100) {
                $errors[] = 'Progress must be between 0% and 100%. Found: ' . $row['progress'] . '%.';
            }
        }

        // Date sanity
        if ($row['start_date'] && $row['finish_date']) {
            if ($row['finish_date'] < $row['start_date']) {
                $errors[] = "Finish date ({$row['finish_date']}) cannot be earlier than start date ({$row['start_date']}).";
            }
        }

        // Non-fatal warnings
        if ($row['start_date'] === null && $row['finish_date'] === null) {
            $warnings[] = 'Training dates are blank.';
        }
        if ($row['learning_hours'] === null) {
            $warnings[] = 'Learning hours are blank.';
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

    public function normalizeString($value): ?string
    {
        if ($value === null) return null;
        $str = trim((string) $value);
        return $str === '' ? null : $str;
    }

    public function normalizeBoolean($value): ?bool
    {
        if ($value === null) return null;
        $str = strtolower(trim((string) $value));
        if ($str === '') return null;

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
        if ($value === null) return null;
        $str = trim((string) $value);
        if ($str === '') return null;

        // Strip % sign if present
        $str = str_replace(['%', ' '], '', $str);

        if (!is_numeric($str)) return null;

        $num = (float) $str;
        // If float fraction <= 1.0 (e.g. 0.75 from raw excel percentage cell), but check if source meant 75%
        // In Excel, 100% is formatted as 1 or 1.0; 50% is 0.5.
        // If the number is between 0 and 1, and contains decimal, convert to percentage
        if ($num > 0 && $num <= 1.0 && str_contains($str, '.')) {
            $num = $num * 100.0;
        }

        return round($num, 4);
    }

    public function normalizeDuration($value): ?int
    {
        if ($value === null) return null;
        $str = trim((string) $value);
        if ($str === '') return null;

        // If formatted as HH:MM:SS or HH:MM
        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $str, $matches)) {
            $h = (int) $matches[1];
            $m = (int) $matches[2];
            $s = isset($matches[3]) ? (int) $matches[3] : 0;
            return ($h * 3600) + ($m * 60) + $s;
        }

        // If integer or numeric seconds
        if (is_numeric($str)) {
            $num = (float) $str;
            // If already seconds (> 100) or hours (< 100)
            // If it's pure hours, e.g. 8 hours -> 8 * 3600 = 28800
            // In OWL LMS, if a number is <= 24, it's typically hours
            if ($num > 0 && $num <= 24) {
                return (int) round($num * 3600);
            }
            return (int) round($num);
        }

        return null;
    }

    public function normalizeLearningHours($value): ?float
    {
        if ($value === null) return null;
        $str = trim((string) $value);
        if ($str === '') return null;

        // Remove any commas or text
        $str = str_replace(['hours', 'hour', 'jam', ' '], '', strtolower($str));
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

        $str = trim((string) $value);
        if ($str === '') return null;

        // Excel numeric serial date
        if (is_numeric($str) && (float) $str > 1000) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $str)->format('Y-m-d');
            } catch (\Throwable $e) {
                // fallback
            }
        }

        try {
            return Carbon::parse($str)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function normalizeMoney($value): ?float
    {
        if ($value === null) return null;
        $str = trim((string) $value);
        if ($str === '') return null;

        // Strip Rp, IDR, $, USD, and whitespace
        $str = preg_replace('/^(?:rp|idr|usd|\$)\.?\s*/i', '', $str);
        $str = trim($str);

        if (str_contains($str, '.') && str_contains($str, ',')) {
            if (strrpos($str, '.') > strrpos($str, ',')) {
                // US format: 1,500.50 -> comma is thousand, dot is decimal
                $str = str_replace(',', '', $str);
            } else {
                // Indonesian format: 1.500,50 -> dot is thousand, comma is decimal
                $str = str_replace('.', '', $str);
                $str = str_replace(',', '.', $str);
            }
        } elseif (str_contains($str, '.') && !str_contains($str, ',')) {
            // E.g. "1.000.000" or "500.000" -> remove dots if thousands
            $parts = explode('.', $str);
            if (count($parts) > 1 && strlen(end($parts)) === 3) {
                $str = str_replace('.', '', $str);
            }
        } elseif (str_contains($str, ',') && !str_contains($str, '.')) {
            // E.g. "1,000,000" -> remove comma
            $str = str_replace(',', '', $str);
        }

        if (!is_numeric($str)) return null;

        return round((float) $str, 2);
    }

    public function normalizeAssessmentScore($value): ?float
    {
        if ($value === null) return null;
        $str = trim((string) $value);
        if ($str === '') return null;
        $str = str_replace(',', '.', $str);

        if (!is_numeric($str)) return null;

        return round((float) $str, 2);
    }

    public function normalizeDecimal($value, int $decimals = 4): ?float
    {
        if ($value === null) return null;
        $str = trim((string) $value);
        if ($str === '') return null;
        $str = str_replace(',', '.', $str);

        if (!is_numeric($str)) return null;

        return round((float) $str, $decimals);
    }
}
