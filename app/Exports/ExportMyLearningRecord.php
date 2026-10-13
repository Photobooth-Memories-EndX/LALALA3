<?php

namespace App\Exports;

use App\Http\Controllers\LearningRecordController;
use App\Services\CertificateEligibilityService;
use App\Services\Learning\LearningRecordPlaylistExportService;
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
    private array $parentPlaylistRows = [];
    private array $childRows = [];

    public function __construct(private readonly array $filters = [])
    {
        $this->rows = collect();
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
            $duration = DurationFormatter::fromSeconds($durationSeconds);

            $lhValue = (float) ($row['learning_hour_value'] ?? 0);
            $learningHour = $lhValue > 0 ? (number_format($lhValue, 2) . ' hrs') : '-';

            $progress = isset($row['progress']) ? ((int) $row['progress'] . '%') : '-';

            $start = !empty($row['start_date']) ? Carbon::parse($row['start_date'])->format('d/m/y H:i:s') : '';
            $finish = !empty($row['finish_date']) ? Carbon::parse($row['finish_date'])->format('d/m/y H:i:s') : '';
            if ($start !== '' && $finish !== '') {
                $dateTime = "Start: {$start}\nFinish: {$finish}";
            } elseif ($finish !== '') {
                $dateTime = "Finish: {$finish}";
            } elseif ($start !== '') {
                $dateTime = "Start: {$start}";
            } else {
                $dateTime = '-';
            }

            $rawProgress = $row['progress'] ?? null;
            $isValidProgress = !is_null($rawProgress) && is_numeric($rawProgress);
            $attendance = $row['attendance'] ?? (($isValidProgress && (int) $rawProgress >= 100) ? 'Present' : '-');

            $score = ($row['score'] !== null && $row['score'] !== '') ? $row['score'] : '-';
            $certificate = ($row['cert_state'] ?? 'none') === 'ready' ? 'Available' : 'Not Available';

            $isPlaylist = ($row['row_kind'] ?? '') === 'playlist' || ($row['type'] ?? '') === 'Playlist';
            $playlistId = $row['plan_id'] ?? $row['playlist_id'] ?? ($row['playlist_ids'][0] ?? null);
            if (!$playlistId && isset($row['item_id']) && str_starts_with((string) $row['item_id'], 'playlist_')) {
                $playlistId = (int) str_replace('playlist_', '', $row['item_id']);
            }

            $output[] = [
                $number++,
                $row['title'] ?? '-',
                $row['type'] ?? '-',
                $row['category'] ?? '-',
                (!empty($row['classification']) && $row['classification'] !== '-') ? $row['classification'] : '-',
                $duration,
                $learningHour,
                $progress,
                $dateTime,
                $attendance,
                $score,
                $certificate,
            ];

            if ($isPlaylist) {
                $this->parentPlaylistRows[] = count($output);

                if ($playlistId && $childrenMap->has((int) $playlistId)) {
                    $children = $childrenMap->get((int) $playlistId);
                    foreach ($children as $child) {
                        $output[] = [
                            '',
                            '    ' . ($child['title'] ?? '-'),
                            $child['type'] ?? '-',
                            'Playlist',
                            $child['classification'] ?? '-',
                            $child['duration'] ?? '-',
                            $child['learning_hour'] ?? '-',
                            $child['progress_formatted'] ?? '-',
                            $child['date_time_kp'] ?? '-',
                            $child['attendance'] ?? '-',
                            $child['score'] ?? '-',
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
            'Type',
            'Category',
            'Classification',
            'Duration',
            'Learning Hour',
            'Progress',
            'Date Time',
            'Attendance',
            'Assessment Score',
            'Certificate',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D9EAF7'],
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
                    $sheet->getStyle('C2:D' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('H2:H' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('I2:I' . $highestRow)->getAlignment()->setWrapText(true);
                    $sheet->getStyle('J2:L' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                foreach ($this->parentPlaylistRows as $r) {
                    $sheet->getStyle("A{$r}:L{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EDEDED');
                    $sheet->getStyle("A{$r}:L{$r}")->getFont()->setBold(true);
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
