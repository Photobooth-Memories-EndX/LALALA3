<?php

namespace App\Http\Controllers;

use App\Models\PlaylistContentUser;
use App\Models\Task;
use App\Models\TaskSubmission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * MyTaskController — sisi USER.
 *
 * My Task tampil sebagai TAB di dalam My Learning Dashboard
 * (bukan halaman sendiri, bukan menu sidebar).
 * User hanya melihat task yang berstatus published dari training plan
 * yang PERNAH IA KLIK/BUKA (course yang ia ikuti), lalu mengumpulkan
 * jawaban via upload file.
 * Alur: not_started → submitted (pending) → completed (dikoreksi admin).
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
     * POST /my-task/{task}/submit — user upload file jawaban (assignment).
     * Membuat/memperbarui 1 submission berstatus pending.
     */
    public function submit(Request $request, Task $task)
    {
        abort_if($task->status !== 'published', 404);

        // User harus pernah membuka training plan milik task ini.
        abort_unless(
            in_array($task->training_plan_id, $this->enrolledPlanIds($request->user()->id)),
            403,
            'Buka course ini dulu untuk mengerjakan task-nya.'
        );

        $request->validate([
            'answer' => 'nullable|string|max:10000',
            'file'   => 'required|file|max:20480', // 20 MB, sesuaikan
        ]);

        $user = $request->user();

        // Hapus file lama jika user submit ulang.
        $old = $task->submissionFor($user);
        if ($old && $old->file_path) {
            Storage::disk('public')->delete($old->file_path);
        }

        $path = $request->file('file')->store('task-submissions', 'public');

        TaskSubmission::updateOrCreate(
            ['task_id' => $task->id, 'user_id' => $user->id],
            [
                'answer'       => $request->input('answer'),
                'file_path'    => $path,
                'status'       => 'pending',
                'submitted_at' => now(),
            ]
        );

        return redirect()->route('user.learning.dashboard', ['learning_tab' => 'my-task'])
            ->with('success', 'Jawaban berhasil dikumpulkan. Menunggu koreksi admin.');
    }

    /**
     * Task published dari training plan yang pernah dibuka user,
     * terurut deadline terdekat dulu.
     */
    protected function tasksFor(User $user)
    {
        return Task::with(['trainingPlan', 'content'])
            ->with(['submissions' => fn ($q) => $q->where('user_id', $user->id)])
            ->published()
            ->whereIn('training_plan_id', $this->enrolledPlanIds($user->id))
            ->orderByRaw('deadline IS NULL, deadline ASC')
            ->get();
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
}
