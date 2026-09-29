<?php

namespace App\Http\Controllers\Manage\Record;

use App\Http\Controllers\Controller;
use App\Services\Record\RecordAllCombinedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RecordAllController extends Controller
{
    public const STATIC_CLASSIFICATIONS = [
        'Basic & Common Duties',
        'ISO Standard',
        'Innovation',
        'Job Functional',
        'Language',
        'Leadership',
        'Maintenance',
        'Other',
        'SHE',
        'Technology',
        'Competency',
        'Management',
        'Technical',
        'Quality & Safety',
        'General',
    ];

    protected RecordAllCombinedService $combinedService;

    public function __construct(RecordAllCombinedService $combinedService)
    {
        $this->combinedService = $combinedService;
    }

    /**
     * Render the lightweight All KP OWL landing overview shell.
     */
    public function index(Request $request)
    {
        return view('main.record.all', [
            'title'           => 'All KP OWL',
            'subtitle'        => 'Combined Online & Offline Learning Record',
            'classifications' => [],
            'years'           => [],
            'entries'         => (int) $request->input('entries', 25),
        ]);
    }

    /**
     * Asynchronously return combined KPI and Source summary.
     */
    public function ajaxSummary(Request $request): JsonResponse
    {
        $summary = $this->combinedService->getSummary();

        return response()->json([
            'success'              => true,
            'total_learning'       => $summary['total_learning'],
            'ongoing'              => $summary['ongoing'],
            'finished'             => $summary['finished'],
            'total_learning_hours' => $summary['total_learning_hours'],
            'completion_rate'      => $summary['completion_rate'],
            'data'                 => $summary,
            'html'                 => view('main.record.components.all-kpi-summary', $summary)->render(),
        ]);
    }

    /**
     * Asynchronously return combined Classification Summary.
     */
    public function ajaxClassification(Request $request): JsonResponse
    {
        $classification = $this->combinedService->getClassificationSummary();

        if ($request->input('format') === 'json') {
            return response()->json([
                'success' => true,
                'data'    => $classification,
            ]);
        }

        return response()->json([
            'success' => true,
            'data'    => $classification,
            'html'    => view('main.record.components.all-classification-summary', [
                'classification' => $classification,
            ])->render(),
        ]);
    }

    /**
     * Render the scoped classification detail view for All KP OWL.
     */
    public function classification(string $classification, Request $request)
    {
        if ($request->input('action') === 'download') {
            return $this->classificationDownload($classification, $request);
        }

        $resolved = self::resolveClassification($classification, true);
        $filterOptions = $this->combinedService->getFilterOptions();

        return view('main.record.all.classification', [
            'title'              => "Classification: {$resolved['name']}",
            'subtitle'           => "Combined Online & Offline Learning Records for {$resolved['name']}",
            'classificationName' => $resolved['name'],
            'slug'               => $resolved['slug'],
            'isUnclassified'     => $resolved['is_unclassified'],
            'years'              => $filterOptions['years'],
            'departments'        => $filterOptions['departments'] ?? [],
            'companies'          => $filterOptions['companies'] ?? [],
            'entries'            => (int) $request->input('entries', 25),
        ]);
    }

    /**
     * Download Excel export locked to the given classification.
     */
    public function classificationDownload(string $classification, Request $request)
    {
        $resolved = self::resolveClassification($classification, true);
        $request->merge([
            'classification' => $resolved['is_unclassified'] ? 'Unclassified' : $resolved['name'],
        ]);

        $export = new \App\Exports\AllClassificationExport($resolved['name'], $request);

        return $export->download($export->getFileName());
    }

    /**
     * Server-side DataTable locked to the given classification.
     */
    public function classificationDatatable(string $classification, Request $request): JsonResponse
    {
        $resolved = self::resolveClassification($classification, true);
        $request->merge([
            'classification' => $resolved['is_unclassified'] ? 'Unclassified' : $resolved['name'],
        ]);

        return $this->datatable($request);
    }

    /**
     * Server-side DataTable for combined learning records.
     */
    public function datatable(Request $request): JsonResponse
    {
        $classParam = trim((string) ($request->input('classification', 'all')));
        if ($classParam !== '' && strtolower($classParam) !== 'all') {
            $resolved = self::resolveClassification($classParam, false);
            $request->merge([
                'classification' => $resolved['is_unclassified'] ? 'Unclassified' : $resolved['name'],
            ]);
        }

        return $this->combinedService->datatable($request);
    }

    /**
     * Resolve a classification slug/string into canonical name, slug, and unclassified flag.
     * Aborts 404 if not found and $abortIfNotFound is true.
     */
    public static function resolveClassification(string $input, bool $abortIfNotFound = true): array
    {
        $trimmed = trim($input);
        $slugified = Str::slug($trimmed);

        if ($trimmed === '' || $slugified === '' || $slugified === 'unclassified' || strtolower($trimmed) === 'unclassified') {
            return [
                'name'            => 'Unclassified',
                'slug'            => 'unclassified',
                'is_unclassified' => true,
            ];
        }

        $dbOnline = DB::table('classification')
            ->whereNotNull('classification_name')
            ->where('classification_name', '!=', '')
            ->distinct()
            ->pluck('classification_name')
            ->all();

        $dbOffline = [];
        if (Schema::hasTable('offline_trainings')) {
            $dbOffline = DB::table('offline_trainings')
                ->whereNull('deleted_at')
                ->whereNotNull('main_classification')
                ->where('main_classification', '!=', '')
                ->distinct()
                ->pluck('main_classification')
                ->all();
        }

        $candidates = array_values(array_unique(array_filter(array_map('trim', array_merge($dbOnline, $dbOffline, self::STATIC_CLASSIFICATIONS)))));

        foreach ($candidates as $candidate) {
            if (Str::slug($candidate) === $slugified || strtolower(trim($candidate)) === strtolower($trimmed)) {
                return [
                    'name'            => $candidate,
                    'slug'            => Str::slug($candidate),
                    'is_unclassified' => false,
                ];
            }
        }

        if ($abortIfNotFound) {
            abort(404, 'Classification not found');
        }

        return [
            'name'            => ucwords(str_replace('-', ' ', $slugified)),
            'slug'            => $slugified,
            'is_unclassified' => false,
        ];
    }
}
