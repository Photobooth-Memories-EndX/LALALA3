<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssesmentScoreHistory extends Model
{
    use HasFactory;

    protected $table = 'assesment_score_histories';

    protected $guarded = ['id'];

    public function assesmentResult()
    {
        return $this->belongsTo(AssesmentResult::class, 'assesment_result_id');
    }

    public function assesment()
    {
        return $this->belongsTo(Assesment::class, 'assesment_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by_user_id')->withDefault([
            'name' => 'System',
        ]);
    }
}
