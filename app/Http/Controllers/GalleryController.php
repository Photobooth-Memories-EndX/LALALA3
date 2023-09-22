<?php

namespace App\Http\Controllers;

use App\Models\EventPhoto;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    public function index()
    {
        //
        $eventphoto = EventPhoto::all();
        return view('user.gallery.index', compact('eventphoto'));
    }
}
