<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\CertificateSetting;
use App\Models\ContentUser;
use App\Models\OfflineTrainingUser;
use App\Services\CertificateGenerateService;
use App\Services\OfflineCertificate\OfflineCertificateGenerationService;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;


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

    public function generateOffline(
        OfflineTrainingUser $offlineTrainingUser,
        OfflineCertificateGenerationService $service
    ) {
        $user = Auth::user();
        $isOwner = ($offlineTrainingUser->user_id === $user->id) ||
            (!empty($user->id_emp) && $offlineTrainingUser->employee_id === $user->id_emp);
        abort_unless($isOwner, 403, 'Unauthorized access to offline training record.');

        $certificate = $service->generateForParticipant($offlineTrainingUser, $user);

        return redirect()->route('certificate.show', $certificate->credential_id)
            ->with('success', 'Certificate berhasil dibuat.');
    }

    public function show(string $credentialId)
    {
        $certificate = Certificate::with([
            'certificateSetting.assessment',
            'certificateSetting.offlineTraining',
            'certificateSetting.trainingPlan.trainingplandetail.content',
            'offlineTrainingUser',
            'offlineTraining',
        ])->where('credential_id', $credentialId)->firstOrFail();
        abort_unless($certificate->user_id === Auth::id(), 403);
        return view('certificate.show', compact('certificate'));
    }

    public function preview(string $credentialId)
    {
        $certificate = Certificate::with([
            'certificateSetting.assessment',
            'certificateSetting.content',
            'certificateSetting.offlineTraining',
            'certificateSetting.trainingPlan.trainingplandetail.content',
            'offlineTrainingUser',
            'offlineTraining',
        ])->where('credential_id', $credentialId)->firstOrFail();

        abort_unless($certificate->user_id === Auth::id(), 403);

        return response()->view('certificate.templates.default', compact('certificate'))
            ->header('Cache-Control', 'private, max-age=300');
    }

    public function viewer(string $credentialId)
    {
        $certificate = Certificate::with([
            'certificateSetting.assessment',
            'certificateSetting.content',
            'certificateSetting.offlineTraining',
            'certificateSetting.trainingPlan.trainingplandetail.content',
            'offlineTrainingUser',
            'offlineTraining',
        ])->where('credential_id', $credentialId)->firstOrFail();

        abort_unless($certificate->user_id === Auth::id(), 403);

        return view('certificate.viewer', compact('certificate'));
    }

    public function viewerDocument(string $credentialId)
    {
        $certificate = Certificate::with([
            'certificateSetting.assessment',
            'certificateSetting.content',
            'certificateSetting.offlineTraining',
            'certificateSetting.trainingPlan.trainingplandetail.content',
            'offlineTrainingUser',
            'offlineTraining',
        ])->where('credential_id', $credentialId)->firstOrFail();

        abort_unless($certificate->user_id === Auth::id(), 403);

        $pdf = Pdf::loadView('certificate.templates.pdf', compact('certificate'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream($certificate->certificate_number . '.pdf', [
            'Attachment' => false,
        ]);
    }

    public function download(string $credentialId)
    {
        $certificate = Certificate::with([
            'certificateSetting.assessment',
            'certificateSetting.offlineTraining',
            'certificateSetting.trainingPlan.trainingplandetail.content',
            'offlineTrainingUser',
            'offlineTraining',
        ])->where('credential_id', $credentialId)->firstOrFail();
        abort_unless($certificate->user_id === Auth::id(), 403);
        return response()->view('certificate.templates.default', compact('certificate'))
            ->header('Content-Disposition', 'attachment; filename="' . $certificate->certificate_number . '.html"');
    }

    public function verify(string $credentialId)
    {
        $certificate = Certificate::with([
            'certificateSetting',
            'certificateSetting.offlineTraining',
            'offlineTrainingUser',
            'offlineTraining',
        ])->where('credential_id', $credentialId)->firstOrFail();
        return view('certificate.verify', compact('certificate'));
    }

    public function downloadPdf($credentialId)
    {
        $certificate = Certificate::with([
            'certificateSetting',
            'certificateSetting.assessment',
            'certificateSetting.content',
            'certificateSetting.offlineTraining',
            'certificateSetting.trainingPlan.trainingplandetail.content',
            'offlineTrainingUser',
            'offlineTraining',
        ])
        ->where('credential_id', $credentialId)
        ->firstOrFail();

        abort_unless($certificate->user_id === Auth::id(), 403);

        $pdf = Pdf::loadView(
            'main.certificate.templates.pdf',
            compact('certificate')
        );

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download(
            $certificate->certificate_number . '.pdf'
        );
    }

    public function downloadCustom(string $credentialId)
    {
        $certificate = Certificate::with([
            'certificateSetting',
            'certificateSetting.assessment',
            'certificateSetting.content',
            'certificateSetting.offlineTraining',
            'certificateSetting.trainingPlan.trainingplandetail.content',
            'offlineTrainingUser',
            'offlineTraining',
        ])->where('credential_id', $credentialId)->firstOrFail();

        abort_unless($certificate->user_id === Auth::id(), 403);

        $setting = $certificate->certificateSetting;

        abort_if(!$setting || $setting->use_default_template, 404);

        $hasCustomFile = !empty($setting->certificate_template)
            || !empty($setting->custom_background)
            || !empty($setting->background_image);

        abort_unless($hasCustomFile, 404);

        $pdf = Pdf::loadView('main.certificate.templates.custom-pdf', compact('certificate'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download($certificate->certificate_number . '-custom.pdf');
    }

    public function previewTemplate(CertificateSetting $certificateSetting)
{
    $setting = $certificateSetting->load([
        'assessment',
        'content',
        'trainingPlan.trainingplandetail.content',
    ]);

    return view('main.certificate.template.preview-template', compact('setting'));
}


}
