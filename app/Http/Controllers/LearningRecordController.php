<?php

namespace App\Http\Controllers;

use App\Models\Assesment;
use App\Models\AssesmentResult;
use App\Models\Certificate;
use App\Models\CertificateSetting;
use App\Models\ContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Services\CertificateEligibilityService;
use App\Services\Learning\LearningHourService;
use App\Support\DurationFormatter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Learning Record — histori pembelajaran yang telah selesai.
 *
 * Arsitektur mengikuti Learning On Going: Playlist (Training Plan) dan Content
 * adalah dua entity berbeda yang hidup berdampingan — tidak saling menggantikan.
 *
 * Aturan tampil:
 *  - Playlist tampil bila SELURUH isinya selesai (progress 100%).
 *  - Content tampil bila finish = 1 (logic lama, tidak berubah).
 *
 * Data user sedikit (<100 baris) sehingga seluruh baris dirender langsung oleh
 * Blade; search, filter, dan pagination berjalan client-side (jQuery) tanpa
 * AJAX. Controller hanya menyiapkan data — seluruh HTML ada di Blade Components.
 */
class LearningRecordController extends Controller
{
    public function __construct(private readonly LearningHourService $learningHourService)
    {
    }

    /**
     * HTML fragment tab "record" pada My Learning dashboard.
     * Dipanggil dari LearningController::dashboardPartial (routing only).
     */
    public function partial(CertificateEligibilityService $eligibilityService)
    {
        $userId = (int) Auth::id();

        $completedPlaylists = $this->getCompletedPlaylists($userId);
        $cert               = $this->certificateContext($eligibilityService);

        // Seluruh baris (Content + Playlist) dinormalisasi lalu diurutkan
        // sebagai antrean prioritas: baris yang siap Generate Certificate
        // selalu paling atas, di dalam tiap kelompok Finish Date terbaru dulu.
        // Murni pengurutan Collection — query dan business logic tidak berubah.
        $rows = $this->mergeLearningRecord(
            $this->buildContentRows($this->getCompletedContents($userId), $cert),
            $this->buildPlaylistRows(
                $completedPlaylists,
                $cert,
                // Slug content pertama tiap playlist — untuk link badge Playlist
                // (route playlist.user.view butuh pasangan slug playlist + content).
                $this->playlistFirstContentSlug($completedPlaylists)
            )
        )->sortBy([
            ['cert_priority', 'asc'],
            ['finish_date', 'desc'],
        ])->values();

        return view('user.learning.dashboard-partials.record', [
            'rows'             => $rows,
            // Section prioritas paling atas: certificate yang sudah memenuhi syarat.
            'certificateReady' => $this->certificateReady($cert, $completedPlaylists),
            'filterOptions'    => $this->recordFilterOptions($userId, $completedPlaylists, $rows),
        ]);
    }

    /**
     * HTML fragment tab "assessment" pada My Learning dashboard.
     */
    public function assessment()
    {
        $user   = Auth::user();
        $userId = Auth::id();

        return view('user.learning.dashboard-partials.assessment', [
            'assessments' => $this->getUserAssessments($user, $userId),
            'result'      => $this->getUserAssessmentResults($userId),
        ]);
    }

    /**
     * HTML fragment tab "certificate" pada My Learning dashboard.
     */
    public function certificate()
    {
        $userCertificates = Certificate::where('user_id', Auth::id())->latest()->get();

        return view('user.learning.dashboard-partials.certificate', compact('userCertificates'));
    }

    // ─── Filter options ─────────────────────────────────────────────────────────

    /**
     * Opsi dropdown filter (Type, Classification, Playlist, Year) dari data asli
     * user. Filter dieksekusi client-side; server hanya menyediakan opsinya.
     *
     * @return array<string, mixed>
     */
    private function recordFilterOptions(int $userId, Collection $completedPlaylists, Collection $rows): array
    {
        $base = ContentUser::query()
            ->join('content', 'content.id', '=', 'content_users.content_id')
            ->leftJoin('classification', 'classification.id', '=', 'content.classification_id')
            ->where('content_users.user_id', $userId)
            ->where('content_users.finish', '1')
            ->whereNull('content.deleted_at');

        $types = (clone $base)
            ->whereNotNull('content.type')->where('content.type', '!=', '')
            ->distinct()->orderBy('content.type')->pluck('content.type');

        $classifications = (clone $base)
            ->whereNotNull('classification.classification_name')
            ->distinct()->orderBy('classification.classification_name')
            ->pluck('classification.classification_name');

        $years = (clone $base)
            ->selectRaw('DISTINCT YEAR(content_users.updated_at) as y')
            ->orderByDesc('y')->pluck('y')
            ->merge($completedPlaylists->map(
                fn ($p) => $p->last_finished_at ? Carbon::parse($p->last_finished_at)->year : null
            ))
            ->filter()->unique()->sortDesc()->values();

        return [
            'types'           => $types,
            'classifications' => $classifications,
            'playlists'       => $completedPlaylists->map(fn ($p) => ['id' => $p->id, 'title' => $p->title])->values(),
            'years'           => $years,
            'learningHours'   => $this->learningHourService->filterOptions($rows->pluck('learning_hour_bucket')),
        ];
    }

