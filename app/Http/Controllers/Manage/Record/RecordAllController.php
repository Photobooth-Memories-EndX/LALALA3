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

        $grandTotal = [
            'kp_owl'         => (int) $classification->sum('kp_owl'),
            'non_kp_owl'     => (int) $classification->sum('non_kp_owl'),
            'total_learning' => (int) $classification->sum('total_learning'),
            'learning_hours' => (float) round($classification->sum('learning_hours'), 2),
            'ongoing'        => (int) $classification->sum('ongoing'),
            'finished'       => (int) $classification->sum('finished'),
        ];

        if ($request->input('format') === 'json') {
            return response()->json([
                'success'     => true,
                'data'        => $classification,
                'grand_total' => $grandTotal,
            ]);
        }

        return response()->json([
            'success'     => true,
            'data'        => $classification,
            'grand_total' => $grandTotal,
            'html'        => view('main.record.components.all-classification-summary', [
                'classification' => $classification,
                'grandTotal'     => $grandTotal,
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
        $displayName = $resolved['displayName'] ?? (strcasecmp($resolved['name'], 'Audit Isolated Classification') === 0 ? 'Unclassification' : $resolved['name']);
        $filterOptions = $this->combinedService->getFilterOptions();

        return view('main.record.all.classification', [
            'title'              => "Classification: {$displayName}",
            'subtitle'           => "Combined Online & Offline Learning Records for {$displayName}",
            'classificationName' => $displayName,
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
        $lower = strtolower($trimmed);

        // 1. Unclassification triggers (empty, null, unclassified, unclassification, audit isolated)
        $unclassSlugs = ['unclassified', 'unclassification', 'audit-isolated-classification'];
        $unclassNames = ['unclassified', 'unclassification', 'audit isolated classification'];
        if ($trimmed === '' || $slugified === '' || in_array($slugified, $unclassSlugs, true) || in_array($lower, $unclassNames, true)) {
            return [
                'name'            => 'Unclassification',
                'displayName'     => 'Unclassification',
                'slug'            => 'unclassification',
                'is_unclassified' => true,
            ];
        }

        // 2. Load master classifications from table `classification`
        $masters = [];
        $dbMasters = DB::table('classification')->orderBy('id')->get();
        foreach ($dbMasters as $m) {
            $mSlug = !empty($m->slug) ? $m->slug : Str::slug($m->classification_name);
            $masters[$mSlug] = [
                'id'                  => (string) $m->id,
                'classification_name' => trim($m->classification_name),
                'slug'                => $mSlug,
            ];
        }

        // Ensure Compliance is recognized as a master classification
        if (!isset($masters['compliance'])) {
            $masters['compliance'] = [
                'id'                  => 'compliance',
                'classification_name' => 'Compliance',
                'slug'                => 'compliance',
            ];
        }

        // 3. Explicit canonical aliases (e.g. Basic Common Duties -> Basic & Common Duties)
        $aliases = [
            'basic common duties'     => 'basic-common-duties',
            'basic & common duties'   => 'basic-common-duties',
            'basic and common duties' => 'basic-common-duties',
            'basic-common-duties'     => 'basic-common-duties',
        ];

        if (isset($aliases[$lower])) {
            $targetSlug = $aliases[$lower];
            if (isset($masters[$targetSlug])) {
                $target = $masters[$targetSlug];
                return [
                    'name'            => $target['classification_name'],
                    'displayName'     => $target['classification_name'],
                    'slug'            => $target['slug'],
                    'is_unclassified' => false,
                ];
            }
        }

        // 4. Match by slug against master classifications
        if (isset($masters[$slugified])) {
            $target = $masters[$slugified];
            return [
                'name'            => $target['classification_name'],
                'displayName'     => $target['classification_name'],
                'slug'            => $target['slug'],
                'is_unclassified' => false,
            ];
        }

        // 5. Match by case-insensitive name against master classifications
        foreach ($masters as $target) {
            if (strcasecmp($target['classification_name'], $trimmed) === 0) {
                return [
                    'name'            => $target['classification_name'],
                    'displayName'     => $target['classification_name'],
                    'slug'            => $target['slug'],
                    'is_unclassified' => false,
                ];
            }
        }

        // 6. Match by normalized ampersand / 'and' slug
        $normalizedAmpSlug = Str::slug(str_replace('&', 'and', $trimmed));
        foreach ($masters as $target) {
            $targetAmpSlug = Str::slug(str_replace('&', 'and', $target['classification_name']));
            if ($normalizedAmpSlug === $targetAmpSlug) {
                return [
                    'name'            => $target['classification_name'],
                    'displayName'     => $target['classification_name'],
                    'slug'            => $target['slug'],
                    'is_unclassified' => false,
                ];
            }
        }

        // 7. Non-master fallback: if abortIfNotFound is true abort 404, else treat as Unclassification or formatted name
        if ($abortIfNotFound) {
            abort(404, 'Classification not found');
        }

        return [
            'name'            => ucwords(str_replace('-', ' ', $slugified)),
            'displayName'     => ucwords(str_replace('-', ' ', $slugified)),
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

        return response()->streamDownload(function () use ($query, $columnHeadings, $participant) {
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($output, $columnHeadings);

            $userCerts = [];
            if (!empty($participant['user_id'])) {
                $cRows = DB::table('certificates as c')
                    ->leftJoin('certificate_settings as cs', 'cs.id', '=', 'c.certificate_setting_id')
                    ->where('c.user_id', $participant['user_id'])
                    ->select('c.content_user_id', 'c.offline_training_user_id', 'cs.issuer_name', 'c.institution', 'c.issued_at', 'c.issued_date', 'c.expired_date')
                    ->get();
                foreach ($cRows as $cr) {
                    if (!empty($cr->content_user_id)) {
                        $userCerts['content:' . $cr->content_user_id] = $cr;
                    }
                    if (!empty($cr->offline_training_user_id)) {
                        $userCerts['offline:' . $cr->offline_training_user_id] = $cr;
                    }
                }
            }

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
                    $durationFormatted = '-';
                    if (!empty($row->duration_seconds)) {
                        $secs = (int) $row->duration_seconds;
                        $hours = floor($secs / 3600);
                        $mins = floor(($secs % 3600) / 60);
                        $seconds = $secs % 60;
                        $durationFormatted = sprintf('%02d:%02d:%02d', $hours, $mins, $seconds);
                    }

                    // Recognized Duration formatted as HH:MM:SS
                    $recognizedFormatted = '-';
                    if ($row->source !== 'offline') {
                        if (isset($row->recognized_duration_seconds)) {
                            $rSecs = (int) $row->recognized_duration_seconds;
                        } else {
                            $recognizedService = app(\App\Services\Learning\LearningRecordRecognizedDurationService::class);
                            $rSecs = $recognizedService->resolveFromRow($row);
                        }
                        $rHours = floor($rSecs / 3600);
                        $rMins = floor(($rSecs % 3600) / 60);
                        $rSeconds = $rSecs % 60;
                        $recognizedFormatted = sprintf('%02d:%02d:%02d', $rHours, $rMins, $rSeconds);
                    }

                    // Total Engagement Duration (LH) formatted as HH:MM:SS
                    $engSecs = (int) ($row->afk_duration_seconds ?? 0);
                    if ($engSecs <= 0 && $row->source === 'offline') {
                        $engSecs = (int) round(((float) ($row->training_hours ?? ($row->learning_hours ?? 0))) * 3600);
                    }
                    $eh = floor($engSecs / 3600);
                    $em = floor(($engSecs % 3600) / 60);
                    $es = $engSecs % 60;
                    $totalEngagementDuration = sprintf('%02d:%02d:%02d', $eh, $em, $es);

                    $lhNumeric = ($row->learning_hours !== null && $row->learning_hours !== '' && is_numeric($row->learning_hours))
                        ? number_format((float) $row->learning_hours, 2)
                        : '-';

                    $progressNum = (float) ($row->progress ?? 0);
                    $progressFormatted = round($progressNum, 2) . '%';
                    $progressStatus = ($progressNum >= 100 || ($row->status ?? '') === 'Finished') ? 'Finished' : (($progressNum > 0) ? 'Ongoing' : 'Not Started');

                    $sourceRaw = strtolower(trim((string) ($row->source ?? '')));
                    $isKpOwl = in_array($sourceRaw, ['online', 'kp owl', 'kp-owl', 'owl'], true);
                    $delivMethod = $isKpOwl ? 'Online' : 'Offline';

                    if ($isKpOwl) {
                        $subDelivMethod = 'KP OWL';
                    } else {
                        $sub = $row->sub_delivery_method ?? $row->training_type ?? $row->type ?? null;
                        if (!$sub || $sub === '-' || strtolower(trim((string) $sub)) === 'offline') {
                            $rawDm = trim((string) ($row->delivery_method ?? ''));
                            if ($rawDm !== '' && strtolower($rawDm) !== 'offline') {
                                $sub = $rawDm;
                            }
                        }
                        $subDelivMethod = $sub ?: '-';
                    }

                    $startDateStr = !empty($row->start_date) ? date('Y-m-d', strtotime($row->start_date)) : '-';
                    $endDateStr = !empty($row->end_date)
                        ? date('Y-m-d', strtotime($row->end_date))
                        : (!empty($row->finish_date) ? date('Y-m-d', strtotime($row->finish_date)) : '-');

                    $startTimeStr = !empty($row->start_time) ? date('H:i:s', strtotime($row->start_time)) : '-';
                    $endTimeStr = !empty($row->end_time) ? date('H:i:s', strtotime($row->end_time)) : '-';

                    $recordMonthDate = $row->start_date ?: ($row->finish_date ?: ($row->record_date ?? null));
                    $recordMonthStr = !empty($recordMonthDate) ? date('F', strtotime($recordMonthDate)) : '-';
                    $finalRecordMonth = ($row->source === 'online')
                        ? ($endDateStr !== '-' ? date('F', strtotime($endDateStr)) : '-')
                        : $recordMonthStr;

                    $attendanceStr = '-';
                    if ($row->attendance !== null && $row->attendance !== '') {
                        $rawAtt = strtolower(trim((string) $row->attendance));
                        $attendanceStr = in_array($rawAtt, ['1', 'yes', 'present', 'true', 'hadir', 'completed', 'finished'], true) ? 'Yes' : 'No';
                    } elseif ($row->source === 'online') {
                        $attendanceStr = ($progressNum >= 100) ? 'Yes' : (($progressNum > 0) ? 'No' : '-');
                    }

                    $idr = $row->bond_amount_idr ?? $row->bond_amount ?? null;
                    $hasBond = ($idr !== null && (float) $idr > 0);
                    $bondAmtStr = $hasBond ? 'Rp ' . number_format((float) $idr, 0, ',', '.') : '-';

                    $scoreL1 = is_numeric($row->l1_program) ? (float) $row->l1_program : (!empty($row->l1_program) ? $row->l1_program : '-');
                    $scoreL2 = is_numeric($row->assessment_score)
                        ? (int) round((float) $row->assessment_score)
                        : (is_numeric($row->l2_numerical) ? (int) round((float) $row->l2_numerical) : '-');
                    $scoreL3 = is_numeric($row->l3_numerical) ? (float) $row->l3_numerical : '-';

                    $l2Status = $row->l2_verbal ?: (is_numeric($scoreL2) ? ($scoreL2 >= 70 ? 'Passed' : 'Failed') : '-');
                    $l3Status = $row->l3_verbal ?: '-';

                    $rawEmp = $row->employee_id ?: '-';
                    $cleanEmpId = ($rawEmp !== '-') ? preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', trim((string) $rawEmp)) : '-';

                    $hasCert = false;
                    $certInstitution = '-';
                    $certIssuedDate = '-';
                    $certExpiredDate = '-';

                    $certKey = ($row->source === 'offline') ? ('offline:' . ($row->source_id ?? '')) : ('content:' . ($row->source_id ?? ''));
                    if (isset($userCerts[$certKey])) {
                        $cr = $userCerts[$certKey];
                        $hasCert = true;
                        if (!empty($cr->issuer_name)) $certInstitution = $cr->issuer_name;
                        elseif (!empty($cr->institution)) $certInstitution = $cr->institution;
                        if (!empty($cr->issued_date)) $certIssuedDate = date('Y-m-d', strtotime($cr->issued_date));
                        elseif (!empty($cr->issued_at)) $certIssuedDate = date('Y-m-d', strtotime($cr->issued_at));
                        if (!empty($cr->expired_date)) $certExpiredDate = date('Y-m-d', strtotime($cr->expired_date));
                    } elseif ($row->source === 'offline') {
                        if (!empty($row->certificate) && $row->certificate !== '-') {
                            $hasCert = true;
                            $certInstitution = $row->certificate;
                        }
                        if (!empty($row->certificate_issued_date) && $row->certificate_issued_date !== '-') {
                            $certIssuedDate = date('Y-m-d', strtotime($row->certificate_issued_date));
                        }
                        if (!empty($row->certificate_expired_date) && $row->certificate_expired_date !== '-') {
                            $certExpiredDate = date('Y-m-d', strtotime($row->certificate_expired_date));
                        }
                    } else {
                        $isFinishedOnline = ($progressNum >= 100 || ($row->status ?? '') === 'Finished' || ($row->finish ?? '') === '1');
                        if ($isFinishedOnline) {
                            $hasCert = true;
                            $certInstitution = 'PT Krakatau Posco';
                            $certIssuedDate = $endDateStr !== '-' ? $endDateStr : ($startDateStr !== '-' ? $startDateStr : '-');
                        }
                    }

                    $certAvailability = $hasCert ? 'Available' : '-';

                    $csvRow = [
                        $rowCount,
                        $row->source === 'offline' ? 'Non KP OWL' : 'KP OWL',
                        $cleanEmpId,
                        $row->employee_name ?: '-',
                        $row->grade ?: '-',
                        $row->job_position ?: '-',
                        $row->team ?: '-',
                        $row->department ?: '-',
                        $row->company ?: '-',
                        $row->learning_title ?: '-',
                        $row->sub_course_name ?: '-',
                        ($row->source === 'offline' || !$isKpOwl) ? '-' : ($row->type ?: '-'),
                        $row->category ?: '-',
                        $row->level ?: '-',
                        $row->batch ?: '-',
                        $row->classification ?: 'Unclassified',
                        $row->sub_classification ?: '-',
                        $delivMethod,
                        $subDelivMethod,
                        $row->source === 'online' ? 'Internal' : ($row->provider ?: '-'),
                        $row->source === 'online' ? '-' : ($row->sub_provider ?: '-'),
                        $row->provider_name ?: ($row->source === 'offline' ? '-' : 'KP-OWL'),
                        $row->venue ?: ($row->source === 'offline' ? '-' : 'Online LMS'),
                        $row->instructor ?: '-',
                        $durationFormatted,
                        $row->source === 'offline' ? '-' : $recognizedFormatted,
                        $lhNumeric,
                        $progressFormatted,
                        $progressStatus,
                        $startDateStr,
                        $endDateStr,
                        $startTimeStr,
                        $endTimeStr,
                        $finalRecordMonth,
                        $row->requirement ?: 'Mandatory',
                        $attendanceStr,
                        $row->attendance_reason ?: '-',
                        $row->bond_status ?: 'No',
                        !empty($row->bond_duration) ? $row->bond_duration . ' Months' : '-',
                        !empty($row->bond_end_date) ? date('Y-m-d', strtotime($row->bond_end_date)) : '-',
                        $bondAmtStr,
                        $scoreL1,
                        $scoreL2,
                        $l2Status,
                        $scoreL3,
                        $l3Status,
                        $certAvailability,
                        $certInstitution,
                        $certIssuedDate,
                        $certExpiredDate,
                        $row->pic ?: '-',
                        $totalEngagementDuration,
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
