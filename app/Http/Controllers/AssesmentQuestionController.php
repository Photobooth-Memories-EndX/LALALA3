<?php

namespace App\Http\Controllers;

use App\Models\Assesment;
use App\Models\AssesmentQuestion;
use Illuminate\Http\Request;

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
}
