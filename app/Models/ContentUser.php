<?php

namespace App\Models;

use App\Services\LearningHours\ActualLearningHourCalculator;
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
        'actual_watch_seconds',
        'afk_duration_seconds',
        'learning_hours',
        'completed_at',
        'learning_status',
    ];

    protected $casts = [
        'progress' => 'integer',
        'last_page' => 'integer',
        'total_pages' => 'integer',
        'current_time' => 'integer',
        'duration_seconds' => 'integer',
        'actual_watch_seconds' => 'integer',
        'afk_duration_seconds' => 'integer',
        'learning_hours' => 'decimal:4',
        'completed_at' => 'datetime',
    ];

    public function getAfkDurationAttribute(): int
    {
        return (int) ($this->afk_duration_seconds ?? 0);
    }

    /**
     * Model boot hooks to ensure completed_at and learning_hours are updated safely
     * WITHOUT executing any database queries (0 N+1).
     */
    protected static function booted(): void
    {
        static::saving(function (ContentUser $contentUser) {
            if ($contentUser->isFinished() && $contentUser->completed_at === null) {
                $contentUser->completed_at = now();
            }

            // Calculate actual learning_hours without executing any database query.
            // Content duration is read from loaded relation or the record's duration_seconds column.
            // If relation is not loaded, NEVER call $contentUser->content()->first() to prevent N+1.
            $contentDuration = null;
            $maxLearningHours = null;
            if ($contentUser->relationLoaded('content') && $contentUser->content) {
                $contentDuration = $contentUser->content->duration;
                $maxLearningHours = $contentUser->content->max_learning_hours;
                $maxCapSeconds = $contentUser->content->max_actual_watch_seconds;
                if ($maxCapSeconds !== null && $contentUser->actual_watch_seconds !== null && $contentUser->actual_watch_seconds > $maxCapSeconds) {
                    $contentUser->actual_watch_seconds = $maxCapSeconds;
                }
            }
            $contentDuration = $contentDuration ?? $contentUser->duration_seconds;

            $actualWatch = (int) ($contentUser->actual_watch_seconds ?? 0);
            if ($actualWatch > 0 || (int) $contentUser->current_time > 0 || $contentDuration !== null || !$contentUser->isFinished()) {
                $calculator = app(ActualLearningHourCalculator::class);
                $contentUser->learning_hours = $calculator->calculateFromTracking(
                    $contentUser->current_time,
                    $contentUser->finish,
                    $contentUser->progress,
                    $contentDuration,
                    $actualWatch,
                    $maxLearningHours
                );
            }
        });
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
