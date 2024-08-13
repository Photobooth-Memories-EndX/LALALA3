<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\AssessmentExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AssessmentQuestionExport;

class DownloadController extends Controller
{
    public function assessmentdownload(Request $request)
    {
        return Excel::download(new AssessmentExport($request->assessment_id), now() . '_' . $request->slug . '_assessment.xlsx');
    }

    public function assessmentquestiondownload(Request $request)
    {
        return Excel::download(new AssessmentQuestionExport($request->assessment_id), now() . '_Assesment-Question_' . $request->slug . '.xlsx');
    }
}
