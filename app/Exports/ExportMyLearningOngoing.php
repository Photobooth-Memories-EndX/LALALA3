<?php

namespace App\Exports;

use App\Models\CertificateSetting;
use App\Models\PlaylistContentUser;
use App\Models\User;
use App\Services\CertificateEligibilityService;
use App\Services\Learning\LearningProgressResolver;
use App\Services\Learning\OngoingPlaylistService;
use App\Support\ContentTypeHelper;
use App\Support\DurationFormatter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ExportMyLearningOngoing extends DefaultValueBinder implements FromArray, WithEvents, WithColumnFormatting, WithStrictNullComparison, WithCustomValueBinder
{
    private array $parentRows = [];
    private array $childRows = [];

    public function __construct(private Collection $cards, private User $user)
    {
    }

    public function headings(): array
    {
        return ['No', 'Course', 'Type', 'Category', 'Classification', 'Duration', 'Learning Hour',
            'Learning Progress', 'Start Date', 'Finish Date', 'Attendance', 'Assessment Score', 'Certificate'];
    }

    public function array(): array
    {
        $this->parentRows = $this->childRows = [];
        $rows = [];
        $playlistService = app(OngoingPlaylistService::class);
        $resolver = app(LearningProgressResolver::class);
        foreach ($this->cards as $card) {
            if (in_array($card['card_type'], ['content', 'mooc'], true)) {
                $cu = $card['list'];
                $rows[] = $this->contentRow($cu->content, $cu, (int) $cu->progress,
                    $cu->isFinished(), (float) $card['current_learning_hours']);
                continue;
            }

            $plan = $card['plan'];
            $details = $plan->trainingplandetail->filter(fn ($d) => $d->content && $d->content->active === 'Active')
                ->unique('content_id')->values();
            $ids = $details->pluck('content_id');
            $progress = $resolver->resolvePlaylistBatch((int) $this->user->id, (int) $plan->id, $ids);
            $pcus = PlaylistContentUser::where('user_id', $this->user->id)->where('trainingplan_id', $plan->id)
                ->whereIn('content_id', $ids)->get()->keyBy('content_id');
            $details->loadMissing('content.classification');
            $children = [];
            foreach ($details as $detail) {
                $resolved = $progress->get($detail->content_id);
                $pcu = $pcus->get($detail->content_id);
                $seconds = DurationFormatter::toSeconds($detail->content->duration) ?? 0;
                $children[] = $this->contentRow($detail->content, $pcu, $resolved?->progress ?? 0,
                    $resolved?->isFinished() ?? false, round($playlistService->consumedSeconds($pcu, $seconds) / 3600, 4));
            }
            $rows[] = [
                'id' => $plan->id, 'kind' => 'playlist', 'title' => $plan->title, 'type' => 'Playlist',
                'classification' => '-', 'seconds' => (int) round($card['total_learning_hours'] * 3600),
                'hours' => (float) $card['current_learning_hours'], 'progress' => (int) $plan->progress_percent,
                'start' => collect($children)->pluck('start')->filter()->min(),
                'finish' => $plan->is_completed ? $plan->last_finished_at : null,
                'completed' => (bool) $plan->is_completed, 'children' => $children,
            ];
        }

        $allRows = collect($rows)->flatMap(fn ($row) => array_merge([$row], $row['children'] ?? []));
        $contentIds = $allRows->where('kind', 'content')->pluck('id')->unique();
        $playlistIds = $allRows->where('kind', 'playlist')->pluck('id')->unique();
        $settings = CertificateSetting::where('is_active', 1)->where(function ($query) use ($contentIds, $playlistIds) {
            $query->where(fn ($q) => $q->where('certificate_type', 'content')->whereIn('content_id', $contentIds))
                ->orWhere(fn ($q) => $q->where('certificate_type', 'playlist')->whereIn('trainingplan_id', $playlistIds));
        })->get();
        $eligibility = app(CertificateEligibilityService::class)->checkMany($settings, $this->user);
        $settings = $settings->keyBy(fn ($s) => $s->certificate_type . ':' . ($s->content_id ?? $s->trainingplan_id));
        $scores = DB::table('assesment_results as r')->join('assesments as a', 'a.id', '=', 'r.assesment_id')
            ->where('r.user_id', $this->user->id)->where('r.final', 1)->where('a.status', '!=', 'NOT')
            ->where(fn ($q) => $q->whereIn('a.content_id', $contentIds)->orWhereIn('a.trainingplan_id', $playlistIds))
            ->get(['a.content_id', 'a.trainingplan_id', 'r.score']);

        $output = [$this->headings()];
        foreach ($rows as $index => $row) {
            $this->parentRows[count($output) + 1] = $row['kind'];
            $output[] = $this->excelRow($row, $index + 1, $settings, $eligibility, $scores);
            foreach ($row['children'] ?? [] as $child) {
                $this->childRows[] = count($output) + 1;
                $output[] = $this->excelRow($child, null, $settings, $eligibility, $scores);
            }
        }
        $output[] = array_fill(0, 13, null);
        $total = array_fill(0, 13, null);
        $total[0] = 'TOTAL';
        // Sum only top-level rows; playlist hours are already aggregated by the card service.
        $total[5] = collect($rows)->sum('seconds') / 86400;
        $total[6] = round(collect($rows)->sum('hours'), 4);
        $output[] = $total;
        return $output;
    }

    private function contentRow($content, $record, int $progress, bool $completed, float $hours): array
    {
        // Duration describes the course, as on the Ongoing card. Consumption stays
        // sourced from the existing service and may use its recorded duration.
        $seconds = DurationFormatter::toSeconds($content->duration)
            ?? max(0, (int) ($record?->duration_seconds ?? 0));
        return [
            'id' => $content->id, 'kind' => 'content', 'title' => $content->title,
            'type' => ContentTypeHelper::label($content->type) ?: 'Content',
            'classification' => $content->classification?->classification_name ?? '-',
            'seconds' => $seconds,
            'hours' => $hours, 'progress' => $progress, 'start' => $record?->created_at,
            'finish' => $completed ? ($record?->completed_at ?? $record?->updated_at) : null,
            'completed' => $completed,
        ];
    }

    private function excelRow(array $row, ?int $number, Collection $settings, array $eligibility, Collection $scores): array
    {
        $setting = $settings->get($row['kind'] . ':' . $row['id']);
        $result = $setting ? ($eligibility[$setting->id] ?? null) : null;
        $score = $result?->score() ?? $scores->where($row['kind'] === 'playlist' ? 'trainingplan_id' : 'content_id', $row['id'])->max('score');
        return [$number, $row['title'], $row['type'], ucfirst($row['kind']), $row['classification'],
            $row['seconds'] / 86400, $row['hours'], $row['progress'] / 100,
            $this->dateValue($row['start']), $this->dateValue($row['finish']), $row['completed'] ? null : 'Absent',
            $score === null ? null : (float) $score, ($result?->eligible ?? false) ? 'Available' : 'Not Available'];
    }

    private function dateValue($date): ?float
    {
        return $date ? Date::dateTimeToExcel(Carbon::parse($date)) : null;
    }

    public function bindValue(Cell $cell, $value): bool
    {
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }
        return parent::bindValue($cell, $value);
    }

    public function columnFormats(): array
    {
        return ['F' => '[hh]:mm:ss', 'G' => '0.00', 'H' => '0%', 'I' => 'yyyy-mm-dd hh:mm', 'J' => 'yyyy-mm-dd hh:mm'];
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event) {
            $sheet = $event->sheet->getDelegate();
            $last = $sheet->getHighestRow();
            $sheet->freezePane('A2');
            $sheet->getStyle("A1:M{$last}")->applyFromArray([
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']]],
            ]);
            $sheet->getStyle('A1:M1')->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9EAF7']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
            ]);
            $sheet->getRowDimension(1)->setRowHeight(32);
            foreach (['A' => 7, 'B' => 55, 'C' => 16, 'D' => 14, 'E' => 28, 'F' => 16, 'G' => 16,
                'H' => 19, 'I' => 23, 'J' => 23, 'K' => 16, 'L' => 19, 'M' => 19] as $column => $width) {
                $sheet->getColumnDimension($column)->setWidth($width);
            }
            $sheet->getStyle("A2:A{$last}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B2:E{$last}")->getAlignment()->setWrapText(true);
            foreach ($this->parentRows as $row => $kind) {
                if ($kind === 'playlist') {
                    $sheet->getStyle("A{$row}:M{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EDEDED');
                }
            }
            foreach ($this->childRows as $row) {
                $sheet->getStyle("B{$row}")->getAlignment()->setIndent(1);
                $sheet->getRowDimension($row)->setOutlineLevel(1);
            }
            $sheet->setShowSummaryBelow(false);
            if (!$this->childRows) {
                $sheet->setAutoFilter('A1:M' . max(1, $last - 2));
            }
            $sheet->getStyle("A{$last}:M{$last}")->getFont()->setBold(true);
        }];
    }
}
