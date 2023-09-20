<?php

namespace App\Http\Controllers;

use App\Models\ContentUser;
use Illuminate\Http\Request;

class LearningController extends Controller
{
    public function ongoing()
    {
        $content_user = ContentUser::with('content')->where('user_id', 1)->where('finish', '0')->get();
        return view('user.learning.ongoing', compact('content_user'));
    }

    public function record()
    {
        $content_user = ContentUser::with('content')->where('user_id', 1)->where('finish', '1')->get();
        return view('user.learning.finish', compact('content_user'));
    }
}
