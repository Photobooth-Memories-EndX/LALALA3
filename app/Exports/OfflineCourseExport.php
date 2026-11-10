<?php

namespace App\Exports;

use App\Models\OfflineTraining;
use App\Services\Record\RecordOfflineQueryService;
use Illuminate\Support\Str;

class OfflineCourseExport extends OfflineRecordExport
{
    protected OfflineTraining $training;

    public function __construct(OfflineTraining $training, $filters = [], ?string $fileName = null)
    {
        $this->training = $training;

        if ($fileName === null) {
            $courseSlug = Str::slug($training->course_name) ?: 'course';
            $fileName = sprintf('non-kp-owl-course-%d-%s-%s.xlsx', $training->id, $courseSlug, now()->format('Y-m-d'));
        }

        parent::__construct($filters, $fileName);
    }

    public function query()
    {
        return RecordOfflineQueryService::buildCourseQuery((int) $this->training->id, $this->filters)
            ->orderBy('otu.name', 'asc')
            ->orderBy('otu.id', 'asc');
    }
}
