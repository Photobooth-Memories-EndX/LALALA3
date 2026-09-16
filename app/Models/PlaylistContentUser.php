<?php

namespace App\Models;

use App\Services\LearningHours\ActualLearningHourCalculator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlaylistContentUser extends Model
{
    use HasFactory;

    protected $table = 'playlist_content_users';

    protected $fillable = [
        'user_id',
        'trainingplan_id',
        'content_id',
        'progress',
        'last_page',
        'total_pages',
        'current_time',
        'duration_seconds',
        'afk_duration_seconds',
        'learning_hour',
        'completed_at',
        'learning_status',
        'finish',
    ];

    protected $casts = [
        'user_id'          => 'integer',
        'trainingplan_id'  => 'integer',
        'content_id'       => 'integer',
        'progress'         => 'integer',
        'last_page'        => 'integer',
        'total_pages'      => 'integer',
        'current_time'     => 'integer',
        'duration_seconds' => 'integer',
        'afk_duration_seconds' => 'integer',
        'learning_hour'    => 'decimal:4',
        'completed_at'     => 'datetime',
    ];

    public function getAfkDurationAttribute(): int
    {
        return (int) ($this->afk_duration_seconds ?? 0);
    }

    /**
     * Check if the playlist content learning record is marked finished or progress >= 100%.
     */
    public function isFinished(): bool
    {
        return (string) $this->finish === '1' || (int) $this->progress >= 100;
    }

    /**
     * Model boot hooks to ensure completed_at and learning_hour are updated safely
     * WITHOUT executing any database queries (0 N+1).
     */
    protected static function booted(): void
    {
        static::saving(function (PlaylistContentUser $record) {
            // Set completed_at on completion if not already set (immutable once set)
            if ($record->isFinished() && $record->completed_at === null) {
                $record->completed_at = now();
            }

            // Calculate actual learning_hour without executing any database query.
            // Content duration is read from loaded relation or the record's duration_seconds column.
            // If relation is not loaded, NEVER call $record->content()->first() to prevent N+1.
            $contentDuration = null;
            if ($record->relationLoaded('content')) {
                $contentDuration = $record->content?->duration;
            }
            $contentDuration = $contentDuration ?? $record->duration_seconds;

            // If current_time > 0 OR we have contentDuration, or record is in progress/unfinished:
            // Calculate learning_hour in-memory via pure calculator.
            if ((int) $record->current_time > 0 || $contentDuration !== null || !$record->isFinished()) {
                $calculator = app(ActualLearningHourCalculator::class);
                $record->learning_hour = $calculator->calculateFromTracking(
                    $record->current_time,
                    $record->finish,
                    $record->progress,
                    $contentDuration
                );
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function trainingPlan()
    {
        return $this->belongsTo(TrainingPlan::class, 'trainingplan_id');
    }

    public function content()
    {
        return $this->belongsTo(Content::class);
    }
}
