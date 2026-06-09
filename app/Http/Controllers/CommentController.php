<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Content;
use App\Models\EventPhoto;
use App\Models\News;
use Illuminate\Http\Request;
use App\Models\CommentLike;
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
        $comment->parent_id   = $request->parent_id;
        $comment->description = $request->input('description');
        $comment->save();

        return to_route('content.user.view', $slug)->with('success', 'Comment posted successfully.')->withFragment('comment');
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
        $comment->parent_id   = $request->parent_id;
        $comment->description = $request->input('description');
        $comment->save();

        return to_route('playlist.user.view', [$request->playlistslug, $slug])->with('success', 'Comment posted successfully.')->withFragment('comment');
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
        $comment->parent_id   = $request->parent_id;
        $comment->description = $request->input('description');
        $comment->save();

        return to_route('news.user.view', $slug)->with('success', 'Comment posted successfully.')->withFragment('comment');
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

        return to_route('gallery.user.view', $slug)->with('success', 'Comment posted successfully.')->withFragment('comment');
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

    public function toggleLike($id)
    {
        Log::info('CommentController@toggleLike called', ['id' => $id, 'user_id' => auth()->id()]);
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

        $reference = $comment->resolvedReference();

        CommentLike::create([
            'comment_id' => $comment->id,
            'user_id' => auth()->id(),
            'reference_type' => $reference['reference_type'],
            'reference_id' => $reference['reference_id'],
        ]);

        return back()
            ->with('success', 'Comment liked.')
            ->withFragment('comment');
    }
}
