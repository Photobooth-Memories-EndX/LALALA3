<?php

namespace App\Exports;

use App\Http\Controllers\LearningRecordController;
use App\Services\CertificateEligibilityService;
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

        $controller = app(LearningRecordController::class);
        $eligibilityService = app(CertificateEligibilityService::class);
        $allRows = $controller->getRawOnlineRows((int) $user->id, $eligibilityService);
        $this->rows = $controller->applyRecordFilters($allRows, $this->filters);

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

            $score = ($row['score'] !== null && $row['score'] !== '') ? $row['score'] : '-';
            $certificate = ($row['cert_state'] ?? 'none') === 'ready' ? 'Available' : 'Not Available';

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
            'Course',
            'Type',
            'Category',
            'Classification',
            'Duration',
            'Learning Hour',
            'Progress',
            'Date Time',
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
                    $sheet->getStyle('J2:K' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
            },
        ];
    }

    public function getRows(): Collection
    {
        return $this->rows;
    }
}
