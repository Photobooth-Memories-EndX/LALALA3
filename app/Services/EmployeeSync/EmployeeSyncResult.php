<?php

namespace App\Services\EmployeeSync;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class EmployeeSyncResult
{
    public bool $isDryRun = false;
    public ?CarbonInterface $startedAt = null;
    public ?CarbonInterface $finishedAt = null;
    public int $totalApi = 0;
    public int $totalOwl = 0;
    public int $createdCount = 0;
    public int $updatedCount = 0;
    public int $updatedActiveCount = 0;
    public int $updatedSoftDeletedCount = 0;
    public int $unchangedCount = 0;
    public int $skippedCount = 0;
    public int $conflictCount = 0;
    public int $failedCount = 0;
    public string $status = 'success'; // success, partial, failed
    public ?string $errorMessage = null;

    /**
     * @var array<int, array{id_emp: string, reason: string, data?: array}>
     */
    public array $conflicts = [];

    /**
     * @var array<int, array{id_emp: string, name: string, is_soft_deleted: bool, changes: array<string, array{from: mixed, to: mixed}>}>
     */
    public array $updatedDetails = [];

    /**
     * @var array<int, array{id_emp: string, name: string, email: string}>
     */
    public array $createdDetails = [];

    /**
     * @var array<int, array{id_emp: string, reason: string}>
     */
    public array $skippedDetails = [];

    public function __construct(bool $isDryRun = false)
    {
        $this->isDryRun = $isDryRun;
        $this->startedAt = Carbon::now();
    }

    public function finish(string $status = 'success', ?string $errorMessage = null): self
    {
        $this->finishedAt = Carbon::now();
        $this->status = $status;
        $this->errorMessage = $errorMessage;

        return $this;
    }

    public function durationSeconds(): float
    {
        if (!$this->startedAt) {
            return 0.0;
        }

        $end = $this->finishedAt ?: Carbon::now();

        return round($this->startedAt->diffInMilliseconds($end) / 1000, 2);
    }

    public function recordConflict(string $idEmp, string $reason, array $data = []): void
    {
        $this->conflictCount++;
        // Strip sensitive fields before storing
        unset($data['password'], $data['ssohash'], $data['newhash']);
        $this->conflicts[] = [
            'id_emp' => $idEmp,
            'reason' => $reason,
            'data' => $data,
        ];
    }

    public function recordSkip(string $idEmp, string $reason): void
    {
        $this->skippedCount++;
        $this->skippedDetails[] = [
            'id_emp' => $idEmp,
            'reason' => $reason,
        ];
    }

    public function recordCreated(string $idEmp, string $name, string $email): void
    {
        $this->createdCount++;
        $this->createdDetails[] = [
            'id_emp' => $idEmp,
            'name' => $name,
            'email' => $email,
        ];
    }

    public function recordUpdated(string $idEmp, string $name, array $changes, bool $isSoftDeleted = false): void
    {
        $this->updatedCount++;
        if ($isSoftDeleted) {
            $this->updatedSoftDeletedCount++;
        } else {
            $this->updatedActiveCount++;
        }

        // Sanitize changes to never leak authentication credentials or hashes
        $sanitizedChanges = [];
        foreach ($changes as $field => $change) {
            if (in_array($field, ['password', 'ssohash', 'newhash'], true)) {
                $sanitizedChanges[$field] = ['from' => '[REDACTED]', 'to' => '[REDACTED]'];
            } else {
                $sanitizedChanges[$field] = $change;
            }
        }

        $this->updatedDetails[] = [
            'id_emp' => $idEmp,
            'name' => $name,
            'is_soft_deleted' => $isSoftDeleted,
            'changes' => $sanitizedChanges,
        ];
    }

    public function toArray(): array
    {
        return [
            'is_dry_run' => $this->isDryRun,
            'status' => $this->status,
            'duration_seconds' => $this->durationSeconds(),
            'started_at' => $this->startedAt?->toIso8601String(),
            'finished_at' => $this->finishedAt?->toIso8601String(),
            'total_api' => $this->totalApi,
            'total_owl' => $this->totalOwl,
            'created' => $this->createdCount,
            'updated' => $this->updatedCount,
            'updated_active' => $this->updatedActiveCount,
            'updated_soft_deleted' => $this->updatedSoftDeletedCount,
            'unchanged' => $this->unchangedCount,
            'skipped' => $this->skippedCount,
            'conflict' => $this->conflictCount,
            'failed' => $this->failedCount,
            'error_message' => $this->errorMessage,
            'conflicts' => $this->conflicts,
            'updated_sample' => array_slice($this->updatedDetails, 0, 50),
            'created_sample' => array_slice($this->createdDetails, 0, 50),
            'skipped_sample' => array_slice($this->skippedDetails, 0, 50),
        ];
    }
}
