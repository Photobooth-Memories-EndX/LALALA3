<?php

namespace App\Services\ImportLog;

use App\Models\ImportBatch;
use App\Models\ImportBatchRow;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ImportHistoryQueryService
{
    /**
     * Compute aggregated statistics for the summary cards.
     *
     * @return array{
     *     total_batches: int,
     *     completed_batches: int,
     *     warning_batches: int,
     *     failed_batches: int,
     *     importing_batches: int,
     *     total_imported_rows: int,
     *     total_skipped_rows: int
     * }
     */
    public function getSummaryCards(): array
    {
        $raw = ImportBatch::query()
            ->selectRaw('
                COUNT(*) as total_batches,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed_batches,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as warning_batches,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as failed_batches,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as importing_batches,
                COALESCE(SUM(imported_rows), 0) as total_imported_rows,
                COALESCE(SUM(skipped_rows), 0) as total_skipped_rows
            ', [
                ImportBatch::STATUS_COMPLETED,
                ImportBatch::STATUS_COMPLETED_WITH_WARNINGS,
                ImportBatch::STATUS_FAILED,
                ImportBatch::STATUS_IMPORTING,
            ])
            ->first();

        return [
            'total_batches'       => (int) ($raw->total_batches ?? 0),
            'completed_batches'   => (int) ($raw->completed_batches ?? 0),
            'warning_batches'     => (int) ($raw->warning_batches ?? 0),
            'failed_batches'      => (int) ($raw->failed_batches ?? 0),
            'importing_batches'   => (int) ($raw->importing_batches ?? 0),
            'total_imported_rows' => (int) ($raw->total_imported_rows ?? 0),
            'total_skipped_rows'  => (int) ($raw->total_skipped_rows ?? 0),
        ];
    }

    /**
     * Build the query for import batches with eager loading and filter criteria.
     *
     * @param array<string, mixed> $filters
     * @return Builder
     */
    public function getBatchesQuery(array $filters = []): Builder
    {
        $query = ImportBatch::query()
            ->with(['importedBy:id,name,email,id_emp'])
            ->select('import_batches.*');

        // Filter: source_type (PTKP or NON_PTKP)
        if (!empty($filters['source_type']) && in_array(strtoupper($filters['source_type']), [ImportBatch::SOURCE_PTKP, ImportBatch::SOURCE_NON_PTKP], true)) {
            $query->where('source_type', strtoupper($filters['source_type']));
        }

        // Filter: status
        $validStatuses = [
            ImportBatch::STATUS_COMPLETED,
            ImportBatch::STATUS_COMPLETED_WITH_WARNINGS,
            ImportBatch::STATUS_FAILED,
            ImportBatch::STATUS_IMPORTING,
        ];
        if (!empty($filters['status']) && in_array(strtoupper($filters['status']), $validStatuses, true)) {
            $query->where('status', strtoupper($filters['status']));
        }

        // Filter: imported_by user ID
        if (!empty($filters['user_id']) && is_numeric($filters['user_id'])) {
            $query->where('imported_by', (int) $filters['user_id']);
        }

        // Filter: Date Range (started_at)
        if (!empty($filters['date_start'])) {
            try {
                $startDate = Carbon::createFromFormat('Y-m-d', trim($filters['date_start']))->startOfDay();
                $query->where('started_at', '>=', $startDate);
            } catch (\Throwable $e) {
                // Ignore invalid date format
            }
        }

        if (!empty($filters['date_end'])) {
            try {
                $endDate = Carbon::createFromFormat('Y-m-d', trim($filters['date_end']))->endOfDay();
                $query->where('started_at', '<=', $endDate);
            } catch (\Throwable $e) {
                // Ignore invalid date format
            }
        }

        // Search: batch_uuid or original_filename
        if (!empty($filters['search'])) {
            $term = '%' . trim($filters['search']) . '%';
            $query->where(function (Builder $sub) use ($term) {
                $sub->where('batch_uuid', 'LIKE', $term)
                    ->orWhere('original_filename', 'LIKE', $term);
            });
        }

        if (empty($filters['no_default_order']) && !request()->filled('order')) {
            $query->orderBy('started_at', 'desc');
        }

        return $query;
    }

    /**
     * Find a batch by UUID, eager loading user.
     */
    public function getBatchByUuid(string $uuid): ?ImportBatch
    {
        if (!preg_match('/^[a-f0-9\-]{36}$/i', $uuid)) {
            return null;
        }

        return ImportBatch::with(['importedBy:id,name,email,id_emp'])
            ->where('batch_uuid', $uuid)
            ->first();
    }

    /**
     * Build the query for batch rows with filters.
     *
     * @param int $batchId
     * @param array<string, mixed> $filters
     * @return Builder
     */
    public function getBatchRowsQuery(int $batchId, array $filters = []): Builder
    {
        $query = ImportBatchRow::query()
            ->where('import_batch_id', $batchId);

        // Filter: action
        $validActions = [
            ImportBatchRow::ACTION_IMPORTED,
            ImportBatchRow::ACTION_SKIPPED,
            ImportBatchRow::ACTION_FAILED,
        ];
        if (!empty($filters['action']) && in_array(strtoupper($filters['action']), $validActions, true)) {
            $query->where('action', strtoupper($filters['action']));
        }

        // Filter: validation_status
        $validStatuses = [
            ImportBatchRow::STATUS_VALID,
            ImportBatchRow::STATUS_WARNING,
            ImportBatchRow::STATUS_DUPLICATE,
            ImportBatchRow::STATUS_INVALID,
            ImportBatchRow::STATUS_INVALID_COMPANY,
        ];
        if (!empty($filters['validation_status']) && in_array(strtoupper($filters['validation_status']), $validStatuses, true)) {
            $query->where('validation_status', strtoupper($filters['validation_status']));
        }

        // Search in employee_id, employee_name, course_name
        if (!empty($filters['search'])) {
            $term = '%' . trim($filters['search']) . '%';
            $query->where(function (Builder $sub) use ($term) {
                $sub->where('employee_id', 'LIKE', $term)
                    ->orWhere('employee_name', 'LIKE', $term)
                    ->orWhere('course_name', 'LIKE', $term);
            });
        }

        return $query->orderBy('row_number', 'asc');
    }

    /**
     * Get distinct users who executed import batches for filter dropdown.
     *
     * @return Collection
     */
    public function getDistinctUsers(): Collection
    {
        return User::query()
            ->whereIn('id', ImportBatch::query()->select('imported_by')->distinct())
            ->select('id', 'name', 'email', 'id_emp')
            ->orderBy('name', 'asc')
            ->get();
    }
}
