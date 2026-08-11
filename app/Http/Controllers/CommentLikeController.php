<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\CommentLike;
use Illuminate\Http\Request;

class CommentLikeController extends Controller
{
    public function toggleLike(Request $request)
    {
        $data = $request->validate([
            'comment_id' => 'required|integer|exists:comments,id',
        ]);

        $comment = Comment::findOrFail($data['comment_id']);
        $reference = $comment->resolvedReference();

        if (!in_array($reference['reference_type'], ['news', 'gallery'], true) || !$reference['reference_id']) {
            return redirect()->back()->withErrors(['comment_id' => 'Likes are only supported for news and gallery comments.']);
        }

        $like = CommentLike::where('comment_id', $comment->id)
            ->where('user_id', auth()->id())
            ->first();

        if ($like) {
            $like->delete();

            return redirect()->back()->with('success', 'Like removed.');
        }

        CommentLike::create([
            'comment_id' => $comment->id,
            'user_id' => auth()->id(),
            'reference_type' => $reference['reference_type'],
            'reference_id' => $reference['reference_id'],
        ]);

        return redirect()->back()->with('success', 'Comment liked.');
    }
}
