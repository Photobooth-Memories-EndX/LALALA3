<?php

namespace App\Services;

use App\Models\Assesment;
use App\Models\AssesmentResult;
use App\Models\CertificateSetting;
use App\Models\ContentUser;
use App\Models\User;
use App\Services\Learning\LearningProgressResolver;
use Illuminate\Support\Collection;

/**
 * Source of truth kelayakan certificate.
 *
 * Wajib/tidaknya assessment TIDAK lagi dibaca dari kolom konfigurasi
 * certificate_settings.is_assessment_required, melainkan dideteksi otomatis
 * dari relasi data: bila Content/Playlist punya minimal satu assessment aktif
 * (status != NOT), assessment otomatis wajib — user harus lulus SALAH SATU
 * di antaranya (post-test atau remedial-nya). Tanpa assessment, cukup progress.
 * CertificateSetting tetap dipakai sebagai konfigurasi (minimum progress,
 * minimum score, template, issuer) — bukan penentu eligibility.
 *
 * Tipe 'global' adalah pengecualian by-design: setting-nya memang identitas
 * satu assessment (assessment_id), jadi tetap dievaluasi terhadap id tersebut.
 *
 * Seluruh hasil dikembalikan sebagai CertificateEligibilityResult — kontrak
 * resmi antar layer; UI wajib menampilkan nilai dari objek ini agar tidak ada
 * dua sumber perhitungan.
 */
class CertificateEligibilityService
{
    public function __construct(private LearningProgressResolver $resolver)
    {
    }

    public function check(CertificateSetting $setting, User $user): CertificateEligibilityResult
    {
        return $this->checkMany(collect([$setting]), $user)[$setting->id];
    }

    /**
     * Evaluasi banyak setting sekaligus dengan konteks yang dimuat sekali
     * (bebas N+1) — dipakai halaman Learning Record yang mengecek puluhan
     * setting per user.
     *
     * @param  Collection<int, CertificateSetting>  $settings
     * @return array<int, CertificateEligibilityResult>  map: setting id => hasil
     */
    public function checkMany(Collection $settings, User $user): array
    {
        $context = $this->loadContext($settings, $user);

        $results = [];
        foreach ($settings as $setting) {
            $results[$setting->id] = $this->evaluate($setting, $context);
        }

        return $results;
    }

    public function getAssessmentResult(CertificateSetting $setting, User $user)
    {
        if (!$setting->assessment_id) {
            return null;
        }

        return AssesmentResult::where('user_id', $user->id)
            ->where('assesment_id', $setting->assessment_id)
            ->where('final', 1)
            ->orderByDesc('score')
            ->latest('id')
            ->first();
    }

