<?php

namespace App\Http\Controllers;

use App\Models\Classification;
use App\Models\OfflineTrainingOption;
use App\Services\Record\OfflineOptionService;
use App\Services\Record\TrainingOptionManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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

        $classificationsList = Classification::orderBy('classification_name', 'asc')->get();
        $subClassificationsList = OfflineTrainingOption::where('option_type', OfflineOptionService::TYPE_SUB_CLASSIFICATION)
            ->orderBy('sort_order', 'asc')
            ->orderBy('value', 'asc')
            ->get();
        $trainingTypeOptions = OfflineTrainingOption::where('option_type', OfflineOptionService::TYPE_TYPE)
            ->orderBy('sort_order', 'asc')
            ->orderBy('value', 'asc')
            ->get();

        return view('main.classification.index', array_merge($optionData, [
            'activeType'             => $type,
            'allTypes'               => $allTypes,
            'primaryTypes'           => $primaryTypes,
            'secondaryTypes'         => $secondaryTypes,
            'typeConfig'             => $typeConfig,
            'parentOptions'          => $parentOptions,
            'kpiYears'               => $kpiYears,
            'summaryStats'           => $summaryStats,
            'classificationsList'    => $classificationsList,
            'subClassificationsList' => $subClassificationsList,
            'trainingTypeOptions'    => $trainingTypeOptions,
            'classification'         => $classification,
            'totalCategories'        => $totalCategories,
            'activeCategories'       => $activeCategories,
            'totalAssignedContent'   => $totalAssignedContent,
            'avgContent'             => $avgContent,
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
     * Display the specified resource.
     */
    public function show(Classification $classification)
    {
        return redirect()->route('manage.classification.edit', $classification);
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
     * Dedicated API Endpoint: Get sub-classifications for a specific main classification.
     */
    public function getSubsByMain(int $mainId): JsonResponse
    {
        $subs = $this->optionManager->getSubsByMain($mainId);

        return response()->json([
            'status'  => 200,
            'success' => true,
            'data'    => $subs,
        ]);
    }

    /**
     * Dedicated API Endpoint: Get Option details for edit modal.
     */
    public function getOption(int $id, Request $request): JsonResponse
    {
        $optionType = $request->get('option_type');

        if ($optionType === 'classification') {
            $cls = Classification::find($id);
            if ($cls) {
                return response()->json([
                    'status'  => 200,
                    'success' => true,
                    'data'    => [
                        'id'                  => $cls->id,
                        'option_type'         => 'classification',
                        'value'               => $cls->classification_name,
                        'classification_name' => $cls->classification_name,
                        'is_active'           => (bool) $cls->is_active,
                    ],
                    'usage'   => $this->optionManager->checkClassificationUsage($cls),
                ]);
            }
        }

        $option = OfflineTrainingOption::with(['parent', 'classification', 'courseExamples'])->find($id);
        if (!$option) {
            $cls = Classification::find($id);
            if ($cls) {
                return response()->json([
                    'status'  => 200,
                    'success' => true,
                    'data'    => [
                        'id'                  => $cls->id,
                        'option_type'         => 'classification',
                        'value'               => $cls->classification_name,
                        'classification_name' => $cls->classification_name,
                        'is_active'           => (bool) $cls->is_active,
                    ],
                    'usage'   => $this->optionManager->checkClassificationUsage($cls),
                ]);
            }

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

    /**
     * Dedicated Main Classification Management Page (Table: classification).
     * URL: /manage/classification/classification
     */
    public function mainClassificationIndex(Request $request)
    {
        $search = trim((string) ($request->get('q') ?? $request->get('search') ?? ''));
        $status = $request->get('status');
        $perPage = (int) $request->get('entries', 25);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 25;
        }

        $query = Classification::query();

        if ($search !== '') {
            $searchLower = mb_strtolower($search);
            $query->where(function ($q) use ($search, $searchLower) {
                $q->where('classification_name', 'LIKE', "%{$search}%")
                  ->orWhere('slug', 'LIKE', "%{$searchLower}%");
            });
        }

        if ($status !== null && $status !== '' && in_array(strtolower((string) $status), ['0', '1', 'active', 'inactive'], true)) {
            $isActive = ($status === '1' || strtolower((string) $status) === 'active');
            $query->where('is_active', $isActive);
        }

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
            $caseParts[] = "WHEN classification_name = '{$cNameEscaped}' THEN {$idx}";
        }
        $caseSql = "CASE " . implode(' ', $caseParts) . " ELSE 99 END";

        $items = $query->orderByRaw($caseSql)->orderBy('classification_name', 'asc')->get();

        $totalCount = Classification::count();
        $activeCount = Classification::where('is_active', 1)->count();
        $inactiveCount = Classification::where('is_active', 0)->count();

        return view('main.classification.classification', [
            'items'         => $items,
            'totalCount'    => $totalCount,
            'activeCount'   => $activeCount,
            'inactiveCount' => $inactiveCount,
            'perPage'       => $perPage,
        ]);
    }

    /**
     * Store new Main Classification (Table: classification).
     */
    public function storeMainClassification(Request $request)
    {
        $validated = $request->validate([
            'classification_name' => 'required|string|max:255',
            'slug'                => 'nullable|string|max:255',
            'is_active'           => 'nullable|in:0,1',
        ]);

        $name = trim($validated['classification_name']);
        $rawSlug = !empty($validated['slug']) ? trim($validated['slug']) : $name;
        $slug = Str::slug($rawSlug);

        // Check duplicate name
        if (Classification::whereRaw('LOWER(classification_name) = ?', [mb_strtolower($name)])->exists()) {
            throw ValidationException::withMessages([
                'classification_name' => ["Classification '{$name}' already exists."],
            ]);
        }

        // Ensure unique slug
        $baseSlug = $slug;
        $counter = 1;
        while (Classification::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        $classification = Classification::create([
            'classification_name' => $name,
            'slug'                => $slug,
            'is_active'           => isset($validated['is_active']) ? (int) $validated['is_active'] : 1,
        ]);

        $msg = "Main Classification '{$classification->classification_name}' created successfully.";

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status'  => 200,
                'success' => true,
                'message' => $msg,
                'data'    => $classification,
            ]);
        }

        return redirect()->route('manage.classification.main.index')->with('success', $msg);
    }

    /**
     * Get single Main Classification details (for edit modal).
     */
    public function getMainClassification(int $id): JsonResponse
    {
        $classification = Classification::find($id);
        if (!$classification) {
            return response()->json(['status' => 404, 'success' => false, 'message' => 'Classification not found.'], 404);
        }

        $usage = $this->optionManager->checkClassificationUsage($classification);

        return response()->json([
            'status'  => 200,
            'success' => true,
            'data'    => [
                'id'                  => $classification->id,
                'classification_name' => $classification->classification_name,
                'slug'                => $classification->slug,
                'is_active'           => (bool) $classification->is_active,
                'created_at'          => $classification->created_at ? $classification->created_at->format('d M Y, H:i') : '-',
            ],
            'usage'   => $usage,
        ]);
    }

    /**
     * Update Main Classification (Table: classification).
     */
    public function updateMainClassification(Request $request, int $id)
    {
        $classification = Classification::findOrFail($id);

        $validated = $request->validate([
            'classification_name' => 'required|string|max:255',
            'slug'                => 'required|string|max:255',
            'is_active'           => 'nullable|in:0,1',
        ]);

        $newName = trim($validated['classification_name']);
        $newSlug = Str::slug($validated['slug']);

        // Check if name is taken by another record
        $existingName = Classification::whereRaw('LOWER(classification_name) = ?', [mb_strtolower($newName)])
            ->where('id', '!=', $id)
            ->first();
        if ($existingName) {
            throw ValidationException::withMessages([
                'classification_name' => ["Classification '{$newName}' already exists."],
            ]);
        }

        // Check if slug is taken by another record
        $existingSlug = Classification::where('slug', $newSlug)
            ->where('id', '!=', $id)
            ->first();
        if ($existingSlug) {
            throw ValidationException::withMessages([
                'slug' => ["Slug '{$newSlug}' is already in use by another classification."],
            ]);
        }

        $oldName = $classification->classification_name;
        $classification->update([
            'classification_name' => $newName,
            'slug'                => $newSlug,
            'is_active'           => isset($validated['is_active']) ? (int) $validated['is_active'] : 1,
        ]);

        if ($oldName !== $newName) {
            try {
                \App\Models\OfflineTraining::where('main_classification', $oldName)->update(['main_classification' => $newName]);
            } catch (\Throwable $e) {}
        }

        $msg = "Classification '{$classification->classification_name}' updated successfully.";

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status'  => 200,
                'success' => true,
                'message' => $msg,
                'data'    => $classification,
            ]);
        }

        return redirect()->route('manage.classification.main.index')->with('success', $msg);
    }

    /**
     * Check usage of Main Classification before delete.
     */
    public function checkMainClassificationUsage(int $id): JsonResponse
    {
        $classification = Classification::find($id);
        if (!$classification) {
            return response()->json(['status' => 404, 'success' => false, 'message' => 'Classification not found.'], 404);
        }

        $usage = $this->optionManager->checkClassificationUsage($classification);

        return response()->json([
            'status'  => 200,
            'success' => true,
            'data'    => array_merge($usage, [
                'id'                  => $classification->id,
                'classification_name' => $classification->classification_name,
                'can_deactivate'      => (bool) $classification->is_active,
            ]),
        ]);
    }

    /**
     * Destroy Main Classification (Table: classification).
     */
    public function destroyMainClassification(int $id)
    {
        $classification = Classification::find($id);
        if (!$classification) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['status' => 404, 'success' => false, 'message' => 'Classification not found.'], 404);
            }
            return redirect()->route('manage.classification.main.index')->with('error', 'Classification not found.');
        }

        $result = $this->optionManager->deleteClassification($classification);

        if (!$result['success']) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'status'  => 422,
                    'success' => false,
                    'is_used' => true,
                    'message' => $result['message'],
                ], 422);
            }
            return redirect()->route('manage.classification.main.index')->with('error', $result['message']);
        }

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'status'  => 200,
                'success' => true,
                'message' => $result['message'],
            ]);
        }

        return redirect()->route('manage.classification.main.index')->with('success', $result['message']);
    }
}
