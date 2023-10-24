<?php

namespace App\Exports;

use Illuminate\View\View;
use App\Models\ContentUser;
use App\Models\Classification;
use App\Models\AssesmentResult;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AssessmentExport implements FromView, ShouldAutoSize, WithStyles
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

        $assesment_result = AssesmentResult::with('user')->where('assesment_id', $this->id)->get();
        $this->total = count($assesment_result);
        return view('main.report.assessmentdownload', [
            'assesment_result' => $assesment_result
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
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $sheet->getStyle('A1:H' . $this->total + 1)->applyFromArray($styleArray);
    }
}
