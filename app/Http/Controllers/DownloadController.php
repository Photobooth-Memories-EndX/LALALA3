<?php

namespace App\Http\Controllers;

use App\Models\ContentUser;
use Illuminate\Http\Request;
use App\Exports\LearningExport;
use App\Exports\AssessmentExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AssessmentQuestionExport;

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
    public function assessmentdownload(Request $request)
    {
        return Excel::download(new AssessmentExport($request->assessment_id), now() . '_' . $request->slug . '_assessment.xlsx');
    }
    public function assessmentquestiondownload(Request $request)
    {
        return Excel::download(new AssessmentQuestionExport($request->assessment_id), now() . '_Assesment-Question_' . $request->slug . '.xlsx');
    }
}
