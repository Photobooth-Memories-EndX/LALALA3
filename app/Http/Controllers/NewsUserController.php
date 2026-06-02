<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Http\Request;

class NewsUserController extends Controller
{
    public function index()
    {
        $search = request('search');
        $sort = request('sort', 'latest');
        $author = request('author');

        $query = News::with('user');

        // Search functionality
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Author filter
        if ($author) {
            $query->where('user_id', $author);
        }

        // Sorting
        $query = match ($sort) {
            'oldest' => $query->oldest(),
            'most_viewed' => $query->orderBy('views', 'desc'),
            'least_viewed' => $query->orderBy('views', 'asc'),
            default => $query->latest(),
        };

        $news = $query->paginate(9)->withQueryString();

        // Get all authors for filter dropdown
        $authors = User::whereHas('news')->select('id', 'name')->distinct()->get();

        // Get trending news
        $trending = News::with('user')
            ->orderBy('views', 'desc')
            ->limit(5)
            ->get();

        return view('user.news.index', compact('news', 'authors', 'trending'));
    }

    public function view($slug)
    {
        // Use a safe query to find by slug when eager loading relations.
        // Calling findBySlug() directly on the query builder may fail,
        // so we filter by the slug column instead.
        $news = News::with('user')
            ->where('slug', $slug)
            ->first();

        if ($news === null) {
            abort(404);
        }

        // Increment views (atomic-ish)
        $news->increment('views');

        // Get comments with replies
        $comments = Comment::with('user')
            ->where('parent_id', null)
            ->where('type', 'news')
            ->where('news_id', $news->id)
            ->latest()
            ->get();

        // Get related articles (continue exploring)
        $related = News::with('user')
            ->where('id', '!=', $news->id)
            ->latest()
            ->limit(3)
            ->get();

        // Calculate read time
        $wordCount = str_word_count(strip_tags($news->description));
        $readTime = ceil($wordCount / 200); // 200 words per minute

        return view('user.news.view', compact('news', 'comments', 'related', 'readTime'));
    }
}
