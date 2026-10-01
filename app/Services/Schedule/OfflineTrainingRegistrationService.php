<?php

namespace App\Services\Schedule;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingRegister;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class OfflineTrainingRegistrationService
{
    /**
     * Register an authenticated user for a Training Schedule.
     *
     * @throws InvalidArgumentException|RuntimeException
     */
    public function register(int $trainingId, User $user): OfflineTrainingRegister
    {
        return DB::transaction(function () use ($trainingId, $user) {
            // 1. Validate training belongs to Schedule and lock row
            /** @var OfflineTraining|null $training */
            $training = OfflineTraining::forSchedule()
                ->where('id', $trainingId)
                ->lockForUpdate()
                ->first();

            if (!$training) {
                throw new InvalidArgumentException('Training schedule not found or not eligible for registration.');
            }

            // 2. Training status must permit registration
            if ($training->status !== 'Open Registration') {
                throw new RuntimeException("Registration is closed for \"{$training->title}\" (Status: {$training->status}).");
            }

            // 3. Prevent duplicate registration
            $existing = OfflineTrainingRegister::where('offline_training_id', $training->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existing) {
                if ($existing->status === 'Pending') {
                    throw new RuntimeException('You already have a pending registration for this training.');
                }
                if ($existing->status === 'Approved') {
                    throw new RuntimeException('You are already approved and registered for this training.');
                }
                if ($existing->status === 'Completed') {
                    throw new RuntimeException('You have already completed this training.');
                }
                // If previously rejected, allow re-registration by updating existing row or throw?
                // Per unique constraint on [offline_training_id, user_id], if rejected, we can re-activate to Pending:
                if ($existing->status === 'Rejected') {
                    // Check quota before re-activating
                    $occupied = OfflineTrainingRegister::where('offline_training_id', $training->id)
                        ->consumingSeats()
                        ->count();

                    if ($training->quota > 0 && $occupied >= $training->quota) {
                        throw new RuntimeException('Registration quota for this training is currently full.');
                    }

                    $existing->update([
                        'status'           => 'Pending',
                        'registered_at'    => now(),
                        'approved_at'      => null,
                        'approved_by'      => null,
                        'rejected_at'      => null,
                        'rejection_reason' => null,
                        'completed_at'     => null,
                    ]);

                    return $existing;
                }

                throw new RuntimeException('You are already registered for this training.');
            }

            // 4. Check quota
            $occupied = OfflineTrainingRegister::where('offline_training_id', $training->id)
                ->consumingSeats()
                ->count();

            if ($training->quota > 0 && $occupied >= $training->quota) {
                throw new RuntimeException('Registration quota for this training is currently full.');
            }

            // 5. Create new registration (status: Pending)
            return OfflineTrainingRegister::create([
                'offline_training_id' => $training->id,
                'user_id'             => $user->id,
                'status'              => 'Pending',
                'registered_at'       => now(),
            ]);
        });
    }

    /**
     * Cancel a pending registration by the user.
     */
    public function cancel(int $registrationId, User $user): bool
    {
        return DB::transaction(function () use ($registrationId, $user) {
            $registration = OfflineTrainingRegister::where('id', $registrationId)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (!$registration) {
                throw new InvalidArgumentException('Registration record not found.');
            }

            if ($registration->status !== 'Pending') {
                throw new RuntimeException('Only pending registrations can be cancelled.');
            }

            return (bool) $registration->delete();
        });
    }

    /**
     * Approve a pending registration (Manage action).
     */
    public function approve(OfflineTrainingRegister $registration, int $approvedById): OfflineTrainingRegister
    {
        return DB::transaction(function () use ($registration, $approvedById) {
            $locked = OfflineTrainingRegister::where('id', $registration->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === 'Approved') {
                return $locked;
            }

            $locked->update([
                'status'           => 'Approved',
                'approved_at'      => now(),
                'approved_by'      => $approvedById,
                'rejected_at'      => null,
                'rejection_reason' => null,
            ]);

            return $locked;
        });
    }

    /**
     * Reject a registration (Manage action).
     */
    public function reject(OfflineTrainingRegister $registration, ?string $reason = null): OfflineTrainingRegister
    {
        return DB::transaction(function () use ($registration, $reason) {
            $locked = OfflineTrainingRegister::where('id', $registration->id)->lockForUpdate()->firstOrFail();

            $locked->update([
                'status'           => 'Rejected',
                'rejected_at'      => now(),
                'rejection_reason' => $reason,
                'approved_at'      => null,
                'approved_by'      => null,
            ]);

            return $locked;
        });
    }
}
