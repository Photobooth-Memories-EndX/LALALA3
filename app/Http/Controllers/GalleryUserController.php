<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\EventPhoto;
use Illuminate\Http\Request;

class GalleryUserController extends Controller
{
    public function index(Request $request)
    {
        $q        = $request->input('q');
        $uploader = $request->input('uploader');
        $sort     = $request->input('sort', 'latest');
        $category = $request->input('category');

        $query = EventPhoto::withCount('comments')
            ->where('active', 'Active');

        if ($q) {
            $query->where('title', 'like', "%{$q}%");
        }

        if ($uploader) {
            $query->where('uploader', $uploader);
        }

        if ($category) {
            $query->where('category', $category);
        }

        switch ($sort) {
            case 'oldest':
                $query->oldest();
                break;
            case 'az':
                $query->orderBy('title', 'asc');
                break;
            case 'za':
                $query->orderBy('title', 'desc');
                break;
            default:
                $query->latest();
        }

        $eventphoto = $query->paginate(9)->withQueryString();

        $totalEvents = EventPhoto::where('active', 'Active')->count();

        $totalPhotos = 0;
        EventPhoto::where('active', 'Active')
            ->pluck('photos')
            ->each(function ($p) use (&$totalPhotos) {
                if ($p) {
                    $arr = json_decode($p, true);
                    if (is_array($arr)) {
                        $totalPhotos += count($arr);
                    }
                }
            });

        $uploaders = EventPhoto::where('active', 'Active')
            ->whereNotNull('uploader')
            ->where('uploader', '!=', '')
            ->distinct()
            ->pluck('uploader');

        $categories = EventPhoto::where('active', 'Active')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category');

        return view('user.gallery.index', [
            'eventphoto'  => $eventphoto,
            'totalEvents' => $totalEvents,
            'totalPhotos' => $totalPhotos,
            'uploaders'   => $uploaders,
            'categories'  => $categories,
        ]);
    }

    public function view($slug)
    {
        $eventphoto = EventPhoto::where('slug', $slug)->firstOrFail();

        $eventphoto->views = ($eventphoto->views ?? 0) + 1;
        $eventphoto->save();

        $photosArr  = $eventphoto->photos ? (json_decode($eventphoto->photos, true) ?? []) : [];
        $photoCount = is_array($photosArr) ? count($photosArr) : 0;

        $comment = Comment::with([
                'user',
                'replies.user',
                'replies.likes',
                'replies.likedByUser',
                'likes',
                'likedByUser',
            ])
            ->withCount('replies')
            ->whereNull('parent_id')
            ->where('type', 'eventphoto')
            ->where('eventphoto_id', $eventphoto->id)
            ->latest()
            ->paginate(10);

        return view('user.gallery.view', [
            'eventphoto' => $eventphoto,
            'comment'    => $comment,
            'photoCount' => $photoCount,
            'photos'     => is_array($photosArr) ? $photosArr : [],
        ]);
    }
}
