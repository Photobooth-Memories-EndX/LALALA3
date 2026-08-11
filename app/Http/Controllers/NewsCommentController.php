<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\News;
use Illuminate\Http\Request;

class NewsCommentController extends Controller
{
    /**
     * Delete a comment.
     *
     * @param  int|string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $comment = Comment::findOrFail($id);
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $userRole = (int) ($user->role_ ?? $user->role ?? 0);

        // Izinkan jika user adalah pemilik komentar, Admin (2), atau Super Admin (3)
        if ((int) $user->id !== (int) $comment->user_id && !in_array($userRole, [2, 3], true)) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $comment->delete();

        $totalComments = 0;
        if (!empty($comment->news_id)) {
            $news = News::find($comment->news_id);
            if ($news && method_exists($news, 'allComments')) {
                $totalComments = $news->allComments()->count();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Comment deleted successfully.',
            'total_comments' => $totalComments,
        ]);
    }
}
