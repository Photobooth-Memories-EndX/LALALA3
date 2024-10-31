<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingPlanDetail extends Model
{
    use HasFactory;

    protected $table = 'trainingplandetail';
    protected $guarded = ['id'];

    public function trainingplan()
    {
        return $this->belongsTo(TrainingPlan::class,  'id', 'trainingplan_id');
    }

    public function content()
    {
        return $this->belongsTo(Content::class,  'content_id', 'id')->withDefault();
    }
}
