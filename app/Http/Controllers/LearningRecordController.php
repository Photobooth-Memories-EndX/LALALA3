<?php

namespace App\Http\Controllers;

use App\Exports\CertificateExport;
use App\Models\Certificate;
use App\Models\CertificateSetting;
use App\Models\ContentUser;
use App\Models\TrainingPlan;
use App\Services\CertificateEligibilityService;
use App\Services\Learning\CompletedPlaylistService;
use App\Services\Learning\LearningHourService;
use App\Support\DurationFormatter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

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
    public function __construct(
        private readonly LearningHourService $learningHourService,
        private readonly CompletedPlaylistService $completedPlaylistService,
    ) {
    }

    /**
     * HTML fragment tab "record" pada My Learning dashboard.
     * Dipanggil dari LearningController::dashboardPartial (routing only).
     */
    public function partial(CertificateEligibilityService $eligibilityService)
    {
        $userId = (int) Auth::id();

        $completedPlaylists = $this->completedPlaylistService->forUser($userId);
        $completedContents  = $this->getCompletedContents($userId);
        $cert               = $this->certificateContext($eligibilityService);

        // Seluruh baris (Content + Playlist) dinormalisasi lalu diurutkan
        // sebagai antrean prioritas: baris yang siap Generate Certificate
        // selalu paling atas, di dalam tiap kelompok Finish Date terbaru dulu.
        // Murni pengurutan Collection — query dan business logic tidak berubah.
        $allRows = $this->mergeLearningRecord(
            $this->buildContentRows($completedContents, $cert),
            $this->buildPlaylistRows(
                $completedPlaylists,
                $cert,
                // Slug content pertama tiap playlist — untuk link badge Playlist
                // (route playlist.user.view butuh pasangan slug playlist + content).
                $this->playlistFirstContentSlug($completedPlaylists),
                $completedContents
            )
        )->sortBy([
            ['cert_priority', 'asc'],
            ['finish_date', 'desc'],
        ])->values();
        $rows = $this->applyRecordFilters($allRows);

        return view('user.learning.dashboard-partials.record', [
            'rows'             => $rows,
            // Section prioritas paling atas: certificate yang sudah memenuhi syarat.
            'certificateReady' => $this->certificateReady($cert, $completedPlaylists),
            'filterOptions'    => $this->recordFilterOptions($userId, $completedPlaylists, $allRows),
        ]);
    }

    /**
     * Apply the GET-backed Learning Record filters before Blade renders rows.
     */
    private function applyRecordFilters(Collection $rows): Collection
    {
        $search = mb_strtolower(trim((string) request('record_search', '')));
        $type = (string) request('record_filter_type', 'all');
        $classification = (string) request('record_filter_classification', 'all');
        $playlist = (string) request('record_filter_playlist', 'all');
        $year = (string) request('record_filter_year', 'all');
        $month = (string) request('record_filter_month', 'all');
        $program = (string) request('record_filter_program', 'all');
        $learningHour = (string) request('record_filter_learning_hour', 'all');
        $duration = (string) request('record_filter_duration', 'all');
        $modalYear = (string) request('record_filter_modal_year', 'all');
        $score = (string) request('record_filter_score', 'all');
        $certificate = (string) request('record_filter_certificate', 'all');
        $completion = (string) request('record_filter_completion', 'all');
        $modalTypes = array_values(array_filter((array) request('record_filter_modal_type', []), fn ($value) => $value !== 'all'));
        $learningStatuses = array_values(array_filter((array) request('record_filter_learning_status', [])));
        $progressRanges = array_values(array_filter((array) request('record_filter_progress', [])));
        $modalMonths = array_map('strval', array_values(array_filter((array) request('record_filter_modal_month', []))));

        $filtered = $rows->filter(function (array $row) use (
            $search,
            $type,
            $classification,
            $playlist,
            $year,
            $month,
            $program,
            $learningHour,
            $duration,
            $modalYear,
            $score,
            $certificate,
            $completion,
            $modalTypes,
            $learningStatuses,
            $progressRanges,
            $modalMonths
        ) {
            $rowType = (string) ($row['type'] ?? '');
            $rowKind = (string) ($row['row_kind'] ?? '');
            $finish = ! empty($row['finish_date']) ? Carbon::parse($row['finish_date']) : null;
            $progress = (int) ($row['progress'] ?? 0);
            $rowStatus = $progress >= 100 ? 'completed' : ($progress <= 0 ? 'not_started' : 'ongoing');
            $subtitle = mb_strtolower((string) ($row['subtitle'] ?? ''));
            $rowProgram = str_contains($subtitle, 'mandatory')
                ? 'mandatory'
                : (str_contains($subtitle, 'optional') ? 'optional' : '');

            if ($search !== '' && ! str_contains(mb_strtolower(($row['title'] ?? '') . ' ' . ($row['classification'] ?? '')), $search)) {
                return false;
            }
            if (! $this->recordTypeMatches($rowType, $rowKind, $type)) {
                return false;
            }
            if ($modalTypes !== [] && ! collect($modalTypes)->contains(fn ($value) => $this->recordTypeMatches($rowType, $rowKind, (string) $value))) {
                return false;
            }
            if ($classification !== 'all' && (string) ($row['classification'] ?? '') !== $classification) {
                return false;
            }
            if ($playlist !== 'all' && ! in_array($playlist, array_map('strval', $row['playlist_ids'] ?? []), true)) {
                return false;
            }
            if ($year !== 'all' && (! $finish || (string) $finish->year !== $year)) {
                return false;
            }
            if ($modalYear !== 'all' && (! $finish || (string) $finish->year !== $modalYear)) {
                return false;
            }
            if ($month !== 'all' && (! $finish || (string) $finish->month !== $month)) {
                return false;
            }
            if ($modalMonths !== [] && (! $finish || ! in_array((string) $finish->month, $modalMonths, true))) {
                return false;
            }
            if ($program !== 'all' && $rowProgram !== $program) {
                return false;
            }
            if ($learningStatuses !== [] && ! in_array($rowStatus, $learningStatuses, true)) {
                return false;
            }
            if ($learningHour !== 'all' && ! $this->recordRangeMatches((float) ($row['learning_hour_value'] ?? -1), $learningHour)) {
                return false;
            }
            if ($duration !== 'all' && ! $this->recordRangeMatches((float) ($row['duration_sort'] ?? -1), $duration)) {
                return false;
            }
            if ($progressRanges !== [] && ! collect($progressRanges)->contains(fn ($range) => $this->recordRangeMatches($progress, (string) $range))) {
                return false;
            }
            if (! $this->recordCompletionMatches($progress, $completion)) {
                return false;
            }
            if (! $this->recordScoreMatches($row['score'] ?? null, $score)) {
                return false;
            }

            return $certificate === 'all' || (string) ($row['cert_state'] ?? 'none') === $certificate;
        });

        $sort = (string) request('record_sort', '');
        $direction = (string) request('record_sort_direction', 'asc');
        $sortKeys = [
            'title' => fn (array $row) => mb_strtolower((string) ($row['title'] ?? '')),
            'type' => fn (array $row) => mb_strtolower((string) ($row['type'] ?? '')),
            'classification' => fn (array $row) => mb_strtolower((string) ($row['classification'] ?? '')),
            'duration' => fn (array $row) => (float) ($row['duration_sort'] ?? -1),
            'learning-hour' => fn (array $row) => (float) ($row['learning_hour_bucket'] ?? -1),
            'progress' => fn (array $row) => (float) ($row['progress'] ?? -1),
            'finish' => fn (array $row) => (string) ($row['finish_date'] ?? ''),
            'score' => fn (array $row) => (float) ($row['score'] ?? -1),
        ];

        if (isset($sortKeys[$sort]) && in_array($direction, ['asc', 'desc'], true)) {
            $filtered = $direction === 'desc'
                ? $filtered->sortByDesc($sortKeys[$sort])
                : $filtered->sortBy($sortKeys[$sort]);
        }

        return $filtered->values();
    }

    private function recordTypeMatches(string $rowType, string $rowKind, string $filter): bool
    {
        return match ($filter) {
            'all' => true,
            'Playlist' => $rowKind === 'playlist',
            'Video' => in_array($rowType, ['Video', 'YouTube'], true),
            default => $rowType === $filter,
        };
    }

    private function recordRangeMatches(float $value, string $range): bool
    {
        if ($range === 'all') {
            return true;
        }
        if (str_starts_with($range, 'gt')) {
            return $value > (float) substr($range, 2);
        }
        if (! str_contains($range, '-')) {
            return $value === (float) $range;
        }

        [$minimum, $maximum] = array_map('floatval', explode('-', $range, 2));
        return $value >= $minimum && $value <= $maximum;
    }

    private function recordCompletionMatches(int $progress, string $completion): bool
    {
        return match ($completion) {
            'not_started' => $progress === 0,
            'in_progress' => $progress > 0 && $progress <= 75,
            'almost_done' => $progress >= 76 && $progress < 100,
            'completed' => $progress === 100,
            default => true,
        };
    }

    private function recordScoreMatches(mixed $score, string $filter): bool
    {
        if ($filter === 'all') {
            return true;
        }

        $hasScore = $score !== null && $score !== '';
        $value = $hasScore ? (float) $score : -1;

        return match ($filter) {
            'none' => ! $hasScore,
            'lt60' => $hasScore && $value < 60,
            '60-79' => $value >= 60 && $value <= 79,
            '80-100' => $value >= 80 && $value <= 100,
            default => true,
        };
    }

    /**
     * HTML fragment tab "certificate" pada My Learning dashboard.
     */
    public function certificate()
    {
        $query = $this->certificateListQuery((int) Auth::id());
        $query = $this->applyCertificateFilters($query);

        if (request()->boolean('certificate_export')) {
            return Excel::download(
                new CertificateExport($query->get()),
                'My_Certificates_' . now()->format('Y-m-d') . '.xlsx'
            );
        }

        $userCertificates = $query
            ->paginate(10, ['*'], 'certificate_page')
            ->withQueryString();

        $filterOptions = [
            'years' => Certificate::query()
                ->where('user_id', Auth::id())
                ->whereNotNull('issued_at')
                ->selectRaw('YEAR(issued_at) as year')
                ->distinct()
                ->orderByDesc('year')
                ->pluck('year'),
            'issuers' => CertificateSetting::query()
                ->whereIn('id', Certificate::query()
                    ->select('certificate_setting_id')
                    ->where('user_id', Auth::id()))
                ->whereNotNull('issuer_name')
                ->where('issuer_name', '!=', '')
                ->distinct()
                ->orderBy('issuer_name')
                ->pluck('issuer_name'),
        ];

        return view('user.learning.dashboard-partials.certificate', compact('userCertificates', 'filterOptions'));
    }

    private function certificateListQuery(int $userId): Builder
    {
        return Certificate::query()
            ->select([
                'id',
                'user_id',
                'certificate_setting_id',
                'assessment_result_id',
                'certificate_type',
                'source_id',
                'certificate_number',
                'credential_id',
                'credential_url',
                'title',
                'participant_name',
                'score',
                'issued_at',
                'created_at',
            ])
            ->with([
                'user:id,name',
                'certificateSetting:id,certificate_type,content_id,trainingplan_id,assessment_id,issuer_name,use_default_template,certificate_template,custom_background,background_image',
                'certificateSetting.content:id,title',
                'certificateSetting.assessment:id,title',
                'certificateSetting.trainingPlan:id,title',
                'certificateSetting.trainingPlan.trainingplandetail:id,trainingplan_id,content_id,content_order',
                'certificateSetting.trainingPlan.trainingplandetail.content:id,title',
            ])
            ->where('user_id', $userId);
    }

    private function applyCertificateFilters(Builder $query): Builder
    {
        $search = trim((string) request('certificate_search', ''));
        $type = (string) request('certificate_filter_type', 'all');
        $year = (string) request('certificate_filter_year', 'all');
        $month = (string) request('certificate_filter_month', 'all');
        $sort = (string) request('certificate_sort', 'issued_desc');

        // Note: Certificate Expiration Status filtering is soft-disabled per advisor decision.
        // Reserved for future development if Expiration requirement is reactivated.

        $query
            ->when($search !== '', function ($q) use ($search) {
                $keyword = '%' . $search . '%';
                $q->where(function ($filter) use ($keyword) {
                    $filter->where('certificate_number', 'like', $keyword)
                        ->orWhere('credential_id', 'like', $keyword)
                        ->orWhere('participant_name', 'like', $keyword)
                        ->orWhere('title', 'like', $keyword)
                        ->orWhere('certificate_type', 'like', $keyword);
                });
            })
            ->when(in_array($type, ['content', 'playlist', 'global'], true), fn ($q) => $q->where('certificate_type', $type))
            ->when($year !== 'all' && ctype_digit($year), fn ($q) => $q->whereYear('issued_at', (int) $year))
            ->when($month !== 'all' && ctype_digit($month), fn ($q) => $q->whereMonth('issued_at', (int) $month))
            ->when(request()->filled('certificate_number'), fn ($q) => $q->where('certificate_number', 'like', '%' . trim((string) request('certificate_number')) . '%'))
            ->when(request()->filled('certificate_credential'), fn ($q) => $q->where('credential_id', 'like', '%' . trim((string) request('certificate_credential')) . '%'))
            ->when(request()->filled('certificate_participant'), fn ($q) => $q->where('participant_name', 'like', '%' . trim((string) request('certificate_participant')) . '%'))
            ->when(request()->filled('certificate_target'), fn ($q) => $q->where('title', 'like', '%' . trim((string) request('certificate_target')) . '%'))
            ->when(request()->filled('certificate_issuer'), function ($q) {
                $issuer = trim((string) request('certificate_issuer'));
                $q->whereHas('certificateSetting', fn ($setting) => $setting->where('issuer_name', 'like', '%' . $issuer . '%'));
            })
            ->when(request()->filled('certificate_date_from'), fn ($q) => $q->whereDate('issued_at', '>=', request('certificate_date_from')))
            ->when(request()->filled('certificate_date_to'), fn ($q) => $q->whereDate('issued_at', '<=', request('certificate_date_to')));

        return match ($sort) {
            'issued_asc' => $query->orderBy('issued_at')->orderBy('id'),
            'id_asc' => $query->orderBy('id'),
            'id_desc' => $query->orderByDesc('id'),
            'type_asc' => $query->orderBy('certificate_type')->orderBy('id'),
            'type_desc' => $query->orderByDesc('certificate_type')->orderByDesc('id'),
            'number_asc' => $query->orderBy('certificate_number')->orderBy('id'),
            'number_desc' => $query->orderByDesc('certificate_number')->orderByDesc('id'),
            'participant_asc' => $query->orderBy('participant_name')->orderBy('id'),
            'participant_desc' => $query->orderByDesc('participant_name')->orderByDesc('id'),
            'target_asc' => $query->orderBy('title')->orderBy('id'),
            'target_desc' => $query->orderByDesc('title')->orderByDesc('id'),
            default => $query->orderByDesc('issued_at')->orderByDesc('id'),
        };
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
                'start_date'      => optional($cu->created_at)->format('Y-m-d H:i:s') ?? '',
                'finish_date'     => (int) $cu->finish === 1
                    ? (optional($cu->updated_at)->format('Y-m-d H:i:s') ?? '')
                    : '',
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
    private function buildPlaylistRows(
        Collection $playlists,
        array $cert,
        Collection $firstContentSlugs,
        Collection $contentUsers
    ): Collection
    {
        return $playlists->map(function (TrainingPlan $plan) use ($cert, $firstContentSlugs, $contentUsers) {
            $setting     = $cert['byPlaylist']->get($plan->id);
            $eligibility = $setting ? ($cert['eligibility'][$setting->id] ?? null) : null;
            $eligible    = $eligibility?->eligible ?? false;

            $startDate = $contentUsers
                ->filter(fn (ContentUser $cu) => $cu->content->trainingPlans->contains('id', $plan->id))
                ->pluck('created_at')
                ->filter()
                ->min();
            $finishDate = $plan->last_finished_at
                ? Carbon::parse($plan->last_finished_at)->format('Y-m-d H:i:s')
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
                'start_date'      => $startDate ? Carbon::parse($startDate)->format('Y-m-d H:i:s') : '',
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

}
