<?php

namespace App\Exports;

use App\Services\CertificateEligibilityService;
use App\Services\Learning\LearningRecordPlaylistExportService;
use App\Services\Learning\UserRecordAllService;
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

class ExportMyAllLearningRecord implements FromArray, ShouldAutoSize, WithEvents, WithStyles
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

        $allService = app(UserRecordAllService::class);
        $eligibilityService = app(CertificateEligibilityService::class);
        $this->rows = $allService->getRecords((int) $user->id, $user->id_emp, $eligibilityService, $this->filters);

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
            $title = !empty($row['training_title']) ? $row['training_title'] : (!empty($row['title']) ? $row['title'] : '-');
            $duration = (!empty($row['duration']) && $row['duration'] !== '—') ? $row['duration'] : '-';
            $learningHour = !empty($row['learning_hour']) ? $row['learning_hour'] : '-';
            $progress = isset($row['progress']) ? ((int) $row['progress'] . '%') : '-';
            $attendance = (!empty($row['attendance_status']) && $row['attendance_status'] !== '—') ? $row['attendance_status'] : '-';
            $date = (!empty($row['date_display']) && $row['date_display'] !== '—') ? $row['date_display'] : '-';
            $score = ($row['score'] !== null && $row['score'] !== '') ? $row['score'] : '-';
            $certificate = (($row['has_certificate'] ?? false) || ($row['cert_state'] ?? '') === 'ready') ? 'Available' : 'Not Available';

            $isPlaylist = ($row['row_kind'] ?? '') === 'playlist' || ($row['type'] ?? '') === 'Playlist';
            $playlistId = $row['plan_id'] ?? $row['playlist_id'] ?? ($row['playlist_ids'][0] ?? null);
            if (!$playlistId && isset($row['item_id']) && str_starts_with((string) $row['item_id'], 'playlist_')) {
                $playlistId = (int) str_replace('playlist_', '', $row['item_id']);
            }

            $output[] = [
                $number++,
                $title,
                $row['type'] ?? '-',
                $row['category'] ?? '-',
                $row['classification'] ?? '-',
                $duration,
                $learningHour,
                $progress,
                $attendance,
                $date,
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
                            $child['attendance'] ?? '-',
                            $child['date_display_all'] ?? '-',
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
            'Training Title',
            'Type',
            'Category',
            'Classification',
            'Duration',
            'Learning Hour',
            'Progress',
            'Attendance',
            'Date',
            'Score',
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
                    'startColor' => ['rgb' => 'EDE9FE'],
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

                if ($highestRow > 1) {
                    $sheet->getStyle('A2:A' . $highestRow)
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('C2:D' . $highestRow)
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('H2:L' . $highestRow)
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
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
