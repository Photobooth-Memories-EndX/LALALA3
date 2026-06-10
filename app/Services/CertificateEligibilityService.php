<?php

namespace App\Services;

use App\Models\AssesmentResult;
use App\Models\CertificateSetting;
use App\Models\ContentUser;
use App\Models\User;

class CertificateEligibilityService
{
    public function check(CertificateSetting $setting, User $user): array
    {
        if (!$setting->is_active) {
            return $this->result(false, 'Certificate setting is inactive.');
        }

        if ($setting->certificate_type === 'content') {
            $learning = ContentUser::where('user_id', $user->id)
                ->where('content_id', $setting->content_id)->first();
            if (!$learning || (int) $learning->progress < (int) $setting->minimum_progress) {
                return $this->result(false, 'Minimum content progress has not been reached.');
            }
            return $this->withAssessment($setting, $user, $learning);
        }

        if ($setting->certificate_type === 'playlist') {
            $contentIds = $setting->trainingPlan
                ? $setting->trainingPlan->trainingplandetail->pluck('content_id')->unique()
                : collect();
            if ($contentIds->isEmpty()) {
                return $this->result(false, 'Playlist has no content.');
            }
            $completed = ContentUser::where('user_id', $user->id)
                ->whereIn('content_id', $contentIds)
                ->where('progress', '>=', $setting->minimum_progress)
                ->distinct()->count('content_id');
            if ($completed !== $contentIds->count()) {
                return $this->result(false, 'All playlist content must be completed.');
            }
            return $this->withAssessment($setting, $user);
        }

        return $this->withAssessment($setting, $user, null, true);
    }

    private function withAssessment(CertificateSetting $setting, User $user, $learning = null, bool $alwaysRequired = false): array
    {
        if (!$alwaysRequired && !$setting->is_assessment_required) {
            return $this->result(true, null, $learning);
        }
        if (!$setting->assessment_id) {
            return $this->result(false, 'Assessment is not configured.');
        }
        $assessmentResult = AssesmentResult::where('user_id', $user->id)
            ->where('assesment_id', $setting->assessment_id)
            ->where('final', 1)->orderByDesc('score')->first();
        if (!$assessmentResult || (float) $assessmentResult->score < (float) $setting->minimum_score) {
            return $this->result(false, 'Minimum assessment score has not been reached.');
        }
        return $this->result(true, null, $learning, $assessmentResult);
    }

    private function result(bool $eligible, ?string $reason = null, $learning = null, $assessmentResult = null): array
    {
        return compact('eligible', 'reason', 'learning', 'assessmentResult');
    }
}
