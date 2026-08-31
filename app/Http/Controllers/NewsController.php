<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\User;
use Illuminate\Http\Request;

class NewsController extends Controller
{
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
        $request->validate([

            'title'       => 'required',

            'category'    => 'nullable|string|max:255',

            'description' => 'required',

            'image'       =>
                'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',

        ]);

        $news = new News();

        $news->user_id = auth()->id();

        $news->title = $request->title;

        $news->category = $request->category;

        $news->status = 'approved';

        $news->description = $request->description;

        if ($request->hasFile('image')) {

            $file = $request->file('image');

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
