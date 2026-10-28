<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model Task — disesuaikan dengan tabel `tasks` yang sudah ada di project
 * (migration 2026_09_25_103301_create_tasks_table).
 *
 * Tambahan dari versi bawaan project: scope & helper untuk alur My Task.
 */
class Task extends Model
{
    use SoftDeletes;

    /**
     * 6 task types (mentor revision 2026-10-01).
     */
    public const TYPES = [
        'file_submission'   => 'File Submission',
        'course_assignment' => 'Course Assignment',
        'survey'            => 'Survey',
        'essay'             => 'Essay/Reflection',
        'action_plan'       => 'Action Plan',
    ];

    /**
     * Which question field types are allowed per task type
     * (empty array = no question builder).
     * - course_assignment: user opens a linked course, no questions.
     */
    public const FIELD_TYPES_BY_TASK = [
        'file_submission'   => ['file'],
        'course_assignment' => [],
        'survey'            => [],
        'essay'             => ['textarea'],
        'action_plan'       => ['short_answer', 'textarea', 'choice', 'checkbox', 'dropdown', 'file', 'scale', 'rating'],
    ];

    /** Short explanation shown under the type dropdown in the admin form. */
    public const TYPE_HINTS = [
        'file_submission'   => 'User uploads file(s) as the answer. Add file-upload questions below.',
        'course_assignment' => 'User opens a course (inside this LMS or an external link). An LMS course completes this task automatically at 100% progress.',
        'survey'            => 'Learners complete an existing survey form. Select the target survey from the dropdown below.',
        'essay'             => 'Written reflection. User answers in long text; admin scores manually.',
        'action_plan'       => 'Google Form-style task: short & long answers, multiple choice, checkboxes, files, ratings; admin reviews manually.',
    ];

    /**
     * Where a task can live (placement). Offline training data has not been
     * merged into the project yet, so its dropdown stays empty for now.
     * Standalone = not tied to any playlist / content (visible to all users).
     */
    public const PARENT_TYPES = [
        'playlist'         => 'Playlist',
        'content'          => 'Content',
        'offline_training' => 'Offline Training',
        'standalone'       => 'Others',
    ];

    protected $fillable = [
        'training_plan_id', 'content_id', 'offline_training_id', 'task_group_id', 'parent_type',
        'access_code',
        'title', 'description',
        'type', 'survey_id', 'deadline', 'attachment', 'created_by', 'status',
        'link_type', 'linked_training_plan_id', 'external_url',
    ];

    protected $casts = [
        'deadline' => 'datetime',
    ];

    // ── Relasi (bawaan project) ──────────────────────────────────────────

    public function trainingPlan(): BelongsTo
    {
        return $this->belongsTo(TrainingPlan::class);
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function offlineTraining(): BelongsTo
    {
        return $this->belongsTo(OfflineTraining::class);
    }

    public function taskGroup(): BelongsTo
    {
        return $this->belongsTo(TaskGroup::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Course this Course Assignment points to (link_type = lms). */
    public function linkedPlan(): BelongsTo
    {
        return $this->belongsTo(TrainingPlan::class, 'linked_training_plan_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(TaskSubmission::class);
    }

    /** Field/pertanyaan dinamis milik task (ala Google Form), terurut. */
    public function fields(): HasMany
    {
        return $this->hasMany(TaskField::class)->ordered();
    }

    // ── Scope ────────────────────────────────────────────────────────────

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    // ── Helper My Task ───────────────────────────────────────────────────

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /** Task types that use the Google Form-style question builder. */
    public function needsFields(): bool
    {
        return ! empty(self::FIELD_TYPES_BY_TASK[$this->type] ?? []);
    }

    public function allowedFieldTypes(): array
    {
        return self::FIELD_TYPES_BY_TASK[$this->type] ?? [];
    }

    public function isCourseAssignment(): bool
    {
        return $this->type === 'course_assignment';
    }

    // ── Placement helpers (playlist | content | offline_training) ─────────

    public function parentTypeLabel(): string
    {
        return self::PARENT_TYPES[$this->parent_type] ?? $this->parent_type;
    }

    /** The id column value matching the current parent_type. */
    public function parentId(): ?int
    {
        return match ($this->parent_type) {
            'content'          => $this->content_id,
            'offline_training' => $this->offline_training_id,
            'standalone'       => $this->task_group_id,
            default            => $this->training_plan_id,
        };
    }

    /** Display name of the parent (playlist / content / offline training / standalone). */
    public function parentName(): string
    {
        return match ($this->parent_type) {
            'content'          => $this->content?->title ?? '—',
            'offline_training' => $this->offlineTraining?->title ?? $this->offlineTraining?->course_name ?? ('Offline Training' . ($this->offline_training_id ? ' #' . $this->offline_training_id : '')),
            'standalone'       => $this->taskGroup?->title ?? 'Others',
            default            => $this->trainingPlan?->title ?? '—',
        };
    }

    public function isStandalone(): bool
    {
        return $this->parent_type === 'standalone';
    }

    public function parentTitle(): string
    {
        return $this->parentName();
    }

    /** Route parameters for this task's parent task list. */
    public function parentRouteParams(): array
    {
        return ['parentType' => $this->parent_type, 'parentId' => $this->parentId() ?? 0];
    }

    /** Users who redeemed this standalone task's access code. */
    public function codeRedemptions(): HasMany
    {
        return $this->hasMany(TaskCodeRedemption::class);
    }

    /** Submission milik user tertentu untuk task ini (1 user = 1 row). */
    public function submissionFor(User $user): ?TaskSubmission
    {
        // Pakai relasi yang sudah di-eager-load bila ada (hemat query + answers ikut ke-load).
        if ($this->relationLoaded('submissions')) {
            return $this->submissions->firstWhere('user_id', $user->id);
        }

        return $this->submissions()->where('user_id', $user->id)->first();
    }

    public function isCompletedBy(User $user): bool
    {
        return $this->submissions()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->exists();
    }

    public function isSubmittedBy(User $user): bool
    {
        return $this->submissions()->where('user_id', $user->id)->exists();
    }

    /** Overdue = deadline lewat dan user belum completed (dihitung, bukan disimpan). */
    public function isOverdueFor(User $user): bool
    {
        return $this->deadline !== null
            && $this->deadline->isPast()
            && ! $this->isCompletedBy($user);
    }

    /**
     * Status task dari sudut pandang user:
     * completed | rejected | submitted (pending review) | overdue | not_started
     */
    public function userStatusFor(User $user): string
    {
        $submission = $this->submissionFor($user);

        if ($submission && $submission->status === 'completed') {
            return 'completed';
        }
        if ($submission && ($submission->status === 'rejected' || $submission->resubmit_requested)) {
            return 'rejected';
        }
        if ($submission) {
            return 'submitted'; // menunggu koreksi admin (pending review)
        }
        if ($this->isOverdueFor($user)) {
            return 'overdue';
        }
        return 'not_started';
    }
}