    // ─── Data sources ───────────────────────────────────────────────────────────

    /**
     * Content selesai (finish = 1) — logic lama, dengan eager loading (bebas N+1).
     * Content yang berada di dalam Playlist tetap tampil berdampingan dengan
     * baris Playlist-nya (konsep sama seperti Learning On Going).
     */
    private function getCompletedContents(int $userId)
    {
        return ContentUser::with([
                'content.classification',
                // Playlist pemilik content — untuk badge di kolom Class (bebas N+1).
                // Catatan: relasi assesment/scores TIDAK dimuat lagi — score kini
                // diambil dari CertificateEligibilityResult (satu source of truth).
                'content.trainingPlans' => fn ($q) => $q->select('trainingplan.id', 'trainingplan.title'),
            ])
            ->where('user_id', $userId)
            ->where('finish', '1')
            ->get()
            ->filter(fn ($cu) => $cu->content !== null)
            ->values();
    }

    /**
     * Playlist (Training Plan) yang telah 100% selesai — agregasi SQL via subquery,
     * tanpa query di dalam foreach.
     *
     * @return \Illuminate\Support\Collection<int, TrainingPlan>
     */
    private function getCompletedPlaylists(int $userId)
    {
        return TrainingPlan::query()
            ->select('trainingplan.id', 'trainingplan.title', 'trainingplan.slug', 'trainingplan.thumbnail', 'trainingplan.total_duration_seconds')
            ->whereExists(function ($q) use ($userId) {
                $q->from('trainingplandetail')
                    ->join('content_users', 'content_users.content_id', '=', 'trainingplandetail.content_id')
                    ->whereColumn('trainingplandetail.trainingplan_id', 'trainingplan.id')
                    ->where('content_users.user_id', $userId)
                    ->where('content_users.finish', '1');
            })
            ->selectSub(
                TrainingPlanDetail::selectRaw('COUNT(*)')
                    ->whereColumn('trainingplandetail.trainingplan_id', 'trainingplan.id'),
                'total_items'
            )
            ->selectSub(
                ContentUser::selectRaw('COUNT(*)')
                    ->join('trainingplandetail', 'trainingplandetail.content_id', '=', 'content_users.content_id')
                    ->whereColumn('trainingplandetail.trainingplan_id', 'trainingplan.id')
                    ->where('content_users.user_id', $userId)
                    ->where('content_users.finish', '1'),
                'finished_items'
            )
            ->selectSub(
                ContentUser::selectRaw('MAX(content_users.updated_at)')
                    ->join('trainingplandetail', 'trainingplandetail.content_id', '=', 'content_users.content_id')
                    ->whereColumn('trainingplandetail.trainingplan_id', 'trainingplan.id')
                    ->where('content_users.user_id', $userId)
                    ->where('content_users.finish', '1'),
                'last_finished_at'
            )
            ->orderByDesc('last_finished_at')
            ->get()
            ->filter(fn (TrainingPlan $p) => (int) $p->total_items > 0 && (int) $p->finished_items >= (int) $p->total_items)
            ->values();
    }

    // ─── Row builders & merge ───────────────────────────────────────────────────

