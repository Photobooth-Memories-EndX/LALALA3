<?php

namespace App\Services\Record;

use App\Models\Classification;
use App\Models\Content;
use App\Models\OfflineTraining;
use App\Models\OfflineTrainingOption;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TrainingOptionManagementService
{
    public const TYPE_CLASSIFICATION = 'classification';

    /**
     * Complete metadata definition for training master option types.
     */
    public const TYPES_CONFIG = [
        self::TYPE_CLASSIFICATION => [
            'type'            => 'classification',
            'label'           => 'Training Classification',
            'plural_label'    => 'Training Classifications',
            'description'     => 'Main learning classifications, Sub Classifications, and Course Examples across OWL & Offline Training.',
            'icon'            => 'ti-category-2',
            'source'          => 'classification_table',
            'is_hierarchical' => false,
            'parent_type'     => null,
            'is_year_scoped'  => false,
            'table_column'    => 'main_classification',
        ],
        OfflineOptionService::TYPE_TYPE => [
            'type'            => 'type',
            'label'           => 'Training Type',
            'plural_label'    => 'Training Types',
            'description'     => 'Training content types for OWL (Document, Video, MOOC, Playlist) and Offline training execution.',
            'icon'            => 'ti-bookmarks',
            'source'          => 'offline_training_options',
            'is_hierarchical' => false,
            'parent_type'     => null,
            'is_year_scoped'  => false,
            'table_column'    => 'type',
        ],
        OfflineOptionService::TYPE_CATEGORY => [
            'type'            => 'category',
            'label'           => 'Training Category',
            'plural_label'    => 'Training Categories',
            'description'     => 'Training categories for OWL (Playlist, All Content, Content Playlist) and Non KP-OWL.',
            'icon'            => 'ti-folder',
            'source'          => 'offline_training_options',
            'is_hierarchical' => false,
            'parent_type'     => null,
            'is_year_scoped'  => false,
            'table_column'    => 'category',
        ],
        OfflineOptionService::TYPE_DELIVERY_METHOD => [
            'type'            => 'delivery_method',
            'label'           => 'Delivery Method',
            'plural_label'    => 'Delivery Methods',
            'description'     => 'Primary training delivery modes (Offline, Online, Blended) and specific Sub Delivery Methods.',
            'icon'            => 'ti-presentation',
            'source'          => 'offline_training_options',
            'is_hierarchical' => false,
            'parent_type'     => null,
            'is_year_scoped'  => false,
            'table_column'    => 'delivery_method',
        ],
        OfflineOptionService::TYPE_PROVIDER => [
            'type'            => 'provider',
            'label'           => 'Training Provider',
            'plural_label'    => 'Training Providers',
            'description'     => 'Training organizers and providers (Internal, External) and specific Sub Providers.',
            'icon'            => 'ti-building',
            'source'          => 'offline_training_options',
            'is_hierarchical' => false,
            'parent_type'     => null,
            'is_year_scoped'  => false,
            'table_column'    => 'provider',
        ],
        OfflineOptionService::TYPE_SUB_CLASSIFICATION => [
            'type'            => 'sub_classification',
            'label'           => 'Sub Classification',
            'plural_label'    => 'Sub Classifications',
            'description'     => 'Specific discipline and domain sub-classifications under Main Classification.',
            'icon'            => 'ti-subtask',
            'source'          => 'offline_training_options',
            'is_hierarchical' => true,
            'parent_type'     => 'classification',
            'is_year_scoped'  => false,
            'table_column'    => 'sub_classification',
        ],
        OfflineOptionService::TYPE_COURSE_NAME => [
            'type'            => 'course_name',
            'label'           => 'Course Example',
            'plural_label'    => 'Course Examples',
            'description'     => 'Standardized course examples linked under Sub Classifications.',
            'icon'            => 'ti-certificate',
            'source'          => 'offline_training_options',
            'is_hierarchical' => true,
            'parent_type'     => 'sub_classification',
            'is_year_scoped'  => false,
            'table_column'    => 'course_name',
        ],
        OfflineOptionService::TYPE_SUB_DELIVERY_METHOD => [
            'type'            => 'sub_delivery_method',
            'label'           => 'Sub Delivery Method',
            'plural_label'    => 'Sub Delivery Methods',
            'description'     => 'Detailed delivery sub-methods (On-site, Off-site, E-learning, Webinar, Hybrid).',
            'icon'            => 'ti-chalkboard',
            'source'          => 'offline_training_options',
            'is_hierarchical' => true,
            'parent_type'     => 'delivery_method',
            'is_year_scoped'  => false,
            'table_column'    => 'sub_delivery_method',
        ],
        OfflineOptionService::TYPE_SUB_PROVIDER => [
            'type'            => 'sub_provider',
            'label'           => 'Sub Provider',
            'plural_label'    => 'Sub Providers',
            'description'     => 'Specific provider categories (Internal-Training Team, Internal-Unit, Institution, Individual).',
            'icon'            => 'ti-building-community',
            'source'          => 'offline_training_options',
            'is_hierarchical' => true,
            'parent_type'     => 'provider',
            'is_year_scoped'  => false,
            'table_column'    => 'sub_provider',
        ],
        OfflineOptionService::TYPE_LEVEL => [
            'type'            => 'level',
            'label'           => 'Level',
            'plural_label'    => 'Levels',
            'description'     => 'Difficulty and competency levels (Basic, Intermediate, Advanced, All, etc.).',
            'icon'            => 'ti-stairs',
            'source'          => 'offline_training_options',
            'is_hierarchical' => false,
            'parent_type'     => null,
            'is_year_scoped'  => false,
            'table_column'    => 'level',
        ],
        OfflineOptionService::TYPE_SUB_CORPORATE_KPI => [
            'type'            => 'sub_corporate_kpi',
            'label'           => 'Corporate KPI',
            'plural_label'    => 'Corporate KPIs',
            'description'     => 'Yearly corporate strategic KPI indicators and targets.',
            'icon'            => 'ti-target',
            'source'          => 'offline_training_options',
            'is_hierarchical' => false,
            'parent_type'     => null,
            'is_year_scoped'  => true,
            'table_column'    => 'sub_corporate_kpi',
        ],
        OfflineOptionService::TYPE_PROVIDER_NAME => [
            'type'            => 'provider_name',
            'label'           => 'Provider Name',
            'plural_label'    => 'Provider Names',
            'description'     => 'Names of specific vendor institutions, companies, or partners.',
            'icon'            => 'ti-briefcase',
            'source'          => 'offline_training_options',
            'is_hierarchical' => false,
            'parent_type'     => null,
            'is_year_scoped'  => false,
            'table_column'    => 'provider_name',
        ],
        OfflineOptionService::TYPE_VENUE => [
            'type'            => 'venue',
            'label'           => 'Venue',
            'plural_label'    => 'Venues',
            'description'     => 'Training rooms, auditoriums, training centers, and locations.',
            'icon'            => 'ti-map-pin',
            'source'          => 'offline_training_options',
            'is_hierarchical' => false,
            'parent_type'     => null,
            'is_year_scoped'  => false,
            'table_column'    => 'venue',
        ],
        OfflineOptionService::TYPE_INSTRUCTOR => [
            'type'            => 'instructor',
            'label'           => 'Instructor',
            'plural_label'    => 'Instructors',
            'description'     => 'Trainers, facilitators, speakers, and instructors.',
            'icon'            => 'ti-user-check',
            'source'          => 'offline_training_options',
            'is_hierarchical' => false,
            'parent_type'     => null,
            'is_year_scoped'  => false,
            'table_column'    => 'instructor',
        ],
    ];

    /**
     * The 5 primary tabs displayed as main navigation.
     */
    public const PRIMARY_TYPES = [
        self::TYPE_CLASSIFICATION,
        OfflineOptionService::TYPE_TYPE,
        OfflineOptionService::TYPE_CATEGORY,
        OfflineOptionService::TYPE_DELIVERY_METHOD,
        OfflineOptionService::TYPE_PROVIDER,
    ];

    public function getAllTypes(): array
    {
        return self::TYPES_CONFIG;
    }

    public function getPrimaryTypes(): array
    {
        return self::PRIMARY_TYPES;
    }

    public function getSecondaryTypes(): array
    {
        $secondary = [];
        foreach (self::TYPES_CONFIG as $key => $config) {
            if (!in_array($key, self::PRIMARY_TYPES, true)) {
                $secondary[$key] = $config;
            }
        }
        return $secondary;
    }

    public function getTypeConfig(string $type): array
    {
        return self::TYPES_CONFIG[$type] ?? self::TYPES_CONFIG[self::TYPE_CLASSIFICATION];
    }

    public function isValidType(string $type): bool
    {
        return array_key_exists($type, self::TYPES_CONFIG);
    }

    public static function normalizeString(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', $value));
    }

    /**
     * Query data for the current active tab with search, status, source, parent, and hierarchy.
     */
    public function getOptionData(string $type, Request $request): array
    {
        $config = $this->getTypeConfig($type);
        $search = trim((string) ($request->get('q') ?? $request->get('search') ?? ''));
        $status = $request->get('status'); // '1', '0', or null (all)
        $source = $request->get('source'); // 'owl', 'non_kp_owl', or null (all)
        $parent = $request->get('parent_id');
        $mainId = $request->get('main_id');
        $subId = $request->get('sub_id');
        $year = $request->get('year');
        $perPage = (int) $request->get('entries', 25);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 25;
        }

        // =========================================================================
        // 1. TRAINING CLASSIFICATION (Single Main DataTable: Sub Classification Rows)
        // =========================================================================
        if ($type === self::TYPE_CLASSIFICATION) {
            $query = OfflineTrainingOption::with(['classification', 'courseExamples'])
                ->where('option_type', OfflineOptionService::TYPE_SUB_CLASSIFICATION);

            if (!empty($mainId)) {
                $query->where('classification_id', (int) $mainId);
            }

            if (!empty($subId)) {
                $query->where('id', (int) $subId);
            }

            if ($status !== null && $status !== '' && in_array($status, ['0', '1'], true)) {
                $query->where('is_active', $status === '1');
            }

            if ($source !== null && $source !== '' && $source !== 'all') {
                $query->where(function ($q) use ($source) {
                    $q->where('source', $source)
                      ->orWhere('source', 'all')
                      ->orWhereNull('source');
                });
            }

            if ($search !== '') {
                $searchLower = mb_strtolower($search);
                $query->where(function ($q) use ($search, $searchLower) {
                    $q->where('value', 'LIKE', "%{$search}%")
                      ->orWhere('normalized_value', 'LIKE', "%{$searchLower}%")
                      ->orWhere('explanation', 'LIKE', "%{$search}%")
                      ->orWhereHas('classification', function ($mq) use ($search) {
                          $mq->where('classification_name', 'LIKE', "%{$search}%");
                      })
                      ->orWhereHas('courseExamples', function ($cq) use ($search, $searchLower) {
                          $cq->where('value', 'LIKE', "%{$search}%")
                             ->orWhere('normalized_value', 'LIKE', "%{$searchLower}%");
                      });
                });
            }

            // Order canonically by Main Classification, then sort_order, then value
            $canonicalOrder = [
                'Basic & Common Duties',
                'Compliance',
                'Innovation',
                'Job Functional',
                'Language',
                'Leadership',
                'Assessment/Test',
            ];
            $caseParts = [];
            foreach ($canonicalOrder as $idx => $cName) {
                $cNameEscaped = addslashes($cName);
                $caseParts[] = "WHEN classification.classification_name = '{$cNameEscaped}' THEN {$idx}";
            }
            $caseSql = "CASE " . implode(' ', $caseParts) . " ELSE 99 END";

            $subs = $query->leftJoin('classification', 'offline_training_options.classification_id', '=', 'classification.id')
                ->select('offline_training_options.*')
                ->orderByRaw($caseSql)
                ->orderBy('classification.classification_name', 'asc')
                ->orderBy('offline_training_options.sort_order', 'asc')
                ->orderBy('offline_training_options.value', 'asc')
                ->get();

            $subs = $subs->map(function ($sub) {
                $offlineUsage = 0;
                try {
                    $offlineUsage = OfflineTraining::where('sub_classification', $sub->value)->count();
                } catch (\Throwable $e) {
                    $offlineUsage = 0;
                }
                $sub->offline_count = $offlineUsage;
                $sub->total_usage = $offlineUsage + ($sub->courseExamples ? $sub->courseExamples->count() : 0);
                return $sub;
            });

            $totalClassifications = Classification::count();
            $totalSubClassifications = OfflineTrainingOption::where('option_type', OfflineOptionService::TYPE_SUB_CLASSIFICATION)->count();
            $totalCourseExamples = OfflineTrainingOption::where('option_type', OfflineOptionService::TYPE_COURSE_NAME)->count();

            return [
                'type'                    => $type,
                'config'                  => $config,
                'items'                   => $subs,
                'total_count'             => $subs->count(),
                'active_count'            => $subs->where('is_active', true)->count(),
                'total_sub_count'         => $totalSubClassifications,
                'total_course_count'      => $totalCourseExamples,
                'total_classifications'   => $totalClassifications,
                'per_page'                => $perPage,
            ];
        }

        // =========================================================================
        // 2. DELIVERY METHOD (with Sub Delivery Methods)
        // =========================================================================
        if ($type === OfflineOptionService::TYPE_DELIVERY_METHOD) {
            $query = OfflineTrainingOption::where('option_type', $type);

            if ($search !== '') {
                $searchLower = mb_strtolower($search);
                $query->where(function ($q) use ($search, $searchLower) {
                    $q->where('value', 'LIKE', "%{$search}%")
                      ->orWhere('normalized_value', 'LIKE', "%{$searchLower}%")
                      ->orWhere('explanation', 'LIKE', "%{$search}%");
                });
            }

            if ($status !== null && $status !== '' && in_array($status, ['0', '1'], true)) {
                $query->where('is_active', $status === '1');
            }

            if ($source !== null && $source !== '' && $source !== 'all') {
                $query->where(function ($q) use ($source) {
                    $q->where('source', $source)
                      ->orWhere('source', 'all')
                      ->orWhereNull('source');
                });
            }

            $items = $query->orderBy('sort_order', 'asc')->orderBy('value', 'asc')->get();

            // Load sub delivery methods
            $items = $items->map(function ($item) {
                $children = OfflineTrainingOption::where('option_type', OfflineOptionService::TYPE_SUB_DELIVERY_METHOD)
                    ->where('parent_id', $item->id)
                    ->orderBy('sort_order', 'asc')
                    ->orderBy('value', 'asc')
                    ->get();

                $usage = 0;
                try {
                    $usage = OfflineTraining::where('delivery_method', $item->value)->count();
                } catch (\Throwable $e) {
                    $usage = 0;
                }

                $item->children = $children;
                $item->offline_count = $usage;
                return $item;
            });

            return [
                'type'         => $type,
                'config'       => $config,
                'items'        => $items,
                'total_count'  => OfflineTrainingOption::where('option_type', $type)->count(),
                'active_count' => OfflineTrainingOption::where('option_type', $type)->where('is_active', true)->count(),
                'per_page'     => $perPage,
            ];
        }

        // =========================================================================
        // 3. TRAINING PROVIDER (with Sub Providers)
        // =========================================================================
        if ($type === OfflineOptionService::TYPE_PROVIDER) {
            $query = OfflineTrainingOption::where('option_type', $type);

            if ($search !== '') {
                $searchLower = mb_strtolower($search);
                $query->where(function ($q) use ($search, $searchLower) {
                    $q->where('value', 'LIKE', "%{$search}%")
                      ->orWhere('normalized_value', 'LIKE', "%{$searchLower}%")
                      ->orWhere('explanation', 'LIKE', "%{$search}%");
                });
            }

            if ($status !== null && $status !== '' && in_array($status, ['0', '1'], true)) {
                $query->where('is_active', $status === '1');
            }

            if ($source !== null && $source !== '' && $source !== 'all') {
                $query->where(function ($q) use ($source) {
                    $q->where('source', $source)
                      ->orWhere('source', 'all')
                      ->orWhereNull('source');
                });
            }

            $items = $query->orderBy('sort_order', 'asc')->orderBy('value', 'asc')->get();

            // Load sub providers
            $items = $items->map(function ($item) {
                $children = OfflineTrainingOption::where('option_type', OfflineOptionService::TYPE_SUB_PROVIDER)
                    ->where('parent_id', $item->id)
                    ->orderBy('sort_order', 'asc')
                    ->orderBy('value', 'asc')
                    ->get();

                $usage = 0;
                try {
                    $usage = OfflineTraining::where('provider', $item->value)->count();
                } catch (\Throwable $e) {
                    $usage = 0;
                }

                $item->children = $children;
                $item->offline_count = $usage;
                return $item;
            });

            return [
                'type'         => $type,
                'config'       => $config,
                'items'        => $items,
                'total_count'  => OfflineTrainingOption::where('option_type', $type)->count(),
                'active_count' => OfflineTrainingOption::where('option_type', $type)->where('is_active', true)->count(),
                'per_page'     => $perPage,
            ];
        }

        // =========================================================================
        // 4. OTHER STANDARD OPTIONS (Type, Category, Level, etc.)
        // =========================================================================
        $query = OfflineTrainingOption::with('parent')->where('option_type', $type);

        // Filter by Option Value (specifically for Training Type & standard options)
        $optionValue = trim((string) ($request->get('option_value') ?? $request->get('value') ?? ''));
        if ($optionValue !== '' && strtolower($optionValue) !== 'all') {
            $query->where('value', $optionValue);
        }

        if ($search !== '') {
            $normalizedSearch = OfflineTrainingOption::normalizeString($search);
            $searchLower = mb_strtolower($search);
            $query->where(function ($q) use ($search, $normalizedSearch, $searchLower) {
                $q->where('value', 'LIKE', "%{$search}%")
                  ->orWhere('normalized_value', 'LIKE', "%{$normalizedSearch}%")
                  ->orWhere('explanation', 'LIKE', "%{$search}%");

                if (stripos('owl', $searchLower) !== false) {
                    $q->orWhere('source', 'owl');
                }
                if (stripos('shared', $searchLower) !== false) {
                    $q->orWhere(function ($sq) {
                        $sq->where('source', 'all')->orWhereNull('source');
                    });
                }
                if (stripos('non kp-owl', $searchLower) !== false || stripos('non kp', $searchLower) !== false) {
                    $q->orWhere('source', 'non_kp_owl');
                }
            });
        }

        if ($status !== null && $status !== '' && in_array(strtolower((string) $status), ['0', '1', 'active', 'inactive'], true)) {
            $isActive = ($status === '1' || strtolower((string) $status) === 'active');
            $query->where('is_active', $isActive);
        }

        if ($source !== null && $source !== '') {
            $srcLower = strtolower(trim((string) $source));
            if ($srcLower === 'shared') {
                $query->where(function ($q) {
                    $q->where('source', 'all')
                      ->orWhere('source', 'shared')
                      ->orWhereNull('source');
                });
            } elseif ($srcLower === 'owl') {
                $query->where('source', 'owl');
            } elseif (in_array($srcLower, ['non_kp_owl', 'non-kp-owl'], true)) {
                $query->where('source', 'non_kp_owl');
            }
            // If $srcLower is 'all' or empty, do not apply source filter (All Sources)
        }

        if ($config['is_hierarchical'] && !empty($parent)) {
            $query->where('parent_id', (int) $parent);
        }

        if ($config['is_year_scoped'] && !empty($year)) {
            $query->where('scope_key', OfflineTrainingOption::buildScopeKey((int) $year));
        }

        $totalCount = OfflineTrainingOption::where('option_type', $type)->count();
        $activeCount = OfflineTrainingOption::where('option_type', $type)->where('is_active', true)->count();

        // Sorting support: value, source, status, order
        $sortBy = strtolower(trim((string) ($request->get('sort_by') ?? $request->get('sort') ?? 'sort_order')));
        $sortDir = strtolower(trim((string) ($request->get('sort_dir') ?? $request->get('direction') ?? 'asc'))) === 'desc' ? 'desc' : 'asc';

        if (in_array($sortBy, ['value', 'option_value'], true)) {
            $query->orderBy('value', $sortDir);
        } elseif ($sortBy === 'source') {
            $query->orderBy('source', $sortDir)->orderBy('value', 'asc');
        } elseif (in_array($sortBy, ['status', 'is_active'], true)) {
            $query->orderBy('is_active', $sortDir)->orderBy('value', 'asc');
        } elseif (in_array($sortBy, ['order', 'sort_order'], true)) {
            $query->orderBy('sort_order', $sortDir)->orderBy('value', 'asc');
        } else {
            $query->orderBy('sort_order', 'asc')->orderBy('value', 'asc');
        }

        $items = $query->get();

        $column = $config['table_column'] ?? null;
        $items = $items->map(function ($item) use ($column) {
            $usage = 0;
            if ($column) {
                try {
                    $usage = OfflineTraining::where($column, $item->value)->count();
                } catch (\Throwable $e) {
                    $usage = 0;
                }
            }
            $item->offline_count = $usage;
            return $item;
        });

        $trainingTypeOptions = OfflineTrainingOption::where('option_type', OfflineOptionService::TYPE_TYPE)
            ->orderBy('sort_order', 'asc')
            ->orderBy('value', 'asc')
            ->get();

        return [
            'type'                => $type,
            'config'              => $config,
            'items'               => $items,
            'total_count'         => $totalCount,
            'active_count'        => $activeCount,
            'per_page'            => $perPage,
            'trainingTypeOptions' => $trainingTypeOptions,
        ];
    }

    /**
     * Store new option (Classification or OfflineTrainingOption).
     */
    public function storeOption(array $data, ?int $userId = null): array
    {
        $type = (string) ($data['option_type'] ?? self::TYPE_CLASSIFICATION);
        if (!$this->isValidType($type)) {
            throw ValidationException::withMessages([
                'option_type' => ["Invalid option type: '{$type}'."],
            ]);
        }

        $config = $this->getTypeConfig($type);

        // 1. Main Classification
        if ($type === self::TYPE_CLASSIFICATION) {
            $name = self::normalizeString((string) ($data['classification_name'] ?? ''));
            if ($name === '') {
                throw ValidationException::withMessages([
                    'classification_name' => ['Classification name is required.'],
                ]);
            }

            $existing = Classification::whereRaw('LOWER(classification_name) = ?', [mb_strtolower($name)])->first();
            if ($existing) {
                throw ValidationException::withMessages([
                    'classification_name' => ["Classification '{$name}' already exists."],
                ]);
            }

            return DB::transaction(function () use ($name, $data) {
                $classification = Classification::create([
                    'classification_name' => $name,
                    'is_active'           => isset($data['is_active']) ? (bool) $data['is_active'] : true,
                ]);

                return [
                    'success' => true,
                    'message' => "Classification '{$classification->classification_name}' created successfully.",
                    'data'    => $classification,
                ];
            });
        }

        // 2. OfflineTrainingOption
        $rawValue = (string) ($data['value'] ?? '');
        if (!OfflineOptionService::isValidValue($rawValue)) {
            throw ValidationException::withMessages([
                'value' => ['The option value is required and cannot be a placeholder (e.g. -, N/A).'],
            ]);
        }

        $cleanValue = self::normalizeString($rawValue);
        $normalized = OfflineTrainingOption::normalizeString($cleanValue);
        $source = !empty($data['source']) ? trim((string) $data['source']) : 'all';
        $explanation = !empty($data['explanation']) ? trim((string) $data['explanation']) : null;
        $sortOrder = isset($data['sort_order']) ? (int) $data['sort_order'] : 0;
        $isActive = isset($data['is_active']) ? (bool) $data['is_active'] : true;
        $year = !empty($data['year']) ? (int) $data['year'] : null;

        // Sub Classification: parent is Main Classification (classification_id)
        if ($type === OfflineOptionService::TYPE_SUB_CLASSIFICATION) {
            $classificationId = !empty($data['classification_id']) ? (int) $data['classification_id'] : (!empty($data['parent_id']) ? (int) $data['parent_id'] : null);
            if (!$classificationId) {
                throw ValidationException::withMessages([
                    'classification_id' => ['Main Classification parent is required for Sub Classification.'],
                ]);
            }

            $duplicate = OfflineTrainingOption::where('option_type', $type)
                ->where('classification_id', $classificationId)
                ->where('normalized_value', $normalized)
                ->first();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'value' => ["Sub Classification '{$cleanValue}' already exists under this Main Classification."],
                ]);
            }

            return DB::transaction(function () use ($type, $cleanValue, $normalized, $classificationId, $source, $explanation, $sortOrder, $isActive, $userId) {
                $created = OfflineTrainingOption::create([
                    'option_type'       => $type,
                    'value'             => $cleanValue,
                    'explanation'       => $explanation,
                    'normalized_value'  => $normalized,
                    'year'              => null,
                    'scope_key'         => 'global',
                    'source'            => $source,
                    'parent_id'         => null,
                    'classification_id' => $classificationId,
                    'is_active'         => $isActive,
                    'sort_order'        => $sortOrder,
                    'created_from'      => 'manual',
                    'created_by'        => $userId ?? Auth::id(),
                ]);

                return [
                    'success' => true,
                    'message' => "Sub Classification '{$created->value}' created successfully.",
                    'data'    => $created,
                ];
            });
        }

        // Course Example: parent is Sub Classification (parent_id)
        if ($type === OfflineOptionService::TYPE_COURSE_NAME) {
            $parentId = !empty($data['parent_id']) ? (int) $data['parent_id'] : null;
            if (!$parentId) {
                throw ValidationException::withMessages([
                    'parent_id' => ['Sub Classification parent is required for Course Example.'],
                ]);
            }

            $parentSub = OfflineTrainingOption::find($parentId);
            $classificationId = $parentSub?->classification_id ?? (!empty($data['classification_id']) ? (int) $data['classification_id'] : null);

            $duplicateUnderSameParent = OfflineTrainingOption::where('option_type', $type)
                ->where('parent_id', $parentId)
                ->where('normalized_value', $normalized)
                ->first();

            if ($duplicateUnderSameParent) {
                throw ValidationException::withMessages([
                    'value' => ["Course Example '{$cleanValue}' already exists under this Sub Classification."],
                ]);
            }

            // Check if exists globally under another parent; if so, scope to this parent
            $existsGlobally = OfflineTrainingOption::where('option_type', $type)
                ->where('scope_key', 'global')
                ->where('normalized_value', $normalized)
                ->exists();

            $scopeKey = $existsGlobally ? 'parent:' . $parentId : 'global';

            return DB::transaction(function () use ($type, $cleanValue, $normalized, $scopeKey, $parentId, $classificationId, $source, $explanation, $sortOrder, $isActive, $userId) {
                $created = OfflineTrainingOption::create([
                    'option_type'       => $type,
                    'value'             => $cleanValue,
                    'explanation'       => $explanation,
                    'normalized_value'  => $normalized,
                    'year'              => null,
                    'scope_key'         => $scopeKey,
                    'source'            => $source,
                    'parent_id'         => $parentId,
                    'classification_id' => $classificationId,
                    'is_active'         => $isActive,
                    'sort_order'        => $sortOrder,
                    'created_from'      => 'manual',
                    'created_by'        => $userId ?? Auth::id(),
                ]);

                return [
                    'success' => true,
                    'message' => "Course Example '{$created->value}' created successfully.",
                    'data'    => $created,
                ];
            });
        }

        // Other options (Type, Category, Delivery Method, Provider, Level, etc.)
        $scopeKey = $config['is_year_scoped'] ? OfflineTrainingOption::buildScopeKey($year ?: (int) date('Y')) : 'global';

        $existing = OfflineTrainingOption::where('option_type', $type)
            ->where('scope_key', $scopeKey)
            ->where('normalized_value', $normalized)
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'value' => ["Option '{$cleanValue}' already exists for this scope."],
            ]);
        }

        $parentId = !empty($data['parent_id']) ? (int) $data['parent_id'] : null;

        return DB::transaction(function () use ($type, $cleanValue, $normalized, $year, $scopeKey, $source, $explanation, $parentId, $sortOrder, $isActive, $userId) {
            $created = OfflineTrainingOption::create([
                'option_type'      => $type,
                'value'            => $cleanValue,
                'explanation'      => $explanation,
                'normalized_value' => $normalized,
                'year'             => $year,
                'scope_key'        => $scopeKey,
                'source'           => $source,
                'parent_id'        => $parentId,
                'is_active'        => $isActive,
                'sort_order'       => $sortOrder,
                'created_from'     => 'manual',
                'created_by'       => $userId ?? Auth::id(),
            ]);

            return [
                'success' => true,
                'message' => "Option '{$created->value}' created successfully.",
                'data'    => $created,
            ];
        });
    }

    /**
     * Update an option (Classification or OfflineTrainingOption).
     */
    public function updateOption(int $id, array $data): array
    {
        $type = (string) ($data['option_type'] ?? '');

        // 1. Classification
        if ($type === self::TYPE_CLASSIFICATION) {
            $classification = Classification::findOrFail($id);
            $name = self::normalizeString((string) ($data['classification_name'] ?? ''));

            if ($name === '') {
                throw ValidationException::withMessages([
                    'classification_name' => ['Classification name is required.'],
                ]);
            }

            $duplicate = Classification::whereRaw('LOWER(classification_name) = ?', [mb_strtolower($name)])
                ->where('id', '!=', $classification->id)
                ->first();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'classification_name' => ["Classification '{$name}' already exists."],
                ]);
            }

            return DB::transaction(function () use ($classification, $name, $data) {
                $classification->classification_name = $name;
                if (isset($data['is_active'])) {
                    $classification->is_active = (bool) $data['is_active'];
                }
                $classification->save();

                return [
                    'success' => true,
                    'message' => "Classification '{$classification->classification_name}' updated successfully.",
                    'data'    => $classification,
                ];
            });
        }

        // 2. OfflineTrainingOption
        $option = OfflineTrainingOption::findOrFail($id);
        $rawValue = (string) ($data['value'] ?? $option->value);

        if (!OfflineOptionService::isValidValue($rawValue)) {
            throw ValidationException::withMessages([
                'value' => ['The option value is required and cannot be a placeholder.'],
            ]);
        }

        $cleanValue = self::normalizeString($rawValue);
        $normalized = OfflineTrainingOption::normalizeString($cleanValue);

        // Check duplicates
        $duplicateQuery = OfflineTrainingOption::where('option_type', $option->option_type)
            ->where('normalized_value', $normalized)
            ->where('id', '!=', $option->id);

        if ($option->option_type === OfflineOptionService::TYPE_SUB_CLASSIFICATION) {
            $clsId = array_key_exists('classification_id', $data) ? (!empty($data['classification_id']) ? (int) $data['classification_id'] : null) : $option->classification_id;
            if ($clsId) {
                $duplicateQuery->where('classification_id', $clsId);
            }
        } elseif ($option->option_type === OfflineOptionService::TYPE_COURSE_NAME) {
            $pId = array_key_exists('parent_id', $data) ? (!empty($data['parent_id']) ? (int) $data['parent_id'] : null) : $option->parent_id;
            if ($pId) {
                $duplicateQuery->where('parent_id', $pId);
            }
        } else {
            $duplicateQuery->where('scope_key', $option->scope_key);
        }

        if ($duplicateQuery->exists()) {
            throw ValidationException::withMessages([
                'value' => ["Option '{$cleanValue}' already exists in this scope."],
            ]);
        }

        return DB::transaction(function () use ($option, $cleanValue, $normalized, $data) {
            $option->value = $cleanValue;
            $option->normalized_value = $normalized;

            if (array_key_exists('explanation', $data)) {
                $option->explanation = !empty($data['explanation']) ? trim((string) $data['explanation']) : null;
            }
            if (array_key_exists('source', $data)) {
                $option->source = !empty($data['source']) ? trim((string) $data['source']) : 'all';
            }
            if (array_key_exists('parent_id', $data)) {
                $option->parent_id = !empty($data['parent_id']) ? (int) $data['parent_id'] : null;
            }
            if (array_key_exists('classification_id', $data)) {
                $option->classification_id = !empty($data['classification_id']) ? (int) $data['classification_id'] : null;
            }
            if (isset($data['sort_order'])) {
                $option->sort_order = (int) $data['sort_order'];
            }
            if (isset($data['is_active'])) {
                $option->is_active = (bool) $data['is_active'];
            }
            $option->save();

            return [
                'success' => true,
                'message' => "Option '{$option->value}' updated successfully.",
                'data'    => $option,
            ];
        });
    }

    /**
     * Toggle active/inactive status.
     */
    public function toggleOptionStatus(int $id): array
    {
        $option = OfflineTrainingOption::find($id);
        if ($option) {
            $option->is_active = !$option->is_active;
            $option->save();

            $statusLabel = $option->is_active ? 'activated' : 'deactivated';

            return [
                'success'   => true,
                'is_active' => $option->is_active,
                'message'   => "Option '{$option->value}' {$statusLabel} successfully.",
                'data'      => $option,
            ];
        }

        $classification = Classification::findOrFail($id);
        $classification->is_active = !$classification->is_active;
        $classification->save();

        $statusLabel = $classification->is_active ? 'activated' : 'deactivated';

        return [
            'success'   => true,
            'is_active' => $classification->is_active,
            'message'   => "Classification '{$classification->classification_name}' {$statusLabel} successfully.",
            'data'      => $classification,
        ];
    }

    /**
     * Check usage before delete.
     */
    public function checkOptionUsage(OfflineTrainingOption $option): array
    {
        $config = $this->getTypeConfig($option->option_type);
        $column = $config['table_column'] ?? null;
        $count = 0;

        if ($column) {
            try {
                $count = OfflineTraining::where($column, $option->value)->count();
            } catch (\Throwable $e) {
                $count = 0;
            }
        }

        $childrenCount = OfflineTrainingOption::where('parent_id', $option->id)->count();

        $isUsed = ($count > 0 || $childrenCount > 0);
        $details = [];
        if ($count > 0) {
            $details[] = "{$count} training record(s)";
        }
        if ($childrenCount > 0) {
            $details[] = "{$childrenCount} child option(s)";
        }

        return [
            'is_used'        => $isUsed,
            'count'          => $count + $childrenCount,
            'details'        => implode(' and ', $details),
            'message'        => $isUsed
                ? "This option is currently linked to " . implode(' and ', $details) . " and cannot be deleted. You can deactivate it instead to hide it while preserving historical records."
                : null,
            'can_deactivate' => $option->is_active,
        ];
    }

    public function checkClassificationUsage(Classification $classification): array
    {
        $contentCount = Content::where('classification_id', $classification->id)->count();
        $offlineCount = OfflineTraining::where('main_classification', $classification->classification_name)->count();
        $subCount = OfflineTrainingOption::where('classification_id', $classification->id)->count();

        $isUsed = ($contentCount > 0 || $offlineCount > 0 || $subCount > 0);
        $details = [];
        if ($contentCount > 0) {
            $details[] = "{$contentCount} learning content(s)";
        }
        if ($offlineCount > 0) {
            $details[] = "{$offlineCount} training record(s)";
        }
        if ($subCount > 0) {
            $details[] = "{$subCount} sub classification(s)";
        }

        return [
            'is_used' => $isUsed,
            'count'   => $contentCount + $offlineCount + $subCount,
            'details' => implode(' and ', $details),
            'message' => $isUsed
                ? "This classification is currently linked to " . implode(' and ', $details) . " and cannot be deleted."
                : null,
        ];
    }

    public function deleteOption(int $id): array
    {
        $option = OfflineTrainingOption::findOrFail($id);
        $usage = $this->checkOptionUsage($option);

        if ($usage['is_used']) {
            return [
                'success'        => false,
                'is_used'        => true,
                'can_deactivate' => $option->is_active,
                'message'        => $usage['message'],
            ];
        }

        $value = $option->value;
        $option->delete();

        return [
            'success' => true,
            'message' => "Option '{$value}' deleted successfully.",
        ];
    }

    public function deleteClassification(Classification $classification): array
    {
        $usage = $this->checkClassificationUsage($classification);

        if ($usage['is_used']) {
            return [
                'success' => false,
                'is_used' => true,
                'message' => $usage['message'],
            ];
        }

        $name = $classification->classification_name;
        $classification->delete();

        return [
            'success' => true,
            'message' => "Classification '{$name}' deleted successfully.",
        ];
    }

    /**
     * Get parent options for hierarchical option creation.
     */
    public function getParentOptions(string $parentType): Collection
    {
        if ($parentType === self::TYPE_CLASSIFICATION) {
            return Classification::where('is_active', true)
                ->orderBy('classification_name', 'asc')
                ->get()
                ->map(function ($item) {
                    return (object) [
                        'id'    => $item->id,
                        'value' => $item->classification_name,
                    ];
                });
        }

        return OfflineTrainingOption::where('option_type', $parentType)
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->orderBy('value', 'asc')
            ->get(['id', 'value']);
    }

    /**
     * Get sub-classifications for a specific main classification.
     */
    public function getSubsByMain(int $mainId): Collection
    {
        return OfflineTrainingOption::where('option_type', OfflineOptionService::TYPE_SUB_CLASSIFICATION)
            ->where('classification_id', $mainId)
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->orderBy('value', 'asc')
            ->get(['id', 'value']);
    }

    /**
     * Get overview counts for hero badge stats.
     */
    public function getSummaryStats(): array
    {
        $totalClassifications = Classification::count();
        $activeClassifications = Classification::where('is_active', true)->count();
        $totalSubClassifications = OfflineTrainingOption::where('option_type', OfflineOptionService::TYPE_SUB_CLASSIFICATION)->count();
        $totalCourseExamples = OfflineTrainingOption::where('option_type', OfflineOptionService::TYPE_COURSE_NAME)->count();
        $totalTypes = OfflineTrainingOption::where('option_type', OfflineOptionService::TYPE_TYPE)->count();
        $totalCategories = OfflineTrainingOption::where('option_type', OfflineOptionService::TYPE_CATEGORY)->count();
        $totalDelivery = OfflineTrainingOption::whereIn('option_type', [
            OfflineOptionService::TYPE_DELIVERY_METHOD,
            OfflineOptionService::TYPE_SUB_DELIVERY_METHOD,
        ])->count();
        $totalProviders = OfflineTrainingOption::whereIn('option_type', [
            OfflineOptionService::TYPE_PROVIDER,
            OfflineOptionService::TYPE_SUB_PROVIDER,
        ])->count();
        $totalOptions = OfflineTrainingOption::count();
        $activeOptions = OfflineTrainingOption::where('is_active', true)->count();

        return [
            'total_classifications'     => $totalClassifications,
            'active_classifications'    => $activeClassifications,
            'total_sub_classifications' => $totalSubClassifications,
            'total_course_examples'     => $totalCourseExamples,
            'total_types'               => $totalTypes,
            'total_categories'          => $totalCategories,
            'total_delivery'            => $totalDelivery,
            'total_providers'           => $totalProviders,
            'total_options'             => $totalOptions,
            'active_options'            => $activeOptions,
            'total_types_count'         => count(self::TYPES_CONFIG),
        ];
    }
}
