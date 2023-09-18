<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\Content;
use Illuminate\Http\Request;

class HomeUserController extends Controller
{
    public function index()
    {
        $top_video = Content::select('id', 'title', 'type', 'slug', 'duration', 'link')->whereIn('type', ['YouTube', 'Video'])->where('active', 'Active')->get();
        $content = Content::select('id', 'title', 'type', 'slug', 'duration', 'link')->where('active', 'Active')->get();
        $news = News::latest()->offset(0)->limit(3)->get();
        return view('homepage', compact('top_video', 'content', 'news'));
    }
}
