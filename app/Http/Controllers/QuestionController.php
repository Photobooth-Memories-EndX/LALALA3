<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\QuestionBank;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function create($question_bank_slug)
    {
        $questionBank = QuestionBank::findBySlug($question_bank_slug);
        $questions    = Question::latest()->get();

        return view('main.question_bank.questions.create', compact('questionBank', 'questions'));
    }

    public function store(Request $request, $question_bank_id)
    {
        $request->validate([
            'question' => 'required',
            'answer'   => 'required',
            'option'   => 'required',
            'image'    => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $questionBank = QuestionBank::findOrFail($question_bank_id);

        $question = new Question;
        $question->question_bank_id = $questionBank->id;
        $question->question         = $request->input('question');
        $question->option           = $request->input('option');
        $question->answer           = $request->input('answer');
        if ($request->hasfile('image')) {
            $file      = $request->file('image');
            $extention = $file->getClientOriginalExtension();
            $filename  = 'id_' . $question_bank_id . '_' . time() . '.' . $extention;
            $file->move('storage/question_bank/image/', $filename);

            $question->question_image = $filename;
        }
        $question->save();

        return redirect()->route('question.bank.create.question', $questionBank->slug)->with('success', 'Question has already been created successfully.');
    }
}
