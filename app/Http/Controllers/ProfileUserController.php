<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ContentUser;
use App\Support\DurationFormatter;
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

        $validContent = $content->filter(function ($item) {
            return !empty($item->content);
        });

    $lastLearning = optional($validContent->sortByDesc('updated_at')->first()?->content)->title ?? '-';

    $favoriteCategory = $validContent
        ->groupBy(function ($item) {
            return $item->content->classification->classification_name ?? '-';
        })
        ->sortByDesc(function ($items) {
            return $items->count();
        })
        ->keys()
        ->first() ?? '-';

    $learningHours = $validContent->sum(function ($item) {
        return DurationFormatter::toHours($item->content->duration ?? null);
    });

    $learningHours = round($learningHours, 1);
        return view('main.profile.index', [
            "profile" =>  $user,
            "content" => $content,
            'lastLearning' => $lastLearning,
            'favoriteCategory' => $favoriteCategory,
            'learningHours' => $learningHours
        ]);
    }

    public function editOutsourcing(User $user)
{
    return view('manage.user.edit_outsourcing', [
        'user' => $user,
    ]);
}
}
