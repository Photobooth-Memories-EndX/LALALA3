<?php

namespace App\Exports;

use App\Models\Question;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class QuestionBankQuestionsExport implements FromView, ShouldAutoSize, WithStyles
{
    public $question_bank_id;
    public $total;

    public function __construct($question_bank_id)
    {
        $this->question_bank_id = $question_bank_id;
        $this->total            = 0;
    }

    public function view(): View
    {
        $questions   = Question::where('question_bank_id', $this->question_bank_id)->get();
        $this->total = $questions->count();

        return view('main.question_bank.questions.excel_questions_download', compact('questions'));
    }

    public function styles(Worksheet $sheet)
    {
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '00000000'],
                ],
            ],
        ];
        $sheet->getStyle('A1:D1')->getFont()->setBold(true);
        $sheet->getStyle('A1:D' . $this->total + 1)->applyFromArray($styleArray);
    }
}
