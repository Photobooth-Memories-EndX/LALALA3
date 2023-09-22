<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Content;
use App\Models\News;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function storecontent(Request $request, $slug)
    {
        $request->validate([
            'description' => "required|string|max:1000"
        ]);

        $content = Content::findBySlug($slug);

        $comment = new Comment();
        $comment->content_id = $content->id;
        $comment->type = 'content';
        $comment->user_id = auth()->user()->id;
        $comment->description = $request->input('description');
        $comment->save();

        return to_route('content.user.view', $slug)->with('success', 'Comment posted successfully.');
    }

    public function storenews(Request $request, $slug)
    {
        $request->validate([
            'description' => "required|string|max:1000"
        ]);

        $news = News::findBySlug($slug);

        $comment = new Comment();
        $comment->news_id = $news->id;
        $comment->type = 'news';
        $comment->user_id = auth()->user()->id;
        $comment->description = $request->input('description');
        $comment->save();

        return to_route('news.user.view', $slug)->with('success', 'Comment posted successfully.');
    }
}
