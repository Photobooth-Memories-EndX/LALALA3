<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Classification extends Model
{
    use HasFactory;
    use HasSlug;

    protected $table = 'classification';
    protected $guarded = ['id'];
    public function content()
    {
        return $this->hasMany(Content::class);
    }

    public function subClassifications()
    {
        return $this->hasMany(OfflineTrainingOption::class, 'classification_id')
            ->where('option_type', \App\Services\Record\OfflineOptionService::TYPE_SUB_CLASSIFICATION)
            ->orderBy('sort_order', 'asc')
            ->orderBy('value', 'asc');
    }
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('classification_name')
            ->saveSlugsTo('slug');
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }
}
