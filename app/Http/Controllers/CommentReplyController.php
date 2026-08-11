<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\CommentReply;
use Illuminate\Http\Request;

class CommentReplyController extends Controller
{
    public function storeReply(Request $request)
    {
        $data = $request->validate([
            'comment_id' => 'required|integer|exists:comments,id',
            'reply' => 'required|string|max:2000',
        ]);

        $comment = Comment::findOrFail($data['comment_id']);
        $reference = $comment->resolvedReference();

        if (!in_array($reference['reference_type'], ['content', 'news', 'gallery'], true) || !$reference['reference_id']) {
            abort(422, 'Unable to determine the reference item for this comment.');
        }

        $reply = CommentReply::create([
            'user_id' => auth()->id(),
            'comment_id' => $comment->id,
            'reference_type' => $reference['reference_type'],
            'reference_id' => $reference['reference_id'],
            'reply' => $data['reply'],
        ]);

        return redirect()->back()->with('success', 'Reply posted successfully.')->withFragment('comment');
    }

    public function updateReply(Request $request, CommentReply $reply)
    {
        $request->validate([
            'reply' => 'required|string|max:2000',
        ]);

        if ($reply->user_id !== auth()->id()) {
            abort(403);
        }

        $reply->reply = $request->input('reply');
        $reply->is_edited = true;
        $reply->save();

        return redirect()->back()->with('success', 'Reply updated successfully.');
    }

    public function deleteReply(CommentReply $reply)
    {
        if ($reply->user_id !== auth()->id()) {
            abort(403);
        }

        $reply->delete();

        return redirect()->back()->with('success', 'Reply deleted successfully.');
    }
}
