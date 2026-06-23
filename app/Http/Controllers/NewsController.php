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

                $q->where(
                    'title',
                    'like',
                    '%' . $search . '%'
                )

                ->orWhere(
                    'description',
                    'like',
                    '%' . $search . '%'
                )

                ->orWhereHas(
                    'user',
                    function ($user) use ($search) {

                        $user->where(
                            'name',
                            'like',
                            '%' . $search . '%'
                        );

                    }
                );

            });

        }

        /*
        |--------------------------------------------------------------------------
        | Filter Author
        |--------------------------------------------------------------------------
        */

        if ($request->filled('author')) {

            $query->where(
                'user_id',
                $request->author
            );

        }

        /*
        |--------------------------------------------------------------------------
        | Show Entries
        |--------------------------------------------------------------------------
        */

        $show = $request->show ?? 25;

        $news = $query
            ->latest()
            ->paginate($show)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Statistics
        |--------------------------------------------------------------------------
        */

        $totalNews = News::count();

        $totalAuthor = News::distinct('user_id')->count();

        /*
        |--------------------------------------------------------------------------
        | Author Dropdown
        |--------------------------------------------------------------------------
        */

        $authors = User::whereHas('news')
            ->orderBy('name')
            ->get();

        return view(
            'main.news.index',
            compact(
                'news',
                'authors',
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

            'description' => 'required',

            'image'       =>
                'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',

        ]);

        $news = new News();

        $news->user_id = auth()->id();

        $news->title = $request->title;

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
            ->route('news.index')
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

            'description' => 'required',

            'image'       =>
                'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',

        ]);

        $news->title = $request->title;

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
            ->route('news.index')
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
            ->route('news.index')
            ->with(
                'success',
                'News deleted successfully'
            );
    }
}
