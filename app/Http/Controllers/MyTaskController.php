<?php

namespace App\Http\Controllers;

use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\Task;
use App\Models\TaskSubmission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * MyTaskController — USER side.
 *
 * My Task lives as a TAB inside the My Learning Dashboard
 * (not a standalone page, not a sidebar menu).
 * Users only see published tasks from playlists / contents they have
 * OPENED/CLICKED, then submit answers PER FIELD as defined by the task
 * builder (Google Form style): multiple file uploads + text answers.
 * Flow: not_started → submitted (pending) → completed (reviewed by admin).
 */
class MyTaskController extends Controller
{
    /**
     * GET /my-learning/partial/my-task — fragment tab My Task.
     * Dipanggil via AJAX oleh dashboard-script.blade.php.
     * Filter: ?my_task_status=all|not_started|submitted|completed|overdue
     */
    public function tabPartial(Request $request)
    {
        $user = $request->user();

        $tasks = $this->tasksFor($user);

        $filter = $request->get('my_task_status', 'all');
        if ($filter !== 'all') {
            $tasks = $tasks->filter(fn ($task) => $task->userStatusFor($user) === $filter)->values();
        }

        $summary = [
            'total'       => $tasks->count(),
            'not_started' => $tasks->filter(fn ($t) => $t->userStatusFor($user) === 'not_started')->count(),
            'submitted'   => $tasks->filter(fn ($t) => $t->userStatusFor($user) === 'submitted')->count(),
            'completed'   => $tasks->filter(fn ($t) => $t->userStatusFor($user) === 'completed')->count(),
            'overdue'     => $tasks->filter(fn ($t) => $t->userStatusFor($user) === 'overdue')->count(),
        ];

        return view('user.learning.dashboard-partials.my-task', compact('tasks', 'summary', 'filter'));
    }

    /**
     * GET /my-task — backward compatibility: arahkan ke tab My Task
     * di My Learning Dashboard.
     */
    public function index(Request $request)
    {
        return redirect()->route('user.learning.dashboard', ['learning_tab' => 'my-task']);
    }

    /**
     * POST /my-task/{task}/submit — user submits answers.
     *
     * Behaviour depends on the task type (mentor revision 2026-10-01):
     * - course_assignment: no questions — user just confirms they did it (pending review).
     * - assessment: multiple choice, auto-scored, marked completed immediately.
     * - others: per-field answers (file/text/choice), pending admin review.
     */
    public function submit(Request $request, Task $task)
    {
        abort_if($task->status !== 'published', 404);

        // User must have opened the parent (playlist / content) this task belongs to.
        abort_unless(
            $this->userCanAccess($task, $request->user()->id),
            403,
            'Open the playlist or content first to work on its tasks.'
        );

        $task->load('fields');
        $user = $request->user();
        $dashboard = redirect()->route('user.learning.dashboard', ['learning_tab' => 'my-task']);

        // ── 1. Course Assignment: confirm completion, no questions ──
        if ($task->isCourseAssignment()) {
            TaskSubmission::updateOrCreate(
                ['task_id' => $task->id, 'user_id' => $user->id],
                ['status' => 'pending', 'submitted_at' => now()]
            );

            return $dashboard->with('success', 'Course assignment marked as done. Waiting for admin review.');
        }

        // ── 2. Assessment/Quiz: auto-scored multiple choice (one correct option) ──
        if ($task->isAssessment()) {
            $rules = [];
            foreach ($task->fields as $field) {
                $rules["fields.{$field->id}"] = [
                    'required', 'string', Rule::in($field->options ?? []),
                ];
            }
            $validated = $request->validate($rules, [
                'fields.*.required' => 'Please answer all quiz questions.',
            ]);
            $inputFields = $validated['fields'] ?? [];

            $total = $task->fields->count();
            $correctCount = $task->fields->filter(
                fn ($field) => ($inputFields[$field->id] ?? null) === $field->correct_answer
            )->count();
            $score = $total > 0 ? (int) round($correctCount / $total * 100) : 0;

            $submission = TaskSubmission::updateOrCreate(
                ['task_id' => $task->id, 'user_id' => $user->id],
                ['status' => 'completed', 'score' => $score, 'submitted_at' => now()]
            );

            foreach ($task->fields as $field) {
                $submission->answers()->updateOrCreate(
                    ['task_field_id' => $field->id],
                    ['answer_text' => $inputFields[$field->id] ?? null, 'file_path' => null]
                );
            }

            return $dashboard->with(
                'success',
                "Quiz submitted! Your score: {$score} ({$correctCount}/{$total} correct)."
            );
        }

        // ── 3. Other types: per-field answers, pending admin review ──
        $existing = TaskSubmission::where('task_id', $task->id)
            ->where('user_id', $user->id)
            ->with('answers')
            ->first();
        $existingAnswers = $existing ? $existing->answers->keyBy('task_field_id') : collect();

        // Validasi dinamis mengikuti field task.
        $rules = [];
        foreach ($task->fields as $field) {
            $key = "fields.{$field->id}";
            if ($field->isFile()) {
                $alreadyUploaded = isset($existingAnswers[$field->id])
                    && $existingAnswers[$field->id]->file_path;
                // Saat kirim ulang: file wajib hanya jika belum ada file tersimpan.
                $rules[$key] = [
                    ($field->required && ! $alreadyUploaded) ? 'required' : 'nullable',
                    'file', 'max:5120', // 5 MB per file
                ];
            } elseif ($field->isChoice()) {
                $rules[$key] = [
                    $field->required ? 'required' : 'nullable',
                    'string', Rule::in($field->options ?? []),
                ];
            } else {
                $rules[$key] = [
                    $field->required ? 'required' : 'nullable',
                    'string', 'max:10000',
                ];
            }
        }
        $validated = $request->validate($rules);

        $inputFields   = $validated['fields'] ?? [];
        $uploadedFiles = $request->file('fields', []);

        $submission = TaskSubmission::updateOrCreate(
            ['task_id' => $task->id, 'user_id' => $user->id],
            [
                'status'       => 'pending',
                'submitted_at' => now(),
            ]
        );

        foreach ($task->fields as $field) {
            $answer = $submission->answers()->firstOrNew([
                'task_field_id' => $field->id,
            ]);

            if ($field->isFile()) {
                $file = $uploadedFiles[$field->id] ?? null;
                if ($file) {
                    // Hapus file lama jika user upload ulang field ini.
                    if ($answer->file_path) {
                        Storage::disk('public')->delete($answer->file_path);
                    }
                    $answer->file_path = $file->store("task-submissions/{$task->id}", 'public');
                }
                $answer->answer_text = null;
            } else {
                $answer->answer_text = $inputFields[$field->id] ?? null;
            }

            $answer->save();
        }

        return $dashboard->with('success', 'Answers submitted successfully. Waiting for admin review.');
    }

