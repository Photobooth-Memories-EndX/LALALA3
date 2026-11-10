<?php

namespace App\Services;

use App\Models\Assesment;
use App\Models\AssesmentQuestion;
use App\Models\AssesmentQuestionBank;
use App\Models\AssesmentResult;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use App\Services\Learning\LearningProgressResolver;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
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
            if ($this->isDraftExpired($assessment, $activeDraft, $now ?? now())) {
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

    /**
     * Determines whether an in-progress draft attempt has expired based on
     * its effective deadline (access period and/or timer limit).
     */
    public function isDraftExpired(Assesment $assessment, AssesmentResult $draft, ?Carbon $now = null): bool
    {
        if ((int) $draft->final === 1) {
            return false;
        }

        $hasAccessPeriod = $assessment->hasAccessPeriod() && !empty($assessment->todate);
        $hasTimeLimit    = $assessment->hasTimeLimit();

        $effectiveDeadline = null;
        if ($hasAccessPeriod && $hasTimeLimit && $draft->created_at) {
            $periodDeadline    = Carbon::parse($assessment->todate);
            $timeLimitDeadline = $draft->created_at->copy()->addMinutes((int) $assessment->time_limit_minutes);
            $effectiveDeadline = $periodDeadline->min($timeLimitDeadline);
        } elseif ($hasAccessPeriod) {
            $effectiveDeadline = Carbon::parse($assessment->todate);
        } elseif ($hasTimeLimit && $draft->created_at) {
            $effectiveDeadline = $draft->created_at->copy()->addMinutes((int) $assessment->time_limit_minutes);
        }

        if ($effectiveDeadline !== null) {
            $nowCarbon = $now ?? now();
            return $nowCarbon->gt($effectiveDeadline);
        }

        return false;
    }

    /**
     * Finalizes an active draft attempt that has expired.
     */
    public function finalizeExpiredDraft(AssesmentResult $result, ?Assesment $assessment = null): void
    {
        if ((int) $result->final === 1) {
            return;
        }

        $assessment = $assessment ?? $result->assesment ?? Assesment::find($result->assesment_id);
        if (!$assessment) {
            return;
        }

        $existingUserAnswers = is_array($result->user_answer) ? $result->user_answer : [];
        $scoreData = $this->calculateAttemptScore($result, $assessment, $existingUserAnswers);

        $result->score = $scoreData['score'];
        $result->batch = $assessment->batch;
        $result->pass = $scoreData['pass'];
        $result->final = 1;
        $result->save();

        $this->syncAfkToLearningProgress($assessment, (int) $result->user_id, (int) ($result->afk_duration_seconds ?? 0));

        $completedKey = 'assessment-completed:' . $result->user_id . ':' . $assessment->id . ':' . $assessment->batch . ':' . $result->id;
        Cache::put($completedKey, $result->id, now()->addDay());
    }

    /**
     * Calculates score and pass status based on attempt question snapshot.
     */
    public function calculateAttemptScore(AssesmentResult $result, Assesment $assessment, array $userAnswers): array
    {
        $this->ensureQuestionSnapshot($result, $assessment);

        $snapshot = is_array($result->question_snapshot) ? $result->question_snapshot : [];
        $snapshotQuestionIds = array_column($snapshot, 'question_id');

        $correctAnswers = !empty($snapshotQuestionIds)
            ? AssesmentQuestion::whereIn('id', $snapshotQuestionIds)->pluck('answer', 'id')->toArray()
            : [];
        $totalQuestions = count($snapshotQuestionIds);

        $correctCount = 0;
        foreach ($snapshotQuestionIds as $qId) {
            $expected = $correctAnswers[$qId] ?? null;
            $actual = $userAnswers[$qId] ?? null;

            if ($expected !== null && $actual !== null && (string) $actual === (string) $expected) {
                $correctCount++;
            }
        }

        $formulaScorePerQuestion = $totalQuestions > 0 ? (100 / $totalQuestions) : 0;
        $score = $correctCount * $formulaScorePerQuestion;
        $score = $score >= 100 ? 100 : round($score, 2);
        $pass = ($score < (float) ($assessment->passing_grade ?? 0)) ? 0 : 1;

        return [
            'score'           => $score,
            'pass'            => $pass,
            'correct_count'   => $correctCount,
            'total_questions' => $totalQuestions,
        ];
    }

    /**
     * Ensures question snapshot is locked and populated for an attempt.
     */
    public function ensureQuestionSnapshot(AssesmentResult $resultDraft, Assesment $assessment)
    {
        if (!empty($resultDraft->question_snapshot) && is_array($resultDraft->question_snapshot) && count($resultDraft->question_snapshot) > 0) {
            $snapshotIds = array_column($resultDraft->question_snapshot, 'question_id');
            $questions = AssesmentQuestion::whereIn('id', $snapshotIds)->get()->keyBy('id');
            return collect($snapshotIds)->map(fn($id) => $questions->get($id))->filter()->values();
        }

        $lockKey = 'assessment-snapshot-lock:' . ($resultDraft->id ?? ($resultDraft->user_id . ':' . $assessment->id));
        return Cache::lock($lockKey, 10)->block(5, function () use ($resultDraft, $assessment) {
            $resultDraft->refresh();
            if (!empty($resultDraft->question_snapshot) && is_array($resultDraft->question_snapshot) && count($resultDraft->question_snapshot) > 0) {
                $snapshotIds = array_column($resultDraft->question_snapshot, 'question_id');
                $questions = AssesmentQuestion::whereIn('id', $snapshotIds)->get()->keyBy('id');
                return collect($snapshotIds)->map(fn($id) => $questions->get($id))->filter()->values();
            }

            $questionBankCount = AssesmentQuestionBank::where('assesment_id', $assessment->id)->count();

            if ($questionBankCount > 0) {
                $questionBanks = AssesmentQuestionBank::where('assesment_id', $assessment->id)->get();
                $assesment_question = collect();
                foreach ($questionBanks as $questionBank) {
                    $questions = AssesmentQuestion::where('assesment_id', $assessment->id)
                        ->where('question_bank_id', $questionBank->question_bank_id)
                        ->inRandomOrder()
                        ->take($questionBank->questions_amount)
                        ->get();
                    $assesment_question = $assesment_question->merge($questions);
                }
                $assesment_question = $assesment_question->shuffle();
            } else {
                $assesment_question = AssesmentQuestion::where('assesment_id', $assessment->id)->inRandomOrder()->get();
            }

            $snapshotData = $assesment_question->values()->map(function ($q, $index) {
                return [
                    'question_id' => $q->id,
                    'order'       => $index + 1,
                ];
            })->toArray();

            if (!$resultDraft->exists) {
                $resultDraft->user_answer = $resultDraft->user_answer ?? [];
                $resultDraft->score = $resultDraft->score ?? 0;
                $resultDraft->pass = $resultDraft->pass ?? 0;
                $resultDraft->final = $resultDraft->final ?? 0;
            }

            $resultDraft->question_snapshot = $snapshotData;
            $resultDraft->save();

            return $assesment_question;
        });
    }

    /**
     * Synchronizes assessment attempt AFK duration to content_users and/or playlist_content_users.
     */
    public function syncAfkToLearningProgress(Assesment $assessment, int $userId, int $attemptAfkSeconds): void
    {
        if ($attemptAfkSeconds < 0 || $userId <= 0) {
            return;
        }

        if (!empty($assessment->content_id)) {
            $contentStatus = $assessment->content?->status ?? 'non mandatory';
            $contentUser = ContentUser::firstOrCreate(
                [
                    'user_id'    => $userId,
                    'content_id' => $assessment->content_id,
                ],
                [
                    'status'     => $contentStatus,
                ]
            );

            if ($contentUser) {
                $totalAssessmentAfk = (int) AssesmentResult::where('user_id', $userId)
                    ->where('assesment_id', $assessment->id)
                    ->sum('afk_duration_seconds');

                $contentUser->afk_duration_seconds = max((int) ($contentUser->afk_duration_seconds ?? 0), $totalAssessmentAfk);
                $contentUser->save();
            }
        }

        if (!empty($assessment->trainingplan_id) && !empty($assessment->content_id)) {
            $contentStatus = $assessment->content?->status ?? 'non mandatory';
            $playlistContentUser = PlaylistContentUser::firstOrCreate(
                [
                    'user_id'         => $userId,
                    'trainingplan_id' => $assessment->trainingplan_id,
                    'content_id'      => $assessment->content_id,
                ],
                [
                    'status'          => $contentStatus,
                ]
            );

            if ($playlistContentUser) {
                $totalAssessmentAfk = (int) AssesmentResult::where('user_id', $userId)
                    ->where('assesment_id', $assessment->id)
                    ->sum('afk_duration_seconds');

                $playlistContentUser->afk_duration_seconds = max((int) ($playlistContentUser->afk_duration_seconds ?? 0), $totalAssessmentAfk);
                $playlistContentUser->save();
            }
        }
    }

    /**
     * Finalizes all expired drafts for a given assessment.
     */
    public function finalizeExpiredDraftsForAssessment(Assesment $assessment): int
    {
        $drafts = AssesmentResult::where('assesment_id', $assessment->id)
            ->where('final', 0)
            ->get();

        $finalizedCount = 0;
        foreach ($drafts as $draft) {
            if ($this->isDraftExpired($assessment, $draft)) {
                $this->finalizeExpiredDraft($draft, $assessment);
                $finalizedCount++;
            }
        }

        return $finalizedCount;
    }

    /**
     * Finalizes all expired drafts for a given user.
     */
    public function finalizeExpiredDraftsForUser(User|int $user): int
    {
        $userId = is_numeric($user) ? (int) $user : (int) ($user->id ?? 0);
        if ($userId <= 0) {
            return 0;
        }

        $drafts = AssesmentResult::with('assesment')
            ->where('user_id', $userId)
            ->where('final', 0)
            ->get();

        $finalizedCount = 0;
        foreach ($drafts as $draft) {
            $assessment = $draft->assesment;
            if ($assessment && $this->isDraftExpired($assessment, $draft)) {
                $this->finalizeExpiredDraft($draft, $assessment);
                $finalizedCount++;
            }
        }

        return $finalizedCount;
    }
}
