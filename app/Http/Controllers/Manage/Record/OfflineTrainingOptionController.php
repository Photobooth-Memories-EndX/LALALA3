<?php

namespace App\Http\Controllers\Manage\Record;

use App\Http\Controllers\Controller;
use App\Models\OfflineTrainingOption;
use App\Services\Record\OfflineOptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class OfflineTrainingOptionController extends Controller
{
    protected OfflineOptionService $optionService;

    public function __construct(OfflineOptionService $optionService)
    {
        $this->optionService = $optionService;
    }

    /**
     * Search options by type, query search, and year.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'type'   => ['required', 'string', Rule::in(OfflineOptionService::ALLOWED_TYPES)],
            'search' => ['nullable', 'string', 'max:255'],
            'q'      => ['nullable', 'string', 'max:255'],
            'year'   => ['nullable', 'integer', 'min:1970', 'max:2100'],
        ]);

        $type = (string) $request->input('type');
        $search = $request->input('search', $request->input('q'));
        $year = $request->filled('year') ? (int) $request->input('year') : null;

        $options = $this->optionService->searchOptions(
            type: $type,
            search: $search,
            year: $year
        );

        $results = $options->map(function (OfflineTrainingOption $item) {
            return [
                'id'               => $item->value,
                'text'             => $item->value,
                'value'            => $item->value,
                'option_id'        => $item->id,
                'option_type'      => $item->option_type,
                'year'             => $item->year,
                'normalized_value' => $item->normalized_value,
            ];
        });

        return response()->json([
            'status'  => 'success',
            'results' => $results,
            'data'    => $results,
        ]);
    }

    /**
     * Store a new master option manually from UI [+] button.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'option_type' => ['required', 'string', Rule::in(OfflineOptionService::ALLOWED_TYPES)],
            'value'       => ['required', 'string', 'max:255'],
            'year'        => ['nullable', 'integer', 'min:1970', 'max:2100'],
        ]);

        $type = (string) $request->input('option_type');
        $rawValue = (string) $request->input('value');
        $year = $request->filled('year') ? (int) $request->input('year') : null;

        if ($type === OfflineOptionService::TYPE_SUB_CORPORATE_KPI && !$year) {
            $trimmed = trim($rawValue);
            if (preg_match('/^\d{4}$/', $trimmed)) {
                $year = (int) $trimmed;
            } else {
                $year = (int) date('Y');
            }
        }

        if (!OfflineOptionService::isValidValue($rawValue)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'The provided option value is invalid or reserved.',
            ], 422);
        }

        $cleanValue = trim(preg_replace('/\s+/', ' ', $rawValue));
        $normalized = OfflineOptionService::normalize($cleanValue);
        $effectiveYear = OfflineOptionService::isYearScoped($type) ? ($year ?: (int) date('Y')) : null;
        $scopeKey = OfflineTrainingOption::buildScopeKey($effectiveYear);

        // Check if exists
        $existing = OfflineTrainingOption::where('option_type', $type)
            ->where('scope_key', $scopeKey)
            ->where('normalized_value', $normalized)
            ->first();

        if ($existing) {
            return response()->json([
                'success'     => true,
                'status'      => 'exists',
                'is_existing' => true,
                'message'     => "Option '{$existing->value}' already exists and has been selected.",
                'data'        => [
                    'id'               => $existing->id,
                    'option_type'      => $existing->option_type,
                    'value'            => $existing->value,
                    'text'             => $existing->value,
                    'normalized_value' => $existing->normalized_value,
                    'year'             => $existing->year,
                    'scope_key'        => $existing->scope_key,
                    'is_active'        => $existing->is_active,
                ],
            ], 200);
        }

        $option = $this->optionService->registerOption(
            type: $type,
            value: $cleanValue,
            year: $effectiveYear,
            createdFrom: 'manual',
            createdBy: Auth::id()
        );

        return response()->json([
            'success'     => true,
            'status'      => 'success',
            'is_existing' => false,
            'message'     => "Option '{$option->value}' created successfully.",
            'data'        => [
                'id'               => $option->id,
                'option_type'      => $option->option_type,
                'value'            => $option->value,
                'text'             => $option->value,
                'normalized_value' => $option->normalized_value,
                'year'             => $option->year,
                'scope_key'        => $option->scope_key,
                'is_active'        => $option->is_active,
            ],
        ], 201);
    }
}
