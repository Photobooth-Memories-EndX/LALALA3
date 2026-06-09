<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\CertificateSetting;
use App\Models\ContentUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class UserCertificateController extends Controller
{
    public function generate(ContentUser $contentUser)
    {
        abort_unless($contentUser->user_id == Auth::id(), 403);

        $setting = CertificateSetting::where('certificate_type', 'content')
            ->where('content_id', $contentUser->content_id)
            ->where('is_active', 1)
            ->firstOrFail();

        abort_unless($contentUser->progress >= $setting->minimum_progress, 403);

        $certificate = Certificate::firstOrCreate(
            [
                'user_id' => Auth::id(),
                'certificate_setting_id' => $setting->id,
                'content_user_id' => $contentUser->id,
            ],
            [
                'certificate_number' => $this->generateCertificateNumber(),
                'credential_id' => strtoupper(Str::random(12)),
                'credential_url' => null,
                'title' => $contentUser->content->title ?? 'Certificate',
                'participant_name' => Auth::user()->name,
                'score' => null,
                'issued_at' => now(),
                'file_path' => null,
            ]
        );

        return redirect()
            ->back()
            ->with('success', 'Certificate berhasil dibuat.');
    }

    private function generateCertificateNumber()
    {
        $nextNumber = Certificate::count() + 1;

        return 'OWL-CERT-' . date('Y') . '-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }
}