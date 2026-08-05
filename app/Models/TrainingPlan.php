<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class TrainingPlan extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasSlug;

    protected $table = 'trainingplan';
    protected $guarded = ['id'];
    protected $dates = ['deleted_at'];

    protected $casts = [
        'total_duration_seconds' => 'integer',
        'has_deadline' => 'boolean',
        'from_date'    => 'date',
        'to_date'      => 'date',
    ];

    public function getThumbnailUrlAttribute(): ?string
    {
        if (!$this->thumbnail) {
            return null;
        }

        if (Str::startsWith($this->thumbnail, ['http://', 'https://'])) {
            return null;
        }

        $rel = ltrim($this->thumbnail, '/');

        if (!File::exists(public_path('storage/' . $rel))) {
            return null;
        }

        return asset('storage/' . $rel);
    }

    public function trainingplandetail()
    {
        return $this->hasMany(TrainingPlanDetail::class,  'trainingplan_id', 'id');
    }

    public function isExpired(): bool
    {
        return (bool) $this->has_deadline && $this->to_date && now()->toDateString() > $this->to_date->format('Y-m-d');
    }

    public function isNotStarted(): bool
    {
        return (bool) $this->has_deadline && $this->from_date && now()->toDateString() < $this->from_date->format('Y-m-d');
    }

    public function isAccessibleByDeadline(): bool
    {
        if (!$this->has_deadline) {
            return true;
        }
        $today = now()->toDateString();
        if ($this->from_date && $today < $this->from_date->format('Y-m-d')) {
            return false;
        }
        if ($this->to_date && $today > $this->to_date->format('Y-m-d')) {
            return false;
        }
        return true;
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Calculate playlist progress percentage based on strict finished items ratio.
     * Formula: (completed_items / total_items) * 100 rounded to nearest integer.
     */
    public static function calculateProgressPercentage(int $completedItemsCount, int $totalItemsCount): int
    {
        if ($totalItemsCount <= 0) {
            return 0;
        }

        return (int) round(($completedItemsCount / $totalItemsCount) * 100);
    }
}
