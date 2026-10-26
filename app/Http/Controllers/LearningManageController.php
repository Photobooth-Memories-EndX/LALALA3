<?php

namespace App\Http\Controllers;

use App\Exports\LearningExport;
use App\Exports\OnlineRecordCsvExport;
use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\User;
use App\Services\Learning\LearningDatatableService;
use App\Services\LearningHours\ActualLearningHourCalculator;
use App\Support\DurationFormatter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class LearningManageController extends Controller
{
    public const PLAYLIST_CUTOFF = '2026-08-28 23:59:59';

    public function getKpiSummary(): array
    {
        $cuTotal = ContentUser::count();
        $cuOngoing = ContentUser::where('finish', '0')->count();
        $cuFinish = ContentUser::where('finish', '1')->count();

        $pcuTotal = PlaylistContentUser::count();
        $pcuOngoing = PlaylistContentUser::where('finish', '0')->count();
        $pcuFinish = PlaylistContentUser::where('finish', '1')->count();

        $totalLearning = $cuTotal + $pcuTotal;
        $ongoing = $cuOngoing + $pcuOngoing;
        $finish = $cuFinish + $pcuFinish;

        $completionRate = $totalLearning > 0
            ? round(($finish / $totalLearning) * 100, 1)
            : 0;

        return [
            'ongoing' => $ongoing,
            'finish' => $finish,
            'finished' => $finish,
            'totalLearning' => $totalLearning,
            'total_learning' => $totalLearning,
            'completionRate' => $completionRate,
            'completion_rate' => $completionRate,
        ];
    }

    public function getClassificationSummary()
    {
        $unifiedSubquery = DB::table('content_users')
            ->select([
                'id',
                'content_id',
                'finish',
            ])
            ->unionAll(
                DB::table('playlist_content_users')
                    ->select([
                        'id',
                        'content_id',
                        'finish',
                    ])
            );

        return DB::table('classification')
            ->leftJoin('content', function ($join) {
                $join->on('content.classification_id', '=', 'classification.id')
                    ->whereNull('content.deleted_at');
            })
            ->leftJoinSub($unifiedSubquery, 'learning_records', function ($join) {
                $join->on('learning_records.content_id', '=', 'content.id');
            })
            ->select([
                'classification.id',
                DB::raw('MIN(classification.classification_name) as classification_name'),
                DB::raw('MIN(classification.slug) as slug'),
                DB::raw('COUNT(DISTINCT content.id) as content_total'),
                DB::raw('COUNT(learning_records.id) as total_learning'),

                DB::raw("
                    COALESCE(
                        SUM(
                            CASE 
                                WHEN learning_records.finish = '0' THEN 1 
                                ELSE 0 
                            END
                        ), 0
                    ) as ongoing_learning
                "),

                DB::raw("
                    COALESCE(
                        SUM(
                            CASE 
                                WHEN learning_records.finish = '1' THEN 1 
                                ELSE 0 
                            END
                        ), 0
                    ) as finished_learning
                "),

                DB::raw("
                    COALESCE(
                        SUM(
                            CASE 
                                WHEN learning_records.id IS NOT NULL 
                                    AND content.duration IS NOT NULL 
                                    AND content.duration != ''
                                THEN CASE
                                    WHEN LENGTH(content.duration) - LENGTH(REPLACE(content.duration, ':', '')) = 2
                                    THEN TIME_TO_SEC(content.duration)
                                    ELSE TIME_TO_SEC(CONCAT('00:', content.duration))
                                END
                                ELSE 0 
                            END
                        ) / 3600, 0
                    ) as learning_hours
                "),
            ])
            ->whereNotNull('classification.classification_name')
            ->whereNotNull('classification.slug')
            ->where('classification.slug', '!=', '')
            ->groupBy('classification.id')
            ->orderBy('classification_name')
            ->get();
    }

    public function getTotalLearningHours(): float
    {
        $res = DB::table('content')
            ->join('classification', 'classification.id', '=', 'content.classification_id')
            ->whereNotNull('classification.classification_name')
            ->whereNotNull('classification.slug')
            ->where('classification.slug', '!=', '')
            ->whereNull('content.deleted_at')
            ->whereNotNull('content.duration')
            ->where('content.duration', '!=', '')
            ->selectRaw("
                SUM(
                    CASE
                        WHEN LENGTH(content.duration) - LENGTH(REPLACE(content.duration, ':', '')) = 2
                        THEN TIME_TO_SEC(content.duration)
                        ELSE TIME_TO_SEC(CONCAT('00:', content.duration))
                    END * (
                        (SELECT COUNT(*) FROM content_users WHERE content_users.content_id = content.id) +
                        (SELECT COUNT(*) FROM playlist_content_users WHERE playlist_content_users.content_id = content.id)
                    )
                ) / 3600 as total_hours
            ")
            ->first();

        return round((float) ($res->total_hours ?? 0), 1);
    }

    public function getSummaryData(): array
    {
        $kpi = $this->getKpiSummary();
        $classification = $this->getClassificationSummary();
        $learningHours = round((float) $classification->sum('learning_hours'), 1);

        return [
            'ongoing' => $kpi['ongoing'],
            'finish' => $kpi['finish'],
            'totalLearning' => $kpi['totalLearning'],
            'completionRate' => $kpi['completionRate'],
            'learningHours' => $learningHours,
            'classification' => $classification,
        ];
    }

    public function index()
    {
        return view('main.learning.index', $this->getSummaryData());
    }

    public function all(Request $request)
    {
        if ($request->input('action') === 'download') {
            return (new LearningExport($request->all(), '', ''))
                ->download(now()->format('Ymd_His') . '_learning.xlsx');
        }

        if ($request->input('action') === 'download_pdf' || $request->input('format') === 'pdf') {
            return $this->downloadPdf($request);
        }

        if ($request->input('action') === 'download_csv' || $request->input('format') === 'csv') {
            return $this->downloadCsv($request);
        }

        return view('main.learning.all', $this->learningViewData($request, 'All Learning'));
    }

    public function category($slug, Request $request)
    {
        $classification = Classification::where('slug', $slug)->firstOrFail();

        if ($request->input('action') === 'download') {
            return (new LearningExport($request->all(), 'category', $slug))
                ->download(now()->format('Ymd_His') . '_learning.xlsx');
        }

        if ($request->input('action') === 'download_pdf' || $request->input('format') === 'pdf') {
            $request->merge(['type' => 'category', 'slug' => $slug]);
            return $this->downloadPdf($request);
        }

        if ($request->input('action') === 'download_csv' || $request->input('format') === 'csv') {
            $request->merge(['type' => 'category', 'slug' => $slug]);
            return $this->downloadCsv($request);
        }

        return view('main.learning.all', $this->learningViewData(
            $request,
            'Classification: ' . $classification->classification_name,
            $slug
        ));
    }

    public function content($slug, Request $request)
    {
        $contentModel = Content::where('slug', $slug)->first();

        if ($request->input('action') === 'download') {
            return (new LearningExport($request->all(), 'content', $slug))
                ->download(now()->format('Ymd_His') . '_learning.xlsx');
        }

        if ($request->input('action') === 'download_pdf' || $request->input('format') === 'pdf') {
            $request->merge(['type' => 'content', 'slug' => $slug]);
            return $this->downloadPdf($request);
        }

        if ($request->input('action') === 'download_csv' || $request->input('format') === 'csv') {
            $request->merge(['type' => 'content', 'slug' => $slug]);
            return $this->downloadCsv($request);
        }

        return view('main.learning.all', $this->learningViewData(
            $request,
            'Content: ' . ($contentModel->title ?? $slug),
            $slug
        ));
    }

    public function playlistDetail($id, Request $request)
    {
        $playlist = is_numeric($id)
            ? TrainingPlan::with(['contents.classification'])->find($id)
            : TrainingPlan::with(['contents.classification'])->where('slug', $id)->first();

        if (!$playlist) {
            abort(404, 'Playlist not found.');
        }

        $playlistId = (string) $playlist->id;

        if ($request->input('action') === 'download') {
            return (new LearningExport($request->all(), 'playlist', $playlistId))
                ->download(now()->format('Ymd_His') . '_' . Str::slug($playlist->title) . '_learning.xlsx');
        }

        if ($request->input('action') === 'download_pdf' || $request->input('format') === 'pdf') {
            $request->merge(['type' => 'playlist', 'slug' => $playlistId, 'playlist_id' => $playlistId]);
            return $this->downloadPdf($request);
        }

        if ($request->input('action') === 'download_csv' || $request->input('format') === 'csv') {
            $request->merge(['type' => 'playlist', 'slug' => $playlistId, 'playlist_id' => $playlistId]);
            return $this->downloadCsv($request);
        }

        // Determine smart back link
        $referer = (string) $request->headers->get('referer', '');
        $backUrl = route('learning.manage.all');
        $backText = 'Back';

        if ($referer && !str_contains($referer, $request->path())) {
            $backUrl = $referer;
            if (str_contains($referer, 'category/')) {
                $categorySlug = basename(parse_url($referer, PHP_URL_PATH));
                $catModel = Classification::where('slug', $categorySlug)->first();
                $backText = 'Back to ' . ($catModel ? $catModel->classification_name : ucwords(str_replace('-', ' ', $categorySlug)));
            } elseif (str_contains($referer, '/manage/learning')) {
                $backText = 'Back to Manage Learning';
            }
        } else {
            // Fallback: check classification of first content in playlist
            $firstClass = $playlist->contents->first()?->classification;
            if ($firstClass && $firstClass->slug) {
                $backUrl = route('learning.manage.category', $firstClass->slug);
                $backText = 'Back to ' . $firstClass->classification_name;
            }
        }

        $viewData = $this->learningViewData(
            $request,
            'Playlist: ' . $playlist->title,
            $playlistId
        );

        $viewData['playlist'] = $playlist;
        $viewData['playlistId'] = $playlistId;
        $viewData['backUrl'] = $backUrl;
        $viewData['backText'] = $backText;
        $viewData['headerBackUrl'] = $backUrl;
        $viewData['headerBackText'] = $backText;
        $viewData['datatableUrl'] = route('learning.manage.datatable', ['playlist_id' => $playlistId]);

        return view('main.learning.all', $viewData);
    }

    public function searchEmployees(Request $request)
    {
        $query = trim((string) ($request->get('q') ?? ($request->get('search') ?? $request->get('employee_q', ''))));
        if (!$query) {
            return response()->json(['results' => []]);
        }

        $users = User::where('id_emp', 'LIKE', "%{$query}%")
            ->orWhere('name', 'LIKE', "%{$query}%")
            ->limit(15)
            ->get(['id', 'id_emp', 'name']);

        $results = $users->map(function ($user) use ($query) {
            $isIdMatch = ($user->id_emp && str_contains(strtolower((string) $user->id_emp), strtolower($query)));

            return [
                'id'    => $user->id,
                'title' => $isIdMatch ? (string) $user->id_emp : $user->name,
                'label' => $isIdMatch ? (string) $user->id_emp : $user->name,
                'type'  => $isIdMatch ? 'NIP' : 'EMPLOYEE',
                'value' => $isIdMatch ? (string) $user->id_emp : $user->name,
                'subtext' => $isIdMatch ? ($user->name ?? '') : ($user->id_emp ? "NIP: {$user->id_emp}" : ''),
            ];
        });

        return response()->json(['results' => $results]);
    }

    public function searchTitles(Request $request)
    {
        $term = trim((string) ($request->input('q') ?? ($request->input('search') ?? $request->input('title_q', ''))));
        $results = [];

        $query = Content::with('classification:id,classification_name')
            ->select('id', 'title', 'classification_id', 'slug')
            ->whereNotNull('title')
            ->where('title', '!=', '');

        if ($request->filled('playlist_id')) {
            $tpId = $request->input('playlist_id');
            $contentIds = DB::table('trainingplandetail')->where('trainingplan_id', $tpId)->pluck('content_id');
            $query->whereIn('content.id', $contentIds);
        }

        if ($term !== '') {
            $queryNormalized = str_replace("'", "’", $term);
            $queryOriginal = str_replace("’", "'", $term);
            $query->where(function ($q) use ($term, $queryNormalized, $queryOriginal) {
                $q->where('title', 'like', "%{$term}%")
                  ->orWhere('title', 'like', "%{$queryNormalized}%")
                  ->orWhere('title', 'like', "%{$queryOriginal}%")
                  ->orWhere('slug', 'like', "%{$term}%");
            });
        }

        $contents = $query->limit(10)->get();

        foreach ($contents as $content) {
            $catName = $content->classification->classification_name ?? 'Content';
            $results[] = [
                'id'      => $content->title,
                'value'   => $content->title,
                'label'   => $content->title,
                'title'   => $content->title,
                'name'    => $content->title,
                'text'    => $content->title,
                'subtext' => $catName,
            ];
        }

        return response()->json(['results' => $results]);
    }

    public function download(Request $request)
    {
        if (class_exists(\Barryvdh\Debugbar\Facades\Debugbar::class)) {
            \Barryvdh\Debugbar\Facades\Debugbar::disable();
        }

        return (new LearningExport($request->all(), $request->input('type', ''), $request->input('slug', '')))
            ->download((function() {
                $monthMap = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'];
                $dt = \Carbon\Carbon::now();
                $day = $dt->format('d');
                $month = $monthMap[(int) $dt->format('n')] ?? $dt->format('M');
                $year = $dt->format('Y');
                $time = $dt->format('H.i.s');
                return "EmployeeOWLrecord_{$day}{$month}{$year} at {$time}.xlsx";
            })());
    }

    public function downloadPdf(Request $request)
    {
        $filters = $request->all();
        $type = (string) $request->input('type', '');
        $slug = (string) $request->input('slug', '');

        if (empty($type)) {
            if (!empty($filters['classification_slug'])) {
                $type = 'category';
                $slug = $filters['classification_slug'];
            } elseif (!empty($filters['content_slug'])) {
                $type = 'content';
                $slug = $filters['content_slug'];
            } elseif (!empty($filters['playlist_id'])) {
                $type = 'playlist';
                $slug = $filters['playlist_id'];
            }
        }

        $query = LearningDatatableService::buildBaseQuery($filters, $type, $slug);

        if (empty($filters['sort'])) {
            $query->orderBy('unified.created_at', 'desc')
                  ->orderBy('unified.source_type', 'desc')
                  ->orderBy('unified.source_id', 'desc');
        }

        $totalRecords = (clone $query)->count();

        // Safe limit for DomPDF rendering
        $limit = 500;
        $records = $query->limit($limit)->get();

        $contextTitle = 'KP OWL — Online Learning Record';
        $classificationName = null;
        if (!empty($slug) && $type === 'category') {
            $classification = Classification::where('slug', $slug)->first();
            if ($classification) {
                $classificationName = $classification->classification_name;
                $contextTitle = 'KP OWL — Classification: ' . $classificationName;
            }
        } elseif (!empty($slug) && $type === 'content') {
            $contentModel = Content::where('slug', $slug)->first();
            if ($contentModel) {
                $contextTitle = 'KP OWL — Content: ' . $contentModel->title;
            }
        } elseif (!empty($slug) && $type === 'playlist') {
            $playlistModel = is_numeric($slug) ? TrainingPlan::find($slug) : TrainingPlan::where('slug', $slug)->first();
            if ($playlistModel) {
                $contextTitle = 'KP OWL — Playlist: ' . $playlistModel->title;
            }
        }

        $pdf = Pdf::loadView('main.learning.export-pdf', [
            'records'            => $records,
            'totalRecords'       => $totalRecords,
            'limit'              => $limit,
            'filters'            => $filters,
            'contextTitle'       => $contextTitle,
            'classificationName' => $classificationName,
            'generatedAt'        => now()->format('d M Y H:i:s'),
        ]);

        $fileNameSlug = !empty($slug) ? Str::slug($slug) . '-' : '';
        $fileName = sprintf('kp-owl-online-records-%s%s.pdf', $fileNameSlug, now()->format('Y-m-d'));

        return $pdf->setPaper('a4', 'landscape')->download($fileName);
    }

    public function downloadCsv(Request $request)
    {
        $filters = $request->all();
        $type = (string) $request->input('type', '');
        $slug = (string) $request->input('slug', '');

        if (empty($type)) {
            if (!empty($filters['classification_slug'])) {
                $type = 'category';
                $slug = $filters['classification_slug'];
            } elseif (!empty($filters['content_slug'])) {
                $type = 'content';
                $slug = $filters['content_slug'];
            } elseif (!empty($filters['playlist_id'])) {
                $type = 'playlist';
                $slug = $filters['playlist_id'];
            }
        }

        $fileNameSlug = !empty($slug) ? Str::slug($slug) . '-' : '';
        $fileName = sprintf('kp-owl-online-records-%s%s.csv', $fileNameSlug, now()->format('Y-m-d'));

        $export = new OnlineRecordCsvExport($filters, $type, $slug);

        return $export->download($fileName);
    }

    public function datatable(Request $request, LearningDatatableService $datatableService)
    {
        return $datatableService->make($request);
    }

    public function recordDetail(string $type, $id)
    {
        if (!in_array($type, ['content', 'playlist'], true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid learning source type.',
            ], 404);
        }

        if ($type === 'content') {
            $record = ContentUser::with(['user', 'content.classification'])
                ->where('id', $id)
                ->first();

            if (!$record) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Learning record not found.',
                ], 404);
            }

            $user = $record->user;
            $content = $record->content;
            $classification = $content?->classification;

            $durationFormatted = DurationFormatter::format($content?->duration, '-');
            $currentTimeSec = $record->current_time !== null ? (int) $record->current_time : null;
            $actualWatchSec = $record->actual_watch_seconds !== null ? (int) $record->actual_watch_seconds : null;
            $afkSec = $record->afk_duration_seconds !== null ? (int) $record->afk_duration_seconds : null;

            $durationSeconds = $record->duration_seconds !== null && (int) $record->duration_seconds > 0
                ? (int) $record->duration_seconds
                : DurationFormatter::toSeconds($content?->duration);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'source_type' => 'content',
                    'source_id' => $record->id,
                    'employee' => [
                        'id_emp' => $user?->id_emp ?? '-',
                        'name' => $user?->name ?? '-',
                        'department' => $user?->department ?? '-',
                        'team' => $user?->team ?? '-',
                    ],
                    'learning' => [
                        'title' => $content?->title ?? '-',
                        'type' => $content?->type ?? '-',
                        'classification' => $classification?->classification_name ?? '-',
                        'category' => 'All Content',
                        'source' => 'All Content',
                        'playlist_name' => '-',
                    ],
                    'progress' => [
                        'progress' => (int) ($record->progress ?? 0),
                        'finish' => $record->isFinished() ? 'Finished' : 'In Progress',
                        'finish_badge' => $record->isFinished() ? 'success' : 'warning',
                        'learning_status' => $record->learning_status ? ucfirst(str_replace('_', ' ', $record->learning_status)) : '-',
                        'status' => $record->status ? ucfirst($record->status) : '-',
                        'current_time' => $currentTimeSec !== null ? DurationFormatter::fromSeconds($currentTimeSec) . " ({$currentTimeSec}s)" : '-',
                        'duration' => $durationFormatted,
                        'actual_watch_seconds' => $actualWatchSec !== null ? DurationFormatter::fromSeconds($actualWatchSec) . " ({$actualWatchSec}s)" : '-',
                        'afk_duration' => $afkSec !== null ? DurationFormatter::fromSeconds($afkSec) . " ({$afkSec}s)" : '-',
                        'learning_hours' => $record->learning_hours !== null ? number_format((float) $record->learning_hours, 4) . ' h' : '-',
                        'raw_current_time' => $currentTimeSec ?? 0,
                        'raw_actual_watch_seconds' => $actualWatchSec ?? 0,
                        'raw_afk_duration_seconds' => $afkSec ?? 0,
                        'duration_seconds' => $durationSeconds ?? 0,
                        'is_finished' => $record->isFinished(),
                    ],
                    'timestamps' => [
                        'created_at' => $record->created_at ? $record->created_at->format('d M Y, H:i') : '-',
                        'updated_at' => $record->updated_at ? $record->updated_at->format('d M Y, H:i') : '-',
                        'completed_at' => $record->completed_at ? $record->completed_at->format('d M Y, H:i') : '-',
                    ],
                ],
            ]);
        }

        // type === 'playlist'
        $record = PlaylistContentUser::with(['user', 'content.classification', 'trainingPlan'])
            ->where('id', $id)
            ->first();

        if (!$record) {
            return response()->json([
                'status' => 'error',
                'message' => 'Playlist learning record not found.',
            ], 404);
        }

        $user = $record->user;
        $content = $record->content;
        $classification = $content?->classification;
        $trainingPlan = $record->trainingPlan;

        $durationFormatted = DurationFormatter::format($content?->duration, '-');
        $currentTimeSec = $record->current_time !== null ? (int) $record->current_time : null;
        $actualWatchSec = $record->actual_watch_seconds !== null ? (int) $record->actual_watch_seconds : null;
        $afkSec = $record->afk_duration_seconds !== null ? (int) $record->afk_duration_seconds : null;
        $durationSeconds = $record->duration_seconds !== null && (int) $record->duration_seconds > 0
            ? (int) $record->duration_seconds
            : DurationFormatter::toSeconds($content?->duration);

        return response()->json([
            'status' => 'success',
            'data' => [
                'source_type' => 'playlist',
                'source_id' => $record->id,
                'employee' => [
                    'id_emp' => $user?->id_emp ?? '-',
                    'name' => $user?->name ?? '-',
                    'department' => $user?->department ?? '-',
                    'team' => $user?->team ?? '-',
                ],
                'learning' => [
                    'title' => $content?->title ?? '-',
                    'type' => $content?->type ?? '-',
                    'classification' => $classification?->classification_name ?? '-',
                    'category' => 'Playlist',
                    'source' => 'Playlist',
                    'playlist_name' => $trainingPlan?->title ?? '-',
                ],
                'progress' => [
                    'progress' => (int) ($record->progress ?? 0),
                    'finish' => $record->isFinished() ? 'Finished' : 'In Progress',
                    'finish_badge' => $record->isFinished() ? 'success' : 'warning',
                    'learning_status' => $record->learning_status ? ucfirst(str_replace('_', ' ', $record->learning_status)) : '-',
                    'status' => 'Playlist',
                    'current_time' => $currentTimeSec !== null ? DurationFormatter::fromSeconds($currentTimeSec) . " ({$currentTimeSec}s)" : '-',
                    'duration' => $durationFormatted,
                    'actual_watch_seconds' => $actualWatchSec !== null ? DurationFormatter::fromSeconds($actualWatchSec) . " ({$actualWatchSec}s)" : '-',
                    'afk_duration' => $afkSec !== null ? DurationFormatter::fromSeconds($afkSec) . " ({$afkSec}s)" : '-',
                    'learning_hours' => $record->learning_hour !== null ? number_format((float) $record->learning_hour, 4) . ' h' : '-',
                    'raw_current_time' => $currentTimeSec ?? 0,
                    'raw_actual_watch_seconds' => $actualWatchSec ?? 0,
                    'raw_afk_duration_seconds' => $afkSec ?? 0,
                    'duration_seconds' => $durationSeconds ?? 0,
                    'is_finished' => $record->isFinished(),
                ],
                'timestamps' => [
                    'created_at' => $record->created_at ? $record->created_at->format('d M Y, H:i') : '-',
                    'updated_at' => $record->updated_at ? $record->updated_at->format('d M Y, H:i') : '-',
                    'completed_at' => $record->completed_at ? $record->completed_at->format('d M Y, H:i') : '-',
                ],
            ],
        ]);
    }

    public function updateRecord(Request $request, string $type, $id)
    {
        if (!in_array($type, ['content', 'playlist'], true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid learning source type.',
            ], 404);
        }

        $validated = $request->validate([
            'progress'             => ['required', 'integer', 'min:0', 'max:100'],
            'current_time'         => ['required', 'integer', 'min:0'],
            'actual_watch_seconds' => ['required', 'integer', 'min:0'],
            'afk_duration_seconds' => ['required', 'integer', 'min:0'],
        ]);

        return DB::transaction(function () use ($type, $id, $validated) {
            $calculator = app(ActualLearningHourCalculator::class);

            if ($type === 'content') {
                $record = ContentUser::with('content')->lockForUpdate()->find($id);

                if (!$record) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Learning record not found.',
                    ], 404);
                }

                $content = $record->content;
                $contentDurationSeconds = null;
                if ($record->duration_seconds !== null && (int) $record->duration_seconds > 0) {
                    $contentDurationSeconds = (int) $record->duration_seconds;
                } elseif ($content && !empty($content->duration)) {
                    $contentDurationSeconds = $calculator->parseContentDurationToSeconds($content->duration);
                }

                if ($contentDurationSeconds !== null && $contentDurationSeconds > 0 && $validated['current_time'] > $contentDurationSeconds) {
                    throw ValidationException::withMessages([
                        'current_time' => ["Current time ({$validated['current_time']}s) cannot exceed content duration ({$contentDurationSeconds}s)."],
                    ]);
                }

                $newProgress = (int) $validated['progress'];
                $wasFinished = $record->isFinished();
                $originalCompletedAt = $record->completed_at;

                $record->progress = $newProgress;
                $record->current_time = (int) $validated['current_time'];
                $record->actual_watch_seconds = (int) $validated['actual_watch_seconds'];
                $record->afk_duration_seconds = (int) $validated['afk_duration_seconds'];

                if ($record->total_pages && (int) $record->total_pages > 0) {
                    $record->last_page = $newProgress >= 100
                        ? (int) $record->total_pages
                        : (int) floor(($newProgress * (int) $record->total_pages) / 100);
                }

                if ($newProgress >= 100) {
                    $record->finish = '1';
                    $record->learning_status = 'completed';
                    $record->completed_at = ($wasFinished && $originalCompletedAt !== null)
                        ? $originalCompletedAt
                        : now();
                } else {
                    $record->finish = '0';
                    $record->learning_status = $newProgress === 0 ? 'not_started' : 'in_progress';
                    $record->completed_at = null;
                }

                $maxLearningHours = $content?->max_learning_hours;
                $record->learning_hours = $calculator->calculateFromTracking(
                    $record->current_time,
                    $record->finish,
                    $record->progress,
                    $contentDurationSeconds ?? $content?->duration ?? $record->duration_seconds,
                    $record->actual_watch_seconds,
                    $maxLearningHours
                );

                $record->save();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Learning record updated successfully.',
                    'data' => [
                        'source_type' => 'content',
                        'source_id' => $record->id,
                        'progress' => (int) $record->progress,
                        'finish' => (int) $record->finish,
                        'learning_status' => $record->learning_status,
                        'current_time' => (int) $record->current_time,
                        'actual_watch_seconds' => (int) $record->actual_watch_seconds,
                        'afk_duration_seconds' => (int) $record->afk_duration_seconds,
                        'learning_hours' => (float) $record->learning_hours,
                        'completed_at' => $record->completed_at ? $record->completed_at->format('Y-m-d H:i:s') : null,
                    ],
                ]);
            }

            // type === 'playlist'
            $record = PlaylistContentUser::with(['content', 'trainingPlan'])->lockForUpdate()->find($id);

            if (!$record) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Playlist learning record not found.',
                ], 404);
            }

            $content = $record->content;
            $contentDurationSeconds = null;
            if ($record->duration_seconds !== null && (int) $record->duration_seconds > 0) {
                $contentDurationSeconds = (int) $record->duration_seconds;
            } elseif ($content && !empty($content->duration)) {
                $contentDurationSeconds = $calculator->parseContentDurationToSeconds($content->duration);
            }

            if ($contentDurationSeconds !== null && $contentDurationSeconds > 0 && $validated['current_time'] > $contentDurationSeconds) {
                throw ValidationException::withMessages([
                    'current_time' => ["Current time ({$validated['current_time']}s) cannot exceed content duration ({$contentDurationSeconds}s)."],
                ]);
            }

            $newProgress = (int) $validated['progress'];
            $wasFinished = $record->isFinished();
            $originalCompletedAt = $record->completed_at;

            $record->progress = $newProgress;
            $record->current_time = (int) $validated['current_time'];
            $record->actual_watch_seconds = (int) $validated['actual_watch_seconds'];
            $record->afk_duration_seconds = (int) $validated['afk_duration_seconds'];

            if ($record->total_pages && (int) $record->total_pages > 0) {
                $record->last_page = $newProgress >= 100
                    ? (int) $record->total_pages
                    : (int) floor(($newProgress * (int) $record->total_pages) / 100);
            }

            if ($newProgress >= 100) {
                $record->finish = '1';
                $record->learning_status = 'completed';
                $record->completed_at = ($wasFinished && $originalCompletedAt !== null)
                    ? $originalCompletedAt
                    : now();
            } else {
                $record->finish = '0';
                $record->learning_status = $newProgress === 0 ? 'not_started' : 'in_progress';
                $record->completed_at = null;
            }

            $maxLearningHours = $content?->max_learning_hours;
            $record->learning_hour = $calculator->calculateFromTracking(
                $record->current_time,
                $record->finish,
                $record->progress,
                $contentDurationSeconds ?? $content?->duration ?? $record->duration_seconds,
                $record->actual_watch_seconds,
                $maxLearningHours
            );

            $record->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Playlist learning record updated successfully.',
                'data' => [
                    'source_type' => 'playlist',
                    'source_id' => $record->id,
                    'progress' => (int) $record->progress,
                    'finish' => (int) $record->finish,
                    'learning_status' => $record->learning_status,
                    'current_time' => (int) $record->current_time,
                    'actual_watch_seconds' => (int) $record->actual_watch_seconds,
                    'afk_duration_seconds' => (int) $record->afk_duration_seconds,
                    'learning_hour' => (float) $record->learning_hour,
                    'completed_at' => $record->completed_at ? $record->completed_at->format('Y-m-d H:i:s') : null,
                ],
            ]);
        });
    }

    public function resetRecord(Request $request, string $type, $id)
    {
        if (!in_array($type, ['content', 'playlist'], true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid learning source type.',
            ], 404);
        }

        return DB::transaction(function () use ($type, $id) {
            if ($type === 'content') {
                $record = ContentUser::with('content')->lockForUpdate()->find($id);

                if (!$record) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Learning record not found.',
                    ], 404);
                }

                $record->progress = 0;
                $record->finish = '0';
                $record->learning_status = 'not_started';
                $record->current_time = 0;
                $record->actual_watch_seconds = 0;
                $record->afk_duration_seconds = 0;
                $record->completed_at = null;
                $record->learning_hours = 0.0;

                if ($record->last_page !== null) {
                    $record->last_page = 0;
                }

                $record->save();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Learning progress has been reset successfully.',
                    'data' => [
                        'source_type' => 'content',
                        'source_id' => $record->id,
                        'progress' => (int) $record->progress,
                        'finish' => (int) $record->finish,
                        'learning_status' => $record->learning_status,
                        'current_time' => (int) $record->current_time,
                        'actual_watch_seconds' => (int) $record->actual_watch_seconds,
                        'afk_duration_seconds' => (int) $record->afk_duration_seconds,
                        'learning_hours' => (float) $record->learning_hours,
                        'completed_at' => null,
                    ],
                ]);
            }

            // type === 'playlist'
            $record = PlaylistContentUser::with(['content', 'trainingPlan'])->lockForUpdate()->find($id);

            if (!$record) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Playlist learning record not found.',
                ], 404);
            }

            $record->progress = 0;
            $record->finish = '0';
            $record->learning_status = 'not_started';
            $record->current_time = 0;
            $record->actual_watch_seconds = 0;
            $record->afk_duration_seconds = 0;
            $record->completed_at = null;
            $record->learning_hour = 0.0;

            if ($record->last_page !== null) {
                $record->last_page = 0;
            }

            $record->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Learning progress has been reset successfully.',
                'data' => [
                    'source_type' => 'playlist',
                    'source_id' => $record->id,
                    'progress' => (int) $record->progress,
                    'finish' => (int) $record->finish,
                    'learning_status' => $record->learning_status,
                    'current_time' => (int) $record->current_time,
                    'actual_watch_seconds' => (int) $record->actual_watch_seconds,
                    'afk_duration_seconds' => (int) $record->afk_duration_seconds,
                    'learning_hour' => (float) $record->learning_hour,
                    'completed_at' => null,
                ],
            ]);
        });
    }

    private function learningViewData(Request $request, string $title, string $downloadslug = ''): array
    {
        return [
            'title' => $title,
            'years' => $this->learningYears(),
            'entries' => (int) $request->input('entries', 25),
            'search' => (string) $request->input('search', ''),
            'downloadslug' => $downloadslug,
            'content' => collect(),
        ];
    }

    public static function learningYears(): array
    {
        return range((int) date('Y'), (int) date('Y') - 4);
    }

    private function normalizeExportFilter($value): string
    {
        if ($value === null || $value === '' || $value === 'all') {
            return '';
        }

        return (string) $value;
    }

    private function durationToHours($duration): float
    {
        if (!$duration) {
            return 0;
        }

        return DurationFormatter::toHours($duration);
}
}
