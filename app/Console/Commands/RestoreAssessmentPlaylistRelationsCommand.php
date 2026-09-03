<?php

namespace App\Console\Commands;

use App\Models\Assesment;
use App\Models\AssesmentQuestion;
use App\Models\AssesmentQuestionBank;
use App\Models\Content;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Services\AssessmentDuplicateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class RestoreAssessmentPlaylistRelationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assessment:restore-playlist-relations
                            {--dry-run : Perform validation and audit simulation without modifying database (read-only)}
                            {--execute : Perform duplication of verified 17 assessments from content to playlist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Duplicate content assessments to playlist assessments for historical relations (supports --dry-run and --execute)';

    /**
     * Explicit verified mapping for the 17 historical target items.
     */
    public const TARGET_MAPPING = [
        // Playlist 1: PTKP Health E-Learning 2026 (trainingplan_id: 32)
        [
            'no' => 1,
            'playlist_name' => 'PTKP Health E-Learning 2026',
            'trainingplan_id' => 32,
            'content_id' => 280,
            'content_title' => 'First Aid Training: CPR dan Penggunaan AED (Part 2)',
            'assessment_id' => 326,
            'assessment_title' => '[Health E-Learning 2026] CPR and AED Post-test',
        ],
        [
            'no' => 2,
            'playlist_name' => 'PTKP Health E-Learning 2026',
            'trainingplan_id' => 32,
            'content_id' => 282,
            'content_title' => 'Penanggulangan TBC di Tempat Kerja (Part 2)',
            'assessment_id' => 325,
            'assessment_title' => '[Health E-Learning 2026] TBC Post-test',
        ],
        [
            'no' => 3,
            'playlist_name' => 'PTKP Health E-Learning 2026',
            'trainingplan_id' => 32,
            'content_id' => 284,
            'content_title' => 'Penanggulangan HIV/AIDS di Tempat Kerja (Part 2)',
            'assessment_id' => 324,
            'assessment_title' => '[Health E-Learning 2026] HIV/AIDS Post-test',
        ],

        // Playlist 2: Safety Mandatory E-Learning 2025 [Series #2] (trainingplan_id: 25)
        [
            'no' => 4,
            'playlist_name' => 'Safety Mandatory E-Learning 2025 [Series #2]',
            'trainingplan_id' => 25,
            'content_id' => 155,
            'content_title' => '[Video] Isolation Locking System (ILS) Operation & Non-Operation',
            'assessment_id' => 260,
            'assessment_title' => '[Safety series 2] Isolation Locking System',
        ],
        [
            'no' => 5,
            'playlist_name' => 'Safety Mandatory E-Learning 2025 [Series #2]',
            'trainingplan_id' => 25,
            'content_id' => 157,
            'content_title' => '[Video] Working At Height (Bekerja Di Ketinggian)',
            'assessment_id' => 262,
            'assessment_title' => '[Safety series 2] Working At Height',
        ],
        [
            'no' => 6,
            'playlist_name' => 'Safety Mandatory E-Learning 2025 [Series #2]',
            'trainingplan_id' => 25,
            'content_id' => 158,
            'content_title' => '[Video] Electrical Safety',
            'assessment_id' => 263,
            'assessment_title' => '[Safety series 2] Electrical Safety',
        ],
        [
            'no' => 7,
            'playlist_name' => 'Safety Mandatory E-Learning 2025 [Series #2]',
            'trainingplan_id' => 25,
            'content_id' => 159,
            'content_title' => '[Video] Lifting & Rigging',
            'assessment_id' => 264,
            'assessment_title' => '[Safety series 2] Lifting & Rigging',
        ],
        [
            'no' => 8,
            'playlist_name' => 'Safety Mandatory E-Learning 2025 [Series #2]',
            'trainingplan_id' => 25,
            'content_id' => 160,
            'content_title' => '[Video] Natural Disaster & Emergency Responses',
            'assessment_id' => 265,
            'assessment_title' => '[Safety series 2] Natural Disaster & Emergency Responses',
        ],
        [
            'no' => 9,
            'playlist_name' => 'Safety Mandatory E-Learning 2025 [Series #2]',
            'trainingplan_id' => 25,
            'content_id' => 161,
            'content_title' => '[Video] Industrial Hygiene',
            'assessment_id' => 266,
            'assessment_title' => '[Safety series 2] Industrial Hygiene',
        ],

        // Playlist 3: Safety Mandatory E-Learning 2025 [Series #1] (trainingplan_id: 18)
        [
            'no' => 10,
            'playlist_name' => 'Safety Mandatory E-Learning 2025 [Series #1]',
            'trainingplan_id' => 18,
            'content_id' => 123,
            'content_title' => '3 Major Accident',
            'assessment_id' => 177,
            'assessment_title' => '[Safety Series #1] Post-Test Chapter 1 : 3 Major Accident',
        ],
        [
            'no' => 11,
            'playlist_name' => 'Safety Mandatory E-Learning 2025 [Series #1]',
            'trainingplan_id' => 18,
            'content_id' => 124,
            'content_title' => '10 Safety Iron Rules & 7 Global Safety Model',
            'assessment_id' => 178,
            'assessment_title' => '[Safety Series #1] Post-Test Chapter 2 : 10 SIR and 7 Global Safety Model',
        ],
        [
            'no' => 12,
            'playlist_name' => 'Safety Mandatory E-Learning 2025 [Series #1]',
            'trainingplan_id' => 18,
            'content_id' => 125,
            'content_title' => 'Hazard Identification',
            'assessment_id' => 179,
            'assessment_title' => '[Safety Series #1] Post-Test Chapter 3 : Hazard Identification',
        ],
        [
            'no' => 13,
            'playlist_name' => 'Safety Mandatory E-Learning 2025 [Series #1]',
            'trainingplan_id' => 18,
            'content_id' => 126,
            'content_title' => 'Hierarchy of Hazard Control',
            'assessment_id' => 180,
            'assessment_title' => '[Safety Series #1] Post-Test Chapter 4 : Hierarchy of Hazard Control',
        ],
        [
            'no' => 14,
            'playlist_name' => 'Safety Mandatory E-Learning 2025 [Series #1]',
            'trainingplan_id' => 18,
            'content_id' => 127,
            'content_title' => 'Tool Box Meeting',
            'assessment_id' => 181,
            'assessment_title' => '[Safety Series #1] Post-Test Chapter 5 : Tool Box Meeting',
        ],
        [
            'no' => 15,
            'playlist_name' => 'Safety Mandatory E-Learning 2025 [Series #1]',
            'trainingplan_id' => 18,
            'content_id' => 128,
            'content_title' => 'Personal Protective Equipment',
            'assessment_id' => 182,
            'assessment_title' => '[Safety Series #1] Post-Test Chapter 6 : Personal Protective Equipment',
        ],
        [
            'no' => 16,
            'playlist_name' => 'Safety Mandatory E-Learning 2025 [Series #1]',
            'trainingplan_id' => 18,
            'content_id' => 129,
            'content_title' => 'Good Driver',
            'assessment_id' => 183,
            'assessment_title' => '[Safety Series #1] Post-Test Chapter 7 : Good Driver',
        ],
        [
            'no' => 17,
            'playlist_name' => 'Safety Mandatory E-Learning 2025 [Series #1]',
            'trainingplan_id' => 18,
            'content_id' => 130,
            'content_title' => 'Fire Prevention and Protection System',
            'assessment_id' => 184,
            'assessment_title' => '[Safety Series #1] Post-Test Chapter 8 : Fire Prevention and Protection System',
        ],
    ];

    /**
     * Execute the console command.
     */
    public function handle(AssessmentDuplicateService $duplicateService): int
    {
        $isExecute = (bool) $this->option('execute');
        $isDryRun  = (bool) $this->option('dry-run');

        // Safe default: if --execute is not specified, run in safe dry-run mode
        if (!$isExecute) {
            $isDryRun = true;
        }

        $this->line('==================================================');
        $this->line('ASSESSMENT CONTENT → PLAYLIST DUPLICATION');
        if ($isExecute) {
            $this->line('MODE: EXECUTE');
        } else {
            $this->line('MODE: DRY RUN (READ-ONLY)');
        }
        $this->line('==================================================');
        $this->newLine();

        $stats = [
            'total'                     => count(self::TARGET_MAPPING),
            'source_assessment_found'   => 0,
            'source_content_found'      => 0,
            'playlist_found'            => 0,
            'trainingplan_detail_found' => 0,
            'ready_for_duplication'     => 0,
            'duplicated'                => 0,
            'already_exists'            => 0,
            'questions_duplicated'      => 0,
            'child_records_duplicated'  => 0,
            'not_found'                 => 0,
            'mismatch'                  => 0,
            'conflict'                  => 0,
            'database_modified'         => 0,
        ];

        // Step 1: Pre-validation & audit of all items (Pure Read-Only SELECT)
        $validatedItems = [];
        $hasFatalValidationIssue = false;

        foreach (self::TARGET_MAPPING as $item) {
            $no   = $item['no'];
            $tpId = $item['trainingplan_id'];
            $cId  = $item['content_id'];
            $aId  = $item['assessment_id'];

            // Query models
            $assessment = Assesment::find($aId);
            $content    = Content::find($cId);
            $tp         = TrainingPlan::find($tpId);
            $tpd        = TrainingPlanDetail::where('trainingplan_id', $tpId)
                ->where('content_id', $cId)
                ->first();

            $assessmentExists = $assessment !== null;
            $contentExists    = $content !== null;
            $playlistExists   = $tp !== null;
            $pivotExists      = $tpd !== null;

            if ($assessmentExists) {
                $stats['source_assessment_found']++;
            }
            if ($contentExists) {
                $stats['source_content_found']++;
            }
            if ($playlistExists) {
                $stats['playlist_found']++;
            }
            if ($pivotExists) {
                $stats['trainingplan_detail_found']++;
            }

            $currentContentFor = $assessment ? ($assessment->assasment_content_for ?? 'NULL') : 'NOT FOUND';
            $currentTpId       = $assessment ? ($assessment->trainingplan_id ?? 'NULL') : 'NOT FOUND';

            // Audit questions, question banks, and options
            $questionsCount = 0;
            $optionsCount   = 0;
            $qbCount        = 0;

            if ($assessmentExists) {
                $questionsCount = $assessment->questions()->count();
                $qbCount        = $assessment->assessment_question_banks()->count();
                foreach ($assessment->questions as $q) {
                    if (is_array($q->option)) {
                        $optionsCount += count($q->option);
                    }
                }
            }

            // Check if playlist assessment already exists for this (trainingplan_id, content_id)
            $existingPlaylistAssessment = Assesment::where('assasment_content_for', 'playlist')
                ->where('trainingplan_id', $tpId)
                ->where('content_id', $cId)
                ->first();

            // Validate status
            $itemStatus        = '';
            $statusDescription = '';
            $duplicateRequired = false;

            if (!$assessmentExists || !$contentExists || !$playlistExists) {
                $stats['not_found']++;
                $itemStatus = 'not_found';
                $statusDescription = 'CONFLICT / SKIPPED: Required record (Assessment, Content, or Playlist) not found';
                $hasFatalValidationIssue = true;
            } elseif ((int) $assessment->content_id !== (int) $cId) {
                $stats['mismatch']++;
                $itemStatus = 'mismatch';
                $statusDescription = "CONFLICT / SKIPPED: Content mismatch (Assessment content_id: {$assessment->content_id} != {$cId})";
                $hasFatalValidationIssue = true;
            } elseif (!$pivotExists) {
                $stats['mismatch']++;
                $itemStatus = 'mismatch';
                $statusDescription = 'CONFLICT / SKIPPED: TrainingPlanDetail not found in target playlist';
                $hasFatalValidationIssue = true;
            } elseif ($assessment->assasment_content_for === 'playlist') {
                $stats['conflict']++;
                $itemStatus = 'conflict';
                $statusDescription = "CONFLICT / SKIPPED: Source Assessment #{$aId} already has assasment_content_for = 'playlist'";
                $hasFatalValidationIssue = true;
            } elseif ($existingPlaylistAssessment !== null) {
                $stats['already_exists']++;
                $itemStatus = 'already_exists';
                $statusDescription = "ALREADY EXISTS (Playlist Assessment ID: {$existingPlaylistAssessment->id})";
                $duplicateRequired = false;
            } else {
                $stats['ready_for_duplication']++;
                $itemStatus = 'ready_for_duplication';
                $statusDescription = 'READY FOR DUPLICATION';
                $duplicateRequired = true;
            }

            $validatedItems[] = [
                'no'                  => $no,
                'aId'                 => $aId,
                'cId'                 => $cId,
                'tpId'                => $tpId,
                'assessment'          => $assessment,
                'content'             => $content,
                'tp'                  => $tp,
                'current_content_for' => $currentContentFor,
                'current_tp_id'       => $currentTpId,
                'pivot_exists'        => $pivotExists,
                'existing_playlist'   => $existingPlaylistAssessment,
                'duplicate_required'  => $duplicateRequired,
                'questions_count'     => $questionsCount,
                'options_count'       => $optionsCount,
                'qb_count'            => $qbCount,
                'status'              => $itemStatus,
                'status_description'  => $statusDescription,
                'mapping'             => $item,
                'new_assessment'      => null,
            ];
        }

        // Step 2: If in Execute mode, perform atomic duplication wrapped in a single DB transaction
        if ($isExecute) {
            if ($hasFatalValidationIssue) {
                $this->error('Execution aborted: One or more validation checks failed (Not Found, Mismatch, or Conflict). No records were modified.');
                $this->displayItems($validatedItems);
                $this->displaySummary($stats, $isExecute);
                return self::FAILURE;
            }

            try {
                DB::transaction(function () use (&$validatedItems, &$stats, $duplicateService) {
                    foreach ($validatedItems as &$valItem) {
                        if ($valItem['status'] !== 'ready_for_duplication') {
                            continue;
                        }

                        $sourceAssessment = Assesment::lockForUpdate()->find($valItem['aId']);

                        // Re-verify inside transaction lock
                        if (!$sourceAssessment || (int) $sourceAssessment->content_id !== (int) $valItem['cId']) {
                            throw new \RuntimeException("Validation failed inside transaction for Assessment ID {$valItem['aId']}: Content mismatch or record missing.");
                        }

                        if ($sourceAssessment->assasment_content_for !== 'content') {
                            throw new \RuntimeException("Validation failed inside transaction for Assessment ID {$valItem['aId']}: Source assessment assasment_content_for is '{$sourceAssessment->assasment_content_for}', expected 'content'.");
                        }

                        // Concurrency idempotency check inside transaction
                        $concurrencyCheck = Assesment::where('assasment_content_for', 'playlist')
                            ->where('trainingplan_id', $valItem['tpId'])
                            ->where('content_id', $valItem['cId'])
                            ->lockForUpdate()
                            ->first();

                        if ($concurrencyCheck) {
                            $valItem['status']             = 'already_exists';
                            $valItem['status_description'] = "ALREADY EXISTS (Playlist Assessment ID: {$concurrencyCheck->id})";
                            $valItem['existing_playlist']  = $concurrencyCheck;
                            $valItem['duplicate_required'] = false;
                            $stats['ready_for_duplication']--;
                            $stats['already_exists']++;
                            continue;
                        }

                        $sourceQuestionsCount = $sourceAssessment->questions()->count();
                        $sourceQbCount        = $sourceAssessment->assessment_question_banks()->count();

                        // Perform Duplication using central AssessmentDuplicateService
                        // 1. Clones assessment configuration and preserves original title
                        // 2. Links to existing target content_id (does NOT duplicate content)
                        // 3. Sets assasment_content_for = 'playlist' and trainingplan_id = $valItem['tpId']
                        // 4. Clones questions, options, and question banks, preserving is_used flags
                        $newAssessment = $duplicateService->duplicate($sourceAssessment, [
                            'title'                 => $sourceAssessment->title,
                            'content_id'            => (int) $valItem['cId'],
                            'assasment_content_for' => 'playlist',
                            'trainingplan_id'       => (int) $valItem['tpId'],
                            'preserve_is_used'      => true,
                        ]);

                        // Step 16: STRICT POST-DUPLICATION VERIFICATION (READ → VALIDATE → DUPLICATE → VERIFY → COMMIT)
                        // AC-01: Source assessment remains untouched
                        $sourceCheck = Assesment::find($valItem['aId']);
                        if (!$sourceCheck || $sourceCheck->assasment_content_for !== 'content' || (int) $sourceCheck->content_id !== (int) $valItem['cId']) {
                            throw new \RuntimeException("Post-duplication verification failed: Original Assessment #{$valItem['aId']} was modified!");
                        }

                        // AC-02, AC-03, AC-04, AC-05: New assessment exists with playlist configuration
                        if (!$newAssessment || !$newAssessment->id) {
                            throw new \RuntimeException("Post-duplication verification failed: New Assessment was not created for target #{$valItem['aId']}.");
                        }

                        if ($newAssessment->assasment_content_for !== 'playlist' || (int) $newAssessment->trainingplan_id !== (int) $valItem['tpId'] || (int) $newAssessment->content_id !== (int) $valItem['cId']) {
                            throw new \RuntimeException("Post-duplication verification failed: New Assessment #{$newAssessment->id} attributes mismatch.");
                        }

                        // AC-06: TrainingPlanDetail exists
                        $pivotExists = TrainingPlanDetail::where('trainingplan_id', $valItem['tpId'])
                            ->where('content_id', $valItem['cId'])
                            ->exists();
                        if (!$pivotExists) {
                            throw new \RuntimeException("Post-duplication verification failed: TrainingPlanDetail not found for target #{$valItem['aId']}.");
                        }

                        // AC-07 & AC-08: Child records verification
                        $newQuestionsCount = $newAssessment->questions()->count();
                        $newQbCount        = $newAssessment->assessment_question_banks()->count();

                        if ($newQuestionsCount !== $sourceQuestionsCount) {
                            throw new \RuntimeException("Post-duplication verification failed: Question count mismatch for Assessment #{$valItem['aId']} (source: {$sourceQuestionsCount}, new: {$newQuestionsCount}).");
                        }

                        if ($newQbCount !== $sourceQbCount) {
                            throw new \RuntimeException("Post-duplication verification failed: Question bank count mismatch for Assessment #{$valItem['aId']} (source: {$sourceQbCount}, new: {$newQbCount}).");
                        }

                        // Verify duplicate child records point exclusively to new Assessment and not source
                        if ($newQuestionsCount > 0) {
                            $sharedQuestions = AssesmentQuestion::where('assesment_id', $sourceAssessment->id)
                                ->whereIn('id', $newAssessment->questions()->pluck('id'))
                                ->exists();
                            if ($sharedQuestions) {
                                throw new \RuntimeException("Post-duplication verification failed: Child questions of Assessment #{$valItem['aId']} shared with source!");
                            }
                        }

                        // Success
                        $valItem['new_assessment']     = $newAssessment;
                        $valItem['status']             = 'duplicated';
                        $valItem['status_description'] = 'SUCCESSFULLY DUPLICATED';

                        $stats['duplicated']++;
                        $stats['questions_duplicated']     += $newQuestionsCount;
                        $stats['child_records_duplicated'] += ($newQuestionsCount + $newQbCount);
                        $stats['database_modified']        += (1 + $newQuestionsCount + $newQbCount);
                    }
                });
            } catch (Throwable $e) {
                $this->error("Database transaction failed and was rolled back: {$e->getMessage()}");
                $this->displayItems($validatedItems);
                $this->displaySummary($stats, $isExecute);
                return self::FAILURE;
            }
        }

        // Step 3: Display item results
        $this->displayItems($validatedItems);

        // Step 4: Display summary
        $this->displaySummary($stats, $isExecute);

        return self::SUCCESS;
    }

    /**
     * Display detailed item results.
     */
    protected function displayItems(array $validatedItems): void
    {
        foreach ($validatedItems as $v) {
            $this->line('--------------------------------------------------');
            $this->line("#{$v['no']}");
            $this->line("Source Assessment : {$v['aId']}");
            $this->line("Title             : " . ($v['assessment'] ? $v['assessment']->title : 'NOT FOUND'));
            $this->line("Content ID        : {$v['cId']}");
            $this->line("Content For       : {$v['current_content_for']}");
            $this->newLine();
            $this->line("Target Playlist   : " . ($v['tp'] ? $v['tp']->title : $v['mapping']['playlist_name']));
            $this->line("TrainingPlan ID   : {$v['tpId']}");
            $this->newLine();
            $this->line("TrainingPlanDetail: " . ($v['pivot_exists'] ? 'FOUND' : 'NOT FOUND'));
            $this->newLine();
            $this->line("Existing Playlist Assessment: " . ($v['existing_playlist'] ? "FOUND (ID: {$v['existing_playlist']->id})" : 'NOT FOUND'));
            $this->line("Duplicate Required: " . ($v['duplicate_required'] ? 'YES' : 'NO'));
            $this->newLine();
            $this->line("Questions         : {$v['questions_count']}");
            $this->line("Options           : {$v['options_count']}");
            $this->newLine();
            $this->line("Result:");
            $this->line($v['status_description']);

            if (isset($v['new_assessment']) && $v['new_assessment']) {
                $this->newLine();
                $this->line("New Assessment ID : {$v['new_assessment']->id}");
                $this->line("assasment_content_for : {$v['new_assessment']->assasment_content_for}");
                $this->line("trainingplan_id   : {$v['new_assessment']->trainingplan_id}");
                $this->line("content_id        : {$v['new_assessment']->content_id}");
            }

            $this->line('--------------------------------------------------');
            $this->newLine();
        }
    }

    /**
     * Print the execution or simulation summary.
     */
    protected function displaySummary(array $stats, bool $isExecute): void
    {
        $this->line('==================================================');
        $this->line('SUMMARY');
        $this->line('==================================================');
        $this->line("Total Target                 : {$stats['total']}");
        $this->newLine();
        $this->line("Source Assessment Found      : {$stats['source_assessment_found']}");
        $this->line("Source Content Found         : {$stats['source_content_found']}");
        $this->line("Playlist Found               : {$stats['playlist_found']}");
        $this->line("TrainingPlanDetail Found     : {$stats['trainingplan_detail_found']}");
        $this->newLine();
        $this->line("Ready for Duplication        : {$stats['ready_for_duplication']}");
        $this->line("Duplicated                   : {$stats['duplicated']}");
        $this->line("Already Exists               : {$stats['already_exists']}");
        $this->newLine();
        $this->line("Questions Duplicated         : {$stats['questions_duplicated']}");
        $this->line("Child Records Duplicated     : {$stats['child_records_duplicated']}");
        $this->newLine();
        $this->line("Not Found                    : {$stats['not_found']}");
        $this->line("Mismatch                     : {$stats['mismatch']}");
        $this->line("Conflict                     : {$stats['conflict']}");
        $this->newLine();
        $this->line("Database Modified            : {$stats['database_modified']}");
        $this->line('==================================================');
    }
}
