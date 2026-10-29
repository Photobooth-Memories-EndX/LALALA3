<?php

namespace App\Http\Controllers;

use App\Exports\TaskSubmissionsExport;
use App\Models\Assesment;
use App\Services\DocumentPreviewService;
use App\Models\Content;
use App\Models\OfflineTraining;
use App\Models\Task;
use App\Models\TaskGroup;
use App\Models\TaskField;
use App\Models\TaskSubmission;
use App\Models\TrainingPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

/**
 * TaskManageController — ADMIN side.
 *
 * - index(): dashboard of PARENTS that have tasks (parent = playlist,
 *   content, or offline training). Only parents with tasks are listed.
 * - parentTasks(): tasks of one parent.
 * - The create/edit form has a Google Form-style dynamic question builder.
 */
class TaskManageController extends Controller
{
    /**
     * GET /manage/task — parents that have tasks.
     * Quick filter (like the class browser):
     *   top level  : KP OWL (online: playlist + content) vs NON KP OWL (offline training)
     *   second row : Content | Playlist pills (KP OWL only)
     */
    public function index(Request $request)
    {
        $scope = $request->input('scope', 'kp'); // kp|non_kp
        if (! in_array($scope, ['kp', 'non_kp'], true)) {
            $scope = 'kp';
        }

        $type = $request->input('parent_type', 'all'); // all|playlist|content|standalone (KP OWL only)
        if (! in_array($type, ['all', 'playlist', 'content', 'standalone'], true)) {
            $type = 'all';
        }

        $search = trim($request->input('search', ''));

        // KP OWL = online parents (playlist/content/standalone). NON KP OWL = offline training (no data yet).
        $allowedTypes = $scope === 'kp'
            ? ($type === 'all' ? ['playlist', 'content', 'standalone'] : [$type])
            : ['offline_training'];

        $baseQuery = Task::query()
            ->leftJoin('trainingplan', 'trainingplan.id', '=', 'tasks.training_plan_id')
            ->leftJoin('content', 'content.id', '=', 'tasks.content_id')
            ->leftJoin('offline_trainings', 'offline_trainings.id', '=', 'tasks.offline_training_id')
            ->leftJoin('task_groups', 'task_groups.id', '=', 'tasks.task_group_id')
            ->whereIn('tasks.parent_type', $allowedTypes)
            ->when($search !== '', fn ($q) => $q->where(function ($w) use ($search) {
                $w->where('trainingplan.title', 'like', "%{$search}%")
                  ->orWhere('content.title', 'like', "%{$search}%")
                  ->orWhere('offline_trainings.title', 'like', "%{$search}%")
                  ->orWhere('offline_trainings.course_name', 'like', "%{$search}%")
                  ->orWhere('task_groups.title', 'like', "%{$search}%")
                  ->orWhere('tasks.title', 'like', "%{$search}%");
            }))
            ->selectRaw('tasks.parent_type, COALESCE(tasks.training_plan_id, tasks.content_id, tasks.offline_training_id, tasks.task_group_id, 0) AS parent_id')
            ->selectRaw("MAX(COALESCE(trainingplan.title, content.title, offline_trainings.title, offline_trainings.course_name, task_groups.title, 'Others')) AS parent_name")
            ->selectRaw("COUNT(*) AS total, SUM(tasks.status = 'published') AS published, SUM(tasks.status = 'draft') AS draft")
            ->groupBy('tasks.parent_type', 'tasks.training_plan_id', 'tasks.content_id', 'tasks.offline_training_id', 'tasks.task_group_id')
            ->orderBy('parent_name');

        $parents = (clone $baseQuery)->paginate(10)->withQueryString();

        // Stat cards (follow the same filters).
        $allRows   = (clone $baseQuery)->get();
        $taskScope = Task::query()
            ->leftJoin('trainingplan', 'trainingplan.id', '=', 'tasks.training_plan_id')
            ->leftJoin('content', 'content.id', '=', 'tasks.content_id')
            ->leftJoin('offline_trainings', 'offline_trainings.id', '=', 'tasks.offline_training_id')
            ->whereIn('tasks.parent_type', $allowedTypes)
            ->when($search !== '', fn ($q) => $q->where(function ($w) use ($search) {
                $w->where('trainingplan.title', 'like', "%{$search}%")
                  ->orWhere('content.title', 'like', "%{$search}%")
                  ->orWhere('offline_trainings.title', 'like', "%{$search}%")
                  ->orWhere('offline_trainings.course_name', 'like', "%{$search}%")
                  ->orWhere('tasks.title', 'like', "%{$search}%");
            }));
        $taskIds   = (clone $taskScope)->pluck('tasks.id');
        $stats = [
            'parents'   => $allRows->count(),
            'tasks'     => $taskIds->count(),
            'published' => Task::whereIn('id', $taskIds)->where('status', 'published')->count(),
            'pending'   => TaskSubmission::whereIn('task_id', $taskIds)->where('status', 'pending')->count(),
        ];

        // Pending submissions per parent (keyed "type:id").
        $pendingByParent = TaskSubmission::selectRaw(
                "tasks.parent_type, COALESCE(tasks.training_plan_id, tasks.content_id, tasks.offline_training_id, tasks.task_group_id, 0) AS parent_id, COUNT(*) AS cnt"
            )
            ->join('tasks', 'tasks.id', '=', 'task_submissions.task_id')
            ->where('task_submissions.status', 'pending')
            ->groupBy('tasks.parent_type', 'tasks.training_plan_id', 'tasks.content_id', 'tasks.offline_training_id', 'tasks.task_group_id')
            ->get()
            ->mapWithKeys(fn ($r) => ["{$r->parent_type}:{$r->parent_id}" => $r->cnt]);

        $trashCount = Task::onlyTrashed()->count();

        return view('manage.task.plans', compact('parents', 'stats', 'pendingByParent', 'scope', 'type', 'search', 'trashCount'));
    }

