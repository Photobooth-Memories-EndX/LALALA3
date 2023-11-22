<?php

namespace App\Exports;

use App\Models\AssesmentQuestion;
use Illuminate\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AssessmentQuestionExport implements FromView, ShouldAutoSize, WithStyles
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        //
    }
    function __construct($id)
    {
        $this->id = $id;
        $this->total = 0;
    }
    public function view(): View
    {

        $assesment_question = AssesmentQuestion::where('assesment_id', $this->id)->get();
        $this->total = count($assesment_question);
        return view('main.report.assessmentquestiondownload', [
            'assesment_question' => $assesment_question
        ]);
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
