<?php

namespace App\Services;

use App\Models\Assesment;
use App\Models\AssesmentQuestion;
use App\Models\AssesmentQuestionBank;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AssessmentDuplicateService
{
    /**
     * Extract base title by stripping trailing " Copy N".
     */
    public function extractBaseTitle(?string $title): string
    {
        $clean = trim((string) $title);
        if ($clean === '') {
            return 'Assessment';
        }

        $base = preg_replace('/\s+Copy\s+\d+$/i', '', $clean);

        return trim($base) !== '' ? trim($base) : $clean;
    }

    /**
     * Generate unique duplicate title with format: {Base Title} Copy N
     */
    public function generateDuplicateTitle(?string $originalTitle): string
    {
        $baseTitle = $this->extractBaseTitle($originalTitle);

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
     * Optional overrides allow specifying target context (e.g. content_for, trainingplan_id) for type = 'content'.
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

            // Apply context overrides for type = content
            if ($assesment->type === 'content') {
                if (isset($overrides['content_for'])) {
                    if ($overrides['content_for'] === 'playlist') {
                        if (empty($overrides['trainingplan_id'])) {
                            throw new \InvalidArgumentException('trainingplan_id is required when duplicating to playlist context.');
                        }
                        $newAssesment->content_for     = 'playlist';
                        $newAssesment->trainingplan_id = (int) $overrides['trainingplan_id'];
                    } elseif ($overrides['content_for'] === 'content') {
                        $newAssesment->content_for     = 'content';
                        $newAssesment->trainingplan_id = null;
                    } else {
                        throw new \InvalidArgumentException("Invalid content_for context: {$overrides['content_for']}. Must be 'content' or 'playlist'.");
                    }
                }
                // Guarantee content_id is preserved exactly as original
                $newAssesment->content_id = $assesment->content_id;
            } elseif (isset($overrides['content_for'])) {
                throw new \InvalidArgumentException('Only content assessments can have content context overrides.');
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
