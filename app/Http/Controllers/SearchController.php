<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\Content;
use App\Models\EventPhoto;
use App\Models\TrainingPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function index($searchtype)
    {
        $keyword = request('s', '');

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

        $result = collect();
        if ($searchtype === 'content') {
            $result = $content;
        } elseif ($searchtype === 'news') {
            $result = $news;
        } elseif ($searchtype === 'eventphoto') {
            $result = $eventphoto;
        } elseif ($searchtype === 'playlist') {
            $result = $playlist;
        } elseif ($searchtype === 'all') {
            $result = $result
                ->concat($content)
                ->concat($news)
                ->concat($eventphoto)
                ->concat($playlist)
                ->sortByDesc('created_at');
        }

        return view('user.search.index', [
            'result'          => $result,
            'keyword'         => $keyword,
            'searchtype'      => $searchtype,
            'totalcontent'    => $content->count(),
            'totalnews'       => $news->count(),
            'totaleventphoto' => $eventphoto->count(),
            'totalplaylist'   => $playlist->count(),
        ]);
    }
}
