<?php

namespace App\Http\Controllers\User\Record;

use App\Http\Controllers\Controller;
use App\Http\Controllers\LearningRecordController;
use App\Services\CertificateEligibilityService;

/**
 * Learning Record Online Controller (User side).
 *
 * Menggunakan logic Online Learning Record existing sebagai source of truth.
 */
class LearningRecordOnlineController extends Controller
{
    public function partial(CertificateEligibilityService $eligibilityService)
    {
        return app(LearningRecordController::class)->partial($eligibilityService);
    }
}