    /**
     * Normalisasi ContentUser menjadi baris tabel. Baris berisi data polos —
     * HTML dirender oleh Blade Components (x-learning.record-row).
     */
    private function buildContentRows(Collection $contentUsers, array $cert): Collection
    {
        return $contentUsers->map(function (ContentUser $cu) use ($cert) {
            $content = $cu->content;

            $setting     = $cert['byContent']->get($content->id);
            $eligibility = $setting ? ($cert['eligibility'][$setting->id] ?? null) : null;

            // Kolom Certificate hanya punya dua status: 'ready' (seluruh syarat
            // terpenuhi menurut CertificateEligibilityService — source of truth
            // yang sama dengan endpoint generate) atau 'none' (kolom kosong).
            // Sudah/belum pernah terbit tidak berpengaruh — generate memakai
            // firstOrCreate sehingga tidak pernah menduplikasi record.
            $certState = ($eligibility?->eligible ?? false) ? 'ready' : 'none';

            // Antrean prioritas urutan tabel: 1 = siap Generate Certificate,
            // 2 = selesai tapi belum memenuhi syarat certificate.
            $certPriority = $certState === 'ready' ? 1 : 2;

            $playlists = $content->relationLoaded('trainingPlans')
                ? $content->trainingPlans
                : collect();
            $learningHourBucket = $this->learningHourService->bucket($content->duration);

            return [
                'row_kind'        => 'content',
                'title'           => $content->title ?? '-',
                'slug'            => $content->slug,
                'playlist_url'    => null,
                'subtitle'        => ucfirst($cu->status ?? '-'),
                'playlist_names'  => $playlists->pluck('title')->filter()->values()->all(),
                // Id playlist pemilik content — dipakai filter Playlist client-side.
                'playlist_ids'    => $playlists->pluck('id')->values()->all(),
                'type'            => $content->type ?? 'Content',
                'classification'  => $content->classification->classification_name ?? '-',
                'duration'        => DurationFormatter::formatForLearningRecord($content->duration),
                'duration_sort'   => DurationFormatter::toSeconds($content->duration) ?? -1,
                'learning_hour'   => $this->learningHourService->formatHours($learningHourBucket),
                'learning_hour_bucket' => $learningHourBucket,
                'learning_hour_value'  => $this->learningHourService->value($learningHourBucket),
                'progress'        => (int) ($cu->progress ?? 0),
                'min_progress'    => $setting ? (int) $setting->minimum_progress : 0,
                'finish_date'     => optional($cu->updated_at)->format('Y-m-d H:i') ?? '',
                // Score & ambangnya dari CertificateEligibilityResult — angka
                // yang sama dengan yang dipakai menentukan tombol Generate.
                'score'                => $eligibility?->score(),
                'min_score'            => (int) ($eligibility?->minimumScore ?? 0),
                'assessment_required'  => (bool) ($eligibility?->assessmentRequired ?? false),
                'cert_state'      => $certState,
                'cert_priority'   => $certPriority,
                'cert_action_url' => route('certificate.generate', $cu->id),
                'cert_is_form'    => false,
            ];
        })->values();
    }

    /**
     * Slug content pertama tiap playlist (urutan content_order) — dipakai untuk
     * membangun link badge Playlist di kolom Type (presentasi, bukan business
     * logic). Satu query untuk seluruh playlist, mengikuti pola
     * LearningOngoingController::playlistNextContent().
     *
     * @return \Illuminate\Support\Collection<int, string>  map: trainingplan_id => content slug
     */
    private function playlistFirstContentSlug(Collection $playlists): Collection
    {
        $planIds = $playlists->pluck('id')->all();

        if (empty($planIds)) {
            return collect();
        }

        return DB::table('trainingplandetail as tpd')
            ->join('content as c', 'c.id', '=', 'tpd.content_id')
            ->whereIn('tpd.trainingplan_id', $planIds)
            ->whereNull('c.deleted_at')
            ->orderByRaw('CAST(tpd.content_order AS UNSIGNED) ASC')
            ->get(['tpd.trainingplan_id as plan_id', 'c.slug as slug'])
            ->groupBy('plan_id')
            ->map(fn ($items) => $items->first()->slug);
    }

