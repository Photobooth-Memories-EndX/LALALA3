<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
