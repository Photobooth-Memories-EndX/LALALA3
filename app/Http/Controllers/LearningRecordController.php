<?php

namespace App\Http\Controllers;

use App\Exports\CertificateExport;
use App\Models\Certificate;
use App\Models\CertificateSetting;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\TrainingPlan;
use App\Services\CertificateEligibilityService;
use App\Services\Learning\CompletedPlaylistService;
use App\Services\Learning\LearningHourService;
use App\Services\Learning\LearningRecordLearningHourService;
use App\Services\Record\RecordOfflineQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        private readonly ?LearningRecordLearningHourService $recordLearningHourService = null,
    ) {
    }

    private function recordLhService(): LearningRecordLearningHourService
    {
        return $this->recordLearningHourService ?? app(LearningRecordLearningHourService::class);
    }

    /**
     * HTML fragment tab "record" pada My Learning dashboard.
     * Dipanggil dari LearningController::dashboardPartial (routing only).
     */
    public function partial(CertificateEligibilityService $eligibilityService)
    {
        $userId = (int) Auth::id();

        $completedPlaylists = $this->completedPlaylistService->forLearningRecord($userId);
        $cert               = $this->certificateContext($eligibilityService);
        $allRows = $this->getRawOnlineRows($userId, $eligibilityService, $completedPlaylists, $cert);
        $rows = $this->applyRecordFilters($allRows);

        $recordMode = (string) request('record_mode', 'online');
        $recordStats = $this->calculateHeroStats($recordMode, $userId, Auth::user()?->id_emp, $eligibilityService, $rows);
        $legacyStats = [
            'courseCompleted'    => $recordStats['courseCompleted'] ?? 0,
            'playlistCompleted'  => $recordStats['playlistCompleted'] ?? 0,
            'totalLearningHours' => $recordStats['totalLearningHours'] ?? 0,
        ];

        return view('user.learning.dashboard-partials.record', [
            'rows'             => $rows,
            // Section prioritas paling atas: certificate yang sudah memenuhi syarat.
            'certificateReady' => $this->certificateReady($cert, $completedPlaylists),
            'filterOptions'    => $this->recordFilterOptions($userId, $completedPlaylists, $allRows),
            'recordMode'       => $recordStats['record_mode'] ?? 'online',
            'recordStats'      => array_merge($legacyStats, $recordStats),
            'stats'            => array_merge($legacyStats, $recordStats),
        ]);
    }

    /**
     * Calculate three distinct sets of hero card metrics depending on the active record_mode parameter:
     *  - 'online' / 'owl' (KP OWL): total_hours, content_completed, playlist_completed
     *  - 'offline' / 'non_owl' (Non-KP OWL): total_hours, internal_count, external_count
     *  - 'all' (All Records): total_hours, owl_completed, non_owl_completed
     */
    public function calculateHeroStats(
        string $recordMode = 'online',
        ?int $userId = null,
        ?string $userEmpId = null,
        ?CertificateEligibilityService $eligibilityService = null,
        ?Collection $onlineRows = null,
        ?Collection $offlineRows = null
    ): array {
        $userId = $userId ?? (int) Auth::id();
        if ($userEmpId === null) {
            $userEmpId = Auth::id() === $userId
                ? Auth::user()?->id_emp
                : \App\Models\User::where('id', $userId)->value('id_emp');
        }

        $normalizedMode = match (strtolower(trim($recordMode))) {
            'offline', 'non_owl', 'non-owl' => 'offline',
            'all'                           => 'all',
            default                         => 'online',
        };

        if ($normalizedMode === 'offline') {
            $offlineService = app(\App\Services\Learning\UserRecordOfflineService::class);
            $rawOffline = $offlineService->queryForUser($userId, $userEmpId)
                ->get()
                ->filter(fn (\App\Models\OfflineTrainingUser $otu) => $otu->training !== null);

            $totalHours = round((float) $rawOffline->sum(function (\App\Models\OfflineTrainingUser $otu) {
                return (float) ($otu->learning_hours ?? $otu->training?->learning_hours ?? 0);
            }), 2);

            $internalCount = $rawOffline->filter(function (\App\Models\OfflineTrainingUser $otu) {
                $provider = strtolower(trim((string) ($otu->training?->provider ?? '')));
                return $provider === 'internal' || str_starts_with($provider, 'internal');
            })->count();

            $externalCount = $rawOffline->filter(function (\App\Models\OfflineTrainingUser $otu) {
                $provider = strtolower(trim((string) ($otu->training?->provider ?? '')));
                return $provider === 'external' || str_starts_with($provider, 'external');
            })->count();

            return [
                'record_mode'        => 'offline',
                'total_hours'        => $totalHours,
                'internal_count'     => $internalCount,
                'external_count'     => $externalCount,
                'totalLearningHours' => $totalHours,
                'courseCompleted'    => $rawOffline->count(),
                'playlistCompleted'  => 0,
            ];
        }

        if ($normalizedMode === 'all') {
            $offlineService = app(\App\Services\Learning\UserRecordOfflineService::class);
            $rawOffline = $offlineService->queryForUser($userId, $userEmpId)
                ->get()
                ->filter(fn (\App\Models\OfflineTrainingUser $otu) => $otu->training !== null);

            $offlineHours = (float) $rawOffline->sum(function (\App\Models\OfflineTrainingUser $otu) {
                return (float) ($otu->learning_hours ?? $otu->training?->learning_hours ?? 0);
            });
            $onlineHours = $this->recordLhService()->getTotalLearningHours($userId);
            $totalHours = round($onlineHours + $offlineHours, 2);

            $owlCompleted = $this->getCompletedContents($userId)->count();
            $nonOwlCompleted = $rawOffline->count();
            $playlistCompleted = $this->completedPlaylistService->forLearningRecord($userId)->count();

            return [
                'record_mode'        => 'all',
                'total_hours'        => $totalHours,
                'owl_completed'      => $owlCompleted,
                'non_owl_completed'  => $nonOwlCompleted,
                'totalLearningHours' => $totalHours,
                'courseCompleted'    => $owlCompleted + $nonOwlCompleted,
                'playlistCompleted'  => $playlistCompleted,
            ];
        }

        // Default mode: 'online' / 'owl' (KP OWL)
        if ($onlineRows !== null) {
            $contentCompleted = $onlineRows->where('row_kind', 'content')->count();
            $playlistCompleted = $onlineRows->where('row_kind', 'playlist')->count();
            $totalHours = round((float) $onlineRows->sum(fn (array $r) => (float) ($r['learning_hour_value'] ?? 0)), 2);
        } else {
            $contentCompleted = $this->getCompletedContents($userId)->count();
            $playlistCompleted = $this->completedPlaylistService->forLearningRecord($userId)->count();
            $totalHours = $this->recordLhService()->getTotalLearningHours($userId);
        }

        return [
            'record_mode'        => 'online',
            'total_hours'        => $totalHours,
            'content_completed'  => $contentCompleted,
            'playlist_completed' => $playlistCompleted,
            'totalLearningHours' => $totalHours,
            'courseCompleted'    => $contentCompleted,
            'playlistCompleted'  => $playlistCompleted,
        ];

    }

    /**
     * Get raw normalized Online rows (Content + Playlist) for a user before applying request filters.
     * Reusable across LearningRecordController and UserRecordAllService.
     */
    public function getRawOnlineRows(int $userId, CertificateEligibilityService $eligibilityService, ?Collection $completedPlaylists = null, ?array $cert = null): Collection
    {
        $completedPlaylists = $completedPlaylists ?? $this->completedPlaylistService->forLearningRecord($userId);
        $completedContents  = $this->getCompletedContents($userId);
        $cert               = $cert ?? $this->certificateContext($eligibilityService);
        $playlistContents   = $this->playlistContentsData($completedPlaylists);
        $firstContentSlugs  = $playlistContents->map(fn ($items) => $items->first()?->slug);
        $playlistStartDates = \App\Models\PlaylistContentUser::where('user_id', $userId)
            ->whereIn('trainingplan_id', $completedPlaylists->pluck('id'))
            ->groupBy('trainingplan_id')
            ->selectRaw('trainingplan_id, MIN(created_at) as min_created')
            ->pluck('min_created', 'trainingplan_id');
        $playlistActualHours = $this->recordLhService()->calculatePlaylistsBatch($userId, $completedPlaylists);

        return $this->mergeLearningRecord(
            $this->buildContentRows($completedContents, $cert),
            $this->buildPlaylistRows(
                $completedPlaylists,
                $cert,
                $firstContentSlugs,
                $completedContents,
                $playlistContents,
                $playlistStartDates,
                $playlistActualHours
            )
        )->sortByDesc(fn (array $row) => (string) ($row['finish_date'] ?? ''))->values();
    }

    /**
     * Apply the GET-backed Learning Record filters before Blade renders rows.
     * Can also be called programmatically (e.g., from ExportMyLearningRecord) with custom $filters.
     */
    public function applyRecordFilters(Collection $rows, array $filters = []): Collection
    {
        $search = mb_strtolower(trim((string) ($filters['record_search'] ?? $filters['search'] ?? $filters['q'] ?? request('record_search') ?? request('search') ?? request('q') ?? '')));
        $type = (string) ($filters['record_filter_type'] ?? $filters['type'] ?? request('record_filter_type') ?? request('type') ?? 'all');
        $rawClassification = (string) ($filters['record_filter_classification'] ?? $filters['classification'] ?? request('record_filter_classification') ?? request('classification') ?? 'all');
        $classification = $rawClassification !== 'all' ? trim(html_entity_decode($rawClassification, ENT_QUOTES | ENT_HTML5, 'UTF-8')) : 'all';
        $playlist = (string) ($filters['record_filter_playlist'] ?? $filters['playlist'] ?? request('record_filter_playlist') ?? request('playlist') ?? 'all');
        $year = (string) ($filters['record_filter_year'] ?? $filters['year'] ?? request('record_filter_year') ?? request('year') ?? 'all');
        $rawMonth = (string) ($filters['record_filter_month'] ?? $filters['month'] ?? request('record_filter_month') ?? request('month') ?? 'all');
        $month = \App\Support\LearningFilterHelper::resolveMonth($rawMonth);
        $program = (string) ($filters['record_filter_program'] ?? $filters['program'] ?? request('record_filter_program') ?? request('program') ?? 'all');
        $learningHour = (string) ($filters['record_filter_learning_hour'] ?? $filters['learningHour'] ?? request('record_filter_learning_hour') ?? request('learningHour') ?? 'all');
        $duration = (string) ($filters['record_filter_duration'] ?? $filters['duration'] ?? request('record_filter_duration') ?? request('duration') ?? 'all');
        $modalYear = (string) ($filters['record_filter_modal_year'] ?? $filters['modalYear'] ?? request('record_filter_modal_year') ?? request('modalYear') ?? 'all');
        $score = (string) ($filters['record_filter_score'] ?? $filters['scoreTest'] ?? request('record_filter_score') ?? request('scoreTest') ?? 'all');
        $certificate = (string) ($filters['record_filter_certificate'] ?? $filters['certificate'] ?? request('record_filter_certificate') ?? request('certificate') ?? 'all');
        $completion = (string) ($filters['record_filter_completion'] ?? $filters['completion'] ?? request('record_filter_completion') ?? request('completion') ?? 'all');
        $modalTypes = array_values(array_filter((array) ($filters['record_filter_modal_type'] ?? $filters['modalTypes'] ?? request('record_filter_modal_type') ?? request('modalTypes') ?? []), fn ($value) => $value !== 'all'));
        $learningStatuses = array_values(array_filter((array) ($filters['record_filter_learning_status'] ?? $filters['learningStatus'] ?? request('record_filter_learning_status') ?? request('learningStatus') ?? [])));
        $progressRanges = array_values(array_filter((array) ($filters['record_filter_progress'] ?? $filters['progressRanges'] ?? request('record_filter_progress') ?? request('progressRanges') ?? [])));
        $modalMonths = array_map('strval', array_values(array_filter((array) ($filters['record_filter_modal_month'] ?? $filters['modalMonths'] ?? request('record_filter_modal_month') ?? request('modalMonths') ?? []))));
        $quickFilter = (string) ($filters['record_quick'] ?? $filters['quickFilter'] ?? $filters['ongoing_mode'] ?? request('record_quick') ?? request('quickFilter') ?? request('ongoing_mode') ?? 'all');

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
            $modalMonths,
            $quickFilter
        ) {
            $rowType = (string) ($row['type'] ?? '');
            $rowKind = (string) ($row['row_kind'] ?? '');

            if ($quickFilter === 'content' && $rowKind === 'playlist') {
                return false;
            }
            if ($quickFilter === 'playlist' && $rowKind !== 'playlist') {
                return false;
            }
            $finish = ! empty($row['finish_date']) ? Carbon::parse($row['finish_date']) : null;
            $progress = (int) ($row['progress'] ?? 0);
            $hasScore = ($row['assessment_required'] ?? false) && ! is_null($row['score'] ?? null);
            $scorePassed = $hasScore && ($row['score'] >= ($row['min_score'] ?? 0));
            $rowStatuses = [];
            if ($progress >= 100) {
                $rowStatuses[] = 'completed';
            }
            if ($hasScore) {
                $rowStatuses[] = $scorePassed ? 'passed' : 'failed';
            }
            if ($progress <= 0) {
                $rowStatuses[] = 'not_started';
            } elseif ($progress < 100) {
                $rowStatuses[] = 'ongoing';
            }
            $reqText = mb_strtolower((string) ($row['requirement'] ?? $row['subtitle'] ?? ''));
            $isNonMandatory = str_contains($reqText, 'non-mandatory') || str_contains($reqText, 'non mandatory') || str_contains($reqText, 'optional');
            $rowProgram = $isNonMandatory
                ? 'optional'
                : (str_contains($reqText, 'mandatory') ? 'mandatory' : '');

            if ($search !== '' && ! str_contains(mb_strtolower(($row['title'] ?? '') . ' ' . ($row['classification'] ?? '')), $search)) {
                return false;
            }
            if (! $this->recordTypeMatches($rowType, $rowKind, $type)) {
                return false;
            }
            if ($modalTypes !== [] && ! collect($modalTypes)->contains(fn ($value) => $this->recordTypeMatches($rowType, $rowKind, (string) $value))) {
                return false;
            }
            if ($classification !== 'all' && strcasecmp((string) ($row['classification'] ?? ''), $classification) !== 0) {
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
            if ($month !== null && (! $finish || (int) $finish->month !== $month)) {
                return false;
            }
            if ($modalMonths !== []) {
                $modalMonthNums = array_filter(array_map([\App\Support\LearningFilterHelper::class, 'resolveMonth'], $modalMonths));
                if (! $finish || ! in_array((int) $finish->month, $modalMonthNums, true)) {
                    return false;
                }
            }
            $normalizedProgram = in_array(mb_strtolower($program), ['non-mandatory', 'non mandatory', 'optional'], true)
                ? 'optional'
                : mb_strtolower($program);
            if ($normalizedProgram !== 'all' && $rowProgram !== $normalizedProgram) {
                return false;
            }
            if ($learningStatuses !== [] && empty(array_intersect($learningStatuses, $rowStatuses))) {
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

        $sort = (string) ($filters['record_sort'] ?? $filters['sort'] ?? request('record_sort', ''));
        $direction = (string) ($filters['record_sort_direction'] ?? $filters['direction'] ?? request('record_sort_direction', 'asc'));
        $activitySort = (string) ($filters['record_filter_activity_sort'] ?? $filters['activitySort'] ?? request('record_filter_activity_sort') ?? request('activitySort') ?? 'all');
        $sortKeys = [
            'title'          => fn (array $row) => mb_strtolower((string) ($row['title'] ?? '')),
            'category'       => fn (array $row) => mb_strtolower((string) ($row['category'] ?? '')),
            'type'           => fn (array $row) => mb_strtolower((string) ($row['type'] ?? '')),
            'classification' => fn (array $row) => mb_strtolower((string) ($row['classification'] ?? '')),
            'duration'       => fn (array $row) => (float) ($row['duration_sort'] ?? -1),
            'learning-hour'  => fn (array $row) => (float) ($row['learning_hour_bucket'] ?? -1),
            'progress'       => fn (array $row) => (float) ($row['progress'] ?? -1),
            'finish'         => fn (array $row) => (string) ($row['finish_date'] ?? ''),
            'attendance'     => fn (array $row) => mb_strtolower((string) ($row['attendance'] ?? '')),
            'score'          => fn (array $row) => (float) ($row['score'] ?? -1),
            'certificate'    => fn (array $row) => mb_strtolower((string) ($row['cert_state'] ?? 'none')),
        ];

        if (isset($sortKeys[$sort]) && in_array($direction, ['asc', 'desc'], true)) {
            $filtered = $direction === 'desc'
                ? $filtered->sortByDesc($sortKeys[$sort])
                : $filtered->sortBy($sortKeys[$sort]);
        } elseif ($activitySort === 'oldest') {
            $filtered = $filtered->sortBy(fn (array $row) => (string) ($row['finish_date'] ?? ''));
        } elseif ($activitySort === 'newest') {
            $filtered = $filtered->sortByDesc(fn (array $row) => (string) ($row['finish_date'] ?? ''));
        } elseif ($activitySort === 'ascending') {
            $filtered = $filtered->sortBy(fn (array $row) => mb_strtolower((string) ($row['title'] ?? '')));
        } elseif ($activitySort === 'descending') {
            $filtered = $filtered->sortByDesc(fn (array $row) => mb_strtolower((string) ($row['title'] ?? '')));
        }

        return $filtered->values();
    }

    private function recordTypeMatches(string $rowType, string $rowKind, string $filter): bool
    {
        $normalized = strtolower(trim($filter));
        if ($normalized === 'all' || $normalized === '') {
            return true;
        }
        if ($normalized === 'playlist' || $normalized === 'trainingplan') {
            return $rowKind === 'playlist';
        }
        if ($normalized === 'content') {
            return $rowKind === 'content';
        }
        if ($normalized === 'video') {
            return in_array($rowType, ['Video', 'YouTube'], true);
        }
        if ($normalized === 'document') {
            return strtolower($rowType) === 'document';
        }
        if ($normalized === 'mooc') {
            return in_array(strtolower($rowType), ['mooc', 'youtube'], true);
        }

        return strtolower($rowType) === $normalized;
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

        $allFilteredCertificates = (clone $query)
            ->without(['user', 'certificateSetting'])
            ->get(['certificate_type']);
        $certificateStats = [
            'total' => $allFilteredCertificates->count(),
            'playlist' => $allFilteredCertificates->where('certificate_type', 'playlist')->count(),
            'content' => $allFilteredCertificates->where('certificate_type', 'content')->count(),
        ];

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

        return view('user.learning.dashboard-partials.certificate', compact('userCertificates', 'filterOptions', 'certificateStats'));
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
        $search = trim((string) (request('certificate_search') ?? request('search') ?? request('q') ?? ''));
        $rawType = (string) (request('certificate_filter_type') ?? request('type') ?? 'all');
        $type = strtolower(trim($rawType));
        $year = (string) (request('certificate_filter_year') ?? request('year') ?? 'all');
        $rawMonth = (string) (request('certificate_filter_month') ?? request('month') ?? 'all');
        $month = \App\Support\LearningFilterHelper::resolveMonth($rawMonth);
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
            ->when($month !== null, fn ($q) => $q->whereMonth('issued_at', $month))
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
            ->selectRaw('DISTINCT YEAR(COALESCE(content_users.completed_at, content_users.updated_at)) as y')
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
            'learningHours'   => $this->recordLhService()->filterOptions($rows->pluck('learning_hour_bucket')),
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
                'content' => fn ($q) => $q->withTrashed(),
                'content.classification',
                // Playlist pemilik content — untuk badge di kolom Class (bebas N+1).
                // Catatan: relasi assesment/scores TIDAK dimuat lagi — score kini
                // diambil dari CertificateEligibilityResult (satu source of truth).
                'content.trainingPlans' => fn ($q) => $q->withTrashed()->select('trainingplan.id', 'trainingplan.title'),
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
            $actualHours = $this->recordLhService()->calculateContent($cu);
            $formattedHours = $this->recordLhService()->formatHours($actualHours);

            $isInactive = ($content->active !== 'Active') || !is_null($content->deleted_at);
            $isExpired  = !$isInactive && $content->isExpired();

            if ($isInactive) {
                $accessStatus = 'INACTIVE';
                $canOpen = false;
                $modalMessage = 'This content is currently inactive.';
            } elseif ($isExpired) {
                $accessStatus = 'EXPIRED';
                $canOpen = false;
                $modalMessage = 'This content period has expired.';
            } else {
                $accessStatus = 'ACTIVE';
                $canOpen = true;
                $modalMessage = '';
            }

            $rawProgress = $cu->progress ?? 0;
            $hasValidProgress = !is_null($rawProgress) && is_numeric($rawProgress);
            $contentProgress = $hasValidProgress ? (int) $rawProgress : 0;
            $attendanceStatus = ($hasValidProgress && (int) $rawProgress >= 100) ? 'Yes' : 'No';
            $status = strtolower(trim((string) ($cu->content->status ?? ($cu->status ?? 'non mandatory'))));
            $requirement = ($status === 'mandatory') ? 'Mandatory' : 'Non-Mandatory';
            $scoreVal = $eligibility?->score();
            $startDatetimeStr = optional($cu->created_at)->format('Y-m-d H:i:s') ?? '';
            $finishDatetimeStr = (int) ($cu->finish ?? 0) === 1
                ? (optional($cu->completed_at ?? $cu->updated_at)->format('Y-m-d H:i:s') ?? '')
                : '';

            return [
                'id'              => $cu->id,
                'row_kind'        => 'content',
                'category'        => 'Content',
                'title'           => optional($content)->title ?? '-',
                'slug'            => optional($content)->slug,
                'playlist_url'    => null,
                'subtitle'        => $requirement,
                'requirement'     => $requirement,
                'playlist_names'  => $playlists->pluck('title')->filter()->values()->all(),
                // Id playlist pemilik content — dipakai filter Playlist client-side.
                'playlist_ids'    => $playlists->pluck('id')->values()->all(),
                'type'            => \App\Support\ContentTypeHelper::label(optional($content)->type) ?: 'Content',
                'classification'  => optional(optional($content)->classification)->classification_name ?? '-',
                'duration'        => DurationFormatter::formatForLearningRecord(optional($content)->duration),
                'duration_sort'   => DurationFormatter::toSeconds(optional($content)->duration) ?? -1,
                'learning_hour'   => $formattedHours,
                'learning_hour_bucket' => $actualHours,
                'learning_hour_value'  => $actualHours,
                'progress'        => $contentProgress,
                'attendance'      => $attendanceStatus,
                'attendance_status' => $attendanceStatus,
                'min_progress'    => $setting ? (int) ($setting->minimum_progress ?? 0) : 0,
                'start_date'      => $startDatetimeStr,
                'finish_date'     => $finishDatetimeStr,
                'start_datetime'  => $startDatetimeStr,
                'end_datetime'    => $finishDatetimeStr,
                // Score & ambangnya dari CertificateEligibilityResult — angka
                // yang sama dengan yang dipakai menentukan tombol Generate.
                'score'                => $scoreVal,
                'assessment_score'     => $scoreVal,
                'min_score'            => (int) ($eligibility?->minimumScore ?? 0),
                'assessment_required'  => (bool) ($eligibility?->assessmentRequired ?? false),
                'cert_state'      => $certState,
                'cert_priority'   => $certPriority,
                'cert_action_url' => route('certificate.generate', $cu->id),
                'cert_is_form'    => false,
                'playlist_items'  => [],
                'item_id'         => 'content_' . (optional($content)->id ?? $cu->content_id),
                'access_status'   => $accessStatus,
                'can_open'        => $canOpen,
                'modal_message'   => $modalMessage,
                'employee_id'     => Auth::user()?->employee_id ?? Auth::user()?->nip ?? Auth::user()?->id_emp ?? '-',
                'participant_name'=> Auth::user()?->name ?? '-',
                'name'            => Auth::user()?->name ?? '-',
                'grade'           => Auth::user()?->grade ?? '-',
                'job_position'    => Auth::user()?->job_position ?? Auth::user()?->position ?? '-',
                'team'            => Auth::user()?->team ?? '-',
                'department'      => Auth::user()?->department ?? '-',
                'company'         => Auth::user()?->company ?? Auth::user()?->company_name ?? 'PT Krakatau Posco',
                'attendance_reason' => '-',
                'bond_status'     => 'No',
                'bond_duration'   => null,
                'bond_end_date'   => '-',
                'bond_amount'     => '-',
                'bond_amount_idr' => '-',
                'bond_amount_usd' => '-',
                'bond_amount_formatted' => '-',
                'pic'             => '-',
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
            ->orderByRaw('CAST(tpd.content_order AS UNSIGNED) ASC')
            ->get(['tpd.trainingplan_id as plan_id', 'c.slug as slug'])
            ->groupBy('plan_id')
            ->map(fn ($items) => $items->first()?->slug);
    }

    /**
     * Seluruh content tiap playlist terurut berdasarkan content_order — satu batch query
     * untuk seluruh playlist selesai (0 N+1).
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection>
     */
    private function playlistContentsData(Collection $playlists): Collection
    {
        $planIds = $playlists->pluck('id')->all();

        if (empty($planIds)) {
            return collect();
        }

        return DB::table('trainingplandetail as tpd')
            ->join('content as c', 'c.id', '=', 'tpd.content_id')
            ->whereIn('tpd.trainingplan_id', $planIds)
            ->orderByRaw('CAST(tpd.content_order AS UNSIGNED) ASC')
            ->get([
                'tpd.trainingplan_id as plan_id',
                'c.id as content_id',
                'c.title as title',
                'c.slug as slug',
                'c.type as type',
                'c.status as status',
                'c.active as active',
                'c.has_deadline as has_deadline',
                'c.from_date as from_date',
                'c.to_date as to_date',
                'c.deleted_at as deleted_at',
                'tpd.content_order as content_order',
            ])
            ->groupBy('plan_id');
    }

    /**
     * Normalisasi Playlist selesai menjadi baris tabel dengan struktur kolom
     * yang sama seperti baris Content.
     */
    private function buildPlaylistRows(
        Collection $playlists,
        array $cert,
        Collection $firstContentSlugs,
        Collection $contentUsers,
        ?Collection $allPlaylistContents = null,
        ?Collection $playlistStartDates = null,
        ?Collection $playlistActualHours = null
    ): Collection
    {
        $allPlaylistContents = $allPlaylistContents ?? $this->playlistContentsData($playlists);

        return $playlists->map(function (TrainingPlan $plan) use ($cert, $firstContentSlugs, $contentUsers, $allPlaylistContents, $playlistStartDates, $playlistActualHours) {
            $setting     = $cert['byPlaylist']->get($plan->id);
            $eligibility = $setting ? ($cert['eligibility'][$setting->id] ?? null) : null;
            $eligible    = $eligibility?->eligible ?? false;

            $startDate = $playlistStartDates?->get($plan->id)
                ?? $contentUsers
                    ->filter(fn (ContentUser $cu) => $cu->content && $cu->content->trainingPlans->contains('id', $plan->id))
                    ->pluck('created_at')
                    ->filter()
                    ->min()
                ?? $plan->created_at;
            $finishDate = $plan->last_finished_at
                ? Carbon::parse($plan->last_finished_at)->format('Y-m-d H:i:s')
                : '';

            $isPlanInactive = ($plan->active !== 'Active') || !is_null($plan->deleted_at);
            $isPlanExpired  = !$isPlanInactive && $plan->isExpired();

            if ($isPlanInactive) {
                $accessStatus = 'INACTIVE';
                $canOpen = false;
                $modalMessage = 'This playlist is currently inactive.';
            } elseif ($isPlanExpired) {
                $accessStatus = 'EXPIRED';
                $canOpen = false;
                $modalMessage = 'This playlist period has expired.';
            } else {
                $accessStatus = 'ACTIVE';
                $canOpen = true;
                $modalMessage = '';
            }

            $planContents = $allPlaylistContents->get($plan->id, collect());
            $firstSlug = $firstContentSlugs->get($plan->id) ?? $planContents->first()?->slug;

            $playlistItems = $planContents->map(function ($item) use ($plan, $isPlanInactive, $isPlanExpired) {
                $itemInactive = ($item->active ?? 'Active') !== 'Active' || !is_null($item->deleted_at ?? null);
                $itemHasDeadline = (bool) ($item->has_deadline ?? false);
                $itemExpired = $itemHasDeadline && !empty($item->to_date) && now()->toDateString() > Carbon::parse($item->to_date)->format('Y-m-d');

                $itemAccessStatus = ($isPlanInactive || $itemInactive) ? 'INACTIVE' : (($isPlanExpired || $itemExpired) ? 'EXPIRED' : 'ACTIVE');
                $itemCanOpen = $itemAccessStatus === 'ACTIVE';
                $itemModalMessage = $itemAccessStatus === 'INACTIVE' ? 'This content is currently inactive.' : ($itemAccessStatus === 'EXPIRED' ? 'This content period has expired.' : '');

                $status = strtolower(trim((string) ($item->status ?? 'non mandatory')));
                $itemRequirement = ($status === 'mandatory') ? 'Mandatory' : 'Non-Mandatory';

                return [
                    'content_id'    => $item->content_id ?? null,
                    'title'         => $item->title ?? '-',
                    'slug'          => $item->slug ?? null,
                    'type'          => \App\Support\ContentTypeHelper::label($item->type ?? null) ?: 'Content',
                    'requirement'   => $itemRequirement,
                    'url'           => ($plan->slug && ($item->slug ?? null))
                        ? route('playlist.user.view', [$plan->slug, $item->slug])
                        : null,
                    'access_status' => $itemAccessStatus,
                    'can_open'      => $itemCanOpen,
                    'modal_message' => $itemModalMessage,
                ];
            })->values()->all();

            $durationSeconds = (int) ($plan->total_duration_seconds ?? 0);
            $actualHours = $playlistActualHours?->get($plan->id)
                ?? (float) $this->recordLhService()->calculatePlaylistSingle((int) Auth::id(), $plan);
            $formattedHours = $this->recordLhService()->formatHours($actualHours);
            $playlistRequirement = collect($playlistItems)->contains(fn ($it) => ($it['requirement'] ?? '') === 'Mandatory')
                ? 'Mandatory'
                : 'Non-Mandatory';
            $playlistScore = $eligibility?->score();

            return [
                'id'              => $plan->id,
                'row_kind'        => 'playlist',
                'category'        => 'Playlist',
                'title'           => $plan->title ?? '-',
                'slug'            => null,
                // Link badge Playlist di kolom Type (route lama, tanpa route baru).
                'playlist_url'    => ($plan->slug && $firstSlug)
                    ? route('playlist.user.view', [$plan->slug, $firstSlug])
                    : null,
                'playlist_items'  => $playlistItems,
                'subtitle'        => (int) ($plan->finished_items ?? 0) . ' / ' . (int) ($plan->total_items ?? count($playlistItems)) . ' content selesai',
                'requirement'     => $playlistRequirement,
                'playlist_names'  => [],
                'playlist_ids'    => [$plan->id],
                'plan_id'         => $plan->id,
                'playlist_id'     => $plan->id,
                'item_id'         => 'playlist_' . $plan->id,
                'type'            => 'Playlist',
                'classification'  => '-',
                'duration'        => $this->formatDurationSeconds($durationSeconds),
                'duration_sort'   => $durationSeconds,
                'learning_hour'   => $formattedHours,
                'learning_hour_bucket' => $actualHours,
                'learning_hour_value'  => $actualHours,
                'progress'        => 100,
                'attendance'      => 'Yes',
                'attendance_status' => 'Yes',
                'min_progress'    => $setting ? (int) ($setting->minimum_progress ?? 0) : 0,
                'start_date'      => $startDate ? Carbon::parse($startDate)->format('Y-m-d H:i:s') : '',
                'finish_date'     => $finishDate,
                'start_datetime'  => $startDate ? Carbon::parse($startDate)->format('Y-m-d H:i:s') : '',
                'end_datetime'    => $finishDate,
                // Pola sama dengan Content: score dari CertificateEligibilityResult —
                // playlist ber-assessment kini menampilkan nilai sungguhan.
                'score'                => $playlistScore,
                'assessment_score'     => $playlistScore,
                'min_score'            => (int) ($eligibility?->minimumScore ?? 0),
                'assessment_required'  => (bool) ($eligibility?->assessmentRequired ?? false),
                // Aturan playlist sudah di CertificateEligibilityService:
                // tanpa assessment cukup progress; dengan assessment wajib
                // lulus minimum score.
                'cert_state'      => $eligible ? 'ready' : 'none',
                'cert_priority'   => $eligible ? 1 : 2,
                'cert_action_url' => $setting ? route('certificate.generate.setting', $setting) : null,
                'cert_is_form'    => true,
                'access_status'   => $accessStatus,
                'can_open'        => $canOpen,
                'modal_message'   => $modalMessage,
                'employee_id'     => Auth::user()?->employee_id ?? Auth::user()?->nip ?? Auth::user()?->id_emp ?? '-',
                'participant_name'=> Auth::user()?->name ?? '-',
                'name'            => Auth::user()?->name ?? '-',
                'grade'           => Auth::user()?->grade ?? '-',
                'job_position'    => Auth::user()?->job_position ?? Auth::user()?->position ?? '-',
                'team'            => Auth::user()?->team ?? '-',
                'department'      => Auth::user()?->department ?? '-',
                'company'         => Auth::user()?->company ?? Auth::user()?->company_name ?? 'PT Krakatau Posco',
                'attendance_reason' => '-',
                'bond_status'     => 'No',
                'bond_duration'   => null,
                'bond_end_date'   => '-',
                'bond_amount'     => '-',
                'bond_amount_idr' => '-',
                'bond_amount_usd' => '-',
                'bond_amount_formatted' => '-',
                'pic'             => '-',
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
                // Playlist: user punya aktivitas pada minimal satu content playlist (PCU atau CU).
                ->orWhere(function ($w) use ($userId) {
                    $w->where('certificate_type', 'playlist')
                        ->where(function ($pw) use ($userId) {
                            $pw->whereExists(function ($s) use ($userId) {
                                $s->from('playlist_content_users')
                                    ->whereColumn('playlist_content_users.trainingplan_id', 'certificate_settings.trainingplan_id')
                                    ->where('playlist_content_users.user_id', $userId);
                            })
                            ->orWhereExists(function ($s) use ($userId) {
                                $s->from('trainingplandetail')
                                    ->join('content_users', 'content_users.content_id', '=', 'trainingplandetail.content_id')
                                    ->whereColumn('trainingplandetail.trainingplan_id', 'certificate_settings.trainingplan_id')
                                    ->where('content_users.user_id', $userId);
                            });
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
                    $finishedAt = $eligibility->learning?->completed_at ?? $eligibility->learning?->updated_at;
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


    /**
     * Get detail of a specific learning record (Online or Offline).
     */
    public function detail(Request $request, string $source, $id): JsonResponse
    {
        $userId = (int) Auth::id();

        if (in_array(strtolower(trim($source)), ['offline', 'non-kp-owl', 'non_kp_owl'], true)) {
            $record = RecordOfflineQueryService::getRecordDetail($id, $userId);

            if (!$record) {
                return response()->json([
                    'success' => false,
                    'message' => 'Offline training record not found.',
                ], 404);
            }

            $rawCert = strtolower(trim((string) ($record->certificate ?? '')));
            $record->certificate = in_array($rawCert, ['yes', '1', 'available', 'true'], true) ? 'Available' : 'Not Available';

            $rawIdr = $record->bond_amount_idr ?? $record->bond_amount ?? null;
            $rawUsd = $record->bond_amount_usd ?? null;
            $parts = [];
            if ($rawIdr !== null && (float) $rawIdr > 0) {
                $record->bond_amount_idr_formatted = 'Rp ' . number_format((float) $rawIdr, 0, ',', '.');
                $parts[] = $record->bond_amount_idr_formatted;
            } else {
                $record->bond_amount_idr_formatted = '-';
            }
            if ($rawUsd !== null && (float) $rawUsd > 0) {
                $record->bond_amount_usd_formatted = '$ ' . number_format((float) $rawUsd, 2, '.', ',');
                $parts[] = $record->bond_amount_usd_formatted;
            } else {
                $record->bond_amount_usd_formatted = '-';
            }
            $record->bond_amount_formatted = !empty($parts) ? implode(' / ', $parts) : '-';

            return response()->json([
                'success' => true,
                'data'    => $record,
            ]);
        }

        // Online Content or Playlist record detail
        $contentUser = ContentUser::with(['user', 'content.classification'])
            ->where('user_id', $userId)
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('content_id', $id);
            })
            ->first();

        if (!$contentUser) {
            // Check if it is a completed playlist
            $planId = is_numeric($id) ? (int) $id : (int) str_replace('playlist_', '', (string) $id);
            $plan = TrainingPlan::with(['trainingplandetail.content.classification'])
                ->where('id', $planId)
                ->first();

            if ($plan) {
                $actualHours = $this->recordLhService()->calculatePlaylist($planId, $userId);
                return response()->json([
                    'success' => true,
                    'data'    => [
                        'id'                     => 'playlist_' . $plan->id,
                        'title'                  => $plan->title,
                        'course_name'            => $plan->title,
                        'type'                   => 'Playlist',
                        'category'               => 'Playlist',
                        'level'                  => '-',
                        'batch'                  => '-',
                        'main_classification'    => '-',
                        'sub_classification'     => '-',
                        'counted_corporate_kpi'  => 'No',
                        'sub_corporate_kpi'      => '-',
                        'delivery_method'        => 'Online',
                        'sub_delivery_method'    => 'E-learning',
                        'provider'               => 'Internal',
                        'sub_provider'           => '-',
                        'provider_name'          => 'KP-OWL',
                        'venue'                  => 'KP-OWL',
                        'instructor'             => '-',
                        'duration'               => $plan->duration ?? '-',
                        'duration_seconds'       => (int) ($plan->total_duration_seconds ?? 0),
                        'course_learning_hours'  => $actualHours,
                        'learning_hours'         => $actualHours,
                        'progress'               => 100,
                        'start_date'             => optional($plan->created_at)->format('Y-m-d H:i:s') ?? '-',
                        'finish_date'            => optional($plan->updated_at)->format('Y-m-d H:i:s') ?? '-',
                        'attendance'             => 'Yes',
                        'attendance_reason'      => '-',
                        'certificate'            => (($plan->finished_items ?? 0) >= ($plan->total_items ?? 1)) ? 'Available' : 'Not Available',
                        'bond_status'            => 'No',
                        'bond_duration'          => null,
                        'bond_end_date'          => '-',
                        'bond_amount'            => '-',
                        'bond_amount_idr'        => '-',
                        'bond_amount_usd'        => '-',
                        'bond_amount_formatted'  => '-',
                        'pic'                    => '-',
                        'participant_name'       => Auth::user()?->name ?? '-',
                        'employee_id'            => Auth::user()?->employee_id ?? Auth::user()?->nip ?? Auth::user()?->id_emp ?? '-',
                        'grade'                  => Auth::user()?->grade ?? '-',
                        'job_position'           => Auth::user()?->job_position ?? Auth::user()?->position ?? '-',
                        'team'                   => Auth::user()?->team ?? '-',
                        'department'             => Auth::user()?->department ?? '-',
                        'company'                => Auth::user()?->company ?? Auth::user()?->company_name ?? 'PT Krakatau Posco',
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Online learning record not found.',
            ], 404);
        }

        $content = $contentUser->content;
        $actualHours = $this->recordLhService()->calculateContent($contentUser);
        $durationSec = DurationFormatter::toSeconds(optional($content)->duration) ?? 0;

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                     => $contentUser->id,
                'title'                  => optional($content)->title ?? '-',
                'course_name'            => optional($content)->title ?? '-',
                'sub_course_name'        => optional($content)->description ?? '',
                'type'                   => \App\Support\ContentTypeHelper::label(optional($content)->type) ?: 'Content',
                'category'               => 'All Content',
                'level'                  => '-',
                'batch'                  => '-',
                'main_classification'    => optional(optional($content)->classification)->classification_name ?? '-',
                'sub_classification'     => '-',
                'counted_corporate_kpi'  => 'No',
                'sub_corporate_kpi'      => '-',
                'delivery_method'        => 'Online',
                'sub_delivery_method'    => 'E-learning',
                'provider'               => 'Internal',
                'sub_provider'           => '-',
                'provider_name'          => 'KP-OWL',
                'venue'                  => 'KP-OWL',
                'instructor'             => optional($content)->author ?: '-',
                'duration'               => DurationFormatter::formatForLearningRecord(optional($content)->duration),
                'duration_seconds'       => $durationSec,
                'course_learning_hours'  => $actualHours,
                'learning_hours'         => $actualHours,
                'progress'               => (int) ($contentUser->progress ?? 0),
                'start_date'             => optional($contentUser->created_at)->format('Y-m-d H:i:s') ?? '-',
                'finish_date'            => (int) ($contentUser->finish ?? 0) === 1 ? optional($contentUser->completed_at ?? $contentUser->updated_at)->format('Y-m-d H:i:s') : '-',
                'attendance'             => ((int) ($contentUser->progress ?? 0) >= 100) ? 'Yes' : 'No',
                'attendance_reason'      => '-',
                'certificate'            => ((int) ($contentUser->progress ?? 0) >= 100) ? 'Available' : 'Not Available',
                'bond_status'            => 'No',
                'bond_duration'          => null,
                'bond_end_date'          => '-',
                'bond_amount'            => '-',
                'bond_amount_idr'        => '-',
                'bond_amount_usd'        => '-',
                'bond_amount_formatted'  => '-',
                'pic'                    => '-',
                'participant_name'       => Auth::user()?->name ?? '-',
                'employee_id'            => Auth::user()?->employee_id ?? Auth::user()?->nip ?? Auth::user()?->id_emp ?? '-',
                'grade'                  => Auth::user()?->grade ?? '-',
                'job_position'           => Auth::user()?->job_position ?? Auth::user()?->position ?? '-',
                'team'                   => Auth::user()?->team ?? '-',
                'department'             => Auth::user()?->department ?? '-',
                'company'                => Auth::user()?->company ?? Auth::user()?->company_name ?? 'PT Krakatau Posco',
            ],
        ]);
    }
}
