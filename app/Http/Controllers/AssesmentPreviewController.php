<?php

namespace App\Http\Controllers;

use App\Models\Assesment;
use App\Models\AssesmentQuestion;
use App\Models\AssesmentQuestionBank;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class AssesmentPreviewController extends Controller
{
    /**
     * Display a read-only preview of the assessment.
     * Accessible only by Administrator (Role 2) and Super Admin (Role 3).
     * Guaranteed ZERO database write.
     */
    public function preview($assesment)
    {
        // 1. Strict Role Authorization: Only Role 2 (Administrator) and Role 3 (Super Admin)
        $role = (int) (auth()->user()->role ?? 0);
        if (!in_array($role, [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN], true)) {
            abort(403, 'Anda tidak memiliki hak akses untuk Preview Assessment.');
        }

        // 2. Resolve Assessment instance
        if (!($assesment instanceof Assesment)) {
            $assesment = Assesment::where('slug', $assesment)->orWhere('id', $assesment)->first();
        }

        if (!$assesment) {
            abort(404, 'Assessment not found.');
        }

        // 3. Retrieve Questions (READ-ONLY)
        $assesment_question = $this->getAssesmentQuestions($assesment);

        // 4. Visual-only Timer Simulation (no auto-submit, no database write)
        $countDownDate = null;
        $expiresAtIso  = null;

        $hasAccessPeriod = $assesment->hasAccessPeriod() && !empty($assesment->todate);
        $hasTimeLimit    = $assesment->hasTimeLimit();

        $effectiveDeadline = null;

        if ($hasAccessPeriod && $hasTimeLimit) {
            // Skenario 1: ACCESS PERIOD + TEST DURATION
            // Gunakan deadline yang lebih awal: min(todate, now() + time_limit_minutes)
            $periodDeadline    = \Illuminate\Support\Carbon::parse($assesment->todate);
            $timeLimitDeadline = now()->addMinutes((int) $assesment->time_limit_minutes);

            if (now()->format('Y-m-d H:i:s') > $periodDeadline->format('Y-m-d H:i:s')) {
                // Jika access period sudah expired, gunakan durasi time limit agar admin tetap dapat mencoba preview
                $effectiveDeadline = $timeLimitDeadline;
            } else {
                $effectiveDeadline = $periodDeadline->min($timeLimitDeadline);
            }
        } elseif ($hasTimeLimit) {
            // Skenario 2: TEST DURATION SAJA
            $effectiveDeadline = now()->addMinutes((int) $assesment->time_limit_minutes);
        } elseif ($hasAccessPeriod) {
            // Skenario 3: ACCESS PERIOD SAJA (seperti Global Assessment)
            $effectiveDeadline = \Illuminate\Support\Carbon::parse($assesment->todate);
        }

        if ($effectiveDeadline !== null) {
            $countDownDate = $effectiveDeadline->format('Y-m-d H:i:s');
            $expiresAtIso  = $effectiveDeadline->toIso8601String();
        }

        // 5. Presentation Metadata
        $currentAttempt = 1;
        $maxAttempts = ($assesment->type ?? '') === 'global' ? 1 : (1 + (int) ($assesment->total_remedials ?? 0));
        $assesment_result_draft = null;

        return view('main.assesment.preview', compact(
            'assesment',
            'assesment_question',
            'assesment_result_draft',
            'countDownDate',
            'expiresAtIso',
            'currentAttempt',
            'maxAttempts'
        ));
    }

    /**
     * Compute in-memory score and display preview result.
     * Accessible only by Administrator (Role 2) and Super Admin (Role 3).
     * Guaranteed ZERO database write.
     */
    public function result(Request $request, $assesment)
    {
        // 1. Strict Role Authorization: Only Role 2 (Administrator) and Role 3 (Super Admin)
        $role = (int) (auth()->user()->role ?? 0);
        if (!in_array($role, [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN], true)) {
            abort(403, 'Anda tidak memiliki hak akses untuk Preview Assessment.');
        }

        // 2. Resolve Assessment instance
        if (!($assesment instanceof Assesment)) {
            $assesment = Assesment::where('slug', $assesment)->orWhere('id', $assesment)->first();
        }

        if (!$assesment) {
            abort(404, 'Assessment not found.');
        }

        // 3. Retrieve Legitimate Assessment Questions (READ-ONLY)
        $questions = $this->getAssesmentQuestions($assesment);

        // 4. Extract submitted answers
        // Supports either ['answers' => ['123' => 'A']] or direct form fields ['123' => 'A']
        $submittedAnswers = $request->input('answers');
        if (!is_array($submittedAnswers)) {
            $submittedAnswers = $request->except(['_token', '_method']);
        }

        // 5. In-Memory Pure Calculation & Tamper Protection
        // Only evaluate legitimate questions belonging to this assessment
        $totalQuestions = count($questions);
        $correctCount = 0;
        $incorrectCount = 0;
        $unansweredCount = 0;
        $questionReviews = [];

        foreach ($questions as $index => $question) {
            $qId = (string) $question->id;
            $userAns = null;

            if (array_key_exists($qId, $submittedAnswers)) {
                $userAns = $submittedAnswers[$qId];
            } elseif (array_key_exists($question->id, $submittedAnswers)) {
                $userAns = $submittedAnswers[$question->id];
            }

            $hasAnswered = $userAns !== null && trim((string) $userAns) !== '';
            $userAnsStr = $hasAnswered ? trim((string) $userAns) : null;
            $expectedAnsStr = trim((string) ($question->answer ?? ''));

            if (!$hasAnswered) {
                $status = 'unanswered';
                $isCorrect = false;
                $unansweredCount++;
            } elseif ($userAnsStr === $expectedAnsStr) {
                $status = 'correct';
                $isCorrect = true;
                $correctCount++;
            } else {
                $status = 'incorrect';
                $isCorrect = false;
                $incorrectCount++;
            }

            $questionReviews[] = [
                'number'         => $index + 1,
                'id'             => $question->id,
                'question'       => $question->question,
                'image'          => $question->image,
                'user_answer'    => $userAnsStr,
                'correct_answer' => $expectedAnsStr,
                'is_correct'     => $isCorrect,
                'status'         => $status,
            ];
        }

        // 6. Score Calculation using official normal assessment standard
        $formulaScorePerQuestion = $totalQuestions > 0 ? (100 / $totalQuestions) : 0;
        $rawScore = $correctCount * $formulaScorePerQuestion;
        $score = $rawScore >= 100 ? 100 : round($rawScore, 2);

        $passingGrade = (float) ($assesment->passing_grade ?? 0);
        $isPassed = $score >= $passingGrade;

        $resultData = [
            'total_questions'   => $totalQuestions,
            'correct_count'     => $correctCount,
            'incorrect_count'   => $incorrectCount,
            'unanswered_count'  => $unansweredCount,
            'score'             => $score,
            'passing_grade'     => $passingGrade,
            'is_passed'         => $isPassed,
            'reviews'           => $questionReviews,
        ];

        // 7. Render dedicated Preview Result view (STRICT ZERO DATABASE WRITE)
        return view('main.assesment.preview.result', compact('assesment', 'resultData'));
    }

    /**
     * Retrieve legitimate assessment questions (READ-ONLY).
     */
    protected function getAssesmentQuestions(Assesment $assesment): Collection
    {
        $checkAssesmentQuestionBanks = AssesmentQuestionBank::where('assesment_id', $assesment->id)->first();

        if ($checkAssesmentQuestionBanks) {
            $assessmentQuestionBanks = AssesmentQuestionBank::where('assesment_id', $assesment->id)->get();
            $assesment_question = new Collection();

            foreach ($assessmentQuestionBanks as $aqb) {
                $assessmentQuestions = AssesmentQuestion::where('assesment_id', $assesment->id)
                    ->where('question_bank_id', $aqb->question_bank_id)
                    ->limit($aqb->questions_amount)
                    ->get();

                $assesment_question = $assesment_question->merge($assessmentQuestions);
            }

            return $assesment_question;
        }

        return AssesmentQuestion::where('assesment_id', $assesment->id)->get();
    }
}
