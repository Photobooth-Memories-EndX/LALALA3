<?php

namespace App\Exports;

use App\Services\Learning\LearningDatatableService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LearningExport implements FromView, ShouldAutoSize, WithStyles
{
    protected array $filters = [];
    protected string $type = '';
    protected string $slug = '';
    protected int $total = 0;

    public function __construct($filters = [], $typeOrStatus = '', $yearOrSlug = '', $slug = '')
    {
        if (is_array($filters)) {
            $this->filters = $filters;
            $this->type = (string) $typeOrStatus;
            $this->slug = (string) $yearOrSlug;
        } elseif ($filters instanceof Request) {
            $this->filters = $filters->all();
            $this->type = (string) ($typeOrStatus ?: $filters->input('type', ''));
            $this->slug = (string) ($yearOrSlug ?: $filters->input('slug', ''));
        } else {
            // Legacy signature: ($type, $status, $year, $slug)
            $this->type = (string) $filters;
            $this->filters = [
                'status' => $typeOrStatus,
                'year' => $yearOrSlug,
            ];
            $this->slug = (string) $slug;
        }

        $this->total = 0;
    }

    public function view(): View
    {
        $query = LearningDatatableService::buildBaseQuery($this->filters, $this->type, $this->slug);

        if (empty($this->filters['sort'])) {
            $query->orderBy('unified.created_at', 'desc');
        }

        $content = $query->get();
        $this->total = count($content);

        return view('main.report.learningdownload', ['content' => $content]);
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

        $sheet->getStyle('A1:L1')->getFont()->setBold(true);
        if ($this->total > 0 && $this->total <= 2500) {
            $sheet->getStyle('A1:L' . ($this->total + 1))->applyFromArray($styleArray);
        }
    }
}
