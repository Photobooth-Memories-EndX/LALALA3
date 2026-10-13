<?php

namespace App\Services\Record;

use App\Models\OfflineTrainingOption;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class OfflineOptionService
{
    public const TYPE_COURSE_NAME = 'course_name';
    public const TYPE_SUB_COURSE_NAME = 'sub_course_name';
    public const TYPE_TYPE = 'type';
    public const TYPE_CATEGORY = 'category';
    public const TYPE_LEVEL = 'level';
    public const TYPE_SUB_CLASSIFICATION = 'sub_classification';
    public const TYPE_SUB_CORPORATE_KPI = 'sub_corporate_kpi';
    public const TYPE_DELIVERY_METHOD = 'delivery_method';
    public const TYPE_SUB_DELIVERY_METHOD = 'sub_delivery_method';
    public const TYPE_PROVIDER = 'provider';
    public const TYPE_SUB_PROVIDER = 'sub_provider';
    public const TYPE_PROVIDER_NAME = 'provider_name';
    public const TYPE_VENUE = 'venue';
    public const TYPE_INSTRUCTOR = 'instructor';
    public const TYPE_TARGET_EMPLOYEE = 'target_employee';
    public const TYPE_PREREQUISITES = 'prerequisites';

    /**
     * Allowed option types in offline_training_options.
     * Note: 'main_classification' is intentionally EXCLUDED because App\Models\Classification
     * is the canonical source of truth for main classifications.
     */
    public const ALLOWED_TYPES = [
        self::TYPE_COURSE_NAME,
        self::TYPE_SUB_COURSE_NAME,
        self::TYPE_TYPE,
        self::TYPE_CATEGORY,
        self::TYPE_LEVEL,
        self::TYPE_SUB_CLASSIFICATION,
        self::TYPE_SUB_CORPORATE_KPI,
        self::TYPE_DELIVERY_METHOD,
        self::TYPE_SUB_DELIVERY_METHOD,
        self::TYPE_PROVIDER,
        self::TYPE_SUB_PROVIDER,
        self::TYPE_PROVIDER_NAME,
        self::TYPE_VENUE,
        self::TYPE_INSTRUCTOR,
        self::TYPE_TARGET_EMPLOYEE,
        self::TYPE_PREREQUISITES,
        'title',
        'location',
        'target_audience',
        'requirements',
    ];

    /**
     * Memory cache for batch operations (e.g. imports)
     * Key: "$type:$scopeKey:$normalizedValue" => OfflineTrainingOption
     */
    protected array $cache = [];

    /**
     * Normalize string for display/storage (trim whitespace and duplicate spaces).
     */
    public static function normalizeString(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', $value));
    }

    /**
     * Normalize key for case-insensitive lookup.
     */
    public static function normalizeKey(string $value): string
    {
        return OfflineTrainingOption::normalizeString($value);
    }

    /**
     * Normalize string for comparison.
     */
    public static function normalize(string $value): string
    {
        return OfflineTrainingOption::normalizeString($value);
    }

    /**
     * Check if a value is a legitimate option name (reject null, empty, hyphens, NA, etc.).
     */
    public static function isValidValue(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return false;
        }

        $normalized = mb_strtolower($trimmed);
        $blacklisted = ['-', '--', '---', 'n/a', 'na', 'null', 'none', '?', 'undefined'];

        return !in_array($normalized, $blacklisted, true);
    }

    /**
     * Determine if an option type is scoped to a specific year.
     */
    public static function isYearScoped(string $type): bool
    {
        return $type === self::TYPE_SUB_CORPORATE_KPI;
    }

    /**
     * Clear in-memory cache.
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }

    /**
     * Convenient alias for finding or creating an option with null handling.
     */
    public function firstOrCreateOption(
        string $type,
        ?string $value,
        ?int $year = null,
        string $createdFrom = 'system',
        ?int $createdBy = null,
        int $sortOrder = 0
    ): ?OfflineTrainingOption {
        if ($value === null || !self::isValidValue($value)) {
            return null;
        }

        return $this->registerOption($type, $value, $year, $createdFrom, $createdBy, $sortOrder);
    }

    /**
     * Register or find a master option.
     */
    public function registerOption(
        string $type,
        string $value,
        ?int $year = null,
        string $createdFrom = 'system',
        ?int $createdBy = null,
        int $sortOrder = 0
    ): ?OfflineTrainingOption {
        $typeMap = [
            'title'           => self::TYPE_COURSE_NAME,
            'location'        => self::TYPE_VENUE,
            'target_audience' => self::TYPE_TARGET_EMPLOYEE,
            'requirements'    => self::TYPE_PREREQUISITES,
        ];
        $type = $typeMap[$type] ?? $type;

        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            if ($type === 'main_classification') {
                throw new InvalidArgumentException(
                    "Main classification must NOT be stored in offline_training_options. Use App\\Models\\Classification instead."
                );
            }
            throw new InvalidArgumentException("Invalid option type: '{$type}'.");
        }

        if (!self::isValidValue($value)) {
            return null;
        }

        $cleanValue = trim(preg_replace('/\s+/', ' ', $value));
        $normalized = self::normalize($cleanValue);

        $effectiveYear = null;
        if (self::isYearScoped($type)) {
            if ($year) {
                $effectiveYear = (int) $year;
            } elseif (preg_match('/^\d{4}$/', $cleanValue)) {
                $effectiveYear = (int) $cleanValue;
            } else {
                $effectiveYear = (int) date('Y');
            }
        }
        $scopeKey = OfflineTrainingOption::buildScopeKey($effectiveYear);
        $cacheKey = "{$type}:{$scopeKey}:{$normalized}";

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $existing = OfflineTrainingOption::where('option_type', $type)
            ->where('scope_key', $scopeKey)
            ->where('normalized_value', $normalized)
            ->first();

        if ($existing) {
            $this->cache[$cacheKey] = $existing;
            return $existing;
        }

        try {
            $created = OfflineTrainingOption::create([
                'option_type'      => $type,
                'value'            => $cleanValue,
                'normalized_value' => $normalized,
                'year'             => $effectiveYear,
                'scope_key'        => $scopeKey,
                'is_active'        => true,
                'sort_order'       => $sortOrder,
                'created_from'     => $createdFrom,
                'created_by'       => $createdBy,
            ]);

            $this->cache[$cacheKey] = $created;
            return $created;
        } catch (QueryException $e) {
            // MySQL error 1062 duplicate key race condition safety
            if ($e->getCode() == 23000 || str_contains($e->getMessage(), 'Duplicate entry') || (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062)) {
                $reQuery = OfflineTrainingOption::where('option_type', $type)
                    ->where('scope_key', $scopeKey)
                    ->where('normalized_value', $normalized)
                    ->first();

                if ($reQuery) {
                    $this->cache[$cacheKey] = $reQuery;
                    return $reQuery;
                }
            }

            throw $e;
        }
    }

    /**
     * Batch ensure/auto-register options from training data attributes.
     */
    public function ensureOptionsFromTraining(array $trainingData, string $createdFrom = 'manual', ?int $createdBy = null): void
    {
        $year = null;
        if (!empty($trainingData['corporate_kpi_year'])) {
            $year = (int) $trainingData['corporate_kpi_year'];
        } elseif (!empty($trainingData['start_date'])) {
            $year = (int) substr((string) $trainingData['start_date'], 0, 4);
        }

        foreach (self::ALLOWED_TYPES as $type) {
            if (!empty($trainingData[$type]) && is_string($trainingData[$type])) {
                $effectiveYear = self::isYearScoped($type) ? $year : null;
                $this->registerOption(
                    $type,
                    $trainingData[$type],
                    $effectiveYear,
                    $createdFrom,
                    $createdBy
                );
            }
        }
    }

    /**
     * Fetch options for a given type and year.
     */
    public function getOptions(string $type, ?int $year = null, bool $onlyActive = true): Collection
    {
        $query = OfflineTrainingOption::where('option_type', $type);

        if (self::isYearScoped($type)) {
            $effectiveYear = $year ?: (int) date('Y');
            $query->where('scope_key', OfflineTrainingOption::buildScopeKey($effectiveYear));
        } else {
            $query->where('scope_key', 'global');
        }

        if ($onlyActive) {
            $query->where('is_active', true);
        }

        return $query->orderBy('sort_order', 'asc')
            ->orderBy('value', 'asc')
            ->get();
    }

    /**
     * Group options by option_type for form rendering.
     */
    public function getGroupedOptions(?int $year = null, bool $onlyActive = true): array
    {
        $effectiveYear = $year ?: (int) date('Y');
        $query = OfflineTrainingOption::query();

        if ($onlyActive) {
            $query->where('is_active', true);
        }

        $all = $query->orderBy('sort_order', 'asc')
            ->orderBy('value', 'asc')
            ->get();

        $grouped = [];
        foreach (self::ALLOWED_TYPES as $type) {
            $grouped[$type] = collect();
        }

        foreach ($all as $option) {
            if (self::isYearScoped($option->option_type)) {
                if ($option->year === $effectiveYear) {
                    $grouped[$option->option_type]->push($option);
                }
            } elseif ($option->scope_key === 'global') {
                $grouped[$option->option_type]->push($option);
            }
        }

        return $grouped;
    }

    /**
     * Search options for AJAX autocompletes / Select2.
     */
    public function searchOptions(string $type, ?string $search = null, ?int $year = null, int $limit = 30): Collection
    {
        $query = OfflineTrainingOption::where('option_type', $type)
            ->where('is_active', true);

        if (self::isYearScoped($type)) {
            $effectiveYear = $year ?: (int) date('Y');
            $query->where('scope_key', OfflineTrainingOption::buildScopeKey($effectiveYear));
        } else {
            $query->where('scope_key', 'global');
        }

        if ($search !== null && trim($search) !== '') {
            $searchNormalized = self::normalize($search);
            $query->where(function ($q) use ($search, $searchNormalized) {
                $q->where('value', 'like', "%{$search}%")
                  ->orWhere('normalized_value', 'like', "%{$searchNormalized}%");
            });
        }

        return $query->orderBy('sort_order', 'asc')
            ->orderBy('value', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get available Corporate KPI years for dropdown selection.
     * Merges option records, training records, and standard year window.
     *
     * @return int[]
     */
    public function getKpiYears(): array
    {
        $years = [];

        // 1. Years from OfflineTrainingOption
        try {
            $optYears = OfflineTrainingOption::where('option_type', self::TYPE_SUB_CORPORATE_KPI)
                ->whereNotNull('year')
                ->pluck('year')
                ->all();
            foreach ($optYears as $y) {
                if ($y) {
                    $years[] = (int) $y;
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // 2. Years from OfflineTraining
        try {
            $trainYears = \App\Models\OfflineTraining::distinct()
                ->whereNotNull('corporate_kpi_year')
                ->pluck('corporate_kpi_year')
                ->all();
            foreach ($trainYears as $y) {
                if ($y) {
                    $years[] = (int) $y;
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // 3. Standard year window: current year + 1 down to current year - 4
        $curYear = (int) date('Y');
        for ($y = $curYear + 1; $y >= $curYear - 4; $y--) {
            $years[] = $y;
        }

        $years = array_values(array_unique($years));
        rsort($years);

        return $years;
    }
}
