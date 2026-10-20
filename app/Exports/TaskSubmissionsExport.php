<?php

namespace App\Exports;

use App\Models\Task;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TaskSubmissionsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected Task $task;
    protected $submissions;

    protected int $index = 0;

    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->task->load(['fields', 'linkedPlan']);
        $this->submissions = $task->submissions()
            ->with('user')
            ->orderByDesc('submitted_at')
            ->get();
    }

    public function collection()
    {
        return $this->submissions;
    }

    public function headings(): array
    {
        $headings = [
            'No',
            'Employee Name',
            'Email',
            'Submission Date',
            'Status',
            'Score',
            'Admin Feedback',
        ];

        if ($this->task->isCourseAssignment()) {
            $headings[] = 'Course Completion';
        } else {
            foreach ($this->task->fields as $field) {
                $headings[] = $field->label;
            }
        }

        return $headings;
    }

    /**
     * @param \App\Models\TaskSubmission $submission
     */
    public function map($submission): array
    {
        $this->index++;

        $row = [
            $this->index,
            $submission->user?->name ?? ('User #' . $submission->user_id),
            $submission->user?->email ?? '-',
            $submission->submitted_at ? $submission->submitted_at->format('d/m/Y H:i') : '-',
            ucfirst($submission->status ?? 'pending'),
            $submission->score !== null ? $submission->score : '-',
            $submission->feedback ?? '-',
        ];

        if ($this->task->isCourseAssignment()) {
            $row[] = 'Course Confirmed';
        } else {
            $answersByField = $submission->answersByField();
            foreach ($this->task->fields as $field) {
                $answer = $answersByField->get($field->id);
                if (! $answer) {
                    $row[] = '-';
                    continue;
                }

                $parts = [];
                if ($answer->hasFiles()) {
                    foreach ($answer->filePaths() as $p) {
                        $parts[] = basename($p);
                    }
                }
                if ($answer->answer_text) {
                    $parts[] = $answer->answer_text;
                }

                $row[] = ! empty($parts) ? implode("\n", $parts) : '-';
            }
        }

        return $row;
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = count($this->submissions) + 1;
        $lastCol = $sheet->getHighestColumn();

        $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$lastCol}1")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle("A1:{$lastCol}1")->getFill()->getStartColor()->setARGB('FFE9ECEF');

        if (count($this->submissions) > 0) {
            $sheet->getStyle("A1:{$lastCol}{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(
                \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
            );

            $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal(
                \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
            );
            $sheet->getStyle("E2:F{$lastRow}")->getAlignment()->setHorizontal(
                \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
            );
        }
    }
}
