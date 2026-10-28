<?php

namespace App\Http\Controllers\Manage\Record;

use App\Exports\AllClassificationExport;
use App\Exports\AllParticipantExport;
use App\Http\Controllers\Controller;
use App\Services\Record\RecordAllCombinedService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RecordAllController extends Controller
{
    public const STATIC_CLASSIFICATIONS = [
        'Basic & Common Duties',
        'ISO Standard',
        'Innovation',
        'Job Functional',
        'Language',
        'Leadership',
        'Maintenance',
        'Other',
        'SHE',
        'Technology',
        'Competency',
        'Management',
        'Technical',
        'Quality & Safety',
        'General',
    ];

    protected RecordAllCombinedService $combinedService;

    public function __construct(RecordAllCombinedService $combinedService)
    {
        $this->combinedService = $combinedService;
    }

    /**
     * Render the lightweight All KP OWL landing overview shell.
     */
    public function index(Request $request)
    {
        return view('main.record.all', [
            'title'           => 'All KP OWL',
            'subtitle'        => 'Combined Online & Offline Learning Record',
            'classifications' => [],
            'years'           => [],
            'entries'         => (int) $request->input('entries', 25),
        ]);
    }

    /**
     * Asynchronously return combined KPI and Source summary.
     */
    public function ajaxSummary(Request $request): JsonResponse
    {
        $summary = $this->combinedService->getSummary();

        return response()->json([
            'success'              => true,
            'total_learning'       => $summary['total_learning'],
            'ongoing'              => $summary['ongoing'],
            'finished'             => $summary['finished'],
            'total_learning_hours' => $summary['total_learning_hours'],
            'completion_rate'      => $summary['completion_rate'],
            'data'                 => $summary,
            'html'                 => view('main.record.components.all-kpi-summary', $summary)->render(),
        ]);
    }

    /**
     * Asynchronously return combined Classification Summary.
     */
    public function ajaxClassification(Request $request): JsonResponse
    {
        $classification = $this->combinedService->getClassificationSummary();

        if ($request->input('format') === 'json') {
            return response()->json([
                'success' => true,
                'data'    => $classification,
            ]);
        }

        return response()->json([
            'success' => true,
            'data'    => $classification,
            'html'    => view('main.record.components.all-classification-summary', [
                'classification' => $classification,
            ])->render(),
        ]);
    }

    /**
     * Render the scoped classification detail view for All KP OWL.
     */
    public function classification(string $classification, Request $request)
    {
        if ($request->input('action') === 'download') {
            return $this->classificationDownload($classification, $request);
        }

        $resolved = self::resolveClassification($classification, true);
        $filterOptions = $this->combinedService->getFilterOptions();

        return view('main.record.all.classification', [
            'title'              => "Classification: {$resolved['name']}",
            'subtitle'           => "Combined Online & Offline Learning Records for {$resolved['name']}",
            'classificationName' => $resolved['name'],
            'slug'               => $resolved['slug'],
            'isUnclassified'     => $resolved['is_unclassified'],
            'years'              => $filterOptions['years'],
            'departments'        => $filterOptions['departments'] ?? [],
            'companies'          => $filterOptions['companies'] ?? [],
            'entries'            => (int) $request->input('entries', 25),
        ]);
    }

    /**
     * Download export locked to the given classification (Excel, CSV, or PDF).
     */
    public function classificationDownload(string $classification, Request $request, ?string $format = null)
    {
        $targetFormat = strtolower(trim((string) ($format ?? $request->input('format', 'xlsx'))));

        if ($targetFormat === 'csv') {
            return $this->classificationDownloadCsv($classification, $request);
        }

        if ($targetFormat === 'pdf') {
            return $this->classificationDownloadPdf($classification, $request);
        }

        return $this->classificationDownloadExcel($classification, $request);
    }

    /**
     * Download Excel export locked to the given classification.
     */
    public function classificationDownloadExcel(string $classification, Request $request)
    {
        $resolved = self::resolveClassification($classification, true);
        $request->merge([
            'classification' => $resolved['is_unclassified'] ? 'Unclassified' : $resolved['name'],
        ]);

        $export = new AllClassificationExport($resolved['name'], $request);

        return $export->download($export->getFileName());
    }

    /**
     * Download CSV export locked to the given classification via unbuffered streaming.
     */
    public function classificationDownloadCsv(string $classification, Request $request)
    {
        $resolved = self::resolveClassification($classification, true);
        $request->merge([
            'classification' => $resolved['is_unclassified'] ? 'Unclassified' : $resolved['name'],
        ]);

        $query = $this->combinedService->buildCombinedQuery($request->all())
            ->orderBy('record_date', 'desc');

        $slug = Str::slug($resolved['name']);
        $fileName = "all_learning_records_{$slug}_" . date('Ymd_His') . ".csv";

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $columnHeadings = (new AllClassificationExport(''))->headings();

        return response()->streamDownload(function () use ($query, $columnHeadings) {
            $output = fopen('php://output', 'w');
            // Write UTF-8 BOM so Microsoft Excel cleanly recognizes UTF-8 encoding
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

                    // Duration formatted as HH:MM:SS
                    $durationFormatted = '';
                    if (!empty($row->duration_seconds)) {
                        $secs = (int) $row->duration_seconds;
                        $hours = floor($secs / 3600);
                        $mins = floor(($secs % 3600) / 60);
                        $seconds = $secs % 60;
                        $durationFormatted = sprintf('%02d:%02d:%02d', $hours, $mins, $seconds);
                    }

                    $recognizedFormatted = '-';
                    if ($row->source !== 'offline') {
                        $rSecs = (int) ($row->recognized_duration_seconds ?? 0);
                        $rHours = floor($rSecs / 3600);
                        $rMins = floor(($rSecs % 3600) / 60);
                        $rSeconds = $rSecs % 60;
                        $recognizedFormatted = sprintf('%02d:%02d:%02d', $rHours, $rMins, $rSeconds);
                    }

                    // Attendance
                    $attendanceStr = '';
                    if ($row->attendance !== null) {
                        $attendanceStr = (bool) $row->attendance ? 'Present' : 'Absent';
                    }

                    // Counted as Corporate KPI
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
                        $recognizedFormatted,
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

    /**
     * Download PDF export locked to the given classification (Max 200 records).
     */
    public function classificationDownloadPdf(string $classification, Request $request)
    {
        $resolved = self::resolveClassification($classification, true);
        $request->merge([
            'classification' => $resolved['is_unclassified'] ? 'Unclassified' : $resolved['name'],
        ]);

        $query = $this->combinedService->buildCombinedQuery($request->all());
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

            return response()->view('main.record.all.pdf-limit-warning', [
                'classification'     => $classification,
                'classificationName' => $resolved['name'],
                'totalRecords'       => $totalRecords,
                'limit'              => $maxPdfLimit,
                'requestParams'      => $request->except(['action', 'format']),
            ], 422);
        }

        $rows = $query->orderBy('record_date', 'desc')->get();
        $headers = (new AllClassificationExport(''))->headings();

        $activeFilterBadges = [];
        if ($emp = $request->input('employee_id')) {
            $activeFilterBadges[] = "Employee/NIP: \"{$emp}\"";
        }
        if ($title = $request->input('learning_title')) {
            $activeFilterBadges[] = "Title: \"{$title}\"";
        }
        if ($src = $request->input('source')) {
            if ($src !== 'all') {
                $activeFilterBadges[] = 'Source: ' . ($src === 'offline' ? 'Non KP OWL' : 'KP OWL');
            }
        }
        if ($status = $request->input('status')) {
            if ($status !== 'all') {
                $activeFilterBadges[] = 'Status: ' . ucfirst($status);
            }
        }
        if ($year = $request->input('year')) {
            if ($year !== 'all') {
                $activeFilterBadges[] = "Year: {$year}";
            }
        }
        if ($from = $request->input('from_date')) {
            $activeFilterBadges[] = "From: {$from}";
        }
        if ($to = $request->input('to_date')) {
            $activeFilterBadges[] = "To: {$to}";
        }
        if ($prog = $request->input('progress_range')) {
            if ($prog !== 'all') {
                $activeFilterBadges[] = "Progress: {$prog}";
            }
        }
        if ($hours = $request->input('learning_hours_range')) {
            if ($hours !== 'all') {
                $activeFilterBadges[] = "Hours: {$hours}";
            }
        }
        if ($dept = $request->input('department')) {
            if ($dept !== 'all') {
                $activeFilterBadges[] = "Dept: {$dept}";
            }
        }
        if ($comp = $request->input('company')) {
            if ($comp !== 'all') {
                $activeFilterBadges[] = "Company: {$comp}";
            }
        }

        $pdf = Pdf::loadView('main.record.all.export-pdf', [
            'classificationName' => $resolved['name'],
            'headers'            => $headers,
            'rows'               => $rows,
            'totalRecords'       => $totalRecords,
            'generatedAt'        => date('d M Y H:i:s'),
            'activeFilterBadges' => $activeFilterBadges,
        ]);

        $pdf->setPaper('a2', 'landscape');
        $slug = Str::slug($resolved['name']);
        $fileName = "all_learning_records_{$slug}_" . date('Ymd_His') . ".pdf";

        return $pdf->download($fileName);
    }

    /**
     * Server-side DataTable locked to the given classification.
     */
    public function classificationDatatable(string $classification, Request $request): JsonResponse
    {
        $resolved = self::resolveClassification($classification, true);
        $request->merge([
            'classification' => $resolved['is_unclassified'] ? 'Unclassified' : $resolved['name'],
        ]);

        return $this->datatable($request);
    }

    /**
     * Server-side DataTable for combined learning records.
     */
    public function datatable(Request $request): JsonResponse
    {
        $classParam = trim((string) ($request->input('classification', 'all')));
        if ($classParam !== '' && strtolower($classParam) !== 'all') {
            $resolved = self::resolveClassification($classParam, false);
            $request->merge([
                'classification' => $resolved['is_unclassified'] ? 'Unclassified' : $resolved['name'],
            ]);
        }

        return $this->combinedService->datatable($request);
    }

    /**
     * Resolve a classification slug/string into canonical name, slug, and unclassified flag.
     * Aborts 404 if not found and $abortIfNotFound is true.
     */
    public static function resolveClassification(string $input, bool $abortIfNotFound = true): array
    {
        $trimmed = trim($input);
        $slugified = Str::slug($trimmed);

        if ($trimmed === '' || $slugified === '' || $slugified === 'unclassified' || strtolower($trimmed) === 'unclassified') {
            return [
                'name'            => 'Unclassified',
                'slug'            => 'unclassified',
                'is_unclassified' => true,
            ];
        }

        $dbOnline = DB::table('classification')
            ->whereNotNull('classification_name')
            ->where('classification_name', '!=', '')
            ->distinct()
            ->pluck('classification_name')
            ->all();

        $dbOffline = [];
        if (Schema::hasTable('offline_trainings')) {
            $dbOffline = DB::table('offline_trainings')
                ->whereNull('deleted_at')
                ->whereNotNull('main_classification')
                ->where('main_classification', '!=', '')
                ->distinct()
                ->pluck('main_classification')
                ->all();
        }

        $candidates = array_values(array_unique(array_filter(array_map('trim', array_merge($dbOnline, $dbOffline, self::STATIC_CLASSIFICATIONS)))));

        foreach ($candidates as $candidate) {
            if (Str::slug($candidate) === $slugified || strtolower(trim($candidate)) === strtolower($trimmed)) {
                return [
                    'name'            => $candidate,
                    'slug'            => Str::slug($candidate),
                    'is_unclassified' => false,
                ];
            }
        }

        if ($abortIfNotFound) {
            abort(404, 'Classification not found');
        }

        return [
            'name'            => ucwords(str_replace('-', ' ', $slugified)),
            'slug'            => $slugified,
            'is_unclassified' => false,
        ];
    }

    /**
     * Render the combined Participant Detail view for All KP OWL.
     */
    public function participantDetail(string $identifier, Request $request)
    {
        $participant = $this->combinedService->resolveParticipant($identifier);
        if (!$participant) {
            abort(404, 'Participant not found.');
        }

        $classificationParam = $request->query('classification');
        $resolvedClass = null;

        if (filled($classificationParam) && strtolower($classificationParam) !== 'all') {
            $resolvedClass = self::resolveClassification($classificationParam, false);
            $backUrl = route('manage.record.all.classification', $resolvedClass['slug']);
            $backTitle = 'Back to ' . $resolvedClass['name'];
        } else {
            $backUrl = route('manage.record.all');
            $backTitle = 'Back to All KP OWL';
        }

        $summary = $this->combinedService->getParticipantSummary($participant, $classificationParam);
        $filterOptions = $this->combinedService->getFilterOptions();

        $rawIdentifier = $participant['raw_identifier'];

        return view('main.record.all.participant', [
            'title'               => "Participant: {$participant['name']}",
            'subtitle'            => "Combined Online & Offline Learning Records for {$participant['name']}",
            'participant'         => $participant,
            'summary'             => $summary,
            'backUrl'             => $backUrl,
            'backTitle'           => $backTitle,
            'classificationParam' => $classificationParam,
            'classificationName'  => $resolvedClass ? $resolvedClass['name'] : null,
            'classificationSlug'  => $resolvedClass ? $resolvedClass['slug'] : null,
            'years'               => $filterOptions['years'] ?? [],
            'classifications'     => $filterOptions['classifications'] ?? [],
            'exportExcelUrl'      => route('manage.record.all.participant.export.excel', array_merge(['identifier' => $rawIdentifier], $request->query())),
            'exportCsvUrl'        => route('manage.record.all.participant.export.csv', array_merge(['identifier' => $rawIdentifier], $request->query())),
            'exportPdfUrl'        => route('manage.record.all.participant.export.pdf', array_merge(['identifier' => $rawIdentifier], $request->query())),
        ]);
    }

    /**
     * Server-side DataTable for a single participant's combined records.
     */
    public function participantDatatable(string $identifier, Request $request): JsonResponse
    {
        $participant = $this->combinedService->resolveParticipant($identifier);
        if (!$participant) {
            return response()->json([
                'draw'            => (int) $request->input('draw', 1),
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => [],
            ]);
        }

        return $this->combinedService->participantDatatable($participant, $request);
    }

    /**
     * Asynchronously return KPI Summary for a specific participant.
     */
    public function participantAjaxSummary(string $identifier, Request $request): JsonResponse
    {
        $participant = $this->combinedService->resolveParticipant($identifier);
        if (!$participant) {
            return response()->json([
                'success' => false,
                'message' => 'Participant not found.',
            ], 404);
        }

        $classificationParam = $request->query('classification');
        $summary = $this->combinedService->getParticipantSummary($participant, $classificationParam);

        return response()->json([
            'success'        => true,
            'data'           => $summary,
            'total_learning' => $summary['total_learning'],
            'finished'       => $summary['finished'],
            'ongoing'        => $summary['ongoing'],
            'learning_hours' => $summary['learning_hours'],
        ]);
    }

    /**
     * Download Excel export for a specific participant's combined records.
     */
    public function participantExportExcel(string $identifier, Request $request)
    {
        $participant = $this->combinedService->resolveParticipant($identifier);
        if (!$participant) {
            abort(404, 'Participant not found.');
        }

        $export = new AllParticipantExport($participant, $request);

        return $export->download($export->getFileName());
    }

    /**
     * Download CSV export for a specific participant's combined records.
     */
    public function participantExportCsv(string $identifier, Request $request)
    {
        $participant = $this->combinedService->resolveParticipant($identifier);
        if (!$participant) {
            abort(404, 'Participant not found.');
        }

        $filters = $request->all();
        if (!empty($filters['classification']) && strtolower($filters['classification']) !== 'all') {
            $resolvedClass = self::resolveClassification($filters['classification'], false);
            $filters['classification'] = $resolvedClass['is_unclassified'] ? 'Unclassified' : $resolvedClass['name'];
        }

        $query = $this->combinedService->buildParticipantCombinedQuery($participant, $filters)
            ->orderBy('raw_finish_date', 'desc')
            ->orderBy('record_date', 'desc');

        $rawId = Str::slug($participant['employee_id'] ?: ($participant['name'] ?: 'participant'));
        $fileName = "all_learning_records_participant_{$rawId}_" . date('Ymd_His') . ".csv";

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $columnHeadings = (new AllParticipantExport($participant))->headings();

        return response()->streamDownload(function () use ($query, $columnHeadings) {
            $output = fopen('php://output', 'w');
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

                    $recognizedFormatted = '-';
                    if ($row->source !== 'offline') {
                        $rSecs = (int) ($row->recognized_duration_seconds ?? 0);
                        $rHours = floor($rSecs / 3600);
                        $rMins = floor(($rSecs % 3600) / 60);
                        $rSeconds = $rSecs % 60;
                        $recognizedFormatted = sprintf('%02d:%02d:%02d', $rHours, $rMins, $rSeconds);
                    }

                    $attendanceStr = '';
                    if ($row->attendance !== null) {
                        $attendanceStr = (bool) $row->attendance ? 'Present' : 'Absent';
                    }

                    $kpiStr = '';
                    if ($row->counted_corporate_kpi !== null) {
                        if ((bool) $row->counted_corporate_kpi) {
                            $year = !empty($row->corporate_kpi_year) ? $row->corporate_kpi_year : (!empty($row->sub_corporate_kpi) && preg_match('/^\d{4}$/', (string)$row->sub_corporate_kpi) ? $row->sub_corporate_kpi : null);
                            $kpiStr = $year ? "Yes ({$year})" : "Yes";
                        } else {
                            $kpiStr = 'No';
                        }
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
                        $recognizedFormatted,
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

    /**
     * Download PDF export for a specific participant's combined records.
     */
    public function participantExportPdf(string $identifier, Request $request)
    {
        $participant = $this->combinedService->resolveParticipant($identifier);
        if (!$participant) {
            abort(404, 'Participant not found.');
        }

        $filters = $request->all();
        if (!empty($filters['classification']) && strtolower($filters['classification']) !== 'all') {
            $resolvedClass = self::resolveClassification($filters['classification'], false);
            $filters['classification'] = $resolvedClass['is_unclassified'] ? 'Unclassified' : $resolvedClass['name'];
        }

        $query = $this->combinedService->buildParticipantCombinedQuery($participant, $filters);
        $totalRecords = (clone $query)->count();

        $rows = $query->orderBy('raw_finish_date', 'desc')
            ->orderBy('record_date', 'desc')
            ->get();

        $headers = (new AllParticipantExport($participant))->headings();

        $pdf = Pdf::loadView('main.record.all.participant-export-pdf', [
            'participant'        => $participant,
            'headers'            => $headers,
            'rows'               => $rows,
            'totalRecords'       => $totalRecords,
            'generatedAt'        => date('d M Y H:i:s'),
        ]);

        $pdf->setPaper('a2', 'landscape');
        $rawId = Str::slug($participant['employee_id'] ?: ($participant['name'] ?: 'participant'));
        $fileName = "all_learning_records_participant_{$rawId}_" . date('Ymd_His') . ".pdf";

        return $pdf->download($fileName);
    }

    /**
     * Smart redirect to the appropriate course detail page based on source type.
     */
    public function courseRedirect(string $type, $id, Request $request)
    {
        $classification = $request->query('classification');
        $params = ['from' => 'all'];
        if (filled($classification)) {
            $params['classification'] = $classification;
        }

        if ($type === 'offline') {
            $params['training'] = $id;
            return redirect()->route('manage.record.offline.course', $params);
        }

        if ($type === 'content') {
            $content = DB::table('content')->where('id', $id)->first();
            if ($content && !empty($content->slug)) {
                $params['slug'] = $content->slug;
                return redirect()->route('learning.manage.content', $params);
            }
            return redirect()->route('learning.manage.content', array_merge(['slug' => $id], $params));
        }

        if ($type === 'playlist') {
            $playlist = DB::table('trainingplan')->where('id', $id)->first();
            if ($playlist && !empty($playlist->slug)) {
                return redirect()->to(url('/playlist/' . $playlist->slug . '?' . http_build_query($params)));
            }
            return redirect()->to(url('/playlist/' . $id . '?' . http_build_query($params)));
        }

        abort(404, 'Course type not supported.');
    }
}
