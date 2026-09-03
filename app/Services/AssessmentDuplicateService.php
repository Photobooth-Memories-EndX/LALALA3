<?php

namespace App\Services;

use App\Models\Assesment;
use App\Models\AssesmentQuestion;
use App\Models\AssesmentQuestionBank;
use App\Models\TrainingPlanDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
     * Duplicate assessment with all configuration, question banks, and questions.
     * Historical results, attempts, assigned users, and unique codes are not copied.
     * For Playlist Assessment (content_for = playlist with trainingplan_id), playlist and content
     * relations are preserved and TrainingPlanDetail pivot is ensured idempotently.
     * For Direct Content Assessment (content_for = content), content_id remains unassigned.
     * Optional overrides allow specifying target context/content (e.g. Bulk Duplicate with mapping).
     */
    public function duplicate(Assesment $assesment, array $overrides = []): Assesment
    {
        return DB::transaction(function () use ($assesment, $overrides) {
            $newAssesment = $assesment->replicate();
            $newAssesment->title       = $this->generateDuplicateTitle($assesment->title);
            $newAssesment->slug        = null; // Handled by Spatie Sluggable
            $newAssesment->unique_code = null;
            $newAssesment->created_at  = Carbon::now();
            $newAssesment->updated_at  = Carbon::now();

            // Validate context overrides if supplied
            if (isset($overrides['content_for'])) {
                if ($assesment->type !== 'content') {
                    throw new \InvalidArgumentException('Only content assessments can have content context overrides.');
                }
                if ($overrides['content_for'] === 'playlist') {
                    if (empty($overrides['trainingplan_id'])) {
                        throw new \InvalidArgumentException('trainingplan_id is required when duplicating to playlist context.');
                    }
                } elseif ($overrides['content_for'] !== 'content') {
                    throw new \InvalidArgumentException("Invalid content_for context: {$overrides['content_for']}. Must be 'content' or 'playlist'.");
                }
            }

            // Determine if source assessment is a Playlist Assessment
            $isSourcePlaylist = ($assesment->type === 'content')
                && (($assesment->content_for ?? 'content') === 'playlist')
                && !empty($assesment->trainingplan_id);

            // Determine target context
            $targetContentFor = $overrides['content_for'] ?? ($isSourcePlaylist ? 'playlist' : 'content');

            if ($assesment->type === 'content') {
                if ($targetContentFor === 'playlist') {
                    $newAssesment->content_for     = 'playlist';
                    $newAssesment->trainingplan_id = isset($overrides['trainingplan_id']) && !empty($overrides['trainingplan_id'])
                        ? (int) $overrides['trainingplan_id']
                        : ($isSourcePlaylist ? (int) $assesment->trainingplan_id : null);

                    if (isset($overrides['content_id']) && !empty($overrides['content_id'])) {
                        $newAssesment->content_id = (int) $overrides['content_id'];
                    } elseif ($isSourcePlaylist && !empty($assesment->content_id)) {
                        $newAssesment->content_id = (int) $assesment->content_id;
                    } else {
                        $newAssesment->content_id = null;
                    }

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
                    $newAssesment->content_id      = isset($overrides['content_id']) && !empty($overrides['content_id'])
                        ? (int) $overrides['content_id']
                        : null;
                }
            } elseif ($assesment->type === 'trainingplan') {
                $newAssesment->content_for     = 'content';
                $newAssesment->content_id      = null;
                $newAssesment->trainingplan_id = isset($overrides['trainingplan_id']) && !empty($overrides['trainingplan_id'])
                    ? (int) $overrides['trainingplan_id']
                    : (int) $assesment->trainingplan_id;
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

                foreach ($questions as $question) {
                    $newQuestion = $question->replicate();
                    $newQuestion->assesment_id     = $newAssesment->id;
                    $newQuestion->question_bank_id = $newQuestionBank->question_bank_id;
                    $newQuestion->is_used          = false;
                    $newQuestion->created_at       = Carbon::now();
                    $newQuestion->updated_at       = Carbon::now();
                    $newQuestion->save();
                }

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
