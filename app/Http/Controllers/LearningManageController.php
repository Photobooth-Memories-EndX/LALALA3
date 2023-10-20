<?php

namespace App\Http\Controllers;

use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use Illuminate\Http\Request;

class LearningManageController extends Controller
{
    public function index()
    {

        $ongoing = ContentUser::with('user')->where('finish', '0')->count();
        $finish = ContentUser::with('user')->where('finish', '1')->count();
        $classification = Classification::withCount('content')->with('content.contentuser')->get();
        return view('main.learning.index', [
            "ongoing" => $ongoing,
            "finish" => $finish,
            "classification" => $classification
        ]);
    }
    public function all()
    {
        if (request('status') && request('year')) {
            $requestyear = request('year');
            $requeststatus = request('status');

            if (request('status') == 'all' && request('year') == 'all') {
                $content = ContentUser::with(['user', 'content', 'content.classification'])->get();
            } else if (request('status') == 'all') {
                $content = ContentUser::with(['user', 'content', 'content.classification'])->whereYear('created_at', $requestyear)->get();
            } else if (request('year') == 'all') {
                $content = ContentUser::with(['user', 'content', 'content.classification'])->where('status', $requeststatus)->get();
            } else {
                $content = ContentUser::with(['user', 'content', 'content.classification'])->where('status', $requeststatus)->whereYear('created_at', $requestyear)->get();
            }
        } else {
            $content = ContentUser::with(['user', 'content', 'content.classification'])->get();
        }
        return view('main.learning.all', [
            "title" => 'All Learning',
            "downloadslug" => "",
            "content" => $content
        ]);
    }
    public function category($slug)
    {
        $classification = Classification::firstWhere('slug', $slug);
        $classification_id = $classification->id;
        if (request('status') && request('year')) {
            $requestyear = request('year');
            $requeststatus = request('status');

            if (request('status') == 'all' && request('year') == 'all') {
                $content = ContentUser::with(['user', 'content.classification'])->whereHas('content', function ($query) use ($classification_id) {
                    $query->where('classification_id', $classification_id);
                })->get();
            } else if (request('status') == 'all') {
                $content = ContentUser::with(['user', 'content.classification'])->whereYear('created_at', $requestyear)->whereHas('content', function ($query) use ($classification_id) {
                    $query->where('classification_id', $classification_id);
                })->get();
            } else if (request('year') == 'all') {
                $content = ContentUser::with(['user', 'content.classification'])->where('status', $requeststatus)->whereHas('content', function ($query) use ($classification_id) {
                    $query->where('classification_id', $classification_id);
                })->get();
            } else {
                $content = ContentUser::with(['user', 'content.classification'])->where('status', $requeststatus)->whereYear('created_at', $requestyear)->whereHas('content', function ($query) use ($classification_id) {
                    $query->where('classification_id', $classification_id);
                })->get();
            }
        } else {
            $content = ContentUser::with(['user', 'content.classification'])->whereHas('content', function ($query) use ($classification_id) {
                $query->where('classification_id', $classification_id);
            })->get();
        }

        return view('main.learning.all', [
            "title" =>  'Classification: ' . $classification->classification_name,
            "downloadslug" => $slug,
            "content" => $content
        ]);
    }
    public function content($slug)
    {
        if (request('status') && request('year')) {
            $requestyear = request('year');
            $requeststatus = request('status');

            if (request('status') == 'all' && request('year') == 'all') {
                $content = ContentUser::with(['user', 'content.classification'])->whereHas('content', function ($query) use ($slug) {
                    $query->where('slug', $slug);
                })->get();
            } else if (request('status') == 'all') {
                $content = ContentUser::with(['user', 'content.classification'])->whereYear('created_at', $requestyear)->whereHas('content', function ($query) use ($slug) {
                    $query->where('slug', $slug);
                })->get();
            } else if (request('year') == 'all') {
                $content = ContentUser::with(['user', 'content.classification'])->where('status', $requeststatus)->whereHas('content', function ($query) use ($slug) {
                    $query->where('slug', $slug);
                })->get();
            } else {
                $content = ContentUser::with(['user', 'content.classification'])->where('status', $requeststatus)->whereYear('created_at', $requestyear)->whereHas('content', function ($query) use ($slug) {
                    $query->where('slug', $slug);
                })->get();
            }
        } else {
            $content = ContentUser::with(['user', 'content.classification'])->whereHas('content', function ($query) use ($slug) {
                $query->where('slug', $slug);
            })->get();
        }
        if (!$content->isEmpty()) {
            $title = $content[0]->content->title;
        } else {
            $title = $slug;
        }
        return view('main.learning.all', [
            "title" =>  $title,
            "downloadslug" => $slug,
            "content" => $content
        ]);
    }
}
