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
     * 1. If explicit trainingplan_id is passed in overrides, use it.
     * 2. If source assessment already has trainingplan_id, use it.
     * 3. If source content belongs to exactly one playlist via TrainingPlanDetail, use it.
     * 4. If source content belongs to multiple playlists and no override was provided,
     *    throw an InvalidArgumentException to avoid guessing or picking randomly.
     * 5. If source content belongs to 0 playlists, return null.
     */
    public function resolveTargetPlaylistId(Assesment $assesment, array $overrides = []): ?int
    {
        // 1. Explicit override
        if (!empty($overrides['trainingplan_id'])) {
            return (int) $overrides['trainingplan_id'];
        }

        // 2. Source assessment already has trainingplan_id
        if (!empty($assesment->trainingplan_id)) {
            return (int) $assesment->trainingplan_id;
        }

        // 3. Look up from source Content's TrainingPlanDetail
        if (!empty($assesment->content_id)) {
            $playlistIds = TrainingPlanDetail::where('content_id', $assesment->content_id)
                ->pluck('trainingplan_id')
                ->unique()
                ->values();

            if ($playlistIds->count() === 1) {
                return (int) $playlistIds[0];
            }

            if ($playlistIds->count() > 1) {
                throw new \InvalidArgumentException(
                    "Target Playlist is ambiguous: Content ID {$assesment->content_id} belongs to multiple Playlists (" .
                    $playlistIds->implode(', ') .
                    "). Please specify trainingplan_id explicitly."
                );
            }
        }

        return null;
    }

    /**
     * Duplicate assessment with all configuration, question banks, and questions.
     * Historical results, attempts, assigned users, and unique codes are not copied.
     * For Content Assessment (type = content), a new duplicate Content record is created
     * and linked, unless explicit content_id override is supplied (e.g. Bulk Duplicate).
     * If the source assessment or its content is associated with a playlist (or specified via overrides),
     * the duplicate becomes a Playlist Assessment (content_for = playlist) with the resolved playlist target,
     * and TrainingPlanDetail pivot is ensured idempotently.
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

            // Resolve target playlist ID
            $targetTpId = $this->resolveTargetPlaylistId($assesment, $overrides);

            // Validate context overrides if supplied
            if (isset($overrides['content_for'])) {
                if ($assesment->type !== 'content') {
                    throw new \InvalidArgumentException('Only content assessments can have content context overrides.');
                }
                if ($overrides['content_for'] === 'playlist') {
                    if (empty($targetTpId)) {
                        throw new \InvalidArgumentException('trainingplan_id is required when duplicating to playlist context.');
                    }
                } elseif ($overrides['content_for'] !== 'content') {
                    throw new \InvalidArgumentException("Invalid content_for context: {$overrides['content_for']}. Must be 'content' or 'playlist'.");
                }
            }

            // Determine target context:
            // 1. If explicit content_for override is supplied, respect it.
            // 2. If target playlist was resolved (from overrides, source TP, or source content's TrainingPlanDetail),
            //    the duplicated assessment becomes a Playlist Assessment ('playlist').
            // 3. Otherwise (e.g. standalone content not in any playlist), remains 'content'.
            if (isset($overrides['content_for'])) {
                $targetContentFor = $overrides['content_for'];
            } elseif (!empty($targetTpId)) {
                $targetContentFor = 'playlist';
            } else {
                $targetContentFor = 'content';
            }

            if ($assesment->type === 'content') {
                // Determine target Content ID:
                // 1. If explicit content_id override is supplied (e.g. Bulk Duplicate with mapping):
                if (isset($overrides['content_id']) && !empty($overrides['content_id'])) {
                    $targetContentId = (int) $overrides['content_id'];
                } elseif (!empty($assesment->content_id)) {
                    // 2. Single Duplicate: duplicate the source Content to create a new Content
                    $sourceContent = Content::find($assesment->content_id) ?? Content::withTrashed()->find($assesment->content_id);
                    if ($sourceContent) {
                        $newContent = $this->duplicateContent($sourceContent);
                        $targetContentId = (int) $newContent->id;
                    } else {
                        $targetContentId = null;
                    }
                } else {
                    $targetContentId = null;
                }

                $newAssesment->content_id = $targetContentId;

                if ($targetContentFor === 'playlist') {
                    $newAssesment->content_for     = 'playlist';
                    $newAssesment->trainingplan_id = $targetTpId;

                    // Ensure TrainingPlanDetail pivot exists for (trainingplan_id, content_id) (idempotent)
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
                } else {
                    // Direct Content Assessment
                    $newAssesment->content_for     = 'content';
                    $newAssesment->trainingplan_id = null;
                }
            } elseif ($assesment->type === 'trainingplan') {
                $newAssesment->content_for     = 'content';
                $newAssesment->content_id      = null;
                $newAssesment->trainingplan_id = $targetTpId;
            } else {
                // Global Assessment
                $newAssesment->content_for     = 'content';
                $newAssesment->content_id      = null;
                $newAssesment->trainingplan_id = null;
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
}
