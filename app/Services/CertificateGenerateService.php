<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\CertificateSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CertificateGenerateService
{
    public function __construct(private CertificateEligibilityService $eligibility)
    {
    }

    public function generate(CertificateSetting $setting, User $user): Certificate
    {
        $check = $this->eligibility->check($setting, $user);
        abort_unless($check->eligible, 403, $check->reason);

        return DB::transaction(function () use ($setting, $user, $check) {
            $certificate = Certificate::firstOrCreate(
                [
                    'user_id'                => $user->id,
                    'certificate_setting_id' => $setting->id,
                    'certificate_type'       => $setting->certificate_type,
                    'source_id'              => $setting->sourceId(),
                ],
                [
                    'content_user_id'      => $check->learning?->id,
                    'assessment_result_id' => $check->assessmentResult?->id,
                    'certificate_number'   => $this->number(),
                    'credential_id'        => 'OWL-' . strtoupper(Str::random(11)),
                    'title'                => $setting->sourceTitle(),
                    'participant_name'     => $user->name,
                    'institution'          => $setting->institution ?: 'PT KRAKATAU POSCO',
                    'issued_date'          => $setting->issued_date ?: now(),
                    'expired_date'         => $setting->expired_date,
                    // Score tercatat bila eligibility ditopang hasil assessment
                    // (deteksi otomatis service) — tidak lagi membaca flag
                    // is_assessment_required yang kini murni konfigurasi.
                    'score'                => $check->assessmentResult?->score,
                    'issued_at'            => now(),
                ]
            );
            if (!$certificate->credential_url) {
                $certificate->update([
                    'credential_url' => route('certificate.verify', $certificate->credential_id),
                ]);
            }
            return $certificate;
        });
    }

    private function number(): string
    {
        $next = ((int) Certificate::lockForUpdate()->max('id')) + 1;
        return 'OWL-CERT-' . now()->format('Y') . '-' . str_pad($next, 6, '0', STR_PAD_LEFT);
    }
}