    /**
     * GET /manage/task/{id}/qr — share page for a standalone task.
     * With a code: QR + redeem link + raw code.
     * Without a code: QR + signed link (unguessable, still private).
     * Either way the link unlocks the task for whoever visits it (must log in).
     */
    public function qr(Request $request, $id)
    {
        $task = Task::findOrFail($id);
        abort_unless($task->parent_type === 'standalone', 404);

        if ($task->access_code) {
            $redeemUrl = route('my-task.redeem-link', ['code' => $task->access_code]);
            $shareNote = 'Share this code, link or QR with specific users — only they can unlock this task.';
        } else {
            $redeemUrl = URL::signedRoute('my-task.redeem-signed', ['task' => $task->id]);
            $shareNote = 'This task has no unique code. Share this link or QR to unlock the task.';
        }

        $qrSvg = class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)
            ? (string) \SimpleSoftwareIO\QrCode\Facades\QrCode::size(240)->margin(1)->generate($redeemUrl)
            : null;

        if ($request->ajax() || $request->wantsJson() || $request->query('format') === 'json') {
            return response()->json([
                'success'     => true,
                'id'          => $task->id,
                'title'       => $task->title,
                'access_code' => $task->access_code,
                'redeem_url'  => $redeemUrl,
                'qr_svg'      => $qrSvg,
                'share_note'  => $shareNote,
            ]);
        }

