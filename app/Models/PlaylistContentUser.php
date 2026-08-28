<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlaylistContentUser extends Model
{
    use HasFactory;

    protected $table = 'playlist_content_users';

    protected $fillable = [
        'user_id',
        'trainingplan_id',
        'content_id',
        'progress',
        'last_page',
        'total_pages',
        'current_time',
        'duration_seconds',
        'learning_status',
        'finish',
    ];

    protected $casts = [
        'user_id'          => 'integer',
        'trainingplan_id'  => 'integer',
        'content_id'       => 'integer',
        'progress'         => 'integer',
        'last_page'        => 'integer',
        'total_pages'      => 'integer',
        'current_time'     => 'integer',
        'duration_seconds' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function trainingPlan()
    {
        return $this->belongsTo(TrainingPlan::class, 'trainingplan_id');
    }

    public function content()
    {
        return $this->belongsTo(Content::class);
    }
}
