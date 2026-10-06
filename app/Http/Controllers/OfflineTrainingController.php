<?php

namespace App\Http\Controllers;

use App\Models\Classification;
use App\Models\OfflineTraining;
use App\Models\OfflineTrainingOption;
use App\Models\OfflineTrainingRegister;
use App\Models\OfflineTrainingUser;
use App\Services\Record\OfflineOptionService;
use App\Services\Record\TrainingOptionManagementService;
use App\Services\Schedule\OfflineTrainingCompletionService;
use App\Services\Schedule\OfflineTrainingRegistrationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
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
     * Display Manage Training Schedule page with live records and live participant tracking.
     */
    public function index(Request $request)
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
        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        // Filter Status
        if ($request->filled('status')) {
            $statusVal = $request->input('status');
            $statusMap = [
                'open'    => 'Open Registration',
                'running' => 'Running',
                'closed'  => 'Closed',
            ];
            $query->where('status', $statusMap[strtolower($statusVal)] ?? $statusVal);
        }

        // Filter Month
        if ($request->filled('month') && $request->input('month') !== 'all') {
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

        $trainings = $query->latest()
            ->paginate(10)
            ->withQueryString();

        $selectedTrainingId = $request->query('training');
        $selectedTraining = null;
        if ($selectedTrainingId) {
            $selectedTraining = $trainings->firstWhere('id', (int) $selectedTrainingId);
        }
        if (!$selectedTraining && $trainings->isNotEmpty()) {
            $selectedTraining = $trainings->first();
        }

        return view('main.manage-training-schedule.index', compact('trainings', 'selectedTraining'));
    }

    /**
     * Gather master classifications, dynamic options, and parent-child mappings for schedule forms.
     */
    protected function getScheduleFormData(?OfflineTraining $training = null): array
    {
        $classifications = Classification::orderBy('classification_name', 'asc')->get();

        // Active master options from offline_training_options
        $types = $this->offlineOptionService->getOptions(OfflineOptionService::TYPE_TYPE);
        $categories = $this->offlineOptionService->getOptions(OfflineOptionService::TYPE_CATEGORY);
        $levels = $this->offlineOptionService->getOptions(OfflineOptionService::TYPE_LEVEL);
        $subClassifications = $this->offlineOptionService->getOptions(OfflineOptionService::TYPE_SUB_CLASSIFICATION);

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

        // Merge any distinct sub-classifications from offline_trainings
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

        // Merge any categories from offline_trainings and standard baseline presets
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

        // Ensure standard levels
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

        // Ensure standard types
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

        $options = [
            'type'               => $types,
            'category'           => $categories,
            'level'              => $levels,
            'sub_classification' => $subClassifications,
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
        return view('main.manage-training-schedule.create', $formData);
    }

    /**
     * Store a newly created training schedule in offline_trainings table.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'               => ['required', 'string', 'max:255'],
            'sub_course_name'     => ['nullable', 'string', 'max:255'],
            'main_classification' => ['nullable', 'string', 'max:100'],
            'sub_classification'  => ['nullable', 'string', 'max:255'],
            'category'            => ['required', 'string', 'max:100'],
            'type'                => ['nullable', 'string', 'max:50'],
            'level'               => ['nullable', 'string', 'max:50'],
            'code'                => ['nullable', 'string', 'max:100'],
            'training_code'       => ['nullable', 'string', 'max:100'],
            'instructor'          => ['required', 'string', 'max:255'],
            'location'            => ['required', 'string', 'max:255'],
            'target_employee'     => ['nullable', 'string', 'max:255'],
            'prerequisites'       => ['nullable', 'string', 'max:255'],
            'quota'               => ['required', 'integer', 'min:1'],
            'status'              => ['required', 'string', 'in:Open Registration,Running,Closed'],
            'start_date'          => ['required', 'date'],
            'end_date'            => ['nullable', 'date'],
            'start_time'          => ['required'],
            'end_time'            => ['required'],
            'description'         => ['nullable', 'string'],
            'objectives'          => ['nullable', 'string'],
            'learning_objectives' => ['nullable', 'string'],
            'syllabus_title'      => ['nullable', 'array'],
            'syllabus_desc'       => ['nullable', 'array'],
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
        $endDate = $request->filled('end_date') ? $request->input('end_date') : $request->input('start_date');

        $training = OfflineTraining::create([
            'title'               => $request->input('title'),
            'course_name'         => $request->input('title'),
            'sub_course_name'     => $request->input('sub_course_name'),
            'category'            => $request->input('category'),
            'type'                => $request->input('type'),
            'level'               => $request->input('level'),
            'main_classification' => $request->input('main_classification'),
            'sub_classification'  => $request->input('sub_classification'),
            'training_code'       => $trainingCode,
            'batch'               => $trainingCode,
            'instructor'          => $request->input('instructor'),
            'location'            => $request->input('location'),
            'venue'               => $request->input('location'),
            'target_employee'     => $request->input('target_employee'),
            'prerequisites'       => $request->input('prerequisites'),
            'quota'               => (int) $request->input('quota'),
            'status'              => $request->input('status', 'Open Registration'),
            'start_date'          => $request->input('start_date'),
            'end_date'            => $endDate,
            'finish_date'         => $endDate,
            'start_time'          => $request->input('start_time'),
            'end_time'            => $request->input('end_time'),
            'description'         => $request->input('description'),
            'learning_objectives' => $objectives,
            'syllabus'            => $syllabus,
            'created_by'          => Auth::id(),
        ]);

        // Auto sync options to offline_training_options for consistent master options
        $this->offlineOptionService->ensureOptionsFromTraining([
            'type'               => $request->input('type'),
            'category'           => $request->input('category'),
            'level'              => $request->input('level'),
            'sub_classification' => $request->input('sub_classification'),
            'instructor'         => $request->input('instructor'),
            'venue'              => $request->input('location'),
        ], 'manual', Auth::id());

        return redirect()->route('manage.training-schedule.index')
            ->with('success', 'Training schedule "' . $training->title . '" has been successfully created.');
    }

    /**
     * Show the form for editing an existing training schedule.
     */
    public function edit($id)
    {
        $training = $id instanceof OfflineTraining ? $id : OfflineTraining::findOrFail($id);
        $formData = $this->getScheduleFormData($training);

        return view('main.manage-training-schedule.edit', array_merge(['training' => $training], $formData));
    }

    /**
     * Update the specified training schedule in offline_trainings table.
     */
    public function update(Request $request, $id)
    {
        $training = $id instanceof OfflineTraining ? $id : OfflineTraining::findOrFail($id);

        $validated = $request->validate([
            'title'               => ['required', 'string', 'max:255'],
            'sub_course_name'     => ['nullable', 'string', 'max:255'],
            'main_classification' => ['nullable', 'string', 'max:100'],
            'sub_classification'  => ['nullable', 'string', 'max:255'],
            'category'            => ['required', 'string', 'max:100'],
            'type'                => ['nullable', 'string', 'max:50'],
            'level'               => ['nullable', 'string', 'max:50'],
            'code'                => ['nullable', 'string', 'max:100'],
            'training_code'       => ['nullable', 'string', 'max:100'],
            'instructor'          => ['required', 'string', 'max:255'],
            'location'            => ['required', 'string', 'max:255'],
            'target_employee'     => ['nullable', 'string', 'max:255'],
            'prerequisites'       => ['nullable', 'string', 'max:255'],
            'quota'               => ['required', 'integer', 'min:1'],
            'status'              => ['required', 'string', 'in:Open Registration,Running,Closed'],
            'start_date'          => ['required', 'date'],
            'end_date'            => ['nullable', 'date'],
            'start_time'          => ['required'],
            'end_time'            => ['required'],
            'description'         => ['nullable', 'string'],
            'objectives'          => ['nullable', 'string'],
            'learning_objectives' => ['nullable', 'string'],
            'syllabus_title'      => ['nullable', 'array'],
            'syllabus_desc'       => ['nullable', 'array'],
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
        $endDate = $request->filled('end_date') ? $request->input('end_date') : $request->input('start_date');

        $training->update([
            'title'               => $request->input('title'),
            'course_name'         => $request->input('title'),
            'sub_course_name'     => $request->input('sub_course_name'),
            'category'            => $request->input('category'),
            'type'                => $request->input('type'),
            'level'               => $request->input('level'),
            'main_classification' => $request->input('main_classification'),
            'sub_classification'  => $request->input('sub_classification'),
            'training_code'       => $trainingCode,
            'batch'               => $trainingCode,
            'instructor'          => $request->input('instructor'),
            'location'            => $request->input('location'),
            'venue'               => $request->input('location'),
            'target_employee'     => $request->input('target_employee'),
            'prerequisites'       => $request->input('prerequisites'),
            'quota'               => (int) $request->input('quota'),
            'status'              => $request->input('status', 'Open Registration'),
            'start_date'          => $request->input('start_date'),
            'end_date'            => $endDate,
            'finish_date'         => $endDate,
            'start_time'          => $request->input('start_time'),
            'end_time'            => $request->input('end_time'),
            'description'         => $request->input('description'),
            'learning_objectives' => $objectives,
            'syllabus'            => $syllabus,
        ]);

        // Auto sync options to offline_training_options for consistent master options
        $this->offlineOptionService->ensureOptionsFromTraining([
            'type'               => $request->input('type'),
            'category'           => $request->input('category'),
            'level'              => $request->input('level'),
            'sub_classification' => $request->input('sub_classification'),
            'instructor'         => $request->input('instructor'),
            'venue'              => $request->input('location'),
        ], 'manual', Auth::id());

        return redirect()->route('manage.training-schedule.index')
            ->with('success', 'Training schedule updated successfully.');
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

            // Past registration deadline (now() <= registration_close_date)
            $isPast = $endDate && ($endDate < $today || ($endDate === $today && $currentTime > $endTime));
            // Only if a single-day training scheduled for today has already commenced
            $isSingleDayRunningToday = ($startDate && $endDate && $startDate === $today && $endDate === $today && $currentTime >= $startTime);

            if ($isSingleDayRunningToday) {
                $msg = "Registration is closed for \"{$training->title}\" as today's session has already started.";
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return redirect()->back()->with('error', $msg);
            }

            if ($isPast) {
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

        $trainings = OfflineTraining::withCount([
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

        $availableQuery = OfflineTraining::withCount([
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
        if ($request->filled('category')) {
            $availableQuery->where('category', $request->input('category'));
        }

        // Filter Status
        if ($request->filled('status')) {
            $statusVal = $request->input('status');
            $statusMap = [
                'open'    => 'Open Registration',
                'running' => 'Running',
                'closed'  => 'Closed',
            ];
            $availableQuery->where('status', $statusMap[strtolower($statusVal)] ?? $statusVal);
        }

        // Filter Year
        if ($request->filled('year') && $request->input('year') !== 'all') {
            $year = (int) $request->input('year');
            $availableQuery->where(function ($q) use ($year) {
                $q->whereYear('start_date', $year)
                  ->orWhereYear('end_date', $year);
            });
        }

        // Keyword Search (q or search)
        $search = $request->input('q') ?? $request->input('search');
        if (filled($search)) {
            $search = trim($search);
            $availableQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('course_name', 'like', "%{$search}%")
                  ->orWhere('instructor', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('venue', 'like', "%{$search}%")
                  ->orWhere('training_code', 'like', "%{$search}%")
                  ->orWhere('batch', 'like', "%{$search}%");
            });
        }

        $availableTrainings = $availableQuery->latest()
            ->paginate(10)
            ->withQueryString();

        $calendarEvents = $trainings->map(function ($t) {
            $startDate = Carbon::parse($t->start_date);
            $endDate = Carbon::parse($t->end_date ?? $t->start_date);
            $enrolled = $t->registered_seats_count ?? 0;
            $quota = $t->quota ?: 1;
            $isPast = $endDate->format('Y-m-d') < Carbon::today()->format('Y-m-d');
            $isClosed = $t->status === 'Closed' || $enrolled >= $quota || $isPast;

            return [
                'id'          => (string) $t->id,
                'year'        => (int) $startDate->format('Y'),
                'month'       => (int) $startDate->format('n') - 1, // 0-indexed for JavaScript Date
                'day'         => (int) $startDate->format('j'),
                'startDate'   => $startDate->format('Y-m-d'),
                'endDate'     => $endDate->format('Y-m-d'),
                'registration_close_date' => $endDate->format('Y-m-d'),
                'registrationCloseDate'   => $endDate->format('Y-m-d'),
                'rawStatus'   => $t->status,
                'title'       => $t->title,
                'shortTitle'  => Str::limit($t->title, 16),
                'category'    => $t->category,
                'badgeClass'  => match($t->category) {
                    'Basic & Common Duties'             => 'bg-secondary-subtle text-secondary',
                    'Compliance', 'Compliance & Safety' => 'bg-warning-lt text-warning',
                    'Innovation'                        => 'bg-success-lt text-success',
                    'Job Functional', 'Technical Skill' => 'bg-info-lt text-info',
                    'Language'                          => 'bg-danger-lt text-danger',
                    'Leadership'                        => 'bg-purple-subtle text-purple',
                    default                             => 'bg-primary-lt text-primary',
                },
                'status'      => $isClosed ? 'closed' : ($t->status === 'Running' ? 'running' : 'open'),
                'isPast'      => $isPast,
                'startTime'   => substr($t->start_time, 0, 5),
                'endTime'     => substr($t->end_time, 0, 5),
                'time'        => substr($t->start_time, 0, 5) . ' - ' . substr($t->end_time, 0, 5) . ' WIB',
                'dateTime'    => $startDate->format('d F Y') . ', ' . substr($t->start_time, 0, 5) . ' - ' . substr($t->end_time, 0, 5) . ' WIB',
                'location'    => $t->location,
                'instructor'  => $t->instructor,
                'quota'       => (int) $t->quota,
                'enrolled'    => (int) $enrolled,
                'isFull'      => $enrolled >= $quota,
                'quotaText'   => "{$enrolled} / {$t->quota} Seats" . ($enrolled >= $quota ? ' (Full)' : ''),
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
            $sDate = Carbon::parse($t->start_date)->format('Y-m-d');
            $eDate = Carbon::parse($t->end_date ?? $t->start_date)->format('Y-m-d');
            return $selectedDate >= $sDate && $selectedDate <= $eDate;
        })->values();

        return view('user.trainingplan-user.index', compact('trainings', 'availableTrainings', 'calendarEvents', 'myHistory', 'selectedDate', 'todaysAgenda'));
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
     * Export Training Schedule list to CSV format with Category, Status, Month, Year filters.
     */
    public function exportSchedule(Request $request): StreamedResponse
    {
        $query = OfflineTraining::withCount('registrations');

        if ($request->filled('category') && $request->input('category') !== 'all') {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $statusVal = $request->input('status');
            $statusMap = [
                'open'    => 'Open Registration',
                'running' => 'Running',
                'closed'  => 'Closed',
            ];
            $query->where('status', $statusMap[strtolower($statusVal)] ?? $statusVal);
        }

        if ($request->filled('month') && $request->input('month') !== 'all') {
            $month = (int) $request->input('month');
            $query->where(function ($q) use ($month) {
                $q->whereMonth('start_date', $month)
                  ->orWhereMonth('end_date', $month);
            });
        }

        if ($request->filled('year') && $request->input('year') !== 'all') {
            $year = (int) $request->input('year');
            $query->where(function ($q) use ($year) {
                $q->whereYear('start_date', $year)
                  ->orWhereYear('end_date', $year);
            });
        }

        $search = $request->input('q') ?? $request->input('search');
        if (filled($search)) {
            $search = trim($search);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('course_name', 'like', "%{$search}%")
                  ->orWhere('instructor', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $records = $query->orderBy('start_date', 'asc')->get();

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
