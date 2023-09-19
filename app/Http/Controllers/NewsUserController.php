<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\Comment;
use Illuminate\Http\Request;

class NewsUserController extends Controller
{
    public function index()
    {
        $news = News::latest()->paginate(15);
        return view('user.news.index', compact('news'));
    }

    public function view($slug)
    {
        $news = News::findBySlug($slug);
        $news->views += 1;
        $news->save();
        $comment = Comment::with('user')->where('type', 'news')->where('news_id', $news->id)->latest()->get();
        return view('user.news.view', compact('news', 'comment'));
    }
}
