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
        $search     = request('search');
        $sort       = request('sort', 'latest');
        $author     = request('author');
        $month      = request('month');
        $year       = request('year');
        $views      = request('views');
        $published  = request('published');
        $sortDate   = request('sort_date');
        $sortTitle  = request('sort_title');
        $dateFrom   = request('date_from');
        $dateTo     = request('date_to');

        $query = News::with('user');

        // Search functionality
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Author filter
        if ($author) {
            $query->where('user_id', $author);
        }

        // Month filter
        if ($month && $month !== 'all') {
            $query->whereMonth('created_at', (int) $month);
        }

        // Year filter
        if ($year && $year !== 'all') {
            $query->whereYear('created_at', (int) $year);
        }

        // Date range filter
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // Published date shortcut
        if ($published && $published !== 'all') {
            match ($published) {
                'today' => $query->whereDate('created_at', \Carbon\Carbon::today()),
                'week'  => $query->whereBetween('created_at', [\Carbon\Carbon::now()->startOfWeek(), \Carbon\Carbon::now()->endOfWeek()]),
                'month' => $query->whereMonth('created_at', \Carbon\Carbon::now()->month)->whereYear('created_at', \Carbon\Carbon::now()->year),
                'year'  => $query->whereYear('created_at', \Carbon\Carbon::now()->year),
                default => null,
            };
        }

        // Views sorting/filter
        if ($views === 'most_viewed' || $sort === 'most_viewed') {
            $query->orderBy('views', 'desc');
        } elseif ($views === 'least_viewed' || $sort === 'least_viewed') {
            $query->orderBy('views', 'asc');
        }

        // Title sorting
        if ($sortTitle === 'az') {
            $query->orderBy('title', 'asc');
        } elseif ($sortTitle === 'za') {
            $query->orderBy('title', 'desc');
        }

        // Date sorting
        if ($sortDate === 'oldest' || $sort === 'oldest') {
            $query->oldest();
        } elseif ($sortDate === 'newest' || $sort === 'newest' || ($sort === 'latest' && !$views && !$sortTitle)) {
            $query->latest();
        }

        $news = $query->paginate(9)->withQueryString();
        $newsCount = $news->total();

        // Get all authors for filter dropdown
        $authors = User::whereHas('news')->select('id', 'name')->distinct()->get();

        // Get trending news
        $trending = News::with('user')
            ->orderBy('views', 'desc')
            ->limit(5)
            ->get();

        return view('user.news.index', compact('news', 'newsCount', 'authors', 'trending'));
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

        // Get comments with replies (paginated root comments + one reply level)
        $comment = Comment::with([
                'user',
                'replies.user',
                'replies.likes',
                'replies.likedByUser',
                'likes',
                'likedByUser',
            ])
            ->withCount('replies')
            ->whereNull('parent_id')
            ->where('type', 'news')
            ->where('news_id', $news->id)
            ->latest()
            ->paginate(5)
            ->withQueryString();
        // Get related articles (continue exploring)
        $related = News::with('user')
            ->where('id', '!=', $news->id)
            ->latest()
            ->limit(3)
            ->get();

        // Calculate read time
        $wordCount = str_word_count(strip_tags($news->description));
        $readTime = ceil($wordCount / 200); // 200 words per minute

        return view('user.news.view', compact('news', 'comment', 'related', 'readTime'));
    }
}
