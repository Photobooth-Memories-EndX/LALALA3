<?php

namespace App\Exports;

use App\Exports\Sheets\OfflineCourseDataSheet;
use App\Exports\Sheets\OfflineTrainingInstructionsSheet;
use App\Models\OfflineTraining;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class OfflineCourseExport implements WithMultipleSheets, Responsable
{
    use Exportable;

    protected OfflineTraining $training;
    protected array $filters = [];
    protected string $fileName;

    public function __construct(OfflineTraining $training, $filters = [])
    {
        $this->training = $training;
        $this->filters = $filters instanceof Request ? $filters->all() : (array) $filters;

        $courseSlug = Str::slug($training->course_name) ?: 'course';
        $this->fileName = sprintf('non-kp-owl-course-%d-%s-%s.xlsx', $training->id, $courseSlug, now()->format('Y-m-d'));
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function sheets(): array
    {
        return [
            new OfflineCourseDataSheet($this->training, $this->filters),
            new OfflineTrainingInstructionsSheet(),
        ];
    }
}
