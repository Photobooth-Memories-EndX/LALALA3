<?php

namespace App\Http\Controllers;

use App\Models\QuestionBank;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class QuestionBankController extends Controller
{
    public function index()
    {
        $questionBanks = QuestionBank::latest()->get();

        return view('main.question_bank.index', compact('questionBanks'));
    }

    public function create() 
    {
        return view('main.question_bank.create');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'title'        => 'required|unique:question_banks,title',
            'created_date' => 'required|date',
        ]);

        $questionBank = new QuestionBank;
        $questionBank->title        = $request->input('title');
        $questionBank->created_date = $request->input('created_date');
        $questionBank->save();

        return redirect()->route('question.bank.index')->with('success',  $request->title . " has already successfully created in the question bank.");
    }

    public function edit($slug)
    {
        $questionBank = QuestionBank::findBySlug($slug);

        return view('main.question_bank.edit', compact('questionBank'));
    }

    public function update(Request $request, $id)
    {
        $questionBank = QuestionBank::findOrFail($id);

        $this->validate($request, [
            'title'        => 'required|unique:question_banks,title,' . $id,
            'created_date' => 'required|date',
        ]);

        $questionBank->title        = $request->title;
        $questionBank->created_date = $request->created_date;
        $questionBank->save();

        return redirect()->route('question.bank.index')->with('warning',  "Question Bank has already successfully updated.");
    }

    public function destroy($id)
    {
        $questionBank = QuestionBank::findOrFail($id);
        $questionBank->delete();

        return redirect()->route('question.bank.index')->with('danger',  $questionBank->title . " has already successfully deleted!");
    }

    public function clone($slug)
    {
        $questionBank = QuestionBank::findBySlug($slug);

        // Clone Question Bank
        $cloneQuestionBank = $questionBank->replicate();
        $cloneQuestionBank->created_date = Carbon::now()->format('Y-m-d');
        $cloneQuestionBank->created_at   = Carbon::now();
        $cloneQuestionBank->push();

        // Add relationship clone
        // Gathering old assessment question, then clone to new
        foreach ($questionBank->questions as $question) {
            $cloneQuestionBank->questions()->create($question->toArray());
        }
        $cloneQuestionBank->save();

        return redirect()->route('question.bank.index')->with('success',  $questionBank->title . " has already successfully cloned/duplicated.");
    }
}
