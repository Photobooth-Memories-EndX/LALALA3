<?php

namespace App\Services\Schedule;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingRegister;
use App\Models\OfflineTrainingUser;
use InvalidArgumentException;
use RuntimeException;

class OfflineTrainingUserSyncService
{
    /**
     * Synchronize an Approved registration into offline_training_users as an ONGOING training record.
     *
     * State semantics for ONGOING:
     * - progress = 0
     * - attendance = null
     * - learning_hours = 0
     * - certificate = null
     * - assessment_score = null
     *
     * Uses unique stable business identity: ['offline_training_id', 'user_id'].
     */
    public function syncApprovedToOngoing(OfflineTrainingRegister $registration): OfflineTrainingUser
    {
        $training = $registration->training;
        if (!$training) {
            throw new InvalidArgumentException('Associated training schedule not found.');
        }

        $user = $registration->user;
        if (!$user) {
            throw new InvalidArgumentException('Associated participant user not found.');
        }

        // Idempotently create or update row to Ongoing state
        return OfflineTrainingUser::updateOrCreate(
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
                'attendance'          => null,
                'progress'            => 0,
                'learning_hours'      => 0,
                'certificate'         => null,
                'assessment_score'    => null,
            ]
        );
    }

    /**
     * Synchronize a Completed registration, updating the SAME offline_training_users row
     * from ONGOING to COMPLETED.
     *
     * State semantics for COMPLETED:
     * - progress = 100
     * - attendance = true
     * - learning_hours = credited hours from training duration
     */
    public function syncCompleted(OfflineTrainingRegister $registration): OfflineTrainingUser
    {
        $training = $registration->training;
        if (!$training) {
            throw new InvalidArgumentException('Associated training schedule not found.');
        }

        $user = $registration->user;
        if (!$user) {
            throw new InvalidArgumentException('Associated participant user not found.');
        }

        // Calculate accredited learning hours from schedule duration
        $learningHours = $training->learning_hours ?? 0;
        if ((float) $learningHours <= 0 && filled($training->start_time) && filled($training->end_time)) {
            $start = strtotime($training->start_time);
            $end = strtotime($training->end_time);
            if ($end > $start) {
                $learningHours = round(($end - $start) / 3600, 2);
            }
        }

        // Update the SAME row to Completed state (or create if not present)
        return OfflineTrainingUser::updateOrCreate(
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
    }
}
