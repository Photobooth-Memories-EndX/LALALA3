<?php

namespace App\Services\OfflineCertificate;

use App\Models\Certificate;
use App\Models\CertificateSetting;
use App\Models\OfflineTrainingUser;
use Illuminate\Support\Collection;

/**
 * Dedicated eligibility evaluator for Training Offline certificates.
 *
 * Enforces strict business rules:
 * 1. Certificate Setting must exist and be active (is_active = true).
 * 2. Training and participant must be valid (not soft-deleted or cancelled).
 * 3. Learning Progress >= minimum_progress (default 100%).
 * 4. Attendance strictly Present (Absent, Cancelled, NULL/Pending = Ineligible).
 * 5. If is_assessment_required = true, assessment score must be present and >= minimum_score (default 80).
 * 6. If is_assessment_required = false, assessment score is not checked.
 * 7. Kirkpatrick L1, L2, L3 evaluations do NOT affect eligibility.
 */
class OfflineCertificateEligibilityService
{
    /**
     * Evaluate eligibility for a single OfflineTrainingUser participant.
     */
    public function evaluate(OfflineTrainingUser $participant): OfflineCertificateEligibilityResult
    {
        return $this->evaluateMany(collect([$participant]))->get($participant->id);
    }

    /**
     * Batch evaluate multiple OfflineTrainingUser participants (prevents N+1 queries).
     *
     * @param Collection<int, OfflineTrainingUser> $participants
     * @return Collection<int, OfflineCertificateEligibilityResult> Keyed by OfflineTrainingUser id
     */
    public function evaluateMany(Collection $participants): Collection
    {
        if ($participants->isEmpty()) {
            return collect();
        }

        $participantIds = $participants->pluck('id')->filter()->values();
        $trainingIds = $participants->pluck('offline_training_id')->filter()->unique()->values();

        // Preload settings for all involved trainings
        $settingsByTraining = CertificateSetting::whereIn('offline_training_id', $trainingIds)
            ->whereIn('certificate_type', ['training_offline', 'offline'])
            ->get()
            ->keyBy('offline_training_id');

        // Check if any training with legacy 'yes'/'1' participant is missing a setting
        $missingTrainingIds = $trainingIds->diff($settingsByTraining->keys());
        if ($missingTrainingIds->isNotEmpty()) {
            $settingService = app(OfflineCertificateSettingService::class);
            foreach ($participants as $p) {
                if ($missingTrainingIds->contains($p->offline_training_id)) {
                    $rawCert = strtolower(trim((string) ($p->certificate ?? '')));
                    if (in_array($rawCert, ['1', 'yes', 'true'], true) && $p->training) {
                        $newSetting = $settingService->ensureSettingForTraining($p->training);
                        $settingsByTraining->put($p->offline_training_id, $newSetting);
                        $missingTrainingIds = $missingTrainingIds->reject(fn ($id) => $id == $p->offline_training_id);
                    }
                }
            }
        }

        // Preload existing certificates for these participants
        $certificatesByParticipant = Certificate::whereIn('offline_training_user_id', $participantIds)
            ->get()
            ->keyBy('offline_training_user_id');

        $results = collect();

        foreach ($participants as $participant) {
            $results->put($participant->id, $this->evaluateSingle(
                $participant,
                $settingsByTraining->get($participant->offline_training_id),
                $certificatesByParticipant->get($participant->id)
            ));
        }

        return $results;
    }

