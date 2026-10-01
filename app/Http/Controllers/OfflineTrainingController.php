<?php

namespace App\Http\Controllers;

use App\Models\OfflineTraining;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class OfflineTrainingController extends Controller
{
    /**
     * Display Manage Training Schedule page with live records.
     */
    public function index()
    {
        $trainings = OfflineTraining::forSchedule()
            ->withCount('participants')
            ->orderBy('start_date', 'asc')
            ->get();

        return view('main.manage-training-schedule.index', compact('trainings'));
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
     * Display User Training Plan page with Calendar & Available Training tabs.
     */
    public function userIndex()
    {
        $trainings = OfflineTraining::forSchedule()
            ->withCount('participants')
            ->orderBy('start_date', 'asc')
            ->get();

        $availableTrainings = OfflineTraining::forSchedule()
            ->withCount('participants')
            ->where('status', '!=', 'Closed')
            ->orderBy('start_date', 'asc')
            ->get();

        $calendarEvents = $trainings->map(function ($t) {
            $startDate = Carbon::parse($t->start_date);
            $endDate = Carbon::parse($t->end_date ?? $t->start_date);
            $enrolled = $t->participants_count ?? 0;
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
                'description' => $t->description,
                'quota'       => "{$enrolled} / {$t->quota} Seats" . ($enrolled >= $t->quota ? ' (Full)' : ''),
            ];
        })->values()->toArray();

        return view('user.trainingplan-user.index', compact('trainings', 'availableTrainings', 'calendarEvents'));
    }
}
