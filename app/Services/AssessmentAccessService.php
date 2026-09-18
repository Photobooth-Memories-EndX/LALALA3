<?php

namespace App\Services;

use App\Models\Assesment;
use App\Models\AssesmentResult;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use App\Services\Learning\LearningProgressResolver;
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

    public function __construct(private LearningProgressResolver $resolver)
    {
    }

    public function denialReason(Assesment $assessment, User $user, ?Carbon $now = null, ?int $trainingPlanId = null): ?string
    {
        // DEBUG ONLY:
        // Role 2 = Admin
        // Role 3 = Super Admin
        // These roles bypass Assessment access restrictions for debugging/testing purposes only.
        if ($this->isAssessmentDebugBypass($user)) {
            return null;
        }

        // 2. Database Status: status = NOT means assessment is inactive for non-admins
        if (strtoupper(trim((string) ($assessment->status ?? ''))) === 'NOT') {
            return self::REASON_INACTIVE;
        }

        // 3. Datetime schedule & time limit evaluation
        $nowValue = ($now ?? now())->format('Y-m-d H:i:s');

        // Check if user already has an active draft (already started)
        $activeDraft = AssesmentResult::where('user_id', $user->id)
            ->where('assesment_id', $assessment->id)
            ->where('batch', $assessment->batch)
            ->where('final', 0)
            ->latest('id')
            ->first();

        if ($activeDraft) {
            // User has ALREADY STARTED the assessment. Evaluate effective deadline.
            $hasAccessPeriod = $assessment->hasAccessPeriod() && !empty($assessment->todate);
            $hasTimeLimit    = $assessment->timer === 'y' && !empty($assessment->time_limit_minutes) && (int) $assessment->time_limit_minutes > 0;

            $effectiveDeadline = null;
            if ($hasAccessPeriod && $hasTimeLimit) {
                $periodDeadline    = Carbon::parse($assessment->todate);
                $timeLimitDeadline = $activeDraft->created_at->copy()->addMinutes((int) $assessment->time_limit_minutes);
                $effectiveDeadline = $periodDeadline->min($timeLimitDeadline);
            } elseif ($hasAccessPeriod) {
                $effectiveDeadline = Carbon::parse($assessment->todate);
            } elseif ($hasTimeLimit) {
                $effectiveDeadline = $activeDraft->created_at->copy()->addMinutes((int) $assessment->time_limit_minutes);
            }

            if ($effectiveDeadline !== null && $nowValue > $effectiveDeadline->format('Y-m-d H:i:s')) {
                return self::REASON_EXPIRED;
            }
        } else {
            // User has NOT STARTED yet. Check Access Period if enabled.
            if ($assessment->hasAccessPeriod() && $assessment->fromdate && $assessment->todate) {
                if ($nowValue < $assessment->fromdate) {
                    return self::REASON_BEFORE_START;
                }

                if ($nowValue > $assessment->todate) {
                    return self::REASON_EXPIRED;
                }
            }
        }

        // 4. Assignment check
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

        // 5. Content / Playlist progress check via LearningProgressResolver
        if ($this->isProgressRequirementUnmet($assessment, $user, $trainingPlanId)) {
            return self::REASON_PROGRESS;
        }

        // 6. Passed check
        $userResults = AssesmentResult::where('user_id', $user->id)
            ->where('assesment_id', $assessment->id)
            ->where('batch', $assessment->batch)
            ->where('final', 1);

        $hasPassed = (clone $userResults)->where('pass', 1)->exists();
        if ($hasPassed) {
            return self::REASON_PASSED;
        }

        // 7. Max attempts check
        $maxAttempts = (string) ($assessment->type ?? '') === 'global' ? 1 : (1 + (int) ($assessment->total_remedials ?? 0));
        $userAttempts = $userResults->count();

        if ($userAttempts >= $maxAttempts) {
            return self::REASON_MAX_ATTEMPTS;
        }

        return null;
    }

    public function denialReasonForSubmitting(Assesment $assessment, User $user, ?Carbon $now = null, ?int $trainingPlanId = null): ?string
    {
        if ($this->isAssessmentDebugBypass($user)) {
            return null;
        }

        if (strtoupper(trim((string) ($assessment->status ?? ''))) === 'NOT') {
            return self::REASON_INACTIVE;
        }

        $activeDraft = AssesmentResult::where('user_id', $user->id)
            ->where('assesment_id', $assessment->id)
            ->where('batch', $assessment->batch)
            ->where('final', 0)
            ->latest('id')
            ->first();

        if ($activeDraft) {
            return null;
        }

        return $this->denialReason($assessment, $user, $now, $trainingPlanId);
    }

    public function authorize(Assesment $assessment, User $user, ?int $trainingPlanId = null): void
    {
        $reason = $this->denialReason($assessment, $user, null, $trainingPlanId);

        if ($reason !== null) {
            throw new AccessDeniedHttpException($this->message($reason));
        }
    }

    /**
     * Check if progress requirement for this assessment is unmet.
     */
    private function isProgressRequirementUnmet(Assesment $assessment, User $user, ?int $contextTrainingPlanId = null): bool
    {
        // A. Playlist Assessment (type = trainingplan or has trainingplan_id without content_id)
        if ($assessment->type === 'trainingplan' || ($assessment->trainingplan_id && !$assessment->content_id)) {
            $planId = $assessment->trainingplan_id ?? $contextTrainingPlanId;
            if (!$planId) {
                return false;
            }

            $contentIds = TrainingPlanDetail::where('trainingplan_id', $planId)
                ->whereHas('content', fn ($q) => $q->where('active', 'Active'))
                ->pluck('content_id');

            if ($contentIds->isEmpty()) {
                return false;
            }

            $resolvedBatch = $this->resolver->resolvePlaylistBatch((int) $user->id, (int) $planId, $contentIds);
            $finishedCount = $resolvedBatch->filter(fn ($r) => $r->isFinished())->count();

            return $finishedCount < $contentIds->count();
        }

        // B. Content Assessment
        if ($assessment->type === 'content' && $assessment->content_id) {
            $planId = $assessment->trainingplan_id ?? $contextTrainingPlanId;

            if ($planId) {
                // Evaluated in explicit Playlist context
                $resolved = $this->resolver->resolvePlaylist((int) $user->id, (int) $planId, (int) $assessment->content_id);
                return !$resolved->isFinished() && $resolved->progress < ContentUser::ASSESSMENT_REQUIRED_PROGRESS;
            }

            // Check if content is playlist-only or single active playlist (Category A)
            $content = $assessment->relationLoaded('contents')
                ? $assessment->contents->first()
                : Content::find($assessment->content_id);

            $isPlaylistOnly = $content && ($content->playlistonly === 'Y' || (int) ($content->playlist_only ?? 0) === 1);

            if ($isPlaylistOnly) {
                $activePlanIds = TrainingPlanDetail::where('content_id', $assessment->content_id)
                    ->whereHas('trainingplan', fn ($q) => $q->where('active', 'Active'))
                    ->pluck('trainingplan_id');

                if ($activePlanIds->count() === 1) {
                    // Category A: Deterministic single active playlist
                    $resolved = $this->resolver->resolvePlaylist((int) $user->id, (int) $activePlanIds->first(), (int) $assessment->content_id);
                    return !$resolved->isFinished() && $resolved->progress < ContentUser::ASSESSMENT_REQUIRED_PROGRESS;
                }

                if ($activePlanIds->count() > 1) {
                    // Category B: Ambiguous multi-playlist without context
                    return true;
                }
            }

            // Direct Content context
            $resolved = $this->resolver->resolveDirect((int) $user->id, (int) $assessment->content_id);
            return !$resolved->isFinished() && $resolved->progress < ContentUser::ASSESSMENT_REQUIRED_PROGRESS;
        }

        return false;
    }

    public function denialReasonForViewingResult(Assesment $assessment, User $user): ?string
    {
        if ($this->isAssessmentDebugBypass($user)) {
            return null;
        }

        // If user has already completed this assessment, allow viewing their result
        $hasCompletedResult = AssesmentResult::where('user_id', $user->id)
            ->where('assesment_id', $assessment->id)
            ->where('final', 1)
            ->exists();

        if ($hasCompletedResult) {
            return null;
        }

        if (strtoupper(trim((string) ($assessment->status ?? ''))) === 'NOT') {
            return self::REASON_INACTIVE;
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

    /**
     * DEBUG ONLY
     *
     * Role 2 = Admin
     * Role 3 = Super Admin
     *
     * These roles bypass Assessment access restrictions
     * for debugging/testing purposes only.
     *
     * All other roles must follow the normal Assessment rules.
     */
    private function isAssessmentDebugBypass(User $user): bool
    {
        $roleId = (int) ($user->role ?? $user->role_id ?? 0);
        return in_array($roleId, [2, 3], true);
    }
}
