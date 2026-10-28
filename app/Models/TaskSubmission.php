<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Model TaskSubmission — jawaban user atas tugas.
 * Jawaban tiap butir pertanyaan tersimpan dalam format JSON di kolom `answers`
 * (sama persis dengan pola assesment_results.user_answer).
 *
 * status: pending (menunggu koreksi admin) | completed
 */
class TaskSubmission extends Model
{
    protected $fillable = [
        'task_id', 'user_id', 'answers', 'status', 'submitted_at',
        'score', 'feedback', 'resubmit_requested',
    ];

    protected $casts = [
        'submitted_at'       => 'datetime',
        'resubmit_requested' => 'boolean',
        'answers'            => 'array',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Accessor answers: mengembalikan collection TaskSubmissionAnswer objects.
     * Kompatibel penuh dengan:
     * - `$submission->answers`
     * - `$submission->answers->keyBy('task_field_id')`
     */
    public function getAnswersAttribute($value): Collection
    {
        return $this->answersByField();
    }

    /**
     * Mutator answers: memastikan tersimpan sebagai json jika menerima array.
     */
    public function setAnswersAttribute($value): void
    {
        $this->attributes['answers'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * Mengembalikan collection jawaban yang di-key berdasarkan task_field_id.
     */
    public function answersByField(): Collection
    {
        $raw = $this->attributes['answers'] ?? null;
        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }
        if (! is_array($raw)) {
            $raw = [];
        }

        $items = [];
        foreach ($raw as $fieldId => $data) {
            if (is_array($data)) {
                $items[$fieldId] = new TaskSubmissionAnswer($data);
            }
        }

        return collect($items);
    }

    /**
     * Mengambil semua path file yang pernah diupload di submission ini (untuk cleanup).
     */
    public function allFilePaths(): array
    {
        $all = [];
        foreach ($this->answersByField() as $answer) {
            foreach ($answer->filePaths() as $p) {
                $all[] = $p;
            }
        }
        return $all;
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

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' && ! $this->resubmit_requested;
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected' || (bool) $this->resubmit_requested;
    }

    public function isCompletedAsRejected(): bool
    {
        return $this->status === 'rejected' && ! $this->resubmit_requested;
    }

    /** Admin menandai submission selesai (terkoreksi), sekaligus memberi nilai & feedback. */
    public function markCompleted(?int $score = null, ?string $feedback = null): void
    {
        $this->update([
            'status'             => 'completed',
            'score'              => $score,
            'feedback'           => $feedback,
            'resubmit_requested' => false,
        ]);
    }

    /** Admin menolak submission: request resubmit atau completed as rejected. */
    public function markRejected(bool $requestResubmit = false, ?string $feedback = null, ?int $score = null): void
    {
        $this->update([
            'status'             => 'rejected',
            'score'              => $score,
            'feedback'           => $feedback,
            'resubmit_requested' => $requestResubmit,
        ]);
    }
}
