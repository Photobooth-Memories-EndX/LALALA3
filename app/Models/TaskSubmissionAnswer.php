<?php

namespace App\Models;

/**
 * TaskSubmissionAnswer — Value Object untuk merepresentasikan SATU butir jawaban field
 * yang tersimpan di kolom JSON `task_submissions.answers`.
 *
 * Tetap mempertahankan interface method (hasFiles, filePaths, displayValue)
 * sehingga seluruh Blade views tetap bekerja tanpa perubahan.
 */
class TaskSubmissionAnswer
{
    public int $task_field_id;
    public ?string $answer_text;
    public $file_path;

    public function __construct(array $attributes = [])
    {
        $this->task_field_id = (int) ($attributes['task_field_id'] ?? 0);
        $this->answer_text   = $attributes['answer_text'] ?? null;
        $this->file_path     = $attributes['file_path'] ?? [];
    }

    /** Ringkasan isi jawaban untuk ditampilkan di tabel admin. */
    public function displayValue(): string
    {
        $paths = $this->filePaths();
        if (! empty($paths)) {
            $names = array_map('basename', $paths);

            return count($names) > 1
                ? $names[0] . ' (+' . (count($names) - 1) . ' more)'
                : $names[0];
        }

        return (string) ($this->answer_text ?? '');
    }

    /**
     * Seluruh file paths yang diunggah untuk jawaban ini.
     */
    public function filePaths(): array
    {
        if (empty($this->file_path)) {
            return [];
        }
        if (is_array($this->file_path)) {
            return array_values(array_filter($this->file_path, fn ($p) => is_string($p) && $p !== ''));
        }
        if (is_string($this->file_path)) {
            $decoded = json_decode($this->file_path, true);
            if (is_array($decoded)) {
                return array_values(array_filter($decoded, fn ($p) => is_string($p) && $p !== ''));
            }

            return [$this->file_path];
        }

        return [];
    }

    public function hasFiles(): bool
    {
        return ! empty($this->filePaths());
    }

    public function __get(string $name)
    {
        return $this->{$name} ?? null;
    }
}
