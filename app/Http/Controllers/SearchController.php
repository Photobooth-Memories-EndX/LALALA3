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
        $queryRaw = trim((string) $request->get('q', ''));
        if (empty($queryRaw)) {
            return response()->json([]);
        }

        // Query string normalization (straight vs curly apostrophe)
        $queryNormalized = str_contains($queryRaw, "'")
            ? str_replace("'", "’", $queryRaw)
            : str_replace("’", "'", $queryRaw);

        // Search dari Content (Active)
        $contents = Content::where('active', 'Active')
            ->where(function ($q) use ($queryRaw, $queryNormalized) {
                $q->where('title', 'LIKE', "%{$queryRaw}%")
                  ->orWhere('title', 'LIKE', "%{$queryNormalized}%");
            })
            ->select('id', 'title', 'slug')
            ->limit(15)
            ->get()
            ->map(function ($item) {
                return [
                    'id'    => $item->id,
                    'title' => $item->title,
                    'type'  => 'Content',
                    'url'   => route('content.user.view', $item->slug ?? $item->id),
                ];
            });

        // Search dari Playlist / Training Plan (Active)
        $playlists = TrainingPlan::where('active', 'Active')
            ->where(function ($q) use ($queryRaw, $queryNormalized) {
                $q->where('title', 'LIKE', "%{$queryRaw}%")
                  ->orWhere('title', 'LIKE', "%{$queryNormalized}%");
            })
            ->select('id', 'title', 'slug')
            ->limit(15)
            ->get()
            ->map(function ($item) {
                return [
                    'id'    => $item->id,
                    'title' => $item->title,
                    'type'  => 'Playlist',
                    'url'   => route('user.playlist.show', $item->slug ?? $item->id),
                ];
            });

        // Search dari News
        $news = News::where(function ($q) use ($queryRaw, $queryNormalized) {
                $q->where('title', 'LIKE', "%{$queryRaw}%")
                  ->orWhere('title', 'LIKE', "%{$queryNormalized}%");
            })
            ->select('id', 'title', 'slug')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'id'    => $item->id,
                    'title' => $item->title,
                    'type'  => 'News',
                    'url'   => route('news.user.view', $item->slug ?? $item->id),
                ];
            });

        // Search dari Gallery / EventPhoto
        $galleries = EventPhoto::where(function ($q) use ($queryRaw, $queryNormalized) {
                $q->where('title', 'LIKE', "%{$queryRaw}%")
                  ->orWhere('title', 'LIKE', "%{$queryNormalized}%");
            })
            ->select('id', 'title', 'slug')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'id'    => $item->id,
                    'title' => $item->title,
                    'type'  => 'Gallery',
                    'url'   => route('gallery.user.view', $item->slug ?? $item->id),
                ];
            });

        $results = collect()
            ->concat($contents)
            ->concat($playlists)
            ->concat($news)
            ->concat($galleries)
            ->take(20);

        return response()->json($results->values());
    }

    public function index(Request $request, $searchtype = 'all')
    {
        $keyword = $request->get('s', $request->get('q', ''));

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
