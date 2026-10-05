<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TaskSubmissionAnswer — jawaban user untuk SATU field task.
 * Terkait ke TaskSubmission (1 submission = banyak answer).
 */
class TaskSubmissionAnswer extends Model
{
    protected $fillable = [
        'submission_id', 'task_field_id', 'answer_text', 'file_path',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(TaskSubmission::class, 'submission_id');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(TaskField::class, 'task_field_id');
    }

    /** Ringkasan isi jawaban untuk ditampilkan di tabel admin. */
    public function displayValue(): string
    {
        if ($this->file_path) {
            return basename($this->file_path);
        }

        return (string) ($this->answer_text ?? '');
    }
}
