<?php

namespace App\Http\Controllers;

use App\Models\Assesment;
use App\Models\AssesmentQuestion;
use App\Models\AssesmentQuestionBank;
use App\Models\Question;
use App\Models\QuestionBank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssesmentQuestionBankController extends Controller
{
    public function storeAssessmentQuestionBank(Request $request)
    {
        $assessment = Assesment::findBySlug($request->assessment_slug);
        if (!$assessment) {
            return response()->json([
                'status'  => 404,
                'message' => 'Assessment not found!',
            ], 404);
        }

        $checkAssessmentQuestionBank = AssesmentQuestionBank::where('assesment_id', $assessment->id)
            ->where('question_bank_id', $request->id_question_bank)->first();

        if ($checkAssessmentQuestionBank) {
            return response()->json([
                'status'  => 422,
                'message' => 'Question Bank already exist!',
            ]);
        }

        $questionBank   = QuestionBank::find($request->id_question_bank);
        if (!$questionBank) {
            return response()->json([
                'status'  => 404,
                'message' => 'Question Bank not found!',
            ], 404);
        }

        $amountQuestion = (int) $request->amount_question;
        $questions      = Question::where('question_bank_id', $questionBank->id)->get();

        DB::transaction(function () use ($assessment, $questionBank, $amountQuestion, $questions) {
            $assessmentQuestionBank = new AssesmentQuestionBank;
            $assessmentQuestionBank->assesment_id     = $assessment->id;
            $assessmentQuestionBank->question_bank_id = $questionBank->id;
            $assessmentQuestionBank->questions_amount = $amountQuestion;
            $assessmentQuestionBank->save();

            $amount = min($amountQuestion, $questions->count());
            $usedQuestionIds = $amount > 0 ? $questions->random($amount)->pluck('id')->flip()->all() : [];

            $now = now();
            $records = [];
            foreach ($questions as $question) {
                $records[] = [
                    'assesment_id'     => $assessment->id,
                    'question_bank_id' => $questionBank->id,
                    'question_id'      => $question->id,
                    'question'         => $question->question,
                    'image'            => $question->question_image,
                    'option'           => is_array($question->option) ? json_encode($question->option) : $question->option,
                    'answer'           => $question->answer,
                    'status'           => 1,
                    'is_used'          => isset($usedQuestionIds[$question->id]) ? 1 : 0,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ];
            }

            foreach (array_chunk($records, 200) as $chunk) {
                AssesmentQuestion::insert($chunk);
            }
        });

        return response()->json([
            'status'  => 200,
            'message' => 'Assessment Question have already added successfully',
        ]);
    }

    public function syncronizeQuestions($id_assessment_question_bank)
    {
        $assessmentQuestionBank = AssesmentQuestionBank::findOrFail($id_assessment_question_bank);
        $questions = Question::where('question_bank_id', $assessmentQuestionBank->question_bank_id)->get();

        DB::transaction(function () use ($assessmentQuestionBank, $questions) {
            foreach ($questions as $question) {
                AssesmentQuestion::updateOrCreate(
                    [
                        'assesment_id'     => $assessmentQuestionBank->assesment_id,
                        'question_bank_id' => $assessmentQuestionBank->question_bank_id,
                        'question_id'      => $question->id,
                    ],
                    [
                        'is_used'  => 0,
                        'question' => $question->question,
                        'image'    => $question->question_image,
                        'option'   => $question->option,
                        'answer'   => $question->answer,
                        'status'   => 1,
                    ]
                );
            }

            $randomIds = AssesmentQuestion::where('assesment_id', $assessmentQuestionBank->assesment_id)
                ->where('question_bank_id', $assessmentQuestionBank->question_bank_id)
                ->inRandomOrder()
                ->limit($assessmentQuestionBank->questions_amount)
                ->pluck('id');

            if ($randomIds->isNotEmpty()) {
                AssesmentQuestion::whereIn('id', $randomIds)->update(['is_used' => 1]);
            }
        });

        return redirect()->back()->with('success-syncronized', $assessmentQuestionBank->question_bank->title . " synchronized successfully.");
    }

    public function edit($id)
    {
        $assessmentQuestionBank = AssesmentQuestionBank::findOrFail($id);

        $data = [
            'question_bank_title' => $assessmentQuestionBank->question_bank->title,
            'total_questions'     => count($assessmentQuestionBank->question_bank->questions),
            'question_per_bank'   => $assessmentQuestionBank->questions_amount,
        ];

        return response()->json([
            'status' => 200,
            'data'   => $data,
        ]);
    }

    public function update($id)
    {
        $assessmentQuestionBank = AssesmentQuestionBank::findOrFail($id);
        $amount = (int) request()->edit_question_per_bank;
        $assessmentQuestionBank->questions_amount = $amount;
        $assessmentQuestionBank->save();

        DB::transaction(function () use ($assessmentQuestionBank, $amount) {
            AssesmentQuestion::where('assesment_id', $assessmentQuestionBank->assesment_id)
                ->where('question_bank_id', $assessmentQuestionBank->question_bank_id)
                ->update(['is_used' => 0]);

            $randomIds = AssesmentQuestion::where('assesment_id', $assessmentQuestionBank->assesment_id)
                ->where('question_bank_id', $assessmentQuestionBank->question_bank_id)
                ->inRandomOrder()
                ->limit($amount)
                ->pluck('id');

            if ($randomIds->isNotEmpty()) {
                AssesmentQuestion::whereIn('id', $randomIds)->update(['is_used' => 1]);
            }
        });

        return redirect()->back()->with('warning', 'Question/Bank of ' . $assessmentQuestionBank->question_bank->title . " updated successfully.");
    }

    public function delete($id)
    {
        $assessmentQuestionBank = AssesmentQuestionBank::findOrFail($id);
        
        DB::transaction(function () use ($assessmentQuestionBank) {
            AssesmentQuestion::where('assesment_id', $assessmentQuestionBank->assesment_id)
                ->where('question_bank_id', $assessmentQuestionBank->question_bank_id)
                ->delete();

            $assessmentQuestionBank->delete();
        });

        return redirect()->back()->with('danger', 'Question Bank ' . $assessmentQuestionBank->question_bank->title . " deleted successfully.");
    }
}
