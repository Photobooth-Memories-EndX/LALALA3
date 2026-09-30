<?php

namespace Database\Seeders;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingOption;
use App\Services\Record\OfflineOptionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OfflineTrainingOptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $optionService = app(OfflineOptionService::class);

        // 1. Seed canonical standard vocabulary
        $canonicalOptions = [
            OfflineOptionService::TYPE_TYPE => [
                'In-House',
                'Public',
                'Certification',
                'Workshop',
                'Seminar',
                'Classroom',
                'External',
                'Overseas',
            ],
            OfflineOptionService::TYPE_LEVEL => [
                'Basic',
                'Intermediate',
                'Advanced',
                'All',
            ],
            OfflineOptionService::TYPE_DELIVERY_METHOD => [
                'Classroom',
                'Practical',
                'Hybrid',
                'Workshop',
                'Hands-on',
                'Seminar',
                'E-Learning',
            ],
            OfflineOptionService::TYPE_PROVIDER => [
                'Internal',
                'External',
            ],
            OfflineOptionService::TYPE_SUB_PROVIDER => [
                'Vendor',
                'University',
                'Certified Center',
                'Internal Trainer',
                'Other',
            ],
        ];

        foreach ($canonicalOptions as $type => $values) {
            $order = 10;
            foreach ($values as $val) {
                $optionService->registerOption(
                    type: $type,
                    value: $val,
                    year: null,
                    createdFrom: 'system',
                    createdBy: null,
                    sortOrder: $order
                );
                $order += 10;
            }
        }

        // 2. Backfill historical values from offline_trainings table
        $trainings = OfflineTraining::all();

        foreach ($trainings as $training) {
            $year = $training->corporate_kpi_year;
            if (!$year && $training->start_date) {
                $year = (int) $training->start_date->format('Y');
            }

            $fields = [
                OfflineOptionService::TYPE_COURSE_NAME         => $training->course_name,
                OfflineOptionService::TYPE_SUB_COURSE_NAME     => $training->sub_course_name,
                OfflineOptionService::TYPE_TYPE                => $training->type,
                OfflineOptionService::TYPE_CATEGORY            => $training->category,
                OfflineOptionService::TYPE_LEVEL               => $training->level,
                OfflineOptionService::TYPE_SUB_CLASSIFICATION  => $training->sub_classification,
                OfflineOptionService::TYPE_DELIVERY_METHOD     => $training->delivery_method,
                OfflineOptionService::TYPE_SUB_DELIVERY_METHOD => $training->sub_delivery_method,
                OfflineOptionService::TYPE_PROVIDER            => $training->provider,
                OfflineOptionService::TYPE_SUB_PROVIDER        => $training->sub_provider,
                OfflineOptionService::TYPE_PROVIDER_NAME       => $training->provider_name,
                OfflineOptionService::TYPE_VENUE               => $training->venue,
                OfflineOptionService::TYPE_INSTRUCTOR          => $training->instructor,
            ];

            foreach ($fields as $type => $value) {
                if ($value !== null && is_string($value) && OfflineOptionService::isValidValue($value)) {
                    $optionService->registerOption(
                        type: $type,
                        value: $value,
                        year: null,
                        createdFrom: 'system',
                        createdBy: null
                    );
                }
            }

            // Sub corporate KPI is year-scoped
            if ($training->sub_corporate_kpi && OfflineOptionService::isValidValue($training->sub_corporate_kpi)) {
                $optionService->registerOption(
                    type: OfflineOptionService::TYPE_SUB_CORPORATE_KPI,
                    value: $training->sub_corporate_kpi,
                    year: $year ?: (int) date('Y'),
                    createdFrom: 'system',
                    createdBy: null
                );
            }
        }
    }
}
