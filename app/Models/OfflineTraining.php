<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'requirement',
        'start_date',
        'end_date',
        'registration_close_date',
        'registration_close_time',
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
        'bond_amount_idr',
        'bond_amount',
    ];

    protected $casts = [
        'syllabus'                => 'array',
        'start_date'              => 'date',
        'end_date'                => 'date',
        'finish_date'             => 'date',
        'registration_close_date' => 'date',
        'quota'                   => 'integer',
        'counted_corporate_kpi'   => 'boolean',
        'corporate_kpi_year'      => 'integer',
        'duration_seconds'        => 'integer',
        'learning_hours'          => 'decimal:4',
    ];

    protected static function booted(): void
    {
        static::saving(function ($model) {
            // Course Name <-> Title
            if (blank($model->course_name) && filled($model->title)) {
                $model->course_name = $model->title;
            }
            if (blank($model->title) && filled($model->course_name)) {
                $model->title = $model->course_name;
            }

            // Venue <-> Location
            if (blank($model->venue) && filled($model->location)) {
                $model->venue = $model->location;
            }
            if (blank($model->location) && filled($model->venue)) {
                $model->location = $model->venue;
            }

            // Dates: Finish Date <-> End Date (Fallback to Start Date)
            if (blank($model->finish_date) && filled($model->end_date)) {
                $model->finish_date = $model->end_date;
            } elseif (blank($model->finish_date) && filled($model->start_date)) {
                $model->finish_date = $model->start_date;
            }

            if (blank($model->end_date) && filled($model->finish_date)) {
                $model->end_date = $model->finish_date;
            } elseif (blank($model->end_date) && filled($model->start_date)) {
                $model->end_date = $model->start_date;
            }

            // Registration Close Date fallback to Start Date
            if (blank($model->registration_close_date) && filled($model->start_date)) {
                $model->registration_close_date = $model->start_date;
            }

            // Registration Close Time fallback to 23:59:00
            if (blank($model->registration_close_time)) {
                $model->registration_close_time = '23:59:00';
            }

            // Start Time and End Time fallbacks matching NOT NULL database schema defaults
            if (blank($model->start_time)) {
                $model->start_time = '08:00:00';
            }
            if (blank($model->end_time)) {
                $model->end_time = '16:00:00';
            }

            // Batch <-> Training Code
            if (blank($model->batch) && filled($model->training_code)) {
                $model->batch = $model->training_code;
            }
            if (blank($model->training_code) && filled($model->batch)) {
                $model->training_code = $model->batch;
            }
        });
    }

    protected static ?bool $hasRequirementCol = null;

    public function setRequirementAttribute($val)
    {
        if (self::$hasRequirementCol === null) {
            self::$hasRequirementCol = \Illuminate\Support\Facades\Schema::hasColumn($this->getTable(), 'requirement');
        }
        if (self::$hasRequirementCol) {
            $this->attributes['requirement'] = $val;
        } else {
            $this->attributes['status'] = $val;
        }
    }

    public function getRequirementAttribute()
    {
        return $this->attributes['requirement'] ?? $this->attributes['status'] ?? 'Mandatory';
    }

    /**
     * Standardize Delivery Method into: "Offline", "Online", or "Blended".
     */
    public static function normalizeDeliveryMethod(?string $method): string
    {
        $raw = strtolower(trim((string) $method));
        if (in_array($raw, ['in class', 'classroom', 'on-site', 'off-site', 'offline'], true)) {
            return 'Offline';
        }
        if (in_array($raw, ['e-learning', 'webinar', 'online', 'kp-owl', 'kp owl'], true)) {
            return 'Online';
        }
        if (in_array($raw, ['hybrid', 'blended'], true)) {
            return 'Blended';
        }
        return !empty($method) && $method !== '-' ? ucfirst(trim((string) $method)) : '-';
    }

    public function getDeliveryMethodAttribute($value): ?string
    {
        if ($value === null || $value === '' || $value === '-') {
            return $value;
        }
        return self::normalizeDeliveryMethod($value);
    }

    public function getCourseNameAttribute($value): ?string
    {
        return $value ?? $this->attributes['title'] ?? null;
    }

    public function getTitleAttribute($value): ?string
    {
        return $value ?? $this->attributes['course_name'] ?? null;
    }

    public function getVenueAttribute($value): ?string
    {
        $val = filled($value) ? $value : ($this->attributes['location'] ?? $this->attributes['room_name'] ?? $this->attributes['room'] ?? null);
        return filled($val) ? $val : null;
    }

    public function getLocationAttribute($value): ?string
    {
        $val = filled($value) ? $value : ($this->attributes['venue'] ?? $this->attributes['room_name'] ?? $this->attributes['room'] ?? null);
        return filled($val) ? $val : null;
    }

    public function getRoomNameAttribute($value): ?string
    {
        $val = filled($value) ? $value : ($this->attributes['room'] ?? $this->attributes['location'] ?? $this->attributes['venue'] ?? null);
        return filled($val) ? $val : null;
    }

    public function getRoomAttribute($value): ?string
    {
        $val = filled($value) ? $value : ($this->attributes['room_name'] ?? $this->attributes['location'] ?? $this->attributes['venue'] ?? null);
        return filled($val) ? $val : null;
    }

    public function getInstructorNameAttribute($value): ?string
    {
        $val = filled($value) ? $value : ($this->attributes['instructor'] ?? null);
        return filled($val) ? $val : null;
    }

    public function getParticipantQuotaAttribute($value)
    {
        return filled($value) ? $value : ($this->attributes['quota'] ?? null);
    }

    public function getSyllabusSummaryAttribute(): ?string
    {
        if (isset($this->attributes['syllabus_summary']) && filled($this->attributes['syllabus_summary'])) {
            return $this->attributes['syllabus_summary'];
        }

        if (!empty($this->syllabus) && is_array($this->syllabus)) {
            $topics = [];
            foreach ($this->syllabus as $s) {
                $t = is_array($s) ? ($s['topic'] ?? $s['title'] ?? '') : (string) $s;
                if (trim($t) !== '') {
                    $topics[] = trim($t);
                }
            }
            if (!empty($topics)) {
                return implode(', ', array_slice($topics, 0, 3));
            }
        }

        return filled($this->learning_objectives) ? $this->learning_objectives : null;
    }

    public function getFinishDateAttribute($value)
    {
        $val = $value ?? ($this->attributes['end_date'] ?? $this->attributes['start_date'] ?? null);
        return $val ? $this->asDate($val) : null;
    }

    public function getEndDateAttribute($value)
    {
        $val = $value ?? ($this->attributes['finish_date'] ?? $this->attributes['start_date'] ?? null);
        return $val ? $this->asDate($val) : null;
    }

    public function getBatchAttribute($value): ?string
    {
        return $value ?? $this->attributes['training_code'] ?? null;
    }

    public function getTrainingCodeAttribute($value): ?string
    {
        return $value ?? $this->attributes['batch'] ?? null;
    }

    public function getFormattedCorporateKpiAttribute(): string
    {
        if ($this->counted_corporate_kpi === true || $this->counted_corporate_kpi === 1 || $this->counted_corporate_kpi === '1') {
            $year = $this->corporate_kpi_year ?: ($this->sub_corporate_kpi && preg_match('/^\d{4}$/', (string) $this->sub_corporate_kpi) ? $this->sub_corporate_kpi : null);
            return $year ? "Yes ({$year})" : "Yes";
        }
        if ($this->counted_corporate_kpi === false || $this->counted_corporate_kpi === 0 || $this->counted_corporate_kpi === '0') {
            return "No";
        }
        return "-";
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

    public function registrations(): HasMany
    {
        return $this->hasMany(OfflineTrainingRegister::class, 'offline_training_id');
    }

    public function certificateSetting(): HasOne
    {
        return $this->hasOne(CertificateSetting::class, 'offline_training_id');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'source_id')
            ->whereIn('certificate_type', ['training_offline', 'offline']);
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

    /**
     * Scope query to Training Schedule records only.
     */
    public function scopeForSchedule($query)
    {
        return self::applyScheduleScope($query);
    }

    /**
     * Scope query to Non KP OWL / Manage Record records only.
     */
    public function scopeForRecord($query)
    {
        return self::applyRecordScope($query);
    }

    /**
     * Alias for scopeForSchedule.
     */
    public function scopeSchedule($query)
    {
        return self::applyScheduleScope($query);
    }

    /**
     * Alias for scopeForRecord.
     */
    public function scopeRecord($query)
    {
        return self::applyRecordScope($query);
    }

    /**
     * Apply Training Schedule structural constraint to an Eloquent or Query Builder query.
     */
    public static function applyScheduleScope($query, string $tablePrefix = '')
    {
        $p = $tablePrefix !== '' ? rtrim($tablePrefix, '.') . '.' : '';

        return $query->where(function ($q) use ($p) {
            $q->whereNotNull("{$p}target_employee")
              ->orWhereNotNull("{$p}syllabus")
              ->orWhereNotNull("{$p}prerequisites")
              ->orWhere(function ($sub) use ($p) {
                  $sub->whereNull("{$p}delivery_method")
                      ->whereNull("{$p}provider")
                      ->whereNull("{$p}type");
              });
        })->whereNull("{$p}delivery_method")
          ->whereNull("{$p}provider");
    }

    /**
     * Apply Non KP OWL / Manage Record structural constraint to an Eloquent or Query Builder query.
     */
    public static function applyRecordScope($query, string $tablePrefix = '')
    {
        $p = $tablePrefix !== '' ? rtrim($tablePrefix, '.') . '.' : '';

        return $query->where(function ($q) use ($p) {
            $q->whereNotNull("{$p}type")
              ->orWhereNotNull("{$p}delivery_method")
              ->orWhereNotNull("{$p}provider");
        })->whereNull("{$p}target_employee")
          ->whereNull("{$p}syllabus");
    }
}

