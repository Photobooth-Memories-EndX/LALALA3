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
    /**
     * Mark a registration as Completed and idempotently synchronize
     * the participant record into offline_training_users.
     *
     * IMPORTANT: This method DOES NOT alter the Schedule training header.
     * Query isolation (forSchedule vs forRecord) remains completely intact.
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

            // 1. Calculate learning hours using training header duration
            $learningHours = $training->learning_hours ?? 0;
            if ((float) $learningHours <= 0 && filled($training->start_time) && filled($training->end_time)) {
                $start = strtotime($training->start_time);
                $end = strtotime($training->end_time);
                if ($end > $start) {
                    $learningHours = round(($end - $start) / 3600, 2);
                }
            }

            // 2. Idempotent upsert into offline_training_users
            OfflineTrainingUser::updateOrCreate(
                [
                    'offline_training_id' => $training->id,
                    'user_id'             => $user->id,
                ],
                [
                    'employee_id'         => $user->id_emp ?? '',
                    'name'                => $user->name,
                    'grade'               => $user->grade ?? null,
                    'job_position'        => $user->job_position ?? null,
                    'team'                => $user->team ?? null,
                    'department'          => $user->department ?? null,
                    'company'             => $user->company_name ?? 'PT Krakatau Posco',
                    'attendance'          => true,
                    'progress'            => 100,
                    'learning_hours'      => $learningHours,
                ]
            );

            // 3. Update registration record
            $registration->update([
                'status'       => 'Completed',
                'completed_at' => $registration->completed_at ?? now(),
            ]);

            return $registration;
        });
    }
}
