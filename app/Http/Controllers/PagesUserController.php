<?php

namespace App\Http\Controllers;

use App\Models\Page;

class PagesUserController extends Controller
{

    public function view($slug)
    {
        $page = Page::findBySlug($slug);
        $page->views += 1;
        $page->save();
        return view('user.page.view', compact('page'));
    }
}
