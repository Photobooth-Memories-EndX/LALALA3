<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\Task;
use App\Models\TaskField;
use App\Models\TaskSubmission;
use App\Models\TrainingPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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

        $type = $request->input('parent_type', 'playlist'); // playlist|content (KP OWL only)
        if (! in_array($type, ['playlist', 'content'], true)) {
            $type = 'playlist';
        }

        $search = trim($request->input('search', ''));

        // KP OWL = online parents (playlist/content). NON KP OWL = offline training (no data yet).
        $allowedTypes = $scope === 'kp' ? [$type] : ['offline_training'];

        $baseQuery = Task::query()
            ->leftJoin('trainingplan', 'trainingplan.id', '=', 'tasks.training_plan_id')
            ->leftJoin('content', 'content.id', '=', 'tasks.content_id')
            ->whereIn('tasks.parent_type', $allowedTypes)
            ->when($search !== '', fn ($q) => $q->where(function ($w) use ($search) {
                $w->where('trainingplan.title', 'like', "%{$search}%")
                  ->orWhere('content.title', 'like', "%{$search}%");
            }))
            ->selectRaw('tasks.parent_type, COALESCE(tasks.training_plan_id, tasks.content_id, tasks.offline_training_id) AS parent_id')
            ->selectRaw('MAX(COALESCE(trainingplan.title, content.title)) AS parent_name')
            ->selectRaw("COUNT(*) AS total, SUM(tasks.status = 'published') AS published, SUM(tasks.status = 'draft') AS draft")
            ->groupBy('tasks.parent_type', 'tasks.training_plan_id', 'tasks.content_id', 'tasks.offline_training_id')
            ->orderBy('parent_name');

        $parents = (clone $baseQuery)->paginate(10)->withQueryString();

        // Stat cards (follow the same filters).
        $allRows   = (clone $baseQuery)->get();
        $taskScope = Task::query()
            ->leftJoin('trainingplan', 'trainingplan.id', '=', 'tasks.training_plan_id')
            ->leftJoin('content', 'content.id', '=', 'tasks.content_id')
            ->whereIn('tasks.parent_type', $allowedTypes)
            ->when($search !== '', fn ($q) => $q->where(function ($w) use ($search) {
                $w->where('trainingplan.title', 'like', "%{$search}%")
                  ->orWhere('content.title', 'like', "%{$search}%");
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
                "tasks.parent_type, COALESCE(tasks.training_plan_id, tasks.content_id, tasks.offline_training_id) AS parent_id, COUNT(*) AS cnt"
            )
            ->join('tasks', 'tasks.id', '=', 'task_submissions.task_id')
            ->where('task_submissions.status', 'pending')
            ->groupBy('tasks.parent_type', 'tasks.training_plan_id', 'tasks.content_id', 'tasks.offline_training_id')
            ->get()
            ->mapWithKeys(fn ($r) => ["{$r->parent_type}:{$r->parent_id}" => $r->cnt]);

        return view('manage.task.plans', compact('parents', 'stats', 'pendingByParent', 'scope', 'type', 'search'));
    }

    /**
     * GET /manage/task/parent/{parentType}/{parentId} — tasks of one parent.
     */
    public function parentTasks(Request $request, string $parentType, int $parentId)
    {
        abort_unless(array_key_exists($parentType, Task::PARENT_TYPES), 404);

        $parent = $this->resolveParent($parentType, $parentId);
        abort_if($parentType !== 'offline_training' && ! $parent, 404);

        $parentName = $parent?->title
            ?? ($parentType === 'offline_training' ? 'Offline Training' : '—');

        $tasks = Task::with(['content', 'creator'])
            ->withCount([
                'submissions as pending_submissions_count'   => fn ($q) => $q->where('status', 'pending'),
                'submissions as completed_submissions_count' => fn ($q) => $q->where('status', 'completed'),
            ])
            ->where('parent_type', $parentType)
            ->where($this->parentColumn($parentType), $parentId)
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

        // Name for the currently-selected parent (old input after failed validation).
        $selectedParentName = '';
        if (! $lockedParent && old('parent_type') && old('parent_id')) {
            $selectedParentName = $this->resolveParent(old('parent_type'), (int) old('parent_id'))?->title ?? '';
        }

        return view('manage.task.create', compact('trainingPlans', 'initialFields', 'lockedParent', 'selectedParentName'));
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
            // Offline training data has not been merged yet.
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
        $taskData['status']     = ($validated['action'] === 'publish') ? 'published' : 'draft';

        // Map the placement picker to the real columns (exactly one is set).
        $this->applyPlacement($taskData, $validated['parent_type'], $validated['parent_id']);

        // Link columns only apply to course_assignment.
        if (($taskData['type'] ?? null) !== 'course_assignment') {
            $taskData['link_type'] = null;
            $taskData['linked_training_plan_id'] = null;
            $taskData['external_url'] = null;
        }

        if ($request->hasFile('attachment')) {
            $taskData['attachment'] = $request->file('attachment')
                ->store('task-attachments', 'public');
        }

        $task = Task::create($taskData);
        $this->syncFields($task, $task->isCourseAssignment() ? [] : ($validated['fields'] ?? []));

        return redirect()
            ->route('manage.task.parent', $task->parentRouteParams())
            ->with('success', $taskData['status'] === 'published'
                ? 'Task published successfully.'
                : 'Task saved as draft.');
    }

    public function edit(Task $task)
    {
        $task->load('fields');
        $trainingPlans = TrainingPlan::orderBy('title')->get();
        $initialFields = $this->initialFieldsFromOld();

        if (empty($initialFields)) {
            $initialFields = $task->fields->map(fn ($f) => [
                'id'       => $f->id,
                'label'    => $f->label,
                'type'     => $f->type,
                'required' => $f->required,
                'options'  => $f->options ?? [],
                'correct'  => max(0, (int) array_search($f->correct_answer, $f->options ?? [])),
            ])->all();
        }

        // Edit keeps the placement picker (admin may move the task).
        $lockedParent = null;
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

        $taskData = collect($validated)->except(['fields', 'parent_type', 'parent_id'])->all();
        $this->applyPlacement($taskData, $validated['parent_type'], $validated['parent_id']);

        $task->update($taskData);
        $task->refresh();
        $this->syncFields($task, $task->isCourseAssignment() ? [] : ($validated['fields'] ?? []));

        return redirect()
            ->route('manage.task.parent', $task->parentRouteParams())
            ->with('success', 'Task updated successfully.');
    }

    public function destroy(Task $task)
    {
        $params = $task->parentRouteParams();

        if ($task->attachment) {
            Storage::disk('public')->delete($task->attachment);
        }

        // Delete users' per-field answer files too (DB rows cascade).
        foreach ($task->submissions as $submission) {
            foreach ($submission->answers as $answer) {
                if ($answer->file_path) {
                    Storage::disk('public')->delete($answer->file_path);
                }
            }
        }

        $task->delete();

        return redirect()
            ->route('manage.task.parent', $params)
            ->with('success', 'Task deleted successfully.');
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
            ->with(['user', 'answers.field'])
            ->when($request->status, fn ($q, $st) => $q->where('status', $st))
            ->orderByDesc('submitted_at')
            ->get();

        return view('manage.task.submissions', compact('task', 'submissions'));
    }

    /**
     * POST /manage/task/{task}/submissions/{submission}/complete
     * Admin reviews & marks a user submission complete.
     */
    public function completeSubmission(Request $request, Task $task, TaskSubmission $submission)
    {
        abort_if($submission->task_id !== $task->id, 404);

        $validated = $request->validate([
            'score' => 'nullable|integer|min:0|max:100',
            'feedback' => 'nullable|string|max:2000',
        ]);

        $submission->markCompleted($validated['score'] ?? null, $validated['feedback'] ?? null);

        return back()->with('success', 'Submission marked as complete.');
    }

    // ── Placement helpers ────────────────────────────────────────────────

    /** DB column that holds the parent id for a placement type. */
    protected function parentColumn(string $parentType): string
    {
        return match ($parentType) {
            'content'          => 'content_id',
            'offline_training' => 'offline_training_id',
            default            => 'training_plan_id',
        };
    }

    /** Load the parent model (null for offline training — no data yet). */
    protected function resolveParent(string $parentType, int $parentId)
    {
        return match ($parentType) {
            'playlist' => TrainingPlan::find($parentId),
            'content'  => Content::find($parentId),
            default    => null,
        };
    }

    /** Write exactly one parent column; the other two are nulled. */
    protected function applyPlacement(array &$taskData, string $parentType, $parentId): void
    {
        $taskData['parent_type']          = $parentType;
        $taskData['training_plan_id']    = $parentType === 'playlist' ? $parentId : null;
        $taskData['content_id']          = $parentType === 'content' ? $parentId : null;
        $taskData['offline_training_id'] = $parentType === 'offline_training' ? $parentId : null;
    }

    /**
     * Locked parent for the create form: only from ?parent_type=&parent_id=
     * (never from old input — after a failed validation the picker stays editable).
     */
    protected function lockedParent(Request $request): ?array
    {
        $type = $request->input('parent_type');
        $id   = $request->input('parent_id');

        if (! $type || ! $id || ! array_key_exists($type, Task::PARENT_TYPES)) {
            return null;
        }

        $parent = $this->resolveParent($type, (int) $id);

        return [
            'type'  => $type,
            'id'    => (int) $id,
            'label' => Task::PARENT_TYPES[$type],
            'name'  => $parent?->title ?? ('#' . $id),
        ];
    }

    // ── Question builder helpers ─────────────────────────────────────────

    /**
     * Validate the task form. Rules adapt to the chosen task type:
     * - course_assignment: course link (LMS course or external URL), no questions.
     * - assessment: multiple-choice questions with options + correct answer.
     * - survey: answer/choice questions (choice has options, no correct answer).
     * - file_submission / essay / action_plan: restricted field types.
     */
    protected function validateTask(Request $request): array
    {
        $type = $request->input('type');
        $allowedFieldTypes = Task::FIELD_TYPES_BY_TASK[$type] ?? [];

        // Parent must exist in its own table (offline: no table yet).
        $parentTable = [
            'playlist' => 'trainingplan',
            'content'  => 'content',
        ][$request->input('parent_type')] ?? null;

        $rules = [
            'parent_type'      => ['required', Rule::in(array_keys(Task::PARENT_TYPES))],
            'parent_id'        => $parentTable
                ? ['required', 'integer', Rule::exists($parentTable, 'id')]
                : ['required', 'integer', 'min:1'],
            'title'            => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'type'             => ['required', Rule::in(array_keys(Task::TYPES))],
            'deadline'         => ['nullable', 'date'],
            'attachment'       => ['nullable', 'file', 'max:5120'], // 5 MB
        ];

        if ($request->route()->getActionMethod() === 'store') {
            $rules['action'] = ['required', 'in:draft,publish'];
        }

        if ($type === 'course_assignment') {
            $rules['link_type'] = ['required', 'in:lms,external'];
            $rules['linked_training_plan_id'] = ['required_if:link_type,lms', 'nullable', 'integer', 'exists:trainingplan,id'];
            $rules['external_url'] = ['required_if:link_type,external', 'nullable', 'url', 'max:500'];
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
                if (! is_array($field) || ($field['type'] ?? null) !== 'choice') {
                    continue;
                }
                $options = array_values(array_filter(
                    array_map('trim', $field['options'] ?? []),
                    fn ($o) => $o !== ''
                ));
                if (count($options) < 2) {
                    $validator->errors()->add(
                        "fields.{$i}.options",
                        'Multiple choice needs at least 2 options.'
                    );
                }
                if ($type === 'assessment') {
                    $ci = $field['correct'] ?? null;
                    if ($ci === null || ! isset($options[(int) $ci])) {
                        $validator->errors()->add(
                            "fields.{$i}.correct",
                            'Tick the one correct answer for this quiz question.'
                        );
                    }
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
            ];

            if ($input['type'] === 'choice') {
                $options = array_values(array_filter(
                    array_map('trim', $input['options'] ?? []),
                    fn ($o) => $o !== ''
                ));
                $data['options'] = $options;
                if ($task->isAssessment()) {
                    // Quiz: exactly one correct option per question.
                    $ci = (int) ($input['correct'] ?? 0);
                    $data['correct_answer'] = $options[$ci] ?? ($options[0] ?? null);
                }
            }

            if ($field) {
                $field->update($data);
            } else {
                $field = $task->fields()->create($data);
            }

            $keepIds[] = $field->id;
        }

        // Fields removed from the form → delete + clean their answer files.
        $task->fields()->whereNotIn('id', $keepIds)->with('answers')->get()
            ->each(function (TaskField $field) {
                foreach ($field->answers as $answer) {
                    if ($answer->file_path) {
                        Storage::disk('public')->delete($answer->file_path);
                    }
                }
                $field->delete(); // answers cascade
            });
    }
}
