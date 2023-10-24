<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AssesmentResult;

class AssesmentResultController extends Controller
{
    public function index()
    {
        $result = AssesmentResult::with(['user', 'assesment'])->get();


        // return $result;
        return view('main.assesment.result', compact('result'));
    }
    public function result($slug)
    {
        $result = AssesmentResult::with('user')->whereHas('assesment', function ($query) use ($slug) {
            $query->where('slug', $slug)->whereFinal(1);
        })->get();
        // return $result;
        return view('main.assesment.result', compact('result'));
    }
}
