<?php

namespace App\Services\Learning;

use App\Models\Assesment;
use App\Models\AssesmentResult;
use App\Models\CertificateSetting;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\Task;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use App\Services\CertificateEligibilityResult;
use App\Services\CertificateEligibilityService;

class PlaylistCompletionRequirementsService
{
    public function __construct(
        private CertificateEligibilityService $certificateEligibility,
        private LearningProgressResolver $progressResolver
    ) {
    }

    /**
     * Resolve requirements state for a playlist detail page.
     *
     * @param TrainingPlan $trainingplan
     * @param Content $content
     * @param User $user
     * @param array $debugOverrides Session debug overrides if user is authorized
     * @param float|null $percentageProgress Precomputed playlist progress
     * @param int|null $contentAssessmentTotal Precomputed content assessment total
     * @param bool|null $hasPlaylistAssessment Precomputed has playlist assessment flag
     * @param bool|null $playlistAssessmentDone Precomputed playlist assessment done flag
     * @param CertificateEligibilityResult|null $certificateEligibilityResult Precomputed eligibility
     * @return array
     */
    public function resolveForPlaylist(
        TrainingPlan $trainingplan,
        Content $content,
        User $user,
        array $debugOverrides = [],
        ?float $percentageProgress = null,
        ?int $contentAssessmentTotal = null,
        ?bool $hasPlaylistAssessment = null,
        ?bool $playlistAssessmentDone = null,
        ?CertificateEligibilityResult $certificateEligibilityResult = null
    ): array {
        // 1. Learning Progress
        if ($percentageProgress === null) {
            $contentAll = TrainingPlanDetail::where('trainingplan_id', $trainingplan->id)
                ->with('content')
                ->orderByRaw('CAST(content_order AS UNSIGNED) ASC')
                ->get();
            $resolvedBatch = $this->progressResolver->resolvePlaylistBatch(
                (int) $user->id,
                (int) $trainingplan->id,
                $contentAll->pluck('content_id')
            );
            $completedCount = $resolvedBatch->filter(fn ($r) => $r->isFinished())->count();
            $percentageProgress = TrainingPlan::calculateProgressPercentage($completedCount, $contentAll->count());
        }

        $progress = (float) $percentageProgress;
        if (($debugOverrides['learning_progress'] ?? null) === 'pass') {
            $progress = 100.0;
        } elseif (($debugOverrides['learning_progress'] ?? null) === 'fail') {
            $progress = 0.0;
        }

        // 2. Assessment Content
        $playlistCertificateSetting = CertificateSetting::where('certificate_type', 'playlist')
            ->where('trainingplan_id', $trainingplan->id)
            ->where('is_active', 1)
            ->first();

        if ($contentAssessmentTotal === null) {
            $contentIds = TrainingPlanDetail::where('trainingplan_id', $trainingplan->id)->pluck('content_id');
            $contentAssessmentProgress = $this->certificateEligibility->contentAssessmentProgress(
                $contentIds,
                $user,
                $playlistCertificateSetting,
                (int) $trainingplan->id
            );
            $contentAssessmentTotal = $contentAssessmentProgress['total'];
        }

        $contentAssessmentAvailable = $contentAssessmentTotal > 0;
        if (($debugOverrides['content_assessment'] ?? null) === 'available') {
            $contentAssessmentAvailable = true;
        } elseif (($debugOverrides['content_assessment'] ?? null) === 'not_available') {
            $contentAssessmentAvailable = false;
        }

        // 3. Playlist Assessment
        if ($hasPlaylistAssessment === null) {
            $playlistAssessment = Assesment::where('type', 'trainingplan')
                ->where('trainingplan_id', $trainingplan->id)
                ->where('status', 'OK')
                ->first();
            $hasPlaylistAssessment = $playlistAssessment !== null;
            if ($hasPlaylistAssessment) {
                $playlistAssessmentDone = AssesmentResult::where('user_id', $user->id)
                    ->where('assesment_id', $playlistAssessment->id)
                    ->where('final', 1)
                    ->exists();
            } else {
                $playlistAssessmentDone = false;
            }
        }

        $playlistProgressComplete = $progress >= 100;
        $playlistAssessmentAvailable = $hasPlaylistAssessment && ($playlistProgressComplete || (bool) $playlistAssessmentDone);
        if (($debugOverrides['playlist_assessment'] ?? null) === 'available') {
            $playlistAssessmentAvailable = true;
        } elseif (($debugOverrides['playlist_assessment'] ?? null) === 'not_available') {
            $playlistAssessmentAvailable = false;
        }

        // 4. My Task
        $myTaskAvailable = Task::where('status', 'published')
            ->where('parent_type', 'playlist')
            ->where('training_plan_id', $trainingplan->id)
            ->exists();

        // 5. Certificate
        if ($certificateEligibilityResult === null && $playlistCertificateSetting) {
            $certificateEligibilityResult = $this->certificateEligibility->check($playlistCertificateSetting, $user);
        }

        $certificateAvailable = (bool) ($certificateEligibilityResult?->eligible ?? false);
        if (($debugOverrides['certificate'] ?? null) === 'available') {
            $certificateAvailable = true;
        } elseif (($debugOverrides['certificate'] ?? null) === 'not_available') {
            $certificateAvailable = false;
        }

        return [
            'learning_progress'            => $progress,
            'content_assessment_available' => $contentAssessmentAvailable,
            'playlist_assessment_available'=> $playlistAssessmentAvailable,
            'my_task_available'            => $myTaskAvailable,
            'certificate_available'        => $certificateAvailable,
            'certificate_setting'          => $playlistCertificateSetting,
            'certificate_eligibility'      => $certificateEligibilityResult,
        ];
    }

