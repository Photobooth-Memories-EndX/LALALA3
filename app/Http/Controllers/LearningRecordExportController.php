<?php

namespace App\Http\Controllers;

use App\Exports\ExportMyAllLearningRecord;
use App\Exports\ExportMyLearningRecord;
use App\Exports\ExportMyOfflineLearningRecord;
use App\Services\Record\OfflineTrainingExcelSchema;
use App\Services\Record\OnlineFullRecordExportService;
use App\Services\Record\RecordAllCombinedService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class LearningRecordExportController extends Controller
{
    public function download(Request $request)
    {
        $source = strtolower(trim((string) $request->input('source', '')));
        if ($source === '') {
            $rawType = strtolower(trim((string) $request->input('type', 'online')));
            $source = match ($rawType) {
                'non_owl', 'offline' => 'offline',
                'all' => 'all',
                default => 'online',
            };
        } elseif ($source === 'non_owl') {
            $source = 'offline';
        } elseif ($source === 'owl') {
            $source = 'online';
        }

        $user = $request->user();
        $rawEmpId = (string) ($user?->employee_id ?: ($user?->nip ?: ($user?->id_emp ?: ($user?->id ?: '0'))));
        $employeeId = preg_replace('/[^A-Za-z0-9_-]/', '', $rawEmpId) ?: '0';

        return match ($source) {
            'offline' => Excel::download(
                new ExportMyOfflineLearningRecord($request->all()),
                OfflineTrainingExcelSchema::generateUserFileName($employeeId)
            ),
            'all' => Excel::download(
                new ExportMyAllLearningRecord($request->all()),
                RecordAllCombinedService::generateUserFileName($employeeId)
            ),
            default => Excel::download(
                new ExportMyLearningRecord($request->all()),
                OnlineFullRecordExportService::generateUserFileName($employeeId)
            ),
        };
    }
}