    /**
     * Build the read-only content assessment progress displayed by a playlist.
     *
     * "Active" and "completed" follow the same assessment gate used by
     * certificate eligibility: status NOT is excluded and at least one final
     * result in each content assessment group must meet the effective score.
     *
     * @param  Collection<int, int|string>  $contentIds
     * @return array{
     *     total: int,
     *     completed: int,
     *     percentage: int,
     *     assessmentsByContent: Collection<int|string, Assesment>
     * }
     */
    public function contentAssessmentProgress(
        Collection $contentIds,
        User $user,
        ?CertificateSetting $setting = null,
        ?int $trainingPlanId = null
    ): array
    {
        $contentIds = $contentIds->filter()->unique()->values();

        if ($contentIds->isEmpty()) {
            return [
                'total' => 0,
                'completed' => 0,
                'percentage' => 0,
                'assessmentsByContent' => collect(),
            ];
        }

        $query = Assesment::query()
            ->select(['id', 'content_id', 'trainingplan_id', 'title', 'type', 'assasment_content_for', 'slug', 'batch', 'fromdate', 'todate', 'passing_grade', 'total_remedials', 'unique_code'])
            ->where('type', 'content')
            ->where('status', '!=', 'NOT')
            ->whereIn('content_id', $contentIds);

        if ($trainingPlanId !== null) {
            $query->where('assasment_content_for', 'playlist')
                  ->where('trainingplan_id', $trainingPlanId);
        }

        $assessments = $query->orderBy('id')->get();

        // Preserve the former first-assessment behaviour with an explicit,
        // deterministic rule while retaining every active assessment above
        // for the existing progress calculation.
        $assessmentsByContent = $assessments
            ->unique('content_id')
            ->keyBy('content_id');

        $total = $assessments->pluck('content_id')->filter()->unique()->count();

        if ($total === 0) {
            return [
                'total' => 0,
                'completed' => 0,
                'percentage' => 0,
                'assessmentsByContent' => $assessmentsByContent,
            ];
        }

        $results = AssesmentResult::query()
            ->where('user_id', $user->id)
            ->where('final', 1)
            ->whereIn('assesment_id', $assessments->pluck('id'))
            ->orderByDesc('score')
            ->orderByDesc('id')
            ->get()
            ->unique('assesment_id')
            ->keyBy('assesment_id');

        $completed = $assessments
            ->groupBy('content_id')
            ->filter(fn (Collection $contentAssessments) => $this->passAssessmentGate(
                $setting,
                $contentAssessments,
                ['results' => $results]
            )->eligible)
            ->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'percentage' => (int) round(($completed / $total) * 100),
            'assessmentsByContent' => $assessmentsByContent,
        ];
    }

    // ─── Context loading (query massal — sekali untuk seluruh setting) ──────

    /**
     * @return array{learnings: Collection, playlistProgressByPlan: Collection, assessmentsByContent: Collection,
     *               assessmentsByPlaylist: Collection, results: Collection}
     */
    private function loadContext(Collection $settings, User $user): array
    {
        $contentIds  = $settings->where('certificate_type', 'content')
            ->pluck('content_id')->filter()->unique()->values();
        $playlistSettings = $settings->where('certificate_type', 'playlist');
        $playlistIds = $playlistSettings
            ->pluck('trainingplan_id')->filter()->unique()->values();

        // Direct Content progress
        $learnings = $contentIds->isEmpty()
            ? collect()
            : ContentUser::where('user_id', $user->id)
                ->whereIn('content_id', $contentIds)
                ->get()
                ->keyBy('content_id');

        // Playlist progress resolved per playlist using LearningProgressResolver
        $playlistProgressByPlan = collect();
        foreach ($playlistSettings as $playlistSetting) {
            $planId = (int) $playlistSetting->trainingplan_id;
            if ($planId && !$playlistProgressByPlan->has($planId)) {
                $pContentIds = $this->playlistContentIds($playlistSetting);
                if ($pContentIds->isNotEmpty()) {
                    $resolvedBatch = $this->resolver->resolvePlaylistBatch((int) $user->id, $planId, $pContentIds);
                    $playlistProgressByPlan->put($planId, $resolvedBatch);
                } else {
                    $playlistProgressByPlan->put($planId, collect());
                }
            }
        }

        // Progress dicek per content: content setting + seluruh isi playlist.
        $playlistContentIds = $playlistSettings
            ->flatMap(fn ($s) => $this->playlistContentIds($s))
            ->unique()->values();

        $assessmentContentIds = $contentIds->merge($playlistContentIds)->unique()->values();

        // Deteksi otomatis: assessment aktif milik content/playlist via FK-nya.
        // Status "NOT" = nonaktif (konvensi AssesmentUserController) — tidak
        // dihitung karena user memang tidak bisa mengerjakannya.
        $assessments = ($assessmentContentIds->isEmpty() && $playlistIds->isEmpty())
            ? collect()
            : Assesment::where('status', '!=', 'NOT')
                ->where(function ($q) use ($assessmentContentIds, $playlistIds) {
                    $q->whereIn('content_id', $assessmentContentIds)
                        ->orWhereIn('trainingplan_id', $playlistIds);
                })
                ->get();

        // Hasil final user untuk seluruh assessment terdeteksi + assessment_id
        // milik setting global. Diurutkan score tertinggi (lalu terbaru) agar
        // unique() menyisakan hasil terbaik per assessment — semantik sama
        // dengan getAssessmentResult().
        $assessmentIds = $assessments->pluck('id')
            ->merge($settings->pluck('assessment_id')->filter())
            ->unique()->values();

        $results = $assessmentIds->isEmpty()
            ? collect()
            : AssesmentResult::where('user_id', $user->id)
                ->where('final', 1)
                ->whereIn('assesment_id', $assessmentIds)
                ->orderByDesc('score')
                ->orderByDesc('id')
                ->get()
                ->unique('assesment_id')
                ->keyBy('assesment_id');

        return [
            'learnings'              => $learnings,
            'playlistProgressByPlan' => $playlistProgressByPlan,
            'assessmentsByContent'   => $assessments->filter(fn ($a) => $a->content_id)->groupBy('content_id'),
            'assessmentsByPlaylist'  => $assessments->filter(fn ($a) => $a->trainingplan_id && $a->type === 'trainingplan')->groupBy('trainingplan_id'),
            'results'                => $results,
        ];
    }

    private function playlistContentIds(CertificateSetting $setting): Collection
    {
        return $setting->trainingPlan
            ? $setting->trainingPlan->trainingplandetail->pluck('content_id')->unique()->values()
            : collect();
    }

    // ─── Evaluasi per setting ────────────────────────────────────────────────

    private function evaluate(CertificateSetting $setting, array $context): CertificateEligibilityResult
    {
        if (!$setting->is_active) {
            return new CertificateEligibilityResult(eligible: false, reason: 'Certificate setting is inactive.');
        }

        return match ($setting->certificate_type) {
            'content' => $this->evaluateContent($setting, $context),
            'playlist' => $this->evaluatePlaylist($setting, $context),
            'global' => $this->evaluateGlobal($setting, $context),
            default => new CertificateEligibilityResult(eligible: false, reason: 'Unknown certificate type.'),
        };
    }

    private function evaluateContent(CertificateSetting $setting, array $context): CertificateEligibilityResult
    {
        $learning = $context['learnings']->get($setting->content_id);

        if (!$learning || (int) $learning->progress < (int) $setting->minimum_progress) {
            return new CertificateEligibilityResult(
                eligible: false,
                reason: 'Minimum content progress has not been reached.',
                learning: $learning,
            );
        }

        $contentAssessments = $context['assessmentsByContent']->get($setting->content_id, collect())
            ->filter(fn ($a) => ($a->assasment_content_for ?? 'content') === 'content');

        return $this->passAssessmentGate(
            $setting,
            $contentAssessments,
            $context,
            $learning
        );
    }

    private function evaluatePlaylist(CertificateSetting $setting, array $context): CertificateEligibilityResult
    {
        $contentIds = $this->playlistContentIds($setting);

        if ($contentIds->isEmpty()) {
            return new CertificateEligibilityResult(eligible: false, reason: 'Playlist has no content.');
        }

        $planProgress = $context['playlistProgressByPlan']->get($setting->trainingplan_id, collect());

        $completed = $contentIds->filter(function ($contentId) use ($setting, $planProgress) {
            $resolved = $planProgress->get($contentId);
            return $resolved && ($resolved->isFinished() || (int) $resolved->progress >= (int) $setting->minimum_progress);
        })->count();

        if ($completed !== $contentIds->count()) {
            return new CertificateEligibilityResult(
                eligible: false,
                reason: 'All playlist content must reach the minimum progress.',
            );
        }

        foreach ($contentIds as $contentId) {
            $contentAssessments = $context['assessmentsByContent']->get($contentId, collect())
                ->filter(fn ($a) => ($a->assasment_content_for ?? 'content') === 'playlist' && (int) $a->trainingplan_id === (int) $setting->trainingplan_id);

            if ($contentAssessments->isEmpty()) {
                continue;
            }

            $contentAssessmentEligibility = $this->passAssessmentGate(
                $setting,
                $contentAssessments,
                $context
            );

            if (!$contentAssessmentEligibility->eligible) {
                return $contentAssessmentEligibility;
            }
        }

        return $this->passAssessmentGate(
            $setting,
            $context['assessmentsByPlaylist']->get($setting->trainingplan_id, collect()),
            $context
        );
    }

    private function evaluateGlobal(CertificateSetting $setting, array $context): CertificateEligibilityResult
    {
        if (!$setting->assessment_id) {
            return new CertificateEligibilityResult(eligible: false, reason: 'Global assessment is not configured.');
        }

        $assessmentResult = $context['results']->get($setting->assessment_id);
        $minimumScore     = $this->minimumScore($setting);

        if (!$assessmentResult || (float) $assessmentResult->score < $minimumScore) {
            return new CertificateEligibilityResult(
                eligible: false,
                reason: 'Minimum assessment score has not been reached.',
                assessmentResult: $assessmentResult,
                assessmentRequired: true,
                minimumScore: $minimumScore,
            );
        }

        return new CertificateEligibilityResult(
            eligible: true,
            assessmentResult: $assessmentResult,
            assessmentRequired: true,
            minimumScore: $minimumScore,
        );
    }

    /**
     * Gerbang assessment hasil deteksi otomatis. Tanpa assessment aktif:
     * cukup progress. Dengan assessment (post-test + remedial sebagai baris
     * terpisah): lulus SALAH SATU sudah memenuhi. Saat gagal, hasil final
     * terbaik tetap dilampirkan agar UI menampilkan nilai yang sama dengan
     * yang dievaluasi — bukan perhitungan kedua.
     */
    private function passAssessmentGate(
        ?CertificateSetting $setting,
        Collection $assessments,
        array $context,
        $learning = null
    ): CertificateEligibilityResult {
        if ($assessments->isEmpty()) {
            return new CertificateEligibilityResult(eligible: true, learning: $learning);
        }

        $best           = null;
        $bestAssessment = null;

        foreach ($assessments as $assessment) {
            $assessmentResult = $context['results']->get($assessment->id);
            if (!$assessmentResult) {
                continue;
            }

            $minimumScore = $this->minimumScore($setting, $assessment);

            if ((float) $assessmentResult->score >= $minimumScore) {
                return new CertificateEligibilityResult(
                    eligible: true,
                    learning: $learning,
                    assessmentResult: $assessmentResult,
                    assessmentRequired: true,
                    minimumScore: $minimumScore,
                );
            }

            if (!$best || (float) $assessmentResult->score > (float) $best->score) {
                $best           = $assessmentResult;
                $bestAssessment = $assessment;
            }
        }

        return new CertificateEligibilityResult(
            eligible: false,
            reason: 'Minimum assessment score has not been reached.',
            learning: $learning,
            assessmentResult: $best,
            assessmentRequired: true,
            minimumScore: $this->minimumScore($setting, $bestAssessment ?? $assessments->first()),
        );
    }

    /**
     * Ambang nilai lulus: minimum_score setting (konfigurasi utama);
     * bila kosong pakai passing_grade assessment; terakhir default sistem 80
     * (nilai default kolom minimum_score di schema).
     */
    private function minimumScore(?CertificateSetting $setting, ?Assesment $assessment = null): float
    {
        return (float) ($setting?->minimum_score ?? $assessment?->passing_grade ?? 80);
    }
}
