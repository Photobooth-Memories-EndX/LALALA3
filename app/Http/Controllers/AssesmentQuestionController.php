<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Assesment;
use Illuminate\Http\Request;
use App\Models\AssesmentResult;
use App\Models\AssesmentQuestion;

class AssesmentQuestionController extends Controller
{
    public function question(Assesment $assesment)
    {
        // return response()->json([
        //     'status' => 200,
        //     'assesment' => $assesment,
        // ]);
        $assesment_question = AssesmentQuestion::where('assesment_id', $assesment->id)->get();
        return view('main.assesment.question', compact('assesment', 'assesment_question'));
    }

    public function store(Request $request, Assesment $assesment)
    {
        $request->validate([
            'question' => 'required',
            'answer' => 'required',
            'option' => 'required',
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $assesment_question = new AssesmentQuestion();
        $assesment_question->assesment_id = $assesment->id;
        $assesment_question->question = $request->input('question');
        $assesment_question->option = $request->input('option');
        $assesment_question->answer = $request->input('answer');
        if ($request->hasfile('image')) {
            $file = $request->file('image');
            $extention = $file->getClientOriginalExtension();
            $filename = time() . '.' . $extention;
            $file->move('storage/assesment/', $filename);
            $assesment_question->image = $filename;
        }
        $assesment_question->save();

        return to_route('assesment.question', $assesment->slug)->with('success','Assesment question created successfully.');
    }

    public function destroy(Assesment $assesment, $id)
    {
        $assesment_question = AssesmentQuestion::findOrFail($id);
        $assesment_question->delete();
        return to_route('assesment.question', $assesment->slug)->with('success','Assesment question deleted successfully');
    }

    public function edit(Assesment $assesment, $id)
    {
        $data = AssesmentQuestion::findOrFail($id);
        $assesment_question = AssesmentQuestion::where('assesment_id', $assesment->id)->latest()->get();
        return view('main.assesment.question_edit', compact('assesment', 'assesment_question',  'data'));
    }

    public function update(Request $request, Assesment $assesment, $id)
    {
        $request->validate([
            'question' => 'required',
            'answer' => 'required',
            'option' => 'required',
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $assesment_question = AssesmentQuestion::findOrFail($id);
        $assesment_question->question = $request->input('question');
        $assesment_question->option = $request->input('option');
        $assesment_question->answer = $request->input('answer');
        if ($request->hasfile('image')) {
            $src = 'storage/assesment/' . $assesment_question->image;
            if (\File::exists(public_path($src)) && !is_dir($src)) {
                \File::delete(public_path($src));
            }

            $file = $request->file('image');
            $extention = $file->getClientOriginalExtension();
            $filename = time() . '.' . $extention;
            $file->move('storage/assesment/', $filename);
            $assesment_question->image = $filename;
        }
        $assesment_question->save();

        return to_route('assesment.question', $assesment->slug)->with('success','Assesment question updated successfully.');
    }

    public function result(Assesment $assesment)
    {
        $assesment_result = AssesmentResult::with('user')->where('assesment_id', $assesment->id)->whereFinal(1)->get();
        return view('main.assesment.result', compact('assesment', 'assesment_result'));
    }

    public function clone(Assesment $assesment)
    {
        //dd($assesment->questions);
        $newAssesment = $assesment->replicate();
        $newAssesment->created_at = Carbon::now();

        //add relationship clone
        $newAssesment->push();

        //gathering master question, then clone to new
        foreach($assesment->questions as $question)
        {
            $newAssesment->questions()->create($question->toArray());
        }

        $newAssesment->save();

        return to_route('assesment.index', $assesment->slug)->with('success','Assesment question clone successfully.');
    }
}
