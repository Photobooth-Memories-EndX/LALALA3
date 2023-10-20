<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ContentUser;
use Illuminate\Http\Request;

class ProfileUserController extends Controller
{
    public function index($hash)
    {
        $user = User::firstWhere('ssohash', $hash);
        /*  return $user;
        return view('main.profile.index', [
            "user" => $user
        ]);
 */
        // $classification = Classification::firstWhere('slug', $slug);
        $user_id = $user->id;
        $content = ContentUser::with(['content', 'content.classification'])->whereHas('user', function ($query) use ($user_id) {
            $query->where('id', $user_id);
        })->get();
        return view('main.profile.index', [
            "profile" =>  $user,
            "content" => $content
        ]);
    }
}
