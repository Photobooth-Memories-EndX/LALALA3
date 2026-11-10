<?php

namespace App\Services\OfflineTraining;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class EmployeeIdentitySyncService
{
    /**
     * Synchronize 5 identity attributes from a User to their offline_training_users records.
     *
     * Five columns synced:
     * - name         <- users.name
     * - grade        <- users.jobgrade
     * - job_position <- users.jobposition
     * - team         <- users.team
     * - department   <- users.department
     *
     * @param User $user
     * @return int Number of affected offline training participant records
     */
    public function syncUserToOfflineRecords(User $user): int
    {
        try {
            $empId = trim((string) ($user->id_emp ?? ''));

            $query = DB::table('offline_training_users')
                ->where(function ($q) use ($user, $empId) {
                    $q->where('user_id', $user->id);
                    if ($empId !== '') {
                        $q->orWhere('employee_id', $empId);
                    }
                });

            $updatePayload = [
                'name'         => $user->name,
                'grade'        => $user->jobgrade,
                'job_position' => $user->jobposition,
                'team'         => $user->team,
                'department'   => $user->department,
                'user_id'      => $user->id, // ensure link is intact
                'updated_at'   => now(),
            ];

            $affected = $query->update($updatePayload);

            return $affected;
        } catch (Throwable $e) {
            Log::error('EmployeeIdentitySyncService failed for User ID ' . $user->id . ': ' . $e->getMessage(), [
                'user_id'   => $user->id,
                'id_emp'    => $user->id_emp ?? null,
                'exception' => $e,
            ]);

            return 0;
        }
    }

    /**
     * Batch synchronization for multiple users.
     *
     * @param iterable<User> $users
     * @return array{total_users: int, total_records_synced: int}
     */
    public function syncMultipleUsers(iterable $users): array
    {
        $totalUsers = 0;
        $totalRecords = 0;

        foreach ($users as $user) {
            if ($user instanceof User) {
                $totalUsers++;
                $totalRecords += $this->syncUserToOfflineRecords($user);
            }
        }

        return [
            'total_users'          => $totalUsers,
            'total_records_synced' => $totalRecords,
        ];
    }
}
