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

    /**
     * File-type groups for the upload settings (ala Google Form).
     * Key = stored in task_fields.file_types (JSON array); empty/null = all allowed.
     */
    public const FILE_TYPE_GROUPS = [
        'document'     => ['label' => 'Document',     'extensions' => ['doc', 'docx', 'txt', 'rtf', 'odt']],
        'spreadsheet'  => ['label' => 'Spreadsheet',  'extensions' => ['xls', 'xlsx', 'csv', 'ods']],
        'pdf'          => ['label' => 'PDF',          'extensions' => ['pdf']],
        'video'        => ['label' => 'Video',        'extensions' => ['mp4', 'avi', 'mov', 'mkv', 'webm']],
        'presentation' => ['label' => 'Presentation', 'extensions' => ['ppt', 'pptx', 'odp']],
        'image'        => ['label' => 'Image',        'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp']],
    ];

    /** Default max file size (MB) when the admin leaves it unset. */
    public const DEFAULT_MAX_SIZE_MB = 5;

    /** Max files choices offered in the admin dropdown. */
    public const MAX_FILES_OPTIONS = [1, 2, 3, 5, 10];

    /** Max size choices (MB) offered in the admin dropdown. */
    public const MAX_SIZE_OPTIONS = [1, 2, 5, 10, 20, 50];

    protected $fillable = [
        'task_id', 'label', 'type', 'required', 'sort_order',
        'options', 'correct_answer',
        'file_types', 'max_files', 'max_size_mb', 'allow_link',
    ];

    protected $casts = [
        'required'   => 'boolean',
        'options'    => 'array',
        'file_types' => 'array',
        'allow_link' => 'boolean',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
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

    // ── File-upload settings (ala Google Form) ──────────────────────────

    /** Selected file-type groups, e.g. ['pdf','document']; empty = all allowed. */
    public function fileTypeGroups(): array
    {
        $groups = $this->file_types ?? [];
        return array_values(array_intersect($groups, array_keys(self::FILE_TYPE_GROUPS)));
    }

    /** Flat list of allowed extensions; empty array = no restriction. */
    public function allowedExtensions(): array
    {
        $exts = [];
        foreach ($this->fileTypeGroups() as $group) {
            $exts = array_merge($exts, self::FILE_TYPE_GROUPS[$group]['extensions']);
        }

        return array_values(array_unique($exts));
    }

    /** Human labels of the selected groups, e.g. "PDF, Document". */
    public function allowedTypesLabel(): string
    {
        $labels = [];
        foreach ($this->fileTypeGroups() as $group) {
            $labels[] = self::FILE_TYPE_GROUPS[$group]['label'];
        }

        return implode(', ', $labels);
    }

    /** Value for the <input type="file" accept="..."> attribute. */
    public function acceptAttribute(): string
    {
        $exts = $this->allowedExtensions();
        if (empty($exts)) {
            return '';
        }

        return '.' . implode(',.', $exts);
    }

    public function maxFiles(): int
    {
        return max(1, (int) ($this->max_files ?? 1));
    }

    public function maxSizeMb(): int
    {
        return max(1, (int) ($this->max_size_mb ?? self::DEFAULT_MAX_SIZE_MB));
    }

    public function maxSizeKb(): int
    {
        return $this->maxSizeMb() * 1024;
    }

}
