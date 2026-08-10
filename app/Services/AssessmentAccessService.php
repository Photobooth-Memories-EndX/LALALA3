<?php

namespace App\Services;

use App\Models\Assesment;
use App\Models\AssesmentResult;
use App\Models\ContentUser;
use App\Models\User;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class AssessmentAccessService
{
    public const REASON_ASSIGNED = 'assigned';
    public const REASON_INACTIVE = 'inactive';
    public const REASON_BEFORE_START = 'before_start';
    public const REASON_EXPIRED = 'expired';
    public const REASON_PROGRESS = 'progress';
    public const REASON_MAX_ATTEMPTS = 'max_attempts';
    public const REASON_PASSED = 'passed';

    public function denialReason(Assesment $assessment, User $user, ?Carbon $now = null): ?string
    {
        // Preserve the existing administrator bypass used by the assessment controller.
        if ($user->isAdmin()) {
            return null;
        }

        $assignedDescriptions = $assessment->assesments_assigned
            ->pluck('assigned_description');

        if ($assignedDescriptions->isNotEmpty()) {
            $userAssignments = [
                $user->name,
                $user->team,
                $user->department,
                $user->division,
            ];

            if (!$assignedDescriptions->intersect($userAssignments)->isNotEmpty()) {
                return self::REASON_ASSIGNED;
            }
        }

        if ($assessment->status === 'NOT') {
            return self::REASON_INACTIVE;
        }

        // Keep the existing schedule convention: enforce dates only when both
        // boundaries are configured.
        if ($assessment->fromdate && $assessment->todate) {
            $nowValue = ($now ?? now())->format('Y-m-d H:i:s');

            if ($nowValue < $assessment->fromdate) {
                return self::REASON_BEFORE_START;
            }

            if ($nowValue > $assessment->todate) {
                return self::REASON_EXPIRED;
            }
        }

        if ($assessment->type === 'content' && $assessment->content_id) {
            $learning = ContentUser::where('user_id', $user->id)
                ->where('content_id', $assessment->content_id)
                ->first();

            if (!$learning || !$learning->meetsAssessmentProgressRequirement()) {
                return self::REASON_PROGRESS;
            }
        }

        $userResults = AssesmentResult::where('user_id', $user->id)
            ->where('assesment_id', $assessment->id)
            ->where('batch', $assessment->batch)
            ->where('final', 1);

        $hasPassed = (clone $userResults)->where('pass', 1)->exists();
        if ($hasPassed) {
            return self::REASON_PASSED;
        }

        $maxAttempts = 1 + (int) ($assessment->total_remedials ?? 0);
        $userAttempts = $userResults->count();

        if ($userAttempts >= $maxAttempts) {
            return self::REASON_MAX_ATTEMPTS;
        }

        return null;
    }

    public function authorize(Assesment $assessment, User $user): void
    {
        $reason = $this->denialReason($assessment, $user);

        if ($reason !== null) {
            throw new AccessDeniedHttpException($this->message($reason));
        }
    }

    public function denialReasonForViewingResult(Assesment $assessment, User $user): ?string
    {
        if ($user->isAdmin()) {
            return null;
        }

        $assignedDescriptions = $assessment->assesments_assigned
            ->pluck('assigned_description');

        if ($assignedDescriptions->isNotEmpty()) {
            $userAssignments = [
                $user->name,
                $user->team,
                $user->department,
                $user->division,
            ];

            if (!$assignedDescriptions->intersect($userAssignments)->isNotEmpty()) {
                return self::REASON_ASSIGNED;
            }
        }

        if ($assessment->status === 'NOT') {
            return self::REASON_INACTIVE;
        }

        return null;
    }

    public function authorizeForViewingResult(Assesment $assessment, User $user): void
    {
        $reason = $this->denialReasonForViewingResult($assessment, $user);

        if ($reason !== null) {
            throw new AccessDeniedHttpException($this->message($reason));
        }
    }

    public function message(string $reason): string
    {
        return match ($reason) {
            self::REASON_ASSIGNED => 'You are not assigned to this assessment.',
            self::REASON_INACTIVE => 'This assessment is inactive.',
            self::REASON_BEFORE_START => 'This assessment is not yet available.',
            self::REASON_EXPIRED => 'This assessment has expired.',
            self::REASON_PROGRESS => 'Complete the related content before starting this assessment.',
            self::REASON_PASSED => 'You have already passed this assessment.',
            self::REASON_MAX_ATTEMPTS => 'Maximum assessment attempts reached.',
            default => 'You cannot access this assessment.',
        };
    }
}
