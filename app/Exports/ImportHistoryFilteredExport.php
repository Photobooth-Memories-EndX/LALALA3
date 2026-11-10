<?php

namespace App\Exports;

use App\Models\ImportBatch;
use App\Services\ImportHistory\ImportHistoryExportService;
use Illuminate\Contracts\Support\Responsable;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportHistoryFilteredExport implements Responsable
{
    protected ImportBatch $batch;
    protected array $filters;
    protected ImportHistoryExportService $exportService;
    protected ?string $fileName = null;

    public function __construct(
        ImportBatch $batch,
        array $filters = [],
        ?ImportHistoryExportService $exportService = null
    ) {
        $this->batch = $batch;
        $this->filters = $filters;
        $this->exportService = $exportService ?? app(ImportHistoryExportService::class);
    }

    /**
     * Get or generate the download filename.
     */
    public function getFileName(): string
    {
        if ($this->fileName) {
            return $this->fileName;
        }

        $source = $this->batch->source_type === ImportBatch::SOURCE_PTKP ? 'PTKP' : 'NonPTKP';
        $statusSuffix = !empty($this->filters['validation_status'])
            ? strtoupper(str_replace(' ', '_', trim($this->filters['validation_status'])))
            : 'ALL';

        $actionSuffix = !empty($this->filters['action'])
            ? '_' . strtoupper(trim($this->filters['action']))
            : '';

        $uuidShort = substr($this->batch->batch_uuid, 0, 8);
        $timestamp = date('Y-m-d_His');

        return "{$source}_Import_Audit_{$uuidShort}_{$statusSuffix}{$actionSuffix}_{$timestamp}.xlsx";
    }

    /**
     * Set a custom filename.
     */
    public function setFileName(string $fileName): self
    {
        $this->fileName = $fileName;
        return $this;
    }

    /**
     * Build the spreadsheet instance.
     */
    public function getSpreadsheet(): Spreadsheet
    {
        return $this->exportService->buildSpreadsheet($this->batch, $this->filters);
    }

    /**
     * Create an HTTP response that represents the export download.
     *
     * @param \Illuminate\Http\Request $request
     * @return StreamedResponse
     */
    public function toResponse($request): StreamedResponse
    {
        return $this->download();
    }

    /**
     * Stream the spreadsheet as an Excel attachment.
     */
    public function download(): StreamedResponse
    {
        $spreadsheet = $this->getSpreadsheet();
        $fileName = $this->getFileName();

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type'              => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition'       => 'attachment; filename="' . $fileName . '"',
            'Cache-Control'             => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma'                    => 'no-cache',
            'Expires'                   => '0',
            'X-Content-Type-Options'    => 'nosniff',
        ]);
    }
}
