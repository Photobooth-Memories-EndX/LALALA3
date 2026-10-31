<?php

namespace App\Services\Manage;

use App\Http\Controllers\Manage\Record\RecordAllController;
use App\Services\Record\RecordAllCombinedService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

class RecordSharedQueryService
{
    protected RecordAllCombinedService $combinedService;

    public function __construct(RecordAllCombinedService $combinedService)
    {
        $this->combinedService = $combinedService;
    }

    /**
     * Build unified combined dataset query across KP OWL (Content, Playlist) and Non KP OWL (Offline).
     *
     * @param array $filters
     * @return Builder
     */
    public function buildCombinedQuery(array $filters = []): Builder
    {
        return $this->combinedService->buildCombinedQuery($filters);
    }

    /**
     * Build combined dataset query strictly filtered by a classification.
     *
     * @param string $classification
     * @param array $filters
     * @return Builder
     */
    public function buildClassificationQuery(string $classification, array $filters = []): Builder
    {
        $resolved = $this->resolveClassification($classification, false);
        $filters['classification'] = $resolved['is_unclassified'] ? 'Unclassified' : $resolved['name'];

        return $this->buildCombinedQuery($filters);
    }

    /**
     * Resolve classification input into canonical name, slug, and unclassified flag.
     *
     * @param string $input
     * @param bool $abortIfNotFound
     * @return array{name: string, slug: string, is_unclassified: bool}
     */
    public function resolveClassification(string $input, bool $abortIfNotFound = true): array
    {
        return RecordAllController::resolveClassification($input, $abortIfNotFound);
    }

    /**
     * Get KPI summary across online and offline records.
     *
     * @return array
     */
    public function getSummary(): array
    {
        return $this->combinedService->getSummary();
    }

    /**
     * Get classification summary list.
     *
     * @return Collection
     */
    public function getClassificationSummary(): Collection
    {
        return $this->combinedService->getClassificationSummary();
    }

    /**
     * Get dropdown filter options (years, departments, companies, classifications).
     *
     * @return array
     */
    public function getFilterOptions(): array
    {
        return $this->combinedService->getFilterOptions();
    }
}