    /**
     * Resolve requirements state for a content detail page (Document or Video).
     *
     * @param Content $content
     * @param User $user
     * @param TrainingPlan|null $playlist
     * @param float|null $contentProgress
     * @param bool|null $hasContentAssessment
     * @param array $debugOverrides Session debug overrides if user is authorized
     * @return array
     */
    public function resolveForContent(
        Content $content,
        User $user,
        ?TrainingPlan $playlist = null,
        ?float $contentProgress = null,
        ?bool $hasContentAssessment = null,
        array $debugOverrides = []
    ): array {
        // 1. Learning Progress
        if ($contentProgress === null) {
            $contentUser = ContentUser::where('user_id', $user->id)
                ->where('content_id', $content->id)
                ->first();
            $progress = (float) ($contentUser?->progress ?? 0);
        } else {
            $progress = (float) $contentProgress;
        }

        if (($debugOverrides['learning_progress'] ?? null) === 'pass') {
            $progress = 100.0;
        } elseif (($debugOverrides['learning_progress'] ?? null) === 'fail') {
            $progress = 0.0;
        }

        // 2. Assessment Content
        if ($hasContentAssessment === null) {
            $hasContentAssessment = Assesment::where('type', 'content')
                ->where(function ($q) {
                    $q->where('assasment_content_for', 'content')
                      ->orWhereNull('assasment_content_for');
                })
                ->where('content_id', $content->id)
                ->where('status', 'OK')
                ->exists();
        }
        $contentAssessmentAvailable = (bool) $hasContentAssessment;
        if (($debugOverrides['content_assessment'] ?? null) === 'available') {
            $contentAssessmentAvailable = true;
        } elseif (($debugOverrides['content_assessment'] ?? null) === 'not_available') {
            $contentAssessmentAvailable = false;
        }

        // 3. Playlist Assessment
        if ($playlist === null) {
            $playlist = $content->trainingPlans()->where('active', 'Active')->first()
                ?? $content->trainingPlans()->first();
        }

        $hasPlaylistAssessment = false;
        $playlistAssessmentAvailable = false;
        if ($playlist) {
            $plAssessment = Assesment::where('type', 'trainingplan')
                ->where('trainingplan_id', $playlist->id)
                ->where('status', 'OK')
                ->first();

            if ($plAssessment) {
                $hasPlaylistAssessment = true;
                $playlistAssessmentDone = AssesmentResult::where('user_id', $user->id)
                    ->where('assesment_id', $plAssessment->id)
                    ->where('final', 1)
                    ->exists();
                $playlistAssessmentAvailable = ($progress >= 100 || $playlistAssessmentDone);
            }
        }
        if (($debugOverrides['playlist_assessment'] ?? null) === 'available') {
            $playlistAssessmentAvailable = true;
        } elseif (($debugOverrides['playlist_assessment'] ?? null) === 'not_available') {
            $playlistAssessmentAvailable = false;
        }

        // 4. My Task
        $myTaskAvailable = Task::where('status', 'published')
            ->where(function ($q) use ($content, $playlist) {
                $q->where(function ($w) use ($content) {
                    $w->where('parent_type', 'content')
                      ->where('content_id', $content->id);
                });
                if ($playlist) {
                    $q->orWhere(function ($w) use ($playlist) {
                        $w->where('parent_type', 'playlist')
                          ->where('training_plan_id', $playlist->id);
                    });
                }
            })
            ->exists();

        // 5. Certificate
        $contentSetting = CertificateSetting::where('certificate_type', 'content')
            ->where('content_id', $content->id)
            ->where('is_active', 1)
            ->first();

        $playlistSetting = null;
        if (!$contentSetting && $playlist) {
            $playlistSetting = CertificateSetting::where('certificate_type', 'playlist')
                ->where('trainingplan_id', $playlist->id)
                ->where('is_active', 1)
                ->first();
        }

        $setting = $contentSetting ?? $playlistSetting;
        $certificateEligibilityResult = null;
        if ($setting) {
            $certificateEligibilityResult = $this->certificateEligibility->check($setting, $user);
        }

        $certificateAvailable = (bool) ($certificateEligibilityResult?->eligible ?? false);
        if (($debugOverrides['certificate'] ?? null) === 'available') {
            $certificateAvailable = true;
        } elseif (($debugOverrides['certificate'] ?? null) === 'not_available') {
            $certificateAvailable = false;
        }

        return [
            'learning_progress'            => $progress,
            'content_assessment_available' => $contentAssessmentAvailable,
            'playlist_assessment_available'=> $playlistAssessmentAvailable,
            'my_task_available'            => $myTaskAvailable,
            'certificate_available'        => $certificateAvailable,
            'certificate_setting'          => $setting,
            'certificate_eligibility'      => $certificateEligibilityResult,
        ];
    }
}
