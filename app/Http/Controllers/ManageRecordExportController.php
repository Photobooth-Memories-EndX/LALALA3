<?php

namespace App\Http\Controllers;

use App\Services\Record\ManageAllRecordExportService;
use App\Services\Record\ManageOfflineRecordExportService;
use App\Services\Record\ManageOnlineRecordExportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ManageRecordExportController extends Controller
{
    protected ManageOnlineRecordExportService $onlineService;
    protected ManageOfflineRecordExportService $offlineService;
    protected ManageAllRecordExportService $allService;

    public function __construct(
        ManageOnlineRecordExportService $onlineService,
        ManageOfflineRecordExportService $offlineService,
        ManageAllRecordExportService $allService
    ) {
        $this->onlineService = $onlineService;
        $this->offlineService = $offlineService;
        $this->allService = $allService;
    }

    public static function formatExportDateTime(?\DateTimeInterface $date = null): string
    {
        $dt = $date ? Carbon::parse($date) : Carbon::now();
        $monthMap = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];
        $day = $dt->format('d');
        $month = $monthMap[(int) $dt->format('n')] ?? $dt->format('M');
        $year = $dt->format('Y');
        $time = $dt->format('H.i.s');

        return "{$day}{$month}{$year} at {$time}";
    }

    public function exportOnline(Request $request): BinaryFileResponse
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 600);
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        $dateTime = self::formatExportDateTime();
        $fileName = "EmployeeOWLrecord_{$dateTime}.xlsx";

        return $this->onlineService->download($request->all(), $fileName);
    }

    public function exportOffline(Request $request): BinaryFileResponse
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 600);
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        $dateTime = self::formatExportDateTime();
        $fileName = "EmployeeNonOWLrecord_{$dateTime}.xlsx";

        return $this->offlineService->download($request->all(), $fileName);
    }

    public function exportAll(Request $request): BinaryFileResponse
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 600);
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        $dateTime = self::formatExportDateTime();
        $fileName = "EmployeeAllrecord_{$dateTime}.xlsx";

        return $this->allService->download($request->all(), $fileName);
    }

    public function exportClassificationOnline(Request $request, ?string $classification = null)
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 600);
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        $classVal = $classification ?? $request->input('classification');
        $dateTime = self::formatExportDateTime();
        $fileName = "EmployeeOWLrecord_{$dateTime}.xlsx";

        return $this->onlineService->downloadClassification($classVal, $request->all(), $fileName);
    }

    public function exportClassificationOffline(Request $request, ?string $classification = null)
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 600);
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        $classVal = $classification ?? $request->input('classification');
        $dateTime = self::formatExportDateTime();
        $fileName = "EmployeeNonOWLrecord_{$dateTime}.xlsx";

        return $this->offlineService->downloadClassification($classVal, $request->all(), $fileName);
    }

    public function exportClassificationAll(Request $request, ?string $classification = null)
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 600);
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        $classVal = $classification ?? $request->input('classification');
        $dateTime = self::formatExportDateTime();
        $fileName = "EmployeeAllrecord_{$dateTime}.xlsx";

        return $this->allService->downloadClassification($classVal, $request->all(), $fileName);
    }

    public function exportClassification(Request $request, ?string $classification = null)
    {
        $source = strtolower((string) $request->input('source', 'all'));
        if ($source === 'online' || $source === 'owl') {
            return $this->exportClassificationOnline($request, $classification);
        }
        if ($source === 'offline' || $source === 'non-owl') {
            return $this->exportClassificationOffline($request, $classification);
        }

        return $this->exportClassificationAll($request, $classification);
    }
}
