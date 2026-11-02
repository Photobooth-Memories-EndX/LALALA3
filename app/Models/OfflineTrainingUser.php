<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class OfflineTrainingUser extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'offline_training_users';

    protected $fillable = [
        'offline_training_id',
        'certificate_id',
        'user_id',
        'employee_id',
        'name',
        'grade',
        'job_position',
        'team',
        'department',
        'company',
        'learning_hours',
        'progress',
        'assessment_score',
        'certificate',
        'attendance',
        'attendance_reason',
        'bond_status',
        'bond_duration',
        'bond_end_date',
        'bond_amount_idr',
        'bond_amount',
        'l1_program',
        'l2_numerical',
        'l2_verbal',
        'l3_numerical',
        'l3_verbal',
        'pic',
    ];

    protected $casts = [
        'learning_hours'   => 'decimal:4',
        'progress'         => 'decimal:4',
        'assessment_score' => 'decimal:2',
        'attendance'       => 'boolean',
        'bond_duration'    => 'integer',
        'bond_end_date'    => 'date',
        'bond_amount_idr'  => 'decimal:2',
        'l2_numerical'     => 'decimal:4',
        'l3_numerical'     => 'decimal:4',
    ];

    protected static ?bool $hasBondDurationCol = null;
    protected static ?bool $hasBondAmountIdrCol = null;

    public function setBondDurationAttribute($val)
    {
        if (self::$hasBondDurationCol === null) {
            self::$hasBondDurationCol = \Illuminate\Support\Facades\Schema::hasColumn($this->getTable(), 'bond_duration');
        }
        if (self::$hasBondDurationCol) {
            $this->attributes['bond_duration'] = $val;
        }
    }

    public function setBondAmountAttribute($val)
    {
        if (self::$hasBondAmountIdrCol === null) {
            self::$hasBondAmountIdrCol = \Illuminate\Support\Facades\Schema::hasColumn($this->getTable(), 'bond_amount_idr');
        }
        if (self::$hasBondAmountIdrCol) {
            $this->attributes['bond_amount_idr'] = $val;
        } else {
            $this->attributes['bond_amount'] = $val;
        }
    }

    public function getBondAmountAttribute()
    {
        return $this->attributes['bond_amount_idr'] ?? $this->attributes['bond_amount'] ?? null;
    }

    public function setBondAmountIdrAttribute($val)
    {
        $this->setBondAmountAttribute($val);
    }

    public function getBondAmountIdrAttribute()
    {
        return $this->getBondAmountAttribute();
    }

    public function training(): BelongsTo
    {
        return $this->belongsTo(OfflineTraining::class, 'offline_training_id');
    }

    public function certificateSetting(): BelongsTo
    {
        return $this->belongsTo(CertificateSetting::class, 'certificate_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'offline_training_user_id');
    }

    public function certificateRecord(): HasOne
    {
        return $this->hasOne(Certificate::class, 'offline_training_user_id')->latestOfMany();
    }

    public function scopeFinished($query)
    {
        return $query->where('progress', '>=', 100);
    }

    public function scopeOngoing($query)
    {
        return $query->where(function ($q) {
            $q->where('progress', '<', 100)
              ->orWhereNull('progress');
        });
    }

    public function scopeAttended($query)
    {
        return $query->where('attendance', true);
    }
}
