<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\Content;
use App\Models\EventPhoto;
use App\Models\TrainingPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class SearchController extends Controller
{
    public function autocomplete(Request $request)
    {
        $query = trim($request->get('q', ''));
        if (empty($query)) {
            return response()->json([]);
        }

        // Search dari tabel Content (Pembelajaran/Video)
        $contents = Content::where('title', 'LIKE', "{$query}%")
            ->orWhere('title', 'LIKE', "%{$query}%")
            ->select('id', 'title', 'slug', DB::raw("'content' as category"))
            ->limit(5)
            ->get();

        // Search dari tabel News
        $news = News::where('title', 'LIKE', "{$query}%")
            ->orWhere('title', 'LIKE', "%{$query}%")
            ->select('id', 'title', 'slug', DB::raw("'news' as category"))
            ->limit(3)
            ->get();

        $results = $contents->concat($news);

        return response()->json($results);
    }

    public function index(Request $request, $searchtype = 'all')
    {
        $keyword = $request->get('s', '');

        $content    = Content::where('title', 'LIKE', "%{$keyword}%")->get();
        $news       = News::where('title', 'LIKE', "%{$keyword}%")->get();
        $eventphoto = EventPhoto::where('title', 'LIKE', "%{$keyword}%")->get();
        $playlist   = TrainingPlan::where('title', 'LIKE', "%{$keyword}%")
                        ->with(['trainingplandetail', 'trainingplandetail.content'])
                        ->get();

        foreach ($content as $item) {
            $item->searchtype  = 'content';
            $item->searchlabel = 'Content';
        }
        foreach ($news as $item) {
            $item->searchtype  = 'news';
            $item->searchlabel = 'NEWS';
        }
        foreach ($eventphoto as $item) {
            $item->searchtype  = 'eventphoto';
            $item->searchlabel = 'Gallery';
        }
        foreach ($playlist as $item) {
            $item->searchtype  = 'playlist';
            $item->searchlabel = 'Playlist';
        }

        $allResults = collect();
        if ($searchtype === 'content') {
            $allResults = $content->sortByDesc('created_at')->values();
        } elseif ($searchtype === 'news') {
            $allResults = $news->sortByDesc('created_at')->values();
        } elseif ($searchtype === 'eventphoto') {
            $allResults = $eventphoto->sortByDesc('created_at')->values();
        } elseif ($searchtype === 'playlist') {
            $allResults = $playlist->sortByDesc('created_at')->values();
        } else {
            $allResults = $allResults
                ->concat($content)
                ->concat($news)
                ->concat($eventphoto)
                ->concat($playlist)
                ->sortByDesc('created_at')
                ->values();
        }

        $currentPage = Paginator::resolveCurrentPage() ?: 1;
        $perPage = 9;
        $currentItems = $allResults->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $results = new LengthAwarePaginator(
            $currentItems,
            $allResults->count(),
            $perPage,
            $currentPage,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
        $results->appends($request->all());

        return view('user.search.index', [
            'results'         => $results,
            'result'          => $results,
            'keyword'         => $keyword,
            'searchtype'      => $searchtype,
            'totalcontent'    => $content->count(),
            'totalnews'       => $news->count(),
            'totaleventphoto' => $eventphoto->count(),
            'totalplaylist'   => $playlist->count(),
        ]);
    }
}
