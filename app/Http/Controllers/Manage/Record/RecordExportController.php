<?php

namespace App\Http\Controllers\Manage\Record;

use App\Exports\AllRecordExport;
use App\Exports\LearningExport;
use App\Exports\OfflineTrainingCsvExport;
use App\Exports\OfflineTrainingExport;
use App\Exports\OnlineRecordCsvExport;
use App\Exports\Record\OnlineFullRecordExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\LearningManageController;
use App\Models\Classification;
use App\Models\OfflineTraining;
use App\Models\OfflineTrainingOption;
use App\Models\User;
use App\Services\Learning\LearningDatatableService;
use App\Services\Record\OfflineTrainingExcelSchema;
use App\Services\Record\OnlineFullRecordExportService;
use App\Services\Record\RecordAllCombinedService;
use App\Services\Record\RecordOfflineQueryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class RecordExportController extends Controller
{
    protected RecordAllCombinedService $combinedService;

    public function __construct(RecordAllCombinedService $combinedService)
    {
        $this->combinedService = $combinedService;
    }

    /**
     * Render the Learning Record Export Center page.
     */
    public function index(Request $request)
    {
        $currentYear = (int) date('Y');
        $years = LearningManageController::learningYears();

        // 1. Combined Filter Options (Classifications, Departments, Companies)
        $filterOptions = $this->combinedService->getFilterOptions();

        // 2. Offline Training Types
        $trainingTypes = RecordOfflineController::TRAINING_TYPES;

        // 3. Offline Distinct Categories
        $offlineCategories = [];
        if (Schema::hasTable('offline_trainings')) {
            $offlineCategories = DB::table('offline_trainings')
                ->whereNull('deleted_at')
                ->whereNotNull('category')
                ->where('category', '!=', '')
                ->distinct()
                ->pluck('category')
                ->sort()
                ->values()
                ->all();
        }

        // 4. Offline Distinct Providers & Delivery Methods
        $providers = ['Internal', 'External'];
        $deliveryMethods = ['Classroom', 'Hybrid', 'Practical'];
        if (Schema::hasTable('offline_trainings')) {
            $dbProviders = DB::table('offline_trainings')
                ->whereNull('deleted_at')
                ->whereNotNull('provider')
                ->where('provider', '!=', '')
                ->distinct()
                ->pluck('provider')
                ->all();
            if (!empty($dbProviders)) {
                $providers = array_values(array_unique(array_filter(array_merge($providers, $dbProviders))));
            }

            $dbDelivery = DB::table('offline_trainings')
                ->whereNull('deleted_at')
                ->whereNotNull('delivery_method')
                ->where('delivery_method', '!=', '')
                ->distinct()
                ->pluck('delivery_method')
                ->all();
            if (!empty($dbDelivery)) {
                $deliveryMethods = array_values(array_unique(array_filter(array_merge($deliveryMethods, $dbDelivery))));
            }
        }

        // 5. Offline Distinct Grades
        $grades = [];
        if (Schema::hasTable('offline_training_users')) {
            $grades = DB::table('offline_training_users')
                ->whereNull('deleted_at')
                ->whereNotNull('grade')
                ->where('grade', '!=', '')
                ->distinct()
                ->pluck('grade')
                ->sort()
                ->values()
                ->all();
        }

        // 6. Sub Classification Map by Main Classification
        $subClassMap = [];
        if (Schema::hasTable('offline_trainings')) {
            $subRecords = DB::table('offline_trainings')
                ->whereNull('deleted_at')
                ->whereNotNull('main_classification')
                ->whereNotNull('sub_classification')
                ->where('sub_classification', '!=', '')
                ->select('main_classification', 'sub_classification')
                ->distinct()
                ->get();

            foreach ($subRecords as $sr) {
                $m = trim((string) $sr->main_classification);
                $s = trim((string) $sr->sub_classification);
                if ($m !== '' && $s !== '') {
                    $subClassMap[$m][] = $s;
                }
            }

            foreach ($subClassMap as $m => $subs) {
                $subClassMap[$m] = array_values(array_unique($subs));
                sort($subClassMap[$m]);
            }
        }

        // 7. Distinct Divisions
        $divisions = DB::table('users')
            ->whereNotNull('division')
            ->where('division', '!=', '')
            ->distinct()
            ->pluck('division')
            ->sort()
            ->values()
            ->all();

        // 8. Distinct Teams
        $teams = DB::table('users')
            ->whereNotNull('team')
            ->where('team', '!=', '')
            ->distinct()
            ->pluck('team')
            ->toArray();
        if (Schema::hasTable('offline_training_users')) {
            $offlineTeams = DB::table('offline_training_users')
                ->whereNull('deleted_at')
                ->whereNotNull('team')
                ->where('team', '!=', '')
                ->distinct()
                ->pluck('team')
                ->toArray();
            if (!empty($offlineTeams)) {
                $teams = array_merge($teams, $offlineTeams);
            }
        }
        $teams = array_values(array_unique(array_filter(array_map('trim', $teams))));
        sort($teams);

        // 9. Placeholder Groups
        $groups = ['Management', 'Operations', 'Technical', 'General Staff', 'Contractor'];

        // 10. Distinct Course / Training Titles (Online, Offline, Combined)
        $onlineContentTitles = [];
        if (Schema::hasTable('content')) {
            $onlineContentTitles = DB::table('content')
                ->whereNotNull('title')
                ->where('title', '!=', '')
                ->distinct()
                ->pluck('title')
                ->toArray();
        }
        $onlinePlaylistTitles = [];
        if (Schema::hasTable('trainingplan')) {
            $onlinePlaylistTitles = DB::table('trainingplan')
                ->whereNotNull('title')
                ->where('title', '!=', '')
                ->distinct()
                ->pluck('title')
                ->toArray();
        }
        $onlineTitles = array_values(array_unique(array_filter(array_map('trim', array_merge($onlineContentTitles, $onlinePlaylistTitles)))));
        sort($onlineTitles);

        $offlineTitles = [];
        if (Schema::hasTable('offline_trainings')) {
            $offlineTitles = DB::table('offline_trainings')
                ->whereNull('deleted_at')
                ->whereNotNull('course_name')
                ->where('course_name', '!=', '')
                ->distinct()
                ->pluck('course_name')
                ->toArray();
            $offlineTitles = array_values(array_unique(array_filter(array_map('trim', $offlineTitles))));
            sort($offlineTitles);
        }

        $courseTitles = array_values(array_unique(array_filter(array_merge($onlineTitles, $offlineTitles))));
        sort($courseTitles);

        return view('main.record.export-all', [
            'title'               => 'Export KP OWL',
            'subtitle'            => 'Learning Record Export Center',
            'years'               => $years,
            'currentYear'         => $currentYear,
            'classifications'     => $filterOptions['classifications'] ?? [],
            'departments'         => $filterOptions['departments'] ?? [],
            'divisions'           => $divisions,
            'companies'           => $filterOptions['companies'] ?? [],
            'teams'               => $teams,
            'groups'              => $groups,
            'trainingTypes'       => $trainingTypes,
            'offlineCategories'   => $offlineCategories,
            'providers'           => $providers,
            'deliveryMethods'     => $deliveryMethods,
            'grades'              => $grades,
            'subClassMap'         => $subClassMap,
            'onlineTitles'        => $onlineTitles,
            'offlineTitles'       => $offlineTitles,
            'courseTitles'        => $courseTitles,
        ]);
    }

    /**
     * AJAX endpoint to get sub-classifications for a given main classification.
     */
    public function subClassifications(Request $request): JsonResponse
    {
        $mainClass = trim((string) $request->input('classification', ''));

        $query = DB::table('offline_trainings')
            ->whereNull('deleted_at')
            ->whereNotNull('sub_classification')
            ->where('sub_classification', '!=', '');

        if ($mainClass !== '' && $mainClass !== 'all') {
            $query->where('main_classification', $mainClass);
        }

        $fromTrainings = $query->distinct()->pluck('sub_classification')->toArray();

        // Also check offline_training_options
        $fromOptions = [];
        if (Schema::hasTable('offline_training_options')) {
            $fromOptions = DB::table('offline_training_options')
                ->where('option_type', 'sub_classification')
                ->where('is_active', true)
                ->pluck('value')
                ->toArray();
        }

        if ($mainClass !== '' && $mainClass !== 'all') {
            // When a specific classification is chosen, keep only training-linked options if available
            $results = !empty($fromTrainings) ? $fromTrainings : $fromOptions;
        } else {
            $results = array_merge($fromTrainings, $fromOptions);
        }

        $results = array_values(array_unique(array_filter(array_map('trim', $results))));
        sort($results);

        return response()->json([
            'success' => true,
            'data'    => $results,
        ]);
    }

    /**
     * AJAX endpoint to get options for Section 3 two-stage filter.
     */
    public function scopeOptions(Request $request): JsonResponse
    {
        $scopeType = strtolower(trim((string) $request->input('type', 'company')));
        $search = trim((string) $request->input('q', ''));

        $options = [];

        switch ($scopeType) {
            case 'company':
                $userCompanies = DB::table('users')
                    ->whereNotNull('company_name')
                    ->where('company_name', '!=', '')
                    ->distinct()
                    ->pluck('company_name')
                    ->toArray();
                $offlineCompanies = [];
                if (Schema::hasTable('offline_training_users')) {
                    $offlineCompanies = DB::table('offline_training_users')
                        ->whereNull('deleted_at')
                        ->whereNotNull('company')
                        ->where('company', '!=', '')
                        ->distinct()
                        ->pluck('company')
                        ->toArray();
                }
                $companies = array_values(array_unique(array_filter(array_merge($userCompanies, $offlineCompanies))));
                sort($companies);
                if ($search !== '') {
                    $companies = array_values(array_filter($companies, fn($item) => stripos($item, $search) !== false));
                }
                $options = array_map(fn($item) => ['id' => $item, 'text' => $item], $companies);
                break;

            case 'division':
                $query = DB::table('users')
                    ->whereNotNull('division')
                    ->where('division', '!=', '');
                if ($search !== '') {
                    $query->where('division', 'like', "%{$search}%");
                }
                $divisions = $query->distinct()->pluck('division')->sort()->values()->toArray();
                $options = array_map(fn($item) => ['id' => $item, 'text' => $item], $divisions);
                break;

            case 'department':
                $userDepts = DB::table('users')
                    ->whereNotNull('department')
                    ->where('department', '!=', '')
                    ->distinct()
                    ->pluck('department')
                    ->toArray();
                $offlineDepts = [];
                if (Schema::hasTable('offline_training_users')) {
                    $offlineDepts = DB::table('offline_training_users')
                        ->whereNull('deleted_at')
                        ->whereNotNull('department')
                        ->where('department', '!=', '')
                        ->distinct()
                        ->pluck('department')
                        ->toArray();
                }
                $depts = array_values(array_unique(array_filter(array_merge($userDepts, $offlineDepts))));
                sort($depts);
                if ($search !== '') {
                    $depts = array_values(array_filter($depts, fn($item) => stripos($item, $search) !== false));
                }
                $options = array_map(fn($item) => ['id' => $item, 'text' => $item], $depts);
                break;

            case 'team':
            case 'team_section':
                $userTeams = DB::table('users')
                    ->whereNotNull('team')
                    ->where('team', '!=', '')
                    ->distinct()
                    ->pluck('team')
                    ->toArray();
                $offlineTeams = [];
                if (Schema::hasTable('offline_training_users')) {
                    $offlineTeams = DB::table('offline_training_users')
                        ->whereNull('deleted_at')
                        ->whereNotNull('team')
                        ->where('team', '!=', '')
                        ->distinct()
                        ->pluck('team')
                        ->toArray();
                }
                $teams = array_values(array_unique(array_filter(array_merge($userTeams, $offlineTeams))));
                sort($teams);
                if ($search !== '') {
                    $teams = array_values(array_filter($teams, fn($item) => stripos($item, $search) !== false));
                }
                $options = array_map(fn($item) => ['id' => $item, 'text' => $item], $teams);
                break;

            case 'group':
                $groups = ['Management', 'Operations', 'Technical', 'General Staff', 'Contractor'];
                if ($search !== '') {
                    $groups = array_values(array_filter($groups, fn($item) => stripos($item, $search) !== false));
                }
                $options = array_map(fn($item) => ['id' => $item, 'text' => $item], $groups);
                break;

            case 'individual':
            case 'employee':
            case 'employee_id':
            case 'learner':
                $query = DB::table('users')
                    ->select('id_emp', 'name', 'team')
                    ->where(function ($q) {
                        $q->whereNotNull('id_emp')->where('id_emp', '!=', '')
                          ->orWhere(function ($q2) {
                              $q2->whereNotNull('name')->where('name', '!=', '');
                          });
                    });

                if ($search !== '') {
                    $query->where(function ($q) use ($search) {
                        $q->where('id_emp', 'like', "%{$search}%")
                          ->orWhere('name', 'like', "%{$search}%")
                          ->orWhere('team', 'like', "%{$search}%");
                    });
                }
                $users = $query->limit(50)->get();
                $options = [];
                foreach ($users as $u) {
                    $empId = trim((string) $u->id_emp);
                    $name = trim((string) $u->name);
                    $team = trim((string) ($u->team ?? ''));
                    $teamName = $team !== '' ? $team : 'No Team';
                    $idVal = $empId !== '' ? $empId : $name;

                    if ($empId !== '' && $name !== '') {
                        $label = "{$empId} - {$name} - {$teamName}";
                    } elseif ($empId !== '') {
                        $label = "{$empId} - {$teamName}";
                    } elseif ($name !== '') {
                        $label = "{$name} - {$teamName}";
                    } else {
                        continue;
                    }

                    $options[] = [
                        'id'   => $idVal,
                        'text' => $label,
                    ];
                }
                break;

            default:
                $options = [];
                break;
        }

        return response()->json([
            'success' => true,
            'type'    => $scopeType,
            'data'    => $options,
            'results' => $options,
        ]);
    }

    /**
     * AJAX endpoint to preview filtered data before exporting (limited to 25 records).
     */
    public function preview(Request $request): JsonResponse
    {
        $source = strtolower(trim((string) $request->input('source', 'online')));
        if (!in_array($source, ['online', 'offline', 'all'], true)) {
            $source = 'online';
        }

        $filters = $this->normalizeFilters($request->all());
        $previewLimit = (int) $request->input('limit', 25);
        if ($previewLimit < 5 || $previewLimit > 100) {
            $previewLimit = 25;
        }

        $rows = [];
        $totalRecords = 0;

        if ($source === 'online') {
            $query = LearningDatatableService::buildBaseQuery($filters);
            $totalRecords = (clone $query)->count();

            $records = $query->orderBy('unified.created_at', 'desc')
                ->orderBy('unified.source_type', 'desc')
                ->orderBy('unified.source_id', 'desc')
                ->limit($previewLimit)
                ->get();

            $no = 1;
            foreach ($records as $r) {
                $title = $r->content_title ?? '-';
                if (!empty($r->playlist_title)) {
                    $title .= ' (Playlist: ' . $r->playlist_title . ')';
                }

                $rawType = (string) ($r->content_type ?? '');
                $type = in_array(strtolower($rawType), ['video', 'youtube', 'mooc'], true) ? 'Video' : ($rawType ?: 'Document');
                if (($r->source_type ?? '') === 'playlist') {
                    $type = 'Playlist';
                }

                $statusStr = 'Not Started';
                $progress = (int) ($r->progress ?? 0);
                if ((string) ($r->finish ?? '') === '1' || $progress >= 100) {
                    $statusStr = 'Finished';
                } elseif ($progress > 0) {
                    $statusStr = 'Ongoing';
                }

                $dateStr = '-';
                if (!empty($r->created_at)) {
                    $dateStr = date('Y-m-d', strtotime($r->created_at));
                }

                $rows[] = [
                    'no'                 => $no++,
                    'source'             => 'online',
                    'source_label'       => 'KP OWL',
                    'source_badge_class' => 'badge-kp-owl',
                    'employee_id'        => $r->id_emp ?: '-',
                    'employee_name'      => $r->employee_name ?: ($r->user_name ?: '-'),
                    'department'         => $r->user_department ?: '-',
                    'company'            => '-',
                    'title'              => $title,
                    'type'               => $type,
                    'classification'     => $r->classification_name ?: 'Unclassified',
                    'date'               => $dateStr,
                    'hours'              => '-',
                    'progress'           => $progress,
                    'status'             => $statusStr,
                ];
            }
        } elseif ($source === 'offline') {
            $query = RecordOfflineQueryService::buildGlobalQuery($filters);
            $totalRecords = (clone $query)->count();

            $records = $query->orderBy('ot.start_date', 'desc')
                ->orderBy('ot.id', 'desc')
                ->orderBy('otu.id', 'asc')
                ->limit($previewLimit)
                ->get();

            $no = 1;
            foreach ($records as $r) {
                $statusStr = 'Not Started';
                $progress = (int) ($r->progress ?? 0);
                if ($progress >= 100) {
                    $statusStr = 'Finished';
                } elseif ($progress > 0) {
                    $statusStr = 'Ongoing';
                }

                $dateStr = '-';
                if (!empty($r->start_date)) {
                    $dateStr = date('Y-m-d', strtotime($r->start_date));
                } elseif (!empty($r->finish_date)) {
                    $dateStr = date('Y-m-d', strtotime($r->finish_date));
                }

                $hoursStr = $r->learning_hours !== null ? number_format((float) $r->learning_hours, 2) : '0.00';

                $rows[] = [
                    'no'                 => $no++,
                    'source'             => 'offline',
                    'source_label'       => 'Non KP OWL',
                    'source_badge_class' => 'badge-non-kp-owl',
                    'employee_id'        => $r->employee_id ?: '-',
                    'employee_name'      => $r->participant_name ?: ($r->user_name ?: '-'),
                    'department'         => $r->department ?: '-',
                    'company'            => $r->company ?: '-',
                    'title'              => $r->course_name ?: '-',
                    'type'               => $r->type ?: 'Offline Training',
                    'classification'     => $r->main_classification ?: 'Unclassified',
                    'date'               => $dateStr,
                    'hours'              => $hoursStr,
                    'progress'           => $progress,
                    'status'             => $statusStr,
                ];
            }
        } else {
            // source === 'all'
            $query = $this->combinedService->buildCombinedQuery($filters);
            $totalRecords = (clone $query)->count();

            $records = $query->orderBy('raw_finish_date', 'desc')
                ->orderBy('record_date', 'desc')
                ->limit($previewLimit)
                ->get();

            $no = 1;
            foreach ($records as $r) {
                $isOffline = ($r->source === 'offline');
                $dateStr = '-';
                if (!empty($r->finish_date)) {
                    $dateStr = date('Y-m-d', strtotime($r->finish_date));
                } elseif (!empty($r->start_date)) {
                    $dateStr = date('Y-m-d', strtotime($r->start_date));
                } elseif (!empty($r->record_date)) {
                    $dateStr = date('Y-m-d', strtotime($r->record_date));
                }

                $hoursStr = $r->learning_hours !== null ? number_format((float) $r->learning_hours, 2) : '0.00';

                $rows[] = [
                    'no'                 => $no++,
                    'source'             => $r->source,
                    'source_label'       => $isOffline ? 'Non KP OWL' : 'KP OWL',
                    'source_badge_class' => $isOffline ? 'badge-non-kp-owl' : 'badge-kp-owl',
                    'employee_id'        => $r->employee_id ?: '-',
                    'employee_name'      => $r->employee_name ?: '-',
                    'department'         => $r->department ?: '-',
                    'company'            => $r->company ?: '-',
                    'title'              => $r->learning_title ?: '-',
                    'type'               => $r->type ?: '-',
                    'classification'     => $r->classification ?: 'Unclassified',
                    'date'               => $dateStr,
                    'hours'              => $hoursStr,
                    'progress'           => (int) ($r->progress ?? 0),
                    'status'             => $r->status ? ucfirst((string) $r->status) : 'Not Started',
                ];
            }
        }

        $showingCount = count($rows);
        $message = $totalRecords > 0
            ? "Showing {$showingCount} of " . number_format($totalRecords) . " matching records"
            : "No records found matching current filters";

        return response()->json([
            'success'      => true,
            'source'       => $source,
            'total'        => $totalRecords,
            'limit'        => $previewLimit,
            'showing'      => $showingCount,
            'message'      => $message,
            'data'         => $rows,
        ]);
    }

    /**
     * Normalize period and general filters for preview and export queries.
     */
    protected function normalizeFilters(array $filters): array
    {
        if (isset($filters['year'])) {
            if (is_array($filters['year'])) {
                $cleanedYears = array_values(array_filter(array_map('trim', $filters['year']), fn($y) => $y !== '' && strtolower($y) !== 'all'));
                $filters['year'] = !empty($cleanedYears) ? (count($cleanedYears) === 1 ? $cleanedYears[0] : $cleanedYears) : null;
                $filters['years'] = $cleanedYears;
            } elseif (strtolower(trim((string) $filters['year'])) === 'all' || trim((string) $filters['year']) === '') {
                $filters['year'] = null;
                $filters['years'] = [];
            } else {
                $filters['years'] = [$filters['year']];
            }
        }

        if (isset($filters['month'])) {
            if (is_array($filters['month'])) {
                $cleanedMonths = array_values(array_filter(array_map('trim', $filters['month']), fn($m) => $m !== '' && strtolower($m) !== 'all'));
                $filters['month'] = !empty($cleanedMonths) ? (count($cleanedMonths) === 1 ? $cleanedMonths[0] : $cleanedMonths) : null;
                $filters['months'] = $cleanedMonths;
            } elseif (strtolower(trim((string) $filters['month'])) === 'all' || trim((string) $filters['month']) === '') {
                $filters['month'] = null;
                $filters['months'] = [];
            } else {
                $filters['months'] = [$filters['month']];
            }
        }

        if (isset($filters['learning_title'])) {
            if (is_array($filters['learning_title'])) {
                $cleanedTitles = array_values(array_filter(array_map('trim', $filters['learning_title']), fn($t) => $t !== '' && strtolower($t) !== 'all'));
                $filters['learning_title'] = !empty($cleanedTitles) ? $cleanedTitles : null;
                $filters['learning_titles'] = $cleanedTitles;
            } elseif (strtolower(trim((string) $filters['learning_title'])) === 'all' || trim((string) $filters['learning_title']) === '') {
                $filters['learning_title'] = null;
                $filters['learning_titles'] = [];
            } else {
                $filters['learning_titles'] = [trim((string) $filters['learning_title'])];
            }
        }

        $periodType = ucfirst(strtolower(trim((string) ($filters['period_type'] ?? ''))));

        if ($periodType === 'Month') {
            $yList = (array) ($filters['years'] ?? ($filters['year'] ?? []));
            $mList = (array) ($filters['months'] ?? ($filters['month'] ?? []));
            $yList = array_values(array_filter($yList, 'is_numeric'));
            $mList = array_values(array_filter($mList, 'is_numeric'));

            if (!empty($yList) && !empty($mList)) {
                sort($yList);
                sort($mList);
                $minY = (int) $yList[0];
                $maxY = (int) end($yList);
                $minM = (int) $mList[0];
                $maxM = (int) end($mList);

                if (empty($filters['from_date'])) {
                    $filters['from_date'] = Carbon::createFromDate($minY, $minM, 1)->startOfMonth()->toDateString();
                }
                if (empty($filters['to_date'])) {
                    $filters['to_date'] = Carbon::createFromDate($maxY, $maxM, 1)->endOfMonth()->toDateString();
                }
            }
        } elseif ($periodType === 'Year') {
            $yList = (array) ($filters['years'] ?? ($filters['year'] ?? []));
            $yList = array_values(array_filter($yList, 'is_numeric'));
            if (!empty($yList)) {
                sort($yList);
                $minY = (int) $yList[0];
                $maxY = (int) end($yList);
                if (empty($filters['from_date'])) {
                    $filters['from_date'] = Carbon::createFromDate($minY, 1, 1)->startOfYear()->toDateString();
                }
                if (empty($filters['to_date'])) {
                    $filters['to_date'] = Carbon::createFromDate($maxY, 12, 31)->endOfYear()->toDateString();
                }
            }
        } elseif ($periodType === 'Quarter' || $periodType === 'Quarterly') {
            $yList = (array) ($filters['years'] ?? ($filters['year'] ?? []));
            $yList = array_values(array_filter($yList, 'is_numeric'));
            $yearVal = !empty($yList) ? (int) $yList[0] : (int) date('Y');

            $qVal = strtoupper(trim((string) ($filters['quarter'] ?? 'Q1')));
            $quarterMonths = [
                'Q1' => [1, 3],
                'Q2' => [4, 6],
                'Q3' => [7, 9],
                'Q4' => [10, 12],
            ];
            $qRange = $quarterMonths[$qVal] ?? [1, 3];
            if (empty($filters['from_date'])) {
                $filters['from_date'] = Carbon::createFromDate($yearVal, $qRange[0], 1)->startOfMonth()->toDateString();
            }
            if (empty($filters['to_date'])) {
                $filters['to_date'] = Carbon::createFromDate($yearVal, $qRange[1], 1)->endOfMonth()->toDateString();
            }
        } elseif ($periodType === 'Half' || $periodType === 'Half-yearly' || $periodType === 'Half-year' || $periodType === 'Semester') {
            $yList = (array) ($filters['years'] ?? ($filters['year'] ?? []));
            $yList = array_values(array_filter($yList, 'is_numeric'));
            $yearVal = !empty($yList) ? (int) $yList[0] : (int) date('Y');

            $hVal = strtoupper(trim((string) ($filters['half'] ?? ($filters['half_year'] ?? 'H1'))));
            $halfMonths = [
                'H1' => [1, 6],
                'H2' => [7, 12],
            ];
            $hRange = $halfMonths[$hVal] ?? [1, 6];
            if (empty($filters['from_date'])) {
                $filters['from_date'] = Carbon::createFromDate($yearVal, $hRange[0], 1)->startOfMonth()->toDateString();
            }
            if (empty($filters['to_date'])) {
                $filters['to_date'] = Carbon::createFromDate($yearVal, $hRange[1], 1)->endOfMonth()->toDateString();
            }
        } elseif ($periodType === 'Custom') {
            $filters['year'] = null;
            $filters['years'] = [];
        }

        // Section 3: Two-stage filter unpack
        $scopeType = strtolower(trim((string) ($filters['filter_scope_type'] ?? '')));
        $scopeValues = (array) ($filters['filter_scope_values'] ?? []);
        $scopeValues = array_values(array_filter(array_map('trim', $scopeValues), fn($v) => $v !== '' && $v !== 'all'));

        if ($scopeType !== '' && !empty($scopeValues)) {
            switch ($scopeType) {
                case 'company':
                    $filters['company'] = $scopeValues;
                    $filters['companies'] = $scopeValues;
                    break;
                case 'division':
                    $filters['division'] = $scopeValues;
                    $filters['divisions'] = $scopeValues;
                    break;
                case 'department':
                    $filters['department'] = $scopeValues;
                    $filters['departments'] = $scopeValues;
                    break;
                case 'team':
                case 'team_section':
                    $filters['team'] = $scopeValues;
                    $filters['teams'] = $scopeValues;
                    break;
                case 'group':
                    $filters['group'] = $scopeValues;
                    break;
                case 'individual':
                case 'employee':
                case 'employee_id':
                case 'learner':
                    $filters['employee_id'] = $scopeValues;
                    $filters['employee'] = $scopeValues;
                    $filters['employees'] = $scopeValues;
                    $filters['individual'] = $scopeValues;
                    break;
            }
        }

        return $filters;
    }

    /**
     * Download the filtered export file (Excel, CSV, or PDF).
     */
    public function download(Request $request): Response
    {
        $inputData = $request->all();
        if (isset($inputData['year'])) {
            if (is_array($inputData['year'])) {
                $cleanedYears = array_values(array_filter(array_map('trim', $inputData['year']), fn($y) => $y !== '' && strtolower($y) !== 'all'));
                $inputData['year'] = !empty($cleanedYears) ? $cleanedYears : null;
            } elseif (strtolower(trim((string) $inputData['year'])) === 'all' || trim((string) $inputData['year']) === '') {
                $inputData['year'] = null;
            }
        }

        $validator = Validator::make($inputData, [
            'source'               => 'required|string|in:online,offline,all',
            'format'               => 'required|string|in:xlsx,csv,pdf',
            'year'                 => 'nullable',
            'period_type'          => 'nullable|string',
            'month'                => 'nullable',
            'week'                 => 'nullable',
            'from_date'            => 'nullable|date_format:Y-m-d,d/m/Y,Y/m/d',
            'to_date'              => 'nullable|date_format:Y-m-d,d/m/Y,Y/m/d',
            'filter_scope_type'    => 'nullable|string',
            'filter_scope_values'  => 'nullable|array',
            'filter_scope_values.*'=> 'nullable|string',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error: ' . implode(', ', $validator->errors()->all()),
                    'errors'  => $validator->errors(),
                ], 422);
            }

            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $source = strtolower(trim((string) $request->input('source')));
        $format = strtolower(trim((string) $request->input('format', 'xlsx')));
        $filters = $this->normalizeFilters($request->all());

        // 1. ONLINE (KP OWL) EXPORT
        if ($source === 'online') {
            return $this->downloadOnline($filters, $format, $request);
        }

        // 2. OFFLINE (NON KP OWL) EXPORT
        if ($source === 'offline') {
            return $this->downloadOffline($filters, $format, $request);
        }

        // 3. ALL (COMBINED) EXPORT
        return $this->downloadAll($filters, $format, $request);
    }

    /**
     * Handle KP OWL Online export.
     */
    protected function downloadOnline(array $filters, string $format, Request $request): Response
    {
        $dateSuffix = now()->format('Y-m-d');
        $service = app(OnlineFullRecordExportService::class);

        if ($format === 'csv') {
            $fileName = sprintf('kp-owl-online-records-%s.csv', $dateSuffix);
            $query = $service->buildQuery($filters);
            $columnHeadings = OnlineFullRecordExportService::headings();

            $headers = [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
                'Pragma'              => 'no-cache',
                'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
                'Expires'             => '0',
            ];

            return response()->streamDownload(function () use ($query, $columnHeadings, $service) {
                $output = fopen('php://output', 'w');
                // UTF-8 BOM
                fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($output, $columnHeadings);

                $streamPdo = $service->getStreamPdo();
                $stmt = $streamPdo->prepare($query->toSql());
                $stmt->execute($query->getBindings());

                $rowCount = 0;
                $batch = [];
                $batchSize = 2500;

                while ($row = $stmt->fetch(\PDO::FETCH_OBJ)) {
                    $batch[] = $row;
                    if (count($batch) >= $batchSize) {
                        $context = $service->buildBatchContext($batch);
                        foreach ($batch as $bRow) {
                            $rowCount++;
                            fputcsv($output, $service->mapRow($bRow, $rowCount, $context));
                        }
                        if (ob_get_level() > 0) {
                            ob_flush();
                        }
                        flush();
                        $batch = [];
                    }
                }

                if (!empty($batch)) {
                    $context = $service->buildBatchContext($batch);
                    foreach ($batch as $bRow) {
                        $rowCount++;
                        fputcsv($output, $service->mapRow($bRow, $rowCount, $context));
                    }
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                    $batch = [];
                }

                $stmt->closeCursor();
                fclose($output);
            }, $fileName, $headers);
        }

        if ($format === 'pdf') {
            $query = $service->buildQuery($filters);

            $totalRecords = (clone $query)->count();
            $maxPdfLimit = 500;

            if ($totalRecords > $maxPdfLimit && !app()->environment('testing')) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => "PDF export is limited to maximum {$maxPdfLimit} records to ensure readability and performance (Current: {$totalRecords}). Please apply more specific filters or choose Excel / CSV.",
                    ], 422);
                }

                return redirect()->back()->with('error', "PDF export is limited to maximum {$maxPdfLimit} records (Current: {$totalRecords}). Please filter further or download via Excel/CSV.");
            }

            $pdfRecordsLimit = app()->environment('testing') ? 25 : $maxPdfLimit;
            $records = $query->limit($pdfRecordsLimit)->get();

            $pdf = Pdf::loadView('main.record.online.export-pdf', [
                'records'       => $records,
                'totalRecords'  => $totalRecords,
                'limit'         => $maxPdfLimit,
                'filters'       => $filters,
                'generatedAt'   => now()->format('d M Y H:i:s'),
            ]);

            $fileName = sprintf('kp-owl-online-records-%s.pdf', $dateSuffix);
            return $pdf->setPaper('a4', 'landscape')->download($fileName);
        }

        // Default: XLSX
        $fileName = \App\Exports\OnlineRecordExport::generateFileName();
        $export = new \App\Exports\OnlineRecordExport($filters, $fileName);
        return $export->download($fileName);
    }

    /**
     * Handle Non KP OWL Offline export.
     */
    protected function downloadOffline(array $filters, string $format, Request $request): Response
    {
        $dateSuffix = now()->format('Y-m-d');

        if ($format === 'csv') {
            $export = new OfflineTrainingCsvExport(null, $filters);
            $fileName = sprintf('non-kp-owl-records-%s.csv', $dateSuffix);
            return $export->download($fileName, \Maatwebsite\Excel\Excel::CSV);
        }

        if ($format === 'pdf') {
            $query = RecordOfflineQueryService::buildGlobalQuery($filters)
                ->orderBy('ot.start_date', 'desc')
                ->orderBy('ot.id', 'desc')
                ->orderBy('otu.id', 'asc');

            $totalRecords = (clone $query)->count();
            $maxPdfLimit = 500;

            if ($totalRecords > $maxPdfLimit && !app()->environment('testing')) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => "PDF export is limited to maximum {$maxPdfLimit} records (Current: {$totalRecords}). Please apply more specific filters or choose Excel / CSV.",
                    ], 422);
                }

                return redirect()->back()->with('error', "PDF export is limited to maximum {$maxPdfLimit} records (Current: {$totalRecords}). Please filter further or download via Excel/CSV.");
            }

            $pdfRecordsLimit = app()->environment('testing') ? 25 : $maxPdfLimit;
            $records = $query->limit($pdfRecordsLimit)->get();

            $pdf = Pdf::loadView('main.record.offline.export-pdf', [
                'records'       => $records,
                'generatedAt'   => now()->format('d M Y H:i:s'),
                'generatedDate' => now()->format('d M Y H:i:s'),
                'totalRecords'  => $totalRecords,
            ]);

            $fileName = sprintf('non-kp-owl-records-%s.pdf', $dateSuffix);
            return $pdf->setPaper('a4', 'landscape')->download($fileName);
        }

        // Default: XLSX
        $fileName = \App\Exports\OfflineRecordExport::generateFileName();
        $export = new \App\Exports\OfflineRecordExport($filters, $fileName);
        return $export->download($fileName);
    }

    /**
     * Handle All KP OWL Combined export.
     */
    protected function downloadAll(array $filters, string $format, Request $request): Response
    {
        $dateSuffix = now()->format('Y-m-d');

        if ($format === 'csv') {
            $query = $this->combinedService->buildCombinedQuery($filters)
                ->orderBy('raw_finish_date', 'desc')
                ->orderBy('record_date', 'desc');

            $fileName = sprintf('all-kp-owl-combined-records-%s.csv', $dateSuffix);

            $headers = [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
                'Pragma'              => 'no-cache',
                'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
                'Expires'             => '0',
            ];

            $columnHeadings = (new AllRecordExport($filters))->headings();

            return response()->streamDownload(function () use ($query, $columnHeadings) {
                $output = fopen('php://output', 'w');
                // UTF-8 BOM
                fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($output, $columnHeadings);

                $pdo = DB::connection()->getPdo();
                $wasBuffered = $pdo->getAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY);
                $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

                try {
                    $stmt = $pdo->prepare($query->toSql());
                    $stmt->execute($query->getBindings());

                    $rowCount = 0;
                    while ($row = $stmt->fetch(\PDO::FETCH_OBJ)) {
                        $rowCount++;

                        $durationFormatted = '-';
                        if (!empty($row->duration_seconds)) {
                            $secs = (int) $row->duration_seconds;
                            $hours = floor($secs / 3600);
                            $mins = floor(($secs % 3600) / 60);
                            $seconds = $secs % 60;
                            $durationFormatted = sprintf('%02d:%02d:%02d', $hours, $mins, $seconds);
                        }

                        $recognizedFormatted = '-';
                        if ($row->source !== 'offline') {
                            $recSec = (int) ($row->recognized_duration_seconds ?? 0);
                            $rHours = floor($recSec / 3600);
                            $rMins = floor(($recSec % 3600) / 60);
                            $rSeconds = $recSec % 60;
                            $recognizedFormatted = sprintf('%02d:%02d:%02d', $rHours, $rMins, $rSeconds);
                        }

                        $engSecs = (int) ($row->afk_duration_seconds ?? 0);
                        if ($engSecs <= 0 && $row->source === 'offline') {
                            $engSecs = (int) round(((float) ($row->training_hours ?? ($row->learning_hours ?? 0))) * 3600);
                        }
                        $totalEngagementDuration = sprintf('%02d:%02d:%02d', floor($engSecs / 3600), floor(($engSecs % 3600) / 60), $engSecs % 60);
                        $totalEngagementLh = round($engSecs / 3600, 2);

                        $lhNumeric = ($row->learning_hours !== null && $row->learning_hours !== '' && is_numeric($row->learning_hours))
                            ? (float) $row->learning_hours
                            : null;

                        $progressNum = (float) ($row->progress ?? 0);
                        $progressDecimal = round($progressNum / 100, 4);
                        $progressStatus = ($progressNum >= 100 || ($row->status ?? '') === 'Finished') ? 'Finished' : (($progressNum > 0) ? 'Ongoing' : 'Not Started');

                        $sourceRaw = strtolower(trim((string) ($row->source ?? '')));
                        $isKpOwl = in_array($sourceRaw, ['online', 'kp owl', 'kp-owl', 'owl'], true);
                        $delivMethod = $isKpOwl ? 'Online' : 'Offline';

                        if ($isKpOwl) {
                            $subDelivMethod = $row->sub_delivery_method ?: (($row->source_type ?? '') === 'playlist' ? 'Playlist / Path' : 'Self-Paced / LMS');
                        } else {
                            $sub = $row->sub_delivery_method ?? $row->training_type ?? $row->type ?? null;
                            if (!$sub || $sub === '-' || strtolower(trim((string) $sub)) === 'offline') {
                                $rawDm = trim((string) ($row->delivery_method ?? ''));
                                if ($rawDm !== '' && strtolower($rawDm) !== 'offline') {
                                    $sub = $rawDm;
                                }
                            }
                            $subDelivMethod = $sub ?: '-';
                        }

                        $startDateStr = !empty($row->start_date) ? date('d/m/Y', strtotime($row->start_date)) : '';
                        $endDateStr = !empty($row->end_date)
                            ? date('d/m/Y', strtotime($row->end_date))
                            : (!empty($row->finish_date) ? date('d/m/Y', strtotime($row->finish_date)) : '');

                        $startTimeStr = !empty($row->start_time) ? date('H:i:s', strtotime($row->start_time)) : '';
                        $endTimeStr = !empty($row->end_time) ? date('H:i:s', strtotime($row->end_time)) : '';

                        $recordMonthDate = $row->start_date ?: ($row->finish_date ?: ($row->record_date ?? null));
                        $recordMonthStr = !empty($recordMonthDate) ? date('F', strtotime($recordMonthDate)) : '-';

                        $attendanceStr = 'No';
                        if ($row->attendance !== null && $row->attendance !== '') {
                            $rawAtt = strtolower((string) $row->attendance);
                            $attendanceStr = ((bool) $row->attendance || in_array($rawAtt, ['1', 'yes', 'present', 'true', 'hadir', 'completed', 'finished'], true)) ? 'Yes' : 'No';
                        } elseif ($row->source === 'online') {
                            $attendanceStr = ($progressNum >= 100) ? 'Yes' : 'No';
                        }

                        $idr = $row->bond_amount_idr ?? $row->bond_amount ?? null;
                        $hasBond = ($idr !== null && (float) $idr > 0);
                        $bondEndDateStr = $hasBond && !empty($row->bond_end_date) ? date('d/m/Y', strtotime($row->bond_end_date)) : '';

                        $scoreL1 = is_numeric($row->l1_program) ? (float) $row->l1_program : null;
                        $scoreL2 = is_numeric($row->assessment_score)
                            ? (int) round((float) $row->assessment_score)
                            : (is_numeric($row->l2_numerical) ? (int) round((float) $row->l2_numerical) : null);
                        $scoreL3 = is_numeric($row->l3_numerical) ? (float) $row->l3_numerical : null;

                        $rawEmp = $row->employee_id ?: '-';
                        $cleanEmpId = ($rawEmp !== '-') ? preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', trim((string) $rawEmp)) : '-';

                        $hasCert = (!empty($row->certificate) && $row->certificate !== '-' && $row->certificate !== 'No' && $row->certificate !== '0') || !empty($row->certificate_issued_date);
                        $certAvailability = $hasCert ? 'Available' : '';
                        $certInstitution = $hasCert ? ($row->certificate ?: 'PT Krakatau Posco') : '';
                        $certIssuedDate = !empty($row->certificate_issued_date) ? date('d/m/Y', strtotime($row->certificate_issued_date)) : '';
                        $certExpiredDate = !empty($row->certificate_expired_date) ? date('d/m/Y', strtotime($row->certificate_expired_date)) : '';

                        $finalRecordMonth = ($row->source === 'online')
                            ? ($endDateStr !== '' ? date('F', strtotime(str_replace('/', '-', $endDateStr))) : $recordMonthStr)
                            : $recordMonthStr;

                        $csvRow = [
                            $rowCount,
                            $cleanEmpId,
                            $row->employee_name ?: '-',
                            $row->grade ?: '-',
                            $row->job_position ?: '-',
                            $row->team ?: '-',
                            $row->department ?: '-',
                            $row->company ?: '-',
                            $row->learning_title ?: '-',
                            $row->sub_course_name ?: '-',
                            $row->type ?: '-',
                            $row->category ?: '-',
                            $row->level ?: '-',
                            $row->batch ?: '-',
                            $row->classification ?: 'Unclassified',
                            $row->sub_classification ?: '-',
                            $delivMethod,
                            $subDelivMethod,
                            $row->source === 'online' ? 'Internal' : ($row->provider ?: '-'),
                            $row->source === 'online' ? '-' : ($row->sub_provider ?: '-'),
                            $row->provider_name ?: ($row->source === 'offline' ? '-' : 'KP-OWL'),
                            $row->venue ?: ($row->source === 'offline' ? '-' : 'Online LMS'),
                            $row->instructor ?: '-',
                            $row->source === 'offline' ? '-' : $durationFormatted,
                            $row->source === 'offline' ? '-' : $recognizedFormatted,
                            $lhNumeric !== null ? number_format($lhNumeric, 2, '.', '') : '-',
                            number_format($progressDecimal * 100, 1) . '%',
                            $progressStatus,
                            $startDateStr,
                            $endDateStr,
                            $startTimeStr,
                            $endTimeStr,
                            $finalRecordMonth,
                            $row->requirement ?: 'Mandatory',
                            $attendanceStr,
                            $row->attendance_reason ?: '',
                            $row->bond_status ?: 'No',
                            !empty($row->bond_duration) ? $row->bond_duration . ' Months' : '',
                            $bondEndDateStr,
                            $hasBond ? number_format((float) $idr, 2, '.', '') : '',
                            $scoreL1 !== null ? number_format($scoreL1, 2, '.', '') : '',
                            $scoreL2 !== null ? $scoreL2 : '',
                            $row->l2_verbal ?: ($scoreL2 !== null ? ($scoreL2 >= 70 ? 'Passed' : 'Failed') : ''),
                            $scoreL3 !== null ? number_format($scoreL3, 2, '.', '') : '',
                            $row->l3_verbal ?: '',
                            $certAvailability,
                            $certInstitution,
                            $certIssuedDate,
                            $certExpiredDate,
                            $row->pic ?: '',
                            $totalEngagementDuration,
                            number_format($totalEngagementLh, 2, '.', ''),
                        ];

                        fputcsv($output, $csvRow);
                    }
                } finally {
                    $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $wasBuffered);
                    fclose($output);
                }
            }, $fileName, $headers);
        }

        if ($format === 'pdf') {
            $query = $this->combinedService->buildCombinedQuery($filters);
            $totalRecords = (clone $query)->count();
            $maxPdfLimit = 200;

            if ($totalRecords > $maxPdfLimit && !app()->environment('testing')) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => "PDF export is limited to maximum {$maxPdfLimit} records to ensure readability on landscape format and prevent memory limits (Current filtered records: {$totalRecords}). Please apply additional filters or download via Excel / CSV.",
                        'total'   => $totalRecords,
                        'limit'   => $maxPdfLimit,
                    ], 422);
                }

                return redirect()->back()->with('error', "PDF export is limited to maximum {$maxPdfLimit} records (Current: {$totalRecords}). Please apply filters or download via Excel/CSV.");
            }

            $rows = $query->orderBy('raw_finish_date', 'desc')->orderBy('record_date', 'desc')->get();
            $headers = (new AllRecordExport($filters))->headings();

            $activeFilterBadges = [];
            if (!empty($filters['employee_id'])) {
                $activeFilterBadges[] = "Employee/NIP: \"{$filters['employee_id']}\"";
            }
            if (!empty($filters['classification']) && $filters['classification'] !== 'all') {
                $activeFilterBadges[] = "Classification: {$filters['classification']}";
            }
            if (!empty($filters['year']) && $filters['year'] !== 'all') {
                $activeFilterBadges[] = "Year: {$filters['year']}";
            }
            if (!empty($filters['department']) && $filters['department'] !== 'all') {
                $activeFilterBadges[] = "Department: {$filters['department']}";
            }
            if (!empty($filters['company']) && $filters['company'] !== 'all') {
                $activeFilterBadges[] = "Company: {$filters['company']}";
            }
            if (!empty($filters['status']) && $filters['status'] !== 'all') {
                $activeFilterBadges[] = "Status: " . ucfirst($filters['status']);
            }

            $pdf = Pdf::loadView('main.record.all.export-pdf', [
                'classificationName' => !empty($filters['classification']) && $filters['classification'] !== 'all' ? $filters['classification'] : 'All Combined',
                'records'            => $rows,
                'totalRecords'       => $totalRecords,
                'headers'            => $headers,
                'filters'            => $filters,
                'activeFilterBadges' => $activeFilterBadges,
                'generatedAt'        => now()->format('d M Y H:i:s'),
            ]);

            $fileName = sprintf('all-kp-owl-combined-records-%s.pdf', $dateSuffix);
            return $pdf->setPaper('a2', 'landscape')->download($fileName);
        }

        // Default: XLSX
        $fileName = RecordAllCombinedService::generateManageFileName();
        return (new AllRecordExport($filters, $fileName))->download();
    }
}
