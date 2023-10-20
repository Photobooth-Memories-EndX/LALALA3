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
        $value = request('s');
        // return $value;
        $content = Content::where('title', 'LIKE', "%$value%")->get();
        $news = News::where('title', 'LIKE', "%$value%")->get();
        $eventphoto = EventPhoto::where('title', 'LIKE', "%$value%")->get();
        // $playlist = TrainingPlan::where('title', 'LIKE', "%$value%")->get();

        $playlist = TrainingPlan::where('title', 'LIKE', "%$value%")->with([
            'trainingplandetail', 'trainingplandetail.content'
        ])->get();
        foreach ($content as $key => $value) {
            $value->searchtype = "content";
            $value->searchlabel = "Content";
        }
        foreach ($news as $key => $value) {
            $value->searchtype = "news";
            $value->searchlabel = "NEWS";
        }
        foreach ($eventphoto as $key => $value) {
            $value->searchtype = "eventphoto";
            $value->searchlabel = "Gallery";
        }
        foreach ($playlist as $key => $value) {
            $value->searchtype = "playlist";
            $value->searchlabel = "Playlist";
        }
        $result = collect(); //Create empty collection which we know has the merge() method
        if ($searchtype == 'content') {
            $result = $content;
        } else if ($searchtype == 'news') {
            $result = $news;
        } else if ($searchtype == 'eventphoto') {
            $result = $eventphoto;
        } else if ($searchtype == 'playlist') {
            $result = $playlist;
        } else if ($searchtype == 'all') {
            $result = $result->concat($content);
            $result = $result->concat($news);
            $result = $result->concat($eventphoto)->sortByDesc('created_at');
            $result = $result->concat($playlist)->sortByDesc('created_at');
        }
        // return $result;
        return view('user.search.index', [
            "result" => $result,
            "totalcontent" => $content->count(),
            "totalnews" => $news->count(),
            "totaleventphoto" => $eventphoto->count(),
            "totalplaylist" => $playlist->count()
        ]);
    }
}
