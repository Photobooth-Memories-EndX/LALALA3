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
        $export->assessments = $assessments->values();
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
                $lastColumn = $this->mode === self::MODE_MY_LEARNING ? 'I' : 'J';
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
                    $sheet->getStyle("B2:B{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle("B2:B{$lastRow}")->getAlignment()->setWrapText(true);
                    $sheet->getStyle("C2:I{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("C2:C{$lastRow}")->getAlignment()->setWrapText(true);
                    $sheet->getStyle("D2:D{$lastRow}")->getAlignment()->setWrapText(true);
                    $sheet->getStyle("E2:E{$lastRow}")->getAlignment()->setWrapText(true);
                    $sheet->getStyle("H2:H{$lastRow}")->getAlignment()->setWrapText(true);
                    $sheet->getStyle("I2:I{$lastRow}")->getAlignment()->setWrapText(true);
                }

                foreach ($this->statusRows as $row => $status) {
                    $color = match ($status) {
                        'Passed'   => 'C6EFCE',
                        'Failed'   => 'FFC7CE',
                        'Remedial' => 'FFF2CC',
                        'Expired'  => 'E2E3E5',
                        default    => 'FCE4D6', // Pending
                    };
                    $sheet->getStyle("H{$row}")->getFill()
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
            'Type',
            'Category',
            'Date & Time',
            'Score',
            'Passing Grade',
            'Status',
            'Action / Result',
        ]];

        $now = now();

        foreach ($this->assessments ?? collect() as $index => $assessment) {
            $userScores = collect($assessment->scores ?? [])
                ->filter(fn ($score) => (string) ($score->user_id ?? '') === (string) $this->userId);

            $finalResult = $userScores
                ->where('final', 1)
                ->sortByDesc('score')
                ->first();

            if (!$finalResult) {
                $finalResult = $userScores
                    ->filter(fn ($s) => $s->score !== null)
                    ->sortByDesc('score')
                    ->first();
            }

            $userAttempts = $userScores->where('final', 1)->count();
            if ($userAttempts === 0 && $userScores->count() > 0) {
                $userAttempts = $userScores->count();
            }

            $activeDraft = $userScores->where('final', 0)->sortByDesc('id')->first();
            $hasActiveDraft = ($activeDraft !== null);

            $isCompleted = ($finalResult !== null);
            $isPassed = ($finalResult && $finalResult->pass);

            $rawType = strtolower((string) ($assessment->type ?? ''));
            $contentFor = strtolower((string) ($assessment->assasment_content_for ?? 'content'));

            if ($rawType === 'global') {
                $type = 'Global Assessment';
                $category = 'Global';
                $relatedTo = '—';
            } elseif ($rawType === 'trainingplan') {
                $type = 'Playlist Assessment';
                $category = '—';
                $tpTitle = $assessment->trainingplan->title ?? null;
                $cTitle = $assessment->content->title ?? ($assessment->contents->first()->title ?? null);
                $relatedTo = !empty($tpTitle) ? $tpTitle : (!empty($cTitle) ? $cTitle : '—');
            } else {
                $type = 'Content Assessment';
                $cTitle = $assessment->content->title ?? ($assessment->contents->first()->title ?? null);

                if ($contentFor === 'playlist') {
                    $category = 'Playlist';
                    $tpTitle = $assessment->trainingplan->title ?? null;
                    $relatedTo = !empty($tpTitle) ? $tpTitle : (!empty($cTitle) ? $cTitle : '—');
                } else {
                    $category = 'All Content';
                    $relatedTo = !empty($cTitle) ? $cTitle : '—';
                }
            }

            // Date & Time logic aligned with UI
            $hasAccessPeriod = $assessment->hasAccessPeriod();
            $hasTimeLimit = $assessment->hasTimeLimit();
            $isExpired = false;

            if ($hasAccessPeriod && !empty($assessment->fromdate) && !empty($assessment->todate)) {
                $fromDateRaw = strlen(trim((string) $assessment->fromdate)) === 10
                    ? trim((string) $assessment->fromdate) . ' 00:00:00'
                    : (string) $assessment->fromdate;
                $toDateRaw = strlen(trim((string) $assessment->todate)) === 10
                    ? trim((string) $assessment->todate) . ' 23:59:59'
                    : (string) $assessment->todate;

                $fromDateCarbon = \Illuminate\Support\Carbon::parse($fromDateRaw);
                $toDateCarbon = \Illuminate\Support\Carbon::parse($toDateRaw);

                $isExpired = $now->gt($toDateCarbon);
                $dateTime = 'From : ' . $fromDateCarbon->format('d/m/Y H:i') . "\nTo : " . $toDateCarbon->format('d/m/Y H:i');
            } else {
                $dateTime = $hasTimeLimit ? ('Time Limit: ' . $assessment->time_limit_minutes . ' mins') : '-';
            }

            if ($hasActiveDraft && $hasTimeLimit && $activeDraft->created_at) {
                $timeLimitDeadline = $activeDraft->created_at->copy()->addMinutes((int) $assessment->time_limit_minutes);
                if ($now->gt($timeLimitDeadline)) {
                    $isExpired = true;
                }
            }

            $totalRemedials = (int) ($assessment->total_remedials ?? 0);
            $maxAttempts = ($rawType === 'global') ? 1 : (1 + $totalRemedials);

            // Status: Passed, Failed, Remedial, Expired, Pending
            if ($isPassed) {
                $status = 'Passed';
            } elseif ($isExpired) {
                $status = 'Expired';
            } elseif ($isCompleted) {
                if ($rawType !== 'global' && $userAttempts < $maxAttempts) {
                    $status = 'Remedial';
                } else {
                    $status = 'Failed';
                }
            } else {
                $status = 'Pending';
            }

            // Action / Result: Passed, Expired, Continue, Remedial, Failed, Start Test
            if ($isPassed) {
                $actionResult = 'Passed';
            } elseif ($isExpired) {
                $actionResult = 'Expired';
            } elseif ($hasActiveDraft) {
                $actionResult = 'Continue';
            } elseif ($isCompleted) {
                if ($userAttempts >= $maxAttempts && $rawType !== 'global') {
                    $actionResult = 'Failed';
                } elseif ($rawType !== 'global') {
                    $actionResult = 'Remedial';
                } else {
                    $actionResult = 'Failed';
                }
            } else {
                $actionResult = 'Start Test';
            }

            $excelRow = $index + 2;
            $this->statusRows[$excelRow] = $status;

            $assessmentTitleCell = ($relatedTo !== '—' && $relatedTo !== '-')
                ? ($assessment->title ?? '-') . "\nRelated to: " . $relatedTo
                : ($assessment->title ?? '-');

            $scoreValue = ($finalResult && $finalResult->score !== null) ? $finalResult->score : '-';
            $passingGradeValue = ($assessment->passing_grade !== null && $assessment->passing_grade !== '') ? $assessment->passing_grade : '-';

            $rows[] = [
                $index + 1,
                $assessmentTitleCell,
                $type,
                $category,
                $dateTime,
                $scoreValue,
                $passingGradeValue,
                $status,
                $actionResult,
            ];
        }

        return $rows;
    }

    private function resultRows(): array
    {
        $rows = [['No', 'ID', 'Name', 'Team', 'Department', 'Score', 'Batch', 'Result', 'Start Date Time', 'Finish Date Time']];
        $assessment = Assesment::find($this->assessmentId);
        if ($assessment) {
            app(\App\Services\AssessmentAccessService::class)->finalizeExpiredDraftsForAssessment($assessment);
        }

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
