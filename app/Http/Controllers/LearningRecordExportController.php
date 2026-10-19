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
        $rawEmpId = (string) ($user?->id_emp ?: $user?->id ?: '0');
        $employeeId = preg_replace('/[^A-Za-z0-9_-]/', '', $rawEmpId) ?: '0';
        $dateTime = now()->format('Ymd_His');

        return match ($source) {
            'offline' => Excel::download(
                new ExportMyOfflineLearningRecord($request->all()),
                "MyNonOWLrecord_{$dateTime}_{$employeeId}.xlsx"
            ),
            'all' => Excel::download(
                new ExportMyAllLearningRecord($request->all()),
                "MyAllrecord_{$dateTime}_{$employeeId}.xlsx"
            ),
            default => Excel::download(
                new ExportMyLearningRecord($request->all()),
                "MyOWLrecord_{$dateTime}_{$employeeId}.xlsx"
            ),
        };
    }
}
