<?php

namespace App\Exports;

use Illuminate\View\View;
use App\Models\ContentUser;
use App\Models\Classification;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LearningExport implements FromView, ShouldAutoSize, WithStyles
{
    /**
     * @return \Illuminate\Support\Collection
     */
    protected $type;
    protected $status;
    protected $year;
    protected $slug;
    protected $total;
    // public $total;

    function __construct($type, $status, $year, $slug)
    {
        $this->type = $type;
        $this->status = $status;
        $this->year = $year;
        $this->slug = $slug;
        $this->total = 0;
    }
    public function view(): View
    {
        $requestyear =  $this->year;
        $requeststatus = $this->status;
        $requesttype = $this->type;
        $requestslug = $this->slug;
        if ($requesttype == 'all') {
            if ($requeststatus == 'all' && $requestyear == 'all') {
                $content = ContentUser::with(['user', 'content', 'content.classification'])->get();
            } else if ($requeststatus == 'all') {
                $content = ContentUser::with(['user', 'content', 'content.classification'])->whereYear('created_at', $requestyear)->get();
            } else if ($requestyear == 'all') {
                $content = ContentUser::with(['user', 'content', 'content.classification'])->where('status', $requeststatus)->get();
            } else {
                $content = ContentUser::with(['user', 'content', 'content.classification'])->where('status', $requeststatus)->whereYear('created_at', $requestyear)->get();
            }
        } else if ($requesttype == 'category') {
            $classification = Classification::firstWhere('slug', $requestslug);
            $classification_id = $classification->id;
            if ($requeststatus == 'all' && $requestyear == 'all') {
                $content = ContentUser::with(['user', 'content.classification'])->whereHas('content', function ($query) use ($classification_id) {
                    $query->where('classification_id', $classification_id);
                })->get();
            } else if ($requeststatus == 'all') {
                $content = ContentUser::with(['user', 'content.classification'])->whereYear('created_at', $requestyear)->whereHas('content', function ($query) use ($classification_id) {
                    $query->where('classification_id', $classification_id);
                })->get();
            } else if ($requestyear == 'all') {
                $content = ContentUser::with(['user', 'content.classification'])->where('status', $requeststatus)->whereHas('content', function ($query) use ($classification_id) {
                    $query->where('classification_id', $classification_id);
                })->get();
            } else {
                $content = ContentUser::with(['user', 'content.classification'])->where('status', $requeststatus)->whereYear('created_at', $requestyear)->whereHas('content', function ($query) use ($classification_id) {
                    $query->where('classification_id', $classification_id);
                })->get();
            }
        } else if ($requesttype == 'content') {
            if ($requeststatus == 'all' && $requestyear == 'all') {
                $content = ContentUser::with(['user', 'content.classification'])->whereHas('content', function ($query) use ($requestslug) {
                    $query->where('slug', $requestslug);
                })->get();
            } else if ($requeststatus == 'all') {
                $content = ContentUser::with(['user', 'content.classification'])->whereYear('created_at', $requestyear)->whereHas('content', function ($query) use ($requestslug) {
                    $query->where('slug', $requestslug);
                })->get();
            } else if ($requestyear == 'all') {
                $content = ContentUser::with(['user', 'content.classification'])->where('status', $requeststatus)->whereHas('content', function ($query) use ($requestslug) {
                    $query->where('slug', $requestslug);
                })->get();
            } else {
                $content = ContentUser::with(['user', 'content.classification'])->where('status', $requeststatus)->whereYear('created_at', $requestyear)->whereHas('content', function ($query) use ($requestslug) {
                    $query->where('slug', $requestslug);
                })->get();
            }
        }
        $this->total = count($content);
        return view('main.report.learningdownload', [
            'content' => $content
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
