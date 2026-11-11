<?php

namespace App\Http\Controllers;

use App\Models\ContentUser;
use App\Models\OfflineTraining;
use App\Models\OfflineTrainingRegister;
use App\Models\OfflineTrainingUser;
use App\Models\PlaylistContentUser;
use App\Models\AssesmentResult;
use App\Models\Task;
use App\Models\TaskCodeRedemption;
use App\Models\TaskPlaylistUnlock;
use App\Models\TrainingPlanDetail;
use App\Models\TaskSubmission;
use App\Models\User;
use App\Http\Controllers\LearningAssessmentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
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
     * Filter: ?my_task_filter=all|playlist|content|asesment|others (or legacy status filters)
     */
    public function tabPartial(Request $request)
    {
        $user = $request->user();

        $tasks = $this->tasksFor($user);

        // LMS-linked Course Assignments complete automatically once the
        // linked course reaches 100% progress.
        $this->syncCourseCompletions($user, $tasks);

        $filter = $request->get('my_task_filter', $request->get('my_task_status', 'all'));
        if ($filter === 'assessment') {
            $filter = 'asesment';
        }
        $search = trim((string) $request->get('my_task_q', ''));

        // Group tasks per parent (playlist / content / offline_training / standalone)
        $taskGroups = $tasks
            ->groupBy(fn ($task) => $task->parent_type . ':' . $task->parentId())
            ->map(function ($groupTasks, $key) use ($user) {
                /** @var \App\Models\Task $first */
                $first = $groupTasks->first();
                $statuses = $groupTasks->map(fn ($t) => $t->userStatusFor($user));

                $needsAction = $groupTasks->filter(
                    fn ($t) => in_array($t->userStatusFor($user), ['overdue', 'not_started', 'resubmit'], true)
                        || (bool) ($t->submissionFor($user)?->resubmit_requested)
                );

                $resubmitCount = $groupTasks->filter(function ($t) use ($user) {
                    $sub = $t->submissionFor($user);
                    return (bool) ($sub && $sub->resubmit_requested);
                })->count();

                $isContentPlaylist = false;
                $courseName = null;
                if ($first->parent_type === 'content') {
                    $content = $first->content;
                    if ($content) {
                        $isPlaylistOnly = ($content->playlistonly ?? '') === 'Y';
                        $hasPlans = $content->relationLoaded('trainingPlans')
                            ? $content->trainingPlans->isNotEmpty()
                            : $content->trainingPlans()->exists();
                        $isContentPlaylist = $isPlaylistOnly || $hasPlans;
                        if ($hasPlans) {
                            $firstPlan = $content->relationLoaded('trainingPlans')
                                ? $content->trainingPlans->first()
                                : $content->trainingPlans()->first();
                            $courseName = $firstPlan?->title;
                        }
                    }
                }

                $parentType = $isContentPlaylist ? 'content_playlist' : $first->parent_type;

                return [
                    'key'              => $key,
                    'parent_type'      => $parentType,
                    'parent_name'      => $first->parentName(),
                    'parent_url'       => $this->parentUrl($first),
                    'course_name'      => $courseName,
                    'tasks'            => $groupTasks->values(),
                    'total'            => $groupTasks->count(),
                    'completed'        => $statuses->filter(fn ($s) => $s === 'completed')->count(),
                    'has_overdue'      => $statuses->contains('overdue'),
                    'resubmit_count'   => $resubmitCount,
                    'nearest_deadline' => $needsAction->map->deadline->filter()->min(),
                    'is_assessment'    => false,
                ];
            })
            ->values();

        // Assessment groups loaded dynamically for this user
        $assessmentGroups = $this->assessmentGroups($request, $user);

        $filterType   = $request->get('my_task_type', 'all');
        $filterStatus = $request->get('my_task_status', 'all');
        $filterYear   = $request->get('my_task_year', 'all');
        $filterMonth  = $request->get('my_task_month', 'all');

        $typeOptions = [
            'all'              => 'All',
            'playlist'         => 'Playlist',
            'content'          => 'Content',
            'content_playlist' => 'Content Playlist',
            'assessment'       => 'Assessment',
            'standalone'       => 'Others',
            'offline_training' => 'Offline Training',
        ];

        $statusOptions = [
            'all'         => 'All',
            'in_progress' => 'In Progress',
            'submitted'   => 'Pending Review',
            'completed'   => 'Completed',
            'rejected'    => 'Rejected',
            'overdue'     => 'Overdue',
        ];

        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];

        $years = $tasks->toBase()->map(function ($t) {
            return $t->deadline ? $t->deadline->format('Y') : ($t->created_at ? $t->created_at->format('Y') : null);
        })->filter()->push(date('Y'))->unique()->sortDesc()->values();

        // Dropdown: Filter by Type if specified
        if ($filterType !== 'all') {
            $taskGroups = $taskGroups->filter(fn ($g) => $g['parent_type'] === $filterType)->values();
            $assessmentGroups = $assessmentGroups->filter(fn ($g) => $filterType === 'assessment')->values();
        }

        // Dropdown: Filter by Status if specified
        if ($filterStatus !== 'all') {
            $taskGroups = $taskGroups->filter(function ($g) use ($user, $filterStatus) {
                return match ($filterStatus) {
                    'completed'   => $g['completed'] === $g['total'] && $g['total'] > 0,
                    'in_progress' => $g['completed'] < $g['total'],
                    'overdue'     => $g['has_overdue'],
                    'submitted'   => $g['tasks']->contains(fn ($t) => $t->userStatusFor($user) === 'submitted'),
                    'rejected'    => $g['tasks']->contains(fn ($t) => in_array($t->userStatusFor($user), ['rejected', 'resubmit'], true)),
                    'resubmit'    => $g['tasks']->contains(fn ($t) => in_array($t->userStatusFor($user), ['rejected', 'resubmit'], true)),
                    default       => true,
                };
            })->values();

            $assessmentGroups = $assessmentGroups->filter(function ($g) use ($filterStatus) {
                return match ($filterStatus) {
                    'completed'   => $g['completed'] === 1,
                    'in_progress' => $g['completed'] === 0 && !$g['has_overdue'],
                    'overdue'     => $g['has_overdue'],
                    default       => true,
                };
            })->values();
        }

        // Dropdown: Filter by Year if specified
        if ($filterYear !== 'all') {
            $taskGroups = $taskGroups->filter(function ($g) use ($filterYear) {
                return $g['tasks']->contains(function ($t) use ($filterYear) {
                    $y = $t->deadline ? $t->deadline->format('Y') : ($t->created_at ? $t->created_at->format('Y') : null);
                    return (string) $y === (string) $filterYear;
                });
            })->values();

            $assessmentGroups = $assessmentGroups->filter(function ($g) use ($filterYear) {
                $first = $g['tasks']->first();
                $y = $first?->deadline ? $first->deadline->format('Y') : null;
                return (string) $y === (string) $filterYear;
            })->values();
        }

        // Dropdown: Filter by Month if specified
        if ($filterMonth !== 'all') {
            $taskGroups = $taskGroups->filter(function ($g) use ($filterMonth) {
                return $g['tasks']->contains(function ($t) use ($filterMonth) {
                    $m = $t->deadline ? $t->deadline->format('n') : ($t->created_at ? $t->created_at->format('n') : null);
                    return (string) $m === (string) $filterMonth;
                });
            })->values();

            $assessmentGroups = $assessmentGroups->filter(function ($g) use ($filterMonth) {
                $first = $g['tasks']->first();
                $m = $first?->deadline ? $first->deadline->format('n') : null;
                return (string) $m === (string) $filterMonth;
            })->values();
        }

        // Merge all groups (tasks + assessments) after dropdown filters
        $allGroups = $taskGroups->concat($assessmentGroups);

        // Search keyword filter across parent name, course name, and task titles
        if ($search !== '') {
            $taskGroups = $taskGroups->filter(function ($g) use ($search) {
                $nameMatch = stripos($g['parent_name'], $search) !== false;
                $courseMatch = !empty($g['course_name']) && stripos($g['course_name'], $search) !== false;
                $taskMatch = $g['tasks']->contains(fn ($t) => stripos($t->title, $search) !== false);
                return $nameMatch || $courseMatch || $taskMatch;
            })->values();

            $assessmentGroups = $assessmentGroups->filter(function ($g) use ($search) {
                $nameMatch = stripos($g['parent_name'], $search) !== false;
                $courseMatch = !empty($g['course_name']) && stripos($g['course_name'], $search) !== false;
                $taskMatch = $g['tasks']->contains(fn ($t) => stripos($t->title, $search) !== false);
                return $nameMatch || $courseMatch || $taskMatch;
            })->values();

            $allGroups = $taskGroups->concat($assessmentGroups);
        }

        // Summary counts across all tasks & assessments
        $allTasksAndAssessments = $allGroups->pluck('tasks')->flatten();
        $summary = [
            'total'       => $allTasksAndAssessments->count(),
            'not_started' => $allTasksAndAssessments->filter(fn ($t) => in_array($t->userStatusFor($user), ['not_started', 'in_progress'], true) && $t->userStatusFor($user) !== 'completed')->count(),
            'submitted'   => $allTasksAndAssessments->filter(fn ($t) => $t->userStatusFor($user) === 'submitted')->count(),
            'completed'   => $allTasksAndAssessments->filter(fn ($t) => $t->userStatusFor($user) === 'completed')->count(),
            'rejected'    => $allTasksAndAssessments->filter(fn ($t) => in_array($t->userStatusFor($user), ['rejected', 'resubmit'], true))->count(),
            'overdue'     => $allTasksAndAssessments->filter(fn ($t) => $t->userStatusFor($user) === 'overdue')->count(),
            'resubmit'    => $allTasksAndAssessments->filter(fn ($t) => in_array($t->userStatusFor($user), ['rejected', 'resubmit'], true))->count(),
        ];

        // Category counts for quick filter tabs: all, playlist, content, content_playlist, asesment, others
        // Note: 'all' contains course tasks. Assessments are dedicated under the 'asesment' quick filter.
        $categoryCounts = [
            'all'              => $taskGroups->count(),
            'playlist'         => $taskGroups->filter(fn ($g) => $g['parent_type'] === 'playlist')->count(),
            'content'          => $taskGroups->filter(fn ($g) => $g['parent_type'] === 'content')->count(),
            'content_playlist' => $taskGroups->filter(fn ($g) => $g['parent_type'] === 'content_playlist')->count(),
            'asesment'         => $assessmentGroups->count(),
            'others'           => $taskGroups->filter(fn ($g) => in_array($g['parent_type'], ['standalone', 'offline_training'], true) || !in_array($g['parent_type'], ['playlist', 'content', 'content_playlist'], true))->count(),
        ];

        // Revision counts per quick filter category for notification badges on pills
        $revisionCounts = [
            'all'              => 0,
            'playlist'         => 0,
            'content'          => 0,
            'content_playlist' => 0,
            'asesment'         => 0,
            'others'           => 0,
        ];

        foreach ($tasks as $task) {
            $submission = $task->submissionFor($user);
            if ($submission && $submission->resubmit_requested) {
                $revisionCounts['all']++;
                if ($task->parent_type === 'playlist') {
                    $revisionCounts['playlist']++;
                } elseif ($task->parent_type === 'content') {
                    $content = $task->content;
                    $isCp = $content && (
                        ($content->playlistonly ?? '') === 'Y'
                        || ($content->relationLoaded('trainingPlans') ? $content->trainingPlans->isNotEmpty() : $content->trainingPlans()->exists())
                    );
                    if ($isCp) {
                        $revisionCounts['content_playlist']++;
                    } else {
                        $revisionCounts['content']++;
                    }
                } else {
                    $revisionCounts['others']++;
                }
            }
        }

        // Filter groups by selected quick filter
        if ($filterType === 'assessment') {
            $groups = $assessmentGroups->values();
        } else {
            $groups = match ($filter) {
                'all'              => $taskGroups,
                'playlist'         => $taskGroups->filter(fn ($g) => $g['parent_type'] === 'playlist')->values(),
                'content'          => $taskGroups->filter(fn ($g) => $g['parent_type'] === 'content')->values(),
                'content_playlist' => $taskGroups->filter(fn ($g) => $g['parent_type'] === 'content_playlist')->values(),
                'asesment'         => $assessmentGroups->values(),
                'others'           => $taskGroups->filter(fn ($g) => in_array($g['parent_type'], ['standalone', 'offline_training'], true) || !in_array($g['parent_type'], ['playlist', 'content', 'content_playlist'], true))->values(),
                // Backward-compatibility: if legacy status filters are passed
                'rejected'    => $allGroups->filter(fn ($g) => $g['tasks']->contains(fn ($t) => in_array($t->userStatusFor($user), ['rejected', 'resubmit'], true)))->values(),
                'resubmit'    => $allGroups->filter(fn ($g) => $g['tasks']->contains(fn ($t) => in_array($t->userStatusFor($user), ['rejected', 'resubmit'], true)))->values(),
                'in_progress' => $allGroups->filter(fn ($g) => $g['completed'] < $g['total'])->values(),
                'submitted'   => $allGroups->filter(fn ($g) => $g['tasks']->contains(fn ($t) => $t->userStatusFor($user) === 'submitted'))->values(),
                'completed'   => $allGroups->filter(fn ($g) => $g['completed'] === $g['total'] && $g['total'] > 0)->values(),
                'overdue'     => $allGroups->filter(fn ($g) => $g['has_overdue'])->values(),
                default       => $taskGroups,
            };
        }

        if ($filter === 'asesment' || $filterType === 'assessment') {
            $groups = $assessmentGroups
                ->sortByDesc(fn ($g) => $g['assessment']->id ?? 0)
                ->values();
        } else {
            $groups = $groups
                ->sortBy(fn ($g) => [$g['nearest_deadline'] === null, $g['nearest_deadline']])
                ->sortByDesc('has_overdue')
                ->sortByDesc(fn ($g) => ($g['resubmit_count'] ?? 0) > 0)
                ->values();
        }

        // Drill-down: ?my_task_parent=... shows only that parent's tasks.
        $activeKey = $request->get('my_task_parent');
        $activeGroup = $activeKey ? $allGroups->firstWhere('key', (string) $activeKey) : null;

        // Show the "enter access code" card when standalone tasks exist
        // (they always carry a code — manual or auto-generated).
        $showRedeemCard = Task::where('parent_type', 'standalone')
            ->where('status', 'published')
            ->exists();

        return view('user.learning.dashboard-partials.my-task', compact(
            'groups',
            'activeGroup',
            'summary',
            'filter',
            'search',
            'showRedeemCard',
            'categoryCounts',
            'revisionCounts',
            'filterType',
            'filterStatus',
            'filterYear',
            'filterMonth',
            'typeOptions',
            'statusOptions',
            'months',
            'years'
        ));
    }

    /**
     * Map user assessments into the unified task group structure
     * so assessments appear seamlessly in the My Task tab.
     */
    protected function assessmentGroups(Request $request, User $user): \Illuminate\Support\Collection
    {
        try {
            $assessmentCtrl = app(LearningAssessmentController::class);
            $userAssessments = $assessmentCtrl->getFilteredUserAssessments($request, (int) $user->id);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed loading user assessments for MyTask: ' . $e->getMessage());
            return collect();
        }

        return $userAssessments
            ->sortByDesc(fn ($a) => $a->id)
            ->map(function ($assessment) use ($user) {
            $res = LearningAssessmentController::resolveCanonicalAssessmentResult($assessment, (int) $user->id);
            $isCompleted = ($res !== null);
            $isPassed = $res && (bool) ($res->pass ?? ($res->score !== null && $assessment->passing_grade !== null && (float) $res->score >= (float) $assessment->passing_grade));
            $isExpired = false;
            if ($assessment->hasAccessPeriod() && !empty($assessment->todate)) {
                $isExpired = now()->gt(\Illuminate\Support\Carbon::parse($assessment->todate));
            }

            $assessmentItem = new class($assessment, $res, $isCompleted, $isPassed, $isExpired) {
                public $id;
                public $assessment_id;
                public bool $is_assessment = true;
                public $assessment;
                public $res;
                public $title;
                public $slug;
                public $description;
                public $type = 'assessment';
                public $deadline;
                public $time_limit;
                public $passing_grade;
                public $score;
                public bool $is_passed;
                public bool $is_completed;
                public bool $is_expired;
                public $parent_type = 'assessment';

                public function __construct($assessment, $res, $isCompleted, $isPassed, $isExpired) {
                    $this->assessment = $assessment;
                    $this->res = $res;
                    $this->assessment_id = $assessment->id;
                    $this->id = 'assessment_' . $assessment->id;
                    $this->title = $assessment->title;
                    $this->slug = $assessment->slug;
                    $this->description = $assessment->description ?: 'Knowledge assessment and material evaluation.';
                    $this->deadline = $assessment->todate ? \Illuminate\Support\Carbon::parse($assessment->todate) : null;
                    $this->time_limit = $assessment->time_limit_minutes;
                    $this->passing_grade = $assessment->passing_grade;
                    $this->score = $res?->score;
                    $this->is_passed = (bool) $isPassed;
                    $this->is_completed = (bool) $isCompleted;
                    $this->is_expired = (bool) $isExpired;
                }

                public function typeLabel(): string {
                    return 'Assessment';
                }

                public function userStatusFor($u = null): string {
                    if ($this->is_completed) return 'completed';
                    if ($this->is_expired) return 'overdue';
                    return 'in_progress';
                }

                public function submissionFor($u = null) {
                    return (object) [
                        'score' => $this->score,
                        'status' => $this->userStatusFor(),
                        'submitted_at' => $this->res?->created_at,
                        'feedback' => null,
                        'resubmit_requested' => false,
                    ];
                }

                public function parentTitle(): ?string {
                    return $this->assessment->trainingplan?->title ?? ($this->assessment->content?->title ?? 'Assessment');
                }

                public function isStandalone(): bool {
                    return empty($this->assessment->trainingplan_id) && empty($this->assessment->content_id);
                }

                public function categoryDisplay(): string {
                    $rawType = strtolower((string) ($this->assessment->type ?? ''));
                    $contentFor = strtolower((string) ($this->assessment->assasment_content_for ?? 'content'));
                    if ($rawType === 'global') return 'Global';
                    if ($rawType === 'trainingplan') return 'Playlist';
                    return $contentFor === 'playlist' ? 'Playlist' : 'All Content';
                }

                public function typeDisplay(): string {
                    $rawType = strtolower((string) ($this->assessment->type ?? ''));
                    if ($rawType === 'global') return 'Global Assessment';
                    if ($rawType === 'trainingplan') return 'Playlist Assessment';
                    return 'Content Assessment';
                }

                public function filterType(): string {
                    $rawType = strtolower((string) ($this->assessment->type ?? ''));
                    $contentFor = strtolower((string) ($this->assessment->assasment_content_for ?? 'content'));
                    if ($rawType === 'global') return 'global';
                    if ($rawType === 'trainingplan') return 'trainingplan';
                    return $contentFor === 'playlist' ? 'content_playlist' : 'content_content';
                }

                public function hasAccessPeriod(): bool {
                    return method_exists($this->assessment, 'hasAccessPeriod')
                        ? $this->assessment->hasAccessPeriod()
                        : (!empty($this->assessment->fromdate) && !empty($this->assessment->todate));
                }

                public function hasTimeLimit(): bool {
                    return method_exists($this->assessment, 'hasTimeLimit')
                        ? $this->assessment->hasTimeLimit()
                        : !empty($this->assessment->time_limit_minutes);
                }

                public function fromDateDisplay(): ?string {
                    return !empty($this->assessment->fromdate)
                        ? \Illuminate\Support\Carbon::parse($this->assessment->fromdate)->format('d M Y, H:i')
                        : null;
                }

                public function toDateDisplay(): ?string {
                    return !empty($this->assessment->todate)
                        ? \Illuminate\Support\Carbon::parse($this->assessment->todate)->format('d M Y, H:i')
                        : null;
                }

                public function takenDateDisplay(): ?string {
                    if ($this->res && !empty($this->res->created_at)) {
                        return \Illuminate\Support\Carbon::parse($this->res->created_at)->format('d/m/y H:i');
                    }
                    return null;
                }
            };

            $nearestDl = $assessment->todate ? \Illuminate\Support\Carbon::parse($assessment->todate) : null;

            return [
                'key'              => 'assessment:' . $assessment->id,
                'parent_type'      => 'assessment',
                'parent_name'      => $assessment->title,
                'parent_url'       => !empty($assessment->slug) && \Illuminate\Support\Facades\Route::has('assesment.user.view')
                                        ? route('assesment.user.view', $assessment->slug)
                                        : null,
                'course_name'      => $assessment->trainingplan?->title ?? ($assessment->content?->title ?? null),
                'tasks'            => collect([$assessmentItem]),
                'total'            => 1,
                'completed'        => $isCompleted ? 1 : 0,
                'has_overdue'      => $isExpired && ! $isCompleted,
                'resubmit_count'   => 0,
                'nearest_deadline' => $nearestDl,
                'is_assessment'    => true,
                'assessment'       => $assessment,
            ];
        });
    }

    /**
     * POST /my-task/redeem — user enters a standalone task's access code.
     * On success the task is unlocked and appears in their My Task list.
     */
    public function redeemCode(Request $request)
    {
        $code = strtoupper(trim((string) $request->input('code', '')));
        $task = $this->redeemAccessCode($request->user()->id, $code);

        // Persistent (not flashed): the My Task partial is AJAX-loaded,
        // so a normal flash would be gone before the partial renders.
        session()->put('redeem_notice', $task
            ? ['type' => 'success', 'text' => 'Task unlocked: ' . $task->title]
            : ['type' => 'error', 'text' => 'Invalid code. Check the code and try again.']);

        return redirect()->route('user.learning.dashboard', ['learning_tab' => 'my-task']);
    }

    /**
     * GET /my-task/redeem/{code} — redeem via shared link / QR code.
     * Same unlock logic; lands on the My Task tab with a notice.
     */
    public function redeemViaLink(Request $request, string $code)
    {
        $task = $this->redeemAccessCode($request->user()->id, $code);

        session()->put('redeem_notice', $task
            ? ['type' => 'success', 'text' => 'Task unlocked: ' . $task->title]
            : ['type' => 'error', 'text' => 'This link is invalid or the task is no longer available.']);

        return redirect()->route('user.learning.dashboard', ['learning_tab' => 'my-task']);
    }

    /**
     * GET /my-task/unlock/{task} — redeem via signed link / QR code
     * (for standalone tasks without an access code). The signature
     * proves the link came from the admin; it cannot be guessed.
     */
    public function redeemViaSignedLink(Request $request, Task $task)
    {
        abort_unless($request->hasValidSignature(), 403);
        abort_unless(
            $task->parent_type === 'standalone' && $task->status === 'published',
            404
        );

        TaskCodeRedemption::firstOrCreate([
            'task_id' => $task->id,
            'user_id' => $request->user()->id,
        ]);

        session()->put('redeem_notice', [
            'type' => 'success',
            'text' => 'Task unlocked: ' . $task->title,
        ]);

        return redirect()->route('user.learning.dashboard', ['learning_tab' => 'my-task']);
    }

    /**
     * Core unlock: find a published standalone task by code and record
     * the redemption. Returns the task, or null when the code is invalid.
     */
    protected function redeemAccessCode(int $userId, string $code): ?Task
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return null;
        }

        $task = Task::where('access_code', $code)
            ->where('parent_type', 'standalone')
            ->where('status', 'published')
            ->first();

        if (! $task) {
            return null;
        }

        TaskCodeRedemption::firstOrCreate([
            'task_id' => $task->id,
            'user_id' => $userId,
        ]);

        return $task;
    }

    /**
     * Link to the parent a task belongs to (null when unknown).
     * Playlist → playlist detail page; content → no user detail route
     * in this payload, so the group header shows the name only.
     */
    protected function parentUrl(Task $task): ?string
    {
        if ($task->parent_type === 'playlist' && $task->trainingPlan) {
            return route('user.playlist.show', $task->trainingPlan->slug);
        }

        if ($task->parent_type === 'content' && $task->content) {
            $content = $task->content;
            $hasPlans = $content->relationLoaded('trainingPlans')
                ? $content->trainingPlans->isNotEmpty()
                : $content->trainingPlans()->exists();
            if ($hasPlans) {
                $plan = $content->relationLoaded('trainingPlans')
                    ? $content->trainingPlans->first()
                    : $content->trainingPlans()->first();
                if ($plan && !empty($plan->slug) && !empty($content->slug) && \Illuminate\Support\Facades\Route::has('playlist.user.view')) {
                    return route('playlist.user.view', [$plan->slug, $content->slug]);
                }
            }
            if (!empty($content->slug) && \Illuminate\Support\Facades\Route::has('content.user.view')) {
                return route('content.user.view', $content->slug);
            }
        }

        if ($task->parent_type === 'offline_training') {
            return route('trainingplan.user.index', ['tab' => 'history']);
        }

        return null;
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
     * - course_assignment (linked LMS course): auto-completed when the
     *   linked course reaches 100% progress.
     * - course_assignment (external link): no progress data — user
     *   confirms manually (pending review).
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

        // ── 1. Course Assignment: auto-completed at 100% course progress ──
        if ($task->isCourseAssignment()) {
            return $dashboard->with('info', 'This task completes automatically once you finish the course (100% progress).');
        }

        // ── 3. Other types: per-field answers, pending admin review ──
        $existing = TaskSubmission::where('task_id', $task->id)
            ->where('user_id', $user->id)
            ->first();
        $existingAnswers = $existing ? $existing->answersByField() : collect();

        $action = $request->input('action', 'submit');
        $isDraft = $action === 'draft';

        // Tolak submit ganda: hanya boleh submit kalau belum pernah,
        // sedang draft, atau kalau admin meminta resubmit.
        if ($existing && ! $existing->resubmit_requested && $existing->status !== 'draft') {
            $msg = $existing->status === 'rejected'
                ? 'This task has been closed as rejected.'
                : ($existing->status === 'completed'
                    ? 'You have already completed this task.'
                    : 'You have already submitted this task. Waiting for admin review.');
            return $dashboard->with('info', $msg);
        }

        // Validasi dinamis mengikuti field task.
        $rules = [];
        foreach ($task->fields as $field) {
            $key = "fields.{$field->id}";
            if ($field->isFile()) {
                // Upload jamak (fields[id][]): jumlah & ukuran mengikuti setting soal.
                $rules[$key] = ['nullable', 'array', 'max:' . $field->maxFiles()];
                $rules[$key . '.*'] = ['file', 'max:' . $field->maxSizeKb()];
                if ($field->allow_link) {
                    $rules["drive_links.{$field->id}"] = ['nullable', 'url', 'max:500'];
                }
            } elseif ($field->isChoice()) {
                $choiceRules = [
                    (! $isDraft && $field->required) ? 'required' : 'nullable',
                    'string',
                ];
                if (! empty($field->options)) {
                    $choiceRules[] = Rule::in($field->options);
                }
                $rules[$key] = $choiceRules;
            } elseif ($field->isCheckbox()) {
                $min = $field->minSelect() ?: ((! $isDraft && $field->required) ? 1 : null);
                $max = $field->maxSelect();
                $cbRules = [
                    (! $isDraft && $field->required) ? 'required' : 'nullable',
                    'array',
                ];
                if ($min && ! $isDraft) {
                    $cbRules[] = 'min:' . $min;
                }
                if ($max) {
                    $cbRules[] = 'max:' . $max;
                }
                $rules[$key] = $cbRules;
                $rules[$key . '.*'] = ['string', 'max:255'];
            } elseif ($field->isRating() || $field->isScale()) {
                $rules[$key] = [
                    (! $isDraft && $field->required) ? 'required' : 'nullable',
                    'integer', 'min:1', 'max:5',
                ];
            } elseif ($field->isParagraph()) {
                $pRules = [
                    (! $isDraft && $field->required) ? 'required' : 'nullable',
                    'string',
                    'max:10000',
                ];
                if (! $isDraft) {
                    $pRules[] = 'min:100';
                }
                $rules[$key] = $pRules;
            } else {
                // Short answer (short_answer), dropdown, dll. Bebas berapa karakter (hanya max:10000).
                $rules[$key] = [
                    (! $isDraft && $field->required) ? 'required' : 'nullable',
                    'string', 'max:10000',
                ];
            }
        }

        $customMessages = [
            'fields.*.max'   => 'Too many files or options selected.',
            'fields.*.*.max' => 'One of the files is too large.',
        ];
        foreach ($task->fields as $field) {
            if ($field->isCheckbox()) {
                $min = $field->minSelect() ?: ((! $isDraft && $field->required) ? 1 : null);
                $max = $field->maxSelect();
                if ($min && $max && $min === $max) {
                    $customMessages["fields.{$field->id}.min"] = "Please select exactly {$max} options.";
                    $customMessages["fields.{$field->id}.max"] = "Please select exactly {$max} options.";
                } else {
                    if ($min) {
                        $customMessages["fields.{$field->id}.min"] = "Please select at least {$min} option" . ($min > 1 ? 's.' : '.');
                    }
                    if ($max) {
                        $customMessages["fields.{$field->id}.max"] = "You can select at most {$max} option" . ($max > 1 ? 's.' : '.');
                    }
                }
            } elseif ($field->isParagraph()) {
                $customMessages["fields.{$field->id}.min"] = "The {$field->label} must be at least 100 characters.";
            }
        }

        $validator = Validator::make($request->all(), $rules, $customMessages);

        // File wajib (saat belum ada file tersimpan) + batas tipe file dicek manual.
        $validator->after(function ($validator) use ($task, $existingAnswers, $request, $isDraft) {
            foreach ($task->fields as $field) {
                if (! $field->isFile()) {
                    continue;
                }
                $files = $request->file("fields.{$field->id}");
                $files = is_array($files)
                    ? array_values(array_filter($files))
                    : array_values(array_filter((array) $files));

                $existingAnswer = $existingAnswers[$field->id] ?? null;
                $link = $field->allow_link
                    ? trim($request->input("drive_links.{$field->id}") ?? '')
                    : '';
                $hasExisting = $existingAnswer
                    && ($existingAnswer->hasFiles()
                        || ($field->allow_link && $existingAnswer->answer_text));

                if (! $isDraft && $field->required && ! $hasExisting && empty($files) && $link === '') {
                    $validator->errors()->add(
                        "fields.{$field->id}",
                        'Please upload a file or paste a Drive link.'
                    );
                }

                $allowed = $field->allowedExtensions();
                if (! empty($allowed)) {
                    foreach ($files as $file) {
                        $ext = strtolower($file->getClientOriginalExtension());
                        if (! in_array($ext, $allowed, true)) {
                            $validator->errors()->add(
                                "fields.{$field->id}",
                                "File type .{$ext} is not allowed here."
                                . ' Allowed: ' . $field->allowedTypesLabel() . '.'
                            );
                            break;
                        }
                    }
                }
            }
        });

        $validated = $validator->validate();

        $inputFields   = $validated['fields'] ?? [];
        $uploadedFiles = $request->file('fields', []);

        $submission = TaskSubmission::firstOrNew([
            'task_id' => $task->id,
            'user_id' => $user->id,
        ]);

        $existingAnswers = $submission->answersByField();
        $newAnswers = [];

        foreach ($task->fields as $field) {
            $existingAnswer = $existingAnswers->get($field->id);
            $existingPaths = $existingAnswer ? $existingAnswer->filePaths() : [];
            $paths = $existingPaths;
            $answerText = null;

            if ($field->isFile()) {
                $files = $uploadedFiles[$field->id] ?? null;
                $files = is_array($files)
                    ? array_values(array_filter($files))
                    : array_values(array_filter((array) $files));
                if (! empty($files)) {
                    // Hapus file lama jika user upload ulang field ini.
                    foreach ($existingPaths as $oldPath) {
                        Storage::disk('public')->delete($oldPath);
                    }
                    $paths = [];
                    foreach ($files as $file) {
                        $paths[] = $file->store("task-submissions/{$task->id}", 'public');
                    }
                }
                $driveLink = $field->allow_link
                    ? trim($request->input("drive_links.{$field->id}") ?? '')
                    : '';
                if ($driveLink !== '') {
                    $answerText = $driveLink;
                } elseif ($existingAnswer && $existingAnswer->answer_text && empty($files)) {
                    $answerText = $existingAnswer->answer_text;
                }
            } else {
                $rawVal = $inputFields[$field->id] ?? null;
                $answerText = is_array($rawVal)
                    ? implode(', ', array_filter(array_map('trim', $rawVal)))
                    : ($rawVal !== null ? trim($rawVal) : null);
            }

            $newAnswers[$field->id] = [
                'task_field_id' => (int) $field->id,
                'answer_text'   => ($answerText !== '' && $answerText !== null) ? $answerText : null,
                'file_path'     => $paths,
            ];
        }

        if ($isDraft) {
            $submission->status             = 'draft';
            $submission->resubmit_requested = false;
            $submission->answers            = $newAnswers;
            $submission->save();

            return redirect()->route('user.learning.dashboard', [
                'learning_tab'   => 'my-task',
                'active_task_id' => $task->id,
            ])->with('success', 'Task answers saved as draft. You can continue anytime before final submission.');
        }

        $submission->status             = 'pending';
        $submission->submitted_at       = now();
        $submission->resubmit_requested = false;
        $submission->answers            = $newAnswers;
        $submission->save();

        return redirect()->route('user.learning.dashboard', [
            'learning_tab'   => 'my-task',
            'active_task_id' => $task->id,
        ])->with('success', 'Answers submitted successfully. Waiting for admin review.')
          ->with('task_submitted_id', $task->id)
          ->with('task_submitted_title', $task->title);
    }

    /**
     * Published tasks from playlists AND contents the user has opened,
     * nearest deadline first.
     * (Offline training has no tracking table yet, so its tasks
     * don't appear for users for now.)
     */
    protected function tasksFor(User $user)
    {
        // Playlist: task baru muncul setelah user menyelesaikan course 100%
        // DAN mengklik tombol Task di halaman course (unlock tercatat di
        // task_playlist_unlocks). Bukan lagi sekadar "pernah membuka".
        $planIds     = $this->unlockedPlanIds($user->id);
        $contentIds  = $this->completedContentIds($user->id);
        $offlineIds  = $this->enrolledOfflineTrainingIds($user->id);
        // Standalone tasks the user unlocked via access code / link / QR.
        // (Fetched up front so the main query needs no nested closures.)
        $redeemedIds = TaskCodeRedemption::where('user_id', $user->id)->pluck('task_id')->all();

        return Task::with(['trainingPlan', 'linkedPlan', 'content.trainingPlans:id,title,slug', 'offlineTraining', 'taskGroup', 'fields' => fn ($q) => $q->ordered()])
            ->with([
                'submissions' => fn ($q) => $q->where('user_id', $user->id),
            ])
            ->published()
            ->where(function ($q) use ($planIds, $contentIds, $offlineIds, $redeemedIds) {
                // Playlists yang sudah 100% + user mengklik tombol Task
                // di halaman course (logic baru 2026-10-13).
                $q->where(function ($w) use ($planIds) {
                    $w->where('parent_type', 'playlist')
                      ->whereIn('training_plan_id', $planIds);
                })
                // Contents the user opened (same logic, other parent type).
                ->orWhere(function ($w) use ($contentIds) {
                    $w->where('parent_type', 'content')
                      ->whereIn('content_id', $contentIds);
                })
                // Offline trainings where the user is registered / assigned as participant.
                ->orWhere(function ($w) use ($offlineIds) {
                    $w->where('parent_type', 'offline_training')
                      ->whereIn('offline_training_id', $offlineIds);
                })
                // Standalone tasks are not tied to any placement and are always
                // private: only users who redeemed the task's access code see it.
                ->orWhere(function ($w) use ($redeemedIds) {
                    $w->where('parent_type', 'standalone')
                      ->whereIn('id', $redeemedIds);
                });
            })
            ->orderByRaw('deadline IS NULL, deadline ASC')
            ->get();
    }

    /**
     * Auto-complete Course Assignment tasks linked to an LMS course.
     *
     * A task with link_type=lms is marked completed once the user has
     * finished every content of the linked playlist (course progress
     * 100%): for each trainingplandetail row of the linked playlist,
     * the user's playlist_content_users row is finished
     * (finish = 1 or progress >= 100).
     */
    protected function syncCourseCompletions(User $user, $tasks): void
    {
        foreach ($tasks as $task) {
            if (! $task->isCourseAssignment()) {
                continue;
            }
            $planId = $task->linked_training_plan_id ?: $task->training_plan_id;
            if (! $planId) {
                continue;
            }

            $contentIds = TrainingPlanDetail::where('trainingplan_id', $planId)
                ->pluck('content_id')->unique()->values();
            $total = $contentIds->count();
            if ($total === 0) {
                continue;
            }

            $finishedQuery = PlaylistContentUser::where('user_id', $user->id)
                ->where('trainingplan_id', $planId)
                ->whereIn('content_id', $contentIds)
                ->where(fn ($q) => $q->where('finish', '1')
                      ->orWhere('progress', '>=', 100));

            if ($finishedQuery->count() < $total) {
                continue;
            }

            $submission = TaskSubmission::firstOrNew([
                'task_id' => $task->id,
                'user_id' => $user->id,
            ]);

            // Already auto-completed before — leave it (and any admin
            // feedback) untouched.
            if ($submission->exists && $submission->status === 'completed') {
                continue;
            }

            $submission->status = 'completed';
            $submission->submitted_at = PlaylistContentUser::where('user_id', $user->id)
                ->where('trainingplan_id', $planId)
                ->whereIn('content_id', $contentIds)
                ->max('completed_at') ?? now();
            $submission->save();

            $task->setRelation('submissions', collect([$submission]));
        }
    }

    /**
     * May this user open/work on this task? Same rule as the listing:
     * the user must have opened the task's parent.
     */
    protected function userCanAccess(Task $task, int $userId): bool
    {
        return match ($task->parent_type) {
            'content'          => in_array($task->content_id, $this->completedContentIds($userId)),
            'offline_training' => in_array($task->offline_training_id, $this->enrolledOfflineTrainingIds($userId)),
            // Standalone is always private: only users who redeemed the code.
            'standalone'       => TaskCodeRedemption::where('task_id', $task->id)
                                    ->where('user_id', $userId)
                                    ->exists(),
            // Playlist: hanya yang sudah di-unlock via tombol Task (100%).
            'playlist'         => in_array($task->training_plan_id, $this->unlockedPlanIds($userId)),
            default            => in_array($task->training_plan_id, $this->enrolledPlanIds($userId)),
        };
    }

    /**
     * GET /my-task/unlock-playlist/{playlist} — user mengklik tombol Task
     * di halaman course setelah progress 100%.
     *
     * Mencatat unlock lalu mengarahkan ke tab Task. Task playlist ini
     * baru muncul di My Task setelah unlock tercatat (logic 2026-10-13).
     */
    public function unlockPlaylist(Request $request, $playlist)
    {
        $user = $request->user();
        $planId = (int) $playlist;

        abort_unless(
            $this->playlistProgress($user, $planId) >= 100,
            403,
            'Complete the course 100% first to unlock Task.'
        );

        TaskPlaylistUnlock::firstOrCreate([
            'user_id'          => $user->id,
            'training_plan_id' => $planId,
        ]);

        // Langsung ke list task playlist ini (drill-down), bukan dashboard grup.
        return redirect()->route('user.learning.dashboard', [
            'learning_tab'   => 'my-task',
            'my_task_parent' => 'playlist:' . $planId,
        ]);
    }

    /**
     * Progress user di sebuah playlist (0-100) — SAMA PERSIS dengan angka
     * "N% Progress" di Content List:
     *   TrainingPlan::calculateProgressPercentage(completed, total)
     *   = round(finished / total * 100)
     * dengan definisi finished mengikuti ResolvedProgress::isFinished()
     * (finish == 1 atau progress >= 100). Termasuk debug override
     * (playlist_debug → 'pass' = 100).
     */
    protected function playlistProgress(User $user, int $planId): int
    {
        if (session('playlist_debug.' . $planId . '.learning_progress') === 'pass') {
            return 100;
        }

        $contentIds = TrainingPlanDetail::where('trainingplan_id', $planId)
            ->pluck('content_id');
        $total = $contentIds->count();
        if ($total === 0) {
            return 0;
        }

        $finished = PlaylistContentUser::where('user_id', $user->id)
            ->where('trainingplan_id', $planId)
            ->whereIn('content_id', $contentIds)
            ->get()
            ->filter(fn ($pcu) => (int) $pcu->finish === 1 || (int) $pcu->progress >= 100)
            ->count();

        return (int) round($finished / $total * 100);
    }

    /**
     * ID training plan yang task-nya sudah di-unlock user via tombol Task
     * di halaman course (setelah progress 100%).
     */
    protected function unlockedPlanIds(int $userId): array
    {
        return TaskPlaylistUnlock::where('user_id', $userId)
            ->pluck('training_plan_id')
            ->values()
            ->all();
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
    /**
     * ID content yang sudah diselesaikan 100% oleh user.
     * Task di wadah content baru muncul setelah user menyelesaikan content 100%.
     */
    protected function completedContentIds(int $userId): array
    {
        $directIds = ContentUser::where('user_id', $userId)
            ->where(function ($q) {
                $q->where('finish', '1')
                  ->orWhere('progress', '>=', 100)
                  ->orWhere('learning_status', 'completed');
            })
            ->pluck('content_id');

        $playlistContentIds = PlaylistContentUser::where('user_id', $userId)
            ->where(function ($q) {
                $q->where('finish', '1')
                  ->orWhere('progress', '>=', 100)
                  ->orWhere('learning_status', 'completed');
            })
            ->pluck('content_id');

        return $directIds->concat($playlistContentIds)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function openedContentIds(int $userId): array
    {
        return $this->completedContentIds($userId);
    }

    /**
     * ID offline training yang diikuti user (baik terdaftar di offline_training_users
     * maupun registrasi mandiri yang disetujui di offline_training_registers).
     */
    protected function enrolledOfflineTrainingIds(int $userId): array
    {
        $direct = OfflineTrainingUser::where('user_id', $userId)
            ->pluck('offline_training_id')
            ->all();

        $registered = OfflineTrainingRegister::where('user_id', $userId)
            ->where('status', 'approved')
            ->pluck('offline_training_id')
            ->all();

        return array_values(array_unique(array_merge($direct, $registered)));
    }
}
