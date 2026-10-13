<?php

namespace App\Services\OfflineCertificate;

use App\Models\Certificate;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OfflineCertificateGenerationService
{
    public function __construct(
        private OfflineCertificateEligibilityService $eligibilityService
    ) {
    }

    /**
     * Lazy generate or retrieve a Certificate for an eligible OfflineTrainingUser.
     *
     * @param OfflineTrainingUser $participant
     * @param User|null $actor
     * @return Certificate
     */
    public function generateForParticipant(OfflineTrainingUser $participant, ?User $actor = null): Certificate
    {
        // 1. Revalidate eligibility server-side
        $check = $this->eligibilityService->evaluate($participant);
        if (!$check->eligible) {
            abort(403, $check->reason ?: 'You are not eligible for this certificate.');
        }

        // 2. Return existing certificate if already generated
        $existing = Certificate::where('offline_training_user_id', $participant->id)->first();
        if ($existing) {
            if (!$existing->credential_url) {
                $existing->update([
                    'credential_url' => route('certificate.verify', $existing->credential_id),
                ]);
            }
            return $existing;
        }

        $setting = $check->certificateSetting;
        $training = $participant->training;

        // 3. Determine user ID
        $userId = $participant->user_id;
        if (!$userId && !empty($participant->employee_id)) {
            $userId = User::where('id_emp', $participant->employee_id)->value('id');
        }
        if (!$userId && $actor) {
            $userId = $actor->id;
        }

        // Fallback user if not found
        if (!$userId) {
            $userId = User::value('id') ?? 1;
        }

        $participantName = $participant->name ?: ($actor?->name ?? 'Participant');
        $trainingTitle = $training->course_name ?: ($training->title ?: 'Offline Training');

        return DB::transaction(function () use ($participant, $setting, $training, $userId, $participantName, $trainingTitle, $check) {
            // Lock and compute sequential certificate number
            $next = ((int) Certificate::lockForUpdate()->max('id')) + 1;
            $certificateNumber = 'OWL-CERT-' . now()->format('Y') . '-' . str_pad($next, 6, '0', STR_PAD_LEFT);
            $credentialId = 'OWL-' . strtoupper(Str::random(11));

            $certificate = Certificate::create([
                'user_id'                  => $userId,
                'certificate_setting_id'   => $setting->id,
                'certificate_type'         => 'training_offline',
                'source_id'                => $training->id,
                'offline_training_user_id' => $participant->id,
                'certificate_number'       => $certificateNumber,
                'credential_id'            => $credentialId,
                'credential_url'           => route('certificate.verify', $credentialId),
                'title'                    => $trainingTitle,
                'participant_name'         => $participantName,
                'score'                    => $check->score,
                'issued_at'                => now(),
            ]);

            // Sync legacy certificate text flag on participant if present
            if (empty($participant->certificate) || in_array(strtolower((string) $participant->certificate), ['0', 'no', 'false'], true)) {
                $participant->update(['certificate' => 'Yes']);
            }

            return $certificate;
        });
    }
}
