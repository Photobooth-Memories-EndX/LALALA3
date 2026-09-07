<?php

namespace App\Services;

use App\Models\Assesment;
use App\Models\AssesmentQuestion;
use App\Models\AssesmentQuestionBank;
use App\Models\Content;
use App\Models\TrainingPlanDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class AssessmentDuplicateService
{
    /**
     * Extract base title by stripping trailing " Copy N".
     */
    public function extractBaseTitle(?string $title, string $default = 'Assessment'): string
    {
        $clean = trim((string) $title);
        if ($clean === '') {
            return $default;
        }

        $base = preg_replace('/\s+Copy\s+\d+$/i', '', $clean);

        return trim($base) !== '' ? trim($base) : $clean;
    }

    /**
     * Generate unique duplicate title with format: {Base Title} Copy N
     */
    public function generateDuplicateTitle(?string $originalTitle): string
    {
        $baseTitle = $this->extractBaseTitle($originalTitle, 'Assessment');

        // Escape % and _ for SQL LIKE queries
        $escapedBase = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $baseTitle);

        $existingTitles = Assesment::where('title', $baseTitle)
            ->orWhere('title', 'like', $escapedBase . ' Copy %')
            ->pluck('title');

        $usedNumbers = [];
        $pattern = '/^' . preg_quote($baseTitle, '/') . '\s+Copy\s+(\d+)$/i';

        foreach ($existingTitles as $title) {
            if (preg_match($pattern, (string) $title, $matches)) {
                $usedNumbers[] = (int) $matches[1];
            }
        }

        $nextNumber = !empty($usedNumbers) ? (max($usedNumbers) + 1) : 1;
        $candidateTitle = $baseTitle . ' Copy ' . $nextNumber;

        // Collision safety loop
        while (Assesment::where('title', $candidateTitle)->exists()) {
            $nextNumber++;
            $candidateTitle = $baseTitle . ' Copy ' . $nextNumber;
        }

        return $candidateTitle;
    }

    /**
     * Generate unique duplicate title for Content with format: {Base Title} Copy N
     */
    public function generateDuplicateContentTitle(?string $originalTitle): string
    {
        $baseTitle = $this->extractBaseTitle($originalTitle, 'Content');

        // Escape % and _ for SQL LIKE queries
        $escapedBase = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $baseTitle);

        $existingTitles = Content::where('title', $baseTitle)
            ->orWhere('title', 'like', $escapedBase . ' Copy %')
            ->pluck('title');

        $usedNumbers = [];
        $pattern = '/^' . preg_quote($baseTitle, '/') . '\s+Copy\s+(\d+)$/i';

        foreach ($existingTitles as $title) {
            if (preg_match($pattern, (string) $title, $matches)) {
                $usedNumbers[] = (int) $matches[1];
            }
        }

        $nextNumber = !empty($usedNumbers) ? (max($usedNumbers) + 1) : 1;
        $candidateTitle = $baseTitle . ' Copy ' . $nextNumber;

        // Collision safety loop
        while (Content::where('title', $candidateTitle)->exists()) {
            $nextNumber++;
            $candidateTitle = $baseTitle . ' Copy ' . $nextNumber;
        }

        return $candidateTitle;
    }

    /**
     * Duplicate a Content record with unique title and fresh slug.
     */
    public function duplicateContent(Content $sourceContent): Content
    {
        $newContent = $sourceContent->replicate(['views', 'deleted_at']);
        $newContent->title      = $this->generateDuplicateContentTitle($sourceContent->title);
        $newContent->slug       = null; // Handled by Spatie Sluggable
        $newContent->views      = 0;
        $newContent->created_at = Carbon::now();
        $newContent->updated_at = Carbon::now();
        $newContent->save();

        // Copy content assets if they exist in storage
        $sourcePath = public_path('storage/files/content/' . $sourceContent->id);
        $targetPath = public_path('storage/files/content/' . $newContent->id);
        if (File::isDirectory($sourcePath)) {
            File::makeDirectory($targetPath, 0777, true, true);
            File::copyDirectory($sourcePath, $targetPath);
        }

        return $newContent;
    }

    /**
     * Resolve target playlist ID for an assessment.
     *
     * Only explicit trainingplan_id in overrides is used.
     * Relational trainingplan_id from the source assessment or source content is never inherited.
     */
    public function resolveTargetPlaylistId(Assesment $assesment, array $overrides = []): ?int
    {
        // 1. Explicit override
        if (!empty($overrides['trainingplan_id'])) {
            return (int) $overrides['trainingplan_id'];
        }

        return null;
    }

    /**
     * Duplicate assessment with all configuration, question banks, and questions.
     * Historical results, attempts, assigned users, and unique codes are not copied.
     * For single duplication, Content/Playlist relations (content_id, trainingplan_id) are not copied
     * and become NULL. Explicit overrides (e.g. Bulk Duplicate) are respected.
     * The duplicate uses the target context (assasment_content_for).
     */
    public function duplicate(Assesment $assesment, array $overrides = []): Assesment
    {
        return DB::transaction(function () use ($assesment, $overrides) {
            $newAssesment = $assesment->replicate();
            $newAssesment->title       = isset($overrides['title']) ? (string) $overrides['title'] : $this->generateDuplicateTitle($assesment->title);
            $newAssesment->slug        = null; // Handled by Spatie Sluggable
            $newAssesment->unique_code = null;
            $newAssesment->created_at  = Carbon::now();
            $newAssesment->updated_at  = Carbon::now();

            // Resolve target playlist ID from explicit overrides only
            $targetTpId = $this->resolveTargetPlaylistId($assesment, $overrides);

            // Validate context overrides if supplied
            $overrideContext = $overrides['assasment_content_for'] ?? $overrides['target_context'] ?? null;
            if ($overrideContext !== null) {
                if ($assesment->type !== 'content') {
                    throw new \InvalidArgumentException('Only content assessments can have content context overrides.');
                }
                if (!in_array($overrideContext, ['content', 'playlist'], true)) {
                    throw new \InvalidArgumentException("Invalid content context: {$overrideContext}. Must be 'content' or 'playlist'.");
                }
            }

            // Determine target context:
            // 1. If explicit override is supplied, respect it.
            // 2. Otherwise, use source assessment's assasment_content_for (defaulting to 'content').
            $targetContentFor = $overrideContext ?? ($assesment->assasment_content_for ?? 'content');

            if ($assesment->type === 'content') {
                // Determine target Content ID:
                // Only use explicit content_id override if supplied (e.g. Bulk Duplicate).
                // Single duplicate never inherits source content_id or duplicates source content.
                $targetContentId = (isset($overrides['content_id']) && !empty($overrides['content_id']))
                    ? (int) $overrides['content_id']
                    : null;

                $newAssesment->content_id            = $targetContentId;
                $newAssesment->assasment_content_for = $targetContentFor;
                $newAssesment->trainingplan_id       = ($targetContentFor === 'playlist') ? $targetTpId : null;

                // Ensure TrainingPlanDetail pivot exists for (trainingplan_id, content_id) if both are set (idempotent)
                if (!empty($newAssesment->trainingplan_id) && !empty($newAssesment->content_id)) {
                    $pivotExists = TrainingPlanDetail::where('trainingplan_id', $newAssesment->trainingplan_id)
                        ->where('content_id', $newAssesment->content_id)
                        ->exists();

                    if (!$pivotExists) {
                        $maxOrder = (int) TrainingPlanDetail::where('trainingplan_id', $newAssesment->trainingplan_id)->max('content_order');
                        TrainingPlanDetail::create([
                            'trainingplan_id' => $newAssesment->trainingplan_id,
                            'content_id'      => $newAssesment->content_id,
                            'content_order'   => (string) ($maxOrder + 1),
                        ]);
                    }
                }
            } elseif ($assesment->type === 'trainingplan') {
                $newAssesment->assasment_content_for = 'content';
                $newAssesment->content_id            = null;
                $newAssesment->trainingplan_id       = $targetTpId;
            } else {
                // Global Assessment
                $newAssesment->assasment_content_for = 'content';
                $newAssesment->content_id            = null;
                $newAssesment->trainingplan_id       = null;
            }

            // Individual duplicate context handling:
            // 1. Result duplicate assessment must have type = NULL.
            // 2. If source assessment has status = OK, duplicate status automatically becomes NOT.
            //    Otherwise, existing status behavior is preserved.
            $isIndividual = !empty($overrides['is_individual']) || (($overrides['context'] ?? null) === 'individual');
            if ($isIndividual) {
                $newAssesment->type = null;

                if ($assesment->status === 'OK') {
                    $newAssesment->status = 'NOT';
                }
            }

            $newAssesment->save();

            // Clone Question Banks and linked questions
            foreach ($assesment->assessment_question_banks as $questionBank) {
                $newQuestionBank = $questionBank->replicate();
                $newQuestionBank->assesment_id = $newAssesment->id;
                $newQuestionBank->created_at   = Carbon::now();
                $newQuestionBank->updated_at   = Carbon::now();
                $newQuestionBank->save();

                // Clone Assessment Questions related to this question bank
                $questions = AssesmentQuestion::where('assesment_id', $assesment->id)
                    ->where('question_bank_id', $questionBank->question_bank_id)
                    ->get();

                $preserveIsUsed = (bool) ($overrides['preserve_is_used'] ?? false);

                foreach ($questions as $question) {
                    $newQuestion = $question->replicate();
                    $newQuestion->assesment_id     = $newAssesment->id;
                    $newQuestion->question_bank_id = $newQuestionBank->question_bank_id;
                    $newQuestion->is_used          = $preserveIsUsed ? (bool) $question->is_used : false;
                    $newQuestion->created_at       = Carbon::now();
                    $newQuestion->updated_at       = Carbon::now();
                    $newQuestion->save();
                }

                if (!$preserveIsUsed) {
                    // Randomize & mark used questions according to questions_amount
                    $amount = (int) $newQuestionBank->questions_amount;
                    if ($amount > 0) {
                        $randomizeQuestions = AssesmentQuestion::where('assesment_id', $newAssesment->id)
                            ->where('question_bank_id', $newQuestionBank->question_bank_id)
                            ->inRandomOrder()
                            ->limit($amount)
                            ->get();

                        foreach ($randomizeQuestions as $randomQuestion) {
                            $randomQuestion->is_used = true;
                            $randomQuestion->save();
                        }
                    }
                }
            }

            // Clone Direct Assessment Questions (without question bank, if any)
            $directQuestions = AssesmentQuestion::where('assesment_id', $assesment->id)
                ->whereNull('question_bank_id')
                ->get();

            foreach ($directQuestions as $directQuestion) {
                $newDirect = $directQuestion->replicate();
                $newDirect->assesment_id = $newAssesment->id;
                $newDirect->created_at   = Carbon::now();
                $newDirect->updated_at   = Carbon::now();
                $newDirect->save();
            }

            return $newAssesment;
        });
    }

    /**
     * Duplicate an individual assessment with type = NULL and status = NOT (if source was OK).
     */
    public function duplicateIndividual(Assesment $assesment, array $overrides = []): Assesment
    {
        $overrides['is_individual'] = true;

        return $this->duplicate($assesment, $overrides);
    }
}
