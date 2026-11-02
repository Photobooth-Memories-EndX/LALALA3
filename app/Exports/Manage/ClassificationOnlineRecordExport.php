<?php

namespace App\Exports\Manage;

use App\Exports\OnlineRecordExport;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ClassificationOnlineRecordExport extends OnlineRecordExport
{
    protected ?string $classification;

    public function __construct(?string $classification = null, $filters = [], ?string $fileName = null)
    {
        $this->classification = !empty($classification) ? (string) $classification : null;
        $filterArr = $filters instanceof Request ? $filters->all() : (array) $filters;

        if ($this->classification !== null) {
            $filterArr['classification'] = $this->classification;
            $filterArr['classification_slug'] = $this->classification;
        }

        parent::__construct($filterArr, $fileName);
    }

    public function getClassification(): ?string
    {
        return $this->classification;
    }

    /**
     * Backward-compatible collection accessor for test suites or legacy callers.
     */
    public function collection(): Collection
    {
        return $this->query()->get()->map(fn($row) => $this->map($row));
    }
}
