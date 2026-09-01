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
        'learning_hours' => 'decimal:1',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (ContentUser $contentUser) {
            if ($contentUser->isFinished() && $contentUser->completed_at === null) {
                $contentUser->completed_at = now();
            }

            $content = $contentUser->relationLoaded('content') ? $contentUser->content : $contentUser->content()->first();
            if ($content) {
                $masterHours = $content->learning_hours;
                if ($masterHours === null && !empty($content->duration)) {
                    $masterHours = app(\App\Services\Learning\LearningHourService::class)->calculate($content->duration);
                }

                if ($masterHours !== null) {
                    $masterFloat = (float) $masterHours;
                    $progress = (int) ($contentUser->progress ?? 0);

                    if ($contentUser->isFinished()) {
                        $contentUser->learning_hours = $masterFloat;
                    } elseif ($progress > 0) {
                        $contentUser->learning_hours = round($masterFloat * ($progress / 100), 1);
                    } else {
                        $contentUser->learning_hours = 0.0;
                    }
                }
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
