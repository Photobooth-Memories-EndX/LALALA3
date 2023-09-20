<?php

namespace App\Http\Controllers;

use App\Models\Assesment;
use App\Models\Classification;
use App\Models\Comment;
use App\Models\Content;
use App\Models\ContentUser;
use Illuminate\Http\Request;

class ContentUserController extends Controller
{
    public function view($slug)
    {
        $content = Content::findBySlug($slug);
        // $content->views += 1;
        // $content->save();

        //add content to user learning on going if not exist yet
        $content_user = ContentUser::firstOrCreate([
            'user_id' => '1', 'content_id' => $content->id
        ]);

        $assesment = Assesment::where('type', 'content')->where('content_id', $content->id)->first();
        $comment = Comment::with('user')->where('type', 'content')->where('content_id', $content->id)->latest()->get();

        return view('user.content.view', compact('content', 'assesment', 'comment'));
    }

    public function category($slug)
    {
        $category = Classification::findBySlug($slug);
        $content = Content::where('classification_id', $category->id)->get();
        return view('user.content.category', compact('content', 'category'));
    }
}
