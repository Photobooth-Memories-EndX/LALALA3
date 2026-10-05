<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * TaskField — satu "pertanyaan" dinamis milik task (ala Google Form).
 *
 * type: file (upload file) | textarea (written answer) | choice (pilihan ganda;
 *     dipakai assessment & survey)
 * options: daftar pilihan (choice) — JSON array
 * correct_answer: jawaban benar (assessment/quiz) — untuk auto-scoring
 */
class TaskField extends Model
{
    public const TYPES = [
        'file'     => 'File upload',
        'textarea' => 'Answer',
        'choice'   => 'Multiple choice',
    ];

    protected $fillable = [
        'task_id', 'label', 'type', 'required', 'sort_order',
        'options', 'correct_answer',
    ];

    protected $casts = [
        'required' => 'boolean',
        'options'  => 'array',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(TaskSubmissionAnswer::class, 'task_field_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function isFile(): bool
    {
        return $this->type === 'file';
    }

    public function isChoice(): bool
    {
        return $this->type === 'choice';
    }

}
