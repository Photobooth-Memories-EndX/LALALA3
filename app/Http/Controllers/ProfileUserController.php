<?php

namespace App\Http\Controllers;

use App\Exports\UserProfileLearningExport;
use App\Models\ContentUser;
use App\Models\User;
use App\Services\User\UserProfileLearningHistoryService;
use App\Support\DurationFormatter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class ProfileUserController extends Controller
{
    public function index(Request $request, $hash)
    {
        $user = User::firstWhere('ssohash', $hash);
        if (!$user) {
            abort(404, 'User not found.');
        }

        // Handle export actions via query parameters if requested directly
        if ($request->input('action') === 'download' || $request->input('format') === 'excel') {
            return $this->exportExcel($request, $hash);
        }
        if ($request->input('action') === 'download_csv' || $request->input('format') === 'csv') {
            return $this->exportCsv($request, $hash);
        }
        if ($request->input('action') === 'download_pdf' || $request->input('format') === 'pdf') {
            return $this->exportPdf($request, $hash);
        }

        $user_id = $user->id;
        $content = ContentUser::with(['content', 'content.classification'])->whereHas('user', function ($query) use ($user_id) {
            $query->where('id', $user_id);
        })->get();

        $validContent = $content->filter(function ($item) {
            return !empty($item->content);
        });

        $lastLearning = optional($validContent->sortByDesc('updated_at')->first()?->content)->title ?? '-';

        $favoriteCategory = $validContent
            ->groupBy(function ($item) {
                return $item->content->classification->classification_name ?? '-';
            })
            ->sortByDesc(function ($items) {
                return $items->count();
            })
            ->keys()
            ->first() ?? '-';

        $learningHours = $validContent->sum(function ($item) {
            return DurationFormatter::toHours($item->content->duration ?? null);
        });

        $learningHours = round($learningHours, 1);

        $filterOptions = UserProfileLearningHistoryService::getFilterOptions($user);

        return view('main.profile.index', [
            "profile" => $user,
            "content" => $content,
            'lastLearning' => $lastLearning,
            'favoriteCategory' => $favoriteCategory,
            'learningHours' => $learningHours,
            'classifications' => $filterOptions['classifications'],
            'trainingTypes' => $filterOptions['trainingTypes'],
            'years' => $filterOptions['years'],
        ]);
    }

    /**
     * Server-side DataTables AJAX endpoint for user's learning history.
     */
    public function learningDatatable(Request $request, $hash)
    {
        $user = User::firstWhere('ssohash', $hash);
        if (!$user) {
            abort(404, 'User not found.');
        }

        $query = UserProfileLearningHistoryService::buildQuery($user, $request->all());

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('status_badge', function (ContentUser $row) {
                return (int) ($row->finish ?? 0) === 1
                    ? '<span class="badge bg-green-lt text-green">Finished</span>'
                    : '<span class="badge bg-yellow-lt text-yellow">Ongoing</span>';
            })
            ->addColumn('user_name', function (ContentUser $row) use ($user) {
                return e($row->user->name ?? $user->name ?? '-');
            })
            ->addColumn('title_html', function (ContentUser $row) {
                $content = $row->content;
                if (!$content) {
                    return '<span class="text-danger fw-semibold">Content Deleted</span>';
                }

                $isVideo = in_array($content->type, ['Video', 'YouTube']);
                $icon = $isVideo
                    ? '<span class="avatar avatar-sm bg-red-lt text-red me-2 flex-shrink-0"><i class="ti ti-video"></i></span>'
                    : '<span class="avatar avatar-sm bg-yellow-lt text-yellow me-2 flex-shrink-0"><i class="ti ti-file-type-pdf"></i></span>';

                $slugUrl = !empty($content->slug) ? route('learning.manage.content', $content->slug) : null;
                $titleLink = $slugUrl
                    ? '<a href="' . e($slugUrl) . '" class="text-reset fw-semibold">' . e($content->title ?: '-') . '</a>'
                    : '<span class="fw-semibold">' . e($content->title ?: '-') . '</span>';

                $typeSub = '<div class="text-muted small">' . e($content->type ?: '-') . '</div>';

                return '<div class="d-flex align-items-center">' . $icon . '<div>' . $titleLink . $typeSub . '</div></div>';
            })
            ->addColumn('classification_html', function (ContentUser $row) {
                $cl = $row->content?->classification;
                if ($cl && !empty($cl->slug)) {
                    $clUrl = route('learning.manage.category', $cl->slug);
                    return '<a href="' . e($clUrl) . '" class="text-reset">' . e($cl->classification_name) . '</a>';
                }
                return '<span class="text-muted">' . e($cl->classification_name ?? '-') . '</span>';
            })
            ->addColumn('duration_formatted', function (ContentUser $row) {
                return DurationFormatter::format($row->content?->duration ?? null);
            })
            ->addColumn('date_formatted', function (ContentUser $row) {
                return optional($row->created_at)->format('d M Y') ?: '-';
            })
            ->addColumn('progress_html', function (ContentUser $row) {
                $prog = (int) ($row->progress ?? 0);
                return '<div class="d-flex align-items-center gap-2">'
                    . '<div class="progress progress-sm flex-fill">'
                    . '<div class="progress-bar bg-primary" style="width: ' . $prog . '%"></div>'
                    . '</div>'
                    . '<span class="small text-muted">' . $prog . '%</span>'
                    . '</div>';
            })
            ->rawColumns(['status_badge', 'title_html', 'classification_html', 'progress_html'])
            ->make(true);
    }

    /**
     * Export user learning history to Excel (.xlsx).
     */
    public function exportExcel(Request $request, $hash)
    {
        $user = User::firstWhere('ssohash', $hash);
        if (!$user) {
            abort(404, 'User not found.');
        }

        $filename = sprintf(
            '%s_%s_learning_history.xlsx',
            now()->format('Ymd_His'),
            Str::slug($user->name ?: 'user')
        );

        return Excel::download(new UserProfileLearningExport($user, $request->all()), $filename);
    }

    /**
     * Export user learning history to CSV (.csv).
     */
    public function exportCsv(Request $request, $hash)
    {
        $user = User::firstWhere('ssohash', $hash);
        if (!$user) {
            abort(404, 'User not found.');
        }

        $filename = sprintf(
            '%s_%s_learning_history.csv',
            now()->format('Ymd_His'),
            Str::slug($user->name ?: 'user')
        );

        $tempCsv = tempnam(sys_get_temp_dir(), 'owl_usr_csv_');
        $handle = fopen($tempCsv, 'w+');

        // UTF-8 BOM
        fwrite($handle, "\xEF\xBB\xBF");

        // CSV Header
        fputcsv($handle, [
            'No',
            'Status',
            'Employee Name',
            'Learning Title',
            'Type',
            'Classification',
            'Duration',
            'Date',
            'Progress',
        ]);

        $query = UserProfileLearningHistoryService::buildQuery($user, $request->all())
            ->orderBy('content_users.created_at', 'desc');

        $rowNum = 0;
        $query->chunk(500, function ($rows) use ($handle, &$rowNum, $user) {
            foreach ($rows as $row) {
                $rowNum++;
                $content = $row->content;
                $classification = $content?->classification;

                fputcsv($handle, [
                    $rowNum,
                    (int) ($row->finish ?? 0) === 1 ? 'Finished' : 'Ongoing',
                    $row->user?->name ?? $user->name ?? '-',
                    $content?->title ?? '-',
                    $content?->type ?? '-',
                    $classification?->classification_name ?? '-',
                    DurationFormatter::format($content?->duration ?? null),
                    optional($row->created_at)->format('d M Y') ?: '-',
                    ((int) ($row->progress ?? 0)) . '%',
                ]);
            }
        });

        fclose($handle);

        return response()->download($tempCsv, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ])->deleteFileAfterSend(true);
    }

    /**
     * Export user learning history to PDF (.pdf).
     */
    public function exportPdf(Request $request, $hash)
    {
        $user = User::firstWhere('ssohash', $hash);
        if (!$user) {
            abort(404, 'User not found.');
        }

        $records = UserProfileLearningHistoryService::buildQuery($user, $request->all())
            ->orderBy('content_users.created_at', 'desc')
            ->get();

        $activeFilters = UserProfileLearningHistoryService::activeFilterLabels($request->all());

        $filename = sprintf(
            '%s_%s_learning_history.pdf',
            now()->format('Ymd_His'),
            Str::slug($user->name ?: 'user')
        );

        $pdf = Pdf::loadView('main.profile.export-pdf', [
            'profile' => $user,
            'records' => $records,
            'activeFilters' => $activeFilters,
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }

    public function editOutsourcing(User $user)
    {
        return view('manage.user.edit_outsourcing', [
            'user' => $user,
        ]);
    }
}
