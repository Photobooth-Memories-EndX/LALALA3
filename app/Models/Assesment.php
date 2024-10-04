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
            ->saveSlugsTo('slug');
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

    public function questions()
    {
        return $this->hasMany(AssesmentQuestion::class);
    }
}
