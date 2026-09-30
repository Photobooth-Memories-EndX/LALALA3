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
        'title',
        'category',
        'training_code',
        'instructor',
        'location',
        'target_employee',
        'prerequisites',
        'quota',
        'status',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'description',
        'learning_objectives',
        'syllabus',

        // Legacy / compatibility attributes
        'course_name',
        'sub_course_name',
        'type',
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
        'duration_seconds',
        'learning_hours',
        'finish_date',
        'created_by',
    ];

    protected $casts = [
        'syllabus'              => 'array',
        'start_date'            => 'date',
        'end_date'              => 'date',
        'finish_date'           => 'date',
        'quota'                 => 'integer',
        'counted_corporate_kpi' => 'boolean',
        'corporate_kpi_year'    => 'integer',
        'duration_seconds'      => 'integer',
        'learning_hours'        => 'decimal:4',
    ];

    protected static function booted(): void
    {
        static::saving(function ($model) {
            if (empty($model->course_name) && !empty($model->title)) {
                $model->course_name = $model->title;
            }
            if (empty($model->title) && !empty($model->course_name)) {
                $model->title = $model->course_name;
            }
            if (empty($model->venue) && !empty($model->location)) {
                $model->venue = $model->location;
            }
            if (empty($model->location) && !empty($model->venue)) {
                $model->location = $model->venue;
            }
            if (empty($model->finish_date)) {
                $model->finish_date = $model->end_date ?? $model->start_date;
            }
            if (empty($model->end_date) && !empty($model->finish_date)) {
                $model->end_date = $model->finish_date;
            }
            if (empty($model->batch) && !empty($model->training_code)) {
                $model->batch = $model->training_code;
            }
            if (empty($model->training_code) && !empty($model->batch)) {
                $model->training_code = $model->batch;
            }
        });
    }

    public function getCourseNameAttribute($value): ?string
    {
        return $value ?? $this->attributes['title'] ?? null;
    }

    public function getVenueAttribute($value): ?string
    {
        return $value ?? $this->attributes['location'] ?? null;
    }

    public function getFinishDateAttribute($value)
    {
        return $value ?? ($this->attributes['end_date'] ?? $this->attributes['start_date'] ?? null);
    }

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
                $q->whereYear('start_date', $yearInt)
                  ->orWhereYear('end_date', $yearInt)
                  ->orWhereYear('finish_date', $yearInt);
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
