<?php

namespace Database\Seeders;

use App\Models\Classification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClassificationSeeder extends Seeder
{
    /**
     * Master data of 13 Main Classifications.
     */
    public const CLASSIFICATIONS = [
        [
            'classification_name' => 'Basic & Common Duties',
            'slug'                => 'basic-common-duties',
            'is_active'           => 1,
        ],
        [
            'classification_name' => 'Compliance',
            'slug'                => 'compliance',
            'is_active'           => 1,
        ],
        [
            'classification_name' => 'Innovation',
            'slug'                => 'innovation',
            'is_active'           => 1,
        ],
        [
            'classification_name' => 'Job Functional',
            'slug'                => 'job-functional',
            'is_active'           => 1,
        ],
        [
            'classification_name' => 'Language',
            'slug'                => 'language',
            'is_active'           => 1,
        ],
        [
            'classification_name' => 'Leadership',
            'slug'                => 'leadership',
            'is_active'           => 1,
        ],
        [
            'classification_name' => 'Assessment/Test',
            'slug'                => 'assessment-test',
            'is_active'           => 1,
        ],
        [
            'classification_name' => 'Basic mindset',
            'slug'                => 'basic-mindset',
            'is_active'           => 1,
        ],
        [
            'classification_name' => 'Maintenance',
            'slug'                => 'maintenance',
            'is_active'           => 1,
        ],
        [
            'classification_name' => 'SHE',
            'slug'                => 'she',
            'is_active'           => 1,
        ],
        [
            'classification_name' => 'ISO Standard',
            'slug'                => 'iso-standard',
            'is_active'           => 1,
        ],
        [
            'classification_name' => 'Technology',
            'slug'                => 'technology',
            'is_active'           => 1,
        ],
        [
            'classification_name' => 'Other',
            'slug'                => 'other',
            'is_active'           => 1,
        ],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $insertedCount = 0;
        $updatedCount = 0;
        $summary = [];

        DB::transaction(function () use (&$insertedCount, &$updatedCount, &$summary) {
            foreach (self::CLASSIFICATIONS as $item) {
                // Pre-align legacy slugs if record already exists by classification_name to avoid duplicates
                $existingByName = Classification::where('classification_name', $item['classification_name'])->first();
                if ($existingByName && $existingByName->slug !== $item['slug']) {
                    $existingByName->slug = $item['slug'];
                    $existingByName->save();
                }

                $wasExisting = Classification::where('slug', $item['slug'])->exists();

                $classification = Classification::updateOrCreate(
                    ['slug' => $item['slug']],
                    [
                        'classification_name' => $item['classification_name'],
                        'is_active'           => $item['is_active'],
                    ]
                );

                if ($wasExisting) {
                    $updatedCount++;
                    $action = 'Updated';
                } else {
                    $insertedCount++;
                    $action = 'Inserted';
                }

                $summary[] = [
                    'ID'                  => $classification->id,
                    'Classification Name' => $classification->classification_name,
                    'Slug'                => $classification->slug,
                    'Status'              => $classification->is_active ? 'Active' : 'Inactive',
                    'Action'              => $action,
                ];
            }
        });

        if ($this->command) {
            $this->command->info(PHP_EOL . '=== Classification Master Data Seeding Summary ===');
            $this->command->table(['ID', 'Classification Name', 'Slug', 'Status', 'Action'], $summary);
            $this->command->info("Total: " . count(self::CLASSIFICATIONS) . " | Inserted: {$insertedCount} | Updated: {$updatedCount}" . PHP_EOL);
        }
    }
}
