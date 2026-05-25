<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'content_id',
        'news_id',
        'eventphoto_id',
        'user_id',
        'parent_id',
        'type',
        'description',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATION USER
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | RELATION CONTENT
    |--------------------------------------------------------------------------
    */

    public function content()
    {
        return $this->belongsTo(Content::class);
    }

    /*
    |--------------------------------------------------------------------------
    | RELATION EVENT PHOTO
    |--------------------------------------------------------------------------
    */

    public function eventphoto()
    {
        return $this->belongsTo(EventPhoto::class);
    }

    /*
    |--------------------------------------------------------------------------
    | RELATION NEWS
    |--------------------------------------------------------------------------
    */

    public function news()
    {
        return $this->belongsTo(News::class);
    }

    /*
    |--------------------------------------------------------------------------
    | REPLY SYSTEM
    |--------------------------------------------------------------------------
    */

    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id')
            ->with('user')
            ->latest();
    }

    /*
    |--------------------------------------------------------------------------
    | LIKE SYSTEM
    |--------------------------------------------------------------------------
    */

    public function likes()
    {
        return $this->hasMany(CommentLike::class);
    }

    public function likedByUser()
    {
        return $this->hasOne(CommentLike::class)
            ->where('user_id', auth()->id());
    }

    /*
    |--------------------------------------------------------------------------
    | COUNT LIKE
    |--------------------------------------------------------------------------
    */

    public function getLikesCountAttribute()
    {
        if ($this->relationLoaded('likes')) {
            return $this->likes->count();
        }

        return $this->likes()->count();
    }

    /*
    |--------------------------------------------------------------------------
    | CHECK USER LIKED
    |--------------------------------------------------------------------------
    */

    public function getIsLikedAttribute()
    {
        if ($this->relationLoaded('likedByUser')) {
            return $this->likedByUser !== null;
        }

        return $this->likedByUser()->exists();
    }
}