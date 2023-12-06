<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Content;
use App\Models\Assesment;
use App\Models\ContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use Illuminate\Http\Request;

class PlaylistUserController extends Controller
{
    public function index()
    {

        $trainingplan = TrainingPlan::with([
            'trainingplandetail' => function ($query) {
                $query->orderBy('content_order');
            }, 'trainingplandetail.content'
        ])->get();

        return view('user.playlist.index', [
            "title" =>  "Playlist",
            "trainingplan" => $trainingplan
        ]);
    }
    public function view($slug, $vidslug)
    {
        // return $vidslug;
        // $trainingplan = TrainingPlan::findBySlug($slug)->with('trainingplandetail')->get();
        // $trainingplan = TrainingPlan::findBySlug($slug)->with([
        //     'trainingplandetail', 'trainingplandetail.content' => function ($query) use ($vidslug) {
        //         $query->firstWhere('slug', $vidslug);
        //     }
        // ])->get();
        // $trainingplan = TrainingPlan::findBySlug($slug)->with([
        //     'trainingplandetail' => function ($query) {
        //         $query->orderBy('content_order');
        //     }, 'trainingplandetail.content'
        // ])->get();

        $trainingplan = TrainingPlan::findBySlug($slug);
        $content = Content::findBySlug($vidslug);
        $content->views += 1;
        $content->save();

        //add content to user learning on going if not exist yet
        $content_user = ContentUser::firstOrCreate([
            'user_id' => auth()->user()->id, 'content_id' => $content->id
        ]);
        $content_user_all = ContentUser::where('user_id', auth()->user()->id)->get();
        $content_all = TrainingPlanDetail::where('trainingplan_id', $trainingplan->id)->with('content')->get();
        $assesment = Assesment::where('type', 'content')->where('content_id', $content->id)->first();
        $comment = Comment::with('user')->where('type', 'content')->where('content_id', $content->id)->latest()->get();
        // return $content_user_all;
        // dd($trainingplan);

        return view('user.playlist.view', [
            "playlist" => $trainingplan,
            "content" => $content,
            "content_all" => $content_all,
            "content_user_all" => $content_user_all,
            "content_user" => $content_user,
            "assesment" => $assesment,
            "comment" => $comment
        ]);
    }
}
