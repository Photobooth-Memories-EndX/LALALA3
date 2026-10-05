<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model TaskSubmission — disesuaikan dengan tabel `task_submissions`
 * yang sudah ada di project (migration 2026_09_25_103343).
 *
 * status: pending (menunggu koreksi admin) | completed
 */
class TaskSubmission extends Model
{
    protected $fillable = [
        'task_id', 'user_id', 'answer', 'file_path', 'status', 'submitted_at',
        'score', 'feedback',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Jawaban user per field (1 submission = banyak answer). */
    public function answers(): HasMany
    {
        return $this->hasMany(TaskSubmissionAnswer::class, 'submission_id');
    }

    /** Jawaban untuk field tertentu (keyed by task_field_id biar gampang di view). */
    public function answersByField()
    {
        return $this->answers->keyBy('task_field_id');
    }

    // ── Scope ────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // ── Helper ───────────────────────────────────────────────────────────

    /** Admin menandai submission selesai (terkoreksi), sekaligus memberi nilai & feedback. */
    public function markCompleted(?int $score = null, ?string $feedback = null): void
    {
        $this->update([
            'status' => 'completed',
            'score' => $score,
            'feedback' => $feedback,
        ]);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
