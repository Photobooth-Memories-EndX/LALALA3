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
        // dd($request->all());
        dd($request->except(['_token', '_method']));

        // $assesment_result = new AssesmentResult();
        // $assesment_result->user_id = auth()->user()->id;
        // $assesment_result->assesment_id = $assesment->id;
        // $assesment_result->user_answer = $request->except(['_token', '_method']);
        // $assesment_result->save();
    }
}
