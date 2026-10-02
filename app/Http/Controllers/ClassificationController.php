<?php

namespace App\Http\Controllers;

use App\Models\Classification;
use App\Models\OfflineTrainingOption;
use App\Services\Record\OfflineOptionService;
use App\Services\Record\TrainingOptionManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ClassificationController extends Controller
{
    protected TrainingOptionManagementService $optionManager;
    protected OfflineOptionService $offlineOptionService;

    public function __construct(
        TrainingOptionManagementService $optionManager,
        OfflineOptionService $offlineOptionService
    ) {
        $this->optionManager = $optionManager;
        $this->offlineOptionService = $offlineOptionService;
    }

    /**
     * AJAX live autocomplete search for Category / Classification Management.
     */
    public function autocompleteSearch(Request $request): JsonResponse
    {
        $rawQuery = trim((string) ($request->get('q') ?? $request->get('search') ?? $request->get('s') ?? ''));
        if (empty($rawQuery)) {
            return response()->json([]);
        }

        $queryNormalized = str_replace("'", "’", $rawQuery);
        $queryOriginal = str_replace("’", "'", $rawQuery);

        $categories = Classification::where(function ($q) use ($rawQuery, $queryNormalized, $queryOriginal) {
                $q->where('classification_name', 'LIKE', "%{$rawQuery}%")
                  ->orWhere('classification_name', 'LIKE', "%{$queryNormalized}%")
                  ->orWhere('classification_name', 'LIKE', "%{$queryOriginal}%")
                  ->orWhere('slug', 'LIKE', "%{$rawQuery}%");
            })
            ->latest('id')
            ->limit(10)
            ->get();

        $results = $categories->map(function ($item) {
            return [
                'id'                  => $item->id,
                'title'               => $item->classification_name,
                'name'                => $item->classification_name,
                'type'                => 'CLASSIFICATION',
                'classification_name' => $item->classification_name,
                'slug'                => $item->slug,
                'url'                 => route('manage.classification.edit', $item->slug ?? $item->id),
            ];
        });

        return response()->json($results->values());
    }

    public function autocomplete(Request $request): JsonResponse
    {
        return $this->autocompleteSearch($request);
    }

    /**
     * Unified Training Option Management Index.
     */
    public function index(Request $request)
    {
        $type = $request->get('type', TrainingOptionManagementService::TYPE_CLASSIFICATION);
        if (!$this->optionManager->isValidType($type)) {
            $type = TrainingOptionManagementService::TYPE_CLASSIFICATION;
        }

        $optionData = $this->optionManager->getOptionData($type, $request);
        $allTypes = $this->optionManager->getAllTypes();
        $primaryTypes = $this->optionManager->getPrimaryTypes();
        $secondaryTypes = $this->optionManager->getSecondaryTypes();
        $summaryStats = $this->optionManager->getSummaryStats();
        $typeConfig = $optionData['config'];

        // Parent options for hierarchical types
        $parentOptions = collect();
        if (!empty($typeConfig['parent_type'])) {
            $parentOptions = $this->optionManager->getParentOptions($typeConfig['parent_type']);
        }

        // Years for KPI
        $kpiYears = $typeConfig['is_year_scoped'] ? $this->offlineOptionService->getKpiYears() : [];

        // Retain backward-compatible classification variable
        $classification = ($type === TrainingOptionManagementService::TYPE_CLASSIFICATION)
            ? $optionData['items']
            : Classification::orderBy('classification_name', 'asc')->get();

        $totalCategories = $summaryStats['total_classifications'];
        $activeCategories = $totalCategories;
        $totalAssignedContent = \App\Models\Content::whereNotNull('classification_id')->count();
        $avgContent = $totalCategories > 0 ? round($totalAssignedContent / $totalCategories, 1) : 0;

        return view('main.classification.index', array_merge($optionData, [
            'activeType'           => $type,
            'allTypes'             => $allTypes,
            'primaryTypes'         => $primaryTypes,
            'secondaryTypes'       => $secondaryTypes,
            'typeConfig'           => $typeConfig,
            'parentOptions'        => $parentOptions,
            'kpiYears'             => $kpiYears,
            'summaryStats'         => $summaryStats,
            'classification'       => $classification,
            'totalCategories'      => $totalCategories,
            'activeCategories'     => $activeCategories,
            'totalAssignedContent' => $totalAssignedContent,
            'avgContent'           => $avgContent,
        ]));
    }

    /**
     * Show create classification form (backward-compatibility).
     */
    public function create()
    {
        return view('main.classification.create');
    }

    /**
     * Store either Classification or Option.
     */
    public function store(Request $request)
    {
        $type = $request->input('option_type', TrainingOptionManagementService::TYPE_CLASSIFICATION);

        try {
            $result = $this->optionManager->storeOption($request->all(), auth()->id());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status'   => 200,
                    'success'  => true,
                    'message'  => $result['message'],
                    'data'     => $result['data'],
                    'redirect' => route('manage.classification.index', ['type' => $type]),
                ]);
            }

            return redirect()->route('manage.classification.index', ['type' => $type])
                ->with('success', $result['message']);
        } catch (ValidationException $ve) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status'  => 422,
                    'success' => false,
                    'message' => $ve->getMessage(),
                    'errors'  => $ve->errors(),
                ], 422);
            }

            return redirect()->back()->withErrors($ve->errors())->withInput();
        } catch (\Throwable $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status'  => 500,
                    'success' => false,
                    'message' => 'Failed to save option: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to save: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Edit Classification page (backward-compatibility).
     */
    public function edit(Classification $classification)
    {
        return view('main.classification.edit', compact('classification'));
    }

    /**
     * Update Classification.
     */
    public function update(Request $request, Classification $classification)
    {
        try {
            $data = array_merge($request->all(), ['option_type' => TrainingOptionManagementService::TYPE_CLASSIFICATION]);
            $result = $this->optionManager->updateOption($classification->id, $data);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status'   => 200,
                    'success'  => true,
                    'message'  => $result['message'],
                    'redirect' => route('manage.classification.index')
                ]);
            }

            return redirect()->route('manage.classification.index')->with('success', $result['message']);
        } catch (ValidationException $ve) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status'  => 422,
                    'success' => false,
                    'message' => $ve->getMessage(),
                    'errors'  => $ve->errors(),
                ], 422);
            }

            return redirect()->back()->withErrors($ve->errors())->withInput();
        }
    }

    /**
     * Remove Classification safely (prevent delete if in use!).
     */
    public function destroy(Classification $classification)
    {
        $result = $this->optionManager->deleteClassification($classification);

        if (!$result['success']) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'status'   => 422,
                    'success'  => false,
                    'is_used'  => true,
                    'message'  => $result['message'],
                ], 422);
            }

            return redirect()->route('manage.classification.index')
                ->with('error', $result['message']);
        }

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'status'  => 200,
                'success' => true,
                'message' => $result['message'],
            ]);
        }

        return redirect()->route('manage.classification.index')
            ->with('success', $result['message']);
    }

    /**
     * Dedicated API Endpoint: Store Option.
     */
    public function storeOption(Request $request): JsonResponse
    {
        return $this->store($request);
    }

    /**
     * Dedicated API Endpoint: Get Option details for edit modal.
     */
    public function getOption(int $id): JsonResponse
    {
        $option = OfflineTrainingOption::with('parent')->find($id);
        if (!$option) {
            return response()->json([
                'status'  => 404,
                'success' => false,
                'message' => 'Option not found.',
            ], 404);
        }

        $usage = $this->optionManager->checkOptionUsage($option);

        return response()->json([
            'status'  => 200,
            'success' => true,
            'data'    => $option,
            'usage'   => $usage,
        ]);
    }

    /**
     * Dedicated API Endpoint: Update Option.
     */
    public function updateOption(Request $request, int $id): JsonResponse
    {
        try {
            $result = $this->optionManager->updateOption($id, $request->all());

            return response()->json([
                'status'  => 200,
                'success' => true,
                'message' => $result['message'],
                'data'    => $result['data'],
            ]);
        } catch (ValidationException $ve) {
            return response()->json([
                'status'  => 422,
                'success' => false,
                'message' => $ve->getMessage(),
                'errors'  => $ve->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 500,
                'success' => false,
                'message' => 'Failed to update option: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Dedicated API Endpoint: Toggle Option Active Status.
     */
    public function toggleOptionStatus(int $id): JsonResponse
    {
        try {
            $result = $this->optionManager->toggleOptionStatus($id);

            return response()->json([
                'status'    => 200,
                'success'   => true,
                'is_active' => $result['is_active'],
                'message'   => $result['message'],
                'data'      => $result['data'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 500,
                'success' => false,
                'message' => 'Failed to toggle status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Dedicated API Endpoint: Check Option Usage before delete.
     */
    public function checkOptionUsage(int $id): JsonResponse
    {
        $option = OfflineTrainingOption::find($id);
        if (!$option) {
            return response()->json([
                'status'  => 404,
                'success' => false,
                'message' => 'Option not found.',
            ], 404);
        }

        $usage = $this->optionManager->checkOptionUsage($option);

        return response()->json([
            'status'  => 200,
            'success' => true,
            'data'    => $usage,
        ]);
    }

    /**
     * Dedicated API Endpoint: Delete Option safely.
     */
    public function destroyOption(int $id): JsonResponse
    {
        $result = $this->optionManager->deleteOption($id);

        if (!$result['success']) {
            return response()->json([
                'status'         => 422,
                'success'        => false,
                'is_used'        => true,
                'can_deactivate' => $result['can_deactivate'] ?? false,
                'message'        => $result['message'],
            ], 422);
        }

        return response()->json([
            'status'  => 200,
            'success' => true,
            'message' => $result['message'],
        ]);
    }

    /**
     * Dedicated API Endpoint: Get parent options for child option dropdowns.
     */
    public function getParentOptions(string $type): JsonResponse
    {
        $parents = $this->optionManager->getParentOptions($type);

        return response()->json([
            'status'  => 200,
            'success' => true,
            'data'    => $parents,
        ]);
    }
}