    /**
     * Published tasks from playlists AND contents the user has opened,
     * nearest deadline first.
     * (Offline training has no tracking table yet, so its tasks
     * don't appear for users for now.)
     */
    protected function tasksFor(User $user)
    {
        $planIds    = $this->enrolledPlanIds($user->id);
        $contentIds = $this->openedContentIds($user->id);

        return Task::with(['trainingPlan', 'linkedPlan', 'content', 'fields' => fn ($q) => $q->ordered()])
            ->with([
                'submissions' => fn ($q) => $q->where('user_id', $user->id),
                'submissions.answers',
            ])
            ->published()
            ->where(function ($q) use ($planIds, $contentIds) {
                // Playlists the user opened (original logic).
                $q->where(function ($w) use ($planIds) {
                    $w->where('parent_type', 'playlist')
                      ->whereIn('training_plan_id', $planIds);
                })
                // Contents the user opened (same logic, other parent type).
                ->orWhere(function ($w) use ($contentIds) {
                    $w->where('parent_type', 'content')
                      ->whereIn('content_id', $contentIds);
                });
            })
            ->orderByRaw('deadline IS NULL, deadline ASC')
            ->get();
    }

    /**
     * May this user open/work on this task? Same rule as the listing:
     * the user must have opened the task's parent.
     */
    protected function userCanAccess(Task $task, int $userId): bool
    {
        return match ($task->parent_type) {
            'content'          => in_array($task->content_id, $this->openedContentIds($userId)),
            'offline_training' => false, // no tracking table yet
            default            => in_array($task->training_plan_id, $this->enrolledPlanIds($userId)),
        };
    }

    /**
     * ID training plan yang pernah diklik/dibuka user.
     *
     * Baris di playlist_content_users dibuat saat user membuka sebuah
     * konten di dalam course (PlaylistUserController@view), jadi
     * daftar ini = course yang benar-benar pernah user klik —
     * bukan semua course yang kontennya kebetulan ia buka.
     */
    protected function enrolledPlanIds(int $userId): array
    {
        return PlaylistContentUser::where('user_id', $userId)
            ->distinct()
            ->pluck('trainingplan_id')
            ->values()
            ->all();
    }

    /**
     * ID content yang pernah diklik/dibuka user.
     *
     * Baris di content_users dibuat saat user membuka sebuah content
     * (ContentUserController), jadi daftar ini = content yang
     * benar-benar pernah user klik.
     */
    protected function openedContentIds(int $userId): array
    {
        return ContentUser::where('user_id', $userId)
            ->distinct()
            ->pluck('content_id')
            ->values()
            ->all();
    }
}
