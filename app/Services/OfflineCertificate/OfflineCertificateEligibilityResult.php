<?php

namespace App\Services\OfflineCertificate;

use App\Models\Certificate;
use App\Models\CertificateSetting;
use App\Models\OfflineTrainingUser;

/**
 * Result of OfflineCertificateEligibilityService evaluation.
 * Serves as the official contract between service, controller, and UI layers.
 */
final class OfflineCertificateEligibilityResult
{
    public function __construct(
        public readonly bool $eligible,
        public readonly ?string $reason = null,
        public readonly ?OfflineTrainingUser $participant = null,
        public readonly ?CertificateSetting $certificateSetting = null,
        public readonly ?Certificate $certificate = null,
        public readonly float $progress = 0.0,
        public readonly float $minimumProgress = 100.0,
        public readonly bool $isAttended = false,
        public readonly bool $assessmentRequired = false,
        public readonly ?float $score = null,
        public readonly ?float $minimumScore = null
    ) {
    }

    /**
     * Whether an active certificate record already exists in the certificates table.
     */
    public function hasExistingCertificate(): bool
    {
        return $this->certificate !== null;
    }
}
