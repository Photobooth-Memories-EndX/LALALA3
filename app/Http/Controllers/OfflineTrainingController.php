<?php

namespace App\Http\Controllers;

use App\Exports\TrainingScheduleExport;
use App\Models\Classification;
use App\Models\OfflineTraining;
use App\Models\OfflineTrainingOption;
use App\Models\OfflineTrainingRegister;
use App\Models\OfflineTrainingUser;
use App\Services\Record\OfflineOptionService;
use App\Services\Record\TrainingOptionManagementService;
use App\Services\Schedule\OfflineTrainingCompletionService;
use App\Services\Schedule\OfflineTrainingRegistrationService;
use App\Services\Schedule\TrainingScheduleImageService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OfflineTrainingController extends Controller
{
    protected OfflineOptionService $offlineOptionService;
    protected TrainingOptionManagementService $optionManager;

    public function __construct(
        OfflineOptionService $offlineOptionService,
        TrainingOptionManagementService $optionManager
    ) {
        $this->offlineOptionService = $offlineOptionService;
        $this->optionManager = $optionManager;
    }
    /**
     * Build base query for OfflineTraining with Category, Status, Month, Year, and Search filters.
     */
    public function buildFilteredScheduleQuery(Request $request)
    {
        $query = OfflineTraining::with(['registrations.user', 'creator'])
            ->withCount([
                'registrations',
                'registrations as pending_count' => function ($q) {
                    $q->where('status', 'Pending');
                },
                'registrations as approved_count' => function ($q) {
                    $q->where('status', 'Approved');
                },
                'registrations as completed_count' => function ($q) {
                    $q->where('status', 'Completed');
                },
                'registrations as rejected_count' => function ($q) {
                    $q->where('status', 'Rejected');
                },
                'registrations as active_seats_count' => function ($q) {
                    $q->whereIn('status', ['Pending', 'Approved', 'Completed']);
                },
            ]);

        // Filter Category
        if ($request->filled('category') && $request->input('category') !== 'all') {
            $query->where('category', $request->input('category'));
        }

        // Filter Status
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $statusVal = strtolower($request->input('status'));
            $statusMap = [
                'open'        => 'Open Registration',
                'running'     => 'Running',
                'closed'      => 'Closed',
                'draft'       => 'Draft',
                'unscheduled' => 'Unscheduled',
            ];
            $query->where('status', $statusMap[$statusVal] ?? $request->input('status'));
        }

        // Filter Month / Period / Week
        $monthVal = $request->input('month');
        $periodVal = $request->input('period');
        if (in_array(strtolower((string)$monthVal), ['this_week', 'weekly'], true) || in_array(strtolower((string)$periodVal), ['this_week', 'weekly'], true)) {
            $startOfWeek = Carbon::now()->startOfWeek();
            $endOfWeek = Carbon::now()->endOfWeek();
            $query->where(function ($q) use ($startOfWeek, $endOfWeek) {
                $q->whereBetween('start_date', [$startOfWeek, $endOfWeek])
                  ->orWhereBetween('end_date', [$startOfWeek, $endOfWeek]);
            });
        } elseif ($request->filled('month') && $request->input('month') !== 'all') {
            $month = (int) $request->input('month');
            $query->where(function ($q) use ($month) {
                $q->whereMonth('start_date', $month)
                  ->orWhereMonth('end_date', $month);
            });
        }

        // Filter Year
        if ($request->filled('year') && $request->input('year') !== 'all') {
            $year = (int) $request->input('year');
            $query->where(function ($q) use ($year) {
                $q->whereYear('start_date', $year)
                  ->orWhereYear('end_date', $year);
            });
        }

        // Keyword Search (q or search)
        $search = $request->input('q') ?? $request->input('search');
        if (filled($search)) {
            $search = trim($search);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('course_name', 'like', "%{$search}%")
                  ->orWhere('sub_course_name', 'like', "%{$search}%")
                  ->orWhere('instructor', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('venue', 'like', "%{$search}%")
                  ->orWhere('training_code', 'like', "%{$search}%")
                  ->orWhere('batch', 'like', "%{$search}%");
            });
        }

        return $query->latest();
    }

    /**
     * Collect human-readable active filter labels for report headers.
     */
    protected function collectActiveFilterLabels(Request $request): array
    {
        $labels = [];

        if ($request->filled('category') && $request->input('category') !== 'all') {
            $labels['Category'] = $request->input('category');
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $statusMap = [
                'open'    => 'Open Registration',
                'running' => 'Running',
                'closed'  => 'Closed',
                'draft'   => 'Draft',
            ];
            $labels['Status'] = $statusMap[strtolower($request->input('status'))] ?? $request->input('status');
        }

        $monthVal = $request->input('month');
        $periodVal = $request->input('period');
        if (in_array(strtolower((string)$monthVal), ['this_week', 'weekly'], true) || in_array(strtolower((string)$periodVal), ['this_week', 'weekly'], true)) {
            $labels['Period'] = 'This Week';
        } elseif ($request->filled('month') && $request->input('month') !== 'all') {
            $monthNum = (int) $request->input('month');
            if ($monthNum >= 1 && $monthNum <= 12) {
                $labels['Month'] = Carbon::create(2026, $monthNum, 1)->format('F');
            }
        }

        if ($request->filled('year') && $request->input('year') !== 'all') {
            $labels['Year'] = $request->input('year');
        }

        $search = $request->input('q') ?? $request->input('search');
        if (filled($search)) {
            $labels['Search'] = trim($search);
        }

        return $labels;
    }

    /**
     * Display Manage Training Schedule page with live records and live participant tracking.
     */
    public function index(Request $request)
    {
        $query = $this->buildFilteredScheduleQuery($request);

        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $schedules = $query->paginate($perPage)->withQueryString();

        $trainings = $schedules;
        $totalSchedules = $schedules->total();

        $selectedTrainingId = $request->query('training');
        $selectedTraining = null;
        if ($selectedTrainingId) {
            $selectedTraining = $trainings->firstWhere('id', (int) $selectedTrainingId);
        }
        if (!$selectedTraining && $trainings->isNotEmpty()) {
            $selectedTraining = $trainings->first();
        }

        $viewName = view()->exists('manage.training-schedule.index')
            ? 'manage.training-schedule.index'
            : 'main.manage-training-schedule.index';

        return view($viewName, compact('trainings', 'selectedTraining', 'schedules', 'totalSchedules'));
    }

    /**
     * Gather master classifications, dynamic options, and parent-child mappings for schedule forms.
     */
    /**
     * Gather master classifications, dynamic options, and parent-child mappings for schedule forms.
     */
    protected function getScheduleFormData(?OfflineTraining $training = null): array
    {
        $classifications = Classification::orderBy('classification_name', 'asc')->get();

        // 1. Single grouped query to fetch all required master options efficiently
        $targetTypes = [
            OfflineOptionService::TYPE_COURSE_NAME,
            OfflineOptionService::TYPE_SUB_COURSE_NAME,
            OfflineOptionService::TYPE_INSTRUCTOR,
            OfflineOptionService::TYPE_VENUE,
            OfflineOptionService::TYPE_TARGET_EMPLOYEE,
            OfflineOptionService::TYPE_PREREQUISITES,
            OfflineOptionService::TYPE_TYPE,
            OfflineOptionService::TYPE_CATEGORY,
            OfflineOptionService::TYPE_LEVEL,
            OfflineOptionService::TYPE_SUB_CLASSIFICATION,
            'title',
            'location',
            'target_audience',
            'requirements',
            'course_completion_requirement',
            'completion_requirement',
            'participant_requirement',
        ];

        $optionsGrouped = OfflineTrainingOption::active()
            ->whereIn('option_type', $targetTypes)
            ->orderBy('sort_order', 'asc')
            ->orderBy('value', 'asc')
            ->get()
            ->groupBy('option_type');

        // Sub Classification mappings per Main Classification from offline_trainings records
        $subClassificationMap = OfflineTraining::whereNotNull('main_classification')
            ->whereNotNull('sub_classification')
            ->where('main_classification', '!=', '')
            ->where('sub_classification', '!=', '')
            ->select('main_classification', 'sub_classification')
            ->distinct()
            ->get()
            ->groupBy('main_classification')
            ->map(function ($items) {
                return $items->pluck('sub_classification')->unique()->values()->all();
            })
            ->all();

        // Sub-classifications
        $subClassifications = $optionsGrouped->get(OfflineOptionService::TYPE_SUB_CLASSIFICATION, collect())
            ->unique('value')
            ->values();
        $existingSubClassNames = OfflineTraining::whereNotNull('sub_classification')
            ->where('sub_classification', '!=', '')
            ->distinct()
            ->pluck('sub_classification')
            ->all();
        $subClassValues = $subClassifications->pluck('value')->all();
        foreach ($existingSubClassNames as $sName) {
            $sTrimmed = trim((string) $sName);
            if ($sTrimmed !== '' && !in_array($sTrimmed, $subClassValues, true)) {
                $subClassifications->push((object)[
                    'id'    => null,
                    'value' => $sTrimmed,
                    'text'  => $sTrimmed,
                ]);
                $subClassValues[] = $sTrimmed;
            }
        }
        $subClassifications = $subClassifications->sortBy('value')->values();

        // Categories
        $categories = $optionsGrouped->get(OfflineOptionService::TYPE_CATEGORY, collect())
            ->unique('value')
            ->values();
        $existingCatNames = OfflineTraining::whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category')
            ->all();
        $standardDefaults = [
            'Compliance & Safety', 'Core Competency', 'Environmental', 'General Competency',
            'Innovation', 'Job Functional', 'Leadership', 'Maintenance', 'Quality', 'Safety',
            'Soft Skill', 'Technical'
        ];
        $allCatCandidates = array_unique(array_merge($existingCatNames, $standardDefaults));
        $catValues = $categories->pluck('value')->all();
        foreach ($allCatCandidates as $cName) {
            $cTrimmed = trim((string) $cName);
            if ($cTrimmed !== '' && !in_array($cTrimmed, $catValues, true)) {
                $categories->push((object)[
                    'id'    => null,
                    'value' => $cTrimmed,
                    'text'  => $cTrimmed,
                ]);
                $catValues[] = $cTrimmed;
            }
        }
        $categories = $categories->sortBy('value')->values();

        // Levels
        $levels = $optionsGrouped->get(OfflineOptionService::TYPE_LEVEL, collect())
            ->unique('value')
            ->values();
        $lvlValues = $levels->pluck('value')->all();
        foreach (['Basic', 'Intermediate', 'Advanced', 'All'] as $lName) {
            if (!in_array($lName, $lvlValues, true)) {
                $levels->push((object)[
                    'id'    => null,
                    'value' => $lName,
                    'text'  => $lName,
                ]);
                $lvlValues[] = $lName;
            }
        }

        // Types
        $types = $optionsGrouped->get(OfflineOptionService::TYPE_TYPE, collect())
            ->unique('value')
            ->values();
        $typeValues = $types->pluck('value')->all();
        foreach (['In-House', 'Public', 'Workshop', 'Certification'] as $tName) {
            if (!in_array($tName, $typeValues, true)) {
                $types->push((object)[
                    'id'    => null,
                    'value' => $tName,
                    'text'  => $tName,
                ]);
                $typeValues[] = $tName;
            }
        }

        // Field 1: Training Title (course_name)
        $titleOptions = $optionsGrouped->get(OfflineOptionService::TYPE_COURSE_NAME, collect())
            ->merge($optionsGrouped->get('title', collect()))
            ->unique('value')
            ->values();
        $existingTitles = OfflineTraining::whereNotNull('title')
            ->where('title', '!=', '')
            ->distinct()
            ->pluck('title')
            ->all();
        $existingCourses = OfflineTraining::whereNotNull('course_name')
            ->where('course_name', '!=', '')
            ->distinct()
            ->pluck('course_name')
            ->all();
        $titleValues = $titleOptions->pluck('value')->all();
        foreach (array_unique(array_merge($existingTitles, $existingCourses)) as $tName) {
            $tTrimmed = trim((string) $tName);
            if ($tTrimmed !== '' && !in_array($tTrimmed, $titleValues, true)) {
                $titleOptions->push((object)[
                    'id'    => null,
                    'value' => $tTrimmed,
                    'text'  => $tTrimmed,
                ]);
                $titleValues[] = $tTrimmed;
            }
        }
        $titleOptions = $titleOptions->sortBy('value')->values();

        // Field 2: Sub Course Name
        $subCourseOptions = $optionsGrouped->get(OfflineOptionService::TYPE_SUB_COURSE_NAME, collect())
            ->unique('value')
            ->values();
        $existingSubCourses = OfflineTraining::whereNotNull('sub_course_name')
            ->where('sub_course_name', '!=', '')
            ->distinct()
            ->pluck('sub_course_name')
            ->all();
        $subCourseValues = $subCourseOptions->pluck('value')->all();
        foreach ($existingSubCourses as $scName) {
            $scTrimmed = trim((string) $scName);
            if ($scTrimmed !== '' && !in_array($scTrimmed, $subCourseValues, true)) {
                $subCourseOptions->push((object)[
                    'id'    => null,
                    'value' => $scTrimmed,
                    'text'  => $scTrimmed,
                ]);
                $subCourseValues[] = $scTrimmed;
            }
        }
        $subCourseOptions = $subCourseOptions->sortBy('value')->values();

        // Field 3: Instructor / Trainer
        $instructorOptions = $optionsGrouped->get(OfflineOptionService::TYPE_INSTRUCTOR, collect())
            ->unique('value')
            ->values();
        $existingInstructors = OfflineTraining::whereNotNull('instructor')
            ->where('instructor', '!=', '')
            ->distinct()
            ->pluck('instructor')
            ->all();
        $instructorValues = $instructorOptions->pluck('value')->all();
        foreach ($existingInstructors as $iName) {
            $iTrimmed = trim((string) $iName);
            if ($iTrimmed !== '' && !in_array($iTrimmed, $instructorValues, true)) {
                $instructorOptions->push((object)[
                    'id'    => null,
                    'value' => $iTrimmed,
                    'text'  => $iTrimmed,
                ]);
                $instructorValues[] = $iTrimmed;
            }
        }
        $instructorOptions = $instructorOptions->sortBy('value')->values();

        // Field 4: Location / Venue
        $locationOptions = $optionsGrouped->get(OfflineOptionService::TYPE_VENUE, collect())
            ->merge($optionsGrouped->get('location', collect()))
            ->unique('value')
            ->values();
        $existingLocations = OfflineTraining::whereNotNull('location')
            ->where('location', '!=', '')
            ->distinct()
            ->pluck('location')
            ->all();
        $existingVenues = OfflineTraining::whereNotNull('venue')
            ->where('venue', '!=', '')
            ->distinct()
            ->pluck('venue')
            ->all();
        $locationValues = $locationOptions->pluck('value')->all();
        foreach (array_unique(array_merge($existingLocations, $existingVenues)) as $lName) {
            $lTrimmed = trim((string) $lName);
            if ($lTrimmed !== '' && !in_array($lTrimmed, $locationValues, true)) {
                $locationOptions->push((object)[
                    'id'    => null,
                    'value' => $lTrimmed,
                    'text'  => $lTrimmed,
                ]);
                $locationValues[] = $lTrimmed;
            }
        }
        $locationOptions = $locationOptions->sortBy('value')->values();

        // Field 5: Target Employee
        $targetEmployeeOptions = $optionsGrouped->get(OfflineOptionService::TYPE_TARGET_EMPLOYEE, collect())
            ->merge($optionsGrouped->get('target_audience', collect()))
            ->unique('value')
            ->values();
        $existingTargets = OfflineTraining::whereNotNull('target_employee')
            ->where('target_employee', '!=', '')
            ->distinct()
            ->pluck('target_employee')
            ->all();
        $defaultTargets = [
            'All Employees',
            'Staff to Supervisor, All Departments',
            'Specialist, Engineer & Data Analyst',
            'All Plant Operations & Maintenance Personnel',
            'Staff, Officers, and Section Heads',
            'Supervisor / Section Head',
            'Manager & Above',
        ];
        $targetValues = $targetEmployeeOptions->pluck('value')->all();
        foreach (array_unique(array_merge($existingTargets, $defaultTargets)) as $tgtName) {
            $tgtTrimmed = trim((string) $tgtName);
            if ($tgtTrimmed !== '' && !in_array($tgtTrimmed, $targetValues, true)) {
                $targetEmployeeOptions->push((object)[
                    'id'    => null,
                    'value' => $tgtTrimmed,
                    'text'  => $tgtTrimmed,
                ]);
                $targetValues[] = $tgtTrimmed;
            }
        }
        $targetEmployeeOptions = $targetEmployeeOptions->sortBy('value')->values();

        // Field 6: Prerequisites
        $prerequisiteOptions = $optionsGrouped->get(OfflineOptionService::TYPE_PREREQUISITES, collect())
            ->merge($optionsGrouped->get('requirements', collect()))
            ->unique('value')
            ->values();
        $existingPrereqs = OfflineTraining::whereNotNull('prerequisites')
            ->where('prerequisites', '!=', '')
            ->distinct()
            ->pluck('prerequisites')
            ->all();
        $defaultPrereqs = [
            'None',
            'Minimum 1 year tenure at PT Krakatau Posco',
            'Basic Microsoft Excel proficiency',
            'Valid PTKP ID badge and completed basic safety induction',
        ];
        $prereqValues = $prerequisiteOptions->pluck('value')->all();
        foreach (array_unique(array_merge($existingPrereqs, $defaultPrereqs)) as $prqName) {
            $prqTrimmed = trim((string) $prqName);
            if ($prqTrimmed !== '' && !in_array($prqTrimmed, $prereqValues, true)) {
                $prerequisiteOptions->push((object)[
                    'id'    => null,
                    'value' => $prqTrimmed,
                    'text'  => $prqTrimmed,
                ]);
                $prereqValues[] = $prqTrimmed;
            }
        }
        $prerequisiteOptions = $prerequisiteOptions->sortBy('value')->values();

        // Auto-seed target_employee and prerequisites baseline into offline_training_options if empty
        if (!OfflineTrainingOption::where('option_type', OfflineOptionService::TYPE_TARGET_EMPLOYEE)->exists()) {
            foreach ($defaultTargets as $dTgt) {
                $this->offlineOptionService->registerOption(OfflineOptionService::TYPE_TARGET_EMPLOYEE, $dTgt, null, 'system');
            }
        }
        if (!OfflineTrainingOption::where('option_type', OfflineOptionService::TYPE_PREREQUISITES)->exists()) {
            foreach ($defaultPrereqs as $dPrq) {
                $this->offlineOptionService->registerOption(OfflineOptionService::TYPE_PREREQUISITES, $dPrq, null, 'system');
            }
        }

        // Field 7: Course Completion Requirements
        $completionReqOptions = $optionsGrouped->get('course_completion_requirement', collect())
            ->merge($optionsGrouped->get('completion_requirement', collect()))
            ->unique('value')
            ->values();
        $existingCompletionReqs = OfflineTraining::whereNotNull('course_completion_requirement')
            ->where('course_completion_requirement', '!=', '')
            ->distinct()
            ->pluck('course_completion_requirement')
            ->all();
        $defaultCompletionReqs = [
            'Minimum 80% attendance and active participation',
            'Minimum 80% attendance and post-test passing score (>= 75)',
            'Complete all module quizzes and final project',
            '100% attendance and course evaluation submission',
            'None (Certificate upon full session attendance)',
        ];
        $completionReqValues = $completionReqOptions->pluck('value')->all();
        foreach (array_unique(array_merge($existingCompletionReqs, $defaultCompletionReqs)) as $crqName) {
            $crqTrimmed = trim((string) $crqName);
            if ($crqTrimmed !== '' && !in_array($crqTrimmed, $completionReqValues, true)) {
                $completionReqOptions->push((object)[
                    'id'    => null,
                    'value' => $crqTrimmed,
                    'text'  => $crqTrimmed,
                ]);
                $completionReqValues[] = $crqTrimmed;
            }
        }
        $completionReqOptions = $completionReqOptions->sortBy('value')->values();

        if (!OfflineTrainingOption::whereIn('option_type', ['course_completion_requirement', 'completion_requirement'])->exists()) {
            foreach ($defaultCompletionReqs as $dCrq) {
                $this->offlineOptionService->registerOption('course_completion_requirement', $dCrq, null, 'system');
            }
        }

        $options = [
            'title'                         => $titleOptions,
            'course_name'                   => $titleOptions,
            'sub_course_name'               => $subCourseOptions,
            'instructor'                    => $instructorOptions,
            'instructor_name'               => $instructorOptions,
            'location'                      => $locationOptions,
            'venue'                         => $locationOptions,
            'target_employee'               => $targetEmployeeOptions,
            'target_audience'               => $targetEmployeeOptions,
            'prerequisites'                 => $prerequisiteOptions,
            'requirements'                  => $prerequisiteOptions,
            'participant_requirement'       => $prerequisiteOptions,
            'course_completion_requirement' => $completionReqOptions,
            'completion_requirement'        => $completionReqOptions,
            'type'                          => $types,
            'category'                      => $categories,
            'level'                         => $levels,
            'sub_classification'            => $subClassifications,
        ];

        return [
            'classifications'      => $classifications,
            'options'              => $options,
            'subClassificationMap' => $subClassificationMap,
        ];
    }

    /**
     * Show the form for creating a new training schedule.
     */
    public function create()
    {
        $formData = $this->getScheduleFormData();
        $viewName = view()->exists('manage.training-schedule.create')
            ? 'manage.training-schedule.create'
            : 'main.manage-training-schedule.create';
        return view($viewName, $formData);
    }

    /**
     * Store a newly created training schedule in offline_trainings table.
     */
    public function store(Request $request)
    {
        $isDraft = $request->input('action') === 'draft';
        $isUnscheduled = $request->input('status') === 'Unscheduled';
        $isScheduleOptional = $isDraft || $isUnscheduled;

        $validated = $request->validate([
            'title'               => ['required', 'string', 'max:255'],
            'sub_course_name'     => ['nullable', 'string', 'max:255'],
            'main_classification' => ['nullable', 'string', 'max:100'],
            'sub_classification'  => ['nullable', 'string', 'max:255'],
            'category'            => [$isDraft ? 'nullable' : 'required', 'string', 'max:100'],
            'type'                => ['nullable', 'string', 'max:50'],
            'level'               => ['nullable', 'string', 'max:50'],
            'code'                => ['nullable', 'string', 'max:100'],
            'training_code'       => ['nullable', 'string', 'max:100'],
            'instructor'          => [$isDraft ? 'nullable' : 'required', 'string', 'max:255'],
            'location'            => [$isDraft ? 'nullable' : 'required', 'string', 'max:255'],
            'target_employee'     => ['nullable', 'string', 'max:255'],
            'prerequisites'                 => ['nullable', 'string', 'max:255'],
            'course_completion_requirement' => ['nullable', 'string', 'max:500'],
            'quota'                         => [$isDraft ? 'nullable' : 'required', 'integer', 'min:1'],
            'status'              => ['nullable', 'string', 'in:Open Registration,Running,Closed,Draft,Open,Unscheduled'],
            'start_date'              => [$isScheduleOptional ? 'nullable' : 'required', 'date'],
            'end_date'                => ['nullable', 'date'],
            'registration_close_date' => ['nullable', 'date', 'before_or_equal:start_date'],
            'registration_close_time' => ['nullable', 'date_format:H:i'],
            'start_time'              => [$isScheduleOptional ? 'nullable' : 'required'],
            'end_time'                => [$isScheduleOptional ? 'nullable' : 'required'],
            'description'             => ['nullable', 'string'],
            'objectives'              => ['nullable', 'string'],
            'learning_objectives'     => ['nullable', 'string'],
            'syllabus_title'          => ['nullable', 'array'],
            'syllabus_desc'           => ['nullable', 'array'],
        ]);

        // Process dynamic syllabus array
        $syllabus = [];
        $titles = $request->input('syllabus_title', []);
        $descs = $request->input('syllabus_desc', []);

        if (is_array($titles)) {
            foreach ($titles as $idx => $t) {
                $topic = trim((string)$t);
                $desc = trim((string)($descs[$idx] ?? ''));
                if ($topic !== '' || $desc !== '') {
                    $syllabus[] = [
                        'topic'       => $topic,
                        'description' => $desc,
                    ];
                }
            }
        }

        $trainingCode = $request->filled('code') ? $request->input('code') : $request->input('training_code');
        $objectives = $request->filled('objectives') ? $request->input('objectives') : $request->input('learning_objectives');

        if ($isUnscheduled) {
            $startDate = $request->filled('start_date') ? $request->input('start_date') : null;
            $endDate = $request->filled('end_date') ? $request->input('end_date') : null;
            $registrationCloseDate = $request->filled('registration_close_date') ? $request->input('registration_close_date') : null;
            $registrationCloseTime = $request->filled('registration_close_time') ? $request->input('registration_close_time') : null;
            $startTime = $request->filled('start_time') ? $request->input('start_time') : null;
            $endTime = $request->filled('end_time') ? $request->input('end_time') : null;
            $status = 'Unscheduled';
        } elseif ($isDraft) {
            $startDate = $request->input('start_date') ?: Carbon::today()->format('Y-m-d');
            $endDate = $request->filled('end_date') ? $request->input('end_date') : $startDate;
            $registrationCloseDate = $request->filled('registration_close_date') ? $request->input('registration_close_date') : $startDate;
            $registrationCloseTime = $request->filled('registration_close_time') ? $request->input('registration_close_time') : '23:59';
            $startTime = $request->input('start_time', '08:00') ?: '08:00';
            $endTime = $request->input('end_time', '16:00') ?: '16:00';
            $status = 'Draft';
        } else {
            $startDate = $request->input('start_date');
            $endDate = $request->filled('end_date') ? $request->input('end_date') : $startDate;
            $registrationCloseDate = $request->filled('registration_close_date') ? $request->input('registration_close_date') : $startDate;
            $registrationCloseTime = $request->filled('registration_close_time') ? $request->input('registration_close_time') : '23:59';
            $startTime = $request->input('start_time', '08:00') ?: '08:00';
            $endTime = $request->input('end_time', '16:00') ?: '16:00';
            $status = $request->input('status', 'Open Registration');
            if ($status === 'Open') {
                $status = 'Open Registration';
            }
            if ($registrationCloseDate) {
                $closeDatetime = Carbon::parse($registrationCloseDate . ' ' . substr($registrationCloseTime, 0, 5));
                if ($status === 'Open Registration' && Carbon::now()->gt($closeDatetime)) {
                    $status = 'Closed';
                }
            }
        }

        if (strlen((string)$registrationCloseTime) === 5) {
            $registrationCloseTime .= ':00';
        }

        $training = OfflineTraining::create([
            'title'                   => $request->input('title'),
            'course_name'             => $request->input('title'),
            'sub_course_name'         => $request->input('sub_course_name'),
            'category'                => $request->input('category', 'General') ?: 'General',
            'type'                    => $request->input('type'),
            'level'                   => $request->input('level'),
            'main_classification'     => $request->input('main_classification'),
            'sub_classification'      => $request->input('sub_classification'),
            'training_code'           => $trainingCode,
            'batch'                   => $trainingCode,
            'instructor'              => $request->input('instructor', '-') ?: '-',
            'location'                => $request->input('location', '-') ?: '-',
            'venue'                   => $request->input('location', '-') ?: '-',
            'target_employee'               => $request->input('target_employee'),
            'prerequisites'                 => $request->input('prerequisites'),
            'course_completion_requirement' => $request->input('course_completion_requirement'),
            'quota'                         => (int) ($request->input('quota') ?: 30),
            'status'                  => $status,
            'start_date'              => $startDate,
            'end_date'                => $endDate,
            'finish_date'             => $endDate,
            'registration_close_date' => $registrationCloseDate,
            'registration_close_time' => $registrationCloseTime,
            'start_time'              => $startTime,
            'end_time'                => $endTime,
            'description'             => $request->input('description'),
            'learning_objectives'     => $objectives,
            'syllabus'                => $syllabus,
            'created_by'              => Auth::id(),
        ]);

        // Auto sync options to offline_training_options for consistent master options
        $this->offlineOptionService->ensureOptionsFromTraining([
            'course_name'        => $request->input('title'),
            'sub_course_name'    => $request->input('sub_course_name'),
            'type'               => $request->input('type'),
            'category'           => $request->input('category'),
            'level'              => $request->input('level'),
            'sub_classification' => $request->input('sub_classification'),
            'instructor'         => $request->input('instructor'),
            'venue'              => $request->input('location'),
            'target_employee'    => $request->input('target_employee'),
            'prerequisites'      => $request->input('prerequisites'),
        ], 'manual', Auth::id());

        $statusMsg = $isDraft ? 'saved as draft' : 'successfully created';
        return redirect()->route('manage.training-schedule.index')
            ->with('success', 'Training schedule "' . $training->title . '" has been ' . $statusMsg . '.');
    }

    /**
     * Show the form for editing an existing training schedule.
     */
    public function edit($id)
    {
        $training = $id instanceof OfflineTraining ? $id : OfflineTraining::findOrFail($id);
        $formData = $this->getScheduleFormData($training);

        $viewName = view()->exists('manage.training-schedule.edit')
            ? 'manage.training-schedule.edit'
            : 'main.manage-training-schedule.edit';

        return view($viewName, array_merge(['training' => $training], $formData));
    }

    /**
     * Update the specified training schedule in offline_trainings table.
     */
    public function update(Request $request, $id)
    {
        $training = $id instanceof OfflineTraining ? $id : OfflineTraining::findOrFail($id);
        $isDraft = $request->input('action') === 'draft';
        $isUnscheduled = $request->input('status') === 'Unscheduled';
        $isScheduleOptional = $isDraft || $isUnscheduled;

        $validated = $request->validate([
            'title'               => ['required', 'string', 'max:255'],
            'sub_course_name'     => ['nullable', 'string', 'max:255'],
            'main_classification' => ['nullable', 'string', 'max:100'],
            'sub_classification'  => ['nullable', 'string', 'max:255'],
            'category'            => [$isDraft ? 'nullable' : 'required', 'string', 'max:100'],
            'type'                => ['nullable', 'string', 'max:50'],
            'level'               => ['nullable', 'string', 'max:50'],
            'code'                => ['nullable', 'string', 'max:100'],
            'training_code'       => ['nullable', 'string', 'max:100'],
            'instructor'          => [$isDraft ? 'nullable' : 'required', 'string', 'max:255'],
            'location'            => [$isDraft ? 'nullable' : 'required', 'string', 'max:255'],
            'target_employee'     => ['nullable', 'string', 'max:255'],
            'prerequisites'                 => ['nullable', 'string', 'max:255'],
            'course_completion_requirement' => ['nullable', 'string', 'max:500'],
            'quota'                         => [$isDraft ? 'nullable' : 'required', 'integer', 'min:1'],
            'status'              => ['nullable', 'string', 'in:Open Registration,Running,Closed,Draft,Open,Unscheduled'],
            'start_date'              => [$isScheduleOptional ? 'nullable' : 'required', 'date'],
            'end_date'                => ['nullable', 'date'],
            'registration_close_date' => ['nullable', 'date', 'before_or_equal:start_date'],
            'registration_close_time' => ['nullable', 'date_format:H:i'],
            'start_time'              => [$isScheduleOptional ? 'nullable' : 'required'],
            'end_time'                => [$isScheduleOptional ? 'nullable' : 'required'],
            'description'             => ['nullable', 'string'],
            'objectives'              => ['nullable', 'string'],
            'learning_objectives'     => ['nullable', 'string'],
            'syllabus_title'          => ['nullable', 'array'],
            'syllabus_desc'           => ['nullable', 'array'],
        ]);

        // Process dynamic syllabus array
        $syllabus = [];
        $titles = $request->input('syllabus_title', []);
        $descs = $request->input('syllabus_desc', []);

        if (is_array($titles)) {
            foreach ($titles as $idx => $t) {
                $topic = trim((string)$t);
                $desc = trim((string)($descs[$idx] ?? ''));
                if ($topic !== '' || $desc !== '') {
                    $syllabus[] = [
                        'topic'       => $topic,
                        'description' => $desc,
                    ];
                }
            }
        }

        $trainingCode = $request->filled('code') ? $request->input('code') : $request->input('training_code');
        $objectives = $request->filled('objectives') ? $request->input('objectives') : $request->input('learning_objectives');

        if ($isUnscheduled) {
            $startDate = $request->filled('start_date') ? $request->input('start_date') : null;
            $endDate = $request->filled('end_date') ? $request->input('end_date') : null;
            $registrationCloseDate = $request->filled('registration_close_date') ? $request->input('registration_close_date') : null;
            $registrationCloseTime = $request->filled('registration_close_time') ? $request->input('registration_close_time') : null;
            $startTime = $request->filled('start_time') ? $request->input('start_time') : null;
            $endTime = $request->filled('end_time') ? $request->input('end_time') : null;
            $status = 'Unscheduled';
        } elseif ($isDraft) {
            $startDate = $request->input('start_date') ?: ($training->start_date ?: Carbon::today()->format('Y-m-d'));
            $endDate = $request->filled('end_date') ? $request->input('end_date') : $startDate;
            $registrationCloseDate = $request->filled('registration_close_date') ? $request->input('registration_close_date') : $startDate;
            $registrationCloseTime = $request->filled('registration_close_time') ? $request->input('registration_close_time') : ($training->registration_close_time ?? '23:59');
            $startTime = $request->input('start_time', $training->start_time ?: '08:00') ?: '08:00';
            $endTime = $request->input('end_time', $training->end_time ?: '16:00') ?: '16:00';
            $status = 'Draft';
        } else {
            $startDate = $request->input('start_date', $training->start_date);
            $endDate = $request->filled('end_date') ? $request->input('end_date') : $startDate;
            $registrationCloseDate = $request->filled('registration_close_date') ? $request->input('registration_close_date') : $startDate;
            $registrationCloseTime = $request->filled('registration_close_time') ? $request->input('registration_close_time') : ($training->registration_close_time ?? '23:59');
            $startTime = $request->input('start_time', $training->start_time ?: '08:00') ?: '08:00';
            $endTime = $request->input('end_time', $training->end_time ?: '16:00') ?: '16:00';
            $status = $request->input('status', $training->status ?: 'Open Registration');
            if ($status === 'Open') {
                $status = 'Open Registration';
            }
            if ($registrationCloseDate) {
                $closeDatetime = Carbon::parse($registrationCloseDate . ' ' . substr($registrationCloseTime, 0, 5));
                if ($status === 'Open Registration' && Carbon::now()->gt($closeDatetime)) {
                    $status = 'Closed';
                }
            }
        }

        if (strlen((string)$registrationCloseTime) === 5) {
            $registrationCloseTime .= ':00';
        }

        $training->update([
            'title'                   => $request->input('title'),
            'course_name'             => $request->input('title'),
            'sub_course_name'         => $request->input('sub_course_name'),
            'category'                => $request->input('category', $training->category ?: 'General') ?: 'General',
            'type'                    => $request->input('type'),
            'level'                   => $request->input('level'),
            'main_classification'     => $request->input('main_classification'),
            'sub_classification'      => $request->input('sub_classification'),
            'training_code'           => $trainingCode,
            'batch'                   => $trainingCode,
            'instructor'              => $request->input('instructor', $training->instructor ?: '-') ?: '-',
            'location'                => $request->input('location', $training->location ?: '-') ?: '-',
            'venue'                   => $request->input('location', $training->venue ?: '-') ?: '-',
            'target_employee'               => $request->input('target_employee'),
            'prerequisites'                 => $request->input('prerequisites'),
            'course_completion_requirement' => $request->input('course_completion_requirement'),
            'quota'                         => (int) ($request->input('quota') ?: ($training->quota ?: 30)),
            'status'                  => $status,
            'start_date'              => $startDate,
            'end_date'                => $endDate,
            'finish_date'             => $endDate,
            'registration_close_date' => $registrationCloseDate,
            'registration_close_time' => $registrationCloseTime,
            'start_time'              => $startTime,
            'end_time'                => $endTime,
            'description'             => $request->input('description'),
            'learning_objectives'     => $objectives,
            'syllabus'                => $syllabus,
        ]);

        // Auto sync options to offline_training_options for consistent master options
        $this->offlineOptionService->ensureOptionsFromTraining([
            'course_name'        => $request->input('title'),
            'sub_course_name'    => $request->input('sub_course_name'),
            'type'               => $request->input('type'),
            'category'           => $request->input('category'),
            'level'              => $request->input('level'),
            'sub_classification' => $request->input('sub_classification'),
            'instructor'         => $request->input('instructor'),
            'venue'              => $request->input('location'),
            'target_employee'    => $request->input('target_employee'),
            'prerequisites'      => $request->input('prerequisites'),
        ], 'manual', Auth::id());

        $statusMsg = $isDraft ? 'saved as draft' : 'successfully updated';
        return redirect()->route('manage.training-schedule.index')
            ->with('success', 'Training schedule "' . $training->title . '" has been ' . $statusMsg . '.');
    }

    /**
     * Store new option dynamically via AJAX from [+] button in create/edit schedule forms.
     */
    public function storeOption(Request $request): JsonResponse
    {
        $request->validate([
            'option_type'          => ['required', 'string'],
            'value'                => ['required', 'string', 'max:255'],
            'main_classification' => ['nullable', 'string', 'max:255'],
        ]);

        $type = (string) $request->input('option_type');
        $typeMap = [
            'title'           => OfflineOptionService::TYPE_COURSE_NAME,
            'location'        => OfflineOptionService::TYPE_VENUE,
            'target_audience'               => OfflineOptionService::TYPE_TARGET_EMPLOYEE,
            'requirements'                  => OfflineOptionService::TYPE_PREREQUISITES,
            'participant_requirement'       => OfflineOptionService::TYPE_PREREQUISITES,
            'course_completion_requirement' => 'course_completion_requirement',
            'completion_requirement'        => 'course_completion_requirement',
        ];
        $type = $typeMap[$type] ?? $type;

        $rawValue = (string) $request->input('value');
        $cleanValue = trim(preg_replace('/\s+/', ' ', $rawValue));

        if (!OfflineOptionService::isValidValue($cleanValue)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'The provided option value is invalid or reserved.',
            ], 422);
        }

        // 1. Classification (Main Classification)
        if ($type === 'classification' || $type === 'main_classification') {
            $existing = Classification::whereRaw('LOWER(classification_name) = ?', [mb_strtolower($cleanValue)])->first();
            if ($existing) {
                return response()->json([
                    'status'      => 'exists',
                    'success'     => true,
                    'is_existing' => true,
                    'message'     => "Classification '{$existing->classification_name}' already exists.",
                    'data'        => [
                        'value' => $existing->classification_name,
                        'text'  => $existing->classification_name,
                    ],
                ]);
            }

            $created = Classification::create([
                'classification_name' => $cleanValue,
            ]);

            return response()->json([
                'status'      => 'success',
                'success'     => true,
                'is_existing' => false,
                'message'     => "Classification '{$created->classification_name}' created successfully.",
                'data'        => [
                    'value' => $created->classification_name,
                    'text'  => $created->classification_name,
                ],
            ], 201);
        }

        // 2. Offline Training Options (type, category, level, sub_classification, etc.)
        if (!in_array($type, OfflineOptionService::ALLOWED_TYPES, true)) {
            return response()->json([
                'status'  => 'error',
                'message' => "Invalid option type: '{$type}'.",
            ], 422);
        }

        $normalized = OfflineOptionService::normalize($cleanValue);
        $existing = OfflineTrainingOption::where('option_type', $type)
            ->where('scope_key', 'global')
            ->where('normalized_value', $normalized)
            ->first();

        if ($existing) {
            return response()->json([
                'status'      => 'exists',
                'success'     => true,
                'is_existing' => true,
                'message'     => "Option '{$existing->value}' already exists.",
                'data'        => [
                    'value' => $existing->value,
                    'text'  => $existing->value,
                ],
            ]);
        }

        $option = $this->offlineOptionService->registerOption(
            type: $type,
            value: $cleanValue,
            year: null,
            createdFrom: 'manual',
            createdBy: Auth::id()
        );

        return response()->json([
            'status'      => 'success',
            'success'     => true,
            'is_existing' => false,
            'message'     => "Option '{$option->value}' created successfully.",
            'data'        => [
                'value' => $option->value,
                'text'  => $option->value,
            ],
        ], 201);
    }

    /**
     * Remove the specified training schedule from storage with safety checks.
     */
    public function destroy($id)
    {
        $training = $id instanceof OfflineTraining ? $id : OfflineTraining::findOrFail($id);

        // 1. Check if participant registrations exist
        if ($training->registrations()->exists()) {
            return redirect()->route('manage.training-schedule.index')
                ->with('warning', 'Training schedule cannot be deleted because participant registrations already exist.');
        }

        // 2. Check if participant learning history exists in offline_training_users
        if (OfflineTrainingUser::where('offline_training_id', $training->id)->exists()) {
            return redirect()->route('manage.training-schedule.index')
                ->with('warning', 'Training schedule cannot be deleted because participant learning history already exists.');
        }

        // 3. Safe deletion
        $training->delete();

        return redirect()->route('manage.training-schedule.index')
            ->with('success', 'Training schedule deleted successfully.');
    }

    /**
     * Route handler for Participant Action button:
     * Prepares view and redirects to Participant Tracking & Approvals tab for the selected training.
     */
    public function participants($id)
    {
        $training = $id instanceof OfflineTraining ? $id : OfflineTraining::findOrFail($id);

        return redirect()->route('manage.training-schedule.index', [
            'tab'      => 'participants',
            'training' => $training->id,
        ]);
    }

    /**
     * User registration action from /trainingplan.
     */
    public function register(Request $request, $trainingId, OfflineTrainingRegistrationService $service)
    {
        try {
            $training = OfflineTraining::find((int) $trainingId);
            if (!$training) {
                throw new \InvalidArgumentException('Training session not found.');
            }

            $statusLower = strtolower(trim($training->status ?? ''));
            $now = Carbon::now();
            $today = $now->format('Y-m-d');
            $currentTime = $now->format('H:i:s');

            $startDate = $training->start_date ? Carbon::parse($training->start_date)->format('Y-m-d') : null;
            $endDate = ($training->end_date ?? $training->start_date) ? Carbon::parse($training->end_date ?? $training->start_date)->format('Y-m-d') : null;
            $startTime = $training->start_time ? (strlen($training->start_time) === 5 ? $training->start_time . ':00' : $training->start_time) : '00:00:00';
            $endTime = $training->end_time ? (strlen($training->end_time) === 5 ? $training->end_time . ':00' : $training->end_time) : '23:59:59';

            // Explicit running status
            $isRunningStatus = in_array($statusLower, ['running', 'in progress']);
            if ($isRunningStatus) {
                $msg = "Registration is closed for \"{$training->title}\" as the training session is currently in progress.";
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return redirect()->back()->with('error', $msg);
            }

            // Must permit registration
            if ($training->status !== 'Open Registration') {
                $msg = "Registration is closed for \"{$training->title}\" (Status: {$training->status}).";
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return redirect()->back()->with('error', $msg);
            }

            // Past registration deadline (now() <= registration_close_date + registration_close_time)
            $regCloseDate = $training->registration_close_date ?? $training->start_date;
            $regCloseDateStr = $regCloseDate ? Carbon::parse($regCloseDate)->format('Y-m-d') : null;
            $regCloseTimeStr = $training->registration_close_time ? (strlen($training->registration_close_time) === 5 ? $training->registration_close_time . ':00' : $training->registration_close_time) : '23:59:59';
            $closeDatetime = $regCloseDateStr ? Carbon::parse($regCloseDateStr . ' ' . $regCloseTimeStr) : null;
            $isPastDeadline = $closeDatetime && Carbon::now()->gt($closeDatetime);

            // Only if a single-day training scheduled for today has already commenced
            $isSingleDayRunningToday = ($startDate && $endDate && $startDate === $today && $endDate === $today && $currentTime >= $startTime);

            if ($isSingleDayRunningToday) {
                $msg = "Registration is closed for \"{$training->title}\" as today's session has already started.";
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return redirect()->back()->with('error', $msg);
            }

            if ($isPastDeadline) {
                $msg = "Registration is closed for \"{$training->title}\" as the registration deadline has passed.";
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return redirect()->back()->with('error', $msg);
            }

            $registration = $service->register((int) $trainingId, Auth::user());

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Successfully registered for training. Your application is pending approval.',
                    'data'    => $registration,
                ]);
            }

            return redirect()->back()->with('success', 'Successfully registered for training. Your application is pending approval.');
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * User cancel pending registration from My History tab.
     */
    public function cancelRegistration(Request $request, $registrationId, OfflineTrainingRegistrationService $service)
    {
        try {
            $service->cancel((int) $registrationId, Auth::user());

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Registration cancelled successfully.',
                ]);
            }

            return redirect()->back()->with('success', 'Registration cancelled successfully.');
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Approve or reject a participant registration (Admin / Subadmin action).
     */
    public function updateRegistrationStatus(Request $request, $registrationId, OfflineTrainingRegistrationService $service)
    {
        $request->validate([
            'status' => 'required|in:Approved,Rejected',
            'reason' => 'nullable|string|max:500',
        ]);

        $registration = OfflineTrainingRegister::with('training')->findOrFail($registrationId);

        if (!Auth::user()->canManageLms()) {
            abort(403, 'Unauthorized action.');
        }

        try {
            if ($request->input('status') === 'Approved') {
                $service->approve($registration, Auth::id());
                $message = 'Participant application has been approved.';
            } else {
                $service->reject($registration, $request->input('reason'));
                $message = 'Participant application has been rejected.';
            }

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $message]);
            }

            return redirect()->back()->with('success', $message);
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Mark a registration as Completed and sync participant to offline_training_users.
     */
    public function completeRegistration(Request $request, $registrationId, OfflineTrainingCompletionService $service)
    {
        if (!Auth::user()->canManageLms()) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $service->complete((int) $registrationId);
            $message = 'Participant training completion recorded successfully.';

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $message]);
            }

            return redirect()->back()->with('success', $message);
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Display User Training Plan page with Calendar, Available Training, and My History tabs.
     */
    public function userIndex(Request $request)
    {
        $userId = Auth::id();

        $trainings = OfflineTraining::where('status', '!=', 'Draft')
            ->withCount([
                'registrations as registered_seats_count' => function ($q) {
                    $q->whereIn('status', ['Pending', 'Approved', 'Completed']);
                },
            ])
            ->with([
                'registrations' => function ($q) use ($userId) {
                    if ($userId) {
                        $q->where('user_id', $userId);
                    }
                },
            ])
            ->orderBy('start_date', 'asc')
            ->get();

        $availableQuery = OfflineTraining::where('status', '!=', 'Draft')
            ->withCount([
                'registrations as registered_seats_count' => function ($q) {
                    $q->whereIn('status', ['Pending', 'Approved', 'Completed']);
                },
            ])
            ->with([
                'registrations' => function ($q) use ($userId) {
                    if ($userId) {
                        $q->where('user_id', $userId);
                    }
                },
            ]);

        // Filter Category
        if ($request->filled('category') && $request->input('category') !== 'all') {
            $availableQuery->where('category', $request->input('category'));
        }

        // Filter Status
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $statusVal = $request->input('status');
            $statusMap = [
                'open'        => 'Open Registration',
                'running'     => 'Running',
                'closed'      => 'Closed',
                'unscheduled' => 'Unscheduled',
            ];
            $availableQuery->where('status', $statusMap[strtolower($statusVal)] ?? $statusVal);
        }

        // Filter Month
        if ($request->filled('month') && $request->input('month') !== 'all') {
            $month = (int) $request->input('month');
            $availableQuery->where(function ($q) use ($month) {
                $q->whereMonth('start_date', $month)
                  ->orWhereMonth('end_date', $month);
            });
        }

        // Filter Year
        if ($request->filled('year') && $request->input('year') !== 'all') {
            $year = (int) $request->input('year');
            $availableQuery->where(function ($q) use ($year) {
                $q->whereYear('start_date', $year)
                  ->orWhereYear('end_date', $year);
            });
        }

        // Keyword Search (search or q)
        $search = $request->input('search') ?? $request->input('q');
        if (filled($search)) {
            $search = trim($search);
            $availableQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('course_name', 'like', "%{$search}%")
                  ->orWhere('sub_course_name', 'like', "%{$search}%")
                  ->orWhere('instructor', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('venue', 'like', "%{$search}%")
                  ->orWhere('training_code', 'like', "%{$search}%")
                  ->orWhere('batch', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $availableTrainings = $availableQuery->latest()
            ->paginate($perPage)
            ->appends(array_merge($request->query(), ['tab' => 'available']));

        $today = Carbon::today()->format('Y-m-d');
        $calendarEvents = $trainings->filter(function ($t) {
            return !empty($t->start_date) && strtolower($t->status ?? '') !== 'unscheduled';
        })->map(function ($t) use ($today) {
            // Properly parse dates before conditional checks
            $startDate = Carbon::parse($t->start_date);
            $endDate = $t->end_date ? Carbon::parse($t->end_date) : $startDate;

            $startDateStr = $startDate->format('Y-m-d');
            $endDateStr = $endDate->format('Y-m-d');

            // Define $regCloseDate and $regCloseDateStr with safe fallbacks
            $regCloseDate = $t->registration_close_date 
                ? Carbon::parse($t->registration_close_date) 
                : $startDate;
            $regCloseDateStr = $regCloseDate->format('Y-m-d');
            $regCloseTime = $t->registration_close_time ? substr($t->registration_close_time, 0, 5) : '23:59';
            $isPublished = $t->status !== 'Draft' && (!isset($t->is_published) || (bool) $t->is_published);

            // Calendar & List Status Classification Logic: Open, Private, Ended, Running
            if ($endDateStr < $today) {
                $status = 'Ended'; // Dark Gray badge
            } elseif ($startDateStr <= $today && $endDateStr >= $today && $regCloseDateStr < $today) {
                $status = 'Running'; // Orange badge (Currently running & registration closed)
            } elseif ($regCloseDateStr >= $today && $isPublished && !in_array(strtolower($t->status ?? ''), ['closed', 'private', 'draft'])) {
                $status = 'Open'; // Green badge (Registration open)
            } else {
                $status = 'Private'; // Soft Blue badge (Invitation-Only Training)
            }

            $enrolled = (int) ($t->participants_count ?? ($t->registered_seats_count ?? 0));
            $quota = (int) ($t->quota ?: 1);
            $isPast = ($endDateStr < $today);

            return [
                'id'                      => (string) $t->id,
                'year'                    => (int) $startDate->format('Y'),
                'month'                   => (int) $startDate->format('n') - 1, // 0-indexed for JavaScript Date
                'day'                     => (int) $startDate->format('j'),
                'startDate'               => $startDateStr,
                'endDate'                 => $endDateStr,
                'registration_close_date' => $regCloseDate->format('Y-m-d'),
                'registrationCloseDate'   => $regCloseDate->format('Y-m-d'),
                'registration_close_time' => $regCloseTime,
                'registrationCloseTime'   => $regCloseTime,
                'rawStatus'               => $t->status,
                'title'                   => $t->title,
                'shortTitle'              => Str::limit($t->title, 16),
                'category'                => $t->category,
                'badgeClass'              => match($t->category) {
                    'Basic & Common Duties'             => 'bg-secondary-subtle text-secondary',
                    'Compliance', 'Compliance & Safety' => 'bg-warning-lt text-warning',
                    'Innovation'                        => 'bg-success-lt text-success',
                    'Job Functional', 'Technical Skill' => 'bg-info-lt text-info',
                    'Language'                          => 'bg-danger-lt text-danger',
                    'Leadership'                        => 'bg-purple-subtle text-purple',
                    default                             => 'bg-primary-lt text-primary',
                },
                'status'                  => $status,
                'computedStatus'          => $status,
                'is_published'            => $isPublished,
                'isPast'                  => $isPast,
                'startTime'               => substr($t->start_time ?? '', 0, 5),
                'endTime'                 => substr($t->end_time ?? '', 0, 5),
                'time'                    => substr($t->start_time ?? '', 0, 5) . ' - ' . substr($t->end_time ?? '', 0, 5) . ' WIB',
                'dateTime'                => $startDate->format('d F Y') . ', ' . substr($t->start_time ?? '', 0, 5) . ' - ' . substr($t->end_time ?? '', 0, 5) . ' WIB',
                'prerequisites'                 => $t->prerequisites,
                'participant_requirement'       => $t->prerequisites,
                'course_completion_requirement' => $t->course_completion_requirement,
                'location'                      => $t->location,
                'instructor'                    => $t->instructor,
                'quota'                   => (int) $t->quota,
                'enrolled'                => (int) $enrolled,
                'isFull'                  => $enrolled >= $quota,
                'quotaText'               => "{$enrolled} / {$t->quota} Seats" . ($enrolled >= $quota ? ' (Full)' : ''),
            ];
        })->values()->toArray();

        // Build live My History for authenticated user
        $myHistory = collect();
        if ($userId) {
            $myHistory = OfflineTrainingRegister::with('training')
                ->where('user_id', $userId)
                ->latest('registered_at')
                ->get()
                ->map(function ($reg, $idx) {
                    $t = $reg->training;
                    return (object)[
                        'id'              => $reg->id,
                        'registration_id' => $reg->id,
                        'modal_id'        => $t->id ?? $reg->id,
                        'title'           => $t->title ?? ($t->course_name ?? 'Training Schedule'),
                        'category'        => $t->category ?? 'General',
                        'start_date'      => $t->start_date ?? $reg->registered_at,
                        'end_date'        => $t->end_date ?? null,
                        'start_time'      => $t->start_time ?? '08:00:00',
                        'end_time'        => $t->end_time ?? '16:00:00',
                        'location'        => $t->location ?? '-',
                        'instructor'      => $t->instructor ?? '-',
                        'description'     => $t->description ?? '',
                        'status'          => $reg->status, // 'Pending', 'Approved', 'Completed', 'Rejected'
                        'can_cancel'      => ($reg->status === 'Pending'),
                    ];
                });
        }

        // Active / Selected Date for Today's Agenda (strictly matching start_date <= activeDate <= end_date)
        $selectedDate = $request->query('selected_date', Carbon::today()->format('Y-m-d'));
        $todaysAgenda = $trainings->filter(function ($t) use ($selectedDate) {
            if (empty($t->start_date) || strtolower($t->status ?? '') === 'unscheduled') {
                return false;
            }
            $sDate = Carbon::parse($t->start_date)->format('Y-m-d');
            $eDate = Carbon::parse($t->end_date ?? $t->start_date)->format('Y-m-d');
            return $selectedDate >= $sDate && $selectedDate <= $eDate;
        })->values();

        return view('user.trainingplan-user.index', compact('trainings', 'availableTrainings', 'calendarEvents', 'myHistory', 'selectedDate', 'todaysAgenda'));
    }

    /**
     * Evaluate the status of a training event specifically for a calendar cell date relative to today.
     *
     * @param mixed $training
     * @param string $cellDate
     * @param string|null $today
     * @return string ('Ended'|'Running'|'Open'|'Closed')
     */
    public static function evaluateCalendarCellStatus($training, string $cellDate, ?string $today = null): string
    {
        $today = $today ?? Carbon::today()->format('Y-m-d');
        $sDate = Carbon::parse($training->start_date)->format('Y-m-d');
        $eDate = Carbon::parse($training->end_date ?? $training->start_date)->format('Y-m-d');
        $regCloseDate = $training->registration_close_date 
            ? Carbon::parse($training->registration_close_date)->format('Y-m-d') 
            : $sDate;
        $statusStr = $training->status ?? '';
        $isPublished = $statusStr !== 'Draft' && (!isset($training->is_published) || (bool) $training->is_published);

        // $cellDate represents the specific day square rendered on the calendar grid
        // $today represents the current real-world date (e.g., Oct 13, 2026)
        $isOpenStatus = !in_array(strtolower($statusStr), ['closed', 'private', 'draft']);

        if ($cellDate < $today) {
            // Past dates in calendar always show as Ended
            return 'Ended';
        } elseif ($cellDate === $today) {
            // Current date ONLY shows as Running if the training execution overlaps today
            if ($sDate <= $today && $eDate >= $today) {
                return 'Running';
            } else {
                return ($regCloseDate >= $today && $isPublished && $isOpenStatus) ? 'Open' : 'Private';
            }
        } else {
            // Future dates in calendar show Open or Private based on registration date
            if ($regCloseDate >= $today && $isPublished && $isOpenStatus) {
                return 'Open';
            } else {
                return 'Private';
            }
        }
    }

    /**
     * Download User Training History as PDF.
     */
    public function downloadMyHistory(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Unauthorized');
        }

        $userId = $user->id;

        $query = OfflineTrainingRegister::with('training')
            ->where('user_id', $userId);

        // Filter by Category
        if ($request->filled('category') && $request->input('category') !== 'all') {
            $category = trim($request->input('category'));
            $query->whereHas('training', function ($q) use ($category) {
                $q->where('category', $category);
            });
        }

        // Filter by Status (Pending, Approved, Completed, Rejected, or Training Status)
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $status = trim($request->input('status'));
            $query->where(function ($q) use ($status) {
                $q->where('status', 'like', $status)
                  ->orWhereHas('training', function ($tq) use ($status) {
                      $statusMap = [
                          'open'    => 'Open Registration',
                          'running' => 'Running',
                          'closed'  => 'Closed',
                      ];
                      $mapped = $statusMap[strtolower($status)] ?? $status;
                      $tq->where('status', $mapped);
                  });
            });
        }

        // Filter by Date (exact date or date range)
        if ($request->filled('date')) {
            $filterDate = Carbon::parse($request->input('date'))->format('Y-m-d');
            $query->whereHas('training', function ($q) use ($filterDate) {
                $q->where(function ($sub) use ($filterDate) {
                    $sub->whereDate('start_date', '<=', $filterDate)
                        ->whereDate('end_date', '>=', $filterDate);
                })->orWhereDate('start_date', $filterDate);
            });
        } elseif ($request->filled('start_date') || $request->filled('end_date')) {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date', $startDate);
            $query->whereHas('training', function ($q) use ($startDate, $endDate) {
                if ($startDate && $endDate) {
                    $q->whereDate('start_date', '>=', $startDate)
                      ->whereDate('start_date', '<=', $endDate);
                } elseif ($startDate) {
                    $q->whereDate('start_date', '>=', $startDate);
                } else {
                    $q->whereDate('start_date', '<=', $endDate);
                }
            });
        }

        // Filter by Month
        if ($request->filled('month') && $request->input('month') !== 'all') {
            $month = (int) $request->input('month');
            $query->whereHas('training', function ($q) use ($month) {
                $q->whereMonth('start_date', $month);
            });
        }

        // Filter by Year
        if ($request->filled('year') && $request->input('year') !== 'all') {
            $year = (int) $request->input('year');
            $query->whereHas('training', function ($q) use ($year) {
                $q->whereYear('start_date', $year);
            });
        }

        $myHistory = $query->orderBy('registered_at', 'desc')->get();
        $generatedAt = Carbon::now()->format('d F Y, H:i') . ' WIB';
        $filters = [
            'category' => $request->input('category'),
            'status'   => $request->input('status'),
            'date'     => $request->input('date') ?? ($request->input('start_date') ? $request->input('start_date') . ($request->input('end_date') ? ' to ' . $request->input('end_date') : '') : null),
        ];

        $fileName = "Training_History_{$userId}.pdf";

        $pdf = Pdf::loadView('pdf.training-history', compact('user', 'myHistory', 'generatedAt', 'filters'))
            ->setPaper('a4', 'landscape')
            ->setOption([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => true,
                'defaultFont'          => 'sans-serif',
            ]);

        return response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    /**
     * Download Training Syllabus as PDF.
     */
    public function downloadSyllabusPdf($id)
    {
        $training = OfflineTraining::findOrFail($id);

        $titleSlug = Str::slug($training->title ?? $training->course_name ?? 'training_syllabus', '_');
        $fileName = 'Syllabus_' . $titleSlug . '_' . Carbon::now()->format('Ymd') . '.pdf';

        $pdf = Pdf::loadView('pdf.training-syllabus', compact('training'))
            ->setPaper('a4', 'portrait')
            ->setOption([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => true,
                'defaultFont'          => 'sans-serif',
            ]);

        return $pdf->download($fileName);
    }

    /**
     * Export Training Schedule list to Excel (.xlsx format) with Category, Status, Month, Year, and Search filters.
     */
    public function exportExcel(Request $request)
    {
        $records = $this->buildFilteredScheduleQuery($request)
            ->orderBy('start_date', 'asc')
            ->get();

        $fileName = 'Training_Schedule_Export_' . Carbon::now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new TrainingScheduleExport($records), $fileName);
    }

    /**
     * Export Training Schedule list to Krakatau Posco 3-column Card PDF Announcement layout.
     */
    public function exportPdf(Request $request)
    {
        $trainings = $this->buildFilteredScheduleQuery($request)
            ->where('status', '!=', 'Running')
            ->whereIn('status', ['Open', 'Open Registration', 'Closed'])
            ->orderBy('start_date', 'asc')
            ->get();

        $activeFilters = $this->collectActiveFilterLabels($request);
        $fileName = 'Training_Schedule_Cards_' . Carbon::now()->format('Ymd_His') . '.pdf';

        $pdf = Pdf::loadView('exports.training-schedule-pdf', compact('trainings', 'activeFilters'))
            ->setPaper('a4', 'landscape')
            ->setOption([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => true,
                'defaultFont'          => 'sans-serif',
            ]);

        return $pdf->download($fileName);
    }

    /**
     * Export Training Schedule announcement card layout to high-resolution JPG image.
     */
    public function exportJpg(Request $request, ?TrainingScheduleImageService $imageService = null)
    {
        $imageService = $imageService ?? app(TrainingScheduleImageService::class);

        $trainings = $this->buildFilteredScheduleQuery($request)
            ->where('status', '!=', 'Running')
            ->whereIn('status', ['Open', 'Open Registration', 'Closed'])
            ->orderBy('start_date', 'asc')
            ->get();

        $activeFilters = $this->collectActiveFilterLabels($request);
        $fileName = 'Training_Schedule_Announcement_' . Carbon::now()->format('Ymd_His') . '.jpg';

        // Dedicated 2-column Blade template (resources/views/exports/training-schedule-jpg.blade.php) for high-resolution JPG exports
        $viewContent = view('exports.training-schedule-jpg', compact('trainings', 'activeFilters'))->render();

        // Browser renderer viewport configuration (1920 width, device scale factor 2, wait delay for custom CSS fonts)
        if (class_exists(\Spatie\Browsershot\Browsershot::class)) {
            $imageContent = \Spatie\Browsershot\Browsershot::html($viewContent)
                ->windowSize(1920, 1080)
                ->deviceScaleFactor(2)
                ->setDelay(1000)
                ->waitForSelector('.card-box')
                ->waitUntilNetworkIdle()
                ->showBackground()
                ->jpeg();
        } else {
            $imageContent = $imageService->generateJpg($trainings, $activeFilters, $viewContent, [
                'width'             => 1920,
                'deviceScaleFactor' => 2,
                'delay'             => 1000,
            ]);
        }

        return response($imageContent, 200, [
            'Content-Type'        => 'image/jpeg',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Cache-Control'       => 'no-cache, private',
        ]);
    }

    /**
     * Export Training Schedule list (backward-compatible: defaults to Excel or CSV when requested).
     */
    public function exportSchedule(Request $request)
    {
        if ($request->query('format') !== 'csv') {
            return $this->exportExcel($request);
        }

        $records = $this->buildFilteredScheduleQuery($request)
            ->orderBy('start_date', 'asc')
            ->get();

        $fileName = 'Training_Schedule_Export_' . Carbon::now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return Response::stream(function () use ($records) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // CSV Header
            fputcsv($handle, [
                'No',
                'Training Code',
                'Training Title',
                'Category',
                'Instructor',
                'Location / Venue',
                'Start Date',
                'End Date',
                'Start Time',
                'End Time',
                'Quota',
                'Registered Count',
                'Status',
                'Created At',
            ]);

            foreach ($records as $index => $item) {
                $startDate = $item->start_date ? Carbon::parse($item->start_date)->format('Y-m-d') : '-';
                $endDate = $item->end_date ? Carbon::parse($item->end_date)->format('Y-m-d') : '-';
                $startTime = $item->start_time ? substr($item->start_time, 0, 5) : '-';
                $endTime = $item->end_time ? substr($item->end_time, 0, 5) : '-';

                fputcsv($handle, [
                    $index + 1,
                    $item->training_code ?? ('PTKP-TR-' . $item->id),
                    $item->title ?? ($item->course_name ?? '-'),
                    $item->category ?? 'General',
                    $item->instructor ?? '-',
                    $item->location ?? ($item->venue ?? '-'),
                    $startDate,
                    $endDate,
                    $startTime,
                    $endTime,
                    $item->quota ?? 0,
                    $item->registrations_count ?? 0,
                    $item->status ?? 'Open Registration',
                    $item->created_at ? $item->created_at->format('Y-m-d H:i:s') : '-',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Export Participant Roster to CSV format with training_id, user_id, month, year filters.
     */
    public function exportRoster(Request $request): StreamedResponse
    {
        $query = OfflineTrainingRegister::with(['training', 'user']);

        if ($request->filled('training_id') && $request->input('training_id') !== 'all') {
            $query->where('offline_training_id', (int) $request->input('training_id'));
        }

        if ($request->filled('user_id') && $request->input('user_id') !== 'all') {
            $query->where('user_id', (int) $request->input('user_id'));
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('month') && $request->input('month') !== 'all') {
            $month = (int) $request->input('month');
            $query->whereMonth('registered_at', $month);
        }

        if ($request->filled('year') && $request->input('year') !== 'all') {
            $year = (int) $request->input('year');
            $query->whereYear('registered_at', $year);
        }

        $records = $query->latest('registered_at')->get();

        $fileName = 'Participant_Roster_Export_' . Carbon::now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return Response::stream(function () use ($records) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // CSV Header
            fputcsv($handle, [
                'No',
                'Participant Name',
                'NIP / Employee ID',
                'Email',
                'Department',
                'Position / Role',
                'Training Title',
                'Training Code',
                'Category',
                'Schedule Date',
                'Registration Status',
                'Registered At',
            ]);

            foreach ($records as $index => $reg) {
                $user = $reg->user;
                $training = $reg->training;

                $scheduleDate = '-';
                if ($training && $training->start_date) {
                    $scheduleDate = Carbon::parse($training->start_date)->format('Y-m-d');
                    if ($training->end_date && $training->start_date != $training->end_date) {
                        $scheduleDate .= ' to ' . Carbon::parse($training->end_date)->format('Y-m-d');
                    }
                }

                fputcsv($handle, [
                    $index + 1,
                    $user->name ?? 'Unknown',
                    $user->id_emp ?? '-',
                    $user->email ?? '-',
                    $user->department ?? '-',
                    $user->job_position ?? 'Employee',
                    $training->title ?? ($training->course_name ?? '-'),
                    $training->training_code ?? ($training ? ('PTKP-TR-' . $training->id) : '-'),
                    $training->category ?? 'General',
                    $scheduleDate,
                    $reg->status ?? 'Pending',
                    $reg->registered_at ? $reg->registered_at->format('Y-m-d H:i:s') : '-',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
