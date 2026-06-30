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
}
