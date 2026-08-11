<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

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
        $uploadedAvatarUrl = null;
        $uploadedCoverUrl = null;

        $validator = Validator::make($request->all(), [
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'cover' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors(),
                ], 422);
            }

            return back()->withErrors($validator)->withInput();
        }

        if (!File::exists(public_path($destinationPath))) {
            File::makeDirectory(public_path($destinationPath), 0755, true);
        }

        if ($request->hasFile('avatar')) {
            $files = $request->file('avatar'); // will get all files
            $file_name = auth()->user()->id . "." . $files->getClientOriginalExtension(); //Get file original name
            $files->move($destinationPath, $file_name); // move files to destination folder
            User::where('id', auth()->user()->id)
                ->update([
                    'avatar' => $file_name
                ]);
            $uploadedAvatarUrl = asset($destinationPath . $file_name) . '?v=' . time();
        }

        if ($request->hasFile('cover')) {
            $files = $request->file('cover');
            $oldCovers = glob(public_path($destinationPath . 'cover_' . auth()->user()->id . '.*')) ?: [];

            foreach ($oldCovers as $oldCover) {
                if (File::exists($oldCover) && !is_dir($oldCover)) {
                    File::delete($oldCover);
                }
            }

            $file_name = 'cover_' . auth()->user()->id . "." . $files->getClientOriginalExtension();
            $files->move($destinationPath, $file_name);
            $uploadedCoverUrl = asset($destinationPath . $file_name) . '?v=' . time();
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => 'Profile photo updated successfully.',
                'avatar_url' => $uploadedAvatarUrl,
                'cover_url' => $uploadedCoverUrl,
            ]);
        }

        if ($request->input('source') === 'home') {
            return redirect()->route('home.user.index')->with('success', 'Profile saved successfully.');
        }

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
