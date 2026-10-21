<?php

namespace App\Exports\Record;

use App\Services\Record\OnlineFullRecordExportService;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipStream\Option\Archive as ArchiveOptions;
use ZipStream\ZipStream;

class OnlineFullRecordExport implements FromQuery, WithHeadings, WithChunkReading, WithColumnWidths, Responsable
{
    protected array $filters = [];
    protected int $rowNumber = 0;
    protected string $fileName;
    protected OnlineFullRecordExportService $service;

    public function __construct($filters = [], ?string $fileName = null)
    {
        $this->filters = $filters instanceof Request ? $filters->all() : (array) $filters;
        $this->rowNumber = 0;
        $this->fileName = $fileName ?? sprintf('kp-owl-online-records-%s.xlsx', now()->format('Y-m-d'));
        $this->service = app(OnlineFullRecordExportService::class);
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function query()
    {
        return $this->service->buildQuery($this->filters);
    }

    public function headings(): array
    {
        return OnlineFullRecordExportService::headings();
    }

    public function chunkSize(): int
    {
        return 2000;
    }

    public function columnWidths(): array
    {
        return OnlineFullRecordExportService::columnWidths();
    }

    public function download(?string $fileName = null): BinaryFileResponse
    {
        $fileName = $fileName ?? $this->fileName;
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

    /**
     * Generate fast streaming XLSX file using ZipStream with zero memory overhead,
     * bold dark-blue styled header, frozen top pane, and AutoFilter.
     */
    public function generateXlsxFile(): string
    {
        $tempXlsx = tempnam(sys_get_temp_dir(), 'online_full_record_') . '.xlsx';
        $opt = new ArchiveOptions();
        $opt->setSendHttpHeaders(false);
        $fh = fopen($tempXlsx, 'w+b');
        $opt->setOutputStream($fh);

        $zip = new ZipStream(null, $opt);

        // 1. [Content_Types].xml
        $zip->addFile(
            '[Content_Types].xml',
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
        $zip->addFile(
            '_rels/.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>'
        );

        // 3. xl/_rels/workbook.xml.rels
        $zip->addFile(
            'xl/_rels/workbook.xml.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>'
        );

        // 4. xl/workbook.xml
        $zip->addFile(
            'xl/workbook.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="KP OWL Online Records" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>'
        );

        // 5. xl/styles.xml with bold white text on #003B6D dark blue header
        $zip->addFile(
            'xl/styles.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2">'
            . '<font><sz val="10"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF002B49"/><bgColor rgb="FF002B49"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="1">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '</borders>'
            . '<cellStyleXfs count="1">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>'
            . '</cellStyleXfs>'
            . '<cellXfs count="2">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '</cellXfs>'
            . '</styleSheet>'
        );

        // 6. Temporary file stream for sheet1.xml
        $sheetFile = tempnam(sys_get_temp_dir(), 'xlsx_sheet_');
        $sfh = fopen($sheetFile, 'w+b');

        fwrite($sfh, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n");
        fwrite($sfh, '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' . "\n");

        // Freeze top header row
        fwrite($sfh, '<sheetViews><sheetView tabSelected="1" workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>' . "\n");

        // Column widths
        fwrite($sfh, '<cols>' . "\n");
        foreach ($this->columnWidths() as $colLetter => $w) {
            $colIdx = Coordinate::columnIndexFromString($colLetter);
            fwrite($sfh, "<col min=\"{$colIdx}\" max=\"{$colIdx}\" width=\"{$w}\" customWidth=\"1\"/>\n");
        }
        fwrite($sfh, '</cols>' . "\n");

        fwrite($sfh, '<sheetData>' . "\n");

        // Header row
        $headers = $this->headings();
        fwrite($sfh, '<row r="1" ht="28" customHeight="1">' . "\n");
        foreach ($headers as $i => $h) {
            $coord = Coordinate::stringFromColumnIndex($i + 1) . '1';
            $val = $this->escapeXml($h);
            fwrite($sfh, "<c r=\"{$coord}\" t=\"inlineStr\" s=\"1\"><is><t>{$val}</t></is></c>\n");
        }
        fwrite($sfh, '</row>' . "\n");

        // High-performance unbuffered stream PDO execution (zero quadratic offset penalty, linear O(N) streaming)
        $query = $this->query();
        $streamPdo = $this->service->getStreamPdo();
        $stmt = $streamPdo->prepare($query->toSql());
        $stmt->execute($query->getBindings());

        $rowCount = 0;
        $batch = [];
        $batchSize = 2500;
        $buffer = '';

        while ($row = $stmt->fetch(\PDO::FETCH_OBJ)) {
            $batch[] = $row;
            if (count($batch) >= $batchSize) {
                $context = $this->service->buildBatchContext($batch);
                foreach ($batch as $bRow) {
                    $rowCount++;
                    $r = $rowCount + 1;
                    $mapped = $this->service->mapRow($bRow, $rowCount, $context);
                    $buffer .= $this->buildRowXml($r, $mapped);
                }
                fwrite($sfh, $buffer);
                $buffer = '';
                $batch = [];
            }
        }

        if (!empty($batch)) {
            $context = $this->service->buildBatchContext($batch);
            foreach ($batch as $bRow) {
                $rowCount++;
                $r = $rowCount + 1;
                $mapped = $this->service->mapRow($bRow, $rowCount, $context);
                $buffer .= $this->buildRowXml($r, $mapped);
            }
            fwrite($sfh, $buffer);
            $buffer = '';
            $batch = [];
        }

        $stmt->closeCursor();

        fwrite($sfh, '</sheetData>' . "\n");

        // AutoFilter on headers A1:AT1 (46 columns: 1=A ... 46=AT)
        fwrite($sfh, '<autoFilter ref="A1:AT1"/>' . "\n");

        fwrite($sfh, '</worksheet>' . "\n");
        rewind($sfh);

        $zip->addFileFromStream('xl/worksheets/sheet1.xml', $sfh);
        fclose($sfh);
        @unlink($sheetFile);

        $zip->finish();
        fclose($fh);

        return $tempXlsx;
    }

    /**
     * Build XML string for a single data row.
     */
    protected function buildRowXml(int $rowIdx, array $mapped): string
    {
        // First column (No) as number
        $no = $mapped[0] ?? ($rowIdx - 1);
        $xml = "<row r=\"{$rowIdx}\"><c r=\"A{$rowIdx}\"><v>{$no}</v></c>";

        // Columns 2 through 46
        for ($i = 1; $i < count($mapped); $i++) {
            $val = $mapped[$i] ?? '';
            if ($val !== '' && $val !== null) {
                $colLetter = Coordinate::stringFromColumnIndex($i + 1);
                $escaped = $this->escapeXml($val);
                $xml .= "<c r=\"{$colLetter}{$rowIdx}\" t=\"inlineStr\"><is><t>{$escaped}</t></is></c>";
            }
        }

        $xml .= "</row>\n";
        return $xml;
    }

    protected function escapeXml(mixed $value): string
    {
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string) $value);
        return htmlspecialchars($clean, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
