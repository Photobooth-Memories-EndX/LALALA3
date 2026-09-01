<?php

namespace App\Exports;

use App\Models\CertificateSetting;
use App\Models\ContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Services\CertificateEligibilityService;
use App\Services\Learning\CompletedPlaylistService;
use App\Services\Learning\LearningHourService;
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

        $hours = app(LearningHourService::class);
        $eligibilityService = app(CertificateEligibilityService::class);
        $contentUsers = $this->completedContents((int) $user->id);
        $playlists = $this->completedPlaylists((int) $user->id);
        $certificate = $this->certificateContext($user, $eligibilityService);
        $contentUsersById = $contentUsers->keyBy('content_id');

        $contentRows = $contentUsers->map(fn (ContentUser $cu) => $this->contentRow($cu, $certificate, $hours));
        $contentRowsById = $contentUsers->mapWithKeys(
            fn (ContentUser $cu, int $index) => [(int) $cu->content_id => $contentRows->get($index)]
        );
        $playlistRows = $playlists->map(fn (TrainingPlan $plan) => $this->playlistRows($plan, $contentUsersById, $certificate, $hours));

        $this->rows = $this->applyFilters($contentRows->concat($playlistRows)->values());
        $output = [$this->headings()];
        $number = 1;

        foreach ($this->rows as $row) {
            $output[] = $this->excelRow($row, $number++);
            foreach ($row['children'] ?? [] as $child) {
                $output[] = $this->excelRow($child, '');
            }
        }

        $filteredContentIds = $contentRowsById
            ->filter(fn (array $row) => $this->applyFilters(collect([$row]))->isNotEmpty())
            ->keys();
        $filteredPlaylistIds = $this->rows
            ->filter(fn (array $row) => $row['type'] === 'Playlist')
            ->pluck('playlist_id');
        $playlistChildContentIds = $playlists
            ->whereIn('id', $filteredPlaylistIds)
            ->flatMap(fn (TrainingPlan $plan) => $plan->trainingplandetail->pluck('content_id'));
        $totalContentIds = $filteredContentIds
            ->merge($playlistChildContentIds)
            ->unique()
            ->values();
        $totalRows = $contentRowsById->only($totalContentIds->all());

        $durationSeconds = (int) $totalRows->sum(
            fn (array $row) => max(0, (int) ($row['duration_seconds'] ?? 0))
        );
        $learningHours = (float) $totalRows->sum(
            fn (array $row) => (float) ($row['learning_hour_value'] ?? 0)
        );

        $output[] = array_fill(0, count($this->headings()), '');
        $output[] = [
            'TOTAL',
            '',
            '',
            '',
            DurationFormatter::fromSeconds($durationSeconds),
            $hours->value($learningHours),
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
            $sheet->getStyle('A1:K1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A1:K' . max(1, $sheet->getHighestRow()))->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $row = 2;
            foreach ($this->rows as $item) {
                $sheet->getStyle('A' . $row . ':K' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($item['type'] === 'Playlist' ? 'EDEDED' : 'FFFFFF');
                $row += 1 + count($item['children'] ?? []);
                if (!empty($item['children'])) {
                    $sheet->getStyle('B' . ($row - count($item['children'])) . ':K' . ($row - 1))->getAlignment()->setIndent(1);
                }
            }

            $summaryRow = $sheet->getHighestRow();
            $sheet->getStyle('A' . $summaryRow . ':K' . $summaryRow)->applyFromArray([
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
            $sheet->getStyle('E' . $summaryRow . ':F' . $summaryRow)
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
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

    private function contentRow(ContentUser $cu, array $cert, LearningHourService $hours, bool $playlistChild = false): array
    {
        $content = $cu->content;
        $setting = $cert['content']->get($content->id);
        $result = $setting ? ($cert['results'][$setting->id] ?? null) : null;
        $learningHoursVal = ($content->learning_hours !== null && $content->learning_hours !== '')
            ? (float) $content->learning_hours
            : (float) $hours->bucket($content->duration);

        return [
            'category'            => 'Content',
            'type'                => $playlistChild ? 'Content' : ($content->type ?? 'Content'),
            'title'               => $content->title ?? '-',
            'classification'      => optional($content->classification)->classification_name ?? '-',
            'duration'            => DurationFormatter::formatForLearningRecord($content->duration),
            'learning_hour'       => $hours->value($learningHoursVal),
            'progress'            => (int) ($cu->progress ?? 0) . '%',
            'start_date'          => optional($cu->created_at)->format('Y-m-d H:i') ?? '',
            'finish_date'         => (int) $cu->finish === 1 ? (optional($cu->updated_at)->format('Y-m-d H:i') ?? '') : '',
            'score'               => $result?->score() ?? '',
            'certificate'         => ($result?->eligible ?? false) ? 'Ready' : '',
            'date'                => $cu->updated_at,
            'duration_seconds'    => DurationFormatter::toSeconds($content->duration) ?? -1,
            'learning_hour_value' => $learningHoursVal,
            'learning_status'     => (string) ($cu->learning_status ?? ''),
            'playlist_ids'        => $content->trainingPlans->pluck('id')->map(fn ($id) => (string) $id)->all(),
            'children'            => [],
        ];
    }

    private function playlistRows(TrainingPlan $plan, Collection $contentUsers, array $cert, LearningHourService $hours): array
    {
        $seconds = (int) ($plan->total_duration_seconds ?? 0);
        $learningHoursVal = ($plan->learning_hours !== null && $plan->learning_hours !== '')
            ? (float) $plan->learning_hours
            : (float) $hours->bucketFromSeconds($seconds);
        $setting = $cert['playlist']->get($plan->id);
        $result = $setting ? ($cert['results'][$setting->id] ?? null) : null;
        $children = $plan->trainingplandetail->sortBy('content_order')
            ->map(fn (TrainingPlanDetail $detail) => $contentUsers->get($detail->content_id))
            ->filter()
            ->map(fn (ContentUser $contentUser) => $this->contentRow($contentUser, $cert, $hours, true))
            ->values()
            ->all();
        $startDate = collect($children)->pluck('start_date')->filter()->min();

        return [
            'category'            => 'Playlist',
            'type'                => 'Playlist',
            'title'               => $plan->title ?? '-',
            'classification'      => '-',
            'duration'            => DurationFormatter::fromSeconds($seconds),
            'learning_hour'       => $hours->value($learningHoursVal),
            'progress'            => '100%',
            'start_date'          => $startDate ?? '',
            'finish_date'         => $plan->last_finished_at ? Carbon::parse($plan->last_finished_at)->format('Y-m-d H:i') : '',
            'score'               => $result?->score() ?? '',
            'certificate'         => ($result?->eligible ?? false) ? 'Ready' : '',
            'date'                => $plan->last_finished_at,
            'duration_seconds'    => $seconds,
            'learning_hour_value' => $learningHoursVal,
            'children'            => $children,
            'playlist_id'         => $plan->id,
        ];
    }

    private function excelRow(array $row, $number): array
    {
        return [
            $number,
            $row['title'],
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

    private function applyFilters(Collection $rows): Collection
    {
        $f = $this->filters;
        return $rows->filter(function ($row) use ($f) {
            $date = $row['date'] ? Carbon::parse($row['date']) : null;
            $type = $f['type'] ?? 'all';
            if ($type !== 'all' && !(($type === 'Video' && in_array($row['type'], ['Video', 'YouTube'], true)) || $row['type'] === $type)) return false;
            if (($f['classification'] ?? 'all') !== 'all' && $row['classification'] !== $f['classification']) return false;
            if (($f['playlist'] ?? 'all') !== 'all' && !in_array((string) $f['playlist'], $row['playlist_ids'] ?? [(string) ($row['playlist_id'] ?? '')], true)) return false;
            if (($f['year'] ?? 'all') !== 'all' && (!$date || $date->year != $f['year'])) return false;
            if (($f['month'] ?? 'all') !== 'all' && (!$date || $date->month != $f['month'])) return false;
            if (($f['modalYear'] ?? 'all') !== 'all' && (!$date || $date->year != $f['modalYear'])) return false;
            if (!empty($f['modalMonths']) && (!$date || !in_array((string) $date->month, (array) $f['modalMonths'], true))) return false;
            if (!empty($f['learningStatus']) && !in_array($row['learning_status'] ?? '', (array) $f['learningStatus'], true)) return false;
            if (($f['learningHour'] ?? 'all') !== 'all' && !$this->rangeMatches((float) ($row['learning_hour_value'] ?? 0), $f['learningHour'])) return false;
            if (($f['duration'] ?? 'all') !== 'all' && !$this->rangeMatches((float) ($row['duration_seconds'] ?? -1), $f['duration'], true)) return false;
            if (($f['completion'] ?? 'all') !== 'all' && !$this->completionMatches($row['progress'], $f['completion'])) return false;
            if (($f['scoreTest'] ?? 'all') !== 'all' && !$this->scoreMatches($row['score'], $f['scoreTest'])) return false;
            if (($f['certificate'] ?? 'all') !== 'all' && strtolower($row['certificate'] ?: 'none') !== strtolower($f['certificate'])) return false;
            if (($f['search'] ?? '') !== '' && !str_contains(strtolower($row['title'] . ' ' . $row['classification']), strtolower($f['search']))) return false;
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
