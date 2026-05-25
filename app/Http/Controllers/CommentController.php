<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Content;
use App\Models\EventPhoto;
use App\Models\News;
use Illuminate\Http\Request;
use App\Models\CommentLike;

class CommentController extends Controller
{
    public function storecontent(Request $request, $slug)
    {
        $request->validate([
            'description' => "required|string|max:1000",
            'parent_id' => 'nullable|integer|exists:comments,id',
        ]);

        $content = Content::findBySlug($slug);

        $comment = new Comment();
        $comment->content_id  = $content->id;
        $comment->type        = 'content';
        $comment->user_id     = auth()->user()->id;
        $comment->parent_id   = $request->parent_id;
        $comment->description = $request->input('description');
        $comment->save();

        return to_route('content.user.view', $slug)->with('success', 'Comment posted successfully.')->withFragment('comment');
    }

    public function storecontentplaylist(Request $request, $slug)
    {
        $request->validate([
            'description' => "required|string|max:1000",
            'parent_id' => 'nullable|integer|exists:comments,id',
        ]);

        $content = Content::findBySlug($slug);

        $comment = new Comment();
        $comment->content_id  = $content->id;
        $comment->type        = 'content';
        $comment->user_id     = auth()->user()->id;
        $comment->parent_id   = $request->parent_id;
        $comment->description = $request->input('description');
        $comment->save();

        return to_route('playlist.user.view', [$request->playlistslug, $slug])->with('success', 'Comment posted successfully.')->withFragment('comment');
    }

    public function storenews(Request $request, $slug)
    {
        $request->validate([
            'description' => "required|string|max:1000",
            'parent_id' => 'nullable|integer|exists:comments,id',
        ]);

        $news = News::findBySlug($slug);

        $comment = new Comment();
        $comment->news_id     = $news->id;
        $comment->type        = 'news';
        $comment->user_id     = auth()->user()->id;
        $comment->parent_id   = $request->parent_id;
        $comment->description = $request->input('description');
        $comment->save();

        return to_route('news.user.view', $slug)->with('success', 'Comment posted successfully.')->withFragment('comment');
    }

    public function storeeventphoto(Request $request, $slug)
    {
        $request->validate([
            'description' => "required|string|max:1000",
            'parent_id' => 'nullable|integer|exists:comments,id',
        ]);

        $eventphoto = EventPhoto::findBySlug($slug);
        $comment = new Comment();
        $comment->eventphoto_id = $eventphoto->id;
        $comment->type          = 'eventphoto';
        $comment->user_id       = auth()->user()->id;
        $comment->parent_id     = $request->parent_id;
        $comment->description   = $request->input('description');
        $comment->save();

        return to_route('gallery.user.view', $slug)->with('success', 'Comment posted successfully.')->withFragment('comment');
    }

    public function toggleLike($id)
    {
        $comment = Comment::findOrFail($id);

        $like = CommentLike::query()
            ->where('comment_id', $comment->id)
            ->where('user_id', auth()->id())
            ->first();

        if ($like) {
            $like->delete();

            return back()
                ->with('success', 'Like removed.')
                ->withFragment('comment');
        }

        CommentLike::create([
            'comment_id' => $comment->id,
            'user_id' => auth()->id(),
        ]);

        return back()
            ->with('success', 'Comment liked.')
            ->withFragment('comment');
    }
}
