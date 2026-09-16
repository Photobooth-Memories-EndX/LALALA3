<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssesmentResult extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $casts = [
        'user_answer' => 'array',
        'question_snapshot' => 'array',
        'afk_duration_seconds' => 'integer',
    ];

    public function getAfkDurationAttribute(): int
    {
        return (int) ($this->afk_duration_seconds ?? 0);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function assesment()
    {
        return $this->belongsTo(Assesment::class);
    }
    public function scoreHistories()
    {
        return $this->hasMany(AssesmentScoreHistory::class, 'assesment_result_id')->latest('id');
    }
}
