<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CertificateSetting extends Model
{
    protected $fillable = [
        'certificate_type',
        'content_id',
        'trainingplan_id',
        'assessment_id',
        'is_assessment_required',
        'minimum_progress',
        'minimum_score',
        'issuer_name',
        'issuer_logo',
        'certificate_template',
        'is_active',
    ];

    protected $casts = [
        'is_assessment_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function content()
    {
        return $this->belongsTo(Content::class, 'content_id');
    }

    public function trainingPlan()
    {
        return $this->belongsTo(TrainingPlan::class, 'trainingplan_id');
    }

    public function assessment()
    {
        return $this->belongsTo(Assesment::class, 'assessment_id');
    }
}