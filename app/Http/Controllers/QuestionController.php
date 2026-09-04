<?php

namespace App\Http\Controllers;

use App\Exports\QuestionBankQuestionsExport;
use App\Models\Question;
use App\Models\QuestionBank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;

class QuestionController extends Controller
{
    public function create($question_bank_slug)
    {
        $questionBank = QuestionBank::findBySlug($question_bank_slug);
        $questions    = Question::where('question_bank_id', $questionBank->id)->latest()->get();

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

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status'   => 200,
                'message'  => 'Question has already been created successfully.',
                'redirect' => route('question.bank.index')
            ]);
        }

        return redirect()->route('question.bank.create.question', $questionBank->slug)->with('success', 'Question has already been created successfully.');
    }

    public function edit($question_bank_slug, $question_id)
    {
        $questionBank = QuestionBank::findBySlug($question_bank_slug);
        $question     = Question::findOrFail($question_id);
        $questions    = Question::where('question_bank_id', $questionBank->id)->latest()->get();

        return view('main.question_bank.questions.edit', compact('questionBank', 'question', 'questions'));
    }

    public function deleteQuestionImage($question_id) 
    {
        $question = Question::findorFail($question_id);

        $src = 'storage/question_bank/image/' . $question->question_image;
        if(File::exists(public_path($src)) && !is_dir($src))
        {
            File::delete(public_path($src));
        }

        $question->question_image = null;
        $question->save();

        return response()->json([
            'status'  =>200,
            'message' =>'Image question deleted successfully', 
        ]);
    }

    public function update(Request $request, $question_bank_slug, $question_id)
    {
        $request->validate([
            'question' => 'required',
            'answer'   => 'required',
            'option'   => 'required',
            'image'    => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $questionBank = QuestionBank::findBySlug($question_bank_slug);

        $question = Question::findOrFail($question_id);
        $question->question = $request->input('question');
        $question->option   = $request->input('option');
        $question->answer   = $request->input('answer');
        if ($request->hasfile('image')) {
            $src = 'storage/question_bank/image/' . $question->question_image;

            if (File::exists(public_path($src)) && !is_dir($src)) {
                File::delete(public_path($src));
            }

            $file      = $request->file('image');
            $extention = $file->getClientOriginalExtension();
            $filename  = 'id_' . $questionBank->id . '_' . time() . '.' . $extention;
            $file->move('storage/question_bank/image/', $filename);
            $question->question_image = $filename;
        }
        $question->save();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status'   => 200,
                'message'  => 'Question has been updated successfully.',
                'redirect' => route('question.bank.index')
            ]);
        }

        return redirect()->route('question.bank.create.question', $questionBank->slug)->with('warning', "Qustion has already updated successfully.");
    }

    public function destroy($question_bank_slug, $question_id)
    {
        $question = Question::findOrFail($question_id);
        $question->delete();

        return redirect()->route('question.bank.create.question', $question_bank_slug)->with('danger', "The question deleted successfully!");
    }

    public function downloadQuestions($question_bank_id)
    {
        $questionBank = QuestionBank::findOrFail($question_bank_id);

        return Excel::download(new QuestionBankQuestionsExport($questionBank->id), 'Questions of ' . $questionBank->title . '.xlsx');
    }
}
