<?php

namespace App\Http\Controllers;

use App\Models\Content;
use Illuminate\Http\Request;

class ContentUserController extends Controller
{
    public function view($slug)
    {
        $content = Content::findBySlug($slug);
        // $content->views += 1;
        // $content->save();
        return view('user.content.view', compact('content'));
    }
}
