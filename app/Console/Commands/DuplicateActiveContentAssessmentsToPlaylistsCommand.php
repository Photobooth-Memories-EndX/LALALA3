<?php

namespace App\Console\Commands;

use App\Models\Assesment;
use App\Models\AssesmentQuestion;
use App\Models\AssesmentQuestionBank;
use App\Models\Content;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Services\AssessmentDuplicateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class DuplicateActiveContentAssessmentsToPlaylistsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assessment:duplicate-active-to-playlists
                            {--dry-run : Perform discovery and simulation without modifying database (read-only default)}
                            {--execute : Perform creation and repair of playlist assessments}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto discover, create, and repair active content assessments to all associated playlists using database relationships';

    /**
     * Default standard passing grade if missing.
     */
    public const DEFAULT_PASSING_GRADE = 80;

    /**
     * Default total remedials if missing.
     */
    public const DEFAULT_TOTAL_REMEDIALS = 2;

    /**
     * Default questions amount per question bank.
     */
    public const DEFAULT_QUESTIONS_AMOUNT = 10;

    /**
     * Execute the console command.
     */
    public function handle(AssessmentDuplicateService $duplicateService): int
    {
        $isExecute = (bool) $this->option('execute');
        $isDryRun  = (bool) $this->option('dry-run');

        // Safe default: if --execute is not explicitly specified, run in safe dry-run mode
        if (!$isExecute) {
            $isDryRun = true;
        }

        // 1. Audit & Query all Content assessments (assasment_content_for = 'content')
        $allContentAssessments = Assesment::where('assasment_content_for', 'content')->get();

        $inactiveAssessments = $allContentAssessments->filter(function ($a) {
            return $a->status !== 'OK' && !empty($a->content_id);
        });

        $activeAssessments = $allContentAssessments->filter(function ($a) {
            return $a->status === 'OK' && !empty($a->content_id);
        })->values();

        $groupedByContent = $activeAssessments->groupBy('content_id');

        $stats = [
            'total_active_content_assessments' => $activeAssessments->count(),
            'unique_contents_with_active'      => $groupedByContent->count(),
            'contents_in_playlists'            => 0,
            'playlist_relations_found'         => 0,
            'ready_for_creation'               => 0,
            'ready_for_repair'                 => 0,
            'already_exists_complete'          => 0,
            'inactive_skipped'                 => $inactiveAssessments->count(),
            'no_playlist'                      => 0,
            'conflicts'                        => 0,
            'created'                          => 0,
            'repaired'                         => 0,
            'questions_added'                  => 0,
            'child_records_added'              => 0,
            'database_modified'                => 0,
        ];

        // 2. Discover Playlist relations for each Content
        $discoveryItems = [];
        $unlinkedContents = [];

        foreach ($groupedByContent as $contentId => $assessments) {
            $content = Content::find($contentId);
            $contentTitle = $content ? $content->title : 'UNKNOWN CONTENT';

            $hasMultipleActive = $assessments->count() > 1;
            if ($hasMultipleActive) {
                $stats['conflicts']++;
            }

            // Look up all playlists containing this content via TrainingPlanDetail
            $details = TrainingPlanDetail::where('content_id', $contentId)
                ->orderBy('trainingplan_id', 'asc')
                ->get();

            if ($details->isEmpty()) {
                $stats['no_playlist']++;
                $unlinkedContents[] = [
                    'content_id'    => $contentId,
                    'content_title' => $contentTitle,
                    'assessments'   => $assessments,
                    'action'        => 'SKIPPED - CONTENT NOT IN PLAYLIST',
                ];
                continue;
            }

            $stats['contents_in_playlists']++;

            foreach ($details as $detail) {
                $stats['playlist_relations_found']++;
                $tpId = (int) $detail->trainingplan_id;
                $trainingPlan = TrainingPlan::find($tpId);
                $tpTitle = $trainingPlan ? $trainingPlan->title : 'UNKNOWN PLAYLIST';

                // Check if a playlist assessment already exists for (trainingplan_id, content_id)
                $existingPlaylistAssessment = Assesment::where('assasment_content_for', 'playlist')
                    ->where('trainingplan_id', $tpId)
                    ->where('content_id', $contentId)
                    ->first();

                $action = '';
                $assessmentStatus = 'NOT FOUND';
                $creationRequired = false;
                $repairRequired   = false;
                $sourceAssessment = $assessments->first();
                $resolvedQb       = null;
                $existingQbCount  = 0;
                $existingQCount   = 0;

                if ($existingPlaylistAssessment !== null) {
                    $existingQbCount = $existingPlaylistAssessment->assessment_question_banks()->count();
                    $existingQCount  = $existingPlaylistAssessment->questions()->count();
                    $isComplete      = $this->isAssessmentComplete($existingPlaylistAssessment);

                    if ($isComplete) {
                        $assessmentStatus = 'COMPLETE';
                        $action = 'ALREADY EXISTS — COMPLETE';
                        $stats['already_exists_complete']++;
                    } else {
                        $assessmentStatus = 'INCOMPLETE';
                        $action = 'READY FOR REPAIR';
                        $repairRequired = true;
                        $stats['ready_for_repair']++;
                        $resolvedQb = $this->resolveQuestionBankForAssessment($existingPlaylistAssessment);
                    }
                } elseif ($hasMultipleActive) {
                    $assessmentStatus = 'CONFLICT';
                    $action = 'SKIPPED - MULTIPLE ACTIVE ASSESSMENTS';
                } elseif (!$trainingPlan) {
                    $assessmentStatus = 'NOT FOUND';
                    $action = 'SKIPPED - PLAYLIST NOT FOUND';
                } else {
                    $assessmentStatus = 'NOT FOUND';
                    $action = 'READY FOR CREATION';
                    $creationRequired = true;
                    $stats['ready_for_creation']++;
                    $resolvedQb = $this->resolveQuestionBankForAssessment($sourceAssessment);
                }

                $discoveryItems[] = [
                    'content_id'                 => $contentId,
                    'content_title'              => $contentTitle,
                    'trainingplan_id'            => $tpId,
                    'playlist_title'             => $tpTitle,
                    'source_assessment'          => $sourceAssessment,
                    'all_assessments'            => $assessments,
                    'existing_assessment'        => $existingPlaylistAssessment,
                    'assessment_status'          => $assessmentStatus,
                    'existing_qb_count'          => $existingQbCount,
                    'existing_q_count'           => $existingQCount,
                    'resolved_qb'                => $resolvedQb,
                    'action'                     => $action,
                    'creation_required'          => $creationRequired,
                    'repair_required'            => $repairRequired,
                    'has_multiple_active'        => $hasMultipleActive,
                    'new_assessment'             => null,
                ];
            }
        }

        // 3. Display Auto-Discovery Report
        $this->displayDiscoveryHeader($stats);
        $this->displayDiscoveryItems($discoveryItems);

        if (!empty($unlinkedContents)) {
            $this->displayUnlinkedContents($unlinkedContents);
        }

        // 4. If --execute is requested, perform atomic creation and repair
        if ($isExecute) {
            $workItems = array_filter($discoveryItems, fn ($i) => $i['creation_required'] || $i['repair_required']);

            if (empty($workItems)) {
                $this->info('No items require creation or repair.');
                $this->displaySummary($stats, $isExecute);
                return self::SUCCESS;
            }

            try {
                DB::transaction(function () use (&$discoveryItems, &$stats, $duplicateService) {
                    foreach ($discoveryItems as &$item) {
                        // CASE 1: REPAIR EXISTING INCOMPLETE ASSESSMENT
                        if ($item['repair_required']) {
                            $targetAssessment = Assesment::lockForUpdate()->find($item['existing_assessment']->id);

                            if (!$targetAssessment) {
                                throw new \RuntimeException("Transaction error: Existing assessment #{$item['existing_assessment']->id} not found under lock.");
                            }

                            $repairedCounts = $this->repairAssessment($targetAssessment, $item['resolved_qb']);

                            // Post-repair strict verification
                            $refreshed = Assesment::find($targetAssessment->id);
                            if (!$this->isAssessmentComplete($refreshed)) {
                                throw new \RuntimeException("Post-repair verification failed: Assessment #{$targetAssessment->id} is still incomplete after repair.");
                            }

                            $item['action']            = 'SUCCESSFULLY REPAIRED';
                            $item['assessment_status'] = 'COMPLETE';

                            $stats['repaired']++;
                            $stats['questions_added']     += $repairedCounts['questions'];
                            $stats['child_records_added'] += ($repairedCounts['questions'] + $repairedCounts['question_banks']);
                            $stats['database_modified']   += (1 + $repairedCounts['questions'] + $repairedCounts['question_banks']);
                            continue;
                        }

                        // CASE 2: CREATE NEW PLAYLIST ASSESSMENT
                        if ($item['creation_required']) {
                            $srcId = $item['source_assessment']->id;
                            $source = Assesment::lockForUpdate()->find($srcId);

                            if (!$source || (int) $source->content_id !== (int) $item['content_id']) {
                                throw new \RuntimeException("Transaction error: Source assessment #{$srcId} missing or content mismatch.");
                            }

                            if ($source->status !== 'OK') {
                                throw new \RuntimeException("Transaction error: Source assessment #{$srcId} is no longer active (status: {$source->status}).");
                            }

                            // Concurrency idempotency check inside transaction lock
                            $concurrencyCheck = Assesment::where('assasment_content_for', 'playlist')
                                ->where('trainingplan_id', $item['trainingplan_id'])
                                ->where('content_id', $item['content_id'])
                                ->lockForUpdate()
                                ->first();

                            if ($concurrencyCheck) {
                                if ($this->isAssessmentComplete($concurrencyCheck)) {
                                    $item['action']              = 'ALREADY EXISTS — COMPLETE';
                                    $item['assessment_status']   = 'COMPLETE';
                                    $item['existing_assessment'] = $concurrencyCheck;
                                    $item['creation_required']   = false;
                                    $stats['ready_for_creation']--;
                                    $stats['already_exists_complete']++;
                                    continue;
                                } else {
                                    // Concurrently exists but incomplete -> repair it
                                    $repairedCounts = $this->repairAssessment($concurrencyCheck, $item['resolved_qb']);
                                    $item['action']              = 'SUCCESSFULLY REPAIRED';
                                    $item['assessment_status']   = 'COMPLETE';
                                    $item['existing_assessment'] = $concurrencyCheck;
                                    $stats['ready_for_creation']--;
                                    $stats['repaired']++;
                                    $stats['questions_added']     += $repairedCounts['questions'];
                                    $stats['child_records_added'] += ($repairedCounts['questions'] + $repairedCounts['question_banks']);
                                    $stats['database_modified']   += (1 + $repairedCounts['questions'] + $repairedCounts['question_banks']);
                                    continue;
                                }
                            }

                            $newAssessment = $duplicateService->duplicate($source, [
                                'title'                 => $source->title,
                                'content_id'            => (int) $item['content_id'],
                                'assasment_content_for' => 'playlist',
                                'trainingplan_id'       => (int) $item['trainingplan_id'],
                                'preserve_is_used'      => true,
                            ]);

                            // If new assessment is incomplete (because source was incomplete shell), repair it immediately
                            if (!$this->isAssessmentComplete($newAssessment)) {
                                $repairedCounts = $this->repairAssessment($newAssessment, $item['resolved_qb']);
                                $newQuestionsCount = $newAssessment->questions()->count();
                                $newQbCount        = $newAssessment->assessment_question_banks()->count();
                            } else {
                                $newQuestionsCount = $newAssessment->questions()->count();
                                $newQbCount        = $newAssessment->assessment_question_banks()->count();
                            }

                            // Post-duplication strict verification
                            if (!$newAssessment || !$newAssessment->id) {
                                throw new \RuntimeException("Post-duplication verification failed: New assessment was not created for target content #{$item['content_id']}.");
                            }

                            if ($newAssessment->assasment_content_for !== 'playlist' ||
                                (int) $newAssessment->trainingplan_id !== (int) $item['trainingplan_id'] ||
                                (int) $newAssessment->content_id !== (int) $item['content_id']) {
                                throw new \RuntimeException("Post-duplication verification failed: New assessment #{$newAssessment->id} attributes mismatch.");
                            }

                            // Source assessment remains untouched
                            $sourceCheck = Assesment::find($srcId);
                            if (!$sourceCheck || $sourceCheck->assasment_content_for !== 'content' || (int) $sourceCheck->content_id !== (int) $item['content_id']) {
                                throw new \RuntimeException("Post-duplication verification failed: Source assessment #{$srcId} was modified!");
                            }

                            $item['new_assessment']    = $newAssessment;
                            $item['action']            = 'SUCCESSFULLY CREATED';
                            $item['assessment_status'] = 'COMPLETE';

                            $stats['created']++;
                            $stats['questions_added']     += $newQuestionsCount;
                            $stats['child_records_added'] += ($newQuestionsCount + $newQbCount);
                            $stats['database_modified']   += (1 + $newQuestionsCount + $newQbCount);
                        }
                    }
                });
            } catch (Throwable $e) {
                $this->error("Database transaction failed and was rolled back: {$e->getMessage()}");
                $this->displaySummary($stats, $isExecute);
                return self::FAILURE;
            }
        }

        // 5. Display Final Summary
        $this->displaySummary($stats, $isExecute);

        return self::SUCCESS;
    }

    /**
     * Check if an assessment is complete.
     * Criteria:
     * - Has questions (> 0)
     * - Has question bank relation (> 0) OR has direct questions (> 0)
     * - Has valid passing_grade
     * - Has status = 'OK'
     */
    public function isAssessmentComplete(?Assesment $assessment): bool
    {
        if (!$assessment) {
            return false;
        }

        $hasQuestions = $assessment->questions()->count() > 0;
        $hasQuestionBanks = $assessment->assessment_question_banks()->count() > 0;
        $hasPassingGrade = !is_null($assessment->passing_grade) && $assessment->passing_grade !== '';
        $isActive = $assessment->status === 'OK';

        return $hasQuestions && ($hasQuestionBanks || $hasQuestions) && $hasPassingGrade && $isActive;
    }

    /**
     * Smart resolver for master QuestionBank matching an Assessment or its Content.
     */
    public function resolveQuestionBankForAssessment(Assesment $assessment): ?QuestionBank
    {
        // 1. If assessment already has linked QuestionBank, return it
        $existing = $assessment->assessment_question_banks()->first();
        if ($existing && $existing->question_bank) {
            return $existing->question_bank;
        }

        // 2. Check if active source assessment for same content has linked QuestionBank
        if ($assessment->content_id) {
            $source = Assesment::where('content_id', $assessment->content_id)
                ->where('assasment_content_for', 'content')
                ->where('status', 'OK')
                ->first();
            if ($source && $source->assessment_question_banks()->count() > 0) {
                $srcQb = $source->assessment_question_banks()->first();
                if ($srcQb && $srcQb->question_bank) {
                    return $srcQb->question_bank;
                }
            }
        }

        // 3. Match against titles
        $titlesToTry = [];
        if (!empty($assessment->title)) {
            $titlesToTry[] = $assessment->title;
        }
        if ($assessment->content && !empty($assessment->content->title)) {
            $titlesToTry[] = $assessment->content->title;
        }

        foreach ($titlesToTry as $t) {
            // Pattern: Chapter N (e.g. "[Safety Series #1] Post-Test Chapter 1 : 3 Major Accident")
            if (preg_match('/Chapter\s+(\d+)\s*[:\-]\s*(.+)$/i', $t, $m)) {
                $chapterNum = $m[1];
                $qb = QuestionBank::where('title', 'like', "Chapter {$chapterNum}%")->first();
                if ($qb) {
                    return $qb;
                }
            }

            // Keyword pattern for Health E-Learning: CPR, TBC, HIV/AIDS
            if (stripos($t, 'CPR') !== false || stripos($t, 'AED') !== false) {
                $qb = QuestionBank::where('title', 'like', '%CPR%')->first();
                if ($qb) return $qb;
            }
            if (stripos($t, 'TBC') !== false) {
                $qb = QuestionBank::where('title', 'like', '%TBC%')->first();
                if ($qb) return $qb;
            }
            if (stripos($t, 'HIV') !== false || stripos($t, 'AIDS') !== false) {
                $qb = QuestionBank::where('title', 'like', '%HIV%')->first();
                if ($qb) return $qb;
            }

            // Clean title search
            $clean = trim(preg_replace('/\[.*?\]|\(.*?\)/', '', $t));
            $clean = trim(preg_replace('/\s+/', ' ', $clean));
            if (strlen($clean) >= 4) {
                $qb = QuestionBank::where('title', 'like', "%{$clean}%")->first();
                if ($qb) return $qb;
            }
        }

        return null;
    }

    /**
     * Repair an incomplete assessment in-place using resolved QuestionBank and standard configuration.
     *
     * @return array{questions: int, question_banks: int}
     */
    public function repairAssessment(Assesment $assessment, ?QuestionBank $questionBank = null): array
    {
        $qb = $questionBank ?? $this->resolveQuestionBankForAssessment($assessment);

        // 1. Repair Configuration
        $assessment->passing_grade     = !empty($assessment->passing_grade) ? $assessment->passing_grade : self::DEFAULT_PASSING_GRADE;
        $assessment->total_remedials   = !is_null($assessment->total_remedials) && $assessment->total_remedials > 0 ? $assessment->total_remedials : self::DEFAULT_TOTAL_REMEDIALS;
        $assessment->timer             = 'y';
        $assessment->deadline_type     = $assessment->deadline_type ?: 'period';
        $assessment->has_access_period = true;
        $assessment->status            = 'OK';
        $assessment->save();

        $questionsAdded = 0;
        $qbsAdded = 0;

        // 2. Link Question Bank & Populate Questions if question bank is found and assessment has no questions
        if ($qb && $assessment->questions()->count() === 0) {
            $masterQuestions = Question::where('question_bank_id', $qb->id)->get();
            $questionsAmount = min(self::DEFAULT_QUESTIONS_AMOUNT, $masterQuestions->count());

            // Link or get AssesmentQuestionBank
            $aqb = AssesmentQuestionBank::firstOrNew([
                'assesment_id'     => $assessment->id,
                'question_bank_id' => $qb->id,
            ]);

            if (!$aqb->exists) {
                $aqb->questions_amount = $questionsAmount;
                $aqb->save();
                $qbsAdded++;
            }

            // Populate Assessment Questions
            foreach ($masterQuestions as $q) {
                $newQuestion = new AssesmentQuestion;
                $newQuestion->assesment_id     = $assessment->id;
                $newQuestion->question_bank_id = $qb->id;
                $newQuestion->question_id      = $q->id;
                $newQuestion->question         = $q->question;
                $newQuestion->image            = $q->question_image;
                $newQuestion->option           = $q->option;
                $newQuestion->answer           = $q->answer;
                $newQuestion->status           = true;
                $newQuestion->is_used          = false;
                $newQuestion->save();
                $questionsAdded++;
            }

            // Set random used questions according to questions_amount
            $randomQuestions = AssesmentQuestion::where('assesment_id', $assessment->id)
                ->where('question_bank_id', $qb->id)
                ->inRandomOrder()
                ->limit($questionsAmount)
                ->get();

            foreach ($randomQuestions as $rq) {
                $rq->is_used = true;
                $rq->save();
            }
        }

        return [
            'questions'      => $questionsAdded,
            'question_banks' => $qbsAdded,
        ];
    }

    /**
     * Display header section.
     */
    protected function displayDiscoveryHeader(array $stats): void
    {
        $this->line('==================================================');
        $this->line('AUTO DISCOVERY ASSESSMENT CONTENT → PLAYLIST');
        $this->line('==================================================');
        $this->line("Active Content Assessments : {$stats['total_active_content_assessments']}");
        $this->line("Contents in Playlists      : {$stats['contents_in_playlists']}");
        $this->line("Playlist Relations Found   : {$stats['playlist_relations_found']}");
        $this->line('--------------------------------------------------');
    }

    /**
     * Display each discovered relation item with completeness details.
     */
    protected function displayDiscoveryItems(array $discoveryItems): void
    {
        foreach ($discoveryItems as $item) {
            $src = $item['source_assessment'];

            $this->newLine();
            if ($item['has_multiple_active']) {
                $this->line("Source Assessment : MULTIPLE (" . $item['all_assessments']->pluck('id')->implode(', ') . ")");
            } else {
                $this->line("Source Assessment : #" . ($src ? $src->id : 'N/A'));
            }

            $this->line("Content           : #{$item['content_id']}");
            $this->newLine();
            $this->line("Playlist          : #{$item['trainingplan_id']} {$item['playlist_title']}");
            $this->newLine();
            $this->line("Existing Playlist Assessment:");
            if ($item['existing_assessment']) {
                $this->line("    #{$item['existing_assessment']->id} ({$item['existing_assessment']->title})");
                $this->newLine();
                $this->line("Assessment Status:");
                $this->line("    {$item['assessment_status']}");
                $this->newLine();
                $this->line("Question Banks:");
                $this->line("    {$item['existing_qb_count']}");
                $this->newLine();
                $this->line("Questions:");
                $this->line("    {$item['existing_q_count']}");
            } else {
                $this->line("    NOT FOUND");
                $this->newLine();
                $this->line("Assessment Status:");
                $this->line("    NOT FOUND");
            }

            if ($item['resolved_qb'] && ($item['repair_required'] || $item['creation_required'])) {
                $this->newLine();
                $this->line("Matched Question Bank:");
                $this->line("    #{$item['resolved_qb']->id} {$item['resolved_qb']->title}");
            }

            $this->newLine();
            $this->line("Action:");
            $this->line("    {$item['action']}");

            if (isset($item['new_assessment']) && $item['new_assessment']) {
                $this->newLine();
                $this->line("New Assessment ID : #{$item['new_assessment']->id}");
                $this->line("assasment_content_for : {$item['new_assessment']->assasment_content_for}");
                $this->line("trainingplan_id   : {$item['new_assessment']->trainingplan_id}");
                $this->line("content_id        : {$item['new_assessment']->content_id}");
            }

            $this->line('--------------------------------------------------');
        }
    }

    /**
     * Display contents not in any playlist.
     */
    protected function displayUnlinkedContents(array $unlinkedContents): void
    {
        $this->line('Contents with Active Assessments NOT in any Playlist:');
        foreach ($unlinkedContents as $u) {
            $ids = $u['assessments']->pluck('id')->implode(', ');
            $this->line("  - Content #{$u['content_id']} ({$u['content_title']}) -> Assessment(s): #{$ids} [{$u['action']}]");
        }
        $this->line('--------------------------------------------------');
    }

    /**
     * Display summary section.
     */
    protected function displaySummary(array $stats, bool $isExecute): void
    {
        $this->newLine();
        $this->line('==================================================');
        $this->line('Summary:');
        $this->newLine();
        $this->line("Ready for Creation       : {$stats['ready_for_creation']}");
        $this->line("Ready for Repair         : {$stats['ready_for_repair']}");
        $this->line("Already Exists Complete  : {$stats['already_exists_complete']}");
        $this->line("Inactive Skipped         : {$stats['inactive_skipped']}");
        $this->line("No Playlist              : {$stats['no_playlist']}");
        $this->line("Conflicts                : {$stats['conflicts']}");
        if ($isExecute) {
            $this->line("Created                  : {$stats['created']}");
            $this->line("Repaired                 : {$stats['repaired']}");
            $this->line("Questions Added          : {$stats['questions_added']}");
            $this->line("Child Records Added      : {$stats['child_records_added']}");
        }
        $this->line("Database Modified        : {$stats['database_modified']}");
        $this->newLine();
        $this->line('==================================================');
        if (!$isExecute) {
            $this->line('DRY RUN ONLY.');
            $this->line('NO DATABASE CHANGES.');
            $this->line('==================================================');
        }
    }
}
