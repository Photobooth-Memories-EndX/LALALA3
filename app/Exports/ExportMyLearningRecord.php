<?php

namespace App\Exports;

use App\Models\CertificateSetting;
use App\Models\ContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Services\CertificateEligibilityService;
use App\Services\Learning\CompletedPlaylistService;
use App\Services\Learning\LearningHourService;
use App\Services\Learning\LearningRecordLearningHourService;
use App\Support\DurationFormatter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExportMyLearningRecord implements FromArray, ShouldAutoSize, WithEvents, WithStyles
{
    private Collection $rows;

    public function __construct(private readonly array $filters = [])
    {
        $this->rows = collect();
    }

    public function array(): array
    {
        $user = Auth::user();
        if (!$user) {
            return [$this->headings()];
        }

        $actualHourService = app(LearningRecordLearningHourService::class);
        $eligibilityService = app(CertificateEligibilityService::class);
        $contentUsers = $this->completedContents((int) $user->id);
        $playlists = $this->completedPlaylists((int) $user->id);
        $certificate = $this->certificateContext($user, $eligibilityService);
        $contentUsersById = $contentUsers->keyBy('content_id');
        $playlistStartDates = \App\Models\PlaylistContentUser::where('user_id', (int) $user->id)
            ->whereIn('trainingplan_id', $playlists->pluck('id'))
            ->groupBy('trainingplan_id')
            ->selectRaw('trainingplan_id, MIN(created_at) as min_created')
            ->pluck('min_created', 'trainingplan_id');
        $playlistActualHours = $actualHourService->calculatePlaylistsBatch((int) $user->id, $playlists);

        $contentRows = $contentUsers->map(fn (ContentUser $cu) => $this->contentRow($cu, $certificate, $actualHourService));
        $contentRowsById = $contentUsers->mapWithKeys(
            fn (ContentUser $cu, int $index) => [(int) $cu->content_id => $contentRows->get($index)]
        );
        $playlistRows = $playlists->map(fn (TrainingPlan $plan) => $this->playlistRows($plan, $contentUsersById, $certificate, $actualHourService, $playlistStartDates, $playlistActualHours));

        $this->rows = $this->applyFilters($contentRows->concat($playlistRows)->values());
        $output = [$this->headings()];
        $number = 1;

        foreach ($this->rows as $row) {
            $output[] = $this->excelRow($row, $number++);
            foreach ($row['children'] ?? [] as $child) {
                $output[] = $this->excelRow($child, '');
            }
        }

        $durationSeconds = (int) $this->rows->sum(
            fn (array $row) => max(0, (int) ($row['duration_seconds'] ?? 0))
        );
        $learningHours = (float) $this->rows->sum(
            fn (array $row) => (float) ($row['learning_hour_value'] ?? 0)
        );

        $output[] = array_fill(0, count($this->headings()), '');
        $output[] = [
            'TOTAL',
            '',
            '',
            '',
            '',
            DurationFormatter::fromSeconds($durationSeconds),
            round($learningHours, 2),
            '',
            '',
            '',
            '',
            '',
        ];

        return $output;
    }

    public function headings(): array
    {
        return [
            'No',
            'Course',
            'Type',
            'Category',
            'Classification',
            'Duration',
            'Learning Hour',
            'Learning Progress',
            'Start Date',
            'Finish Date',
            'Assessment Score',
            'Certificate',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9EAF7']]]];
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event) {
            $sheet = $event->sheet->getDelegate();
            $sheet->freezePane('A2');
            $sheet->getStyle('A1:L1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A1:L' . max(1, $sheet->getHighestRow()))->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $row = 2;
            foreach ($this->rows as $item) {
                $sheet->getStyle('A' . $row . ':L' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($item['type'] === 'Playlist' ? 'EDEDED' : 'FFFFFF');
                $row += 1 + count($item['children'] ?? []);
                if (!empty($item['children'])) {
                    $sheet->getStyle('B' . ($row - count($item['children'])) . ':L' . ($row - 1))->getAlignment()->setIndent(1);
                }
            }

            $summaryRow = $sheet->getHighestRow();
            $sheet->getStyle('A' . $summaryRow . ':L' . $summaryRow)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'EDEDED'],
                ],
                'borders' => [
                    'top' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '808080'],
                    ],
                ],
            ]);
            $sheet->getStyle('F' . $summaryRow . ':G' . $summaryRow)
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('G2:G' . $summaryRow)
                ->getNumberFormat()
                ->setFormatCode('0.00');
        }];
    }

    private function completedContents(int $userId): Collection
    {
        return ContentUser::with(['content.classification', 'content.trainingPlans:id,title'])
            ->where('user_id', $userId)->where('finish', '1')->get()
            ->filter(fn ($cu) => $cu->content !== null && $cu->content->deleted_at === null)->values();
    }

    private function completedPlaylists(int $userId): Collection
    {
        return app(CompletedPlaylistService::class)->forLearningRecord($userId);
    }

    private function certificateContext($user, CertificateEligibilityService $service): array
    {
        $userId = (int) $user->id;

        $settings = CertificateSetting::with(['content:id,title', 'trainingPlan.trainingplandetail', 'assessment:id,title'])
            ->where('is_active', 1)
            ->where(function ($q) use ($userId) {
                $q->where(function ($w) use ($userId) {
                    $w->where('certificate_type', 'content')
                        ->whereExists(fn ($s) => $s->from('content_users')
                            ->whereColumn('content_users.content_id', 'certificate_settings.content_id')
                            ->where('content_users.user_id', $userId)
                            ->whereRaw('content_users.progress >= COALESCE(certificate_settings.minimum_progress, 0)'));
                })
                ->orWhere(function ($w) use ($userId) {
                    $w->where('certificate_type', 'playlist')
                        ->where(function ($pw) use ($userId) {
                            $pw->whereExists(fn ($s) => $s->from('playlist_content_users')
                                ->whereColumn('playlist_content_users.trainingplan_id', 'certificate_settings.trainingplan_id')
                                ->where('playlist_content_users.user_id', $userId))
                            ->orWhereExists(fn ($s) => $s->from('trainingplandetail')
                                ->join('content_users', 'content_users.content_id', '=', 'trainingplandetail.content_id')
                                ->whereColumn('trainingplandetail.trainingplan_id', 'certificate_settings.trainingplan_id')
                                ->where('content_users.user_id', $userId));
                        });
                })
                ->orWhere(function ($w) use ($userId) {
                    $w->where('certificate_type', 'global')
                        ->whereExists(fn ($s) => $s->from('assesment_results')
                            ->whereColumn('assesment_results.assesment_id', 'certificate_settings.assessment_id')
                            ->where('assesment_results.user_id', $userId)
                            ->where('assesment_results.final', 1));
                });
            })
            ->get();

        return [
            'content'  => $settings->where('certificate_type', 'content')->keyBy('content_id'),
            'playlist' => $settings->where('certificate_type', 'playlist')->keyBy('trainingplan_id'),
            'results'  => $service->checkMany($settings, $user),
        ];
    }

    private function contentRow(ContentUser $cu, array $cert, LearningRecordLearningHourService $hourService, bool $playlistChild = false): array
    {
        $content = $cu->content;
        $setting = $cert['content']->get($content->id);
        $result = $setting ? ($cert['results'][$setting->id] ?? null) : null;
        $actualHours = $hourService->calculateContent($cu);
        $durationSeconds = DurationFormatter::toSeconds($content->duration);
        $durationFormatted = $durationSeconds !== null
            ? DurationFormatter::fromSeconds($durationSeconds)
            : '00:00:00';

        $progress = (int) ($cu->progress ?? 0);
        $hasScore = ($setting !== null) && !is_null($result?->score());
        $minScore = (int) ($setting?->minimum_score ?? 0);
        $scorePassed = $hasScore && ($result?->score() >= $minScore);

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

        $finishDate = (int) $cu->finish === 1
            ? (optional($cu->completed_at ?? $cu->updated_at)->format('Y-m-d H:i:s') ?? '')
            : '';

        return [
            'row_kind'            => 'content',
            'category'            => 'Content',
            'type'                => \App\Support\ContentTypeHelper::label($content->type) ?: 'Content',
            'title'               => $content->title ?? '-',
            'subtitle'            => ucfirst($cu->status ?? '-'),
            'classification'      => optional($content->classification)->classification_name ?? '-',
            'duration'            => $durationFormatted,
            'duration_sort'       => $durationSeconds ?? -1,
            'learning_hour'       => round($actualHours, 2),
            'learning_hour_value' => $actualHours,
            'progress'            => $progress,
            'start_date'          => optional($cu->created_at)->format('Y-m-d H:i') ?? '',
            'finish_date'         => $finishDate,
            'score'               => $result?->score() ?? '',
            'min_score'           => $minScore,
            'certificate'         => ($result?->eligible ?? false) ? 'Ready' : '',
            'date'                => $cu->completed_at ?? $cu->updated_at,
            'duration_seconds'    => $durationSeconds ?? -1,
            'learning_status'     => (string) ($cu->learning_status ?? ''),
            'learning_statuses'   => $rowStatuses,
            'playlist_ids'        => $content->trainingPlans->pluck('id')->map(fn ($id) => (string) $id)->all(),
            'item_id'             => 'content_' . $content->id,
            'children'            => [],
        ];
    }

    private function playlistRows(
        TrainingPlan $plan,
        Collection $contentUsers,
        array $cert,
        LearningRecordLearningHourService $hourService,
        ?Collection $playlistStartDates = null,
        ?Collection $playlistActualHours = null
    ): array
    {
        $seconds = (int) ($plan->total_duration_seconds ?? 0);
        $actualHours = $playlistActualHours?->get($plan->id)
            ?? $hourService->calculatePlaylistSingle((int) Auth::id(), $plan);
        $setting = $cert['playlist']->get($plan->id);
        $result = $setting ? ($cert['results'][$setting->id] ?? null) : null;
        $children = $plan->trainingplandetail->sortBy('content_order')
            ->map(fn (TrainingPlanDetail $detail) => $contentUsers->get($detail->content_id))
            ->filter()
            ->map(fn (ContentUser $contentUser) => $this->contentRow($contentUser, $cert, $hourService, true))
            ->values()
            ->all();

        $rawStartDate = $playlistStartDates?->get($plan->id)
            ?? collect($children)->pluck('start_date')->filter()->min()
            ?? $plan->created_at;
        $startDate = $rawStartDate ? Carbon::parse($rawStartDate)->format('Y-m-d H:i') : '';
        $finishDate = $plan->last_finished_at ? Carbon::parse($plan->last_finished_at)->format('Y-m-d H:i:s') : '';

        $hasScore = ($setting !== null) && !is_null($result?->score());
        $minScore = (int) ($setting?->minimum_score ?? 0);
        $scorePassed = $hasScore && ($result?->score() >= $minScore);

        $rowStatuses = ['completed'];
        if ($hasScore) {
            $rowStatuses[] = $scorePassed ? 'passed' : 'failed';
        }

        return [
            'row_kind'            => 'playlist',
            'category'            => 'Playlist',
            'type'                => 'Playlist',
            'title'               => $plan->title ?? '-',
            'subtitle'            => (int) ($plan->finished_items ?? 0) . ' / ' . (int) ($plan->total_items ?? count($children)) . ' content selesai',
            'classification'      => '-',
            'duration'            => DurationFormatter::fromSeconds($seconds),
            'duration_sort'       => $seconds,
            'learning_hour'       => round($actualHours, 2),
            'learning_hour_value' => $actualHours,
            'progress'            => 100,
            'start_date'          => $startDate,
            'finish_date'         => $finishDate,
            'score'               => $result?->score() ?? '',
            'min_score'           => $minScore,
            'certificate'         => ($result?->eligible ?? false) ? 'Ready' : '',
            'date'                => $plan->last_finished_at,
            'duration_seconds'    => $seconds,
            'learning_status'     => 'completed',
            'learning_statuses'   => $rowStatuses,
            'children'            => $children,
            'playlist_id'         => $plan->id,
            'playlist_ids'        => [(string) $plan->id],
            'item_id'             => 'playlist_' . $plan->id,
        ];
    }

    private function excelRow(array $row, $number): array
    {
        $progress = is_numeric($row['progress']) ? $row['progress'] . '%' : (string) $row['progress'];
        $startDate = !empty($row['start_date']) ? Carbon::parse($row['start_date'])->format('Y-m-d H:i') : '';
        $finishDate = !empty($row['finish_date']) ? Carbon::parse($row['finish_date'])->format('Y-m-d H:i') : '';

        return [
            $number,
            $row['title'],
            $row['type'],
            $row['category'],
            $row['classification'],
            $row['duration'],
            $row['learning_hour'],
            $progress,
            $startDate,
            $finishDate,
            $row['score'],
            $row['certificate'] === 'Ready' ? 'Available' : 'Not Available',
        ];
    }

    private function normalizeFilters(array $f): array
    {
        $rawMonth = $f['month'] ?? $f['record_filter_month'] ?? 'all';
        $monthNum = is_array($rawMonth) ? null : \App\Support\LearningFilterHelper::resolveMonth((string) $rawMonth);
        $rawClassification = (string) ($f['classification'] ?? $f['record_filter_classification'] ?? 'all');
        $classification = $rawClassification !== 'all' ? trim(html_entity_decode($rawClassification, ENT_QUOTES | ENT_HTML5, 'UTF-8')) : 'all';

        $modalMonthsInput = (array) ($f['modalMonths'] ?? $f['record_filter_modal_month'] ?? (is_array($rawMonth) ? $rawMonth : []));
        $modalMonths = array_map('strval', array_values(array_filter(array_map([\App\Support\LearningFilterHelper::class, 'resolveMonth'], $modalMonthsInput))));

        $durationInput = $f['duration'] ?? $f['record_filter_duration'] ?? $f['recordModalDurationCheck'] ?? 'all';
        $duration = is_array($durationInput) ? ($durationInput[0] ?? 'all') : (string) $durationInput;

        return [
            'search'         => trim((string) ($f['search'] ?? $f['record_search'] ?? $f['q'] ?? '')),
            'type'           => (string) ($f['type'] ?? $f['record_filter_type'] ?? 'all'),
            'classification' => $classification,
            'playlist'       => (string) ($f['playlist'] ?? $f['record_filter_playlist'] ?? 'all'),
            'year'           => (string) ($f['year'] ?? $f['record_filter_year'] ?? 'all'),
            'month'          => $monthNum !== null ? (string) $monthNum : 'all',
            'modalYear'      => (string) ($f['modalYear'] ?? $f['record_filter_modal_year'] ?? 'all'),
            'modalMonths'    => $modalMonths,
            'modalTypes'     => array_values(array_filter((array) ($f['modalTypes'] ?? $f['record_filter_modal_type'] ?? []), fn ($v) => $v !== 'all')),
            'program'        => (string) ($f['program'] ?? $f['record_filter_program'] ?? 'all'),
            'learningStatus' => array_values(array_filter((array) ($f['learningStatus'] ?? $f['record_filter_learning_status'] ?? []))),
            'learningHour'   => (string) ($f['learningHour'] ?? $f['record_filter_learning_hour'] ?? 'all'),
            'duration'       => $duration,
            'progressRanges' => array_values(array_filter((array) ($f['progressRanges'] ?? $f['record_filter_progress'] ?? []))),
            'completion'     => (string) ($f['completion'] ?? $f['record_filter_completion'] ?? 'all'),
            'scoreTest'      => (string) ($f['scoreTest'] ?? $f['record_filter_score'] ?? 'all'),
            'certificate'    => (string) ($f['certificate'] ?? $f['record_filter_certificate'] ?? 'all'),
            'quickFilter'    => (string) ($f['quickFilter'] ?? $f['record_quick'] ?? $f['ongoing_mode'] ?? 'all'),
        ];
    }

    private function applyFilters(Collection $rows): Collection
    {
        $f = $this->normalizeFilters($this->filters);

        $filtered = $rows->filter(function ($row) use ($f) {
            $finishDate = !empty($row['finish_date']) ? $row['finish_date'] : (!empty($row['date']) ? (string) $row['date'] : null);
            $date = $finishDate ? Carbon::parse($finishDate) : null;
            $rowType = (string) ($row['type'] ?? '');
            $rowKind = (string) ($row['row_kind'] ?? '');
            $isPlaylist = ($rowKind === 'playlist' || $rowType === 'Playlist' || ($row['category'] ?? '') === 'Playlist');

            // Quick Filter mode
            if ($f['quickFilter'] === 'content' && $isPlaylist) {
                return false;
            }
            if ($f['quickFilter'] === 'playlist' && !$isPlaylist) {
                return false;
            }

            // Type filter
            if (!$this->typeMatches($rowType, $rowKind, $f['type'])) {
                return false;
            }

            // Modal types
            if (!empty($f['modalTypes']) && !collect($f['modalTypes'])->contains(fn ($mt) => $this->typeMatches($rowType, $rowKind, (string) $mt))) {
                return false;
            }

            if ($f['classification'] !== 'all' && strcasecmp((string) ($row['classification'] ?? ''), $f['classification']) !== 0) {
                return false;
            }

            if ($f['playlist'] !== 'all') {
                $pids = array_map('strval', $row['playlist_ids'] ?? [(string) ($row['playlist_id'] ?? '')]);
                if (!in_array((string) $f['playlist'], $pids, true)) {
                    return false;
                }
            }

            if ($f['year'] !== 'all' && (!$date || (string) $date->year !== $f['year'])) {
                return false;
            }
            if ($f['modalYear'] !== 'all' && (!$date || (string) $date->year !== $f['modalYear'])) {
                return false;
            }
            if ($f['month'] !== 'all' && (!$date || (int) $date->month !== (int) $f['month'])) {
                return false;
            }
            if (!empty($f['modalMonths'])) {
                $modalMonthNums = array_filter(array_map([\App\Support\LearningFilterHelper::class, 'resolveMonth'], $f['modalMonths']));
                if (!$date || !in_array((int) $date->month, $modalMonthNums, true)) {
                    return false;
                }
            }

            // Program filter (mandatory / optional)
            $subtitle = mb_strtolower((string) ($row['subtitle'] ?? ''));
            $rowProgram = str_contains($subtitle, 'mandatory')
                ? 'mandatory'
                : (str_contains($subtitle, 'optional') ? 'optional' : '');
            if ($f['program'] !== 'all' && $rowProgram !== $f['program']) {
                return false;
            }

            // Learning Status (completed / passed / failed)
            if (!empty($f['learningStatus']) && empty(array_intersect($f['learningStatus'], $row['learning_statuses'] ?? []))) {
                return false;
            }

            if ($f['learningHour'] !== 'all' && !$this->rangeMatches((float) ($row['learning_hour_value'] ?? -1), $f['learningHour'])) {
                return false;
            }

            if ($f['duration'] !== 'all' && !$this->rangeMatches((float) ($row['duration_sort'] ?? -1), $f['duration'])) {
                return false;
            }

            $progress = (int) ($row['progress'] ?? 0);
            if (!empty($f['progressRanges']) && !collect($f['progressRanges'])->contains(fn ($range) => $this->rangeMatches((float) $progress, (string) $range))) {
                return false;
            }

            if ($f['completion'] !== 'all' && !$this->completionMatches($progress, $f['completion'])) {
                return false;
            }

            if ($f['scoreTest'] !== 'all' && !$this->scoreMatches($row['score'] ?? null, $f['scoreTest'])) {
                return false;
            }

            if ($f['certificate'] !== 'all') {
                $certVal = strtolower((string) ($row['certificate'] ?? 'none'));
                if ($certVal === '') $certVal = 'none';
                if ($certVal !== strtolower($f['certificate'])) {
                    return false;
                }
            }

            if ($f['search'] !== '') {
                $searchContent = mb_strtolower(($row['title'] ?? '') . ' ' . ($row['classification'] ?? ''));
                if (!str_contains($searchContent, mb_strtolower($f['search']))) {
                    return false;
                }
            }

            return true;
        });

        $sort = (string) ($this->filters['record_sort'] ?? $this->filters['sort'] ?? '');
        $direction = (string) ($this->filters['record_sort_direction'] ?? $this->filters['direction'] ?? 'asc');
        $activitySort = (string) ($this->filters['record_filter_activity_sort'] ?? $this->filters['activitySort'] ?? 'all');

        $sortKeys = [
            'title'          => fn (array $row) => mb_strtolower((string) ($row['title'] ?? '')),
            'category'       => fn (array $row) => mb_strtolower((string) ($row['category'] ?? '')),
            'type'           => fn (array $row) => mb_strtolower((string) ($row['type'] ?? '')),
            'classification' => fn (array $row) => mb_strtolower((string) ($row['classification'] ?? '')),
            'duration'       => fn (array $row) => (float) ($row['duration_sort'] ?? -1),
            'learning-hour'  => fn (array $row) => (float) ($row['learning_hour_value'] ?? -1),
            'progress'       => fn (array $row) => (int) ($row['progress'] ?? -1),
            'finish'         => fn (array $row) => (string) ($row['finish_date'] ?? ''),
            'score'          => fn (array $row) => (float) ($row['score'] ?? -1),
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
        } else {
            $filtered = $filtered->sortByDesc(fn (array $row) => (string) ($row['finish_date'] ?? ''));
        }

        return $filtered->values();
    }

    private function typeMatches(string $rowType, string $rowKind, string $filter): bool
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

    private function rangeMatches(float $value, string $range): bool
    {
        if ($range === 'all') return true;
        if (str_starts_with($range, 'gt')) return $value > (float) substr($range, 2);
        if (!str_contains($range, '-')) return $value === (float) $range;
        [$min, $max] = array_map('floatval', explode('-', $range, 2));
        return $value >= $min && $value <= $max;
    }

    private function completionMatches(int $progress, string $mode): bool
    {
        return match ($mode) {
            'not_started' => $progress === 0,
            'in_progress' => $progress > 0 && $progress <= 75,
            'almost_done' => $progress >= 76 && $progress < 100,
            'completed'   => $progress === 100,
            default       => true,
        };
    }

    private function scoreMatches(mixed $score, string $mode): bool
    {
        if ($mode === 'all') {
            return true;
        }
        $hasScore = $score !== null && $score !== '';
        $value = $hasScore ? (float) $score : -1;

        return match ($mode) {
            'none'   => ! $hasScore,
            'lt60'   => $hasScore && $value < 60,
            '60-79'  => $value >= 60 && $value <= 79,
            '80-100' => $value >= 80 && $value <= 100,
            default  => true,
        };
    }
}
