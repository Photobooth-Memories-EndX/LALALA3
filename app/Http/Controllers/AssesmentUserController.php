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
        // if ($assesment === null or $assesment->status == "NOT") {
        //     abort(404);
        // }

        $today = date("Y-m-d H:i:s");



        if (!auth()->user()->isAdmin())
        {
            if ($assesment === null or $assesment->status == "NOT") {
                abort(404);
            }

            if(!($today >= $assesment->fromdate and $today <= $assesment->todate)){
                abort(404);
            }

            $assesment_result = AssesmentResult::where('user_id', auth()->user()->id)->where('assesment_id', $assesment->id)->where('batch', $assesment->batch)->first();
            if($assesment_result ){
                //abort(404);
                return view('user.assesment.done', compact('assesment', 'assesment_result'));
            }

        }

        $assesment_question = AssesmentQuestion::where('assesment_id', $assesment->id)->inRandomOrder()->get();
        return view('user.assesment.view', compact('assesment', 'assesment_question'));
    }

    public function store(Request $request, $slug)
    {
        $assesment = Assesment::findBySlug($slug);
        if ($assesment === null) {
            abort(404);
        }
        $assesment_question = AssesmentQuestion::where('assesment_id', $assesment->id)->pluck('answer', 'id')->toArray();
        $assesment_question_count = count($assesment_question);
        // dd($assesment_question);
        $user_answers = $request->collect()->except(['_token', '_method'])->sortKeys()->toArray();
        // $user_answers = $request->except(['_token', '_method']);
        // dd($user_answers);
        // dd(array_diff_assoc($assesment_question, $user_answers));

        $assesment_result = AssesmentResult::firstOrNew(['user_id' =>  auth()->user()->id, 'assesment_id' =>  $assesment->id, 'batch' =>  $assesment->batch]);
        $assesment_result->user_answer = $request->except(['_token', '_method']);

        //process score
        $wrong_answer = array_diff_assoc($assesment_question, $user_answers);
        $wrong_answer_count = count($wrong_answer);
        $right_answer_count = $assesment_question_count - $wrong_answer_count;
        $score = ((int)$right_answer_count / (int)$assesment_question_count) * 100;
        // dd((int)$score);

        $assesment_result->score = $score;
        $assesment_result->batch = $assesment->batch;
        $assesment_result->pass = ($score < $assesment->passing_grade) ? 0 : 1;

        $assesment_result->save();

        return to_route('assesment.user.result', $slug)->with('score', $score);
    }

    public function result($slug)
    {
        $assesment = Assesment::findBySlug($slug);
        if ($assesment === null) {
            abort(404);
        }
        $assesment_result = AssesmentResult::where('user_id', auth()->user()->id)->where('assesment_id', $assesment->id)->latest()->first();
        // dd($assesment_result );
        return view('user.assesment.result', compact('assesment', 'assesment_result'));
    }
}
