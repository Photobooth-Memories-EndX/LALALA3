<?php

namespace App\Services\ImportLog;

use App\Models\ImportBatch;
use App\Models\ImportBatchRow;
use Illuminate\Support\Str;
use Throwable;

class ImportLogService
{
    protected ImportFileStorageService $fileStorage;

    public function __construct(ImportFileStorageService $fileStorage)
    {
        $this->fileStorage = $fileStorage;
    }

    /**
     * Create an initial ImportBatch record with status IMPORTING and store the source Excel file.
     *
     * @param string $sourceType 'PTKP' or 'NON_PTKP'
     * @param string $tempFilePath Path to temporary uploaded file
     * @param string $originalFilename Original uploaded filename
     * @param int $userId ID of importing user
     * @return ImportBatch
     */
    public function createBatch(
        string $sourceType,
        string $tempFilePath,
        string $originalFilename,
        int $userId
    ): ImportBatch {
        $uuid = (string) Str::uuid();

        // 1. Store file in private local storage
        $stored = $this->fileStorage->storeImportFile(
            file: $tempFilePath,
            sourceType: $sourceType,
            batchUuid: $uuid,
            originalFilename: $originalFilename
        );

        // 2. Create batch record in database
        try {
            return ImportBatch::create([
                'batch_uuid'        => $uuid,
                'source_type'       => $sourceType,
                'original_filename' => $stored['original_filename'],
                'file_path'         => $stored['file_path'],
                'file_disk'         => $stored['file_disk'],
                'file_hash'         => $stored['file_hash'],
                'file_size'         => $stored['file_size'],
                'imported_by'       => $userId,
                'status'            => ImportBatch::STATUS_IMPORTING,
                'started_at'        => now(),
            ]);
        } catch (Throwable $e) {
            // Cleanup orphaned file if batch creation fails
            $this->fileStorage->deleteStoredFile($stored['file_path'], $stored['file_disk']);
            throw $e;
        }
    }

    /**
     * Insert row log records in chunks.
     *
     * @param ImportBatch $batch
     * @param array<int, array<string, mixed>> $rows
     */
    public function insertBatchRows(ImportBatch $batch, array $rows): void
    {
        if (empty($rows)) {
            return;
        }

        $now = now()->toDateTimeString();
        $records = [];

        foreach ($rows as $row) {
            $records[] = [
                'import_batch_id'          => $batch->id,
                'row_number'               => (int) ($row['row_number'] ?? 0),
                'sheet_name'               => isset($row['sheet_name']) ? (string) $row['sheet_name'] : null,
                'employee_id'              => isset($row['employee_id']) ? (string) $row['employee_id'] : null,
                'employee_name'            => isset($row['employee_name']) ? (string) $row['employee_name'] : null,
                'course_name'              => isset($row['course_name']) ? (string) $row['course_name'] : null,
                'validation_status'        => (string) ($row['validation_status'] ?? ImportBatchRow::STATUS_VALID),
                'action'                   => (string) ($row['action'] ?? ImportBatchRow::ACTION_IMPORTED),
                'offline_training_id'      => isset($row['offline_training_id']) ? (int) $row['offline_training_id'] : null,
                'offline_training_user_id' => isset($row['offline_training_user_id']) ? (int) $row['offline_training_user_id'] : null,
                'error_code'               => isset($row['error_code']) ? (string) $row['error_code'] : null,
                'messages'                 => isset($row['messages']) ? json_encode($row['messages'], JSON_UNESCAPED_UNICODE) : null,
                'row_data'                 => isset($row['row_data']) ? json_encode($row['row_data'], JSON_UNESCAPED_UNICODE) : null,
                'created_at'               => $now,
                'updated_at'               => $now,
            ];
        }

        foreach (array_chunk($records, 100) as $chunk) {
            ImportBatchRow::insert($chunk);
        }
    }

    /**
     * Complete the import batch with final statistics and status.
     *
     * @param ImportBatch $batch
     * @param array{
     *     total_rows?: int,
     *     valid_rows?: int,
     *     warning_rows?: int,
     *     duplicate_rows?: int,
     *     invalid_rows?: int,
     *     imported_rows?: int,
     *     skipped_rows?: int,
     *     failed_rows?: int,
     *     trainings_created?: int,
     *     trainings_reused?: int,
     *     metadata?: array
     * } $stats
     */
    public function completeBatch(ImportBatch $batch, array $stats): void
    {
        $warningRows = (int) ($stats['warning_rows'] ?? 0);
        $finalStatus = $warningRows > 0
            ? ImportBatch::STATUS_COMPLETED_WITH_WARNINGS
            : ImportBatch::STATUS_COMPLETED;

        $batch->update([
            'status'            => $finalStatus,
            'finished_at'       => now(),
            'total_rows'        => (int) ($stats['total_rows'] ?? 0),
            'valid_rows'        => (int) ($stats['valid_rows'] ?? 0),
            'warning_rows'      => $warningRows,
            'duplicate_rows'    => (int) ($stats['duplicate_rows'] ?? 0),
            'invalid_rows'      => (int) ($stats['invalid_rows'] ?? 0),
            'imported_rows'     => (int) ($stats['imported_rows'] ?? 0),
            'skipped_rows'      => (int) ($stats['skipped_rows'] ?? 0),
            'failed_rows'       => (int) ($stats['failed_rows'] ?? 0),
            'trainings_created' => (int) ($stats['trainings_created'] ?? 0),
            'trainings_reused'  => (int) ($stats['trainings_reused'] ?? 0),
            'metadata'          => $stats['metadata'] ?? $batch->metadata,
        ]);
    }

    /**
     * Mark the import batch as failed after transaction rollback.
     *
     * @param ImportBatch $batch
     * @param Throwable $exception
     * @param int $totalRows
     */
    public function failBatch(ImportBatch $batch, Throwable $exception, int $totalRows = 0): void
    {
        $safeMessage = $this->sanitizeErrorSummary($exception->getMessage());

        $batch->update([
            'status'        => ImportBatch::STATUS_FAILED,
            'finished_at'   => now(),
            'total_rows'    => $totalRows > 0 ? $totalRows : $batch->total_rows,
            'imported_rows' => 0,
            'failed_rows'   => $totalRows > 0 ? $totalRows : $batch->total_rows,
            'error_summary' => $safeMessage,
        ]);
    }

    /**
     * Sanitize error messages to avoid leaking sensitive credentials or stack traces.
     */
    public function sanitizeErrorSummary(string $message): string
    {
        // Strip SQL queries with potentially sensitive params
        $sanitized = preg_replace('/\(SQL:.*?\)/is', '', $message) ?? $message;

        // Strip connection info or passwords if any
        $sanitized = preg_replace('/password=\S+/i', 'password=***', $sanitized) ?? $sanitized;

        // Limit length
        $sanitized = Str::limit(trim($sanitized), 500);

        return $sanitized !== '' ? $sanitized : 'An unexpected database error occurred during import execution.';
    }

    public function getFileStorage(): ImportFileStorageService
    {
        return $this->fileStorage;
    }
}
