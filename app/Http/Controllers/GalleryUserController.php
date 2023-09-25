<?php

namespace App\Http\Controllers;

use App\Models\EventPhoto;
use Illuminate\Http\Request;

class GalleryUserController extends Controller
{
    public function index()
    {
        $eventphoto = EventPhoto::where('active', 'Active')->orderBy('id', 'desc')->get();
        return view('user.gallery.index', [
            "eventphoto" => $eventphoto
        ]);
    }
    public function view($slug)
    {
        $eventphoto = EventPhoto::findBySlug($slug);
        $eventphoto->views += 1;
        $eventphoto->save();
        return view('user.gallery.view', [
            "eventphoto" => $eventphoto
        ]);
    }
}
