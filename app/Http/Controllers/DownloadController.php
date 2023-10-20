<?php

namespace App\Http\Controllers;

use App\Models\ContentUser;
use Illuminate\Http\Request;
use App\Exports\LearningExport;
use Maatwebsite\Excel\Facades\Excel;

class DownloadController extends Controller
{
    //
    public function learningdownload(Request $request)
    {
        $type = request('type');
        $status = request('status');
        $year = request('year');
        $slug = request('slug');
        return Excel::download(new LearningExport($type, $status, $year, $slug), now() . '_learning.xlsx');
        // $content = ContentUser::with(['user', 'content', 'content.classification'])->get();
        // return view('main.report.learningdownload', [
        //     // "title" =>  $title,
        //     "downloadslug" => $slug,
        //     "content" => $content
        // ]);
    }
}
