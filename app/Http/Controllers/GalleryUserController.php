<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\EventPhoto;
use Illuminate\Http\Request;

class GalleryUserController extends Controller
{
    public function index()
    {
        $eventphoto = EventPhoto::withCount('comments')->where('active', 'Active')->orderBy('id', 'desc')->get();

        return view('user.gallery.index', [
            "eventphoto" => $eventphoto
        ]);
    }

    public function view($slug)
    {
        $eventphoto = EventPhoto::where('slug', $slug)->firstOrFail();

        $eventphoto->views = ($eventphoto->views ?? 0) + 1;
        $eventphoto->save();

        $comment = Comment::with('user')
            ->where('parent_id', null)
            ->where('type', 'eventphoto')
            ->where('eventphoto_id', $eventphoto->id)
            ->latest()
            ->paginate(10);

        return view('user.gallery.view', [
            'eventphoto' => $eventphoto,
            'comment' => $comment,
        ]);
    }
}
