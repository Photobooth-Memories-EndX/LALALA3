<?php

namespace App\Http\Controllers;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingRegister;
use App\Models\OfflineTrainingUser;
use App\Services\Schedule\OfflineTrainingCompletionService;
use App\Services\Schedule\OfflineTrainingRegistrationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class OfflineTrainingController extends Controller
{
    /**
     * Display Manage Training Schedule page with live records and live participant tracking.
     */
    public function index(Request $request)
    {
        $trainings = OfflineTraining::forSchedule()
            ->with(['registrations.user', 'creator'])
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
            ])
            ->orderBy('start_date', 'asc')
            ->get();

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
     * Show the form for creating a new training schedule.
     */
    public function create()
    {
        return view('main.manage-training-schedule.create');
    }

    /**
     * Store a newly created training schedule in offline_trainings table.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'               => ['required', 'string', 'max:255'],
            'category'            => ['required', 'string', 'max:100'],
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
            'category'            => $request->input('category'),
            'training_code'       => $trainingCode,
            'instructor'          => $request->input('instructor'),
            'location'            => $request->input('location'),
            'target_employee'     => $request->input('target_employee'),
            'prerequisites'       => $request->input('prerequisites'),
            'quota'               => (int) $request->input('quota'),
            'status'              => $request->input('status', 'Open Registration'),
            'start_date'          => $request->input('start_date'),
            'end_date'            => $endDate,
            'start_time'          => $request->input('start_time'),
            'end_time'            => $request->input('end_time'),
            'description'         => $request->input('description'),
            'learning_objectives' => $objectives,
            'syllabus'            => $syllabus,
            'created_by'          => Auth::id(),
        ]);

        return redirect()->route('manage.training-schedule.index')
            ->with('success', 'Training schedule "' . $training->title . '" has been successfully created.');
    }

    /**
     * Route handler for Participant Action button:
     * Prepares view and redirects to Participant Tracking & Approvals tab for the selected training.
     */
    public function participants($trainingId)
    {
        $training = OfflineTraining::forSchedule()->findOrFail($trainingId);

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
    public function userIndex()
    {
        $userId = Auth::id();

        $trainings = OfflineTraining::forSchedule()
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

        $availableTrainings = $trainings->where('status', '!=', 'Closed')->values();

        $calendarEvents = $trainings->map(function ($t) {
            $startDate = Carbon::parse($t->start_date);
            $endDate = Carbon::parse($t->end_date ?? $t->start_date);
            $enrolled = $t->registered_seats_count ?? 0;
            $quota = $t->quota ?: 1;
            $isClosed = $t->status === 'Closed' || $enrolled >= $quota;

            return [
                'id'          => (string) $t->id,
                'year'        => (int) $startDate->format('Y'),
                'month'       => (int) $startDate->format('n') - 1, // 0-indexed for JavaScript Date
                'day'         => (int) $startDate->format('j'),
                'startDate'   => $startDate->format('Y-m-d'),
                'endDate'     => $endDate->format('Y-m-d'),
                'rawStatus'   => $t->status,
                'title'       => $t->title,
                'shortTitle'  => Str::limit($t->title, 16),
                'category'    => $t->category,
                'badgeClass'  => match($t->category) {
                    'Technical Skill'     => 'bg-info-lt text-info',
                    'Compliance & Safety' => 'bg-warning-lt text-warning',
                    'Leadership'          => 'bg-purple-subtle text-purple',
                    default               => 'bg-primary-lt text-primary',
                },
                'status'      => $isClosed ? 'closed' : 'open',
                'time'        => substr($t->start_time, 0, 5) . ' - ' . substr($t->end_time, 0, 5) . ' WIB',
                'dateTime'    => $startDate->format('d F Y') . ', ' . substr($t->start_time, 0, 5) . ' - ' . substr($t->end_time, 0, 5) . ' WIB',
                'location'    => $t->location,
                'instructor'  => $t->instructor,
                'description' => $t->description ?? '',
                'quota'       => "{$enrolled} / {$t->quota} Seats" . ($enrolled >= $t->quota ? ' (Full)' : ''),
            ];
        })->values()->toArray();

        // Build live My History for authenticated user
        $myRegistrations = collect();
        if ($userId) {
            $myRegistrations = OfflineTrainingRegister::with('training')
                ->where('user_id', $userId)
                ->orderBy('registered_at', 'desc')
                ->get()
                ->map(function ($reg, $idx) {
                    $t = $reg->training;
                    return (object)[
                        'id'             => $reg->id,
                        'registration_id'=> $reg->id,
                        'modal_id'       => $t->id ?? $reg->id,
                        'title'          => $t->title ?? 'Training Schedule',
                        'category'       => $t->category ?? 'General',
                        'start_date'     => $t->start_date ?? $reg->registered_at,
                        'end_date'       => $t->end_date ?? null,
                        'start_time'     => $t->start_time ?? '08:00:00',
                        'end_time'       => $t->end_time ?? '16:00:00',
                        'location'       => $t->location ?? '-',
                        'instructor'     => $t->instructor ?? '-',
                        'description'    => $t->description ?? '',
                        'status'         => $reg->status, // 'Pending', 'Approved', 'Completed', 'Rejected'
                        'can_cancel'     => ($reg->status === 'Pending'),
                    ];
                });
        }

        $myHistory = $myRegistrations;

        return view('user.trainingplan-user.index', compact('trainings', 'availableTrainings', 'calendarEvents', 'myHistory'));
    }
}
