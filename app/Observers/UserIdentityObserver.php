<?php

namespace App\Observers;

use App\Models\User;
use App\Services\OfflineTraining\EmployeeIdentitySyncService;

class UserIdentityObserver
{
    protected EmployeeIdentitySyncService $syncService;

    public function __construct(EmployeeIdentitySyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // Only trigger sync if at least one of the 5 identity fields or id_emp was modified
        if ($user->wasChanged(['name', 'jobgrade', 'jobposition', 'team', 'department', 'id_emp'])) {
            $this->syncService->syncUserToOfflineRecords($user);
        }
    }
}
