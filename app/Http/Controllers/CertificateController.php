<?php

namespace App\Http\Controllers;

use App\Models\Assesment;
use App\Models\CertificateSetting;
use App\Models\ContentUser;

class CertificateController extends Controller
{
    public function index()
    {
        $this->syncCertificateSettings();

        $settings = CertificateSetting::with([
            'content',
            'assessment'
        ])
            ->latest()
            ->paginate(15);

        return view(
            'main.certificate.index',
            compact('settings')
        );
    }

    private function syncCertificateSettings()
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Sync dari Assessment yang punya content_id
        |--------------------------------------------------------------------------
        */
        $assessments = Assesment::select(
                'id',
                'title',
                'passing_grade',
                'content_id',
                'trainingplan_id',
                'type'
            )
            ->get();

        foreach ($assessments as $assessment) {

            CertificateSetting::firstOrCreate(
                [
                    'assessment_id' => $assessment->id,
                    'content_id' => $assessment->content_id,
                ],
                [
                    'minimum_progress' => 100,
                    'minimum_score' => $assessment->passing_grade ?? 80,
                    'issuer_name' => 'OWL Training Management',
                    'issuer_logo' => null,
                    'is_active' => 1,
                ]
            );

        }

        /*
        |--------------------------------------------------------------------------
        | 2. Sync dari Content yang sudah selesai tetapi belum punya assessment
        |--------------------------------------------------------------------------
        */
        $completedContents = ContentUser::select('content_id')
            ->where('finish', '1')
            ->where('progress', '>=', 100)
            ->whereNotNull('content_id')
            ->distinct()
            ->get();

        foreach ($completedContents as $item) {

            $hasSetting = CertificateSetting::where('content_id', $item->content_id)
                ->exists();

            if (!$hasSetting) {

                CertificateSetting::create([
                    'content_id' => $item->content_id,
                    'assessment_id' => null,
                    'minimum_progress' => 100,
                    'minimum_score' => 80,
                    'issuer_name' => 'OWL Training Management',
                    'issuer_logo' => null,
                    'is_active' => 1,
                ]);

            }

        }
    }
}
