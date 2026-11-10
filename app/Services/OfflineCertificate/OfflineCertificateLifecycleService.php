<?php

namespace App\Services\OfflineCertificate;

use App\Models\CertificateSetting;
use App\Models\OfflineTraining;

class OfflineCertificateLifecycleService
{
    public function __construct(
        private OfflineCertificateSettingService $settingService
    ) {
    }

    /**
     * Triggered when an OfflineTraining record is created (via UI or Excel import).
     * Automatically provisions a default CertificateSetting for this training.
     */
    public function onTrainingCreated(OfflineTraining $training, array $overrides = []): CertificateSetting
    {
        return $this->settingService->ensureSettingForTraining($training, $overrides);
    }
}
