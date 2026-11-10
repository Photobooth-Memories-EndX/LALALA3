<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Services\Manage\ClassificationRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ClassificationRecordController extends Controller
{
    protected ClassificationRecordService $classificationService;

    public function __construct(ClassificationRecordService $classificationService)
    {
        $this->classificationService = $classificationService;
    }

    /**
     * Render the Classification Detail page for All KP OWL.
     */
    public function index(Request $request, string $classification)
    {
        $resolved = $this->classificationService->resolveClassification($classification, true);
        $rawName = $resolved['displayName'] ?? (strcasecmp($resolved['name'], 'Audit Isolated Classification') === 0 ? 'Unclassification' : $resolved['name']);
        $displayName = html_entity_decode($rawName, ENT_QUOTES, 'UTF-8');
        $filterOptions = $this->classificationService->getFilterOptions();

        return view('main.record.all.classification', [
            'title'              => "Classification: {$displayName}",
            'subtitle'           => "Combined Online & Offline Learning Records for {$displayName}",
            'classificationName' => $displayName,
            'slug'               => $resolved['slug'],
            'isUnclassified'     => $resolved['is_unclassified'],
            'years'              => $filterOptions['years'] ?? [],
            'departments'        => $filterOptions['departments'] ?? [],
            'companies'          => $filterOptions['companies'] ?? [],
            'entries'            => (int) $request->input('entries', 25),
        ]);
    }

    /**
     * Handle AJAX DataTable requests locked to the given classification.
     */
    public function data(Request $request, string $classification): JsonResponse
    {
        return $this->classificationService->datatable($classification, $request);
    }

    /**
     * Handle single record details request for the Detail View modal.
     */
    public function details(Request $request, string $classification, string $source, $id): JsonResponse
    {
        return $this->classificationService->getRecordDetails($source, $id);
    }

    /**
     * Download Excel export locked to the given classification with all 52 columns.
     */
    public function export(Request $request, string $classification): BinaryFileResponse
    {
        return $this->classificationService->export($classification, $request);
    }
}
