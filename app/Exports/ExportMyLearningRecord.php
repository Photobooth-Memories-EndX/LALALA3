<?php

namespace App\Exports;

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
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
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

        return $output;
    }

    public function headings(): array
    {
        return ['No', 'Type', 'Title', 'Classification', 'Duration', 'Learning Hour', 'Progress', 'Finish Date', 'Assessment Score', 'Certificate Status'];
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
            $sheet->getStyle('A1:J1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A1:J' . max(1, $sheet->getHighestRow()))->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $row = 2;
            foreach ($this->rows as $item) {
                $sheet->getStyle('A' . $row . ':J' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($item['type'] === 'Playlist' ? 'EDEDED' : 'FFFFFF');
                $row += 1 + count($item['children'] ?? []);
                if (!empty($item['children'])) {
                    $sheet->getStyle('C' . ($row - count($item['children'])) . ':J' . ($row - 1))->getAlignment()->setIndent(1);
                }
            }
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
        return TrainingPlan::query()->with(['trainingplandetail.content'])
            ->select('trainingplan.id', 'trainingplan.title', 'trainingplan.total_duration_seconds')
            ->whereExists(fn ($q) => $q->from('trainingplandetail')->join('content_users', 'content_users.content_id', '=', 'trainingplandetail.content_id')->whereColumn('trainingplandetail.trainingplan_id', 'trainingplan.id')->where('content_users.user_id', $userId)->where('content_users.finish', '1'))
            ->selectSub(TrainingPlanDetail::selectRaw('COUNT(*)')->whereColumn('trainingplandetail.trainingplan_id', 'trainingplan.id'), 'total_items')
            ->selectSub(ContentUser::selectRaw('COUNT(*)')->join('trainingplandetail', 'trainingplandetail.content_id', '=', 'content_users.content_id')->whereColumn('trainingplandetail.trainingplan_id', 'trainingplan.id')->where('content_users.user_id', $userId)->where('content_users.finish', '1'), 'finished_items')
            ->selectSub(ContentUser::selectRaw('MAX(content_users.updated_at)')->join('trainingplandetail', 'trainingplandetail.content_id', '=', 'content_users.content_id')->whereColumn('trainingplandetail.trainingplan_id', 'trainingplan.id')->where('content_users.user_id', $userId)->where('content_users.finish', '1'), 'last_finished_at')
            ->get()->filter(fn ($p) => (int) $p->total_items > 0 && (int) $p->finished_items >= (int) $p->total_items)->values();
    }

    private function certificateContext($user, CertificateEligibilityService $service): array
    {
        $settings = CertificateSetting::with(['content:id,title', 'trainingPlan.trainingplandetail', 'assessment:id,title'])
            ->where('is_active', 1)->where(function ($q) use ($user) {
                $q->where(function ($w) use ($user) { $w->where('certificate_type', 'content')->whereExists(fn ($s) => $s->from('content_users')->whereColumn('content_users.content_id', 'certificate_settings.content_id')->where('content_users.user_id', $user->id)->whereRaw('content_users.progress >= COALESCE(certificate_settings.minimum_progress, 0)')); })
                    ->orWhere(function ($w) use ($user) { $w->where('certificate_type', 'playlist')->whereExists(fn ($s) => $s->from('trainingplandetail')->join('content_users', 'content_users.content_id', '=', 'trainingplandetail.content_id')->whereColumn('trainingplandetail.trainingplan_id', 'certificate_settings.trainingplan_id')->where('content_users.user_id', $user->id)); });
            })->get();

        return ['content' => $settings->where('certificate_type', 'content')->keyBy('content_id'), 'playlist' => $settings->where('certificate_type', 'playlist')->keyBy('trainingplan_id'), 'results' => $service->checkMany($settings, $user)];
    }

    private function contentRow(ContentUser $cu, array $cert, LearningHourService $hours, bool $playlistChild = false): array
    {
        $content = $cu->content;
        $setting = $cert['content']->get($content->id);
        $result = $setting ? ($cert['results'][$setting->id] ?? null) : null;
        return ['type' => $playlistChild ? 'Content' : ($content->type ?? 'Content'), 'title' => $content->title ?? '-', 'classification' => optional($content->classification)->classification_name ?? '-', 'duration' => DurationFormatter::formatForLearningRecord($content->duration), 'learning_hour' => $hours->format($content->duration), 'progress' => (int) ($cu->progress ?? 0) . '%', 'finish_date' => optional($cu->updated_at)->format('Y-m-d H:i') ?? '', 'score' => $result?->score() ?? '', 'certificate' => ($result?->eligible ?? false) ? 'Ready' : '', 'date' => $cu->updated_at, 'duration_seconds' => DurationFormatter::toSeconds($content->duration) ?? -1, 'learning_hour_value' => $hours->value($hours->bucket($content->duration)), 'learning_status' => (string) ($cu->learning_status ?? ''), 'playlist_ids' => $content->trainingPlans->pluck('id')->map(fn ($id) => (string) $id)->all(), 'children' => []];
    }

    private function playlistRows(TrainingPlan $plan, Collection $contentUsers, array $cert, LearningHourService $hours): array
    {
        $seconds = (int) ($plan->total_duration_seconds ?? 0);
        $setting = $cert['playlist']->get($plan->id);
        $result = $setting ? ($cert['results'][$setting->id] ?? null) : null;
        $children = $plan->trainingplandetail->sortBy('content_order')
            ->map(fn (TrainingPlanDetail $detail) => $contentUsers->get($detail->content_id))
            ->filter()
            ->map(fn (ContentUser $contentUser) => $this->contentRow($contentUser, $cert, $hours, true))
            ->values()
            ->all();
        return ['type' => 'Playlist', 'title' => $plan->title ?? '-', 'classification' => 'Playlist', 'duration' => $this->duration($seconds), 'learning_hour' => $hours->formatFromSeconds($seconds), 'progress' => '100%', 'finish_date' => $plan->last_finished_at ? Carbon::parse($plan->last_finished_at)->format('Y-m-d H:i') : '', 'score' => $result?->score() ?? '', 'certificate' => ($result?->eligible ?? false) ? 'Ready' : '', 'date' => $plan->last_finished_at, 'children' => $children, 'playlist_id' => $plan->id];
    }

    private function excelRow(array $row, $number): array
    {
        return [$number, $row['type'], $row['title'], $row['classification'], $row['duration'], $row['learning_hour'], $row['progress'], $row['finish_date'], $row['score'], $row['certificate']];
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

    private function duration(int $seconds): string
    {
        if ($seconds <= 0) return '-';
        $hours = intdiv($seconds, 3600); $minutes = intdiv($seconds % 3600, 60);
        return ($hours ? $hours . ' ' . ($hours === 1 ? 'hour' : 'hours') . ' ' : '') . ($minutes || !$hours ? $minutes . ' min' : '');
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
