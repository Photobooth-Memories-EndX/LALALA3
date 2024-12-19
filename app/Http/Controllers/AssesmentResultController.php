<?php

namespace App\Http\Controllers;

use App\Models\Assesment;
use Illuminate\Http\Request;
use App\Models\AssesmentResult;
use App\Models\AssesmentQuestion;
use App\Models\AssesmentQuestionBank;

class AssesmentResultController extends Controller
{
    public function index()
    {
        $result = AssesmentResult::with(['user', 'assesment'])->get();
        return view('main.assesment.result', compact('result'));
    }

    public function result($slug)
    {
        $result = AssesmentResult::with('user')->whereHas('assesment', function ($query) use ($slug) {
            $query->where('slug', $slug)->whereFinal(1);
        })->get();
        return view('main.assesment.result', compact('result'));
    }

    public function wrong($slug, $id)
    {
        $assesment = Assesment::findBySlug($slug);

        if ($assesment === null) {
            abort(404);
        }
        // $assesment_question = AssesmentQuestion::where('assesment_id', $assesment->id)->pluck('answer', 'id')->toArray();

        $assesment_result = AssesmentResult::select('user_answer')->where('id', $id)->first();
        $user_answers = $assesment_result->user_answer;

        $amountOfAnswer = '[0/0]';
        $checkAssesmentQuestionBanks = AssesmentQuestionBank::where('assesment_id', $assesment->id)->first();

        if ($checkAssesmentQuestionBanks) {
            $assessmentQuestionBanks = AssesmentQuestionBank::where('assesment_id', $assesment->id)->get();
            $sumOfquestionsAmount    = $assessmentQuestionBanks->sum('questions_amount');
            
            $amountOfAnswer = '[' . count($user_answers) . '/' . $sumOfquestionsAmount . ']';
        } else {
            $amountOfAnswer = '[' . count($user_answers) . '/' . count($assesment->questions) . ']';
        }

        // $wrong_answer = array_diff_assoc($assesment_question, $user_answers);

        $format_answer = [];
        // ksort($wrong_answer);
        foreach($user_answers as $key => $value){
            $data = AssesmentQuestion::findOrFail($key);
            array_push($format_answer, ['question' => $data->question, 'answer' => $data->answer, 'user_answer' => ($user_answers[$key]) ?? '<i>Empty Answer</i>']);
        }

        return response()->json([
            'status'         => 200,
            'amountOfAnswer' => $amountOfAnswer,
            'wrong_answer'   => $format_answer,
        ]);
    }
}
