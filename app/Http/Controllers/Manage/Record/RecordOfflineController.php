<?php

namespace App\Http\Controllers\Manage\Record;

use App\Http\Controllers\Controller;
use App\Http\Controllers\LearningManageController;
use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Services\Record\OfflineTrainingCreateService;
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

    public function create()
    {
        return view('main.record.offline-create', [
            'title'               => 'Add Non KP OWL Training',
            'subtitle'            => 'Create an offline employee training record.',
            'trainingTypes'       => self::TRAINING_TYPES,
            'mainClassifications' => self::MAIN_CLASSIFICATIONS,
        ]);
    }

    public function ajaxSummary(Request $request): JsonResponse
    {
        $hasTables = Schema::hasTable('offline_trainings') && Schema::hasTable('offline_training_users');

        if ($hasTables) {
            $totalTrainings = (int) DB::table('offline_trainings')->whereNull('deleted_at')->count();
            $totalParticipants = (int) DB::table('offline_training_users')->whereNull('deleted_at')->count();
            $totalHours = (float) DB::table('offline_training_users')->whereNull('deleted_at')->sum('learning_hours');
            $finished = (int) DB::table('offline_training_users')
                ->whereNull('deleted_at')
                ->where('progress', '>=', 100)
                ->count();
            $ongoing = (int) DB::table('offline_training_users')
                ->whereNull('deleted_at')
                ->where(function ($q) {
                    $q->where('progress', '<', 100)
                      ->orWhereNull('progress');
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

        $records = DB::table('offline_trainings as ot')
            ->leftJoin('offline_training_users as otu', function ($join) {
                $join->on('otu.offline_training_id', '=', 'ot.id')
                    ->whereNull('otu.deleted_at');
            })
            ->whereNull('ot.deleted_at')
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
        ]);
    }

    public function classificationDatatable(string $classification, Request $request)
    {
        $request->merge(['classification' => $classification]);

        return $this->datatable($request);
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
            $dbClasses = DB::table('offline_trainings')
                ->whereNull('deleted_at')
                ->whereNotNull('main_classification')
                ->where('main_classification', '!=', '')
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

        $query = DB::table('offline_training_users as otu')
            ->join('offline_trainings as ot', 'ot.id', '=', 'otu.offline_training_id')
            ->leftJoin('users as u', 'u.id', '=', 'otu.user_id')
            ->whereNull('otu.deleted_at')
            ->whereNull('ot.deleted_at')
            ->select([
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
                'otu.learning_hours',
                'otu.progress',
                'otu.assessment_score',
                'otu.certificate',
                'otu.attendance',
                'otu.attendance_reason',
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
                'ot.course_name',
                'ot.sub_course_name',
                'ot.type',
                'ot.category',
                'ot.level',
                'ot.batch',
                'ot.main_classification',
                'ot.sub_classification',
                'ot.counted_corporate_kpi',
                'ot.sub_corporate_kpi',
                'ot.delivery_method',
                'ot.sub_delivery_method',
                'ot.provider',
                'ot.sub_provider',
                'ot.provider_name',
                'ot.venue',
                'ot.instructor',
                'ot.duration_seconds',
                'ot.learning_hours as course_learning_hours',
                'ot.start_date',
                'ot.finish_date',
                'u.name as user_name',
            ]);

        // 1. Filter: Employee Search (ID or Name)
        $employeeParam = trim((string) ($request->input('employee_id') ?? ($request->input('search_employee') ?? '')));
        if ($employeeParam !== '') {
            $query->where(function ($q) use ($employeeParam) {
                $q->where('otu.employee_id', 'like', "%{$employeeParam}%")
                  ->orWhere('otu.name', 'like', "%{$employeeParam}%")
                  ->orWhere('u.name', 'like', "%{$employeeParam}%");
            });
        }

        // 2. Filter: Course Name
        $courseParam = trim((string) ($request->input('course_name') ?? ($request->input('training_title') ?? ($request->input('title') ?? ''))));
        if ($courseParam !== '') {
            $query->where(function ($q) use ($courseParam) {
                $q->where('ot.course_name', 'like', "%{$courseParam}%")
                  ->orWhere('ot.sub_course_name', 'like', "%{$courseParam}%");
            });
        }

        // 3. Filter: Type
        $typeParam = trim((string) ($request->input('type') ?? $request->input('training_type', 'all')));
        if ($typeParam !== '' && $typeParam !== 'all') {
            $query->where('ot.type', $typeParam);
        }

        // 4. Filter: Main Classification
        $classParam = trim((string) ($request->input('main_classification') ?? ($request->input('classification', 'all'))));
        if ($classParam !== '' && $classParam !== 'all') {
            $resolved = self::resolveClassification($classParam);
            if ($resolved['is_unclassified']) {
                $query->where(function ($q) {
                    $q->whereNull('ot.main_classification')
                      ->orWhere('ot.main_classification', '');
                });
            } else {
                $query->where('ot.main_classification', $resolved['name']);
            }
        }

        // 5. Filter: Status (derived from progress)
        $statusParam = trim((string) $request->input('status', 'all'));
        if ($statusParam !== '' && $statusParam !== 'all') {
            if ($statusParam === 'finished' || $statusParam === '1') {
                $query->where('otu.progress', '>=', 100);
            } elseif ($statusParam === 'ongoing' || $statusParam === '0') {
                $query->where(function ($q) {
                    $q->where('otu.progress', '<', 100)
                      ->orWhereNull('otu.progress');
                });
            }
        }

        // 6. Filter: Year (from finish_date or start_date)
        $yearParam = trim((string) $request->input('year', 'all'));
        if ($yearParam !== '' && $yearParam !== 'all') {
            $yearInt = (int) $yearParam;
            $query->where(function ($q) use ($yearInt) {
                $q->whereYear('ot.finish_date', $yearInt)
                  ->orWhere(function ($q2) use ($yearInt) {
                      $q2->whereNull('ot.finish_date')->whereYear('ot.start_date', $yearInt);
                  });
            });
        }

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
            ->addColumn('course_name', function ($row) {
                $sub = $row->sub_course_name
                    ? '<div class="text-muted extra-small" style="font-size: 0.75rem;">' . htmlspecialchars($row->sub_course_name) . '</div>'
                    : '';

                return '<div class="fw-semibold text-truncate" style="max-width: 240px;" title="' . htmlspecialchars($row->course_name) . '">'
                    . htmlspecialchars($row->course_name) . '</div>' . $sub;
            })
            ->addColumn('type_badge', function ($row) {
                return '<span class="badge bg-light text-secondary border px-2 py-1">' . htmlspecialchars($row->type ?: 'In-House') . '</span>';
            })
            ->addColumn('main_classification', function ($row) {
                return htmlspecialchars($row->main_classification ?: '-');
            })
            ->addColumn('batch', function ($row) {
                return htmlspecialchars($row->batch ?: '-');
            })
            ->addColumn('learning_hours', function ($row) {
                $hours = (float) ($row->learning_hours ?? 0);
                return '<span class="fw-semibold">' . number_format($hours, 1) . 'h</span>';
            })
            ->addColumn('progress_badge', function ($row) {
                $progress = (float) ($row->progress ?? 0);
                $color = $progress >= 100 ? 'bg-success' : ($progress > 0 ? 'bg-warning' : 'bg-secondary');
                return '<div class="d-flex align-items-center gap-2">
                    <div class="progress progress-xs flex-grow-1" style="width: 55px; height: 6px;">
                        <div class="progress-bar ' . $color . '" style="width: ' . min(100, $progress) . '%"></div>
                    </div>
                    <span class="small fw-semibold text-muted">' . round($progress) . '%</span>
                </div>';
            })
            ->addColumn('finish_date', function ($row) {
                if ($row->finish_date) {
                    return date('d M Y', strtotime($row->finish_date));
                } elseif ($row->start_date) {
                    return date('d M Y', strtotime($row->start_date));
                }

                return '-';
            })
            ->addColumn('attendance_badge', function ($row) {
                if ($row->attendance === null) {
                    return '<span class="text-muted small">-</span>';
                }

                return $row->attendance
                    ? '<span class="badge bg-success-lt text-success px-2 py-1">Present</span>'
                    : '<span class="badge bg-danger-lt text-danger px-2 py-1" title="' . htmlspecialchars($row->attendance_reason ?: 'Absent') . '">Absent</span>';
            })
            ->addColumn('assessment_score', function ($row) {
                if ($row->assessment_score === null) {
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
            ->addColumn('action', function ($row) {
                return '<button type="button" class="btn btn-sm btn-outline-primary btn-view-offline-detail px-2.5 py-1" data-id="' . $row->id . '">
                    <i class="ti ti-eye me-1"></i> Detail
                </button>';
            })
            ->rawColumns(['status_badge', 'employee', 'course_name', 'type_badge', 'learning_hours', 'progress_badge', 'attendance_badge', 'assessment_score', 'certificate_badge', 'action'])
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
}
