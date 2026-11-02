<?php

namespace App\Services\OfflineCertificate;

use App\Models\CertificateSetting;
use App\Models\OfflineTraining;

class OfflineCertificateSettingService
{
    /**
     * Ensure a CertificateSetting exists for the given OfflineTraining.
     * Idempotent: returns existing setting if already present, otherwise creates default.
     */
    public function ensureSettingForTraining(OfflineTraining $training, array $overrides = []): CertificateSetting
    {
        $existing = CertificateSetting::where('offline_training_id', $training->id)
            ->whereIn('certificate_type', ['training_offline', 'offline'])
            ->first();

        if ($existing) {
            if (!empty($overrides)) {
                $existing->update($overrides);
            }
            return $existing;
        }

        $attributes = array_merge([
            'certificate_type'       => 'training_offline',
            'offline_training_id'    => $training->id,
            'content_id'             => null,
            'trainingplan_id'        => null,
            'assessment_id'          => null,
            'minimum_progress'       => 100,
            'minimum_score'          => 80,
            'is_assessment_required' => 0,
            'issuer_name'            => 'OWL Training Management',
            'institution'            => 'PT KRAKATAU POSCO',
            'use_default_template'   => 1,
            'is_active'              => 1,
        ], $overrides);

        return CertificateSetting::create($attributes);
    }

    /**
     * Get the active or any CertificateSetting for an OfflineTraining.
     */
    public function getSettingForTraining(OfflineTraining|int $training): ?CertificateSetting
    {
        $trainingId = $training instanceof OfflineTraining ? $training->id : $training;

        return CertificateSetting::where('offline_training_id', $trainingId)
            ->whereIn('certificate_type', ['training_offline', 'offline'])
            ->first();
    }
}
