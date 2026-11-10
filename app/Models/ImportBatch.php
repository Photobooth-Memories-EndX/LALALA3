<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ImportBatch extends Model
{
    use HasFactory;

    protected $table = 'import_batches';

    public const SOURCE_PTKP = 'PTKP';
    public const SOURCE_NON_PTKP = 'NON_PTKP';

    public const STATUS_IMPORTING = 'IMPORTING';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_COMPLETED_WITH_WARNINGS = 'COMPLETED_WITH_WARNINGS';
    public const STATUS_FAILED = 'FAILED';

    public const DEFAULT_DISK = 'local';

    protected $fillable = [
        'batch_uuid',
        'source_type',
        'original_filename',
        'file_path',
        'file_disk',
        'file_hash',
        'file_size',
        'imported_by',
        'status',
        'total_rows',
        'valid_rows',
        'warning_rows',
        'duplicate_rows',
        'invalid_rows',
        'imported_rows',
        'skipped_rows',
        'failed_rows',
        'trainings_created',
        'trainings_reused',
        'started_at',
        'finished_at',
        'error_summary',
        'metadata',
    ];

    protected $casts = [
        'file_size'         => 'integer',
        'total_rows'        => 'integer',
        'valid_rows'        => 'integer',
        'warning_rows'      => 'integer',
        'duplicate_rows'    => 'integer',
        'invalid_rows'      => 'integer',
        'imported_rows'     => 'integer',
        'skipped_rows'      => 'integer',
        'failed_rows'       => 'integer',
        'trainings_created' => 'integer',
        'trainings_reused'  => 'integer',
        'started_at'        => 'datetime',
        'finished_at'       => 'datetime',
        'metadata'          => 'array',
    ];

    /**
     * Detail rows associated with this batch.
     */
    public function rows(): HasMany
    {
        return $this->hasMany(ImportBatchRow::class, 'import_batch_id');
    }

    /**
     * User who executed this import batch.
     */
    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    /**
     * Check if the original Excel file exists in private local storage.
     */
    public function hasStoredFile(): bool
    {
        if (empty($this->file_path)) {
            return false;
        }

        $disk = $this->file_disk ?: self::DEFAULT_DISK;
        return Storage::disk($disk)->exists($this->file_path);
    }

    /**
     * Retrieve the stream of the stored private Excel file.
     *
     * @return resource|null
     */
    public function getStoredFileStream()
    {
        if (!$this->hasStoredFile()) {
            return null;
        }

        $disk = $this->file_disk ?: self::DEFAULT_DISK;
        return Storage::disk($disk)->readStream($this->file_path);
    }

    /**
     * Get a sanitized, safe filename for authenticated download.
     */
    public function getSafeDownloadFilename(): string
    {
        $name = pathinfo($this->original_filename, PATHINFO_FILENAME);
        $ext = pathinfo($this->original_filename, PATHINFO_EXTENSION) ?: 'xlsx';
        $sanitized = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $name);
        $prefix = strtolower($this->source_type);

        return "{$prefix}_import_{$this->batch_uuid}_{$sanitized}.{$ext}";
    }

    /**
     * Check if batch finished successfully (with or without warnings).
     */
    public function isSuccessful(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_COMPLETED_WITH_WARNINGS], true);
    }
}
