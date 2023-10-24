<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function index()
    {
        $user = User::firstWhere('id', auth()->user()->id);
        return view('user.profile.index', [
            "user" => $user
        ]);
    }
    public function update(Request $request)
    {

        $destinationPath = 'storage/profile/';
        $files = $request->file('avatar'); // will get all files
        $file_name = auth()->user()->id . "." . $files->getClientOriginalExtension(); //Get file original name
        $files->move($destinationPath, $file_name); // move files to destination folder
        User::where('id', auth()->user()->id)
            ->update([
                'avatar' => $file_name
            ]);
        return redirect()->route('user.profile')->with('success', 'Profile saved successfully.');
    }
    public function deleteavatar()
    {
        $src = 'storage/profile/' . auth()->user()->avatar;
        User::where('id', auth()->user()->id)
            ->update([
                'avatar' => null
            ]);
        if (File::exists(public_path($src)) && !is_dir($src)) {
            File::delete(public_path($src));
            $status = "200";
        } else {
            $status = "404";
        }
        return redirect()->route('user.profile')->with('success', 'Avatar deleted successfully.');
    }
    public function comment()
    {
        $comment = Comment::with(['content', 'eventphoto', 'news'])->where('user_id', auth()->user()->id)->orderBy('id', 'DESC')->get();
        $commenttype = array('eventphoto' => 'Gallery', 'content' => 'Content', 'news' => 'News');
        // foreach ($comment as $item) {
        //     // echo $item->type;
        //     $comment->push((object)[
        //         'customer_id' => 'aaaa'
        //     ]);
        // }

        // return $commenttype['eventphoto'];
        // $comment->aaaa = "bbbb";
        return view('user.profile.comment', [
            "comment" => $comment,
            "commenttype" => $commenttype
        ]);
    }

    public function changePassword()
    {
        return view('user.profile.change-password');
    }

    public function updatePassword(Request $request)
    {
        # Validation
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|confirmed',
        ]);

        #Match The Old Password
        if(!Hash::check($request->old_password, auth()->user()->password)){
            return back()->with("warning", "Old Password Doesn't match!");
        }

        #Update the new Password
        User::whereId(auth()->user()->id)->update([
            'password' => Hash::make($request->new_password)
        ]);

        return back()->with("success", "Password changed successfully!");
    }
}
