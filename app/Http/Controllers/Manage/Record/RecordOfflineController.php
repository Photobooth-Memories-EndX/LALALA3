<?php

namespace App\Http\Controllers\Manage\Record;

use App\Exports\OfflineClassificationExport;
use App\Exports\OfflineCourseCsvExport;
use App\Exports\OfflineCourseExport;
use App\Exports\OfflineParticipantCsvExport;
use App\Exports\OfflineParticipantExport;
use App\Exports\OfflineTrainingCsvExport;
use App\Exports\OfflineTrainingExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Http\Controllers\LearningManageController;
use App\Models\Classification;
use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Services\Record\OfflineOptionService;
use App\Services\Record\OfflineTrainingCreateService;
use App\Services\Record\OfflineTrainingUpdateService;
use App\Services\Record\OfflineTrainingExcelSchema;
use App\Services\Record\RecordOfflineQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class RecordOfflineController extends Controller
{
    public const TRAINING_TYPES = [
        'In-House',
        'Public',
        'Certification',
        'Workshop',
        'Seminar',
        'Classroom',
        'External',
        'Overseas',
    ];

    public const MAIN_CLASSIFICATIONS = [
        'Competency',
        'Leadership',
        'Management',
        'Technical',
        'Quality & Safety',
        'General',
    ];

    public function index(Request $request)
    {
        return view('main.record.offline', [
            'title'               => 'Non KP OWL',
            'subtitle'            => 'Offline Training Record',
            'years'               => LearningManageController::learningYears(),
            'entries'             => (int) $request->input('entries', 25),
            'trainingTypes'       => self::TRAINING_TYPES,
            'mainClassifications' => self::MAIN_CLASSIFICATIONS,
        ]);
    }

    public function create(OfflineOptionService $optionService)
    {
        $currentYear = (int) date('Y');
        $options = $optionService->getGroupedOptions($currentYear, true);
        $kpiYears = $optionService->getKpiYears();
        $classifications = Classification::orderBy('classification_name', 'asc')->get();

        return view('main.record.offline-create', [
            'title'               => 'Add Non KP OWL Training',
            'subtitle'            => 'Create an offline employee training record.',
            'classifications'     => $classifications,
            'options'             => $options,
            'kpiYears'            => $kpiYears,
            'currentYear'         => $currentYear,
            'trainingTypes'       => self::TRAINING_TYPES,
            'mainClassifications' => $classifications->pluck('classification_name')->all(),
            'schemaColumns'       => OfflineTrainingExcelSchema::columns(),
            'schemaGroups'        => OfflineTrainingExcelSchema::groups(),
        ]);
    }

    public function ajaxSummary(Request $request): JsonResponse
    {
        $hasTables = Schema::hasTable('offline_trainings') && Schema::hasTable('offline_training_users');

        if ($hasTables) {
            $totalTrainings = (int) OfflineTraining::forRecord()->count();

            $baseUserQuery = DB::table('offline_training_users as otu')
                ->join('offline_trainings as ot', 'ot.id', '=', 'otu.offline_training_id')
                ->whereNull('otu.deleted_at')
                ->whereNull('ot.deleted_at');
            OfflineTraining::applyRecordScope($baseUserQuery, 'ot');

            $totalParticipants = (int) (clone $baseUserQuery)->count();
            $totalHours = (float) (clone $baseUserQuery)->sum('otu.learning_hours');
            $finished = (int) (clone $baseUserQuery)
                ->where('otu.progress', '>=', 100)
                ->count();
            $ongoing = (int) (clone $baseUserQuery)
                ->where(function ($q) {
                    $q->where('otu.progress', '<', 100)
                      ->orWhereNull('otu.progress');
                })
                ->count();
            $completionRate = $totalParticipants > 0 ? round(($finished / $totalParticipants) * 100, 1) : 0.0;
        } else {
            $totalTrainings = 0;
            $totalParticipants = 0;
            $totalHours = 0.0;
            $finished = 0;
            $ongoing = 0;
            $completionRate = 0.0;
        }

        $data = [
            'total_trainings'      => $totalTrainings,
            'total_participants'   => $totalParticipants,
            'total_learning_hours' => round($totalHours, 1),
            'finished'             => $finished,
            'ongoing'              => $ongoing,
            'completion_rate'      => $completionRate,
            'totalTrainings'       => $totalTrainings,
            'totalParticipants'    => $totalParticipants,
            'totalLearningHours'   => round($totalHours, 1),
            'completionRate'       => $completionRate,
        ];

        return response()->json([
            'success'              => true,
            'total_trainings'      => $data['total_trainings'],
            'total_participants'   => $data['total_participants'],
            'total_learning_hours' => $data['total_learning_hours'],
            'completion_rate'      => $data['completion_rate'],
            'data'                 => $data,
            'html'                 => view('main.record.components.offline-kpi-summary', $data)->render(),
        ]);
    }

    public function ajaxClassification(Request $request)
    {
        $classification = $this->getClassificationSummary();

        if ($request->input('format') === 'html') {
            return view('main.record.components.offline-classification-summary', [
                'classification' => $classification,
            ]);
        }

        if ($request->wantsJson() || $request->ajax() || $request->input('format') === 'json') {
            return response()->json([
                'success' => true,
                'data'    => $classification,
                'html'    => view('main.record.components.offline-classification-summary', [
                    'classification' => $classification,
                ])->render(),
            ]);
        }

        return view('main.record.components.offline-classification-summary', [
            'classification' => $classification,
        ]);
    }

    public function getClassificationSummary(): Collection
    {
        $hasTables = Schema::hasTable('offline_trainings') && Schema::hasTable('offline_training_users');

        if (!$hasTables) {
            return collect();
        }

        $query = DB::table('offline_trainings as ot')
            ->leftJoin('offline_training_users as otu', function ($join) {
                $join->on('otu.offline_training_id', '=', 'ot.id')
                    ->whereNull('otu.deleted_at');
            })
            ->whereNull('ot.deleted_at');

        OfflineTraining::applyRecordScope($query, 'ot');

        $records = $query
            ->select([
                DB::raw("COALESCE(NULLIF(TRIM(ot.main_classification), ''), 'Unclassified') as classification_name"),
                DB::raw("COUNT(DISTINCT ot.id) as programs"),
                DB::raw("COUNT(otu.id) as total_participants"),
                DB::raw("COALESCE(SUM(otu.learning_hours), 0) as learning_hours"),
                DB::raw("SUM(CASE WHEN otu.progress > 0 AND otu.progress < 100 THEN 1 ELSE 0 END) as ongoing"),
                DB::raw("SUM(CASE WHEN otu.progress >= 100 THEN 1 ELSE 0 END) as finished"),
            ])
            ->groupBy(DB::raw("COALESCE(NULLIF(TRIM(ot.main_classification), ''), 'Unclassified')"))
            ->orderBy('classification_name', 'asc')
            ->get();

        return $records->map(function ($item, $index) {
            $name = (string) $item->classification_name;
            $resolved = self::resolveClassification($name);

            return (object) [
                'no'                 => $index + 1,
                'classification_name'=> $name,
                'slug'               => $resolved['slug'],
                'programs'           => (int) ($item->programs ?? 0),
                'total_participants' => (int) ($item->total_participants ?? 0),
                'learning_hours'     => (float) ($item->learning_hours ?? 0),
                'ongoing'            => (int) ($item->ongoing ?? 0),
                'finished'           => (int) ($item->finished ?? 0),
            ];
        });
    }

    public function classification(string $classification, Request $request)
    {
        $resolved = self::resolveClassification($classification);

        return view('main.record.offline.classification', [
            'title'               => "Classification: {$resolved['name']}",
            'subtitle'            => 'Monitor offline training records and participant progress for this classification.',
            'classificationName'  => $resolved['name'],
            'slug'                => $resolved['slug'],
            'isUnclassified'      => $resolved['is_unclassified'],
            'years'               => LearningManageController::learningYears(),
            'entries'             => (int) $request->input('entries', 25),
            'trainingTypes'       => self::TRAINING_TYPES,
            'downloadUrl'         => route('manage.record.offline.classification.download', $resolved['slug']),
        ]);
    }

    /**
     * Download global Excel export containing all active offline training records across all classifications.
     */
    public function export(Request $request)
    {
        $export = new OfflineTrainingExport(null, $request);

        return $export->download($export->getFileName());
    }

    /**
     * Download global PDF export containing all active offline training records across all classifications.
     */
    public function exportPdf(Request $request)
    {
        $records = RecordOfflineQueryService::buildGlobalQuery($request->all())
            ->orderBy('ot.start_date', 'desc')
            ->orderBy('ot.id', 'desc')
            ->orderBy('otu.id', 'asc')
            ->get();

        $pdf = Pdf::loadView('main.record.offline.export-pdf', [
            'records'       => $records,
            'generatedAt'   => now()->format('d M Y H:i:s'),
            'generatedDate' => now()->format('d M Y H:i:s'),
            'totalRecords'  => $records->count(),
        ]);

        $fileName = sprintf('non-kp-owl-all-records-%s.pdf', now()->format('Y-m-d'));

        return $pdf->setPaper('a4', 'landscape')->download($fileName);
    }

    /**
     * Download global CSV export containing all active offline training records across all classifications.
     */
    public function exportCsv(Request $request)
    {
        $export = new OfflineTrainingCsvExport(null, $request);

        return $export->download($export->getFileName(), \Maatwebsite\Excel\Excel::CSV);
    }

    public function classificationDownload(string $classification, Request $request)
    {
        $export = new OfflineClassificationExport($classification, $request);

        return $export->download($export->getFileName());
    }

    public function classificationDatatable(string $classification, Request $request)
    {
        $request->merge(['classification' => $classification]);

        return $this->datatable($request);
    }

    public function participantDetail(string $identifier, Request $request)
    {
        $participant = RecordOfflineQueryService::resolveParticipant($identifier);
        if (!$participant) {
            abort(404, 'Participant not found.');
        }

        $summary = RecordOfflineQueryService::getParticipantSummary($participant);

        // Resolve safe back URL
        $backUrl = route('manage.record.offline');
        $backTitle = 'Back to Non KP OWL';

        $classificationParam = $request->query('classification');
        if (filled($classificationParam)) {
            $resolvedClass = self::resolveClassification($classificationParam);
            $backUrl = route('manage.record.offline.classification', $resolvedClass['slug']);
            $backTitle = 'Back to ' . $resolvedClass['name'];
        }

        $classifications = array_unique(array_merge(
            self::MAIN_CLASSIFICATIONS,
            DB::table('offline_trainings')->whereNull('deleted_at')->whereNotNull('main_classification')->where('main_classification', '!=', '')->distinct()->pluck('main_classification')->all()
        ));
        sort($classifications);

        $categories = DB::table('offline_trainings')->whereNull('deleted_at')->whereNotNull('category')->where('category', '!=', '')->distinct()->pluck('category')->all();
        sort($categories);

        $providers = DB::table('offline_trainings')->whereNull('deleted_at')->whereNotNull('provider')->where('provider', '!=', '')->distinct()->pluck('provider')->all();
        sort($providers);

        $deliveryMethods = DB::table('offline_trainings')->whereNull('deleted_at')->whereNotNull('delivery_method')->where('delivery_method', '!=', '')->distinct()->pluck('delivery_method')->all();
        sort($deliveryMethods);

        $levels = DB::table('offline_trainings')->whereNull('deleted_at')->whereNotNull('level')->where('level', '!=', '')->distinct()->pluck('level')->all();
        sort($levels);

        return view('main.record.offline.participant', [
            'title'               => "Participant: {$participant['name']}",
            'subtitle'            => 'Historical offline training records for this employee.',
            'participant'         => $participant,
            'summary'             => $summary,
            'backUrl'             => $backUrl,
            'backTitle'           => $backTitle,
            'classificationParam' => $classificationParam,
            'years'               => LearningManageController::learningYears(),
            'trainingTypes'       => self::TRAINING_TYPES,
            'classifications'     => $classifications,
            'categories'          => $categories,
            'providers'           => $providers,
            'deliveryMethods'     => $deliveryMethods,
            'levels'              => $levels,
            'exportExcelUrl'      => route('manage.record.offline.participant.export.excel', $participant['raw_identifier']),
            'exportCsvUrl'        => route('manage.record.offline.participant.export.csv', $participant['raw_identifier']),
            'exportPdfUrl'        => route('manage.record.offline.participant.export.pdf', $participant['raw_identifier']),
        ]);
    }

    public function participantDatatable(string $identifier, Request $request)
    {
        $participant = RecordOfflineQueryService::resolveParticipant($identifier);
        if (!$participant) {
            return response()->json([
                'draw'            => (int) $request->input('draw', 1),
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => [],
            ]);
        }

        $query = RecordOfflineQueryService::buildParticipantQuery($participant, $request->all());
        $classificationParam = $request->input('classification');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('status_badge', function ($row) {
                $progress = (float) ($row->progress ?? 0);
                if ($progress >= 100) {
                    return '<span class="badge bg-success-lt text-success fw-bold px-2 py-1">Finished</span>';
                } elseif ($progress > 0) {
                    return '<span class="badge bg-warning-lt text-warning fw-bold px-2 py-1">Ongoing (' . round($progress) . '%)</span>';
                }

                return '<span class="badge bg-secondary-lt fw-bold px-2 py-1">Not Started</span>';
            })
            ->addColumn('employee_id', function ($row) {
                return htmlspecialchars($row->employee_id ?: '-');
            })
            ->addColumn('participant_name', function ($row) {
                $name = $row->participant_name ?: ($row->user_name ?: '-');
                return '<div class="fw-semibold text-dark text-nowrap">' . htmlspecialchars($name) . '</div>';
            })
            ->addColumn('grade', function ($row) {
                return htmlspecialchars($row->grade ?: '-');
            })
            ->addColumn('job_position', function ($row) {
                return !empty($row->job_position)
                    ? '<div class="text-truncate" style="max-width: 200px;" title="' . htmlspecialchars($row->job_position) . '">' . htmlspecialchars($row->job_position) . '</div>'
                    : '-';
            })
            ->addColumn('team', function ($row) {
                return htmlspecialchars($row->team ?: '-');
            })
            ->addColumn('department', function ($row) {
                return !empty($row->department)
                    ? '<div class="text-truncate" style="max-width: 200px;" title="' . htmlspecialchars($row->department) . '">' . htmlspecialchars($row->department) . '</div>'
                    : '-';
            })
            ->addColumn('company', function ($row) {
                return htmlspecialchars($row->company ?: '-');
            })
            ->addColumn('course_name', function ($row) use ($classificationParam) {
                $courseTitle = $row->course_name ?: '-';
                $tId = $row->training_id ?? ($row->offline_training_id ?? null);
                if (!empty($tId)) {
                    $params = ['training' => $tId];
                    if ($classificationParam) {
                        $params['classification'] = $classificationParam;
                    }
                    $url = route('manage.record.offline.course', $params);
                    return '<div class="fw-semibold text-truncate" style="max-width: 260px;" title="' . htmlspecialchars($courseTitle) . '">'
                        . '<a href="' . htmlspecialchars($url) . '" class="text-primary text-decoration-none hover-underline course-link" title="View training details">'
                        . htmlspecialchars($courseTitle) . '</a></div>';
                }

                return '<div class="fw-semibold text-dark text-truncate" style="max-width: 260px;" title="' . htmlspecialchars($courseTitle) . '">'
                    . htmlspecialchars($courseTitle) . '</div>';
            })
            ->addColumn('sub_course_name', function ($row) {
                return !empty($row->sub_course_name)
                    ? '<div class="text-truncate" style="max-width: 220px;" title="' . htmlspecialchars($row->sub_course_name) . '">' . htmlspecialchars($row->sub_course_name) . '</div>'
                    : '-';
            })
            ->addColumn('type_badge', function ($row) {
                return '<span class="badge bg-light text-secondary border px-2 py-1">' . htmlspecialchars($row->type ?: 'In-House') . '</span>';
            })
            ->addColumn('category', function ($row) {
                return htmlspecialchars($row->category ?: '-');
            })
            ->addColumn('level', function ($row) {
                return htmlspecialchars($row->level ?: '-');
            })
            ->addColumn('batch', function ($row) {
                return htmlspecialchars($row->batch ?: '-');
            })
            ->addColumn('main_classification', function ($row) {
                return htmlspecialchars($row->main_classification ?: '-');
            })
            ->addColumn('sub_classification', function ($row) {
                return htmlspecialchars($row->sub_classification ?: '-');
            })
            ->addColumn('counted_corporate_kpi', function ($row) {
                if ($row->counted_corporate_kpi === null || $row->counted_corporate_kpi === '') {
                    return '<span class="text-muted small">-</span>';
                }
                return $row->counted_corporate_kpi
                    ? '<span class="badge bg-blue-lt text-primary px-2 py-1">Yes</span>'
                    : '<span class="badge bg-light text-muted border px-2 py-1">No</span>';
            })
            ->addColumn('sub_corporate_kpi', function ($row) {
                return htmlspecialchars($row->sub_corporate_kpi ?: '-');
            })
            ->addColumn('delivery_method', function ($row) {
                return htmlspecialchars($row->delivery_method ?: '-');
            })
            ->addColumn('sub_delivery_method', function ($row) {
                return htmlspecialchars($row->sub_delivery_method ?: '-');
            })
            ->addColumn('provider', function ($row) {
                return htmlspecialchars($row->provider ?: '-');
            })
            ->addColumn('sub_provider', function ($row) {
                return htmlspecialchars($row->sub_provider ?: '-');
            })
            ->addColumn('provider_name', function ($row) {
                return htmlspecialchars($row->provider_name ?: '-');
            })
            ->addColumn('venue', function ($row) {
                return htmlspecialchars($row->venue ?: '-');
            })
            ->addColumn('instructor', function ($row) {
                return htmlspecialchars($row->instructor ?: '-');
            })
            ->addColumn('duration', function ($row) {
                if (!empty($row->duration_seconds)) {
                    $secs = (int) $row->duration_seconds;
                    $hours = floor($secs / 3600);
                    $mins = floor(($secs % 3600) / 60);
                    $seconds = $secs % 60;
                    return sprintf('%02d:%02d:%02d', $hours, $mins, $seconds);
                }
                return '-';
            })
            ->addColumn('course_learning_hours', function ($row) {
                if ($row->course_learning_hours !== null && $row->course_learning_hours !== '') {
                    return '<span class="fw-semibold">' . number_format((float) $row->course_learning_hours, 1) . 'h</span>';
                }
                return '-';
            })
            ->addColumn('start_date', function ($row) {
                return $row->start_date ? date('d M Y', strtotime($row->start_date)) : '-';
            })
            ->addColumn('finish_date', function ($row) {
                if ($row->finish_date) {
                    return date('d M Y', strtotime($row->finish_date));
                } elseif ($row->start_date) {
                    return date('d M Y', strtotime($row->start_date));
                }
                return '-';
            })
            ->addColumn('month', function ($row) {
                $dateForMonth = $row->start_date ?: $row->finish_date;
                if (!empty($dateForMonth)) {
                    try {
                        return date('F', strtotime($dateForMonth));
                    } catch (\Throwable $e) {
                        return '-';
                    }
                }
                return '-';
            })
            ->addColumn('learning_hours', function ($row) {
                $hours = (float) ($row->learning_hours ?? 0);
                return '<span class="fw-semibold">' . number_format($hours, 1) . 'h</span>';
            })
            ->addColumn('progress_badge', function ($row) {
                $progress = (float) ($row->progress ?? 0);
                $color = $progress >= 100 ? 'bg-success' : ($progress > 0 ? 'bg-warning' : 'bg-secondary');
                return '<div class="d-flex align-items-center gap-2" style="min-width: 85px;">
                    <div class="progress progress-xs flex-grow-1" style="width: 50px; height: 6px;">
                        <div class="progress-bar ' . $color . '" style="width: ' . min(100, $progress) . '%"></div>
                    </div>
                    <span class="small fw-semibold text-muted">' . round($progress) . '%</span>
                </div>';
            })
            ->addColumn('assessment_score', function ($row) {
                if ($row->assessment_score === null || $row->assessment_score === '') {
                    return '<span class="text-muted small">-</span>';
                }
                return '<span class="fw-semibold">' . number_format((float) $row->assessment_score, 1) . '</span>';
            })
            ->addColumn('certificate_badge', function ($row) {
                if (!empty($row->certificate)) {
                    return '<span class="badge bg-blue-lt text-primary px-2 py-1" title="' . htmlspecialchars($row->certificate) . '"><i class="ti ti-file-certificate me-1"></i> Available</span>';
                }
                return '<span class="text-muted small">-</span>';
            })
            ->addColumn('attendance_badge', function ($row) {
                if ($row->attendance === null || $row->attendance === '') {
                    return '<span class="text-muted small">-</span>';
                }
                return $row->attendance
                    ? '<span class="badge bg-success-lt text-success px-2 py-1">Present</span>'
                    : '<span class="badge bg-danger-lt text-danger px-2 py-1" title="' . htmlspecialchars($row->attendance_reason ?: 'Absent') . '">Absent</span>';
            })
            ->addColumn('attendance_reason', function ($row) {
                if (!empty($row->attendance_reason)) {
                    return '<div class="text-truncate" style="max-width: 220px;" title="' . htmlspecialchars($row->attendance_reason) . '">' . htmlspecialchars($row->attendance_reason) . '</div>';
                }
                return '-';
            })
            ->addColumn('bond_status', function ($row) {
                if (empty($row->bond_status) || strtolower(trim($row->bond_status)) === 'no') {
                    return '<span class="badge bg-light text-muted border px-2 py-1">No</span>';
                }
                return '<span class="badge bg-warning-lt text-warning fw-bold px-2 py-1">' . htmlspecialchars($row->bond_status) . '</span>';
            })
            ->addColumn('bond_end_date', function ($row) {
                return $row->bond_end_date ? date('d M Y', strtotime($row->bond_end_date)) : '-';
            })
            ->addColumn('bond_amount', function ($row) {
                if ($row->bond_amount !== null && $row->bond_amount !== '') {
                    return '<span class="fw-semibold font-monospace">Rp ' . number_format((float) $row->bond_amount, 0, ',', '.') . '</span>';
                }
                return '-';
            })
            ->addColumn('l1_program', function ($row) {
                return htmlspecialchars($row->l1_program ?: '-');
            })
            ->addColumn('l2_numerical', function ($row) {
                if ($row->l2_numerical !== null && $row->l2_numerical !== '') {
                    return '<span class="fw-semibold">' . number_format((float) $row->l2_numerical, 1) . '</span>';
                }
                return '-';
            })
            ->addColumn('l2_verbal', function ($row) {
                if (!empty($row->l2_verbal)) {
                    return '<div class="text-truncate" style="max-width: 180px;" title="' . htmlspecialchars($row->l2_verbal) . '">' . htmlspecialchars($row->l2_verbal) . '</div>';
                }
                return '-';
            })
            ->addColumn('l3_numerical', function ($row) {
                if ($row->l3_numerical !== null && $row->l3_numerical !== '') {
                    return '<span class="fw-semibold">' . number_format((float) $row->l3_numerical, 1) . '</span>';
                }
                return '-';
            })
            ->addColumn('l3_verbal', function ($row) {
                if (!empty($row->l3_verbal)) {
                    return '<div class="text-truncate" style="max-width: 220px;" title="' . htmlspecialchars($row->l3_verbal) . '">' . htmlspecialchars($row->l3_verbal) . '</div>';
                }
                return '-';
            })
            ->addColumn('pic', function ($row) {
                return htmlspecialchars($row->pic ?: '-');
            })
            ->addColumn('action', function ($row) {
                return '<div class="d-inline-flex align-items-center justify-content-center gap-1">
                    <button type="button" class="btn btn-sm btn-icon btn-outline-primary btn-view-offline-detail rounded-2" 
                            data-id="' . $row->id . '" 
                            title="View Detail" 
                            aria-label="View Detail" 
                            data-bs-toggle="tooltip">
                        <i class="ti ti-eye"></i>
                    </button>
                </div>';
            })
            ->rawColumns([
                'status_badge',
                'participant_name',
                'job_position',
                'department',
                'course_name',
                'sub_course_name',
                'type_badge',
                'counted_corporate_kpi',
                'course_learning_hours',
                'learning_hours',
                'progress_badge',
                'assessment_score',
                'certificate_badge',
                'attendance_badge',
                'attendance_reason',
                'bond_status',
                'bond_amount',
                'l2_numerical',
                'l2_verbal',
                'l3_numerical',
                'l3_verbal',
                'action',
            ])
            ->make(true);
    }

    /**
     * Download Excel export containing active offline training records for a specific participant.
     */
    public function participantExportExcel(string $identifier, Request $request)
    {
        $participant = RecordOfflineQueryService::resolveParticipant($identifier);
        if (!$participant) {
            abort(404, 'Participant not found.');
        }

        $export = new OfflineParticipantExport($participant, $request);

        return $export->download($export->getFileName());
    }

    /**
     * Download CSV export containing active offline training records for a specific participant.
     */
    public function participantExportCsv(string $identifier, Request $request)
    {
        $participant = RecordOfflineQueryService::resolveParticipant($identifier);
        if (!$participant) {
            abort(404, 'Participant not found.');
        }

        $export = new OfflineParticipantCsvExport($participant, $request);

        return $export->download($export->getFileName(), \Maatwebsite\Excel\Excel::CSV, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Download PDF landscape export containing active offline training records for a specific participant.
     */
    public function participantExportPdf(string $identifier, Request $request)
    {
        $participant = RecordOfflineQueryService::resolveParticipant($identifier);
        if (!$participant) {
            abort(404, 'Participant not found.');
        }

        $records = RecordOfflineQueryService::buildParticipantQuery($participant, $request->all())
            ->orderBy('ot.start_date', 'desc')
            ->orderBy('ot.id', 'desc')
            ->orderBy('otu.id', 'asc')
            ->get();

        $pdf = Pdf::loadView('main.record.offline.participant-export-pdf', [
            'participant'   => $participant,
            'records'       => $records,
            'generatedAt'   => now()->format('d M Y H:i:s'),
            'totalRecords'  => $records->count(),
        ]);

        $identifierSlug = Str::slug($participant['employee_id'] ?: ($participant['name'] ?: 'participant'));
        $fileName = sprintf('non-kp-owl-participant-%s-%s.pdf', $identifierSlug, now()->format('Y-m-d'));

        return $pdf->setPaper('a4', 'landscape')->download($fileName);
    }

    public function courseDetail($training, Request $request)
    {
        $trainingModel = OfflineTraining::whereNull('deleted_at')->find($training);
        if (!$trainingModel) {
            abort(404, 'Training not found.');
        }

        $summary = RecordOfflineQueryService::getCourseSummary((int) $trainingModel->id, $trainingModel);

        // Safe back navigation
        $backUrl = route('manage.record.offline');
        $backTitle = 'Back to Non KP OWL';

        $classificationParam = $request->query('classification');
        if (filled($classificationParam)) {
            $resolvedClass = self::resolveClassification($classificationParam);
            $backUrl = route('manage.record.offline.classification', $resolvedClass['slug']);
            $backTitle = 'Back to ' . $resolvedClass['name'];
        } elseif (filled($trainingModel->main_classification)) {
            $resolvedClass = self::resolveClassification($trainingModel->main_classification);
            $backUrl = route('manage.record.offline.classification', $resolvedClass['slug']);
            $backTitle = 'Back to ' . $resolvedClass['name'];
        }

        $grades = DB::table('offline_training_users')
            ->where('offline_training_id', $trainingModel->id)
            ->whereNull('deleted_at')
            ->whereNotNull('grade')
            ->where('grade', '!=', '')
            ->distinct()
            ->orderBy('grade')
            ->pluck('grade')
            ->all();

        $teams = DB::table('offline_training_users')
            ->where('offline_training_id', $trainingModel->id)
            ->whereNull('deleted_at')
            ->whereNotNull('team')
            ->where('team', '!=', '')
            ->distinct()
            ->orderBy('team')
            ->pluck('team')
            ->all();

        $departments = DB::table('offline_training_users')
            ->where('offline_training_id', $trainingModel->id)
            ->whereNull('deleted_at')
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->orderBy('department')
            ->pluck('department')
            ->all();

        $companies = DB::table('offline_training_users')
            ->where('offline_training_id', $trainingModel->id)
            ->whereNull('deleted_at')
            ->whereNotNull('company')
            ->where('company', '!=', '')
            ->distinct()
            ->orderBy('company')
            ->pluck('company')
            ->all();

        return view('main.record.offline.course', [
            'title'               => "Course: {$trainingModel->course_name}",
            'subtitle'            => 'Training session details and enrolled participants.',
            'training'            => $trainingModel,
            'summary'             => $summary,
            'backUrl'             => $backUrl,
            'backTitle'           => $backTitle,
            'classificationParam' => $classificationParam,
            'years'               => LearningManageController::learningYears(),
            'trainingTypes'       => self::TRAINING_TYPES,
            'grades'              => $grades,
            'teams'               => $teams,
            'departments'         => $departments,
            'companies'           => $companies,
            'exportExcelUrl'      => route('manage.record.offline.course.export.excel', $trainingModel->id),
            'exportCsvUrl'        => route('manage.record.offline.course.export.csv', $trainingModel->id),
            'exportPdfUrl'        => route('manage.record.offline.course.export.pdf', $trainingModel->id),
        ]);
    }

    public function courseDatatable($training, Request $request)
    {
        $trainingModel = OfflineTraining::whereNull('deleted_at')->find($training);
        if (!$trainingModel) {
            return response()->json([
                'draw'            => (int) $request->input('draw', 1),
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => [],
            ]);
        }

        $query = RecordOfflineQueryService::buildCourseQuery((int) $trainingModel->id, $request->all());
        $classificationParam = $request->input('classification');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('status_badge', function ($row) {
                $progress = (float) ($row->progress ?? 0);
                if ($progress >= 100) {
                    return '<span class="badge bg-success-lt text-success fw-bold px-2 py-1">Finished</span>';
                } elseif ($progress > 0) {
                    return '<span class="badge bg-warning-lt text-warning fw-bold px-2 py-1">Ongoing (' . round($progress) . '%)</span>';
                }

                return '<span class="badge bg-secondary-lt fw-bold px-2 py-1">Not Started</span>';
            })
            ->addColumn('employee_id', function ($row) {
                return htmlspecialchars($row->employee_id ?: '-');
            })
            ->addColumn('participant_name', function ($row) use ($classificationParam) {
                $name = $row->participant_name ?: ($row->user_name ?: '-');
                $identifier = $row->user_id ?: ($row->employee_id ?: null);
                if ($identifier) {
                    $params = ['identifier' => $identifier];
                    if ($classificationParam) {
                        $params['classification'] = $classificationParam;
                    }
                    $url = route('manage.record.offline.participant', $params);
                    return '<div class="fw-semibold text-nowrap"><a href="' . htmlspecialchars($url) . '" class="text-primary text-decoration-none hover-underline participant-link" title="View participant training history">'
                        . htmlspecialchars($name) . '</a></div>';
                }

                return '<div class="fw-semibold text-dark text-nowrap">' . htmlspecialchars($name) . '</div>';
            })
            ->addColumn('grade', function ($row) {
                return htmlspecialchars($row->grade ?: '-');
            })
            ->addColumn('job_position', function ($row) {
                return !empty($row->job_position)
                    ? '<div class="text-truncate" style="max-width: 200px;" title="' . htmlspecialchars($row->job_position) . '">' . htmlspecialchars($row->job_position) . '</div>'
                    : '-';
            })
            ->addColumn('team', function ($row) {
                return htmlspecialchars($row->team ?: '-');
            })
            ->addColumn('department', function ($row) {
                return !empty($row->department)
                    ? '<div class="text-truncate" style="max-width: 200px;" title="' . htmlspecialchars($row->department) . '">' . htmlspecialchars($row->department) . '</div>'
                    : '-';
            })
            ->addColumn('company', function ($row) {
                return htmlspecialchars($row->company ?: '-');
            })
            ->addColumn('course_name', function ($row) {
                return '<div class="fw-semibold text-dark text-nowrap">' . htmlspecialchars($row->course_name ?: '-') . '</div>';
            })
            ->addColumn('sub_course_name', function ($row) {
                return !empty($row->sub_course_name)
                    ? '<div class="text-truncate" style="max-width: 220px;" title="' . htmlspecialchars($row->sub_course_name) . '">' . htmlspecialchars($row->sub_course_name) . '</div>'
                    : '-';
            })
            ->addColumn('type_badge', function ($row) {
                return !empty($row->type)
                    ? '<span class="badge bg-light text-secondary border px-2 py-1">' . htmlspecialchars($row->type) . '</span>'
                    : '-';
            })
            ->addColumn('category', function ($row) {
                return htmlspecialchars($row->category ?: '-');
            })
            ->addColumn('level', function ($row) {
                return htmlspecialchars($row->level ?: '-');
            })
            ->addColumn('batch', function ($row) {
                return htmlspecialchars($row->batch ?: '-');
            })
            ->addColumn('main_classification', function ($row) {
                return htmlspecialchars($row->main_classification ?: '-');
            })
            ->addColumn('sub_classification', function ($row) {
                return htmlspecialchars($row->sub_classification ?: '-');
            })
            ->addColumn('counted_corporate_kpi', function ($row) {
                if ($row->counted_corporate_kpi === null || $row->counted_corporate_kpi === '') {
                    return '<span class="text-muted small">-</span>';
                }
                return $row->counted_corporate_kpi
                    ? '<span class="badge bg-blue-lt text-primary px-2 py-1">Yes</span>'
                    : '<span class="badge bg-light text-muted border px-2 py-1">No</span>';
            })
            ->addColumn('sub_corporate_kpi', function ($row) {
                return htmlspecialchars($row->sub_corporate_kpi ?: '-');
            })
            ->addColumn('delivery_method', function ($row) {
                return htmlspecialchars($row->delivery_method ?: '-');
            })
            ->addColumn('sub_delivery_method', function ($row) {
                return htmlspecialchars($row->sub_delivery_method ?: '-');
            })
            ->addColumn('provider', function ($row) {
                return htmlspecialchars($row->provider ?: '-');
            })
            ->addColumn('sub_provider', function ($row) {
                return htmlspecialchars($row->sub_provider ?: '-');
            })
            ->addColumn('provider_name', function ($row) {
                return htmlspecialchars($row->provider_name ?: '-');
            })
            ->addColumn('venue', function ($row) {
                return htmlspecialchars($row->venue ?: '-');
            })
            ->addColumn('instructor', function ($row) {
                return htmlspecialchars($row->instructor ?: '-');
            })
            ->addColumn('duration', function ($row) {
                if (!empty($row->duration_seconds)) {
                    $secs = (int) $row->duration_seconds;
                    $hours = floor($secs / 3600);
                    $mins = floor(($secs % 3600) / 60);
                    $seconds = $secs % 60;
                    return sprintf('%02d:%02d:%02d', $hours, $mins, $seconds);
                }
                return '-';
            })
            ->addColumn('course_learning_hours', function ($row) {
                if ($row->course_learning_hours !== null && $row->course_learning_hours !== '') {
                    return '<span class="fw-semibold">' . number_format((float) $row->course_learning_hours, 1) . 'h</span>';
                }
                return '-';
            })
            ->addColumn('start_date', function ($row) {
                return $row->start_date ? date('d M Y', strtotime($row->start_date)) : '-';
            })
            ->addColumn('finish_date', function ($row) {
                if ($row->finish_date) {
                    return date('d M Y', strtotime($row->finish_date));
                } elseif ($row->start_date) {
                    return date('d M Y', strtotime($row->start_date));
                }
                return '-';
            })
            ->addColumn('month', function ($row) {
                $dateForMonth = $row->start_date ?: $row->finish_date;
                if (!empty($dateForMonth)) {
                    try {
                        return date('F', strtotime($dateForMonth));
                    } catch (\Throwable $e) {
                        return '-';
                    }
                }
                return '-';
            })
            ->addColumn('learning_hours', function ($row) {
                $hours = (float) ($row->learning_hours ?? 0);
                return '<span class="fw-semibold">' . number_format($hours, 1) . 'h</span>';
            })
            ->addColumn('progress_badge', function ($row) {
                $progress = (float) ($row->progress ?? 0);
                $color = $progress >= 100 ? 'bg-success' : ($progress > 0 ? 'bg-warning' : 'bg-secondary');
                return '<div class="d-flex align-items-center gap-2" style="min-width: 85px;">
                    <div class="progress progress-xs flex-grow-1" style="width: 50px; height: 6px;">
                        <div class="progress-bar ' . $color . '" style="width: ' . min(100, $progress) . '%"></div>
                    </div>
                    <span class="small fw-semibold text-muted">' . round($progress) . '%</span>
                </div>';
            })
            ->addColumn('assessment_score', function ($row) {
                if ($row->assessment_score === null || $row->assessment_score === '') {
                    return '<span class="text-muted small">-</span>';
                }
                return '<span class="fw-semibold">' . number_format((float) $row->assessment_score, 1) . '</span>';
            })
            ->addColumn('certificate_badge', function ($row) {
                if (!empty($row->certificate)) {
                    return '<span class="badge bg-blue-lt text-primary px-2 py-1" title="' . htmlspecialchars($row->certificate) . '"><i class="ti ti-file-certificate me-1"></i> Available</span>';
                }
                return '<span class="text-muted small">-</span>';
            })
            ->addColumn('attendance_badge', function ($row) {
                if ($row->attendance === 1 || $row->attendance === true || $row->attendance === '1') {
                    return '<span class="badge bg-success-lt text-success fw-bold px-2 py-1">Present</span>';
                } elseif ($row->attendance === 0 || $row->attendance === false || $row->attendance === '0') {
                    return '<span class="badge bg-danger-lt text-danger fw-bold px-2 py-1">Absent</span>';
                }

                return '<span class="badge bg-secondary-lt fw-bold px-2 py-1">-</span>';
            })
            ->addColumn('attendance_reason', function ($row) {
                if (!empty($row->attendance_reason)) {
                    return '<div class="text-truncate" style="max-width: 220px;" title="' . htmlspecialchars($row->attendance_reason) . '">' . htmlspecialchars($row->attendance_reason) . '</div>';
                }
                return '-';
            })
            ->addColumn('bond_status', function ($row) {
                if (empty($row->bond_status) || strtolower(trim($row->bond_status)) === 'no') {
                    return '<span class="badge bg-light text-muted border px-2 py-1">No</span>';
                }
                return '<span class="badge bg-warning-lt text-warning fw-bold px-2 py-1">' . htmlspecialchars($row->bond_status) . '</span>';
            })
            ->addColumn('bond_end_date', function ($row) {
                return $row->bond_end_date ? date('d M Y', strtotime($row->bond_end_date)) : '-';
            })
            ->addColumn('bond_amount', function ($row) {
                if ($row->bond_amount !== null && $row->bond_amount !== '') {
                    return '<span class="fw-semibold font-monospace">Rp ' . number_format((float) $row->bond_amount, 0, ',', '.') . '</span>';
                }
                return '-';
            })
            ->addColumn('l1_program', function ($row) {
                return htmlspecialchars($row->l1_program ?: '-');
            })
            ->addColumn('l2_numerical', function ($row) {
                if ($row->l2_numerical !== null && $row->l2_numerical !== '') {
                    return '<span class="fw-semibold">' . number_format((float) $row->l2_numerical, 1) . '</span>';
                }
                return '-';
            })
            ->addColumn('l2_verbal', function ($row) {
                if (!empty($row->l2_verbal)) {
                    return '<div class="text-truncate" style="max-width: 180px;" title="' . htmlspecialchars($row->l2_verbal) . '">' . htmlspecialchars($row->l2_verbal) . '</div>';
                }
                return '-';
            })
            ->addColumn('l3_numerical', function ($row) {
                if ($row->l3_numerical !== null && $row->l3_numerical !== '') {
                    return '<span class="fw-semibold">' . number_format((float) $row->l3_numerical, 1) . '</span>';
                }
                return '-';
            })
            ->addColumn('l3_verbal', function ($row) {
                if (!empty($row->l3_verbal)) {
                    return '<div class="text-truncate" style="max-width: 220px;" title="' . htmlspecialchars($row->l3_verbal) . '">' . htmlspecialchars($row->l3_verbal) . '</div>';
                }
                return '-';
            })
            ->addColumn('pic', function ($row) {
                return htmlspecialchars($row->pic ?: '-');
            })
            ->addColumn('action', function ($row) {
                return '<div class="d-inline-flex align-items-center justify-content-center gap-1">
                    <button type="button" class="btn btn-sm btn-icon btn-outline-primary btn-view-offline-detail rounded-2" 
                            data-id="' . $row->id . '" 
                            title="View Detail" 
                            aria-label="View Detail" 
                            data-bs-toggle="tooltip">
                        <i class="ti ti-eye"></i>
                    </button>
                </div>';
            })
            ->rawColumns([
                'status_badge',
                'participant_name',
                'job_position',
                'department',
                'course_name',
                'sub_course_name',
                'type_badge',
                'counted_corporate_kpi',
                'course_learning_hours',
                'learning_hours',
                'progress_badge',
                'assessment_score',
                'certificate_badge',
                'attendance_badge',
                'attendance_reason',
                'bond_status',
                'bond_amount',
                'l2_numerical',
                'l2_verbal',
                'l3_numerical',
                'l3_verbal',
                'action',
            ])
            ->make(true);
    }

    /**
     * Download Excel export containing active participant records for a specific course/training event.
     */
    public function courseExportExcel($training, Request $request)
    {
        $trainingModel = OfflineTraining::whereNull('deleted_at')->find($training);
        if (!$trainingModel) {
            abort(404, 'Training not found.');
        }

        $export = new OfflineCourseExport($trainingModel, $request);

        return $export->download($export->getFileName());
    }

    /**
     * Download CSV export containing active participant records for a specific course/training event.
     */
    public function courseExportCsv($training, Request $request)
    {
        $trainingModel = OfflineTraining::whereNull('deleted_at')->find($training);
        if (!$trainingModel) {
            abort(404, 'Training not found.');
        }

        $export = new OfflineCourseCsvExport($trainingModel, $request);

        return $export->download($export->getFileName(), \Maatwebsite\Excel\Excel::CSV, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Download PDF landscape export containing active participant records for a specific course/training event.
     */
    public function courseExportPdf($training, Request $request)
    {
        $trainingModel = OfflineTraining::whereNull('deleted_at')->find($training);
        if (!$trainingModel) {
            abort(404, 'Training not found.');
        }

        $records = RecordOfflineQueryService::buildCourseQuery((int) $trainingModel->id, $request->all())
            ->orderBy('otu.name', 'asc')
            ->orderBy('otu.id', 'asc')
            ->get();

        $pdf = Pdf::loadView('main.record.offline.course-export-pdf', [
            'training'     => $trainingModel,
            'records'      => $records,
            'filters'      => $request->all(),
            'generatedAt'  => now()->format('d M Y H:i:s'),
            'totalRecords' => $records->count(),
        ]);

        $slug = Str::slug($trainingModel->course_name) ?: 'course';
        $fileName = sprintf('non-kp-owl-course-%d-%s-%s.pdf', $trainingModel->id, $slug, now()->format('Y-m-d'));

        return $pdf->setPaper('a3', 'landscape')->download($fileName);
    }

    public static function resolveClassification(string $input): array
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

        $dbClasses = [];
        if (Schema::hasTable('offline_trainings')) {
            $dbClassesQuery = DB::table('offline_trainings')
                ->whereNull('deleted_at')
                ->whereNotNull('main_classification')
                ->where('main_classification', '!=', '');
            OfflineTraining::applyRecordScope($dbClassesQuery);
            $dbClasses = $dbClassesQuery
                ->distinct()
                ->pluck('main_classification')
                ->all();
        }

        $candidates = array_unique(array_merge(
            $dbClasses,
            self::MAIN_CLASSIFICATIONS,
            ['Basic & Common Duties', 'Leadership', 'Competency', 'Management', 'Technical', 'Quality & Safety', 'General', 'Innovation', 'ISO Standard', 'Job Functional']
        ));

        foreach ($candidates as $candidate) {
            if (Str::slug($candidate) === $slugified || strtolower(trim($candidate)) === strtolower($trimmed)) {
                return [
                    'name'            => $candidate,
                    'slug'            => Str::slug($candidate),
                    'is_unclassified' => false,
                ];
            }
        }

        return [
            'name'            => ucwords(str_replace('-', ' ', $slugified)),
            'slug'            => $slugified,
            'is_unclassified' => false,
        ];
    }

    public function datatable(Request $request)
    {
        $hasTables = Schema::hasTable('offline_trainings') && Schema::hasTable('offline_training_users');

        if (!$hasTables) {
            return response()->json([
                'draw'            => (int) $request->input('draw', 1),
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => [],
            ]);
        }

        $classificationParam = $request->input('classification');
        $classificationSlug = null;
        if (filled($classificationParam) && $classificationParam !== 'all') {
            $resolvedClass = self::resolveClassification($classificationParam);
            $classificationSlug = $resolvedClass['slug'];
        }

        $query = RecordOfflineQueryService::buildQuery($request->all(), $classificationParam);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('status_badge', function ($row) {
                $progress = (float) ($row->progress ?? 0);
                if ($progress >= 100) {
                    return '<span class="badge bg-success-lt text-success fw-bold px-2 py-1">Finished</span>';
                } elseif ($progress > 0) {
                    return '<span class="badge bg-warning-lt text-warning fw-bold px-2 py-1">Ongoing (' . round($progress) . '%)</span>';
                }

                return '<span class="badge bg-secondary-lt fw-bold px-2 py-1">Not Started</span>';
            })
            ->addColumn('employee_id', function ($row) {
                return htmlspecialchars($row->employee_id ?: '-');
            })
            ->addColumn('participant_name', function ($row) use ($classificationSlug) {
                $name = $row->participant_name ?: ($row->user_name ?: '-');
                $identifier = $row->user_id ?: ($row->employee_id ?: null);
                if ($identifier) {
                    $params = ['identifier' => $identifier];
                    if ($classificationSlug) {
                        $params['classification'] = $classificationSlug;
                    }
                    $url = route('manage.record.offline.participant', $params);
                    return '<div class="fw-semibold text-nowrap"><a href="' . htmlspecialchars($url) . '" class="text-primary text-decoration-none hover-underline participant-link" title="View participant training history">'
                        . htmlspecialchars($name) . '</a></div>';
                }

                return '<div class="fw-semibold text-dark text-nowrap">' . htmlspecialchars($name) . '</div>';
            })
            ->addColumn('employee', function ($row) {
                $name = $row->participant_name ?: ($row->user_name ?: '-');
                $subInfo = [];
                if ($row->job_position) {
                    $subInfo[] = htmlspecialchars($row->job_position);
                }
                if ($row->department) {
                    $subInfo[] = htmlspecialchars($row->department);
                }
                $subHtml = !empty($subInfo)
                    ? '<div class="text-muted extra-small" style="font-size: 0.75rem;">' . implode(' • ', $subInfo) . '</div>'
                    : '';

                return '<div class="fw-semibold">' . htmlspecialchars($name) . '</div>' . $subHtml;
            })
            ->addColumn('grade', function ($row) {
                return htmlspecialchars($row->grade ?: '-');
            })
            ->addColumn('job_position', function ($row) {
                return !empty($row->job_position)
                    ? '<div class="text-truncate" style="max-width: 200px;" title="' . htmlspecialchars($row->job_position) . '">' . htmlspecialchars($row->job_position) . '</div>'
                    : '-';
            })
            ->addColumn('team', function ($row) {
                return htmlspecialchars($row->team ?: '-');
            })
            ->addColumn('department', function ($row) {
                return !empty($row->department)
                    ? '<div class="text-truncate" style="max-width: 200px;" title="' . htmlspecialchars($row->department) . '">' . htmlspecialchars($row->department) . '</div>'
                    : '-';
            })
            ->addColumn('company', function ($row) {
                return htmlspecialchars($row->company ?: '-');
            })
            ->addColumn('course_name', function ($row) use ($classificationSlug) {
                $courseTitle = $row->course_name ?: '-';
                if (!empty($row->offline_training_id)) {
                    $params = ['training' => $row->offline_training_id];
                    if ($classificationSlug) {
                        $params['classification'] = $classificationSlug;
                    }
                    $url = route('manage.record.offline.course', $params);
                    return '<div class="fw-semibold text-truncate" style="max-width: 260px;" title="' . htmlspecialchars($courseTitle) . '">'
                        . '<a href="' . htmlspecialchars($url) . '" class="text-primary text-decoration-none hover-underline course-link">'
                        . htmlspecialchars($courseTitle) . '</a></div>';
                }

                return '<div class="fw-semibold text-dark text-truncate" style="max-width: 260px;" title="' . htmlspecialchars($courseTitle) . '">'
                    . htmlspecialchars($courseTitle) . '</div>';
            })
            ->addColumn('sub_course_name', function ($row) {
                return !empty($row->sub_course_name)
                    ? '<div class="text-truncate" style="max-width: 220px;" title="' . htmlspecialchars($row->sub_course_name) . '">' . htmlspecialchars($row->sub_course_name) . '</div>'
                    : '-';
            })
            ->addColumn('type_badge', function ($row) {
                return '<span class="badge bg-light text-secondary border px-2 py-1">' . htmlspecialchars($row->type ?: 'In-House') . '</span>';
            })
            ->addColumn('category', function ($row) {
                return htmlspecialchars($row->category ?: '-');
            })
            ->addColumn('level', function ($row) {
                return htmlspecialchars($row->level ?: '-');
            })
            ->addColumn('batch', function ($row) {
                return htmlspecialchars($row->batch ?: '-');
            })
            ->addColumn('main_classification', function ($row) {
                return htmlspecialchars($row->main_classification ?: '-');
            })
            ->addColumn('sub_classification', function ($row) {
                return htmlspecialchars($row->sub_classification ?: '-');
            })
            ->addColumn('counted_corporate_kpi', function ($row) {
                if ($row->counted_corporate_kpi === null || $row->counted_corporate_kpi === '') {
                    return '<span class="text-muted small">-</span>';
                }
                $isYes = (bool) $row->counted_corporate_kpi;
                if ($isYes) {
                    $year = !empty($row->corporate_kpi_year) ? $row->corporate_kpi_year : (!empty($row->sub_corporate_kpi) && preg_match('/^\d{4}$/', (string)$row->sub_corporate_kpi) ? $row->sub_corporate_kpi : null);
                    $label = $year ? "Yes ({$year})" : "Yes";
                    return '<span class="badge bg-blue-lt text-primary px-2 py-1">' . htmlspecialchars($label) . '</span>';
                }
                return '<span class="badge bg-light text-muted border px-2 py-1">No</span>';
            })
            ->addColumn('sub_corporate_kpi', function ($row) {
                return htmlspecialchars($row->sub_corporate_kpi ?: '-');
            })
            ->addColumn('delivery_method', function ($row) {
                return htmlspecialchars($row->delivery_method ?: '-');
            })
            ->addColumn('sub_delivery_method', function ($row) {
                return htmlspecialchars($row->sub_delivery_method ?: '-');
            })
            ->addColumn('provider', function ($row) {
                return htmlspecialchars($row->provider ?: '-');
            })
            ->addColumn('sub_provider', function ($row) {
                return htmlspecialchars($row->sub_provider ?: '-');
            })
            ->addColumn('provider_name', function ($row) {
                return htmlspecialchars($row->provider_name ?: '-');
            })
            ->addColumn('venue', function ($row) {
                return htmlspecialchars($row->venue ?: '-');
            })
            ->addColumn('instructor', function ($row) {
                return htmlspecialchars($row->instructor ?: '-') ;
            })
            ->addColumn('duration', function ($row) {
                if (!empty($row->duration_seconds)) {
                    $secs = (int) $row->duration_seconds;
                    $hours = floor($secs / 3600);
                    $mins = floor(($secs % 3600) / 60);
                    $seconds = $secs % 60;
                    return sprintf('%02d:%02d:%02d', $hours, $mins, $seconds);
                }
                return '-';
            })
            ->addColumn('course_learning_hours', function ($row) {
                if ($row->course_learning_hours !== null && $row->course_learning_hours !== '') {
                    return '<span class="fw-semibold">' . number_format((float) $row->course_learning_hours, 1) . 'h</span>';
                }
                return '-';
            })
            ->addColumn('start_date', function ($row) {
                return $row->start_date ? date('d M Y', strtotime($row->start_date)) : '-';
            })
            ->addColumn('finish_date', function ($row) {
                if ($row->finish_date) {
                    return date('d M Y', strtotime($row->finish_date));
                } elseif ($row->start_date) {
                    return date('d M Y', strtotime($row->start_date));
                }

                return '-';
            })
            ->addColumn('month', function ($row) {
                $dateForMonth = $row->start_date ?: $row->finish_date;
                if (!empty($dateForMonth)) {
                    try {
                        return date('F', strtotime($dateForMonth));
                    } catch (\Throwable $e) {
                        return '-';
                    }
                }
                return '-';
            })
            ->addColumn('learning_hours', function ($row) {
                $hours = (float) ($row->learning_hours ?? 0);
                return '<span class="fw-semibold">' . number_format($hours, 1) . 'h</span>';
            })
            ->addColumn('progress_badge', function ($row) {
                $progress = (float) ($row->progress ?? 0);
                $color = $progress >= 100 ? 'bg-success' : ($progress > 0 ? 'bg-warning' : 'bg-secondary');
                return '<div class="d-flex align-items-center gap-2" style="min-width: 85px;">
                    <div class="progress progress-xs flex-grow-1" style="width: 50px; height: 6px;">
                        <div class="progress-bar ' . $color . '" style="width: ' . min(100, $progress) . '%"></div>
                    </div>
                    <span class="small fw-semibold text-muted">' . round($progress) . '%</span>
                </div>';
            })
            ->addColumn('assessment_score', function ($row) {
                if ($row->assessment_score === null || $row->assessment_score === '') {
                    return '<span class="text-muted small">-</span>';
                }

                return '<span class="fw-semibold">' . number_format((float) $row->assessment_score, 1) . '</span>';
            })
            ->addColumn('certificate_badge', function ($row) {
                if (!empty($row->certificate)) {
                    return '<span class="badge bg-blue-lt text-primary px-2 py-1" title="' . htmlspecialchars($row->certificate) . '"><i class="ti ti-file-certificate me-1"></i> Available</span>';
                }

                return '<span class="text-muted small">-</span>';
            })
            ->addColumn('attendance_badge', function ($row) {
                if ($row->attendance === null || $row->attendance === '') {
                    return '<span class="text-muted small">-</span>';
                }

                return $row->attendance
                    ? '<span class="badge bg-success-lt text-success px-2 py-1">Present</span>'
                    : '<span class="badge bg-danger-lt text-danger px-2 py-1" title="' . htmlspecialchars($row->attendance_reason ?: 'Absent') . '">Absent</span>';
            })
            ->addColumn('attendance_reason', function ($row) {
                if (!empty($row->attendance_reason)) {
                    return '<div class="text-truncate" style="max-width: 220px;" title="' . htmlspecialchars($row->attendance_reason) . '">' . htmlspecialchars($row->attendance_reason) . '</div>';
                }
                return '-';
            })
            ->addColumn('bond_status', function ($row) {
                if (empty($row->bond_status) || strtolower(trim($row->bond_status)) === 'no') {
                    return '<span class="badge bg-light text-muted border px-2 py-1">No</span>';
                }
                return '<span class="badge bg-warning-lt text-warning fw-bold px-2 py-1">' . htmlspecialchars($row->bond_status) . '</span>';
            })
            ->addColumn('bond_end_date', function ($row) {
                return $row->bond_end_date ? date('d M Y', strtotime($row->bond_end_date)) : '-';
            })
            ->addColumn('bond_amount', function ($row) {
                if ($row->bond_amount !== null && $row->bond_amount !== '') {
                    return '<span class="fw-semibold font-monospace">Rp ' . number_format((float) $row->bond_amount, 0, ',', '.') . '</span>';
                }
                return '-';
            })
            ->addColumn('l1_program', function ($row) {
                return htmlspecialchars($row->l1_program ?: '-');
            })
            ->addColumn('l2_numerical', function ($row) {
                if ($row->l2_numerical !== null && $row->l2_numerical !== '') {
                    return '<span class="fw-semibold">' . number_format((float) $row->l2_numerical, 1) . '</span>';
                }
                return '-';
            })
            ->addColumn('l2_verbal', function ($row) {
                if (!empty($row->l2_verbal)) {
                    return '<div class="text-truncate" style="max-width: 180px;" title="' . htmlspecialchars($row->l2_verbal) . '">' . htmlspecialchars($row->l2_verbal) . '</div>';
                }
                return '-';
            })
            ->addColumn('l3_numerical', function ($row) {
                if ($row->l3_numerical !== null && $row->l3_numerical !== '') {
                    return '<span class="fw-semibold">' . number_format((float) $row->l3_numerical, 1) . '</span>';
                }
                return '-';
            })
            ->addColumn('l3_verbal', function ($row) {
                if (!empty($row->l3_verbal)) {
                    return '<div class="text-truncate" style="max-width: 220px;" title="' . htmlspecialchars($row->l3_verbal) . '">' . htmlspecialchars($row->l3_verbal) . '</div>';
                }
                return '-';
            })
            ->addColumn('pic', function ($row) {
                return htmlspecialchars($row->pic ?: '-');
            })
            ->addColumn('action', function ($row) {
                $name = $row->participant_name ?: ($row->user_name ?: 'Participant');
                $course = $row->course_name ?: 'Training';
                $editUrl = route('manage.record.offline.edit', $row->id);

                return '<div class="d-inline-flex align-items-center justify-content-center gap-1">
                    <button type="button" class="btn btn-sm btn-icon btn-outline-primary btn-view-offline-detail rounded-2" 
                            data-id="' . $row->id . '" 
                            title="View Detail" 
                            aria-label="View Detail" 
                            data-bs-toggle="tooltip">
                        <i class="ti ti-eye"></i>
                    </button>
                    <a href="' . $editUrl . '" class="btn btn-sm btn-icon btn-outline-warning rounded-2 btn-edit-offline-record" 
                        title="Edit Record" 
                        aria-label="Edit Record" 
                        data-bs-toggle="tooltip">
                        <i class="ti ti-edit"></i>
                    </a>
                    <button type="button" class="btn btn-sm btn-icon btn-outline-danger btn-delete-offline-record rounded-2" 
                            data-id="' . $row->id . '" 
                            data-employee="' . htmlspecialchars($name, ENT_QUOTES) . '" 
                            data-course="' . htmlspecialchars($course, ENT_QUOTES) . '" 
                            title="Delete Record" 
                            aria-label="Delete Record" 
                            data-bs-toggle="tooltip">
                        <i class="ti ti-trash"></i>
                    </button>
                </div>';
            })
            ->rawColumns([
                'status_badge',
                'participant_name',
                'employee',
                'job_position',
                'department',
                'course_name',
                'sub_course_name',
                'type_badge',
                'counted_corporate_kpi',
                'course_learning_hours',
                'learning_hours',
                'progress_badge',
                'attendance_badge',
                'attendance_reason',
                'assessment_score',
                'certificate_badge',
                'bond_status',
                'bond_amount',
                'l2_numerical',
                'l2_verbal',
                'l3_numerical',
                'l3_verbal',
                'action',
            ])
            ->make(true);
    }

    public function detail($id): JsonResponse
    {
        $hasTables = Schema::hasTable('offline_trainings') && Schema::hasTable('offline_training_users');

        if (!$hasTables) {
            return response()->json([
                'success' => false,
                'message' => 'Offline training tables not migrated yet.',
            ], 404);
        }

        $record = DB::table('offline_training_users as otu')
            ->join('offline_trainings as ot', 'ot.id', '=', 'otu.offline_training_id')
            ->leftJoin('users as u', 'u.id', '=', 'otu.user_id')
            ->where('otu.id', $id)
            ->whereNull('otu.deleted_at')
            ->select([
                // Participant Info
                'otu.id',
                'otu.offline_training_id',
                'otu.user_id',
                'otu.employee_id',
                'otu.name as participant_name',
                'otu.grade',
                'otu.job_position',
                'otu.team',
                'otu.department',
                'otu.company',
                // Progress & Evaluation
                'otu.learning_hours as participant_learning_hours',
                'otu.progress',
                'otu.assessment_score',
                'otu.certificate',
                'otu.attendance',
                'otu.attendance_reason',
                // Bond & L1-L3
                'otu.bond_status',
                'otu.bond_end_date',
                'otu.bond_amount',
                'otu.l1_program',
                'otu.l2_numerical',
                'otu.l2_verbal',
                'otu.l3_numerical',
                'otu.l3_verbal',
                'otu.pic',
                'otu.created_at',
                // Course Info
                'ot.course_name',
                'ot.sub_course_name',
                'ot.type',
                'ot.category',
                'ot.level',
                'ot.batch',
                // Classification & KPI
                'ot.main_classification',
                'ot.sub_classification',
                'ot.counted_corporate_kpi',
                'ot.corporate_kpi_year',
                'ot.sub_corporate_kpi',
                // Delivery & Provider
                'ot.delivery_method',
                'ot.sub_delivery_method',
                'ot.provider',
                'ot.sub_provider',
                'ot.provider_name',
                'ot.venue',
                'ot.instructor',
                // Duration & Schedule
                'ot.duration_seconds',
                'ot.learning_hours as course_learning_hours',
                'ot.start_date',
                'ot.finish_date',
                'u.name as user_name',
            ])
            ->first();

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Training record not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $record,
        ]);
    }

    /**
     * Search employees for adding participants in manual Add Training modal.
     */
    public function searchEmployees(Request $request): JsonResponse
    {
        $query = trim((string) ($request->get('q') ?? ($request->get('search') ?? '')));
        if ($query === '') {
            return response()->json(['results' => []]);
        }

        $users = DB::table('users')
            ->whereNull('deleted_at')
            ->where(function ($q) use ($query) {
                $q->where('id_emp', 'LIKE', "%{$query}%")
                  ->orWhere('name', 'LIKE', "%{$query}%");
            })
            ->limit(20)
            ->get(['id', 'id_emp', 'name', 'team', 'department', 'company_name']);

        $results = $users->map(function ($u) {
            return [
                'id'          => $u->id,
                'user_id'     => $u->id,
                'employee_id' => $u->id_emp,
                'name'        => $u->name,
                'grade'       => '',
                'job_position'=> '',
                'team'        => $u->team ?? '',
                'department'  => $u->department ?? '',
                'company'     => $u->company_name ?? 'PT Krakatau Posco',
            ];
        });

        return response()->json(['results' => $results]);
    }

    /**
     * Store new offline training and its enrolled participants inside a database transaction.
     */
    public function store(Request $request, OfflineTrainingCreateService $createService): JsonResponse
    {
        try {
            $training = $createService->create($request->all(), auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Offline training successfully saved.',
                'data'    => [
                    'id'                 => $training->id,
                    'course_name'        => $training->course_name,
                    'batch'              => $training->batch,
                    'participants_count' => $training->participants()->count(),
                ],
            ], 201);
        } catch (ValidationException $ve) {
            return response()->json([
                'success' => false,
                'message' => $ve->getMessage(),
                'errors'  => $ve->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save offline training: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show dedicated edit page for an offline training record.
     */
    public function edit($id, OfflineOptionService $optionService)
    {
        $hasTables = Schema::hasTable('offline_trainings') && Schema::hasTable('offline_training_users');
        if (!$hasTables) {
            abort(404, 'Offline training tables not migrated.');
        }

        $record = OfflineTrainingUser::with('training')->find($id);

        if (!$record || !$record->training) {
            abort(404, 'Offline training record not found.');
        }

        $training = $record->training;
        $resolved = self::resolveClassification($training->main_classification ?? 'unclassified');
        $kpiYear = $training->corporate_kpi_year ?: ($training->start_date ? (int) $training->start_date->format('Y') : (int) date('Y'));
        $options = $optionService->getGroupedOptions($kpiYear, true);
        $kpiYears = $optionService->getKpiYears();
        if (!in_array($kpiYear, $kpiYears, true)) {
            $kpiYears[] = $kpiYear;
            rsort($kpiYears);
        }
        $classifications = Classification::orderBy('classification_name', 'asc')->get();

        return view('main.record.offline-edit', [
            'title'               => 'Edit Non KP OWL Record',
            'subtitle'            => 'Update offline employee training record and results.',
            'record'              => $record,
            'training'            => $training,
            'classification'      => $resolved,
            'classifications'     => $classifications,
            'options'             => $options,
            'kpiYear'             => $kpiYear,
            'kpiYears'            => $kpiYears,
            'trainingTypes'       => self::TRAINING_TYPES,
            'mainClassifications' => $classifications->pluck('classification_name')->all(),
        ]);
    }

    /**
     * Update an offline training record and its participant data.
     */
    public function update($id, Request $request, OfflineTrainingUpdateService $updateService)
    {
        $record = OfflineTrainingUser::with('training')->find($id);

        if (!$record || !$record->training) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Offline training record not found.',
                ], 404);
            }
            abort(404, 'Offline training record not found.');
        }

        try {
            $updatedRecord = $updateService->update($record, $request->all());
            $resolved = self::resolveClassification($updatedRecord->training->main_classification ?? 'unclassified');
            $redirectUrl = route('manage.record.offline.classification', $resolved['slug']);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success'      => true,
                    'message'      => 'Offline training record updated successfully.',
                    'redirect_url' => $redirectUrl,
                    'data'         => [
                        'id'                  => $updatedRecord->id,
                        'course_name'         => $updatedRecord->training->course_name,
                        'participant_name'    => $updatedRecord->name,
                        'classification_slug' => $resolved['slug'],
                    ],
                ]);
            }

            return redirect($redirectUrl)->with('success', 'Training record successfully updated.');
        } catch (ValidationException $ve) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $ve->getMessage(),
                    'errors'  => $ve->errors(),
                ], 422);
            }

            return redirect()->back()->withErrors($ve->errors())->withInput();
        } catch (\Throwable $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update record: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to update record: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Soft-delete an offline training participant record.
     * If this was the last participant, also soft-deletes the parent training.
     */
    public function destroy($id): JsonResponse
    {
        $hasTables = Schema::hasTable('offline_trainings') && Schema::hasTable('offline_training_users');
        if (!$hasTables) {
            return response()->json([
                'success' => false,
                'message' => 'Offline training tables not migrated.',
            ], 404);
        }

        $record = OfflineTrainingUser::with('training')->find($id);

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Training record not found.',
            ], 404);
        }

        $training = $record->training;

        DB::transaction(function () use ($record, $training) {
            $record->delete();

            if ($training && $training->participants()->count() === 0) {
                $training->delete();
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Record successfully deleted.',
        ]);
    }
}
