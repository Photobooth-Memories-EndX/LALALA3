<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\Content;
use App\Models\EventPhoto;
use App\Models\TrainingPlan;
use Illuminate\Http\Request;

class SearchController extends Controller
{
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
