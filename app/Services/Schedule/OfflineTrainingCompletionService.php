<?php

namespace App\Services\Schedule;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingRegister;
use App\Models\OfflineTrainingUser;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class OfflineTrainingCompletionService
{
    public function __construct(
        private readonly OfflineTrainingUserSyncService $syncService
    ) {
    }

    /**
     * Mark a registration as Completed and idempotently synchronize
     * the participant record into offline_training_users.
     *
     * IMPORTANT: This method DOES NOT alter the Schedule training header.
     * Query isolation (forSchedule vs forRecord) remains completely intact.
     *
     * State transition:
     * - Approved -> Completed
     * - Updates the SAME offline_training_users row from Ongoing (progress = 0)
     *   to Completed (progress = 100, attendance = true, accredited learning_hours).
     *
     * @throws InvalidArgumentException|RuntimeException
     */
    public function complete(int $registrationId): OfflineTrainingRegister
    {
        return DB::transaction(function () use ($registrationId) {
            /** @var OfflineTrainingRegister|null $registration */
            $registration = OfflineTrainingRegister::with(['training', 'user'])
                ->where('id', $registrationId)
                ->lockForUpdate()
                ->first();

            if (!$registration) {
                throw new InvalidArgumentException('Registration record not found.');
            }

            $training = $registration->training;
            if (!$training) {
                throw new InvalidArgumentException('Associated training schedule not found.');
            }

            // Ensure training is a Schedule training
            $isSchedule = OfflineTraining::forSchedule()->where('id', $training->id)->exists();
            if (!$isSchedule) {
                throw new RuntimeException('Only Training Schedule participants can be completed through this workflow.');
            }

            // Must be Approved or already Completed (for idempotency)
            if (!in_array($registration->status, ['Approved', 'Completed'], true)) {
                throw new RuntimeException("Cannot complete registration with status \"{$registration->status}\". Registration must be Approved first.");
            }

            $user = $registration->user;
            if (!$user) {
                throw new RuntimeException('Participant user record not found.');
            }

            // 1. Idempotently update the SAME offline_training_users row to Completed
            $this->syncService->syncCompleted($registration);

            // 2. Update registration record
            $registration->update([
                'status'       => 'Completed',
                'completed_at' => $registration->completed_at ?? now(),
            ]);

            return $registration->fresh(['training', 'user']);
        });
    }
}
