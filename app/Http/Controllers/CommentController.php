<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Content;
use App\Models\EventPhoto;
use App\Models\News;
use Illuminate\Http\Request;
use App\Models\CommentLike;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommentController extends Controller
{
    public function storecontent(Request $request, $slug)
    {
        Log::info('CommentController@storecontent called', ['slug' => $slug, 'user_id' => auth()->id(), 'payload' => $request->all()]);
        $request->validate([
            'description' => "required|string|max:1000",
            'parent_id' => 'nullable|integer|exists:comments,id',
        ]);

        $content = Content::findBySlug($slug);

        $comment = new Comment();
        $comment->content_id  = $content->id;
        $comment->type        = 'content';
        $comment->user_id     = auth()->user()->id;
        $comment->parent_id   = $this->resolveRootParentId($request->parent_id);
        $comment->description = $request->input('description');
        $comment->save();

        return $this->createdResponse($request, $comment, 'content.user.view', $slug);
    }

    public function storecontentplaylist(Request $request, $slug)
    {
        Log::info('CommentController@storecontentplaylist called', ['slug' => $slug, 'user_id' => auth()->id(), 'payload' => $request->all()]);
        $request->validate([
            'description' => "required|string|max:1000",
            'parent_id' => 'nullable|integer|exists:comments,id',
        ]);

        $content = Content::findBySlug($slug);

        $comment = new Comment();
        $comment->content_id  = $content->id;
        $comment->type        = 'content';
        $comment->user_id     = auth()->user()->id;
        $comment->parent_id   = $this->resolveRootParentId($request->parent_id);
        $comment->description = $request->input('description');
        $comment->save();

        return $this->createdResponse(
            $request,
            $comment,
            'playlist.user.view',
            [$request->playlistslug, $slug]
        );
    }

    public function storenews(Request $request, $slug)
    {
        Log::info('CommentController@storenews called', ['slug' => $slug, 'user_id' => auth()->id(), 'payload' => $request->all()]);
        $request->validate([
            'description' => "required|string|max:1000",
            'parent_id' => 'nullable|integer|exists:comments,id',
        ]);

        $news = News::findBySlug($slug);

        $comment = new Comment();
        $comment->news_id     = $news->id;
        $comment->type        = 'news';
        $comment->user_id     = auth()->user()->id;
        $comment->parent_id   = $this->resolveRootParentId($request->parent_id);
        $comment->description = $request->input('description');
        $comment->save();

        return $this->createdResponse($request, $comment, 'news.user.view', $slug);
    }

    public function storeeventphoto(Request $request, $slug)
    {
        Log::info('CommentController@storeeventphoto called', ['slug' => $slug, 'user_id' => auth()->id(), 'payload' => $request->all()]);
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

        return $this->createdResponse($request, $comment, 'gallery.user.view', $slug);
    }

    private function createdResponse(Request $request, Comment $comment, string $route, $parameters)
    {
        if ($request->expectsJson()) {
            $totalComments = 0;
            if ($comment->type === 'news' && $comment->news_id) {
                $totalComments = Comment::where('type', 'news')->where('news_id', $comment->news_id)->count();
            } elseif ($comment->type === 'content' && $comment->content_id) {
                $totalComments = Comment::where('type', 'content')->where('content_id', $comment->content_id)->count();
            } elseif ($comment->type === 'eventphoto' && $comment->eventphoto_id) {
                $totalComments = Comment::where('type', 'eventphoto')->where('eventphoto_id', $comment->eventphoto_id)->count();
            }

            return response()->json([
                'message' => 'Comment posted successfully.',
                'comment' => [
                    'id' => $comment->id,
                    'parent_id' => $comment->parent_id,
                    'description' => $comment->description,
                ],
                'total_comments' => $totalComments,
            ], 201);
        }

        return to_route($route, $parameters)
            ->with('success', 'Comment posted successfully.')
            ->withFragment('comment');
    }

    /**
     * Instagram-style threading: replies never nest. If the given parent is
     * itself a reply, walk up to the top-level (root) comment so the new reply
     * is always attached to the main parent comment.
     */
    private function resolveRootParentId($parentId): ?int
    {
        if (empty($parentId)) {
            return null;
        }

        $parent = Comment::find($parentId);

        while ($parent && $parent->parent_id) {
            $parent = Comment::find($parent->parent_id);
        }

        return $parent?->id;
    }

    private function referenceModels(): array
    {
        return [
            'content' => Content::class,
            'news' => News::class,
            'gallery' => EventPhoto::class,
        ];
    }

    private function resolveReference(string $type, int $id)
    {
        $models = $this->referenceModels();

        if (!isset($models[$type])) {
            abort(404, 'Unsupported reference type.');
        }

        $model = $models[$type];

        return $model::findOrFail($id);
    }

    public function storeComment(Request $request)
    {
        Log::info('CommentController@storeComment called', ['user_id' => auth()->id(), 'payload' => $request->all()]);
        $data = $request->validate([
            'reference_type' => 'required|in:content,news,gallery',
            'reference_id' => 'required|integer',
            'description' => 'required|string|max:1000',
            'parent_id' => 'nullable|integer|exists:comments,id',
        ]);

        $this->resolveReference($data['reference_type'], $data['reference_id']);

        if (!empty($data['parent_id'])) {
            $parent = Comment::findOrFail($data['parent_id']);
            $parentReference = $parent->resolvedReference();

            if ($parentReference['reference_type'] !== $data['reference_type'] || $parentReference['reference_id'] !== $data['reference_id']) {
                return back()->withErrors(['parent_id' => 'Parent comment must belong to the same reference item.']);
            }
        }

        $type = $data['reference_type'] === 'gallery' ? 'eventphoto' : $data['reference_type'];

        Comment::create([
            'reference_type' => $data['reference_type'],
            'reference_id' => $data['reference_id'],
            'type' => $type,
            'content_id' => $data['reference_type'] === 'content' ? $data['reference_id'] : null,
            'news_id' => $data['reference_type'] === 'news' ? $data['reference_id'] : null,
            'eventphoto_id' => $data['reference_type'] === 'gallery' ? $data['reference_id'] : null,
            'user_id' => auth()->id(),
            'parent_id' => $data['parent_id'] ?? null,
            'description' => $data['description'],
        ]);

        return back()->with('success', 'Comment posted successfully.')->withFragment('comment');
    }

    public function updateComment(Request $request, Comment $comment)
    {
        if ($comment->user_id !== auth()->id()) {
            abort(403);
        }

        $data = $request->validate([
            'description' => 'required|string|max:1000',
        ]);

        $comment->description = $data['description'];
        $comment->is_edited = true;
        $comment->save();

        return back()->with('success', 'Comment updated successfully.');
    }

    public function deleteComment(Comment $comment)
    {
        if ($comment->user_id !== auth()->id()) {
            abort(403);
        }

        $comment->delete();

        return back()->with('success', 'Comment deleted successfully.');
    }

    public function toggleLike(Request $request, $id)
    {
        Log::info('CommentController@toggleLike called', ['id' => $id, 'user_id' => auth()->id()]);
        $data = $request->validate([
            'liked' => 'nullable|boolean',
        ]);

        $result = DB::transaction(function () use ($id, $data) {
            $comment = Comment::query()->lockForUpdate()->findOrFail($id);
            $like = CommentLike::query()
                ->where('comment_id', $comment->id)
                ->where('user_id', auth()->id())
                ->first();
            $shouldLike = array_key_exists('liked', $data)
                ? (bool) $data['liked']
                : !$like;

            if ($shouldLike && !$like) {
                $reference = $comment->resolvedReference();

                CommentLike::create([
                    'comment_id' => $comment->id,
                    'user_id' => auth()->id(),
                    'reference_type' => $reference['reference_type'],
                    'reference_id' => $reference['reference_id'],
                ]);
            } elseif (!$shouldLike && $like) {
                $like->delete();
            }

            return [
                'comment_id' => $comment->id,
                'liked' => $shouldLike,
                'likes_count' => $comment->likes()->count(),
            ];
        });

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return back()
            ->with('success', $result['liked'] ? 'Comment liked.' : 'Like removed.')
            ->withFragment('comment');
    }

    public function destroy(Request $request, $id)
    {
        try {
            $comment = Comment::find($id);

            if (!$comment) {
                if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Comment already deleted',
                    ], 404);
                }

                return back()->with('error', 'Comment already deleted');
            }

            $user = auth()->user();
            if (!$user) {
                if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                    return response()->json(['message' => 'Unauthorized action.'], 403);
                }
                abort(403, 'Unauthorized action.');
            }

            $isOwner = (int) $user->id === (int) $comment->user_id;
            $isAdmin = (method_exists($user, 'isAdmin') && $user->isAdmin())
                || $user->role === 'admin'
                || (int) $user->role === \App\Models\User::ROLE_ADMIN;

            if (!$isOwner && !$isAdmin) {
                if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                    return response()->json(['message' => 'Unauthorized action.'], 403);
                }
                abort(403, 'Unauthorized action.');
            }

            $comment->delete();

            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Comment deleted successfully.',
                ]);
            }

            return back()->with('success', 'Comment deleted successfully.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comment already deleted',
                ], 404);
            }

            return back()->with('error', 'Comment already deleted');
        } catch (\Exception $e) {
            Log::error('Error deleting comment: ' . $e->getMessage());

            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while deleting the comment.',
                ], 500);
            }

            return back()->with('error', 'An error occurred while deleting the comment.');
        }
    }
}
