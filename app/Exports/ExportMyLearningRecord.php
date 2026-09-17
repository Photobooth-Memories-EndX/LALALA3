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
        return app(CompletedPlaylistService::class)->forUser($userId);
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

        return [
            'category'            => 'Content',
            'type'                => \App\Support\ContentTypeHelper::label($content->type) ?: 'Content',
            'title'               => $content->title ?? '-',
            'classification'      => optional($content->classification)->classification_name ?? '-',
            'duration'            => $durationFormatted,
            'learning_hour'       => round($actualHours, 2),
            'progress'            => (int) ($cu->progress ?? 0) . '%',
            'start_date'          => optional($cu->created_at)->format('Y-m-d H:i') ?? '',
            'finish_date'         => (int) $cu->finish === 1 ? (optional($cu->completed_at ?? $cu->updated_at)->format('Y-m-d H:i') ?? '') : '',
            'score'               => $result?->score() ?? '',
            'certificate'         => ($result?->eligible ?? false) ? 'Ready' : '',
            'date'                => $cu->completed_at ?? $cu->updated_at,
            'duration_seconds'    => $durationSeconds ?? -1,
            'learning_hour_value' => $actualHours,
            'learning_status'     => (string) ($cu->learning_status ?? ''),
            'playlist_ids'        => $content->trainingPlans->pluck('id')->map(fn ($id) => (string) $id)->all(),
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

        return [
            'category'            => 'Playlist',
            'type'                => 'Playlist',
            'title'               => $plan->title ?? '-',
            'classification'      => '-',
            'duration'            => DurationFormatter::fromSeconds($seconds),
            'learning_hour'       => round($actualHours, 2),
            'progress'            => '100%',
            'start_date'          => $startDate,
            'finish_date'         => $plan->last_finished_at ? Carbon::parse($plan->last_finished_at)->format('Y-m-d H:i') : '',
            'score'               => $result?->score() ?? '',
            'certificate'         => ($result?->eligible ?? false) ? 'Ready' : '',
            'date'                => $plan->last_finished_at,
            'duration_seconds'    => $seconds,
            'learning_hour_value' => $actualHours,
            'children'            => $children,
            'playlist_id'         => $plan->id,
        ];
    }

    private function excelRow(array $row, $number): array
    {
        return [
            $number,
            $row['title'],
            $row['type'],
            $row['category'],
            $row['classification'],
            $row['duration'],
            $row['learning_hour'],
            $row['progress'],
            $row['start_date'],
            $row['finish_date'],
            $row['score'],
            $row['certificate'] === 'Ready' ? 'Available' : 'Not Available',
        ];
    }

    private function normalizeFilters(array $f): array
    {
        $rawMonth = $f['month'] ?? $f['record_filter_month'] ?? 'all';
        $monthNum = \App\Support\LearningFilterHelper::resolveMonth((string) $rawMonth);

        return [
            'search'         => trim((string) ($f['search'] ?? $f['record_search'] ?? $f['q'] ?? '')),
            'type'           => (string) ($f['type'] ?? $f['record_filter_type'] ?? 'all'),
            'classification' => (string) ($f['classification'] ?? $f['record_filter_classification'] ?? 'all'),
            'playlist'       => (string) ($f['playlist'] ?? $f['record_filter_playlist'] ?? 'all'),
            'year'           => (string) ($f['year'] ?? $f['record_filter_year'] ?? 'all'),
            'month'          => $monthNum !== null ? (string) $monthNum : 'all',
            'modalYear'      => (string) ($f['modalYear'] ?? $f['record_filter_modal_year'] ?? 'all'),
            'modalMonths'    => array_map('strval', array_values(array_filter(array_map([\App\Support\LearningFilterHelper::class, 'resolveMonth'], (array) ($f['modalMonths'] ?? $f['record_filter_modal_month'] ?? []))))),
            'modalTypes'     => array_values(array_filter((array) ($f['modalTypes'] ?? $f['record_filter_modal_type'] ?? []), fn ($v) => $v !== 'all')),
            'program'        => (string) ($f['program'] ?? $f['record_filter_program'] ?? 'all'),
            'learningStatus' => array_values(array_filter((array) ($f['learningStatus'] ?? $f['record_filter_learning_status'] ?? []))),
            'learningHour'   => (string) ($f['learningHour'] ?? $f['record_filter_learning_hour'] ?? 'all'),
            'duration'       => (string) ($f['duration'] ?? $f['record_filter_duration'] ?? 'all'),
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

        return $rows->filter(function ($row) use ($f) {
            $date = !empty($row['date']) ? Carbon::parse($row['date']) : null;
            $rowType = (string) ($row['type'] ?? '');
            $isPlaylist = ($rowType === 'Playlist' || ($row['category'] ?? '') === 'Playlist');

            // Quick Filter mode
            if ($f['quickFilter'] === 'content' && $isPlaylist) {
                return false;
            }
            if ($f['quickFilter'] === 'playlist' && !$isPlaylist) {
                return false;
            }

            // Type filter
            $type = strtolower(trim($f['type']));
            if ($type !== 'all' && $type !== '') {
                if (($type === 'playlist' || $type === 'trainingplan') && !$isPlaylist) {
                    return false;
                }
                if ($type === 'content' && $isPlaylist) {
                    return false;
                }
                if ($type === 'video' && !in_array($rowType, ['Video', 'YouTube'], true)) {
                    return false;
                }
                if ($type === 'document' && strtolower($rowType) !== 'document') {
                    return false;
                }
                if ($type === 'mooc' && !in_array(strtolower($rowType), ['mooc', 'youtube'], true)) {
                    return false;
                }
                if (!in_array($type, ['playlist', 'trainingplan', 'content', 'video', 'document', 'mooc'], true) && strtolower($rowType) !== $type) {
                    return false;
                }
            }

            // Modal types
            if (!empty($f['modalTypes'])) {
                $matchedType = false;
                foreach ($f['modalTypes'] as $mt) {
                    $normMt = strtolower(trim($mt));
                    if (($normMt === 'playlist' || $normMt === 'trainingplan') && $isPlaylist) { $matchedType = true; break; }
                    if ($normMt === 'content' && !$isPlaylist) { $matchedType = true; break; }
                    if ($normMt === 'video' && in_array($rowType, ['Video', 'YouTube'], true)) { $matchedType = true; break; }
                    if ($normMt === 'document' && strtolower($rowType) === 'document') { $matchedType = true; break; }
                    if ($normMt === 'mooc' && in_array(strtolower($rowType), ['mooc', 'youtube'], true)) { $matchedType = true; break; }
                    if (strtolower($rowType) === $normMt) { $matchedType = true; break; }
                }
                if (!$matchedType) {
                    return false;
                }
            }

            if ($f['classification'] !== 'all' && ($row['classification'] ?? '') !== $f['classification']) {
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
            if (!empty($f['modalMonths']) && (!$date || !in_array((string) (int) $date->month, $f['modalMonths'], true))) {
                return false;
            }

            if (!empty($f['learningStatus']) && !in_array($row['learning_status'] ?? '', $f['learningStatus'], true)) {
                return false;
            }

            if ($f['learningHour'] !== 'all' && !$this->rangeMatches((float) ($row['learning_hour_value'] ?? 0), $f['learningHour'])) {
                return false;
            }

            if ($f['duration'] !== 'all' && !$this->rangeMatches((float) ($row['duration_seconds'] ?? -1), $f['duration'], true)) {
                return false;
            }

            if (!empty($f['progressRanges'])) {
                $rawProg = (float) rtrim((string) ($row['progress'] ?? 0), '%');
                $matchedProg = false;
                foreach ($f['progressRanges'] as $pr) {
                    if ($this->rangeMatches($rawProg, (string) $pr)) {
                        $matchedProg = true;
                        break;
                    }
                }
                if (!$matchedProg) {
                    return false;
                }
            }

            if ($f['completion'] !== 'all' && !$this->completionMatches((string) ($row['progress'] ?? ''), $f['completion'])) {
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
        })->sortBy(fn ($row) => $row['type'] === 'Playlist' ? 2 : (strtolower($row['type']) === 'video' || strtolower($row['type']) === 'youtube' ? 1 : 0))->values();
    }

    private function rangeMatches(float $value, string $range, bool $seconds = false): bool
    {
        if ($range === 'all') return true;
        if (str_starts_with($range, 'gt')) return $value > (float) substr($range, 2) * ($seconds ? 60 : 1);
        if (!str_contains($range, '-')) return $value === (float) $range * ($seconds ? 60 : 1);
        [$min, $max] = array_map('floatval', explode('-', $range, 2));
        return $value >= $min * ($seconds ? 60 : 1) && $value <= $max * ($seconds ? 60 : 1);
    }

    private function completionMatches(string $progress, string $mode): bool
    {
        $value = (float) rtrim($progress, '%');
        return match ($mode) { 'not_started' => $value === 0.0, 'in_progress' => $value > 0 && $value <= 75, 'almost_done' => $value >= 76 && $value < 100, 'completed' => $value === 100, default => true };
    }

    private function scoreMatches($score, string $mode): bool
    {
        $value = $score === '' || $score === null ? -1 : (float) $score;
        return match ($mode) { 'none' => $value < 0, 'lt60' => $value >= 0 && $value < 60, '60-79' => $value >= 60 && $value <= 79, '80-100' => $value >= 80 && $value <= 100, default => true };
    }
}
