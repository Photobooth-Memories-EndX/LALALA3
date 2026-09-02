<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    /**
     * AJAX live autocomplete search for News Management (All News Articles).
     */
    public function autocompleteSearch(Request $request): JsonResponse
    {
        $rawQuery = trim((string) ($request->get('q') ?? $request->get('search') ?? $request->get('s') ?? ''));
        if (empty($rawQuery)) {
            return response()->json([]);
        }

        // Replace straight apostrophe with curly apostrophe and vice versa for flexible matching
        $queryNormalized = str_replace("'", "’", $rawQuery);
        $queryOriginal = str_replace("’", "'", $rawQuery);

        $news = News::with('user')
            ->where(function ($q) use ($rawQuery, $queryNormalized, $queryOriginal) {
                $q->where('title', 'LIKE', "%{$rawQuery}%")
                  ->orWhere('title', 'LIKE', "%{$queryNormalized}%")
                  ->orWhere('title', 'LIKE', "%{$queryOriginal}%")
                  ->orWhere('description', 'LIKE', "%{$rawQuery}%")
                  ->orWhere('slug', 'LIKE', "%{$rawQuery}%")
                  ->orWhere('category', 'LIKE', "%{$rawQuery}%")
                  ->orWhereHas('user', function ($uq) use ($rawQuery) {
                      $uq->where('name', 'LIKE', "%{$rawQuery}%");
                  });
            })
            ->latest('id')
            ->limit(10)
            ->get();

        $results = $news->map(function ($item) {
            return [
                'id'         => $item->id,
                'title'      => $item->title,
                'name'       => $item->title,
                'slug'       => $item->slug,
                'active'     => $item->active ?? $item->status,
                'status'     => $item->status ?? $item->active,
                'category'   => $item->category,
                'author'     => $item->user->name ?? null,
                'created_at' => $item->created_at ? $item->created_at->format('d M Y') : null,
                'views'      => (int) ($item->views ?? 0),
            ];
        });

        return response()->json($results->values());
    }

    public function autocomplete(Request $request): JsonResponse
    {
        return $this->autocompleteSearch($request);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = News::with('user');

        /*
        |--------------------------------------------------------------------------
        | Search News
        |--------------------------------------------------------------------------
        */
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%')
                  ->orWhereHas('user', function ($user) use ($search) {
                      $user->where('name', 'like', '%' . $search . '%');
                  });
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Filter Status (Approved, Pending, Rejected)
        |--------------------------------------------------------------------------
        */
        if ($request->filled('status')) {
            $status = strtolower($request->status);
            if ($status === 'rejected' || $status === 'reject') {
                $query->whereIn('status', ['reject', 'rejected']);
            } else {
                $query->where('status', $status);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Filter Month & Year
        |--------------------------------------------------------------------------
        */
        if ($request->filled('month') && $request->month !== 'all') {
            $query->whereMonth('created_at', (int) $request->month);
        }

        if ($request->filled('year') && $request->year !== 'all') {
            $query->whereYear('created_at', (int) $request->year);
        }

        /*
        |--------------------------------------------------------------------------
        | Filter Author
        |--------------------------------------------------------------------------
        */
        if ($request->filled('author_id')) {
            $query->where('user_id', $request->author_id);
        } elseif ($request->filled('author')) {
            $query->where('user_id', $request->author);
        }

        /*
        |--------------------------------------------------------------------------
        | Filter Category / Topic
        |--------------------------------------------------------------------------
        */
        if ($request->filled('category_id')) {
            $query->where('category', $request->category_id);
        } elseif ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        /*
        |--------------------------------------------------------------------------
        | Filter Date Period
        |--------------------------------------------------------------------------
        */
        $fromDate = $request->input('from_date', $request->input('date_from'));
        $toDate = $request->input('to_date', $request->input('date_to'));

        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        /*
        |--------------------------------------------------------------------------
        | Sorting & Views
        |--------------------------------------------------------------------------
        */
        $views = $request->input('views');
        $sort = $request->input('sort');

        if ($views === 'most' || $views === 'most_viewed' || $sort === 'most_viewed') {
            $query->orderBy('views', 'desc');
        } elseif ($views === 'least' || $views === 'least_viewed' || $sort === 'least_viewed') {
            $query->orderBy('views', 'asc');
        } elseif ($sort === 'oldest') {
            $query->orderBy('created_at', 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */
        $show = $request->input('show', 15);
        $news = $query->paginate($show)->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Statistics & Options
        |--------------------------------------------------------------------------
        */
        $totalNews = News::count();
        $totalAuthor = News::distinct('user_id')->count();

        $authors = User::whereHas('news')
            ->orderBy('name')
            ->get();

        $dbCategories = News::whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category')
            ->toArray();

        $defaultCategories = ['Technology', 'Leadership', 'Announcement', 'General'];
        $mergedCategories = array_unique(array_merge($defaultCategories, $dbCategories));

        $categories = collect($mergedCategories)->map(function ($cat) {
            return (object) [
                'id'   => $cat,
                'name' => $cat,
            ];
        });

        return view(
            'main.news.index',
            compact(
                'news',
                'authors',
                'categories',
                'show',
                'totalNews',
                'totalAuthor'
            )
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('main.news.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $isDraft = $request->input('action') === 'draft' || $request->input('action') === 'save';

        $request->validate([
            'title'       => $isDraft ? 'nullable|string|max:255' : 'required|string|max:255',
            'category'    => 'nullable|string|max:255',
            'description' => $isDraft ? 'nullable' : 'required',
            'image'       => $isDraft ? 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048' : 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $news = new News();
        $news->user_id = auth()->id();
        $news->title = $request->input('title') ?: 'Draft News ' . date('Y-m-d H:i');
        $news->category = $request->category;
        $news->status = 'approved';
        $news->active = $isDraft ? 'Inactive' : 'Active';
        $news->description = $request->input('description') ?? '';

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $extension = $file->getClientOriginalExtension();
            $filename = time() . '.' . $extension;
            $file->move(
                public_path('storage/news'),
                $filename
            );
            $news->image = $filename;
        }

        $news->save();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status'   => 200,
                'message'  => 'News created successfully.',
                'redirect' => route('manage.news.index')
            ]);
        }

        return redirect()
            ->route('manage.news.index')
            ->with(
                'success',
                'News created successfully.'
            );
    }

    /**
     * Display the specified resource.
     */
    public function show(News $news)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(News $news)
    {
        return view(
            'main.news.edit',
            compact('news')
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        Request $request,
        News $news
    )
    {
        $request->validate([

            'title'       => 'required',

            'category'    => 'nullable|string|max:255',

            'status'      => 'required|in:approved,reject,pending',

            'description' => 'required',

            'image'       =>
                'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',

        ]);

        $news->title = $request->title;

        $news->category = $request->category;

        $news->status = $request->status;

        $news->description = $request->description;

        if ($request->input('action') === 'publish') {
            $news->active = 'Active';
        } elseif ($request->input('action') === 'unpublish' || $request->input('action') === 'draft') {
            $news->active = 'Inactive';
        }

        if ($request->hasFile('image')) {

            $src =
                'storage/news/' .
                $news->image;

            if (
                \File::exists(public_path($src))
                &&
                !is_dir(public_path($src))
            ) {

                \File::delete(
                    public_path($src)
                );

            }

            $file =
                $request->file('image');

            $extension =
                $file->getClientOriginalExtension();

            $filename =
                time() . '.' . $extension;

            $file->move(
                public_path('storage/news'),
                $filename
            );

            $news->image = $filename;
        }

        $news->save();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status'   => 200,
                'message'  => 'News saved successfully.',
                'redirect' => route('manage.news.index')
            ]);
        }

        if ($request->input('action') === 'unpublish') {
            return redirect()
                ->route('news.edit', $news->slug)
                ->with('success', 'News article successfully unpublished.');
        }

        return redirect()
            ->route('manage.news.index')
            ->with(
                'success',
                'News updated successfully'
            );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(News $news)
    {
        $src =
            'storage/news/' .
            $news->image;

        if (
            \File::exists(public_path($src))
            &&
            !is_dir(public_path($src))
        ) {

            \File::delete(
                public_path($src)
            );

        }

        $news->delete();

        return redirect()
            ->route('manage.news.index')
            ->with(
                'success',
                'News deleted successfully'
            );
    }
}
