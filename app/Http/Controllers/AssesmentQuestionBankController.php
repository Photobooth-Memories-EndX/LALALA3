<?php

namespace App\Http\Controllers;

use App\Models\Assesment;
use App\Models\AssesmentQuestion;
use App\Models\AssesmentQuestionBank;
use App\Models\Question;
use App\Models\QuestionBank;
use Illuminate\Http\Request;

class AssesmentQuestionBankController extends Controller
{
    public function storeAssessmentQuestionBank(Request $request)
    {
        $checkAssessmentQuestionBank = AssesmentQuestionBank::where('question_bank_id', $request->id_question_bank)->first();

        if ($checkAssessmentQuestionBank) {
            return response()->json([
                'status' => 422,
                'message' => 'Question Bank already exist!',
            ]);
        }

        $assessment     = Assesment::findBySlug($request->assessment_slug);
        $questionBank   = QuestionBank::find($request->id_question_bank);
        $amountQuestion = $request->amount_question;
        $questions      = Question::where('question_bank_id', $questionBank->id)->get();

        $assessmentQuestionBank = new AssesmentQuestionBank;
        $assessmentQuestionBank->assesment_id     = $assessment->id;
        $assessmentQuestionBank->question_bank_id = $questionBank->id;
        $assessmentQuestionBank->questions_amount = $amountQuestion;
        $assessmentQuestionBank->save();

        foreach ($questions as $question) {
            $assesmentQuestion = new AssesmentQuestion;
            $assesmentQuestion->assesment_id     = $assessment->id;
            $assesmentQuestion->question_bank_id = $questionBank->id;
            $assesmentQuestion->question_id      = $question->id;
            $assesmentQuestion->question         = $question->question;
            $assesmentQuestion->image            = $question->question_image;
            $assesmentQuestion->option           = $question->option;
            $assesmentQuestion->answer           = $question->answer;
            $assesmentQuestion->status           = true;
            $assesmentQuestion->save();
        }

        return response()->json([
            'status'  => 200,
            'message' => 'Assessment Question have already added successfully',
        ]);
    }

    public function syncronizeQuestions($id_assessment_question_bank)
    {
        $assessmentQuestionBank = AssesmentQuestionBank::findOrFail($id_assessment_question_bank);
        $questions = Question::where('question_bank_id', $assessmentQuestionBank->question_bank_id)->get();

        foreach ($questions as $question) {
            $assesmentQuestion = AssesmentQuestion::firstOrNew([
                'assesment_id'     => $assessmentQuestionBank->assesment_id,
                'question_bank_id' => $assessmentQuestionBank->question_bank_id,
                'question_id'      => $question->id,
            ]);
            $assesmentQuestion->question = $question->question;
            $assesmentQuestion->image    = $question->question_image;
            $assesmentQuestion->option   = $question->option;
            $assesmentQuestion->answer   = $question->answer;
            $assesmentQuestion->save();
        }

        return redirect()->back()->with('success-syncronized', $assessmentQuestionBank->question_bank->title . " synchronized successfully.");
    }
}
