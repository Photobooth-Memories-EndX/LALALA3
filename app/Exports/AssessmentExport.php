<?php

namespace App\Exports;

use App\Models\AssesmentResult;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AssessmentExport implements FromArray, ShouldAutoSize, WithEvents, WithStyles
{
    private const MODE_RESULT = 'result';
    private const MODE_MY_LEARNING = 'my-learning';

    private string $mode = self::MODE_RESULT;
    private ?Collection $assessments = null;
    private ?int $userId = null;
    private array $statusRows = [];

    /**
     * Keep the existing assessment-result export constructor compatible.
     */
    public function __construct(private $assessmentId = null)
    {
    }

    public static function forMyLearning(Collection $assessments, int $userId): self
    {
        $export = new self();
        $export->mode = self::MODE_MY_LEARNING;
        $export->assessments = $assessments
            ->sort(function ($left, $right) {
                $typeOrder = ['content' => 0, 'trainingplan' => 1, 'playlist' => 1, 'global' => 2];
                $leftType = $typeOrder[strtolower((string) $left->type)] ?? 99;
                $rightType = $typeOrder[strtolower((string) $right->type)] ?? 99;

                return $leftType === $rightType
                    ? strcasecmp((string) $left->title, (string) $right->title)
                    : $leftType <=> $rightType;
            })
            ->values();
        $export->userId = $userId;

        return $export;
    }

    public function array(): array
    {
        return $this->mode === self::MODE_MY_LEARNING
            ? $this->myLearningRows()
            : $this->resultRows();
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = $this->mode === self::MODE_MY_LEARNING ? 'G' : 'J';
                $lastRow = max(1, $sheet->getHighestRow());
                $range = "A1:{$lastColumn}{$lastRow}";

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:{$lastColumn}1");
                $sheet->getStyle($range)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)
                    ->getColor()->setRGB('000000');
                $sheet->getStyle($range)->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle("A1:{$lastColumn}1")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                if ($this->mode !== self::MODE_MY_LEARNING) {
                    return;
                }

                if ($lastRow >= 2) {
                    $sheet->getDefaultRowDimension()->setRowHeight(-1);
                    $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("C2:G{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("B2:B{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle("B2:B{$lastRow}")->getAlignment()->setWrapText(true);
                    $sheet->getStyle("C2:C{$lastRow}")->getAlignment()->setWrapText(true);
                    $sheet->getStyle("G2:G{$lastRow}")->getAlignment()->setWrapText(true);
                }

                foreach ($this->statusRows as $row => $status) {
                    $color = match ($status) {
                        'Passed' => 'C6EFCE',
                        'Failed' => 'FFC7CE',
                        default => 'FCE4D6',
                    };
                    $sheet->getStyle("G{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB($color);
                }
            },
        ];
    }

    private function myLearningRows(): array
    {
        $rows = [[
            'No',
            'Assessment',
            'Date Time',
            'Type',
            'Batch',
            'Assessment Score',
            'Status',
        ]];

        foreach ($this->assessments ?? collect() as $index => $assessment) {
            $finalResult = collect($assessment->scores ?? [])
                ->where('user_id', $this->userId)
                ->where('final', 1)
                ->sortByDesc('score')
                ->first();
            $type = $assessment->type === 'trainingplan'
                ? 'Playlist'
                : ucfirst((string) ($assessment->type ?? '-'));
            $resultStatus = !$finalResult
                ? 'Not Started'
                : ($finalResult->pass ? 'Passed' : 'Failed');
            $status = $resultStatus === 'Failed' && $assessment->passing_grade !== null
                ? $resultStatus . "\nPassing Grade: " . $assessment->passing_grade
                : $resultStatus;
            $subtitle = $assessment->trainingplan->title ?? 'Assessment';
            $dateTime = ($assessment->timer ?? 'n') === 'y'
                ? 'Start : ' . ($assessment->fromdate ? date('d/m/Y H:i', strtotime($assessment->fromdate)) : '-')
                    . "\nFinish : " . ($assessment->todate ? date('d/m/Y H:i', strtotime($assessment->todate)) : '-')
                : '-';
            $excelRow = $index + 2;
            $this->statusRows[$excelRow] = $resultStatus;

            $rows[] = [
                $index + 1,
                ($assessment->title ?? '-') . "\n" . $subtitle,
                $dateTime,
                $type,
                $finalResult->batch ?? '-',
                $finalResult && $finalResult->score !== null ? $finalResult->score : '-',
                $status,
            ];
        }

        return $rows;
    }

    private function resultRows(): array
    {
        $rows = [['No', 'ID', 'Name', 'Team', 'Department', 'Score', 'Batch', 'Result', 'Start Date Time', 'Finish Date Time']];
        $results = AssesmentResult::with('user')
            ->where('assesment_id', $this->assessmentId)
            ->whereFinal(1)
            ->get();

        foreach ($results as $index => $result) {
            $rows[] = [
                $index + 1,
                $result->user->id_emp,
                $result->user->name,
                $result->user->team,
                $result->user->department,
                $result->score,
                $result->batch,
                $result->pass ? 'Pass' : 'Failed',
                date('d/m/y H:i:s', strtotime($result->created_at)),
                date('d/m/y H:i:s', strtotime($result->updated_at)),
            ];
        }

        return $rows;
    }
}
