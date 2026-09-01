<?php

namespace App\Models;

use App\Services\Learning\LearningHourService;
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
        'learning_hour'    => 'decimal:1',
        'completed_at'     => 'datetime',
    ];

    /**
     * Check if the playlist content learning record is marked finished or progress >= 100%.
     */
    public function isFinished(): bool
    {
        return (string) $this->finish === '1' || (int) $this->progress >= 100;
    }

    /**
     * Model boot hooks to ensure learning_hour and completed_at are calculated and persisted.
     */
    protected static function booted(): void
    {
        static::saving(function (PlaylistContentUser $record) {
            $isFinished = $record->isFinished();

            // Set completed_at on completion if not already set (immutable once set)
            if ($isFinished && $record->completed_at === null) {
                $record->completed_at = now();
            }

            // Calculate user learning_hour based on content master learning_hours and progress
            $content = $record->relationLoaded('content') ? $record->content : $record->content()->first();
            if ($content) {
                $masterHours = $content->learning_hours;
                if ($masterHours === null && !empty($content->duration)) {
                    $masterHours = app(LearningHourService::class)->calculate($content->duration);
                }

                if ($masterHours !== null) {
                    $masterFloat = (float) $masterHours;
                    $progress = (int) ($record->progress ?? 0);

                    if ($isFinished) {
                        $record->learning_hour = $masterFloat;
                    } elseif ($progress > 0) {
                        $record->learning_hour = round($masterFloat * ($progress / 100), 1);
                    } else {
                        $record->learning_hour = 0.0;
                    }
                }
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
