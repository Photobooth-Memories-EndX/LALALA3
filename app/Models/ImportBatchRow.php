<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportBatchRow extends Model
{
    use HasFactory;

    protected $table = 'import_batch_rows';

    public const STATUS_VALID = 'VALID';
    public const STATUS_WARNING = 'WARNING';
    public const STATUS_DUPLICATE = 'DUPLICATE';
    public const STATUS_INVALID = 'INVALID';
    public const STATUS_INVALID_COMPANY = 'INVALID COMPANY';

    public const ACTION_IMPORTED = 'IMPORTED';
    public const ACTION_SKIPPED = 'SKIPPED';
    public const ACTION_FAILED = 'FAILED';

    protected $fillable = [
        'import_batch_id',
        'row_number',
        'sheet_name',
        'employee_id',
        'employee_name',
        'course_name',
        'validation_status',
        'action',
        'offline_training_id',
        'offline_training_user_id',
        'error_code',
        'messages',
        'row_data',
    ];

    protected $casts = [
        'row_number' => 'integer',
        'messages'   => 'array',
        'row_data'   => 'array',
    ];

    /**
     * Parent import batch.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }

    /**
     * Associated offline training header event (if created/reused).
     */
    public function training(): BelongsTo
    {
        return $this->belongsTo(OfflineTraining::class, 'offline_training_id');
    }

    /**
     * Associated participant record (if created).
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(OfflineTrainingUser::class, 'offline_training_user_id');
    }

    /**
     * Helper to check if row was actually imported into business tables.
     */
    public function isImported(): bool
    {
        return $this->action === self::ACTION_IMPORTED;
    }
}