    /**
     * Internal evaluation for a single participant with preloaded relations.
     */
    protected function evaluateSingle(
        OfflineTrainingUser $participant,
        ?CertificateSetting $setting,
        ?Certificate $existingCertificate = null
    ): OfflineCertificateEligibilityResult {
        $training = $participant->training;

        // 1. Participant and Training record validity
        if ($participant->trashed()) {
            return new OfflineCertificateEligibilityResult(
                eligible: false,
                reason: 'Participant record has been deleted.',
                participant: $participant,
                certificateSetting: $setting,
                certificate: $existingCertificate
            );
        }

        if (!$training || $training->trashed()) {
            return new OfflineCertificateEligibilityResult(
                eligible: false,
                reason: 'Training record is invalid or has been deleted.',
                participant: $participant,
                certificateSetting: $setting,
                certificate: $existingCertificate
            );
        }

        $trainingStatus = strtolower(trim((string) ($training->status ?? '')));
        if (in_array($trainingStatus, ['cancelled', 'canceled'], true)) {
            return new OfflineCertificateEligibilityResult(
                eligible: false,
                reason: 'Training event was cancelled.',
                participant: $participant,
                certificateSetting: $setting,
                certificate: $existingCertificate
            );
        }

        // 2. Certificate Setting active check
        if (!$setting) {
            // Check if participant has legacy certificate flag ('yes' or '1')
            $rawCert = strtolower(trim((string) ($participant->certificate ?? '')));
            $hasLegacyCertFlag = in_array($rawCert, ['1', 'yes', 'true'], true);

            if ($hasLegacyCertFlag) {
                $setting = app(OfflineCertificateSettingService::class)->ensureSettingForTraining($training);
            } else {
                return new OfflineCertificateEligibilityResult(
                    eligible: false,
                    reason: 'Certificate setting is not configured for this training.',
                    participant: $participant,
                    certificateSetting: null,
                    certificate: $existingCertificate
                );
            }
        }

        if (!$setting->is_active) {
            return new OfflineCertificateEligibilityResult(
                eligible: false,
                reason: 'Certificate setting is currently inactive.',
                participant: $participant,
                certificateSetting: $setting,
                certificate: $existingCertificate
            );
        }

        $minProgress = (float) ($setting->minimum_progress ?? 100.0);
        $progress = (float) ($participant->progress ?? 0.0);
        $assessmentRequired = (bool) $setting->is_assessment_required;
        $minScore = $assessmentRequired ? (float) ($setting->minimum_score ?? 80.0) : null;
        $score = $participant->assessment_score !== null ? (float) $participant->assessment_score : null;

        // 3. Attendance check (Strict Present required)
        $rawAttendance = $participant->getRawOriginal('attendance');
        $isAttended = $this->isAttendancePresent($participant->attendance, $rawAttendance);

        if (!$isAttended) {
            return new OfflineCertificateEligibilityResult(
                eligible: false,
                reason: 'Attendance must be Present to be eligible for certificate.',
                participant: $participant,
                certificateSetting: $setting,
                certificate: $existingCertificate,
                progress: $progress,
                minimumProgress: $minProgress,
                isAttended: false,
                assessmentRequired: $assessmentRequired,
                score: $score,
                minimumScore: $minScore
            );
        }

        // 4. Learning Progress check (default >= 100%)
        if ($progress < $minProgress) {
            return new OfflineCertificateEligibilityResult(
                eligible: false,
                reason: sprintf('Learning progress (%.1f%%) does not meet the minimum requirement of %.1f%%.', $progress, $minProgress),
                participant: $participant,
                certificateSetting: $setting,
                certificate: $existingCertificate,
                progress: $progress,
                minimumProgress: $minProgress,
                isAttended: true,
                assessmentRequired: $assessmentRequired,
                score: $score,
                minimumScore: $minScore
            );
        }

        // 5. Assessment check (Conditional)
        if ($assessmentRequired) {
            if ($score === null) {
                return new OfflineCertificateEligibilityResult(
                    eligible: false,
                    reason: 'Assessment score is required but not recorded.',
                    participant: $participant,
                    certificateSetting: $setting,
                    certificate: $existingCertificate,
                    progress: $progress,
                    minimumProgress: $minProgress,
                    isAttended: true,
                    assessmentRequired: true,
                    score: null,
                    minimumScore: $minScore
                );
            }

            if ($score < $minScore) {
                return new OfflineCertificateEligibilityResult(
                    eligible: false,
                    reason: sprintf('Assessment score (%.1f) is below the minimum passing score of %.1f.', $score, $minScore),
                    participant: $participant,
                    certificateSetting: $setting,
                    certificate: $existingCertificate,
                    progress: $progress,
                    minimumProgress: $minProgress,
                    isAttended: true,
                    assessmentRequired: true,
                    score: $score,
                    minimumScore: $minScore
                );
            }
        }

        // All eligibility conditions satisfied!
        return new OfflineCertificateEligibilityResult(
            eligible: true,
            reason: null,
            participant: $participant,
            certificateSetting: $setting,
            certificate: $existingCertificate,
            progress: $progress,
            minimumProgress: $minProgress,
            isAttended: true,
            assessmentRequired: $assessmentRequired,
            score: $score,
            minimumScore: $minScore
        );
    }

    /**
     * Strictly verifies whether participant attendance represents Present.
     *
     * @param mixed $castedValue Model casted value (boolean)
     * @param mixed $rawOriginal Raw database value from getRawOriginal
     */
    public function isAttendancePresent(mixed $castedValue, mixed $rawOriginal): bool
    {
        // NULL / pending / unknown -> Not Present
        if ($rawOriginal === null) {
            return false;
        }

        // Check if raw value is explicitly absent, cancelled, 0, or false
        $rawStr = strtolower(trim((string) $rawOriginal));
        if (in_array($rawStr, ['0', 'false', 'absent', 'cancelled', 'canceled', 'no', ''], true)) {
            return false;
        }

        // Check if casted value or raw representation is Present
        if ($castedValue === true) {
            return true;
        }

        return in_array($rawStr, ['1', 'true', 'present', 'yes'], true);
    }
}
