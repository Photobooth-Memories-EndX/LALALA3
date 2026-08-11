<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class News extends Model
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function allComments()
    {
        return $this->hasMany(Comment::class, 'news_id')->where('type', 'news');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'news_id')->where('type', 'news')->whereNull('parent_id');
    }

    public function getTotalCommentsCountAttribute(): int
    {
        if ($this->relationLoaded('allComments')) {
            return $this->allComments->count();
        }

        return $this->allComments()->count();
    }
}
