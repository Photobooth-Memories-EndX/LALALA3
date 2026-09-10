<?php

namespace App\Exports;

use App\Models\Classification;
use App\Models\ContentUser;
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
        $query = ContentUser::with(['user', 'content.classification'])
            ->select('content_users.*');

        $status = $this->filters['status'] ?? null;
        if (filled($status) && $status !== 'all') {
            $statusStr = (string) $status;
            if ($statusStr === 'finished' || $statusStr === 'finish' || $statusStr === '1') {
                $query->where('content_users.finish', '1');
            } elseif ($statusStr === 'ongoing' || $statusStr === '0') {
                $query->where('content_users.finish', '0')->where('content_users.progress', '>', 0);
            } elseif ($statusStr === 'not_started') {
                $query->where('content_users.finish', '0')->where(function ($q) {
                    $q->where('content_users.progress', 0)->orWhereNull('content_users.progress');
                });
            } elseif (in_array($statusStr, ['mandatory', 'optional'], true)) {
                $query->where('content_users.status', $statusStr);
            }
        }

        $programType = $this->filters['program_type'] ?? ($this->filters['program'] ?? null);
        if (filled($programType) && $programType !== 'all') {
            if ($programType === 'mandatory') {
                $query->where(function ($q) {
                    $q->where('content_users.status', 'mandatory');
                    if (DB::getSchemaBuilder()->hasColumn('content_users', 'is_mandatory')) {
                        $q->orWhere('content_users.is_mandatory', 1);
                    }
                });
            } elseif ($programType === 'optional') {
                $query->where(function ($q) {
                    $q->where('content_users.status', 'optional');
                    if (DB::getSchemaBuilder()->hasColumn('content_users', 'is_mandatory')) {
                        $q->orWhere('content_users.is_mandatory', 0);
                    }
                });
            } else {
                $query->where('content_users.status', $programType);
            }
        }

        $year = $this->filters['year'] ?? null;
        if (filled($year) && $year !== 'all') {
            $query->whereYear('content_users.created_at', $year);
        }

        $fromDate = $this->filters['from_date'] ?? ($this->filters['date_from'] ?? null);
        $toDate = $this->filters['to_date'] ?? ($this->filters['date_to'] ?? null);

        if (filled($fromDate) && filled($toDate)) {
            $query->whereBetween('content_users.created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
        } elseif (filled($fromDate)) {
            $query->whereDate('content_users.created_at', '>=', $fromDate);
        } elseif (filled($toDate)) {
            $query->whereDate('content_users.created_at', '<=', $toDate);
        }

        $progressRange = $this->filters['progress_range'] ?? null;
        if (filled($progressRange) && $progressRange !== 'all') {
            $progressVal = str_replace([' ', '%'], '', (string) $progressRange);
            switch ($progressVal) {
                case '0':
                    $query->where(function ($q) {
                        $q->where('content_users.progress', 0)->orWhereNull('content_users.progress');
                    });
                    break;
                case '1-25':
                    $query->whereBetween('content_users.progress', [1, 25]);
                    break;
                case '26-50':
                    $query->whereBetween('content_users.progress', [26, 50]);
                    break;
                case '51-75':
                    $query->whereBetween('content_users.progress', [51, 75]);
                    break;
                case '76-99':
                    $query->whereBetween('content_users.progress', [76, 99]);
                    break;
                case '100':
                    $query->where('content_users.progress', 100);
                    break;
            }
        }

        $duration = $this->filters['duration'] ?? null;
        if (filled($duration) && $duration !== 'all') {
            $durationInput = strtolower(trim((string) $duration));
            $durationRange = match ($durationInput) {
                'lt10', 'under_10', '< 10 minutes', '<10min', '<10' => [null, 600, false, true],
                '10-30', '10_30', '10 - 30 minutes', '10-30min' => [600, 1800, true, false],
                '30-60', '30_60', '30 - 60 minutes', '30-60min' => [1800, 3600, false, false],
                'gt60', 'above_60', '> 1 hour', '>1hour', '>60', '>60min' => [3600, null, false, false],
                default => null,
            };

            if ($durationRange !== null) {
                [$minimum, $maximum, $includeMinimum, $excludeMaximum] = $durationRange;
                $isSqlite = DB::connection()->getDriverName() === 'sqlite';
                if (!$isSqlite) {
                    $durationInSeconds = "CASE
                        WHEN content.duration IS NULL OR TRIM(content.duration) = '' THEN NULL
                        WHEN LENGTH(content.duration) - LENGTH(REPLACE(content.duration, ':', '')) = 1 THEN
                            CAST(SUBSTRING_INDEX(content.duration, ':', 1) AS UNSIGNED) * 60
                            + CAST(SUBSTRING_INDEX(content.duration, ':', -1) AS UNSIGNED)
                        WHEN LENGTH(content.duration) - LENGTH(REPLACE(content.duration, ':', '')) = 2 THEN
                            CAST(SUBSTRING_INDEX(content.duration, ':', 1) AS UNSIGNED) * 3600
                            + CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(content.duration, ':', 2), ':', -1) AS UNSIGNED) * 60
                            + CAST(SUBSTRING_INDEX(content.duration, ':', -1) AS UNSIGNED)
                        ELSE NULL
                    END";

                    $query->whereHas('content', function ($contentQ) use ($durationInSeconds, $minimum, $maximum, $includeMinimum, $excludeMaximum) {
                        if ($minimum !== null) {
                            $contentQ->whereRaw($durationInSeconds . ($includeMinimum ? ' >= ?' : ' > ?'), [$minimum]);
                        }
                        if ($maximum !== null) {
                            $contentQ->whereRaw($durationInSeconds . ($excludeMaximum ? ' < ?' : ' <= ?'), [$maximum]);
                        }
                    });
                }
            }
        }

        $classSlug = $this->filters['classification_slug'] ?? ($this->type === 'category' ? $this->slug : null);
        if (filled($classSlug)) {
            $query->whereHas('content.classification', fn ($q) => $q->where('slug', $classSlug));
        }

        $contentSlug = $this->filters['content_slug'] ?? ($this->type === 'content' ? $this->slug : null);
        if (filled($contentSlug)) {
            $query->whereHas('content', fn ($q) => $q->where('slug', $contentSlug));
        }

        // Filter by Employee ID / NIP / Name
        $employeeParam = $this->filters['employee_id'] ?? ($this->filters['user_id'] ?? ($this->filters['employee'] ?? null));
        if (filled($employeeParam) && $employeeParam !== 'all') {
            $query->whereHas('user', function ($userQuery) use ($employeeParam) {
                $userQuery->where(function ($uq) use ($employeeParam) {
                    $uq->where('id_emp', 'like', '%' . $employeeParam . '%')
                        ->orWhere('name', 'like', '%' . $employeeParam . '%');
                    if (is_numeric($employeeParam)) {
                        $uq->orWhere('id', (int) $employeeParam);
                    }
                });
            });
        }

        // Filter by Learning Title
        $learningTitleParam = $this->filters['learning_title'] ?? ($this->filters['title'] ?? ($this->filters['content_title'] ?? null));
        if (filled($learningTitleParam)) {
            $query->whereHas('content', function ($contentQuery) use ($learningTitleParam) {
                $contentQuery->where('title', 'like', '%' . $learningTitleParam . '%');
            });
        }

        $searchParam = $this->filters['search'] ?? '';
        $search = is_array($searchParam)
            ? trim((string) ($searchParam['value'] ?? ''))
            : trim((string) $searchParam);

        if ($search !== '' && !filled($employeeParam) && !filled($learningTitleParam)) {
            $query->where(function ($q) use ($search) {
                $q->where('content_users.status', 'like', '%' . $search . '%')
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('id_emp', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('content', function ($contentQuery) use ($search) {
                        $contentQuery->where('title', 'like', '%' . $search . '%')
                            ->orWhere('duration', 'like', '%' . $search . '%')
                            ->orWhereHas('classification', function ($classificationQuery) use ($search) {
                                $classificationQuery->where('classification_name', 'like', '%' . $search . '%');
                            });
                    });
            });
        }

        $sort = $this->filters['sort'] ?? null;
        if (filled($sort)) {
            switch ($sort) {
                case 'newest':
                    $query->orderBy('content_users.created_at', 'desc');
                    break;
                case 'oldest':
                    $query->orderBy('content_users.created_at', 'asc');
                    break;
                case 'highest_progress':
                    $query->orderBy('content_users.progress', 'desc');
                    break;
                case 'lowest_progress':
                    $query->orderBy('content_users.progress', 'asc');
                    break;
            }
        } else {
            $query->orderBy('content_users.created_at', 'desc');
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

        $sheet->getStyle('A1:K1')->getFont()->setBold(true);
        if ($this->total > 0 && $this->total <= 2500) {
            $sheet->getStyle('A1:K' . ($this->total + 1))->applyFromArray($styleArray);
        }
    }
}
