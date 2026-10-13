<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CertificateSetting extends Model
{
    protected $fillable = [
        'certificate_type',
        'content_id',
        'trainingplan_id',
        'offline_training_id',
        'assessment_id',
        'is_assessment_required',
        'minimum_progress',
        'minimum_score',
        'issuer_name',
        'issuer_logo',
        'use_default_template',
        'certificate_template',
        'custom_background',
        'background_image',
        'is_active',
    ];

    protected $casts = [
        'is_assessment_required' => 'boolean',
        'use_default_template' => 'boolean',
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

    public function offlineTraining()
    {
        return $this->belongsTo(OfflineTraining::class, 'offline_training_id');
    }

    public function assessment()
    {
        return $this->belongsTo(Assesment::class, 'assessment_id');
    }

    public function sourceId()
    {
        return match ($this->certificate_type) {
            'content' => $this->content_id,
            'playlist' => $this->trainingplan_id,
            'global' => $this->assessment_id,
            'training_offline', 'offline' => $this->offline_training_id,
            default => null,
        };
    }

    public function sourceTitle(): string
    {
        if ($this->certificate_type === 'content') {
            return $this->content->title ?? 'Content Certificate';
        }
        if ($this->certificate_type === 'playlist') {
            return $this->trainingPlan->title ?? 'Playlist Certificate';
        }
        if (in_array($this->certificate_type, ['training_offline', 'offline'], true)) {
            return $this->offlineTraining->course_name ?? ($this->offlineTraining->title ?? 'Offline Training Certificate');
        }
        return $this->assessment->title ?? 'Global Assessment Certificate';
    }

    public function playlistContents()
    {
        return $this->trainingPlan
            ? $this->trainingPlan->trainingplandetail->pluck('content')->filter()
            : collect();
    }
}
