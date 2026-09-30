<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OfflineTraining extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'offline_trainings';

    protected $fillable = [
        'course_name',
        'sub_course_name',
        'type',
        'category',
        'level',
        'batch',
        'main_classification',
        'sub_classification',
        'counted_corporate_kpi',
        'sub_corporate_kpi',
        'corporate_kpi_year',
        'delivery_method',
        'sub_delivery_method',
        'provider',
        'sub_provider',
        'provider_name',
        'venue',
        'instructor',
        'duration_seconds',
        'learning_hours',
        'start_date',
        'finish_date',
        'created_by',
    ];

    protected $casts = [
        'counted_corporate_kpi' => 'boolean',
        'corporate_kpi_year'    => 'integer',
        'duration_seconds'      => 'integer',
        'learning_hours'        => 'decimal:4',
        'start_date'            => 'date',
        'finish_date'           => 'date',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(OfflineTrainingUser::class, 'offline_training_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(OfflineTrainingUser::class, 'offline_training_id');
    }

    public function scopeByYear($query, $year)
    {
        if (filled($year) && $year !== 'all') {
            $yearInt = (int) $year;
            return $query->where(function ($q) use ($yearInt) {
                $q->whereYear('finish_date', $yearInt)
                  ->orWhere(function ($q2) use ($yearInt) {
                      $q2->whereNull('finish_date')->whereYear('start_date', $yearInt);
                  });
            });
        }

        return $query;
    }

    public function scopeByType($query, $type)
    {
        if (filled($type) && $type !== 'all') {
            return $query->where('type', $type);
        }

        return $query;
    }

    public function scopeByClassification($query, $classification)
    {
        if (filled($classification) && $classification !== 'all') {
            return $query->where('main_classification', $classification);
        }

        return $query;
    }
}
