<?php

namespace App\Http\Controllers;

use App\Models\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ContentDeleteController extends Controller
{
    //
    public function deletecontentajax($slug)
    {
        // $news = News::select('id', 'image')->where('slug', $slug)->first();
        $content = Content::findBySlug($slug);

        $src = 'storage/files/content/' . $content->id . '/' . $content->slug;
        if (File::exists(public_path($src)) && !is_dir($src)) {
            File::delete(public_path($src));
        }

        $content->file = '';
        $content->save();

        return response()->json(['success' => 'Content deleted successfully', 'status' => 200]);
    }
}
