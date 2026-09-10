<?php

namespace App\Http\Controllers;

use App\Exports\LearningExport;
use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\User;
use App\Services\Learning\LearningDatatableService;
use App\Support\DurationFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class LearningManageController extends Controller
{
    public const PLAYLIST_CUTOFF = '2026-08-28 23:59:59';

    public function index()
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

        $classification = DB::table('classification')
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

        $learningHours = round((float) $classification->sum('learning_hours'), 1);

        return view('main.learning.index', [
            'ongoing' => $ongoing,
            'finish' => $finish,
            'totalLearning' => $totalLearning,
            'completionRate' => $completionRate,
            'learningHours' => $learningHours,
            'classification' => $classification,
        ]);
    }

    public function all(Request $request)
    {
        if ($request->input('action') === 'download') {
            return (new LearningExport($request->all(), '', ''))
                ->download(now()->format('Ymd_His') . '_learning.xlsx');
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

        return view('main.learning.all', $this->learningViewData(
            $request,
            'Content: ' . ($contentModel->title ?? $slug),
            $slug
        ));
    }

    public function searchEmployees(Request $request)
    {
        $query = trim((string) ($request->get('q') ?? ($request->get('search') ?? $request->get('employee_q', ''))));
        if (!$query) {
            return response()->json([]);
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

        return response()->json($results);
    }

    public function searchTitles(Request $request)
    {
        $term = trim((string) ($request->input('q') ?? ($request->input('search') ?? $request->input('title_q', ''))));
        $results = [];

        $query = Content::with('classification:id,classification_name')
            ->select('id', 'title', 'classification_id', 'slug')
            ->whereNotNull('title')
            ->where('title', '!=', '');

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
        return (new LearningExport($request->all(), $request->input('type', ''), $request->input('slug', '')))
            ->download(now()->format('Ymd_His') . '_learning_data.xlsx');
    }

    public function datatable(Request $request, LearningDatatableService $datatableService)
    {
        return $datatableService->make($request);
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

    private function learningYears(): array
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
