<?php

namespace App\Http\Controllers;

use App\Exports\LearningExport;
use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\User;
use App\Services\Learning\LearningDatatableService;
use App\Support\DurationFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class LearningManageController extends Controller
{
    public function index()
    {
        $totalLearning = ContentUser::count();
        $ongoing = ContentUser::where('finish', '0')->count();
        $finish = ContentUser::where('finish', '1')->count();

        $completionRate = $totalLearning > 0
            ? round(($finish / $totalLearning) * 100, 1)
            : 0;

        $classification = DB::table('classification')
            ->leftJoin('content', function ($join) {
                $join->on('content.classification_id', '=', 'classification.id')
                    ->whereNull('content.deleted_at');
            })
            ->leftJoin('content_users', 'content_users.content_id', '=', 'content.id')
            ->select([
                'classification.id',
                DB::raw('MIN(classification.classification_name) as classification_name'),
                DB::raw('MIN(classification.slug) as slug'),
                DB::raw('COUNT(DISTINCT content.id) as content_total'),
                DB::raw('COUNT(content_users.id) as total_learning'),

                DB::raw("
                    COALESCE(
                        SUM(
                            CASE 
                                WHEN content_users.finish = '0' THEN 1 
                                ELSE 0 
                            END
                        ), 0
                    ) as ongoing_learning
                "),

                DB::raw("
                    COALESCE(
                        SUM(
                            CASE 
                                WHEN content_users.finish = '1' THEN 1 
                                ELSE 0 
                            END
                        ), 0
                    ) as finished_learning
                "),

                DB::raw("
                    COALESCE(
                        SUM(
                            CASE 
                                WHEN content_users.id IS NOT NULL 
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
            return Excel::download(
                new LearningExport($request->all(), '', ''),
                now()->format('Ymd_His') . '_learning.xlsx'
            );
        }

        return view('main.learning.all', $this->learningViewData($request, 'All Learning'));
    }

    public function category($slug, Request $request)
    {
        $classification = Classification::where('slug', $slug)->firstOrFail();

        if ($request->input('action') === 'download') {
            return Excel::download(
                new LearningExport($request->all(), 'category', $slug),
                now()->format('Ymd_His') . '_learning.xlsx'
            );
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
            return Excel::download(
                new LearningExport($request->all(), 'content', $slug),
                now()->format('Ymd_His') . '_learning.xlsx'
            );
        }

        return view('main.learning.all', $this->learningViewData(
            $request,
            'Content: ' . ($contentModel->title ?? $slug),
            $slug
        ));
    }

    public function searchEmployees(Request $request)
    {
        $term = trim((string) ($request->input('q') ?? $request->input('search', '')));
        $results = [];

        if ($term === '') {
            $users = User::select('id', 'name', 'id_emp')
                ->whereNotNull('name')
                ->where('name', '!=', '')
                ->limit(10)
                ->get();

            foreach ($users as $user) {
                $nip = $user->id_emp ? " (NIP: {$user->id_emp})" : '';
                $results[] = [
                    'id' => $user->name,
                    'text' => $user->name . $nip,
                ];
            }

            return response()->json(['results' => $results]);
        }

        // Search Users by Name or NIP
        $users = User::select('id', 'name', 'id_emp')
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('id_emp', 'like', "%{$term}%");
            })
            ->limit(10)
            ->get();

        foreach ($users as $user) {
            $nip = $user->id_emp ? " (NIP: {$user->id_emp})" : '';
            $results[] = [
                'id' => $user->name,
                'text' => $user->name . $nip,
            ];
        }

        // Search Content by Title
        $contents = Content::select('id', 'title')
            ->where('title', 'like', "%{$term}%")
            ->limit(10)
            ->get();

        foreach ($contents as $content) {
            $results[] = [
                'id' => $content->title,
                'text' => $content->title,
            ];
        }

        return response()->json(['results' => array_slice($results, 0, 20)]);
    }

    public function download(Request $request)
    {
        return Excel::download(
            new LearningExport($request->all(), $request->input('type', ''), $request->input('slug', '')),
            now()->format('Ymd_His') . '_learning_data.xlsx'
        );
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
