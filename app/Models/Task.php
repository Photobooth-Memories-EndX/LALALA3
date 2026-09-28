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
    protected $fillable = [
        'training_plan_id', 'content_id', 'title', 'description',
        'type', 'deadline', 'attachment', 'created_by', 'status',
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

    public function submissions(): HasMany
    {
        return $this->hasMany(TaskSubmission::class);
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

    /** Submission milik user tertentu untuk task ini (1 user = 1 row). */
    public function submissionFor(User $user): ?TaskSubmission
    {
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
