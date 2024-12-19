<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Content extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasSlug;
    protected $table = 'content';
    protected $guarded = ['id'];
    protected $dates = ['deleted_at'];

    public function classification()
    {
        return $this->belongsTo(Classification::class, "classification_id", "id");
    }

    public function contentuser()
    {
        return $this->hasMany(ContentUser::class);
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
