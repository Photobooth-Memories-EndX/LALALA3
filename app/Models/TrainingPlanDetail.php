<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\PlaylistDurationService;

class TrainingPlanDetail extends Model
{
    use HasFactory;

    protected $table = 'trainingplandetail';
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::saved(function (TrainingPlanDetail $detail) {
            app(PlaylistDurationService::class)->refresh((int) $detail->trainingplan_id);
        });

        static::deleted(function (TrainingPlanDetail $detail) {
            app(PlaylistDurationService::class)->refresh((int) $detail->trainingplan_id);
        });
    }

    public function trainingplan()
    {
        return $this->belongsTo(TrainingPlan::class,  'trainingplan_id', 'id')->withTrashed();
    }

    public function content()
    {
        return $this->belongsTo(Content::class,  'content_id', 'id')->withDefault();
    }
}
