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
     * Complete metadata definition for all 15 training option types.
     */
    public const TYPES_CONFIG = [
        self::TYPE_CLASSIFICATION => [
            'type'            => 'classification',
            'label'           => 'Classification',
            'plural_label'    => 'Classifications',
            'description'     => 'Main learning classifications used for content organization and LMS reporting.',
            'icon'            => 'ti-category-2',
            'source'          => 'classification_table',
            'is_hierarchical' => false,
            'parent_type'     => null,
            'is_year_scoped'  => false,
            'table_column'    => 'main_classification',
        ],
        OfflineOptionService::TYPE_TYPE => [
            'type'            => 'type',
            'label'           => 'Type',
            'plural_label'    => 'Types',
            'description'     => 'Training execution types (In-House, Public, Workshop, Certification, etc.).',
            'icon'            => 'ti-bookmarks',
            'source'          => 'offline_training_options',
            'is_hierarchical' => false,
            'parent_type'     => null,
            'is_year_scoped'  => false,
            'table_column'    => 'type',
        ],
        OfflineOptionService::TYPE_CATEGORY => [
            'type'            => 'category',
            'label'           => 'Category',
            'plural_label'    => 'Categories',
            'description'     => 'Training category themes (Technical, Leadership, Quality, Functional, etc.).',
            'icon'            => 'ti-folder',
            'source'          => 'offline_training_options',
            'is_hierarchical' => false,
            'parent_type'     => null,
            'is_year_scoped'  => false,
            'table_column'    => 'category',
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
        OfflineOptionService::TYPE_DELIVERY_METHOD => [
            'type'            => 'delivery_method',
            'label'           => 'Delivery Method',
            'plural_label'    => 'Delivery Methods',
            'description'     => 'Primary training delivery modes (Classroom, Practical, Hybrid, E-Learning, etc.).',
            'icon'            => 'ti-presentation',
            'source'          => 'offline_training_options',
            'is_hierarchical' => false,
            'parent_type'     => null,
            'is_year_scoped'  => false,
            'table_column'    => 'delivery_method',
        ],
        OfflineOptionService::TYPE_SUB_DELIVERY_METHOD => [
            'type'            => 'sub_delivery_method',
            'label'           => 'Sub Delivery Method',
            'plural_label'    => 'Sub Delivery Methods',
            'description'     => 'Detailed delivery sub-methods (Hands-on Lab, Interactive Workshop, etc.).',
            'icon'            => 'ti-chalkboard',
            'source'          => 'offline_training_options',
            'is_hierarchical' => true,
            'parent_type'     => 'delivery_method',
            'is_year_scoped'  => false,
            'table_column'    => 'sub_delivery_method',
        ],
        OfflineOptionService::TYPE_PROVIDER => [
            'type'            => 'provider',
            'label'           => 'Provider',
            'plural_label'    => 'Providers',
            'description'     => 'Training organizers and source categories (Internal, External).',
            'icon'            => 'ti-building',
            'source'          => 'offline_training_options',
            'is_hierarchical' => false,
            'parent_type'     => null,
            'is_year_scoped'  => false,
            'table_column'    => 'provider',
        ],
        OfflineOptionService::TYPE_SUB_PROVIDER => [
            'type'            => 'sub_provider',
            'label'           => 'Sub Provider',
            'plural_label'    => 'Sub Providers',
            'description'     => 'Provider types (Vendor, University, Certified Center, Internal Trainer, etc.).',
            'icon'            => 'ti-building-community',
            'source'          => 'offline_training_options',
            'is_hierarchical' => true,
            'parent_type'     => 'provider',
            'is_year_scoped'  => false,
            'table_column'    => 'sub_provider',
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
        OfflineOptionService::TYPE_SUB_CLASSIFICATION => [
            'type'            => 'sub_classification',
            'label'           => 'Sub Classification',
            'plural_label'    => 'Sub Classifications',
            'description'     => 'Specific discipline and domain sub-classifications.',
            'icon'            => 'ti-subtask',
            'source'          => 'offline_training_options',
            'is_hierarchical' => false,
            'parent_type'     => null,
            'is_year_scoped'  => false,
            'table_column'    => 'sub_classification',
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
        OfflineOptionService::TYPE_COURSE_NAME => [
            'type'            => 'course_name',
            'label'           => 'Course Name',
            'plural_label'    => 'Course Names',
            'description'     => 'Standardized master course titles used across training records.',
            'icon'            => 'ti-certificate',
            'source'          => 'offline_training_options',
            'is_hierarchical' => false,
            'parent_type'     => null,
            'is_year_scoped'  => false,
            'table_column'    => 'course_name',
        ],
        OfflineOptionService::TYPE_SUB_COURSE_NAME => [
            'type'            => 'sub_course_name',
            'label'           => 'Sub Course Name',
            'plural_label'    => 'Sub Course Names',
            'description'     => 'Specific modules and syllabus sub-topics under course names.',
            'icon'            => 'ti-list-details',
            'source'          => 'offline_training_options',
            'is_hierarchical' => true,
            'parent_type'     => 'course_name',
            'is_year_scoped'  => false,
            'table_column'    => 'sub_course_name',
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
     * Primary quick-access types displayed as main tabs.
     */
    public const PRIMARY_TYPES = [
        self::TYPE_CLASSIFICATION,
        OfflineOptionService::TYPE_TYPE,
        OfflineOptionService::TYPE_CATEGORY,
        OfflineOptionService::TYPE_LEVEL,
        OfflineOptionService::TYPE_DELIVERY_METHOD,
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

    /**
     * Normalize string value safely (trim duplicate spaces).
     */
    public static function normalizeString(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', $value));
    }

    /**
     * Query data for current active option type with search, status, parent, year and pagination.
     */
    public function getOptionData(string $type, Request $request): array
    {
        $config = $this->getTypeConfig($type);
        $search = trim((string) ($request->get('q') ?? $request->get('search') ?? ''));
        $status = $request->get('status'); // '1', '0', or null (all)
        $parent = $request->get('parent_id');
        $year = $request->get('year');
        $perPage = (int) $request->get('entries', 25);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 25;
        }

        if ($type === self::TYPE_CLASSIFICATION) {
            $query = Classification::query();

            if ($search !== '') {
                $searchNormalized = str_replace("'", "’", $search);
                $searchOriginal = str_replace("’", "'", $search);
                $query->where(function ($q) use ($search, $searchNormalized, $searchOriginal) {
                    $q->where('classification_name', 'LIKE', "%{$search}%")
                      ->orWhere('classification_name', 'LIKE', "%{$searchNormalized}%")
                      ->orWhere('classification_name', 'LIKE', "%{$searchOriginal}%")
                      ->orWhere('slug', 'LIKE', "%{$search}%");
                });
            }

            $totalCount = Classification::count();
            $items = $query->orderBy('classification_name', 'asc')->get();

            // Enrich with usage stats
            $enriched = $items->map(function ($item) {
                $contentCount = Content::where('classification_id', $item->id)->count();
                $offlineCount = OfflineTraining::where('main_classification', $item->classification_name)->count();
                $item->content_count = $contentCount;
                $item->offline_count = $offlineCount;
                $item->total_usage = $contentCount + $offlineCount;
                return $item;
            });

            return [
                'type'        => $type,
                'config'      => $config,
                'items'       => $enriched,
                'total_count' => $totalCount,
                'active_count'=> $totalCount,
                'per_page'    => $perPage,
            ];
        }

        // Offline Training Options
        $query = OfflineTrainingOption::with('parent')
            ->where('option_type', $type);

        if ($search !== '') {
            $normalizedSearch = OfflineTrainingOption::normalizeString($search);
            $query->where(function ($q) use ($search, $normalizedSearch) {
                $q->where('value', 'LIKE', "%{$search}%")
                  ->orWhere('normalized_value', 'LIKE', "%{$normalizedSearch}%");
            });
        }

        if ($status !== null && $status !== '' && in_array($status, ['0', '1'], true)) {
            $query->where('is_active', $status === '1');
        }

        if ($config['is_hierarchical'] && !empty($parent)) {
            $query->where('parent_id', (int) $parent);
        }

        if ($config['is_year_scoped']) {
            if (!empty($year)) {
                $query->where('scope_key', OfflineTrainingOption::buildScopeKey((int) $year));
            }
        }

        $totalCount = OfflineTrainingOption::where('option_type', $type)->count();
        $activeCount = OfflineTrainingOption::where('option_type', $type)->where('is_active', true)->count();

        $items = $query->orderBy('sort_order', 'asc')
            ->orderBy('value', 'asc')
            ->get();

        // Enrich with usage counts in offline_trainings
        $column = $config['table_column'] ?? null;
        $enriched = $items->map(function ($item) use ($column) {
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

        return [
            'type'        => $type,
            'config'      => $config,
            'items'       => $enriched,
            'total_count' => $totalCount,
            'active_count'=> $activeCount,
            'per_page'    => $perPage,
        ];
    }

    /**
     * Store new option (either Classification or OfflineTrainingOption).
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

        if ($type === self::TYPE_CLASSIFICATION) {
            $name = self::normalizeString((string) ($data['classification_name'] ?? ''));
            if ($name === '') {
                throw ValidationException::withMessages([
                    'classification_name' => ['Classification name is required.'],
                ]);
            }

            // Check duplicate (case-insensitive)
            $existing = Classification::whereRaw('LOWER(classification_name) = ?', [mb_strtolower($name)])->first();
            if ($existing) {
                throw ValidationException::withMessages([
                    'classification_name' => ["Classification '{$name}' already exists."],
                ]);
            }

            return DB::transaction(function () use ($name) {
                $classification = Classification::create([
                    'classification_name' => $name,
                ]);

                return [
                    'success' => true,
                    'message' => "Classification '{$classification->classification_name}' created successfully.",
                    'data'    => $classification,
                ];
            });
        }

        // OfflineTrainingOption
        $rawValue = (string) ($data['value'] ?? '');
        if (!OfflineOptionService::isValidValue($rawValue)) {
            throw ValidationException::withMessages([
                'value' => ['The option value is required and cannot be a placeholder (e.g. -, N/A).'],
            ]);
        }

        $cleanValue = self::normalizeString($rawValue);
        $normalized = OfflineTrainingOption::normalizeString($cleanValue);
        $year = !empty($data['year']) ? (int) $data['year'] : null;

        if ($config['is_year_scoped'] && !$year) {
            if (preg_match('/^\d{4}$/', $cleanValue)) {
                $year = (int) $cleanValue;
            } else {
                $year = (int) date('Y');
            }
        }

        $scopeKey = $config['is_year_scoped'] ? OfflineTrainingOption::buildScopeKey($year) : 'global';

        // Duplicate check
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
        if ($parentId) {
            $parentExists = OfflineTrainingOption::where('id', $parentId)->exists();
            if (!$parentExists) {
                $parentId = null;
            }
        }

        $sortOrder = isset($data['sort_order']) ? (int) $data['sort_order'] : 0;
        $isActive = isset($data['is_active']) ? (bool) $data['is_active'] : true;

        return DB::transaction(function () use ($type, $cleanValue, $normalized, $year, $scopeKey, $parentId, $sortOrder, $isActive, $userId) {
            $created = OfflineTrainingOption::create([
                'option_type'      => $type,
                'value'            => $cleanValue,
                'normalized_value' => $normalized,
                'year'             => $year,
                'scope_key'        => $scopeKey,
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
        $config = $this->getTypeConfig($type);

        if ($type === self::TYPE_CLASSIFICATION) {
            $classification = Classification::findOrFail($id);
            $name = self::normalizeString((string) ($data['classification_name'] ?? ''));

            if ($name === '') {
                throw ValidationException::withMessages([
                    'classification_name' => ['Classification name is required.'],
                ]);
            }

            // Check duplicate
            $duplicate = Classification::whereRaw('LOWER(classification_name) = ?', [mb_strtolower($name)])
                ->where('id', '!=', $classification->id)
                ->first();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'classification_name' => ["Classification '{$name}' already exists."],
                ]);
            }

            return DB::transaction(function () use ($classification, $name) {
                $oldName = $classification->classification_name;
                $classification->classification_name = $name;
                $classification->save();

                return [
                    'success' => true,
                    'message' => "Classification updated successfully.",
                    'data'    => $classification,
                ];
            });
        }

        // OfflineTrainingOption
        $option = OfflineTrainingOption::findOrFail($id);
        $rawValue = (string) ($data['value'] ?? $option->value);

        if (!OfflineOptionService::isValidValue($rawValue)) {
            throw ValidationException::withMessages([
                'value' => ['The option value is required and cannot be a placeholder.'],
            ]);
        }

        $cleanValue = self::normalizeString($rawValue);
        $normalized = OfflineTrainingOption::normalizeString($cleanValue);
        $year = isset($data['year']) ? (int) $data['year'] : $option->year;

        if ($config['is_year_scoped'] && !$year) {
            $year = (int) date('Y');
        }

        $scopeKey = $config['is_year_scoped'] ? OfflineTrainingOption::buildScopeKey($year) : 'global';

        // Duplicate check
        $duplicate = OfflineTrainingOption::where('option_type', $option->option_type)
            ->where('scope_key', $scopeKey)
            ->where('normalized_value', $normalized)
            ->where('id', '!=', $option->id)
            ->first();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'value' => ["Option '{$cleanValue}' already exists for this scope."],
            ]);
        }

        $parentId = array_key_exists('parent_id', $data) ? (!empty($data['parent_id']) ? (int) $data['parent_id'] : null) : $option->parent_id;
        $sortOrder = isset($data['sort_order']) ? (int) $data['sort_order'] : $option->sort_order;
        $isActive = isset($data['is_active']) ? (bool) $data['is_active'] : $option->is_active;

        return DB::transaction(function () use ($option, $cleanValue, $normalized, $year, $scopeKey, $parentId, $sortOrder, $isActive) {
            $option->value = $cleanValue;
            $option->normalized_value = $normalized;
            $option->year = $year;
            $option->scope_key = $scopeKey;
            $option->parent_id = $parentId;
            $option->sort_order = $sortOrder;
            $option->is_active = $isActive;
            $option->save();

            return [
                'success' => true,
                'message' => "Option '{$option->value}' updated successfully.",
                'data'    => $option,
            ];
        });
    }

    /**
     * Toggle active/inactive status of an OfflineTrainingOption.
     */
    public function toggleOptionStatus(int $id): array
    {
        $option = OfflineTrainingOption::findOrFail($id);
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

    /**
     * Check if an OfflineTrainingOption is currently in use.
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

        // Also check if any other option has this option as parent
        $childrenCount = OfflineTrainingOption::where('parent_id', $option->id)->count();

        $isUsed = ($count > 0 || $childrenCount > 0);
        $details = [];
        if ($count > 0) {
            $details[] = "{$count} training record(s)";
        }
        if ($childrenCount > 0) {
            $details[] = "{$childrenCount} sub-option(s)";
        }

        return [
            'is_used'        => $isUsed,
            'count'          => $count + $childrenCount,
            'details'        => implode(' and ', $details),
            'message'        => $isUsed
                ? "This option is currently used by " . implode(' and ', $details) . " and cannot be deleted. You can deactivate it instead to hide it from future dropdowns while preserving historical data."
                : null,
            'can_deactivate' => $option->is_active,
        ];
    }

    /**
     * Check if a Classification is currently in use.
     */
    public function checkClassificationUsage(Classification $classification): array
    {
        $contentCount = Content::where('classification_id', $classification->id)->count();
        $offlineCount = OfflineTraining::where('main_classification', $classification->classification_name)->count();

        $isUsed = ($contentCount > 0 || $offlineCount > 0);
        $details = [];
        if ($contentCount > 0) {
            $details[] = "{$contentCount} learning content(s)";
        }
        if ($offlineCount > 0) {
            $details[] = "{$offlineCount} training record(s)";
        }

        return [
            'is_used' => $isUsed,
            'count'   => $contentCount + $offlineCount,
            'details' => implode(' and ', $details),
            'message' => $isUsed
                ? "This classification is currently used by " . implode(' and ', $details) . " and cannot be deleted."
                : null,
        ];
    }

    /**
     * Delete an option safely (reject if used).
     */
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

    /**
     * Delete a classification safely (reject if used).
     */
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
     * Get parent options for hierarchical option creation (e.g. for sub_delivery_method get delivery_methods).
     */
    public function getParentOptions(string $parentType): Collection
    {
        return OfflineTrainingOption::where('option_type', $parentType)
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
        $totalOptions = OfflineTrainingOption::count();
        $activeOptions = OfflineTrainingOption::where('is_active', true)->count();
        $totalTypes = count(self::TYPES_CONFIG);

        return [
            'total_classifications' => $totalClassifications,
            'total_options'         => $totalOptions,
            'active_options'        => $activeOptions,
            'total_types'           => $totalTypes,
        ];
    }
}
