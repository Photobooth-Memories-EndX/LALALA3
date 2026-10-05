<?php

namespace App\Http\Controllers;

use App\Exports\ExportMyAllLearningRecord;
use App\Exports\ExportMyLearningRecord;
use App\Exports\ExportMyOfflineLearningRecord;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class LearningRecordExportController extends Controller
{
    public function download(Request $request)
    {
        $source = strtolower(trim((string) $request->input('source', 'online')));

        return match ($source) {
            'offline' => Excel::download(
                new ExportMyOfflineLearningRecord($request->all()),
                'MyOfflineTrainingRecord_' . now()->format('Ymd_His') . '.xlsx'
            ),
            'all' => Excel::download(
                new ExportMyAllLearningRecord($request->all()),
                'MyAllLearningRecord_' . now()->format('Ymd_His') . '.xlsx'
            ),
            default => Excel::download(
                new ExportMyLearningRecord($request->all()),
                'MyLearningRecord_' . now()->format('Ymd_His') . '.xlsx'
            ),
        };
    }
}