        return view('manage.task.qr', compact('task', 'redeemUrl', 'qrSvg', 'shareNote'));
    }

    /**
     * GET /manage/task/trash — soft-deleted tasks. Restore or delete forever.
     */
    public function trash()
    {
        $tasks = Task::onlyTrashed()
            ->with(['creator', 'trainingPlan', 'content'])
            ->orderByDesc('deleted_at')
            ->paginate(20);

        return view('manage.task.trash', compact('tasks'));
    }

    /**
     * POST /manage/task/{id}/restore — bring a trashed task back
     * (questions, answers and files are untouched by the soft delete).
     */
    public function restore($id)
    {
        $task = Task::withTrashed()->findOrFail($id);
        $task->restore();

        return back()->with('success', 'Task restored.');
    }

    /**
     * DELETE /manage/task/{id}/force — permanent delete: task, questions,
     * submissions, attachments and answer files are wiped for real.
     */
    public function forceDestroy($id)
    {
        $task = Task::withTrashed()->findOrFail($id);

        if ($task->attachment) {
            Storage::disk('public')->delete($task->attachment);
        }

        // Delete users' per-field answer files too (DB rows cascade).
        foreach ($task->submissions as $submission) {
            foreach ($submission->answers as $answer) {
                foreach ($answer->filePaths() as $oldPath) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
        }

        $task->forceDelete();

        return back()->with('success', 'Task permanently deleted.');
    }

    /**
     * GET /manage/task/parent/{parentType}/{parentId} — tasks of one parent.
     * parentType=standalone lists tasks not tied to any placement.
     */
    public function parentTasks(Request $request, string $parentType, int $parentId)
    {
        abort_unless(array_key_exists($parentType, Task::PARENT_TYPES), 404);

        $parent = $this->resolveParent($parentType, $parentId);
        abort_if(! in_array($parentType, ['offline_training', 'standalone'], true) && ! $parent, 404);

        $parentName = $parent?->title ?? $parent?->course_name
            ?? ($parentType === 'standalone' ? 'Others'
                : ($parentType === 'offline_training' ? ('Offline Training' . ($parentId ? " #{$parentId}" : '')) : '—'));

        $parentColumn = $this->parentColumn($parentType);

        $tasks = Task::with(['content', 'creator', 'offlineTraining', 'taskGroup'])
            ->withCount([
                'submissions as pending_submissions_count'   => fn ($q) => $q->where('status', 'pending'),
                'submissions as completed_submissions_count' => fn ($q) => $q->where('status', 'completed'),
            ])
            ->where('parent_type', $parentType)
            ->when($parentColumn, function ($q) use ($parentColumn, $parentId, $parentType) {
                if ($parentType === 'standalone' && ($parentId === 0 || empty($parentId))) {
                    $q->whereNull('task_group_id');
                } else {
                    $q->where($parentColumn, $parentId);
                }
            })
            ->when($request->search, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->when($request->status, fn ($q, $st) => $q->where('status', $st))
            ->orderByDesc('created_at')
            ->get();

        return view('manage.task.index', compact('tasks', 'parentType', 'parentId', 'parentName'));
    }

    /**
     * GET /manage/task/create — new task form.
     * Accepts ?parent_type=&parent_id= to pre-select (and lock) the parent
     * when the admin comes from a parent's task list.
     */
    public function create(Request $request)
    {
        // Still needed for the Course Assignment "target course" dropdown.
        $trainingPlans = TrainingPlan::orderBy('title')->get();
        $initialFields = $this->initialFieldsFromOld();

        $lockedParent = $this->lockedParent($request);

        // Name for the currently-selected parent (old input after failed validation or from query).
        $selectedParentName = '';
        if (! $lockedParent) {
            $effectiveParentType = old('parent_type', $request->input('parent_type') ?? ($request->input('scope') === 'non_kp' ? 'offline_training' : null));
            $effectiveParentId = old('parent_id', $request->input('parent_id'));
            if ($effectiveParentType && $effectiveParentId) {
                $resolved = $this->resolveParent($effectiveParentType, (int) $effectiveParentId);
                $selectedParentName = $resolved?->title ?? $resolved?->course_name ?? '';
            }
        }

        return view('manage.task.create', compact('trainingPlans', 'initialFields', 'lockedParent', 'selectedParentName'));
    }

    /**
     * POST /manage/task/group — Create a new task container for the Others category.
     */
    public function storeGroup(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $group = TaskGroup::create([
            'title'       => trim($validated['title']),
            'description' => !empty($validated['description']) ? trim($validated['description']) : null,
            'created_by'  => Auth::id(),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'      => true,
                'group'        => $group,
                'redirect_url' => route('manage.task.parent', ['parentType' => 'standalone', 'parentId' => $group->id]),
            ]);
        }

        return redirect()
            ->route('manage.task.parent', ['parentType' => 'standalone', 'parentId' => $group->id])
            ->with('success', 'Task container "' . $group->title . '" created successfully. You can now add tasks to it.');
    }

    /**
     * AJAX — searchable parent dropdown options for the placement picker.
     * GET /manage/task/parents/{type}?q=...
     */
    public function parentOptions(Request $request, string $type)
    {
        abort_unless(array_key_exists($type, Task::PARENT_TYPES), 404);

        $q = trim($request->input('q', ''));

        $items = match ($type) {
            'playlist' => TrainingPlan::when($q !== '', fn ($qq) => $qq->where('title', 'like', "%{$q}%"))
                ->orderBy('title')->limit(50)->get(['id', 'title']),
            'content'  => Content::when($q !== '', fn ($qq) => $qq->where('title', 'like', "%{$q}%"))
                ->orderBy('title')->limit(50)->get(['id', 'title']),
            'standalone' => TaskGroup::when($q !== '', fn ($qq) => $qq->where('title', 'like', "%{$q}%"))
                ->orderBy('title')->limit(50)->get(['id', 'title']),
            'offline_training' => OfflineTraining::when($q !== '', fn ($qq) => $qq->where(function ($w) use ($q) {
                    $w->where('title', 'like', "%{$q}%")
                      ->orWhere('course_name', 'like', "%{$q}%")
                      ->orWhere('batch', 'like', "%{$q}%")
                      ->orWhere('training_code', 'like', "%{$q}%");
                }))
                ->selectRaw('id, CONCAT(COALESCE(NULLIF(title, ""), course_name, CONCAT("Offline Training #", id)), IF(batch IS NOT NULL AND batch != "", CONCAT(" (", batch, ")"), "")) as title')
                ->orderBy('title')->limit(50)->get(),
            default    => collect(),
        };

        return response()->json($items);
    }

    /**
     * AJAX — dependent dropdown: contents of the selected course.
     * (Legacy; kept for backward compatibility.)
     */
    public function trainingPlanContents(TrainingPlan $trainingPlan)
    {
        $contents = $trainingPlan->contents()->orderBy('title')->get(['id', 'title']);

        return response()->json($contents);
    }

    public function store(Request $request)
    {
        $validated = $this->validateTask($request);

        $taskData = collect($validated)->except(['action', 'fields', 'parent_type', 'parent_id'])->all();
        $taskData['created_by'] = Auth::id();
        $action = $validated['action'] ?? $request->input('action', 'draft');
        $taskData['status']     = ($action === 'publish') ? 'published' : 'draft';

        // Map the placement picker to the real columns (exactly one is set).
        $this->applyPlacement($taskData, $validated['parent_type'], $validated['parent_id'] ?? null);

        // Link columns only apply to course_assignment.
        if (($taskData['type'] ?? null) !== 'course_assignment') {
            $taskData['link_type'] = null;
            $taskData['linked_training_plan_id'] = null;
            $taskData['external_url'] = null;
        }

        // Survey column only applies to survey.
        if (($taskData['type'] ?? null) !== 'survey') {
            $taskData['survey_id'] = null;
        }

        if ($request->hasFile('attachment')) {
            $taskData['attachment'] = $request->file('attachment')
                ->store('task-attachments', 'public');
        }

        $task = Task::create($taskData);
        $this->syncFields($task, $task->needsFields() ? ($validated['fields'] ?? []) : []);

        // 'draft': save task and remain on edit page so user does not lose their inputs
        if ($action === 'draft') {
            $redirectParams = ['task' => $task->id];
            if (!empty($validated['parent_type'])) {
                $redirectParams['parent_type'] = $validated['parent_type'];
                if (!empty($validated['parent_id'])) {
                    $redirectParams['parent_id'] = $validated['parent_id'];
                }
            }
            if ($request->query('scope')) {
                $redirectParams['scope'] = $request->query('scope');
            }

            return redirect()
                ->route('manage.task.edit', $redirectParams)
                ->with('success', 'Task "' . $task->title . '" saved as draft.');
        }

        // 'draft_exit' or 'publish': redirect to parent task list
        return redirect()
            ->route('manage.task.parent', $task->parentRouteParams())
            ->with('success', $taskData['status'] === 'published'
                ? 'Task published successfully.'
                : 'Task saved as draft.');
    }

    public function edit(Request $request, Task $task)
    {
        $task->load('fields');
        $trainingPlans = TrainingPlan::orderBy('title')->get();
        $initialFields = $this->initialFieldsFromOld();

        if (empty($initialFields)) {
            $initialFields = $task->fields->map(fn ($f) => [
                'id'       => $f->id,
                'label'    => $f->label,
                'type'     => $f->type,
                'required' => (bool) $f->required,
                'options'  => $f->options ?? [],
                'correct'  => max(0, (int) array_search($f->correct_answer, $f->options ?? [])),
                'file_types'  => $f->fileTypeGroups()[0] ?? '',
                'max_files'   => $f->maxFiles(),
                'max_size_mb' => $f->maxSizeMb(),
                'allow_link'  => (bool) $f->allow_link,
            ])->all();
        }

        // Lock parent container info so Section 1 placement is hidden consistently
        $lockedParent = $this->lockedParent($request);
        if (! $lockedParent && $task->parent_type) {
            $lockedParent = [
                'type'  => $task->parent_type,
                'id'    => (int) ($task->parentId() ?? 0),
                'label' => Task::PARENT_TYPES[$task->parent_type] ?? 'Course',
                'name'  => $task->parentName(),
            ];
        }

        $selType = old('parent_type', $task->parent_type);
        $selId   = old('parent_id', $task->parentId());
        $selectedParentName = ($selType && $selId)
            ? ($this->resolveParent($selType, (int) $selId)?->title ?? '')
            : '';
        if ($selectedParentName === '') {
            $selectedParentName = $task->parentName();
        }

        return view('manage.task.edit', compact('task', 'trainingPlans', 'initialFields', 'lockedParent', 'selectedParentName'));
    }

    public function update(Request $request, Task $task)
    {
        $validated = $this->validateTask($request);

        if ($request->hasFile('attachment')) {
            if ($task->attachment) {
                Storage::disk('public')->delete($task->attachment);
            }
            $validated['attachment'] = $request->file('attachment')
                ->store('task-attachments', 'public');
        }

        // Link columns only apply to course_assignment.
        if (($validated['type'] ?? null) !== 'course_assignment') {
            $validated['link_type'] = null;
            $validated['linked_training_plan_id'] = null;
            $validated['external_url'] = null;
        }

        // Survey column only applies to survey.
        if (($validated['type'] ?? null) !== 'survey') {
            $validated['survey_id'] = null;
        }

        $action = $request->input('action');
        $taskData = collect($validated)->except(['action', 'fields', 'parent_type', 'parent_id'])->all();
        if ($action === 'publish') {
            $taskData['status'] = 'published';
        } elseif ($action === 'draft' || $action === 'draft_exit') {
            $taskData['status'] = 'draft';
        }
        $this->applyPlacement($taskData, $validated['parent_type'], $validated['parent_id'] ?? null);

        $task->update($taskData);
        $task->refresh();

        // Leaving standalone: old code redemptions no longer mean anything.
        if ($task->parent_type !== 'standalone') {
            $task->codeRedemptions()->delete();
        }

        $this->syncFields($task, $task->needsFields() ? ($validated['fields'] ?? []) : []);

        // 'draft': remain on edit page so inputs stay intact
        if ($action === 'draft') {
            $redirectParams = ['task' => $task->id];
            if (!empty($validated['parent_type'])) {
                $redirectParams['parent_type'] = $validated['parent_type'];
                if (!empty($validated['parent_id'])) {
                    $redirectParams['parent_id'] = $validated['parent_id'];
                }
            }
            if ($request->query('scope')) {
                $redirectParams['scope'] = $request->query('scope');
            }

            return redirect()
                ->route('manage.task.edit', $redirectParams)
                ->with('success', 'Task "' . $task->title . '" draft saved.');
        }

        return redirect()
            ->route('manage.task.parent', $task->parentRouteParams())
            ->with('success', $task->status === 'published' ? 'Task published successfully.' : 'Task updated successfully.');
    }

    public function destroy(Task $task)
    {
        $params = $task->parentRouteParams();

        // Soft delete → Trash. Questions, submissions and files stay intact
        // so the task can be restored. Permanent wipe lives in forceDestroy().
        $task->delete();

        return redirect()
            ->route('manage.task.parent', $params)
            ->with('success', 'Task moved to trash. You can restore it from the Trash page.');
    }

    public function publish(Task $task)
    {
        $task->update(['status' => 'published']);

        return redirect()
            ->route('manage.task.parent', $task->parentRouteParams())
            ->with('success', 'Task published successfully.');
    }

    /**
     * GET /manage/task/{task}/submissions — user submissions list.
     * Admin can download per-field answer files and mark submissions complete.
     */
    public function submissions(Request $request, Task $task)
    {
        $task->load(['fields', 'linkedPlan']);

        $submissions = $task->submissions()
            ->with('user')
            ->when($request->status, fn ($q, $st) => $q->where('status', $st))
            ->orderByDesc('submitted_at')
            ->get();

        $counts = [
            'total'     => $task->submissions()->count(),
            'pending'   => $task->submissions()->where('status', 'pending')->where('resubmit_requested', false)->count(),
            'completed' => $task->submissions()->where('status', 'completed')->count(),
            'rejected'  => $task->submissions()->where(fn ($q) => $q->where('status', 'rejected')->orWhere('resubmit_requested', true))->count(),
        ];

        return view('manage.task.submissions', compact('task', 'submissions', 'counts'));
    }

    /**
     * GET /manage/task/{task}/submissions/preview-document
     * Renders office documents (.docx, .xlsx, .xls, .csv, text) to clean standalone HTML
     * for direct in-browser embedded preview without relying on external cloud viewers.
     */
    public function previewDocument(Request $request, Task $task, DocumentPreviewService $previewService)
    {
        $path = $request->query('path');

        if (! $path || ! is_string($path)) {
            abort(400, 'Document path is required.');
        }

        // Prevent directory traversal
        if (str_contains($path, '..') || str_contains($path, "\0")) {
            abort(403, 'Invalid document path.');
        }

        $html = $previewService->render($path);

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Frame-Options' => 'SAMEORIGIN',
        ]);
    }

    /**
     * POST /manage/task/{task}/submissions/{submission}/complete
     * Admin reviews & marks a user submission approved/complete.
     */
    public function completeSubmission(Request $request, Task $task, TaskSubmission $submission)
    {
        abort_if($submission->task_id !== $task->id, 404);

        if ($submission->status === 'completed' || ($submission->status === 'rejected' && ! $submission->resubmit_requested)) {
            return back()->with('error', 'This submission has already been graded and cannot be changed.');
        }

        $validated = $request->validate([
            'score'    => 'nullable|integer|min:0|max:100',
            'feedback' => 'nullable|string|max:2000',
        ]);

        $submission->markCompleted($validated['score'] ?? null, $validated['feedback'] ?? null);

        return back()->with('success', 'Response approved and marked as complete.');
    }

    /**
     * POST /manage/task/{task}/submissions/{submission}/reject
     * Admin rejects a user submission with 2 options:
     * - 'resubmit': Request Resubmit (user can revise and resubmit answers)
     * - 'completed_rejected': Completed as Rejected (final rejection, task closed)
     */
    public function rejectSubmission(Request $request, Task $task, TaskSubmission $submission)
    {
        abort_if($submission->task_id !== $task->id, 404);

        if ($submission->status === 'completed' || ($submission->status === 'rejected' && ! $submission->resubmit_requested)) {
            return back()->with('error', 'This submission has already been graded and cannot be changed.');
        }

        $validated = $request->validate([
            'reject_type' => 'required|in:resubmit,completed_rejected',
            'score'       => 'nullable|integer|min:0|max:100',
            'feedback'    => 'required|string|max:2000',
        ]);

        $isResubmit = ($validated['reject_type'] === 'resubmit');

        $submission->update([
            'status'             => 'rejected',
            'resubmit_requested' => $isResubmit,
            'score'              => $validated['score'] ?? null,
            'feedback'           => trim($validated['feedback']),
        ]);

        $message = $isResubmit
            ? 'Submission rejected with revision requested. The user can now resubmit their answers.'
            : 'Submission marked as completed as rejected.';

        return back()->with('success', $message);
    }

    /**
     * Ask the user to resubmit: sets the flag, saves revision reason/feedback,
     * and reverts the response to pending so the user sees the resubmit form on their My Task page.
     */
    public function requestResubmit(Request $request, Task $task, TaskSubmission $submission)
    {
        abort_if($submission->task_id !== $task->id, 404);

        if ($submission->status === 'completed' || ($submission->status === 'rejected' && ! $submission->resubmit_requested)) {
            return back()->with('error', 'This submission has already been graded and cannot be changed.');
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:2000',
            'feedback' => 'nullable|string|max:2000',
        ]);

        $reason = $validated['reason'] ?? $validated['feedback'] ?? null;

        $updateData = [
            'resubmit_requested' => true,
            'status' => 'pending',
        ];

        if ($reason !== null && trim($reason) !== '') {
            $updateData['feedback'] = trim($reason);
        }

        $submission->update($updateData);

        return back()->with('success', 'Resubmit requested. The user can now resubmit their answers with your feedback.');
    }

    /**
     * GET /manage/task/{task}/submissions/export — export submissions to Excel (.xlsx).
     */
    public function exportSubmissions(Task $task)
    {
        $safeTitle = Str::slug($task->title) ?: 'task-' . $task->id;
        $filename = 'Submissions-' . $safeTitle . '-' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new TaskSubmissionsExport($task), $filename);
    }

    /**
     * POST /manage/task/{task}/submissions/bulk-complete — mark multiple submissions as completed.
     */
    public function bulkComplete(Request $request, Task $task)
    {
        $validated = $request->validate([
            'submission_ids'   => 'required|array|min:1',
            'submission_ids.*' => 'integer',
            'score'            => 'nullable|integer|min:0|max:100',
            'feedback'         => 'nullable|string|max:2000',
        ]);

        $submissions = $task->submissions()
            ->whereIn('id', $validated['submission_ids'])
            ->get();

        if ($submissions->isEmpty()) {
            return back()->with('error', 'No valid submissions selected.');
        }

        $count = 0;
        foreach ($submissions as $submission) {
            $submission->markCompleted($validated['score'] ?? null, $validated['feedback'] ?? null);
            $count++;
        }

        return back()->with('success', "{$count} responses successfully marked as complete.");
    }

    // ── Placement helpers ────────────────────────────────────────────────

    /** DB column that holds the parent id for a placement type (null = no column). */
    protected function parentColumn(string $parentType): ?string
    {
        return match ($parentType) {
            'content'          => 'content_id',
            'offline_training' => 'offline_training_id',
            'standalone'       => 'task_group_id',
            default            => 'training_plan_id',
        };
    }

    /** Load the parent model (playlist, content, or offline training). */
    protected function resolveParent(string $parentType, int $parentId)
    {
        return match ($parentType) {
            'playlist'         => TrainingPlan::find($parentId),
            'content'          => Content::find($parentId),
            'offline_training' => OfflineTraining::find($parentId),
            'standalone'       => TaskGroup::find($parentId),
            default            => null,
        };
    }

    /**
     * Write exactly one parent column; the other two are nulled.
     * Access code is typed manually by the admin and optional — empty
     * stays empty. Tasks without a code are shared via an unguessable
     * signed link/QR instead (still private).
     * Other placements never carry a code.
     */
    protected function applyPlacement(array &$taskData, string $parentType, $parentId): void
    {
        $taskData['parent_type']          = $parentType;
        $taskData['training_plan_id']    = $parentType === 'playlist' ? $parentId : null;
        $taskData['content_id']          = $parentType === 'content' ? $parentId : null;
        $taskData['offline_training_id'] = $parentType === 'offline_training' ? $parentId : null;
        $taskData['task_group_id']       = $parentType === 'standalone' ? ($parentId ?: null) : null;

        if ($parentType === 'standalone') {
            $code = trim((string) ($taskData['access_code'] ?? ''));
            $taskData['access_code'] = $code !== '' ? strtoupper($code) : null;
        } else {
            $taskData['access_code'] = null;
        }
    }

    /**
     * Locked parent for the create form: only from ?parent_type=&parent_id=
     * (never from old input — after a failed validation the picker stays editable).
     */
    protected function lockedParent(Request $request): ?array
    {
        $type = $request->input('parent_type');
        $id   = $request->input('parent_id');

        if (! $type || ! array_key_exists($type, Task::PARENT_TYPES)) {
            return null;
        }

        if ($type === 'standalone') {
            $group = $id ? TaskGroup::find($id) : null;
            return [
                'type'  => 'standalone',
                'id'    => $group ? (int) $group->id : 0,
                'label' => Task::PARENT_TYPES['standalone'],
                'name'  => $group ? $group->title : 'Others',
            ];
        }

        if (! $id) {
            return null;
        }

        $parent = $this->resolveParent($type, (int) $id);

        return [
            'type'  => $type,
            'id'    => (int) $id,
            'label' => Task::PARENT_TYPES[$type],
            'name'  => $parent?->title ?? $parent?->course_name ?? ('#' . $id),
        ];
    }

    // ── Question builder helpers ─────────────────────────────────────────

    /**
     * Validate the task form. Rules adapt to the chosen task type:
     * - course_assignment: course link (LMS course or external URL), no questions.
     * - survey: answer/choice questions (choice has options, no correct answer).
     * - file_submission / essay / action_plan: restricted field types.
     */
    protected function validateTask(Request $request): array
    {
        $type = $request->input('type');
        $allowedFieldTypes = Task::FIELD_TYPES_BY_TASK[$type] ?? [];

        // Unique code (optional, max 8 chars, alpha_dash) — same logic and process as in assessment.
        $rawCode = $request->input('unique_code') ?? $request->input('access_code');
        $code = trim((string) $rawCode);
        $request->merge(['access_code' => $code !== '' ? strtoupper($code) : null]);

        // Parent must exist in its own table.
        $parentTable = [
            'playlist'         => 'trainingplan',
            'content'          => 'content',
            'offline_training' => 'offline_trainings',
        ][$request->input('parent_type')] ?? null;

        $routeTask = $request->route('task');

        $rules = [
            'parent_type'      => ['required', Rule::in(array_keys(Task::PARENT_TYPES))],
            // Standalone tasks are not tied to any placement → no parent_id.
            'parent_id'        => $request->input('parent_type') === 'standalone'
                ? ['nullable']
                : ($parentTable
                    ? ['required', 'integer', Rule::exists($parentTable, 'id')]
                    : ['required', 'integer', 'min:1']),
            // Standalone: unique code is optional, max 8 chars, alpha_dash (same as assessment).
            'access_code'      => [
                'nullable', 'string', 'max:8', 'alpha_dash',
                Rule::unique('tasks', 'access_code')
                    ->ignore($routeTask instanceof Task ? $routeTask->id : null),
            ],
            'title'            => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'type'             => ['required', Rule::in(array_keys(Task::TYPES))],
            'deadline'         => ['nullable', 'date'],
            'attachment'       => ['nullable', 'file', 'max:5120'], // 5 MB
        ];

        if (in_array($request->route()?->getActionMethod(), ['store', 'update'], true)) {
            $rules['action'] = ['nullable', 'in:draft,draft_exit,publish'];
        }

        if ($type === 'course_assignment') {
            $rules['link_type'] = ['required', 'in:lms,external'];
            $rules['linked_training_plan_id'] = ['required_if:link_type,lms', 'nullable', 'integer', 'exists:trainingplan,id'];
            $rules['external_url'] = ['required_if:link_type,external', 'nullable', 'url', 'max:500'];
            $rules['fields'] = ['nullable', 'array'];
        } elseif ($type === 'survey') {
            $rules['survey_id'] = ['nullable', 'integer'];
            $rules['fields'] = ['nullable', 'array'];
        } else {
            $rules['fields'] = ['required', 'array', 'min:1'];
            $rules['fields.*.id'] = ['nullable', 'integer'];
            $rules['fields.*.label'] = ['required', 'string', 'max:255'];
            $rules['fields.*.type'] = ['required', Rule::in($allowedFieldTypes)];
            $rules['fields.*.required'] = ['nullable'];
            // Choice options & correct-answer index are validated in after()
            // (wildcard required_if does not work reliably with fields.*.type).
            $rules['fields.*.options'] = ['nullable', 'array'];
            $rules['fields.*.options.*'] = ['nullable', 'string', 'max:255'];
            $rules['fields.*.correct'] = ['nullable', 'integer', 'min:0'];
            // File-upload settings (ala Google Form): single dropdown.
            $rules['fields.*.file_types'] = ['nullable', 'string', Rule::in(array_keys(TaskField::FILE_TYPE_GROUPS))];
            $rules['fields.*.max_files'] = ['nullable', 'integer', 'min:1', 'max:10'];
            $rules['fields.*.max_size_mb'] = ['nullable', 'integer', 'min:1', 'max:100'];
            $rules['fields.*.allow_link'] = ['nullable'];
        }

        $validator = Validator::make($request->all(), $rules, [
            'fields.required'  => 'Add at least 1 question to this task.',
            'fields.min'       => 'Add at least 1 question to this task.',
            'parent_id.required' => 'Pick where this task should live.',
        ]);

        $validator->after(function ($validator) use ($type) {
            $fields = $validator->getData()['fields'] ?? [];
            if (! is_array($fields)) {
                return;
            }
            foreach (array_values($fields) as $i => $field) {
                if (! is_array($field) || ! in_array($field['type'] ?? null, ['choice', 'checkbox', 'dropdown'], true)) {
                    continue;
                }
                $options = array_values(array_filter(
                    array_map('trim', $field['options'] ?? []),
                    fn ($o) => $o !== ''
                ));
                if (count($options) < 2) {
                    $validator->errors()->add(
                        "fields.{$i}.options",
                        'This question needs at least 2 options.'
                    );
                }
            }
        });

        return $validator->validate();
    }

    /**
     * Pull fields from old() input (after failed validation) so the builder
     * can re-render them. Normalized shape: id/label/type/required/options/correct.
     */
    protected function initialFieldsFromOld(): array
    {
        return collect(old('fields', []))->map(fn ($f) => [
            'id'       => $f['id'] ?? null,
            'label'    => $f['label'] ?? '',
            'type'     => $f['type'] ?? 'file',
            'required' => ! empty($f['required']),
            'options'  => array_values($f['options'] ?? []),
            'correct'  => (int) ($f['correct'] ?? 0),
            'file_types'  => is_array($f['file_types'] ?? null)
                ? ($f['file_types'][0] ?? '')
                : ($f['file_types'] ?? ''),
            'max_files'   => (int) ($f['max_files'] ?? 1),
            'max_size_mb' => (int) ($f['max_size_mb'] ?? TaskField::DEFAULT_MAX_SIZE_MB),
            'allow_link'  => ! empty($f['allow_link']),
        ])->values()->all();
    }

    /**
     * Sync task fields with the builder input: update kept ones, create new
     * ones, delete removed ones (answer files of deleted fields are cleaned).
     * For multiple-choice fields, options + correct answer are resolved here.
     */
    protected function syncFields(Task $task, array $fields): void
    {
        $keepIds = [];

        foreach (array_values($fields) as $index => $input) {
            $field = null;
            if (! empty($input['id'])) {
                // Make sure the id really belongs to this task (anti-tampering).
                $field = $task->fields()->where('id', $input['id'])->first();
            }

            $data = [
                'label'          => $input['label'],
                'type'           => $input['type'],
                'required'       => ! empty($input['required']),
                'sort_order'     => $index,
                'options'        => null,
                'correct_answer' => null,
                'file_types'     => null,
                'max_files'      => 1,
                'max_size_mb'    => null,
                'allow_link'     => false,
            ];

            if ($input['type'] === 'file') {
                // File-upload settings (ala Google Form): single dropdown value.
                $ft = $input['file_types'] ?? '';
                $ft = is_array($ft) ? ($ft[0] ?? '') : $ft;
                $data['file_types'] = in_array($ft, array_keys(TaskField::FILE_TYPE_GROUPS), true) ? [$ft] : null;
                $data['max_files'] = max(1, min(10, (int) ($input['max_files'] ?? 1)));
                $sizeMb = $input['max_size_mb'] ?? null;
                $data['max_size_mb'] = ($sizeMb === null || $sizeMb === '')
                    ? null
                    : max(1, min(100, (int) $sizeMb));
                $data['allow_link'] = ! empty($input['allow_link']);
            }

            if (in_array($input['type'], ['choice', 'checkbox', 'dropdown'], true)) {
                $options = array_values(array_filter(
                    array_map('trim', $input['options'] ?? []),
                    fn ($o) => $o !== ''
                ));
                $data['options'] = $options;
            }

            if ($field) {
                $field->update($data);
            } else {
                $field = $task->fields()->create($data);
            }

            $keepIds[] = $field->id;
        }

        // Fields removed from the form → delete + clean their answer files.
        $removedFields = $task->fields()->whereNotIn('id', $keepIds)->get();
        if ($removedFields->isNotEmpty()) {
            $removedIds = $removedFields->pluck('id')->all();
            foreach ($task->submissions as $submission) {
                $answersByField = $submission->answersByField();
                foreach ($removedIds as $rId) {
                    if ($ans = $answersByField->get($rId)) {
                        foreach ($ans->filePaths() as $oldPath) {
                            Storage::disk('public')->delete($oldPath);
                        }
                    }
                }
            }
            $task->fields()->whereIn('id', $removedIds)->delete();
        }
    }
}
