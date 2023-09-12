<?php

namespace App\Http\Controllers;

use App\Models\AssesmentQuestion;

class AssesmentImageController extends Controller
{
    public function deleteImage($id)
    {
        $assesmentquestion = AssesmentQuestion::findorFail($id);

        $src = 'storage/assesment/'.$assesmentquestion->image;
        if(\File::exists(public_path($src)) && !is_dir($src))
        {
            \File::delete(public_path($src));
        }

        $assesmentquestion->image = '';
        $assesmentquestion->save();

        return response()->json(['success'=>'Image question deleted successfully', 'status'=>200]);

    }
}
