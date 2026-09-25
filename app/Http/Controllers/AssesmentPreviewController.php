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
        } else {
            $assesment_question = AssesmentQuestion::where('assesment_id', $assesment->id)->get();
        }

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
}
