<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\Content;
use App\Models\EventPhoto;
use Illuminate\Http\Request;

class HomeUserController extends Controller
{
    public function index()
    {
        $top_video = Content::select('id', 'title', 'type', 'slug', 'duration', 'link')->whereIn('type', ['YouTube', 'Video'])->where('active', 'Active')->orderby('views', 'desc')->offset(0)->limit(8)->get();
        $content = Content::select('id', 'title', 'type', 'slug', 'duration', 'link')->where('active', 'Active')->get();
        $news = News::latest()->offset(0)->limit(3)->get();
        $eventphoto = EventPhoto::where('active', 'Active')->latest()->first();
        return view('homepage', compact('top_video', 'content', 'news', 'eventphoto'));
    }
}
