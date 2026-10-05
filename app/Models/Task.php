<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Task — disesuaikan dengan tabel `tasks` yang sudah ada di project
 * (migration 2026_09_25_103301_create_tasks_table).
 *
 * Tambahan dari versi bawaan project: scope & helper untuk alur My Task.
 */
class Task extends Model
{
    /**
     * 6 task types (mentor revision 2026-10-01).
     */
    public const TYPES = [
        'file_submission'   => 'File Submission',
        'course_assignment' => 'Course Assignment',
        'assessment'        => 'Assessment/Quiz',
        'survey'            => 'Survey',
        'essay'             => 'Essay/Reflection',
        'action_plan'       => 'Action Plan',
    ];

    /**
     * Which question field types are allowed per task type
     * (empty array = no question builder, e.g. course link only).
     */
    public const FIELD_TYPES_BY_TASK = [
        'file_submission'   => ['file'],
        'course_assignment' => [],
        'assessment'        => ['choice'],
        'survey'            => ['textarea', 'choice'],
        'essay'             => ['textarea'],
        'action_plan'       => ['file', 'textarea'],
    ];

    /** Short explanation shown under the type dropdown in the admin form. */
    public const TYPE_HINTS = [
        'file_submission'   => 'User uploads file(s) as the answer. Add file-upload questions below.',
        'course_assignment' => 'User opens a course (inside this LMS or an external link), then marks it done. No questions needed.',
        'assessment'        => 'Quiz with multiple-choice questions. Tick the one correct answer for each — scoring is automatic.',
        'survey'            => 'Collect opinions. Multiple choice, short & long answers — no scoring.',
        'essay'             => 'Written reflection. User answers in long text; admin scores manually.',
        'action_plan'       => 'Google Form-style task: file uploads, short & long answers; admin reviews manually.',
    ];

    /**
     * Where a task can live (placement). Offline training data has not been
     * merged into the project yet, so its dropdown stays empty for now.
     */
    public const PARENT_TYPES = [
        'playlist'         => 'Playlist',
        'content'          => 'Content',
        'offline_training' => 'Offline Training',
    ];

    protected $fillable = [
        'training_plan_id', 'content_id', 'offline_training_id', 'parent_type',
        'title', 'description',
        'type', 'deadline', 'attachment', 'created_by', 'status',
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

    public function isAssessment(): bool
    {
        return $this->type === 'assessment';
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
            default            => $this->training_plan_id,
        };
    }

    /** Display name of the parent (playlist / content / offline training). */
    public function parentName(): string
    {
        return match ($this->parent_type) {
            'content'          => $this->content?->title ?? '—',
            'offline_training' => 'Offline Training' . ($this->offline_training_id ? ' #' . $this->offline_training_id : ''),
            default            => $this->trainingPlan?->title ?? '—',
        };
    }

    /** Route parameters for this task's parent task list. */
    public function parentRouteParams(): array
    {
        return ['parentType' => $this->parent_type, 'parentId' => $this->parentId()];
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
     * completed | submitted | overdue | not_started
     */
    public function userStatusFor(User $user): string
    {
        $submission = $this->submissionFor($user);

        if ($submission && $submission->status === 'completed') {
            return 'completed';
        }
        if ($submission) {
            return 'submitted'; // menunggu koreksi admin
        }
        if ($this->isOverdueFor($user)) {
            return 'overdue';
        }
        return 'not_started';
    }
}
