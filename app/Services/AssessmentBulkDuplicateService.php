<?php

namespace App\Services;

use App\Models\Assesment;
use App\Models\TrainingPlan;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class AssessmentBulkDuplicateService
{
    protected AssessmentDuplicateService $duplicateService;

    public function __construct(AssessmentDuplicateService $duplicateService)
    {
        $this->duplicateService = $duplicateService;
    }

    /**
     * Preview duplication for selected source assessment IDs.
     */
    public function preview(array $sourceIds, array $options = []): array
    {
        $targetContext = $options['target_context'] ?? 'playlist';
        $trainingplanId = isset($options['trainingplan_id']) ? (int) $options['trainingplan_id'] : null;

        $this->validateTargetOptions($targetContext, $trainingplanId);

        $assessments = Assesment::whereIn('id', $sourceIds)->get()->keyBy('id');

        $willBeCreated = [];
        $alreadyExists = [];
        $ineligible = [];

        // Track content_ids to prevent within-batch duplicates during preview
        $previewProcessedContentIds = [];
        $contentMapping = $options['content_mapping'] ?? [];

        foreach ($sourceIds as $id) {
            $assessment = $assessments->get($id);

            if (!$assessment) {
                $ineligible[] = [
                    'source_assessment_id' => $id,
                    'content_id'           => null,
                    'title'                => '-',
                    'reason'               => 'NOT_FOUND',
                ];
                continue;
            }

            if ($assessment->type !== 'content') {
                $ineligible[] = [
                    'source_assessment_id' => $assessment->id,
                    'content_id'           => $assessment->content_id,
                    'title'                => $assessment->title,
                    'reason'               => 'INVALID_TYPE',
                ];
                continue;
            }

            if ($assessment->status !== 'OK') {
                $ineligible[] = [
                    'source_assessment_id' => $assessment->id,
                    'content_id'           => $assessment->content_id,
                    'title'                => $assessment->title,
                    'reason'               => 'INACTIVE',
                ];
                continue;
            }

            $targetContentId = isset($contentMapping[$id]) && !empty($contentMapping[$id])
                ? (int) $contentMapping[$id]
                : (int) $assessment->content_id;

            // Check playlist idempotency if target is playlist
            if ($targetContext === 'playlist') {
                $contentId = $targetContentId;
                if (in_array($contentId, $previewProcessedContentIds, true) || $this->isAlreadyInPlaylist($contentId, (int) $trainingplanId)) {
                    $alreadyExists[] = [
                        'source_assessment_id' => $assessment->id,
                        'content_id'           => $targetContentId,
                        'title'                => $assessment->title,
                        'reason'               => 'ALREADY_EXISTS',
                    ];
                    continue;
                }
                $previewProcessedContentIds[] = $contentId;
            }

            $willBeCreated[] = [
                'source_assessment_id' => $assessment->id,
                'content_id'           => $targetContentId,
                'title'                => $assessment->title,
            ];
        }

        return [
            'total'            => count($sourceIds),
            'will_be_created'  => count($willBeCreated),
            'already_exists'   => count($alreadyExists),
            'ineligible'       => count($ineligible),
            'eligible_items'   => $willBeCreated,
            'skipped_items'    => $alreadyExists,
            'ineligible_items' => $ineligible,
        ];
    }

    /**
     * Preview duplication for all active content assessments.
     */
    public function previewAllActive(array $options = []): array
    {
        $activeContentIds = Assesment::where('type', 'content')
            ->where('status', 'OK')
            ->pluck('id')
            ->toArray();

        return $this->preview($activeContentIds, $options);
    }

    /**
     * Duplicate multiple selected assessments.
     */
    public function duplicateMany(array $sourceIds, array $options = []): array
    {
        $targetContext = $options['target_context'] ?? 'playlist';
        $trainingplanId = isset($options['trainingplan_id']) ? (int) $options['trainingplan_id'] : null;

        $this->validateTargetOptions($targetContext, $trainingplanId);

        $assessments = Assesment::whereIn('id', $sourceIds)
            ->with(['assessment_question_banks', 'questions'])
            ->get()
            ->keyBy('id');

        $createdItems = [];
        $skippedItems = [];
        $failedItems = [];

        $overrides = [
            'content_for'     => $targetContext,
            'trainingplan_id' => $targetContext === 'playlist' ? $trainingplanId : null,
        ];

        // Track content_ids duplicated during this batch run to prevent within-batch duplicates
        $batchProcessedContentIds = [];
        $contentMapping = $options['content_mapping'] ?? [];

        foreach ($sourceIds as $id) {
            $assessment = $assessments->get($id);

            if (!$assessment) {
                $skippedItems[] = [
                    'source_assessment_id' => $id,
                    'content_id'           => null,
                    'title'                => '-',
                    'reason'               => 'NOT_FOUND',
                ];
                continue;
            }

            if ($assessment->type !== 'content') {
                $skippedItems[] = [
                    'source_assessment_id' => $assessment->id,
                    'content_id'           => $assessment->content_id,
                    'title'                => $assessment->title,
                    'reason'               => 'INVALID_TYPE',
                ];
                continue;
            }

            if ($assessment->status !== 'OK') {
                $skippedItems[] = [
                    'source_assessment_id' => $assessment->id,
                    'content_id'           => $assessment->content_id,
                    'title'                => $assessment->title,
                    'reason'               => 'INACTIVE',
                ];
                continue;
            }

            $targetContentId = isset($contentMapping[$id]) && !empty($contentMapping[$id])
                ? (int) $contentMapping[$id]
                : (int) $assessment->content_id;

            // Playlist idempotency check
            if ($targetContext === 'playlist') {
                $contentId = $targetContentId;

                if (in_array($contentId, $batchProcessedContentIds, true) || $this->isAlreadyInPlaylist($contentId, (int) $trainingplanId)) {
                    $skippedItems[] = [
                        'source_assessment_id' => $assessment->id,
                        'content_id'           => $targetContentId,
                        'title'                => $assessment->title,
                        'reason'               => 'ALREADY_EXISTS',
                    ];
                    continue;
                }
            }

            try {
                $itemOverrides = array_merge($overrides, [
                    'content_id' => $targetContentId,
                ]);

                $duplicated = $this->duplicateService->duplicate($assessment, $itemOverrides);

                if ($targetContext === 'playlist' && $targetContentId) {
                    $batchProcessedContentIds[] = $targetContentId;
                }

                $createdItems[] = [
                    'source_assessment_id' => $assessment->id,
                    'new_assessment_id'    => $duplicated->id,
                    'content_id'           => $duplicated->content_id,
                    'title'                => $duplicated->title,
                    'target_context'       => $targetContext,
                    'trainingplan_id'      => $duplicated->trainingplan_id,
                ];
            } catch (\Throwable $e) {
                Log::error('AssessmentBulkDuplicateService failed to duplicate assessment: ' . $e->getMessage(), [
                    'assessment_id' => $assessment->id,
                    'options'       => $options,
                ]);

                $failedItems[] = [
                    'source_assessment_id' => $assessment->id,
                    'content_id'           => $assessment->content_id,
                    'title'                => $assessment->title,
                    'reason'               => $e->getMessage(),
                ];
            }
        }

        return [
            'total'         => count($sourceIds),
            'success'       => count($createdItems),
            'skipped'       => count($skippedItems),
            'failed'        => count($failedItems),
            'created_items' => $createdItems,
            'skipped_items' => $skippedItems,
            'failed_items'  => $failedItems,
        ];
    }

    /**
     * Duplicate all active content assessments.
     */
    public function duplicateAllActive(array $options = []): array
    {
        $activeContentIds = Assesment::where('type', 'content')
            ->where('status', 'OK')
            ->pluck('id')
            ->toArray();

        return $this->duplicateMany($activeContentIds, $options);
    }

    /**
     * Validate target context and trainingplan_id if target is playlist.
     */
    protected function validateTargetOptions(string $targetContext, ?int $trainingplanId): void
    {
        if (!in_array($targetContext, ['content', 'playlist'], true)) {
            throw new InvalidArgumentException("Invalid target_context: {$targetContext}. Must be 'content' or 'playlist'.");
        }

        if ($targetContext === 'playlist') {
            if (!$trainingplanId || !TrainingPlan::where('id', $trainingplanId)->exists()) {
                throw new InvalidArgumentException("A valid trainingplan_id is required when target_context is 'playlist'.");
            }
        }
    }

    /**
     * Check if an active assessment for this content already exists in the playlist.
     */
    public function isAlreadyInPlaylist(int $contentId, int $trainingplanId): bool
    {
        return Assesment::where('type', 'content')
            ->where('content_for', 'playlist')
            ->where('trainingplan_id', $trainingplanId)
            ->where('content_id', $contentId)
            ->where('status', 'OK')
            ->exists();
    }
}
