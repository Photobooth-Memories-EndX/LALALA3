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

        if ($content && $content->isPublished()) {
            return response()->json([
                'error' => 'Cannot delete media while content is published. Please unpublish the content first.',
                'status' => 422
            ], 422);
        }

        if ($content) {
            $_cf = json_decode($content->file ?? '[]', true) ?: [];
            $_fnList = is_array($_cf) ? $_cf : ($content->file ? [$content->file] : []);
            foreach ($_fnList as $_fn) {
                if (!empty($_fn)) {
                    $src = public_path('storage/files/content/' . $content->id . '/' . basename($_fn));
                    if (File::exists($src) && !is_dir($src)) {
                        File::delete($src);
                    }
                }
            }

            $content->file = null;
            $content->save();
        }

        return response()->json(['success' => 'Content deleted successfully', 'status' => 200]);
    }

    public function deletethumbajax($slug)
    {
        $content = Content::findBySlug($slug);

        if ($content && $content->isPublished()) {
            return response()->json([
                'error' => 'Cannot delete thumbnail while content is published. Please unpublish the content first.',
                'status' => 422
            ], 422);
        }

        if ($content) {
            $src = 'storage/files/content/' . $content->id . '/thumb.jpg';
            if (File::exists(public_path($src)) && !is_dir(public_path($src))) {
                File::delete(public_path($src));
            }
        }

        return response()->json(['success' => 'Thumbnail deleted successfully', 'status' => 200]);
    }
}
