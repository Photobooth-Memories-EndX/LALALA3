<?php

namespace App\Exports;

use App\Http\Controllers\LearningRecordController;
use App\Models\Content;
use App\Services\CertificateEligibilityService;
use App\Services\Learning\LearningRecordPlaylistExportService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExportMyLearningRecord implements FromArray, ShouldAutoSize, WithColumnFormatting, WithEvents, WithStyles
{
    private Collection $rows;
    private array $parentPlaylistRows = [];
    private array $childRows = [];

    public function __construct(private readonly array $filters = [])
    {
        $this->rows = collect();
    }

    private function toExcelTime(?string $dateTimeOrTime): float|string
    {
        if (empty($dateTimeOrTime) || $dateTimeOrTime === '-' || $dateTimeOrTime === '—') {
            return '-';
        }

        try {
            $dt = Carbon::parse($dateTimeOrTime);
            return (($dt->hour * 3600) + ($dt->minute * 60) + $dt->second) / 86400;
        } catch (\Throwable) {
            return '-';
        }
    }

    public function array(): array
    {
        $this->parentPlaylistRows = [];
        $this->childRows = [];

        $user = Auth::user();
        if (!$user) {
            return [$this->headings()];
        }

        $controller = app(LearningRecordController::class);
        $eligibilityService = app(CertificateEligibilityService::class);
        $allRows = $controller->getRawOnlineRows((int) $user->id, $eligibilityService);
        $this->rows = $controller->applyRecordFilters($allRows, $this->filters);

        // Batch load playlist children (Zero N+1)
        $playlistIds = $this->rows->filter(function ($row) {
            return ($row['row_kind'] ?? '') === 'playlist' || ($row['type'] ?? '') === 'Playlist';
        })->map(function ($row) {
            $pid = $row['plan_id'] ?? $row['playlist_id'] ?? ($row['playlist_ids'][0] ?? null);
            if (!$pid && isset($row['item_id']) && str_starts_with((string) $row['item_id'], 'playlist_')) {
                $pid = (int) str_replace('playlist_', '', $row['item_id']);
            }
            return $pid ? (int) $pid : null;
        })->filter()->unique()->values()->all();

        $playlistChildrenService = app(LearningRecordPlaylistExportService::class);
        $childrenMap = $playlistChildrenService->getPlaylistChildrenBatch((int) $user->id, $playlistIds);

        $output = [$this->headings()];
        $number = 1;

        foreach ($this->rows as $row) {
            $durationSeconds = max(0, (int) ($row['duration_sort'] ?? 0));
            $durationCell = $durationSeconds > 0 ? ($durationSeconds / 86400) : '-';

            $lhValue = round((float) ($row['learning_hour_value'] ?? 0), 2);

            $rawProgress = $row['progress'] ?? 0;
            $progressInt = is_numeric($rawProgress) ? max(0, min(100, (int) $rawProgress)) : 0;
            $progressCell = $progressInt / 100;
            $completionStatus = $progressInt >= 100 ? 'Complete' : 'Ongoing';

            $startRaw = $row['start_date'] ?? '';
            $finishRaw = $row['finish_date'] ?? '';
            $startDate = $startRaw !== '' ? Carbon::parse($startRaw)->format('Y-m-d') : '-';
            $endDate = $finishRaw !== '' ? Carbon::parse($finishRaw)->format('Y-m-d') : '-';
            $startTime = $this->toExcelTime($startRaw);
            $endTime = $this->toExcelTime($finishRaw);

            $attendance = $row['attendance'] ?? ($progressInt >= 100 ? 'Present' : '-');
            $requirement = $row['requirement'] ?? Content::mapRequirement($row['subtitle'] ?? null);

            $hasScore = ($row['score'] !== null && $row['score'] !== '' && is_numeric($row['score']));
            $scoreVal = $hasScore ? (float) $row['score'] : '-';
            $minScore = (float) ($row['min_score'] ?? 70);
            $assessmentStatus = $hasScore
                ? ($scoreVal >= $minScore ? 'Passed' : 'Failed')
                : '-';

            $certificate = ($row['cert_state'] ?? 'none') === 'ready' ? 'Available' : 'Not Available';

            $isPlaylist = ($row['row_kind'] ?? '') === 'playlist' || ($row['type'] ?? '') === 'Playlist';
            $playlistId = $row['plan_id'] ?? $row['playlist_id'] ?? ($row['playlist_ids'][0] ?? null);
            if (!$playlistId && isset($row['item_id']) && str_starts_with((string) $row['item_id'], 'playlist_')) {
                $playlistId = (int) str_replace('playlist_', '', $row['item_id']);
            }

            $output[] = [
                $number++,
                $row['title'] ?? '-',
                $requirement,
                $row['type'] ?? '-',
                $row['category'] ?? '-',
                (!empty($row['classification']) && $row['classification'] !== '-') ? $row['classification'] : '-',
                $durationCell,
                $lhValue,
                $completionStatus,
                $progressCell,
                $attendance,
                $startDate,
                $endDate,
                $startTime,
                $endTime,
                $assessmentStatus,
                $scoreVal,
                $certificate,
            ];

            if ($isPlaylist) {
                $this->parentPlaylistRows[] = count($output);

                if ($playlistId && $childrenMap->has((int) $playlistId)) {
                    $children = $childrenMap->get((int) $playlistId);
                    foreach ($children as $child) {
                        $childDurSec = max(0, (int) ($child['duration_seconds'] ?? 0));
                        $childDurCell = $childDurSec > 0 ? ($childDurSec / 86400) : '-';
                        $childLhVal = round((float) ($child['learning_hour_value'] ?? 0), 2);
                        $childProgInt = max(0, min(100, (int) ($child['progress'] ?? 0)));
                        $childScore = ($child['score'] !== null && $child['score'] !== '' && $child['score'] !== '-' && is_numeric($child['score']))
                            ? (float) $child['score']
                            : '-';

                        $output[] = [
                            '',
                            '    ' . ($child['title'] ?? '-'),
                            $child['requirement'] ?? 'Non-Mandatory',
                            $child['type'] ?? '-',
                            'Playlist',
                            $child['classification'] ?? '-',
                            $childDurCell,
                            $childLhVal,
                            $child['completion_status'] ?? ($childProgInt >= 100 ? 'Complete' : 'Ongoing'),
                            $childProgInt / 100,
                            $child['attendance'] ?? '-',
                            $child['start_date'] ?? '-',
                            $child['end_date'] ?? '-',
                            $this->toExcelTime($child['start_time'] ?? null),
                            $this->toExcelTime($child['end_time'] ?? null),
                            $child['assessment_status'] ?? '-',
                            $childScore,
                            '-',
                        ];
                        $this->childRows[] = count($output);
                    }
                }
            }
        }

        return $output;
    }

    public function headings(): array
    {
        return [
            'No',
            'Course',
            'Requirement',
            'Type',
            'Category',
            'Classification',
            'Duration',
            'Learning Hour',
            'Completion Status',
            'Progress',
            'Attendance',
            'Start Date',
            'End Date',
            'Start Time',
            'End Time',
            'Assessment Status',
            'Assessment Score',
            'Certificate',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_NUMBER,
            'B' => NumberFormat::FORMAT_GENERAL,
            'C' => NumberFormat::FORMAT_GENERAL,
            'D' => NumberFormat::FORMAT_GENERAL,
            'E' => NumberFormat::FORMAT_GENERAL,
            'F' => NumberFormat::FORMAT_GENERAL,
            'G' => NumberFormat::FORMAT_DATE_TIME4,
            'H' => NumberFormat::FORMAT_NUMBER_00,
            'I' => NumberFormat::FORMAT_GENERAL,
            'J' => NumberFormat::FORMAT_PERCENTAGE,
            'K' => NumberFormat::FORMAT_GENERAL,
            'L' => NumberFormat::FORMAT_GENERAL,
            'M' => NumberFormat::FORMAT_GENERAL,
            'N' => NumberFormat::FORMAT_DATE_TIME4,
            'O' => NumberFormat::FORMAT_DATE_TIME4,
            'P' => NumberFormat::FORMAT_GENERAL,
            'Q' => NumberFormat::FORMAT_GENERAL,
            'R' => NumberFormat::FORMAT_GENERAL,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold'  => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '002B49'],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->freezePane('A2');
                $highestColumn = $sheet->getHighestColumn();
                $highestRow = max(1, $sheet->getHighestRow());

                $sheet->getStyle('A1:' . $highestColumn . '1')
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle('A1:' . $highestColumn . $highestRow)
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);

                if ($highestRow >= 2) {
                    $sheet->getStyle('A2:A' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('C2:E' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('G2:R' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                foreach ($this->parentPlaylistRows as $r) {
                    $sheet->getStyle("A{$r}:{$highestColumn}{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F1F5F9');
                    $sheet->getStyle("A{$r}:{$highestColumn}{$r}")->getFont()->setBold(true);
                }

                foreach ($this->childRows as $r) {
                    $sheet->getStyle("B{$r}")->getAlignment()->setIndent(1);
                    $sheet->getRowDimension($r)->setOutlineLevel(1);
                }

                $sheet->setShowSummaryBelow(false);
            },
        ];
    }

    public function getRows(): Collection
    {
        return $this->rows;
    }
}
