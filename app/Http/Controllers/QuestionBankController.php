<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class QuestionBankController extends Controller
{
    public function index()
    {
        return view('main.question_bank.index');
    }
}
