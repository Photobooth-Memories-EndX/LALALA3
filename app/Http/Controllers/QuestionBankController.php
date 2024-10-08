<?php

namespace App\Http\Controllers;

use App\Models\QuestionBank;
use Illuminate\Http\Request;

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
}
