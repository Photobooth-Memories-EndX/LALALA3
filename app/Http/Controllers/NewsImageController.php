<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;

class NewsImageController extends Controller
{
    public function deleteImage($slug)
    {
        // $news = News::select('id', 'image')->where('slug', $slug)->first();
        $news = News::findBySlug($slug);

        $src = 'storage/news/'.$news->image;
        if(\File::exists(public_path($src)) && !is_dir($src))
        {
            \File::delete(public_path($src));
        }

        $news->image = '';
        $news->save();

        return response()->json(['success'=>'Image news deleted successfully', 'status'=>200]);

    }
}
