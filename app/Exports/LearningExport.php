<?php

namespace App\Exports;

use App\Services\Learning\LearningDatatableService;
use App\Support\DurationFormatter;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipStream\Option\Archive as ArchiveOptions;
use ZipStream\ZipStream;

class LearningExport implements FromQuery, WithHeadings, WithMapping, WithChunkReading, WithStyles, WithColumnWidths, Responsable
{
    protected array $filters = [];
    protected string $type = '';
    protected string $slug = '';
    protected int $rowNumber = 0;

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

        $this->rowNumber = 0;
    }

    public function query()
    {
        $query = LearningDatatableService::buildBaseQuery($this->filters, $this->type, $this->slug);

        if (empty($this->filters['sort'])) {
            $query->orderBy('unified.created_at', 'desc')
                  ->orderBy('unified.source_type', 'desc')
                  ->orderBy('unified.source_id', 'desc');
        }

        return $query;
    }

    public function view(): \Illuminate\View\View
    {
        $content = $this->query()->get();

        return view('main.report.learningdownload', ['content' => $content]);
    }

    public function headings(): array
    {
        return [
            '#',
            'Status',
            'Employee ID',
            'Name',
            'Department',
            'Team',
            'Title',
            'Type',
            'Classification',
            'Category',
            'Duration',
            'Date',
            'Progress',
        ];
    }

    public function map($row): array
    {
        $this->rowNumber++;

        $title = $row->content_title ?? '-';
        $category = $row->category_source ?? 'All Content';
        if ($category === 'Playlist' && !empty($row->playlist_title)) {
            $category = 'Playlist: ' . $row->playlist_title;
        }

        $rawType = (string) ($row->content_type ?? '');
        $type = in_array(strtolower($rawType), ['video', 'youtube', 'mooc'], true) ? 'Video' : 'Document';

        return [
            $this->rowNumber,
            $row->status ?? '-',
            $row->user_id_emp ?? '-',
            $row->user_name ?? '-',
            $row->user_department ?? '-',
            $row->user_team ?? '-',
            $title,
            $type,
            $row->classification_name ?? '-',
            $category,
            DurationFormatter::format($row->content_duration ?? null),
            $row->created_at,
            ($row->progress ?? 0) . '%',
        ];
    }

    public function chunkSize(): int
    {
        return 2000;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 12,
            'C' => 15,
            'D' => 25,
            'E' => 20,
            'F' => 20,
            'G' => 45,
            'H' => 14,
            'I' => 25,
            'J' => 15,
            'K' => 12,
            'L' => 20,
            'M' => 10,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function download(?string $fileName = null): BinaryFileResponse
    {
        $fileName = $fileName ?? (now()->format('Ymd_His') . '_learning_data.xlsx');
        $tempPath = $this->generateXlsxFile();

        return response()->download(
            $tempPath,
            $fileName,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        )->deleteFileAfterSend(true);
    }

    public function toResponse($request)
    {
        return $this->download();
    }

    public function generateXlsxFile(): string
    {
        $tempXlsx = tempnam(sys_get_temp_dir(), 'xlsx_export_') . '.xlsx';
        $opt = new ArchiveOptions();
        $opt->setSendHttpHeaders(false);
        $fh = fopen($tempXlsx, 'w+b');
        $opt->setOutputStream($fh);

        $zip = new ZipStream(null, $opt);

        // 1. [Content_Types].xml
        $zip->addFile('[Content_Types].xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>'
        );

        // 2. _rels/.rels
        $zip->addFile('_rels/.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>'
        );

        // 3. xl/_rels/workbook.xml.rels
        $zip->addFile('xl/_rels/workbook.xml.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>'
        );

        // 4. xl/workbook.xml
        $zip->addFile('xl/workbook.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Learning Report" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>'
        );

        // 5. xl/styles.xml
        $zip->addFile('xl/styles.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="2">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '</fills>'
            . '<borders count="1">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '</borders>'
            . '<cellStyleXfs count="1">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>'
            . '</cellStyleXfs>'
            . '<cellXfs count="2">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '</cellXfs>'
            . '</styleSheet>'
        );

        // 6. Temporary file stream for sheet1.xml
        $sheetFile = tempnam(sys_get_temp_dir(), 'xlsx_sheet_');
        $sfh = fopen($sheetFile, 'w+b');

        fwrite($sfh, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n");
        fwrite($sfh, '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' . "\n");
        fwrite($sfh, '<cols>' . "\n");
        foreach ($this->columnWidths() as $colLetter => $w) {
            $colIdx = Coordinate::columnIndexFromString($colLetter);
            fwrite($sfh, "<col min=\"{$colIdx}\" max=\"{$colIdx}\" width=\"{$w}\" customWidth=\"1\"/>\n");
        }
        fwrite($sfh, '</cols>' . "\n");
        fwrite($sfh, '<sheetData>' . "\n");

        // Header row
        $headers = $this->headings();
        $colLetters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M'];
        fwrite($sfh, '<row r="1">' . "\n");
        foreach ($headers as $i => $h) {
            $c = $colLetters[$i] . '1';
            $val = $this->escapeXml($h);
            fwrite($sfh, "<c r=\"{$c}\" t=\"inlineStr\" s=\"1\"><is><t>{$val}</t></is></c>\n");
        }
        fwrite($sfh, '</row>' . "\n");

        // Single unbuffered streaming query execution to prevent timeout & memory bloat
        $query = $this->query();
        $rowCount = 0;

        $pdo = DB::connection()->getPdo();
        $wasBuffered = $pdo->getAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY);
        $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

        $buffer = '';
        $batchSize = 2000;

        try {
            $stmt = $pdo->prepare($query->toSql());
            $stmt->execute($query->getBindings());

            while ($row = $stmt->fetch(\PDO::FETCH_OBJ)) {
                $rowCount++;
                $r = $rowCount + 1;

                $title = $row->content_title ?? '-';
                $category = $row->category_source ?? 'All Content';
                if ($category === 'Playlist' && !empty($row->playlist_title)) {
                    $category = 'Playlist: ' . $row->playlist_title;
                }

                $rawType = (string) ($row->content_type ?? '');
                $type = in_array(strtolower($rawType), ['video', 'youtube', 'mooc'], true) ? 'Video' : 'Document';

                $duration = DurationFormatter::format($row->content_duration ?? null);
                $progress = ($row->progress ?? 0) . '%';

                $data = [
                    $row->status ?? '-',
                    $row->user_id_emp ?? '-',
                    $row->user_name ?? '-',
                    $row->user_department ?? '-',
                    $row->user_team ?? '-',
                    $title,
                    $type,
                    $row->classification_name ?? '-',
                    $category,
                    $duration,
                    $row->created_at,
                    $progress,
                ];

                $rowXml = "<row r=\"{$r}\"><c r=\"A{$r}\"><v>{$rowCount}</v></c>";
                foreach ($data as $idx => $val) {
                    $coord = $colLetters[$idx + 1] . $r;
                    $escaped = $this->escapeXml($val);
                    $rowXml .= "<c r=\"{$coord}\" t=\"inlineStr\"><is><t>{$escaped}</t></is></c>";
                }
                $rowXml .= "</row>\n";

                $buffer .= $rowXml;
                if ($rowCount % $batchSize === 0) {
                    fwrite($sfh, $buffer);
                    $buffer = '';
                }
            }

            if ($buffer !== '') {
                fwrite($sfh, $buffer);
                $buffer = '';
            }

            $stmt->closeCursor();
        } finally {
            $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $wasBuffered);
        }

        fwrite($sfh, '</sheetData>' . "\n");
        fwrite($sfh, '</worksheet>' . "\n");
        rewind($sfh);

        $zip->addFileFromStream('xl/worksheets/sheet1.xml', $sfh);
        fclose($sfh);
        @unlink($sheetFile);

        $zip->finish();
        fclose($fh);

        return $tempXlsx;
    }

    protected function escapeXml($value): string
    {
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string) $value);
        return htmlspecialchars($clean, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}

