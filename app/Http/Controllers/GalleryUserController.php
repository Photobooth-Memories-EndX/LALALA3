<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\EventPhoto;
use Illuminate\Http\Request;

class GalleryUserController extends Controller
{
    public function index()
    {
        $eventphoto = EventPhoto::withCount('comments')
            ->where('active', 'Active')
            ->latest()
            ->get();

        $totalEvents = $eventphoto->count();

        $totalPhotos = 0;

        foreach ($eventphoto as $item) {

            if ($item->photos) {

                $photos = json_decode($item->photos, true);

                if ($photos) {
                    $totalPhotos += count($photos);
                }
            }
        }

        return view('user.gallery.index', [
            'eventphoto' => $eventphoto,
            'totalEvents' => $totalEvents,
            'totalPhotos' => $totalPhotos,
        ]);
    }

    public function view($slug)
    {
        $eventphoto = EventPhoto::where('slug', $slug)->firstOrFail();

        $eventphoto->views = ($eventphoto->views ?? 0) + 1;
        $eventphoto->save();

        $photoCount = 0;

        if ($eventphoto->photos) {

            $photos = json_decode($eventphoto->photos, true);

            if ($photos) {
                $photoCount = count($photos);
            }
        }

        $comment = Comment::with('user')
            ->where('parent_id', null)
            ->where('type', 'eventphoto')
            ->where('eventphoto_id', $eventphoto->id)
            ->latest()
            ->paginate(10);

     return view(
    'user.gallery.view',
    [
        'eventphoto' => $eventphoto,
        'comment' => $comment,
        'photoCount' => $photoCount
    ]
);
    }
}