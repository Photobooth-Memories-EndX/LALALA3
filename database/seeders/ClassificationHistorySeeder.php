<?php

namespace Database\Seeders;

use App\Models\Classification;
use App\Models\OfflineTrainingOption;
use App\Services\Record\OfflineOptionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClassificationHistorySeeder extends Seeder
{
    /**
     * Statistics tracking for execution reporting.
     */
    protected array $stats = [
        'classification'      => ['inserted' => 0, 'updated' => 0, 'skipped' => 0],
        'type'                => ['inserted' => 0, 'updated' => 0, 'skipped' => 0],
        'category'            => ['inserted' => 0, 'updated' => 0, 'skipped' => 0],
        'delivery_method'     => ['inserted' => 0, 'updated' => 0, 'skipped' => 0],
        'sub_delivery_method' => ['inserted' => 0, 'updated' => 0, 'skipped' => 0],
        'provider'            => ['inserted' => 0, 'updated' => 0, 'skipped' => 0],
        'sub_provider'        => ['inserted' => 0, 'updated' => 0, 'skipped' => 0],
        'sub_classification'  => ['inserted' => 0, 'updated' => 0, 'skipped' => 0],
        'course_name'         => ['inserted' => 0, 'updated' => 0, 'skipped' => 0],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // 1. Seed Main Classifications (table: classification)
            $this->seedMainClassifications();

            // 2. Seed Training Types (table: offline_training_options, option_type: type)
            $this->seedTrainingTypes();

            // 3. Seed Training Categories (table: offline_training_options, option_type: category)
            $this->seedTrainingCategories();

            // 4. Seed Delivery Methods & Sub Delivery Methods (table: offline_training_options)
            $this->seedDeliveryMethods();

            // 5. Seed Training Providers & Sub Providers (table: offline_training_options)
            $this->seedTrainingProviders();

            // 6. Seed Classification Hierarchy: Sub Classifications & Course Examples
            $this->seedClassificationHierarchy();
        });

        $this->printSummary();
    }

    /**
     * 1. Seed Main Classifications into classification table.
     */
    protected function seedMainClassifications(): void
    {
        $mainClassifications = [
            'Basic & Common Duties',
            'Compliance',
            'Innovation',
            'Job Functional',
            'Language',
            'Leadership',
            'Assessment/Test',
        ];

        foreach ($mainClassifications as $name) {
            $trimmed = trim($name);
            $normalized = mb_strtolower($trimmed);

            $existing = Classification::whereRaw('LOWER(classification_name) = ?', [$normalized])->first();

            if (!$existing) {
                Classification::create([
                    'classification_name' => $trimmed,
                    'is_active'           => true,
                ]);
                $this->stats['classification']['inserted']++;
            } else {
                if (!$existing->is_active) {
                    $existing->update(['is_active' => true]);
                    $this->stats['classification']['updated']++;
                } else {
                    $this->stats['classification']['skipped']++;
                }
            }
        }
    }

    /**
     * 2. Seed Training Types into offline_training_options.
     * Source OWL: Document, Video, MOOC, Playlist
     * Source Non KP-OWL: '-' (ignored)
     */
    protected function seedTrainingTypes(): void
    {
        $types = [
            'Document',
            'Video',
            'MOOC',
            'Playlist',
        ];

        $order = 10;
        foreach ($types as $typeVal) {
            $this->syncOption(OfflineOptionService::TYPE_TYPE, $typeVal, null, $order);
            $order += 10;
        }
    }

    /**
     * 3. Seed Training Categories into offline_training_options.
     * Source OWL: Playlist, All Content, Content Playlist
     * Source Non KP-OWL: Non KP-OWL
     */
    protected function seedTrainingCategories(): void
    {
        $categories = [
            'Playlist',
            'All Content',
            'Content Playlist',
            'Non KP-OWL',
        ];

        $order = 10;
        foreach ($categories as $catVal) {
            $this->syncOption(OfflineOptionService::TYPE_CATEGORY, $catVal, null, $order);
            $order += 10;
        }
    }

    /**
     * 4. Seed Delivery Methods and Sub Delivery Methods.
     * Source NON KP-OWL:
     * - Offline: On-site, Off-site
     * - Online: E-learning, Webinar
     * - Blended: Hybrid
     * Source OWL:
     * - Online: E-learning
     */
    protected function seedDeliveryMethods(): void
    {
        $deliveryHierarchy = [
            'Offline' => [
                'On-site',
                'Off-site',
            ],
            'Online' => [
                'E-learning',
                'Webinar',
            ],
            'Blended' => [
                'Hybrid',
            ],
        ];

        $parentOrder = 10;
        foreach ($deliveryHierarchy as $parentMethod => $subMethods) {
            $parentOption = $this->syncOption(
                OfflineOptionService::TYPE_DELIVERY_METHOD,
                $parentMethod,
                null,
                $parentOrder
            );
            $parentOrder += 10;

            $subOrder = 10;
            foreach ($subMethods as $subMethod) {
                $this->syncOption(
                    OfflineOptionService::TYPE_SUB_DELIVERY_METHOD,
                    $subMethod,
                    $parentOption?->id,
                    $subOrder
                );
                $subOrder += 10;
            }
        }
    }

    /**
     * 5. Seed Training Providers and Sub Providers.
     * Source NON KP-OWL:
     * - Internal: Internal-Training Team, Internal-Unit
     * - External: Institution, Individual
     * Source OWL:
     * - Internal: Internal-Training Team
     */
    protected function seedTrainingProviders(): void
    {
        $providerHierarchy = [
            'Internal' => [
                'Internal-Training Team',
                'Internal-Unit',
            ],
            'External' => [
                'Institution',
                'Individual',
            ],
        ];

        $parentOrder = 10;
        foreach ($providerHierarchy as $parentProvider => $subProviders) {
            $parentOption = $this->syncOption(
                OfflineOptionService::TYPE_PROVIDER,
                $parentProvider,
                null,
                $parentOrder
            );
            $parentOrder += 10;

            $subOrder = 10;
            foreach ($subProviders as $subProvider) {
                $this->syncOption(
                    OfflineOptionService::TYPE_SUB_PROVIDER,
                    $subProvider,
                    $parentOption?->id,
                    $subOrder
                );
                $subOrder += 10;
            }
        }
    }

    /**
     * 6. Seed Sub Classifications and Course Examples.
     */
    protected function seedClassificationHierarchy(): void
    {
        $hierarchy = [
            'Basic & Common Duties' => [
                'Production' => [
                    'Basic Python',
                    'Statistical Process Control',
                    'Production Knowledge',
                    'Robotic (RPA)',
                ],
                'QHSE' => [
                    'PTKP 3S',
                    'First Aid',
                    'Fire Fighting',
                ],
                'Sales & Marketing' => [
                    'Sales Communication',
                    'Marketing',
                ],
                'Business & Management' => [
                    'PTKP Global Assessment',
                    'Business Writing',
                ],
                'Management Philosophy' => [
                    'Ethics',
                    'ESG',
                    'PTKP Way',
                    'Corporate Citizenship',
                ],
                'General Skill' => [
                    'Basic Train of Trainer',
                    'Ms. Office',
                ],
            ],
            'Compliance' => [
                'Certification' => [
                    'Operator Overhead Crane',
                    'Confined Space Officer',
                    'Fire Safety D',
                    'POPAL',
                    'POIPPU',
                    'OPLB3',
                    'Internal Auditor',
                    'Energy Auditor',
                    'Refractory',
                    'SIM',
                    'Garda Pratama',
                ],
                'Recertification' => [
                    'All recertification training',
                ],
            ],
            'Innovation' => [
                'Quick Six Sigma' => [
                    'QSS for Group Leader',
                    'QSS Off Job',
                    'QSS Office',
                ],
                '1% Better Project' => [],
                'Others' => [
                    'Design Thinking',
                ],
            ],
            'Job Functional' => [
                'Technology-General' => [
                    'Basic Facility Management',
                    'Junior Master',
                    'Phyton',
                ],
                'Technology-Mechanical' => [
                    'Welding',
                    'Lubrication',
                ],
                'Technology-Electrical/Instrument' => [
                    'ACS Inverter',
                    'PLC Siemens',
                ],
                'Non-Technology' => [
                    'ICOfR',
                    'Global Job School Finance',
                    'HR Remuneration',
                    'ESG Implementation',
                    'Legal',
                ],
            ],
            'Language' => [
                'Korean' => [
                    'Basic A1',
                    'Basic A2',
                    'Intermediate B1',
                    'Intermediate B2',
                    'TOPIK I Preparation',
                    'TOPIK II Preparation',
                ],
                'Indonesian' => [
                    'Basic A1',
                    'Intermediate B1',
                    'Intermediate B2',
                ],
                'English' => [
                    'English Conversation',
                ],
            ],
            'Leadership' => [
                'Equipping' => [
                    'New Employee Orientation',
                    'New Promoted Leadership',
                    'Global New Leader',
                ],
                'Fostering' => [
                    'GLA',
                    'KPIUM',
                    'Current Expatriate Leadership',
                    'GMP',
                    'P.A.L',
                    'Scholarship',
                ],
            ],
            'Assessment/Test' => [
                'Language' => [
                    'Indonesian (OPI)',
                    'Korean (TOPIK)',
                    'English (TOEIC)',
                ],
                'Global Assessment' => [
                    'Global Assessment',
                ],
            ],
        ];

        $subOrder = 10;
        $courseOrder = 10;

        foreach ($hierarchy as $mainName => $subClassifications) {
            foreach ($subClassifications as $subName => $courses) {
                // Register Sub Classification
                $subOption = $this->syncOption(
                    OfflineOptionService::TYPE_SUB_CLASSIFICATION,
                    $subName,
                    null,
                    $subOrder
                );
                $subOrder += 10;

                // Register Course Examples
                foreach ($courses as $courseName) {
                    if (!OfflineOptionService::isValidValue($courseName)) {
                        continue;
                    }

                    $this->syncOption(
                        OfflineOptionService::TYPE_COURSE_NAME,
                        $courseName,
                        $subOption?->id,
                        $courseOrder
                    );
                    $courseOrder += 10;
                }
            }
        }
    }

    /**
     * Idempotently sync an option into offline_training_options.
     */
    protected function syncOption(
        string $type,
        string $value,
        ?int $parentId = null,
        int $sortOrder = 0
    ): ?OfflineTrainingOption {
        if (!OfflineOptionService::isValidValue($value)) {
            return null;
        }

        $cleanValue = trim(preg_replace('/\s+/', ' ', $value));
        $normalized = OfflineTrainingOption::normalizeString($cleanValue);

        $existing = OfflineTrainingOption::where('option_type', $type)
            ->where('scope_key', 'global')
            ->where('normalized_value', $normalized)
            ->first();

        if (!$existing) {
            $created = OfflineTrainingOption::create([
                'option_type'      => $type,
                'value'            => $cleanValue,
                'normalized_value' => $normalized,
                'year'             => null,
                'scope_key'        => 'global',
                'parent_id'        => $parentId,
                'is_active'        => true,
                'sort_order'       => $sortOrder,
                'created_from'     => 'system',
                'created_by'       => null,
            ]);

            if (isset($this->stats[$type])) {
                $this->stats[$type]['inserted']++;
            }

            return $created;
        }

        // Check if existing record needs update (e.g. parent_id link or active state)
        $needsUpdate = false;
        $updateData = [];

        if ($parentId !== null && empty($existing->parent_id)) {
            $updateData['parent_id'] = $parentId;
            $needsUpdate = true;
        }

        if (!$existing->is_active) {
            $updateData['is_active'] = true;
            $needsUpdate = true;
        }

        if ($needsUpdate) {
            $existing->update($updateData);
            if (isset($this->stats[$type])) {
                $this->stats[$type]['updated']++;
            }
        } else {
            if (isset($this->stats[$type])) {
                $this->stats[$type]['skipped']++;
            }
        }

        return $existing;
    }

    /**
     * Output execution statistics table to console.
     */
    protected function printSummary(): void
    {
        if (!$this->command) {
            return;
        }

        $rows = [];
        $totalInserted = 0;
        $totalUpdated = 0;
        $totalSkipped = 0;

        foreach ($this->stats as $entity => $counts) {
            $totalInserted += $counts['inserted'];
            $totalUpdated += $counts['updated'];
            $totalSkipped += $counts['skipped'];

            $rows[] = [
                ucwords(str_replace('_', ' ', $entity)),
                $counts['inserted'],
                $counts['updated'],
                $counts['skipped'],
                $counts['inserted'] + $counts['updated'] + $counts['skipped'],
            ];
        }

        $rows[] = [
            '<fg=cyan;options=bold>TOTAL</>',
            "<fg=green;options=bold>{$totalInserted}</>",
            "<fg=yellow;options=bold>{$totalUpdated}</>",
            "<fg=blue;options=bold>{$totalSkipped}</>",
            "<fg=white;options=bold>" . ($totalInserted + $totalUpdated + $totalSkipped) . "</>",
        ];

        $this->command->newLine();
        $this->command->info('=== Classification History Seeding Summary ===');
        $this->command->table(['Entity / Type', 'Inserted', 'Updated', 'Skipped', 'Total Processed'], $rows);
        $this->command->newLine();
    }
}