    /**
     * Normalisasi Playlist selesai menjadi baris tabel dengan struktur kolom
     * yang sama seperti baris Content.
     */
    private function buildPlaylistRows(Collection $playlists, array $cert, Collection $firstContentSlugs): Collection
    {
        return $playlists->map(function (TrainingPlan $plan) use ($cert, $firstContentSlugs) {
            $setting     = $cert['byPlaylist']->get($plan->id);
            $eligibility = $setting ? ($cert['eligibility'][$setting->id] ?? null) : null;
            $eligible    = $eligibility?->eligible ?? false;

            $finishDate = $plan->last_finished_at
                ? Carbon::parse($plan->last_finished_at)->format('Y-m-d H:i')
                : '';

            $firstSlug = $firstContentSlugs->get($plan->id);
            $durationSeconds = (int) ($plan->total_duration_seconds ?? 0);
            $learningHourBucket = $this->learningHourService->bucketFromSeconds($durationSeconds);

            return [
                'row_kind'        => 'playlist',
                'title'           => $plan->title ?? '-',
                'slug'            => null,
                // Link badge Playlist di kolom Type (route lama, tanpa route baru).
                'playlist_url'    => ($plan->slug && $firstSlug)
                    ? route('playlist.user.view', [$plan->slug, $firstSlug])
                    : null,
                'subtitle'        => (int) $plan->finished_items . ' / ' . (int) $plan->total_items . ' content selesai',
                'playlist_names'  => [],
                'playlist_ids'    => [$plan->id],
                'type'            => 'Playlist',
                'classification'  => 'Playlist',
                'duration'        => $this->formatDurationSeconds($durationSeconds),
                'duration_sort'   => $durationSeconds,
                'learning_hour'   => $this->learningHourService->formatHours($learningHourBucket),
                'learning_hour_bucket' => $learningHourBucket,
                'learning_hour_value'  => $this->learningHourService->value($learningHourBucket),
                'progress'        => 100,
                'min_progress'    => $setting ? (int) ($setting->minimum_progress ?? 0) : 0,
                'finish_date'     => $finishDate,
                // Pola sama dengan Content: score dari CertificateEligibilityResult —
                // playlist ber-assessment kini menampilkan nilai sungguhan.
                'score'                => $eligibility?->score(),
                'min_score'            => (int) ($eligibility?->minimumScore ?? 0),
                'assessment_required'  => (bool) ($eligibility?->assessmentRequired ?? false),
                // Aturan playlist sudah di CertificateEligibilityService:
                // tanpa assessment cukup progress; dengan assessment wajib
                // lulus minimum score.
                'cert_state'      => $eligible ? 'ready' : 'none',
                'cert_priority'   => $eligible ? 1 : 2,
                'cert_action_url' => $setting ? route('certificate.generate.setting', $setting) : null,
                'cert_is_form'    => true,
            ];
        })->values();
    }

    /**
     * Gabungkan baris Content + Playlist — Collection merge, bukan UNION,
     * karena strukturnya berbeda di level query.
     */
    private function mergeLearningRecord(Collection $contentRows, Collection $playlistRows): Collection
    {
        return $contentRows->concat($playlistRows)->values();
    }

    private function formatDurationSeconds(int $seconds): string
    {
        if ($seconds <= 0) {
            return '-';
        }

        $hours   = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        $parts = [];

        if ($hours > 0) {
            $parts[] = $hours . ' ' . ($hours === 1 ? 'hour' : 'hours');
        }

        if ($minutes > 0 || $hours === 0) {
            $parts[] = $minutes . ' min';
        }

        return implode(' ', $parts);
    }

    // ─── Certificate ────────────────────────────────────────────────────────────

    /**
     * Konteks certificate sekali muat: setting aktif, sertifikat terbit, dan
     * hasil eligibility per setting (CertificateEligibilityService yang sudah ada).
     *
     * @return array{byContent: Collection, byPlaylist: Collection, eligibility: array<int, \App\Services\CertificateEligibilityResult>, certificates: Collection, settings: Collection}
     */
    private function certificateContext(CertificateEligibilityService $service): array
    {
        $user   = Auth::user();
        $userId = (int) $user->id;

        // Pre-filter SQL: hanya setting yang MUNGKIN relevan bagi user ini yang
        // dicek — syarat perlu (bukan cukup); keputusan akhir tetap di
        // CertificateEligibilityService. Tanpa ini, ratusan setting aktif milik
        // user lain ikut memicu query eligibility (N+1 terhadap setting).
        $settings = CertificateSetting::with([
                'content:id,title',
                'trainingPlan.trainingplandetail',
                'assessment:id,title',
            ])
            ->where('is_active', 1)
            ->where(function ($q) use ($userId) {
                // Content: user pernah mengerjakan content tsb & progress-nya
                // sudah menyentuh minimum (perlu untuk eligible / tampil di record).
                $q->where(function ($w) use ($userId) {
                    $w->where('certificate_type', 'content')
                        ->whereExists(function ($s) use ($userId) {
                            $s->from('content_users')
                                ->whereColumn('content_users.content_id', 'certificate_settings.content_id')
                                ->where('content_users.user_id', $userId)
                                ->whereRaw('content_users.progress >= COALESCE(certificate_settings.minimum_progress, 0)');
                        });
                })
                // Playlist: user punya aktivitas pada minimal satu content playlist.
                ->orWhere(function ($w) use ($userId) {
                    $w->where('certificate_type', 'playlist')
                        ->whereExists(function ($s) use ($userId) {
                            $s->from('trainingplandetail')
                                ->join('content_users', 'content_users.content_id', '=', 'trainingplandetail.content_id')
                                ->whereColumn('trainingplandetail.trainingplan_id', 'certificate_settings.trainingplan_id')
                                ->where('content_users.user_id', $userId);
                        });
                })
                // Global: user punya hasil assessment final pada assessment tsb.
                ->orWhere(function ($w) use ($userId) {
                    $w->where('certificate_type', 'global')
                        ->whereExists(function ($s) use ($userId) {
                            $s->from('assesment_results')
                                ->whereColumn('assesment_results.assesment_id', 'certificate_settings.assessment_id')
                                ->where('assesment_results.user_id', $userId)
                                ->where('assesment_results.final', 1);
                        });
                });
            })
            ->get();

        $certificates = Certificate::where('user_id', $user->id)
            ->get()
            ->keyBy('certificate_setting_id');

        // Satu panggilan batch — service memuat konteks (progress, assessment,
        // hasil final) sekali untuk seluruh setting, bukan query per setting.
        $eligibility = $service->checkMany($settings, $user);

        return [
            'settings'     => $settings,
            'byContent'    => $settings->where('certificate_type', 'content')->keyBy('content_id'),
            'byPlaylist'   => $settings->where('certificate_type', 'playlist')->keyBy('trainingplan_id'),
            'eligibility'  => $eligibility,
            'certificates' => $certificates,
        ];
    }

