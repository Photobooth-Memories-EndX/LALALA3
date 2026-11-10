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
                $needsUpdate = false;
                $updateData = [];

                if (!$existing->is_active) {
                    $updateData['is_active'] = true;
                    $needsUpdate = true;
                }

                if ($needsUpdate) {
                    $existing->update($updateData);
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
            $this->syncOption(
                OfflineOptionService::TYPE_TYPE,
                $typeVal,
                null,
                $order,
                null,
                'owl'
            );
            $order += 10;
        }
    }

    /**
     * 3. Seed Training Categories into offline_training_options.
     * Source OWL: Playlist, All Content, Content Playlist
     * Source Non KP-OWL: NON KP-OWL
     */
    protected function seedTrainingCategories(): void
    {
        $owlCategories = [
            'Playlist',
            'All Content',
            'Content Playlist',
        ];

        $order = 10;
        foreach ($owlCategories as $catVal) {
            $this->syncOption(
                OfflineOptionService::TYPE_CATEGORY,
                $catVal,
                null,
                $order,
                null,
                'owl'
            );
            $order += 10;
        }

        // NON KP-OWL category
        $this->syncOption(
            OfflineOptionService::TYPE_CATEGORY,
            'NON KP-OWL',
            null,
            $order,
            null,
            'non_kp_owl'
        );
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
                'source'      => 'non_kp_owl',
                'explanation' => 'Face-to-face training conducted offline.',
                'children'    => [
                    'On-site'  => [
                        'source'      => 'non_kp_owl',
                        'explanation' => 'Face-to-face training conducted at the PTKP (class room, meeting room, inside the plant)',
                    ],
                    'Off-site' => [
                        'source'      => 'non_kp_owl',
                        'explanation' => 'Face-to-face training conducted outside the PTKP (hotel, external training center, vendor\'s facility)',
                    ],
                ],
            ],
            'Online' => [
                'source'      => 'all',
                'explanation' => 'Virtual or web-based training sessions.',
                'children'    => [
                    'E-learning' => [
                        'source'      => 'all',
                        'explanation' => 'Self-paced modules accessed online via LMS or platform. No live instructor. Participants complete training independently.',
                    ],
                    'Webinar'    => [
                        'source'      => 'non_kp_owl',
                        'explanation' => 'Synchronous (live) virtual session led by an instructor via platforms like Zoom, Teams, or Webex. Includes interaction like Q&A or polls.',
                    ],
                ],
            ],
            'Blended' => [
                'source'      => 'non_kp_owl',
                'explanation' => 'A structured mix of online and offline methods.',
                'children'    => [
                    'Hybrid' => [
                        'source'      => 'non_kp_owl',
                        'explanation' => 'A structured mix of online and offline methods',
                    ],
                ],
            ],
        ];

        $parentOrder = 10;
        foreach ($deliveryHierarchy as $parentMethod => $meta) {
            $parentOption = $this->syncOption(
                OfflineOptionService::TYPE_DELIVERY_METHOD,
                $parentMethod,
                null,
                $parentOrder,
                null,
                $meta['source'] ?? 'all',
                $meta['explanation'] ?? null
            );
            $parentOrder += 10;

            $subOrder = 10;
            foreach ($meta['children'] as $subMethod => $subMeta) {
                $this->syncOption(
                    OfflineOptionService::TYPE_SUB_DELIVERY_METHOD,
                    $subMethod,
                    $parentOption?->id,
                    $subOrder,
                    null,
                    $subMeta['source'] ?? 'all',
                    $subMeta['explanation'] ?? null
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
                'source'      => 'all',
                'explanation' => 'Internal company training delivery and resources.',
                'children'    => [
                    'Internal-Training Team' => [
                        'source'      => 'all',
                        'explanation' => 'Delivered & organized by Training Team. Location: On-site PTKP/Online/Blended. Content: developed by internal company. Instructor: Internal employees.',
                    ],
                    'Internal-Unit'          => [
                        'source'      => 'non_kp_owl',
                        'explanation' => 'Delivered & organized by each working unit (Not Training Team). Location: On-site PTKP/Online/Blended. Content: developed by internal company. Instructor: Internal employees.',
                    ],
                ],
            ],
            'External' => [
                'source'      => 'non_kp_owl',
                'explanation' => 'External institutions, vendors, and professional partners.',
                'children'    => [
                    'Institution' => [
                        'source'      => 'non_kp_owl',
                        'explanation' => 'Organized by Training Team. Content: developed by external. Instructor: delivered by external (training companies, universities, certification bodies, consulting firms, etc). Location: On-site/Off-site PTKP/Online/Blended.',
                    ],
                    'Individual'  => [
                        'source'      => 'non_kp_owl',
                        'explanation' => 'Organized by Training Team. Content: developed by external. Instructor: delivered by external (Freelance trainers or consultant hired personally for their expertise). Location: On-site/Off-site PTKP/Online/Blended.',
                    ],
                ],
            ],
        ];

        $parentOrder = 10;
        foreach ($providerHierarchy as $parentProvider => $meta) {
            $parentOption = $this->syncOption(
                OfflineOptionService::TYPE_PROVIDER,
                $parentProvider,
                null,
                $parentOrder,
                null,
                $meta['source'] ?? 'all',
                $meta['explanation'] ?? null
            );
            $parentOrder += 10;

            $subOrder = 10;
            foreach ($meta['children'] as $subProvider => $subMeta) {
                $this->syncOption(
                    OfflineOptionService::TYPE_SUB_PROVIDER,
                    $subProvider,
                    $parentOption?->id,
                    $subOrder,
                    null,
                    $subMeta['source'] ?? 'all',
                    $subMeta['explanation'] ?? null
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
        // First, check and rename legacy "All recertification training" to "All recertification" per Excel reference
        $legacyRecert = OfflineTrainingOption::where('option_type', OfflineOptionService::TYPE_COURSE_NAME)
            ->where('normalized_value', 'all recertification training')
            ->first();
        if ($legacyRecert) {
            $legacyRecert->update([
                'value'            => 'All recertification',
                'normalized_value' => 'all recertification',
            ]);
        }

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
                    'All recertification',
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
            $mainClassification = Classification::whereRaw('LOWER(classification_name) = ?', [mb_strtolower(trim($mainName))])->first();
            $mainClassificationId = $mainClassification?->id;

            foreach ($subClassifications as $subName => $courses) {
                // Register Sub Classification with classification_id linked to Main Classification!
                $subOption = $this->syncOption(
                    OfflineOptionService::TYPE_SUB_CLASSIFICATION,
                    $subName,
                    null,
                    $subOrder,
                    $mainClassificationId,
                    'all'
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
                        $courseOrder,
                        $mainClassificationId,
                        'all'
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
        int $sortOrder = 0,
        ?int $classificationId = null,
        ?string $source = 'all',
        ?string $explanation = null
    ): ?OfflineTrainingOption {
        if (!OfflineOptionService::isValidValue($value)) {
            return null;
        }

        $cleanValue = trim(preg_replace('/\s+/', ' ', $value));
        $normalized = OfflineTrainingOption::normalizeString($cleanValue);

        // First check if an exact record exists under the given parent_id
        $existing = null;
        if ($parentId !== null) {
            $existing = OfflineTrainingOption::where('option_type', $type)
                ->where('parent_id', $parentId)
                ->where('normalized_value', $normalized)
                ->first();
        }

        // If not found by parent_id, check globally
        if (!$existing) {
            $existing = OfflineTrainingOption::where('option_type', $type)
                ->where('scope_key', 'global')
                ->where('normalized_value', $normalized)
                ->first();
        }

        // If course example already exists under a DIFFERENT parent, create a scoped entry so both parents have the course!
        if ($existing && $parentId !== null && !empty($existing->parent_id) && (int) $existing->parent_id !== (int) $parentId) {
            $scopedKey = 'parent:' . $parentId;
            $existingScoped = OfflineTrainingOption::where('option_type', $type)
                ->where('scope_key', $scopedKey)
                ->where('normalized_value', $normalized)
                ->first();

            if (!$existingScoped) {
                $created = OfflineTrainingOption::create([
                    'option_type'       => $type,
                    'value'             => $cleanValue,
                    'explanation'       => $explanation,
                    'normalized_value'  => $normalized,
                    'year'              => null,
                    'scope_key'         => $scopedKey,
                    'source'            => $source ?: 'all',
                    'parent_id'         => $parentId,
                    'classification_id' => $classificationId,
                    'is_active'         => true,
                    'sort_order'        => $sortOrder,
                    'created_from'      => 'system',
                    'created_by'        => null,
                ]);

                if (isset($this->stats[$type])) {
                    $this->stats[$type]['inserted']++;
                }

                return $created;
            }

            $existing = $existingScoped;
        }

        if (!$existing) {
            $created = OfflineTrainingOption::create([
                'option_type'       => $type,
                'value'             => $cleanValue,
                'explanation'       => $explanation,
                'normalized_value'  => $normalized,
                'year'              => null,
                'scope_key'         => 'global',
                'source'            => $source ?: 'all',
                'parent_id'         => $parentId,
                'classification_id' => $classificationId,
                'is_active'         => true,
                'sort_order'        => $sortOrder,
                'created_from'      => 'system',
                'created_by'        => null,
            ]);

            if (isset($this->stats[$type])) {
                $this->stats[$type]['inserted']++;
            }

            return $created;
        }

        // Check if existing record needs update
        $needsUpdate = false;
        $updateData = [];

        if ($parentId !== null && empty($existing->parent_id)) {
            $updateData['parent_id'] = $parentId;
            $needsUpdate = true;
        }

        if ($classificationId !== null && (empty($existing->classification_id) || (int) $existing->classification_id !== (int) $classificationId)) {
            $updateData['classification_id'] = $classificationId;
            $needsUpdate = true;
        }

        if ($source !== null && $existing->source !== $source) {
            $updateData['source'] = $source;
            $needsUpdate = true;
        }

        if ($explanation !== null && $existing->explanation !== $explanation) {
            $updateData['explanation'] = $explanation;
            $needsUpdate = true;
        }

        if (!$existing->is_active) {
            $updateData['is_active'] = true;
            $needsUpdate = true;
        }

        // Ensure value maintains exact casing if normalized matches
        if ($existing->value !== $cleanValue) {
            $updateData['value'] = $cleanValue;
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
