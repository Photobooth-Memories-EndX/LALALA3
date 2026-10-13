<?php

namespace App\Exports;

use App\Services\CertificateEligibilityService;
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

        $allService = app(UserRecordAllService::class);
        $eligibilityService = app(CertificateEligibilityService::class);
        $this->rows = $allService->getRecords((int) $user->id, $user->id_emp, $eligibilityService, $this->filters);

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
                    $sheet->getStyle('H2:I' . $highestRow)
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('K2:L' . $highestRow)
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
            },
        ];
    }
}
