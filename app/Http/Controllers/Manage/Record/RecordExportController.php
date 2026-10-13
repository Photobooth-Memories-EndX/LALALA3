<?php

namespace App\Http\Controllers\Manage\Record;

use App\Exports\AllRecordExport;
use App\Exports\LearningExport;
use App\Exports\OfflineTrainingCsvExport;
use App\Exports\OfflineTrainingExport;
use App\Exports\OnlineRecordCsvExport;
use App\Exports\Record\OnlineFullRecordExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\LearningManageController;
use App\Models\Classification;
use App\Models\OfflineTraining;
use App\Models\OfflineTrainingOption;
use App\Models\User;
use App\Services\Learning\LearningDatatableService;
use App\Services\Record\OnlineFullRecordExportService;
use App\Services\Record\RecordAllCombinedService;
use App\Services\Record\RecordOfflineQueryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class RecordExportController extends Controller
{
    protected RecordAllCombinedService $combinedService;

    public function __construct(RecordAllCombinedService $combinedService)
    {
        $this->combinedService = $combinedService;
    }

    /**
     * Render the Learning Record Export Center page.
     */
    public function index(Request $request)
    {
        $currentYear = (int) date('Y');
        $years = LearningManageController::learningYears();

        // 1. Combined Filter Options (Classifications, Departments, Companies)
        $filterOptions = $this->combinedService->getFilterOptions();

        // 2. Offline Training Types
        $trainingTypes = RecordOfflineController::TRAINING_TYPES;

        // 3. Offline Distinct Categories
        $offlineCategories = [];
        if (Schema::hasTable('offline_trainings')) {
            $offlineCategories = DB::table('offline_trainings')
                ->whereNull('deleted_at')
                ->whereNotNull('category')
                ->where('category', '!=', '')
                ->distinct()
                ->pluck('category')
                ->sort()
                ->values()
                ->all();
        }

        // 4. Offline Distinct Providers & Delivery Methods
        $providers = ['Internal', 'External'];
        $deliveryMethods = ['Classroom', 'Hybrid', 'Practical'];
        if (Schema::hasTable('offline_trainings')) {
            $dbProviders = DB::table('offline_trainings')
                ->whereNull('deleted_at')
                ->whereNotNull('provider')
                ->where('provider', '!=', '')
                ->distinct()
                ->pluck('provider')
                ->all();
            if (!empty($dbProviders)) {
                $providers = array_values(array_unique(array_filter(array_merge($providers, $dbProviders))));
            }

            $dbDelivery = DB::table('offline_trainings')
                ->whereNull('deleted_at')
                ->whereNotNull('delivery_method')
                ->where('delivery_method', '!=', '')
                ->distinct()
                ->pluck('delivery_method')
                ->all();
            if (!empty($dbDelivery)) {
                $deliveryMethods = array_values(array_unique(array_filter(array_merge($deliveryMethods, $dbDelivery))));
            }
        }

        // 5. Offline Distinct Grades
        $grades = [];
        if (Schema::hasTable('offline_training_users')) {
            $grades = DB::table('offline_training_users')
                ->whereNull('deleted_at')
                ->whereNotNull('grade')
                ->where('grade', '!=', '')
                ->distinct()
                ->pluck('grade')
                ->sort()
                ->values()
                ->all();
        }

        // 6. Sub Classification Map by Main Classification
        $subClassMap = [];
        if (Schema::hasTable('offline_trainings')) {
            $subRecords = DB::table('offline_trainings')
                ->whereNull('deleted_at')
                ->whereNotNull('main_classification')
                ->whereNotNull('sub_classification')
                ->where('sub_classification', '!=', '')
                ->select('main_classification', 'sub_classification')
                ->distinct()
                ->get();

            foreach ($subRecords as $sr) {
                $m = trim((string) $sr->main_classification);
                $s = trim((string) $sr->sub_classification);
                if ($m !== '' && $s !== '') {
                    $subClassMap[$m][] = $s;
                }
            }

            foreach ($subClassMap as $m => $subs) {
                $subClassMap[$m] = array_values(array_unique($subs));
                sort($subClassMap[$m]);
            }
        }

        // 7. Distinct Divisions
        $divisions = DB::table('users')
            ->whereNotNull('division')
            ->where('division', '!=', '')
            ->distinct()
            ->pluck('division')
            ->sort()
            ->values()
            ->all();

        return view('main.record.export-all', [
            'title'               => 'Export KP OWL',
            'subtitle'            => 'Learning Record Export Center',
            'years'               => $years,
            'currentYear'         => $currentYear,
            'classifications'     => $filterOptions['classifications'] ?? [],
            'departments'         => $filterOptions['departments'] ?? [],
            'divisions'           => $divisions,
            'companies'           => $filterOptions['companies'] ?? [],
            'trainingTypes'       => $trainingTypes,
            'offlineCategories'   => $offlineCategories,
            'providers'           => $providers,
            'deliveryMethods'     => $deliveryMethods,
            'grades'              => $grades,
            'subClassMap'         => $subClassMap,
        ]);
    }

    /**
     * AJAX endpoint to get sub-classifications for a given main classification.
     */
    public function subClassifications(Request $request): JsonResponse
    {
        $mainClass = trim((string) $request->input('classification', ''));

        $query = DB::table('offline_trainings')
            ->whereNull('deleted_at')
            ->whereNotNull('sub_classification')
            ->where('sub_classification', '!=', '');

        if ($mainClass !== '' && $mainClass !== 'all') {
            $query->where('main_classification', $mainClass);
        }

        $fromTrainings = $query->distinct()->pluck('sub_classification')->toArray();

        // Also check offline_training_options
        $fromOptions = [];
        if (Schema::hasTable('offline_training_options')) {
            $fromOptions = DB::table('offline_training_options')
                ->where('option_type', 'sub_classification')
                ->where('is_active', true)
                ->pluck('value')
                ->toArray();
        }

        if ($mainClass !== '' && $mainClass !== 'all') {
            // When a specific classification is chosen, keep only training-linked options if available
            $results = !empty($fromTrainings) ? $fromTrainings : $fromOptions;
        } else {
            $results = array_merge($fromTrainings, $fromOptions);
        }

        $results = array_values(array_unique(array_filter(array_map('trim', $results))));
        sort($results);

        return response()->json([
            'success' => true,
            'data'    => $results,
        ]);
    }

    /**
     * AJAX endpoint to preview filtered data before exporting (limited to 25 records).
     */
    public function preview(Request $request): JsonResponse
    {
        $source = strtolower(trim((string) $request->input('source', 'online')));
        if (!in_array($source, ['online', 'offline', 'all'], true)) {
            $source = 'online';
        }

        $filters = $request->all();
        $previewLimit = (int) $request->input('limit', 25);
        if ($previewLimit < 5 || $previewLimit > 100) {
            $previewLimit = 25;
        }

        $rows = [];
        $totalRecords = 0;

        if ($source === 'online') {
            $query = LearningDatatableService::buildBaseQuery($filters);
            $totalRecords = (clone $query)->count();

            $records = $query->orderBy('unified.created_at', 'desc')
                ->orderBy('unified.source_type', 'desc')
                ->orderBy('unified.source_id', 'desc')
                ->limit($previewLimit)
                ->get();

            $no = 1;
            foreach ($records as $r) {
                $title = $r->content_title ?? '-';
                if (!empty($r->playlist_title)) {
                    $title .= ' (Playlist: ' . $r->playlist_title . ')';
                }

                $rawType = (string) ($r->content_type ?? '');
                $type = in_array(strtolower($rawType), ['video', 'youtube', 'mooc'], true) ? 'Video' : ($rawType ?: 'Document');
                if (($r->source_type ?? '') === 'playlist') {
                    $type = 'Playlist';
                }

                $statusStr = 'Not Started';
                $progress = (int) ($r->progress ?? 0);
                if ((string) ($r->finish ?? '') === '1' || $progress >= 100) {
                    $statusStr = 'Finished';
                } elseif ($progress > 0) {
                    $statusStr = 'Ongoing';
                }

                $dateStr = '-';
                if (!empty($r->created_at)) {
                    $dateStr = date('Y-m-d', strtotime($r->created_at));
                }

                $rows[] = [
                    'no'                 => $no++,
                    'source'             => 'online',
                    'source_label'       => 'KP OWL',
                    'source_badge_class' => 'badge-kp-owl',
                    'employee_id'        => $r->id_emp ?: '-',
                    'employee_name'      => $r->employee_name ?: ($r->user_name ?: '-'),
                    'department'         => $r->user_department ?: '-',
                    'company'            => '-',
                    'title'              => $title,
                    'type'               => $type,
                    'classification'     => $r->classification_name ?: 'Unclassified',
                    'date'               => $dateStr,
                    'hours'              => '-',
                    'progress'           => $progress,
                    'status'             => $statusStr,
                ];
            }
        } elseif ($source === 'offline') {
            $query = RecordOfflineQueryService::buildGlobalQuery($filters);
            $totalRecords = (clone $query)->count();

            $records = $query->orderBy('ot.start_date', 'desc')
                ->orderBy('ot.id', 'desc')
                ->orderBy('otu.id', 'asc')
                ->limit($previewLimit)
                ->get();

            $no = 1;
            foreach ($records as $r) {
                $statusStr = 'Not Started';
                $progress = (int) ($r->progress ?? 0);
                if ($progress >= 100) {
                    $statusStr = 'Finished';
                } elseif ($progress > 0) {
                    $statusStr = 'Ongoing';
                }

                $dateStr = '-';
                if (!empty($r->start_date)) {
                    $dateStr = date('Y-m-d', strtotime($r->start_date));
                } elseif (!empty($r->finish_date)) {
                    $dateStr = date('Y-m-d', strtotime($r->finish_date));
                }

                $hoursStr = $r->learning_hours !== null ? number_format((float) $r->learning_hours, 2) : '0.00';

                $rows[] = [
                    'no'                 => $no++,
                    'source'             => 'offline',
                    'source_label'       => 'Non KP OWL',
                    'source_badge_class' => 'badge-non-kp-owl',
                    'employee_id'        => $r->employee_id ?: '-',
                    'employee_name'      => $r->participant_name ?: ($r->user_name ?: '-'),
                    'department'         => $r->department ?: '-',
                    'company'            => $r->company ?: '-',
                    'title'              => $r->course_name ?: '-',
                    'type'               => $r->type ?: 'Offline Training',
                    'classification'     => $r->main_classification ?: 'Unclassified',
                    'date'               => $dateStr,
                    'hours'              => $hoursStr,
                    'progress'           => $progress,
                    'status'             => $statusStr,
                ];
            }
        } else {
            // source === 'all'
            $query = $this->combinedService->buildCombinedQuery($filters);
            $totalRecords = (clone $query)->count();

            $records = $query->orderBy('raw_finish_date', 'desc')
                ->orderBy('record_date', 'desc')
                ->limit($previewLimit)
                ->get();

            $no = 1;
            foreach ($records as $r) {
                $isOffline = ($r->source === 'offline');
                $dateStr = '-';
                if (!empty($r->finish_date)) {
                    $dateStr = date('Y-m-d', strtotime($r->finish_date));
                } elseif (!empty($r->start_date)) {
                    $dateStr = date('Y-m-d', strtotime($r->start_date));
                } elseif (!empty($r->record_date)) {
                    $dateStr = date('Y-m-d', strtotime($r->record_date));
                }

                $hoursStr = $r->learning_hours !== null ? number_format((float) $r->learning_hours, 2) : '0.00';

                $rows[] = [
                    'no'                 => $no++,
                    'source'             => $r->source,
                    'source_label'       => $isOffline ? 'Non KP OWL' : 'KP OWL',
                    'source_badge_class' => $isOffline ? 'badge-non-kp-owl' : 'badge-kp-owl',
                    'employee_id'        => $r->employee_id ?: '-',
                    'employee_name'      => $r->employee_name ?: '-',
                    'department'         => $r->department ?: '-',
                    'company'            => $r->company ?: '-',
                    'title'              => $r->learning_title ?: '-',
                    'type'               => $r->type ?: '-',
                    'classification'     => $r->classification ?: 'Unclassified',
                    'date'               => $dateStr,
                    'hours'              => $hoursStr,
                    'progress'           => (int) ($r->progress ?? 0),
                    'status'             => $r->status ? ucfirst((string) $r->status) : 'Not Started',
                ];
            }
        }

        $showingCount = count($rows);
        $message = $totalRecords > 0
            ? "Showing {$showingCount} of " . number_format($totalRecords) . " matching records"
            : "No records found matching current filters";

        return response()->json([
            'success'      => true,
            'source'       => $source,
            'total'        => $totalRecords,
            'limit'        => $previewLimit,
            'showing'      => $showingCount,
            'message'      => $message,
            'data'         => $rows,
        ]);
    }

    /**
     * Download the filtered export file (Excel, CSV, or PDF).
     */
    public function download(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'source'     => 'required|string|in:online,offline,all',
            'format'     => 'required|string|in:xlsx,csv,pdf',
            'year'       => 'nullable|numeric|min:2000|max:2099',
            'from_date'  => 'nullable|date_format:Y-m-d,d/m/Y,Y/m/d',
            'to_date'    => 'nullable|date_format:Y-m-d,d/m/Y,Y/m/d',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error: ' . implode(', ', $validator->errors()->all()),
                    'errors'  => $validator->errors(),
                ], 422);
            }

            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $source = strtolower(trim((string) $request->input('source')));
        $format = strtolower(trim((string) $request->input('format', 'xlsx')));
        $filters = $request->all();

        // 1. ONLINE (KP OWL) EXPORT
        if ($source === 'online') {
            return $this->downloadOnline($filters, $format, $request);
        }

        // 2. OFFLINE (NON KP OWL) EXPORT
        if ($source === 'offline') {
            return $this->downloadOffline($filters, $format, $request);
        }

        // 3. ALL (COMBINED) EXPORT
        return $this->downloadAll($filters, $format, $request);
    }

    /**
     * Handle KP OWL Online export.
     */
    protected function downloadOnline(array $filters, string $format, Request $request): Response
    {
        $dateSuffix = now()->format('Y-m-d');
        $service = app(OnlineFullRecordExportService::class);

        if ($format === 'csv') {
            $fileName = sprintf('kp-owl-online-records-%s.csv', $dateSuffix);
            $query = $service->buildQuery($filters);
            $columnHeadings = OnlineFullRecordExportService::headings();

            $headers = [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
                'Pragma'              => 'no-cache',
                'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
                'Expires'             => '0',
            ];

            return response()->streamDownload(function () use ($query, $columnHeadings, $service) {
                $output = fopen('php://output', 'w');
                // UTF-8 BOM
                fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($output, $columnHeadings);

                $streamPdo = $service->getStreamPdo();
                $stmt = $streamPdo->prepare($query->toSql());
                $stmt->execute($query->getBindings());

                $rowCount = 0;
                $batch = [];
                $batchSize = 2500;

                while ($row = $stmt->fetch(\PDO::FETCH_OBJ)) {
                    $batch[] = $row;
                    if (count($batch) >= $batchSize) {
                        $context = $service->buildBatchContext($batch);
                        foreach ($batch as $bRow) {
                            $rowCount++;
                            fputcsv($output, $service->mapRow($bRow, $rowCount, $context));
                        }
                        if (ob_get_level() > 0) {
                            ob_flush();
                        }
                        flush();
                        $batch = [];
                    }
                }

                if (!empty($batch)) {
                    $context = $service->buildBatchContext($batch);
                    foreach ($batch as $bRow) {
                        $rowCount++;
                        fputcsv($output, $service->mapRow($bRow, $rowCount, $context));
                    }
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                    $batch = [];
                }

                $stmt->closeCursor();
                fclose($output);
            }, $fileName, $headers);
        }

        if ($format === 'pdf') {
            $query = $service->buildQuery($filters);

            $totalRecords = (clone $query)->count();
            $maxPdfLimit = 500;

            if ($totalRecords > $maxPdfLimit) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => "PDF export is limited to maximum {$maxPdfLimit} records to ensure readability and performance (Current: {$totalRecords}). Please apply more specific filters or choose Excel / CSV.",
                    ], 422);
                }

                return redirect()->back()->with('error', "PDF export is limited to maximum {$maxPdfLimit} records (Current: {$totalRecords}). Please filter further or download via Excel/CSV.");
            }

            $records = $query->limit($maxPdfLimit)->get();

            $pdf = Pdf::loadView('main.record.online.export-pdf', [
                'records'       => $records,
                'totalRecords'  => $totalRecords,
                'limit'         => $maxPdfLimit,
                'filters'       => $filters,
                'generatedAt'   => now()->format('d M Y H:i:s'),
            ]);

            $fileName = sprintf('kp-owl-online-records-%s.pdf', $dateSuffix);
            return $pdf->setPaper('a4', 'landscape')->download($fileName);
        }

        // Default: XLSX
        $fileName = sprintf('kp-owl-online-records-%s.xlsx', $dateSuffix);
        return (new OnlineFullRecordExport($filters, $fileName))->download();
    }

    /**
     * Handle Non KP OWL Offline export.
     */
    protected function downloadOffline(array $filters, string $format, Request $request): Response
    {
        $dateSuffix = now()->format('Y-m-d');

        if ($format === 'csv') {
            $export = new OfflineTrainingCsvExport(null, $filters);
            $fileName = sprintf('non-kp-owl-records-%s.csv', $dateSuffix);
            return $export->download($fileName, \Maatwebsite\Excel\Excel::CSV);
        }

        if ($format === 'pdf') {
            $query = RecordOfflineQueryService::buildGlobalQuery($filters)
                ->orderBy('ot.start_date', 'desc')
                ->orderBy('ot.id', 'desc')
                ->orderBy('otu.id', 'asc');

            $totalRecords = (clone $query)->count();
            $maxPdfLimit = 500;

            if ($totalRecords > $maxPdfLimit) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => "PDF export is limited to maximum {$maxPdfLimit} records (Current: {$totalRecords}). Please apply more specific filters or choose Excel / CSV.",
                    ], 422);
                }

                return redirect()->back()->with('error', "PDF export is limited to maximum {$maxPdfLimit} records (Current: {$totalRecords}). Please filter further or download via Excel/CSV.");
            }

            $records = $query->limit($maxPdfLimit)->get();

            $pdf = Pdf::loadView('main.record.offline.export-pdf', [
                'records'       => $records,
                'generatedAt'   => now()->format('d M Y H:i:s'),
                'generatedDate' => now()->format('d M Y H:i:s'),
                'totalRecords'  => $totalRecords,
            ]);

            $fileName = sprintf('non-kp-owl-records-%s.pdf', $dateSuffix);
            return $pdf->setPaper('a4', 'landscape')->download($fileName);
        }

        // Default: XLSX
        $export = new OfflineTrainingExport(null, $filters);
        $fileName = sprintf('non-kp-owl-records-%s.xlsx', $dateSuffix);
        return $export->download($fileName);
    }

    /**
     * Handle All KP OWL Combined export.
     */
    protected function downloadAll(array $filters, string $format, Request $request): Response
    {
        $dateSuffix = now()->format('Y-m-d');

        if ($format === 'csv') {
            $query = $this->combinedService->buildCombinedQuery($filters)
                ->orderBy('raw_finish_date', 'desc')
                ->orderBy('record_date', 'desc');

            $fileName = sprintf('all-kp-owl-combined-records-%s.csv', $dateSuffix);

            $headers = [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
                'Pragma'              => 'no-cache',
                'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
                'Expires'             => '0',
            ];

            $columnHeadings = (new AllRecordExport($filters))->headings();

            return response()->streamDownload(function () use ($query, $columnHeadings) {
                $output = fopen('php://output', 'w');
                // UTF-8 BOM
                fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($output, $columnHeadings);

                $pdo = DB::connection()->getPdo();
                $wasBuffered = $pdo->getAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY);
                $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

                try {
                    $stmt = $pdo->prepare($query->toSql());
                    $stmt->execute($query->getBindings());

                    $rowCount = 0;
                    while ($row = $stmt->fetch(\PDO::FETCH_OBJ)) {
                        $rowCount++;

                        $durationFormatted = '';
                        if (!empty($row->duration_seconds)) {
                            $secs = (int) $row->duration_seconds;
                            $hours = floor($secs / 3600);
                            $mins = floor(($secs % 3600) / 60);
                            $seconds = $secs % 60;
                            $durationFormatted = sprintf('%02d:%02d:%02d', $hours, $mins, $seconds);
                        }

                        $attendanceStr = '';
                        if ($row->attendance !== null) {
                            $attendanceStr = (bool) $row->attendance ? 'Present' : 'Absent';
                        }

                        $kpiStr = '';
                        if ($row->counted_corporate_kpi !== null) {
                            $kpiStr = (bool) $row->counted_corporate_kpi ? 'Yes' : 'No';
                        }

                        $csvRow = [
                            $rowCount,
                            $row->source === 'offline' ? 'Non KP OWL' : 'KP OWL',
                            $row->employee_id ?: '',
                            $row->employee_name ?: '',
                            $row->status ? ucfirst((string) $row->status) : '',
                            $row->grade ?: '',
                            $row->job_position ?: '',
                            $row->team ?: '',
                            $row->department ?: '',
                            $row->company ?: '',
                            $row->learning_title ?: '',
                            $row->sub_course_name ?: '',
                            $row->type ?: '',
                            $row->category ?: '',
                            $row->level ?: '',
                            $row->batch ?: '',
                            $row->classification ?: '',
                            $row->sub_classification ?: '',
                            $kpiStr,
                            $row->sub_corporate_kpi ?: '',
                            $row->delivery_method ?: '',
                            $row->sub_delivery_method ?: '',
                            $row->provider ?: '',
                            $row->sub_provider ?: '',
                            $row->provider_name ?: '',
                            $row->venue ?: '',
                            $row->instructor ?: '',
                            $durationFormatted,
                            $row->training_hours !== null ? number_format((float) $row->training_hours, 2) : '',
                            $row->start_date ? date('Y-m-d', strtotime($row->start_date)) : '',
                            $row->finish_date ? date('Y-m-d', strtotime($row->finish_date)) : '',
                            $row->learning_hours !== null ? number_format((float) $row->learning_hours, 2) : '0.00',
                            $row->progress !== null ? ($row->progress . '%') : '0%',
                            $row->assessment_score !== null ? number_format((float) $row->assessment_score, 2) : '',
                            $row->certificate ?: '',
                            $attendanceStr,
                            $row->attendance_reason ?: '',
                            $row->bond_status ?: '',
                            $row->bond_end_date ? date('Y-m-d', strtotime($row->bond_end_date)) : '',
                            $row->bond_amount !== null ? number_format((float) $row->bond_amount, 2) : '',
                            $row->l1_program ?: '',
                            $row->l2_numerical !== null ? number_format((float) $row->l2_numerical, 2) : '',
                            $row->l2_verbal ?: '',
                            $row->l3_numerical !== null ? number_format((float) $row->l3_numerical, 2) : '',
                            $row->l3_verbal ?: '',
                            $row->pic ?: '',
                        ];

                        fputcsv($output, $csvRow);
                    }
                } finally {
                    $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $wasBuffered);
                    fclose($output);
                }
            }, $fileName, $headers);
        }

        if ($format === 'pdf') {
            $query = $this->combinedService->buildCombinedQuery($filters);
            $totalRecords = (clone $query)->count();
            $maxPdfLimit = 200;

            if ($totalRecords > $maxPdfLimit) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => "PDF export is limited to maximum {$maxPdfLimit} records to ensure readability on landscape format and prevent memory limits (Current filtered records: {$totalRecords}). Please apply additional filters or download via Excel / CSV.",
                        'total'   => $totalRecords,
                        'limit'   => $maxPdfLimit,
                    ], 422);
                }

                return redirect()->back()->with('error', "PDF export is limited to maximum {$maxPdfLimit} records (Current: {$totalRecords}). Please apply filters or download via Excel/CSV.");
            }

            $rows = $query->orderBy('raw_finish_date', 'desc')->orderBy('record_date', 'desc')->get();
            $headers = (new AllRecordExport($filters))->headings();

            $activeFilterBadges = [];
            if (!empty($filters['employee_id'])) {
                $activeFilterBadges[] = "Employee/NIP: \"{$filters['employee_id']}\"";
            }
            if (!empty($filters['classification']) && $filters['classification'] !== 'all') {
                $activeFilterBadges[] = "Classification: {$filters['classification']}";
            }
            if (!empty($filters['year']) && $filters['year'] !== 'all') {
                $activeFilterBadges[] = "Year: {$filters['year']}";
            }
            if (!empty($filters['department']) && $filters['department'] !== 'all') {
                $activeFilterBadges[] = "Department: {$filters['department']}";
            }
            if (!empty($filters['company']) && $filters['company'] !== 'all') {
                $activeFilterBadges[] = "Company: {$filters['company']}";
            }
            if (!empty($filters['status']) && $filters['status'] !== 'all') {
                $activeFilterBadges[] = "Status: " . ucfirst($filters['status']);
            }

            $pdf = Pdf::loadView('main.record.all.export-pdf', [
                'classificationName' => !empty($filters['classification']) && $filters['classification'] !== 'all' ? $filters['classification'] : 'All Combined',
                'records'            => $rows,
                'totalRecords'       => $totalRecords,
                'headers'            => $headers,
                'filters'            => $filters,
                'activeFilterBadges' => $activeFilterBadges,
                'generatedAt'        => now()->format('d M Y H:i:s'),
            ]);

            $fileName = sprintf('all-kp-owl-combined-records-%s.pdf', $dateSuffix);
            return $pdf->setPaper('a2', 'landscape')->download($fileName);
        }

        // Default: XLSX
        $fileName = sprintf('all-kp-owl-combined-records-%s.xlsx', $dateSuffix);
        return (new AllRecordExport($filters, $fileName))->download();
    }
}
