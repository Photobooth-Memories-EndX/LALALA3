<?php

namespace App\Exports;

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

class CertificateExport implements FromArray, ShouldAutoSize, WithEvents, WithStyles
{
    public function __construct(private readonly Collection $certificates)
    {
    }

    public function array(): array
    {
        $rows = [[
            'No',
            'Certificate Number',
            'Title / Target',
            'Participant',
            'Certificate Type',
            'Credential ID',
            'Issued Date',
            'Issuer',
            'Score',
            'Status',
            'Verification URL',
        ]];

        foreach ($this->certificates as $index => $certificate) {
            $rows[] = [
                $index + 1,
                $certificate->certificate_number,
                $certificate->title,
                $certificate->participant_name,
                ucfirst((string) $certificate->certificate_type),
                $certificate->credential_id,
                optional($certificate->issued_at)->format('d M Y'),
                optional($certificate->certificateSetting)->issuer_name ?? '-',
                $certificate->score ?? '-',
                'Generated',
                $certificate->credential_url ?: route('certificate.verify', $certificate->credential_id),
            ];
        }

        return $rows;
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
                $lastRow = max(1, $sheet->getHighestRow());
                $range = "A1:K{$lastRow}";

                $sheet->freezePane('A2');
                $sheet->setAutoFilter('A1:K1');
                $sheet->getStyle($range)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)
                    ->getColor()->setRGB('D1D5DB');
                $sheet->getStyle($range)->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('A1:K1')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            },
        ];
    }
}
