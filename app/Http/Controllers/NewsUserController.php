<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;

class NewsUserController extends Controller
{
    public function index()
    {
        $news = News::latest()->paginate(3);
        return view('user.news.index', compact('news'));
    }

    public function view($slug)
    {
        $news = News::findBySlug($slug);
        $news->views += 1;
        $news->save();
        return view('user.news.view', compact('news'));
    }
}
