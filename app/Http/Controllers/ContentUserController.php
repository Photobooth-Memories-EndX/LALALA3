<?php

namespace App\Http\Controllers;

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
        return view('user.content.view', compact('content'));
    }

    public function category($slug)
    {
        $category = Classification::findBySlug($slug);
        $content = Content::where('classification_id', $category->id)->whereIn('type', ['Video', 'YouTube'])->get();
        $pdf = Content::where('classification_id', $category->id)->whereIn('type', ['Document'])->get();
        return view('user.content.category', compact('content', 'category', 'pdf'));
    }
}
