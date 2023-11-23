<?php

namespace App\Http\Controllers;

use App\Models\ContentUser;
use Illuminate\Http\Request;
use App\Models\AssesmentResult;

class LearningController extends Controller
{
    public function ongoing()
    {
        $content_user = ContentUser::with('content.classification')->where('user_id', auth()->user()->id)->where('finish', '0')->get();
        return view('user.learning.ongoing', compact('content_user'));
    }

    public function record()
    {
        $content_user = ContentUser::with('content.classification', 'assesment.scores')->where('user_id', auth()->user()->id)->where('finish', '1')->get();
        // dd($content_user->toArray());
        return view('user.learning.finish', compact('content_user'));
    }

    public function assesment()
    {
        $result = AssesmentResult::with(['assesment'])->where('user_id', auth()->user()->id)->whereFinal(1)->get();
        return view('user.learning.result', compact('result'));
    }
}
