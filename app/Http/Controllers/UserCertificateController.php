<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\CertificateSetting;
use App\Models\ContentUser;
use App\Services\CertificateGenerateService;
use Illuminate\Support\Facades\Auth;

class UserCertificateController extends Controller
{
    public function generateSetting(CertificateSetting $certificateSetting, CertificateGenerateService $service)
    {
        $certificate = $service->generate($certificateSetting, Auth::user());
        return redirect()->route('certificate.show', $certificate->credential_id)
            ->with('success', 'Certificate berhasil dibuat.');
    }

    public function generate(ContentUser $contentUser, CertificateGenerateService $service)
    {
        abort_unless($contentUser->user_id === Auth::id(), 403);
        $setting = CertificateSetting::where('certificate_type', 'content')
            ->where('content_id', $contentUser->content_id)->where('is_active', 1)->firstOrFail();
        return $this->generateSetting($setting, $service);
    }

    public function show(string $credentialId)
    {
        $certificate = Certificate::with([
            'certificateSetting.assessment',
            'certificateSetting.trainingPlan.trainingplandetail.content',
        ])->where('credential_id', $credentialId)->firstOrFail();
        abort_unless($certificate->user_id === Auth::id(), 403);
        return view('certificate.show', compact('certificate'));
    }

    public function download(string $credentialId)
    {
        $certificate = Certificate::with([
            'certificateSetting.assessment',
            'certificateSetting.trainingPlan.trainingplandetail.content',
        ])->where('credential_id', $credentialId)->firstOrFail();
        abort_unless($certificate->user_id === Auth::id(), 403);
        return response()->view('certificate.templates.default', compact('certificate'))
            ->header('Content-Disposition', 'attachment; filename="' . $certificate->certificate_number . '.html"');
    }

    public function verify(string $credentialId)
    {
        $certificate = Certificate::with('certificateSetting')->where('credential_id', $credentialId)->firstOrFail();
        return view('certificate.verify', compact('certificate'));
    }
}
