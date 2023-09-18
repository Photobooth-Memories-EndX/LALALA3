<?php

namespace App\Http\Controllers;

use App\Models\Assesment;
use App\Models\Classification;
use App\Models\Content;
use Illuminate\Http\Request;

class ContentUserController extends Controller
{
    public function view($slug)
    {
        $content = Content::findBySlug($slug);
        // $content->views += 1;
        // $content->save();

        $assesment = Assesment::where('type', 'content')->where('content_id', $content->id)->first();
        return view('user.content.view', compact('content', 'assesment'));
    }

    public function category($slug)
    {
        $category = Classification::findBySlug($slug);
        $content = Content::where('classification_id', $category->id)->get();
        return view('user.content.category', compact('content', 'category'));
    }
}
