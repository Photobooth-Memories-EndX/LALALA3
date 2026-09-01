<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContentUser extends Model
{
    use HasFactory;

    public const ASSESSMENT_REQUIRED_PROGRESS = 100;

    protected $fillable = [
        'user_id',
        'content_id',
        'status',
        'finish',
        'progress',
        'last_page',
        'total_pages',
        'current_time',
        'duration_seconds',
        'learning_status',
    ];

    protected $casts = [
        'progress' => 'integer',
        'last_page' => 'integer',
        'total_pages' => 'integer',
        'current_time' => 'integer',
        'duration_seconds' => 'integer',
    ];

    public function getLearningHoursAttribute(): ?float
    {
        if ($this->relationLoaded('content')) {
            return $this->content?->learning_hours !== null ? (float) $this->content->learning_hours : null;
        }

        return $this->content?->learning_hours !== null ? (float) $this->content->learning_hours : null;
    }

    public function content()
    {
        return $this->belongsTo(Content::class);
    }

    public function scopeWithAvailableContent(Builder $query): Builder
    {
        return $query->whereHas('content', fn (Builder $query) => $query->notExpired());
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function assesment()
    {
        return $this->hasOne(Assesment::class, 'content_id', 'content_id');
    }

    public function assesmentPlaylist()
    {
        return $this->hasOne(TrainingPlanDetail::class, 'content_id', 'content_id');
    }

    public function meetsAssessmentProgressRequirement(): bool
    {
        return (int) $this->progress >= self::ASSESSMENT_REQUIRED_PROGRESS;
    }

    public function isFinished(): bool
    {
        return (int) $this->finish === 1 || (int) $this->progress >= 100;
    }
}
