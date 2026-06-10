<?php

namespace App\Services;

use App\Models\Assesment;
use App\Models\CertificateSetting;
use App\Models\Content;
use App\Models\TrainingPlan;

class CertificateSyncService
{
    public function sync(): void
    {
        $this->syncContents();
        $this->syncPlaylists();
        $this->syncGlobalAssessments();
    }

    public function syncContents(): void
    {
        Content::select('id')->each(function ($content) {
            CertificateSetting::firstOrCreate(
                ['certificate_type' => 'content', 'content_id' => $content->id],
                $this->defaults()
            );
        });
    }

    public function syncPlaylists(): void
    {
        TrainingPlan::select('id')->each(function ($playlist) {
            CertificateSetting::firstOrCreate(
                ['certificate_type' => 'playlist', 'trainingplan_id' => $playlist->id],
                $this->defaults()
            );
        });
    }

    public function syncGlobalAssessments(): void
    {
        Assesment::where('type', 'global')->select('id')->each(function ($assessment) {
            CertificateSetting::firstOrCreate(
                ['certificate_type' => 'global', 'assessment_id' => $assessment->id],
                array_merge($this->defaults(), [
                    'is_assessment_required' => true,
                    'minimum_progress' => 0,
                    'minimum_score' => 80,
                ])
            );
        });
    }

    private function defaults(): array
    {
        return [
            'minimum_progress' => 100,
            'minimum_score' => 80,
            'issuer_name' => 'OWL Training Management',
            'use_default_template' => true,
            'is_active' => true,
        ];
    }
}
