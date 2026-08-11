<?php

namespace App\Http\Controllers;

use App\Exports\ExportMyLearningRecord;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class LearningRecordExportController extends Controller
{
    public function download(Request $request)
    {
        return Excel::download(new ExportMyLearningRecord($request->all()), 'MyLearningRecord_' . now()->format('Ymd_His') . '.xlsx');
    }
}
