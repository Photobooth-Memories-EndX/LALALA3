<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskSubmission;
use App\Models\TrainingPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * TaskManageController — sisi ADMIN (revisi course-first).
 *
 * Perubahan dari versi lama:
 *  - index() sekarang menampilkan DAFTAR TRAINING PLAN (kartu per course)
 *    lengkap dengan hitungan task, bukan lagi daftar semua task sekaligus.
 *  - planTasks() menampilkan task-task milik satu training plan terpilih.
 *  - completeSubmission() untuk admin menandai submission user selesai.
 *
 * Ganti file app/Http/Controllers/TaskManageController.php yang lama
 * dengan file ini.
 */
class TaskManageController extends Controller
{
    /**
     * GET /manage/task — pilih training plan dulu (course-first).
     */
    public function index(Request $request)
    {
        $plans = TrainingPlan::withCount([
                'tasks',
                'tasks as published_tasks_count' => fn ($q) => $q->where('status', 'published'),
                'tasks as draft_tasks_count'     => fn ($q) => $q->where('status', 'draft'),
            ])
            ->when($request->search, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->orderBy('title')
            ->get();

        return view('manage.task.plans', compact('plans'));
    }

    /**
     * GET /manage/task/plan/{trainingPlan} — daftar task milik satu course.
     */
    public function planTasks(Request $request, TrainingPlan $trainingPlan)
    {
        $tasks = Task::with(['content', 'creator'])
            ->withCount([
                'submissions as pending_submissions_count'   => fn ($q) => $q->where('status', 'pending'),
                'submissions as completed_submissions_count' => fn ($q) => $q->where('status', 'completed'),
            ])
            ->where('training_plan_id', $trainingPlan->id)
            ->when($request->search, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->when($request->status, fn ($q, $st) => $q->where('status', $st))
            ->orderByDesc('created_at')
            ->get();

        return view('manage.task.index', compact('tasks', 'trainingPlan'));
    }

    public function create()
    {
        $trainingPlans = TrainingPlan::orderBy('title')->get();

        return view('manage.task.create', compact('trainingPlans'));
    }

    /**
     * AJAX — dropdown dependent: daftar module/content dari course terpilih.
     */
    public function trainingPlanContents(TrainingPlan $trainingPlan)
    {
        $contents = $trainingPlan->contents()->orderBy('title')->get(['id', 'title']);

        return response()->json($contents);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'training_plan_id' => ['required', 'integer', 'exists:trainingplan,id'],
            'content_id'       => ['nullable', 'integer', 'exists:content,id'],
            'title'            => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'type'             => ['required', 'in:assignment'], // quiz dicakup asesmen
            'deadline'         => ['nullable', 'date'],
            'attachment'       => ['nullable', 'file', 'max:10240'],
            'action'           => ['required', 'in:draft,publish'],
        ]);

        $taskData = collect($validated)->except('action')->all();
        $taskData['created_by'] = Auth::id();
        $taskData['status']     = ($validated['action'] === 'publish') ? 'published' : 'draft';

        if ($request->hasFile('attachment')) {
            $taskData['attachment'] = $request->file('attachment')
                ->store('task-attachments', 'public');
        }

        $task = Task::create($taskData);

        return redirect()
            ->route('manage.task.plan', $task->trainingPlan)
            ->with('success', $taskData['status'] === 'published'
                ? 'Task berhasil dipublikasikan.'
                : 'Task berhasil disimpan sebagai draft.');
    }

    public function edit(Task $task)
    {
        $trainingPlans = TrainingPlan::orderBy('title')->get();

        return view('manage.task.edit', compact('task', 'trainingPlans'));
    }

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'training_plan_id' => ['required', 'integer', 'exists:trainingplan,id'],
            'content_id'       => ['nullable', 'integer', 'exists:content,id'],
            'title'            => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'type'             => ['required', 'in:assignment'],
            'deadline'         => ['nullable', 'date'],
            'attachment'       => ['nullable', 'file', 'max:10240'],
        ]);

        if ($request->hasFile('attachment')) {
            if ($task->attachment) {
                Storage::disk('public')->delete($task->attachment);
            }
            $validated['attachment'] = $request->file('attachment')
                ->store('task-attachments', 'public');
        }

        $task->update($validated);

        return redirect()
            ->route('manage.task.plan', $task->trainingPlan)
            ->with('success', 'Task berhasil diperbarui.');
    }

    public function destroy(Task $task)
    {
        $trainingPlan = $task->trainingPlan;

        if ($task->attachment) {
            Storage::disk('public')->delete($task->attachment);
        }

        $task->delete();

        return redirect()
            ->route('manage.task.plan', $trainingPlan)
            ->with('success', 'Task berhasil dihapus.');
    }

    public function publish(Task $task)
    {
        $task->update(['status' => 'published']);

        return redirect()
            ->route('manage.task.plan', $task->trainingPlan)
            ->with('success', 'Task berhasil dipublikasikan.');
    }

    /**
     * GET /manage/task/{task}/submissions — daftar pengumpulan jawaban user.
     * Admin bisa unduh file jawaban dan menandai submission selesai.
     */
    public function submissions(Request $request, Task $task)
    {
        $submissions = $task->submissions()
            ->with('user')
            ->when($request->status, fn ($q, $st) => $q->where('status', $st))
            ->orderByDesc('submitted_at')
            ->get();

        return view('manage.task.submissions', compact('task', 'submissions'));
    }

    /**
     * POST /manage/task/{task}/submissions/{submission}/complete
     * Admin mengoreksi & menandai submission user selesai.
     */
    public function completeSubmission(Request $request, Task $task, TaskSubmission $submission)
    {
        abort_if($submission->task_id !== $task->id, 404);

        $validated = $request->validate([
            'score' => 'nullable|integer|min:0|max:100',
            'feedback' => 'nullable|string|max:2000',
        ]);

        $submission->markCompleted($validated['score'] ?? null, $validated['feedback'] ?? null);

        return back()->with('success', 'Submission ditandai selesai.');
    }
}
