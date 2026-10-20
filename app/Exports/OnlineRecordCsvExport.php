<?php

namespace App\Exports;

use App\Services\Learning\LearningDatatableService;
use App\Support\DurationFormatter;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OnlineRecordCsvExport implements Responsable
{
    protected array $filters = [];
    protected string $type = '';
    protected string $slug = '';
    protected string $fileName;

    public function __construct($filters = [], string $type = '', string $slug = '')
    {
        if ($filters instanceof Request) {
            $this->filters = $filters->all();
            $this->type = (string) ($type ?: $filters->input('type', ''));
            $this->slug = (string) ($slug ?: $filters->input('slug', ''));
        } elseif (is_array($filters)) {
            $this->filters = $filters;
            $this->type = $type;
            $this->slug = $slug;
        } else {
            $this->type = (string) $filters;
            $this->slug = $slug;
        }

        $slugPart = !empty($this->slug) ? Str::slug($this->slug) . '-' : '';
        $this->fileName = sprintf('kp-owl-online-records-%s%s.csv', $slugPart, now()->format('Y-m-d'));
    }

    public function getFileName(): string
    {
        return $this->fileName;
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

    /**
     * Stream CSV to temp file and return BinaryFileResponse to ensure low memory usage.
     */
    public function download(?string $fileName = null, $writerType = null): BinaryFileResponse
    {
        $targetFileName = $fileName ?? $this->fileName;
        $tempCsv = tempnam(sys_get_temp_dir(), 'csv_export_') . '.csv';
        $out = fopen($tempCsv, 'w+b');

        // UTF-8 Byte Order Mark (BOM) for Excel compatibility
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Write CSV headers
        fputcsv($out, $this->headings());

        $pdo = DB::connection()->getPdo();
        $wasBuffered = $pdo->getAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY);
        $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

        try {
            $query = $this->query();
            $stmt = $pdo->prepare($query->toSql());
            $stmt->execute($query->getBindings());

            $rowCount = 0;
            while ($row = $stmt->fetch(\PDO::FETCH_OBJ)) {
                $rowCount++;

                $title = $row->content_title ?? '-';
                $category = $row->category_source ?? 'All Content';
                if ($category === 'Playlist' && !empty($row->playlist_title)) {
                    $category = 'Playlist: ' . $row->playlist_title;
                }

                $rawType = (string) ($row->content_type ?? '');
                $type = in_array(strtolower($rawType), ['video', 'youtube', 'mooc'], true) ? 'Video' : 'Document';

                $duration = DurationFormatter::format($row->content_duration ?? null);
                $progress = ($row->progress ?? 0) . '%';

                fputcsv($out, [
                    $rowCount,
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
                    $row->created_at ?? '-',
                    $progress,
                ]);
            }

            $stmt->closeCursor();
        } finally {
            $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $wasBuffered);
            fclose($out);
        }

        return response()->download($tempCsv, $targetFileName, [
            'Content-Type'  => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ])->deleteFileAfterSend(true);
    }

    public function toResponse($request)
    {
        return $this->download();
    }
}
