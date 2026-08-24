<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningHourCalculationBatch extends Model
{
    use HasFactory;

    protected $table = 'learning_hour_calculation_batches';

    protected $fillable = [
        'type',
        'status',
        'total_records',
        'processed_records',
        'failed_records',
        'last_processed_id',
        'created_by',
        'started_at',
        'finished_at',
        'error_message',
    ];

    protected $casts = [
        'total_records' => 'integer',
        'processed_records' => 'integer',
        'failed_records' => 'integer',
        'last_processed_id' => 'integer',
        'created_by' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getPercentageAttribute(): float
    {
        if ($this->total_records <= 0) {
            return 100.0;
        }

        return round(min(100.0, ($this->processed_records / $this->total_records) * 100), 1);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }
}