    /**
     * Certificate yang SIAP (semua syarat terpenuhi) — section prioritas paling
     * atas Learning Record, diurutkan berdasarkan tanggal selesai terbaru.
     * Memakai konteks certificate yang sudah dimuat (tidak query ulang).
     *
     * @return \Illuminate\Support\Collection<int, array>
     */
    private function certificateReady(array $cert, Collection $completedPlaylists): Collection
    {
        $playlistFinishDates = $completedPlaylists->pluck('last_finished_at', 'id');

        return $cert['settings']
            ->filter(fn ($setting) => ($cert['eligibility'][$setting->id] ?? null)?->eligible ?? false)
            ->map(function ($setting) use ($cert, $playlistFinishDates) {
                $eligibility = $cert['eligibility'][$setting->id];

                // Tanggal selesai untuk sorting: content -> learning terakhir,
                // playlist -> content terakhir dalam playlist, global -> hasil assessment.
                $finishedAt = null;
                if ($setting->certificate_type === 'content') {
                    $finishedAt = $eligibility->learning?->updated_at;
                } elseif ($setting->certificate_type === 'playlist') {
                    $raw        = $playlistFinishDates->get($setting->trainingplan_id);
                    $finishedAt = $raw ? Carbon::parse($raw) : null;
                }
                $finishedAt = $finishedAt ?? $eligibility->assessmentResult?->created_at;

                return [
                    'setting'     => $setting,
                    'certificate' => $cert['certificates']->get($setting->id),
                    'finished_at' => $finishedAt,
                ];
            })
            ->sortByDesc(fn ($item) => optional($item['finished_at'])->timestamp ?? 0)
            ->values();
    }

    // ─── Assessment tab ─────────────────────────────────────────────────────────

    private function getUserAssessments($user, $userId)
    {
        return Assesment::with([
                'assesments_assigned',
                'contents',
                'scores'      => fn ($q) => $q->where('user_id', $userId),
                'trainingplan',
            ])
            ->where(function ($query) use ($user, $userId) {
                $query->whereHas('assesments_assigned', function ($q) use ($user) {
                    $q->where('assigned_id', $user->id_emp)
                        ->orWhere('assigned_id', $user->id_team ?? $user->id_tean)
                        ->orWhere('assigned_id', $user->id_department);
                })
                ->orWhereHas('contents', function ($q) use ($userId) {
                    $q->whereHas('contentuser', function ($q) use ($userId) {
                        $q->where('progress', '>=', 100)->where('user_id', $userId);
                    });
                })
                ->orWhereHas('scores', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                });
            })
            ->latest()
            ->get();
    }

    private function getUserAssessmentResults($userId)
    {
        return AssesmentResult::with('assesment.trainingplan')
            ->where('user_id', $userId)
            ->whereFinal(1)
            ->latest()
            ->get();
    }
}
