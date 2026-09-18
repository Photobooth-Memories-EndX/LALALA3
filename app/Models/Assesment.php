<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Assesment extends Model
{
    use HasFactory;
    use HasSlug;

    public function getSlugOptions() : SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function assesments_assigned()
    {
        return $this->hasMany(AssesmentAssigned::class);
    }

    public function scores()
    {
        return $this->hasMany(AssesmentResult::class);
    }

    public function assessmentResults()
    {
        return $this->hasMany(AssesmentResult::class);
    }

    public function scoreHistories()
    {
        return $this->hasMany(AssesmentScoreHistory::class, 'assesment_id')->latest('id');
    }

    public function questions()
    {
        return $this->hasMany(AssesmentQuestion::class);
    }

    public function assessment_question_banks()
    {
        return $this->hasMany(AssesmentQuestionBank::class);
    }

    public function contents()
    {
        return $this->hasMany(Content::class, 'id', 'content_id');
    }

    public function content()
    {
        return $this->belongsTo(Content::class, 'content_id');
    }

    public function trainingplan()
    {
        return $this->belongsTo(TrainingPlan::class, 'trainingplan_id', 'id')->withTrashed()->withDefault();
    }

    protected $guarded = ['id'];

    protected $casts = [
        'has_access_period'     => 'boolean',
        'assasment_content_for' => 'string',
        'afk_threshold_seconds' => 'integer',
    ];

    protected $attributes = [
        'assasment_content_for' => 'content',
        'afk_threshold_seconds' => 60,
    ];

    public function getAfkThresholdSecondsAttribute($value): int
    {
        return !empty($value) && (int) $value > 0 ? (int) $value : 60;
    }

    public function getAfkDurationAttribute(): int
    {
        return (int) $this->afk_threshold_seconds;
    }

    public function hasAccessPeriod(): bool
    {
        if ($this->has_access_period !== null) {
            return (bool) $this->has_access_period;
        }
        return !empty($this->fromdate) && !empty($this->todate);
    }

    public function hasTimeLimit(): bool
    {
        return ($this->timer === 'y') && !empty($this->time_limit_minutes) && (int) $this->time_limit_minutes > 0;
    }

    public static function getUpcomingDeadlineByUser($userId)
    {
        $contentIds = ContentUser::where(
            'user_id',
            $userId
        )
        ->where('finish', 0)
        ->pluck('content_id')
        ->toArray();

        return self::where('status', 'OK')
            ->where('has_access_period', true)
            ->whereNotNull('todate')
            ->whereDate('todate', '>=', now())
            ->whereIn('content_id', $contentIds)
            ->orderBy('todate')
            ->take(5)
            ->get();
    }
}
