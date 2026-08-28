<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use App\Models\ContentUser;
use App\Models\Comment;
use App\Services\PlaylistDurationService;

class Content extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasSlug;
    protected $table = 'content';
    protected $guarded = ['id'];
    protected $dates = ['deleted_at'];

    protected static function booted(): void
    {
        static::saved(function (Content $content) {
            if (!$content->wasChanged('duration')) {
                return;
            }

            $planIds = $content->trainingPlans()->pluck('trainingplan.id');
            app(PlaylistDurationService::class)->refreshMany($planIds);
        });
    }

    protected $casts = [
        'has_deadline' => 'boolean',
        'from_date'    => 'date',
        'to_date'      => 'date',
        'page_duration' => 'integer',
        'learning_hours' => 'float',
    ];

    public function classification()
    {
        return $this->belongsTo(Classification::class, "classification_id", "id");
    }

    public function contentuser()
    {
        return $this->hasMany(ContentUser::class);
    }

    public function playlistContentUsers()
    {
        return $this->hasMany(PlaylistContentUser::class, 'content_id');
    }

    /**
     * Playlist (Training Plan) yang memuat content ini.
     * Dipakai untuk eager-load badge "Playlist" di kartu Learning On Going
     * tanpa memicu N+1. Pivot memakai tabel trainingplandetail.
     */
    public function trainingPlans()
    {
        return $this->belongsToMany(
            TrainingPlan::class,
            'trainingplandetail',
            'content_id',
            'trainingplan_id'
        );
    }

    public function bookmarkedByUsers()
    {
        return $this->belongsToMany(User::class, 'bookmark_contents')
            ->withTimestamps();
    }

    public function bookmarkContents()
    {
        return $this->hasMany(BookmarkContent::class);
    }

    public function allComments()
    {
        return $this->hasMany(Comment::class, 'content_id')->where('type', 'content');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'content_id')->where('type', 'content')->whereNull('parent_id');
    }

    public function getTotalCommentsCountAttribute(): int
    {
        if ($this->relationLoaded('allComments')) {
            return $this->allComments->count();
        }

        return $this->allComments()->count();
    }

    public function isExpired(): bool
    {
        return (bool) $this->has_deadline && $this->to_date && now()->toDateString() > $this->to_date->format('Y-m-d');
    }

    public function isNotStarted(): bool
    {
        return (bool) $this->has_deadline && $this->from_date && now()->toDateString() < $this->from_date->format('Y-m-d');
    }

    public function isPublished(): bool
    {
        return $this->active === 'Active';
    }

    public function isUnpublished(): bool
    {
        return $this->active !== 'Active';
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

    /**
     * Keep content unless it has a non-null deadline before the given date.
     */
    public function scopeNotExpired(Builder $query, ?string $date = null): Builder
    {
        $date ??= today()->toDateString();

        return $query->where(function (Builder $query) use ($date) {
            $query->where('has_deadline', false)
                ->orWhereNull('to_date')
                ->orWhereDate('to_date', '>=', $date);
        });
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

    public function getRouteKey()
    {
        return $this->slug ?: $this->id;
    }
}
