<?php

namespace App\Http\Controllers;

use App\Models\Assesment;
use Illuminate\Http\Request;
use App\Models\AssesmentQuestion;
use App\Models\AssesmentResult;

class AssesmentUserController extends Controller
{
    public function index()
    {
        $assesment = Assesment::latest()->paginate(15);
        return view('user.assesment.index', compact('assesment'));
    }

    public function view($slug)
    {
        $assesment = Assesment::findBySlug($slug);
        $assesment_question = AssesmentQuestion::where('assesment_id', $assesment->id)->inRandomOrder()->get();
        return view('user.assesment.view', compact('assesment', 'assesment_question'));
    }

    public function store(Request $request, $slug)
    {
        $assesment = Assesment::findBySlug($slug);
        $assesment_question = AssesmentQuestion::where('assesment_id', $assesment->id)->pluck('answer', 'id')->toArray();
        $assesment_question_count = count($assesment_question);
        // dd($assesment_question);

        $user_answers = $request->collect()->except(['_token', '_method'])->sortKeys()->toArray();
        // dd(array_diff($assesment_question, $user_answers));

        $assesment_result = new AssesmentResult();
        $assesment_result->user_id = auth()->user()->id;
        $assesment_result->assesment_id = $assesment->id;
        $assesment_result->user_answer = $request->except(['_token', '_method']);

        //process score
        $wrong_answer = array_diff($assesment_question, $user_answers);
        $wrong_answer_count = count($wrong_answer);
        $right_answer_count = $assesment_question_count - $wrong_answer_count;
        $score = ((int)$right_answer_count / (int)$assesment_question_count) * 100;
        dd($score);
        //process score

        $assesment_result->score = $score;
        $assesment_result->save();
    }
}
