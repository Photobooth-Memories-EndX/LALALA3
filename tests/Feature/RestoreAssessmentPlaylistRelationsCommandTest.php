<?php

namespace Tests\Feature;

use App\Console\Commands\RestoreAssessmentPlaylistRelationsCommand;
use App\Models\Assesment;
use App\Models\AssesmentAssigned;
use App\Models\AssesmentQuestion;
use App\Models\AssesmentQuestionBank;
use App\Models\AssesmentResult;
use App\Models\AssesmentScoreHistory;
use App\Models\Content;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RestoreAssessmentPlaylistRelationsCommandTest extends TestCase
{
    use DatabaseTransactions;

    public function test_dry_run_is_read_only_and_reports_ready(): void
    {
        $assessmentCountBefore = Assesment::count();
        $questionCountBefore   = AssesmentQuestion::count();

        $this->artisan('assessment:restore-playlist-relations', ['--dry-run' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('ASSESSMENT CONTENT → PLAYLIST DUPLICATION')
            ->expectsOutputToContain('MODE: DRY RUN (READ-ONLY)')
            ->expectsOutputToContain('Ready for Duplication        : 17')
            ->expectsOutputToContain('Database Modified            : 0');

        $this->assertSame($assessmentCountBefore, Assesment::count());
        $this->assertSame($questionCountBefore, AssesmentQuestion::count());
    }

    public function test_default_invocation_is_safe_dry_run(): void
    {
        $assessmentCountBefore = Assesment::count();

        $this->artisan('assessment:restore-playlist-relations')
            ->assertSuccessful()
            ->expectsOutputToContain('MODE: DRY RUN (READ-ONLY)')
            ->expectsOutputToContain('Database Modified            : 0');

        $this->assertSame($assessmentCountBefore, Assesment::count());
    }

    public function test_source_assessment_integrity_and_child_isolation_after_execute(): void
    {
        // 1. Snapshot source assessments state BEFORE execute
        $sourceSnapshots = [];
        foreach (RestoreAssessmentPlaylistRelationsCommand::TARGET_MAPPING as $item) {
            $src = Assesment::with(['questions', 'assessment_question_banks'])->find($item['assessment_id']);
            $this->assertNotNull($src, "Source assessment #{$item['assessment_id']} must exist in DB");
            $this->assertSame('content', $src->assasment_content_for, "Source assessment #{$item['assessment_id']} must have assasment_content_for = 'content' before execute");
            $this->assertNull($src->trainingplan_id, "Source assessment #{$item['assessment_id']} must have trainingplan_id = NULL before execute");
            $this->assertSame((int) $item['content_id'], (int) $src->content_id);

            $sourceSnapshots[$item['assessment_id']] = [
                'assessment'     => $src,
                'question_ids'   => $src->questions->pluck('id')->all(),
                'qb_ids'         => $src->assessment_question_banks->pluck('id')->all(),
                'questions_count'=> $src->questions->count(),
                'qb_count'       => $src->assessment_question_banks->count(),
            ];
        }

        // 2. Execute command
        $this->artisan('assessment:restore-playlist-relations', ['--execute' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('MODE: EXECUTE')
            ->expectsOutputToContain('Duplicated                   : 17');

        // 3. Verify AFTER execute
        foreach (RestoreAssessmentPlaylistRelationsCommand::TARGET_MAPPING as $item) {
            $snap = $sourceSnapshots[$item['assessment_id']];

            // Re-fetch source assessment fresh from DB
            $srcFresh = Assesment::with(['questions', 'assessment_question_banks'])->find($item['assessment_id']);

            // INTEGRITY CHECK: Source Assessment MUST NOT BE MODIFIED
            $this->assertNotNull($srcFresh);
            $this->assertSame('content', $srcFresh->assasment_content_for, "Source Assessment #{$item['assessment_id']} assasment_content_for was modified!");
            $this->assertNull($srcFresh->trainingplan_id, "Source Assessment #{$item['assessment_id']} trainingplan_id was modified!");
            $this->assertSame((int) $item['content_id'], (int) $srcFresh->content_id);
            $this->assertSame($snap['questions_count'], $srcFresh->questions->count(), "Source Assessment #{$item['assessment_id']} lost questions!");
            $this->assertSame($snap['question_ids'], $srcFresh->questions->pluck('id')->all(), "Source Assessment #{$item['assessment_id']} question IDs altered!");
            $this->assertSame($snap['qb_count'], $srcFresh->assessment_question_banks->count(), "Source Assessment #{$item['assessment_id']} lost question banks!");

            // DUPLICATE CHECK: New assessment exists with playlist context
            $newAssessment = Assesment::where('assasment_content_for', 'playlist')
                ->where('trainingplan_id', $item['trainingplan_id'])
                ->where('content_id', $item['content_id'])
                ->first();

            $this->assertNotNull($newAssessment, "New playlist assessment was not created for target #{$item['assessment_id']}");
            $this->assertNotEquals($srcFresh->id, $newAssessment->id);
            $this->assertSame('playlist', $newAssessment->assasment_content_for);
            $this->assertSame((int) $item['trainingplan_id'], (int) $newAssessment->trainingplan_id);
            $this->assertSame((int) $item['content_id'], (int) $newAssessment->content_id);
            $this->assertSame($srcFresh->title, $newAssessment->title);
            $this->assertNull($newAssessment->unique_code);

            // Verified replicated configuration fields
            $this->assertSame($srcFresh->passing_grade, $newAssessment->passing_grade);
            $this->assertSame($srcFresh->timer, $newAssessment->timer);
            $this->assertSame($srcFresh->has_access_period, $newAssessment->has_access_period);
            $this->assertSame($srcFresh->deadline_type, $newAssessment->deadline_type);
            $this->assertSame($srcFresh->time_limit_minutes, $newAssessment->time_limit_minutes);
            $this->assertSame($srcFresh->status, $newAssessment->status);
            $this->assertSame($srcFresh->batch, $newAssessment->batch);
            $this->assertSame($srcFresh->total_remedials, $newAssessment->total_remedials);

            // CHILD ISOLATION CHECK: Questions count matches exactly
            $newQuestions = $newAssessment->questions()->get();
            $this->assertSame($snap['questions_count'], $newQuestions->count(), "New Assessment #{$newAssessment->id} question count mismatch");

            // Check Question Banks count matches
            $newQbs = $newAssessment->assessment_question_banks()->get();
            $this->assertSame($snap['qb_count'], $newQbs->count(), "New Assessment #{$newAssessment->id} question bank count mismatch");

            // For each duplicate question:
            foreach ($newQuestions as $nQ) {
                // Must point strictly to NEW assessment ID
                $this->assertSame((int) $newAssessment->id, (int) $nQ->assesment_id);
                // Must NOT be in source question IDs
                $this->assertNotContains($nQ->id, $snap['question_ids']);
                // Options JSON must be valid and non-empty array if source had options
                $this->assertIsArray($nQ->option);
            }

            // USER HISTORY CHECK: New assessment MUST NOT duplicate user execution history
            $this->assertSame(0, AssesmentResult::where('assesment_id', $newAssessment->id)->count(), "New Assessment #{$newAssessment->id} must not have assessment results");
            $this->assertSame(0, AssesmentAssigned::where('assesment_id', $newAssessment->id)->count(), "New Assessment #{$newAssessment->id} must not have assigned users");
            $this->assertSame(0, AssesmentScoreHistory::where('assesment_id', $newAssessment->id)->count(), "New Assessment #{$newAssessment->id} must not have score histories");
        }
    }

    public function test_idempotency_second_run_creates_zero_duplicates(): void
    {
        $assessmentCountBefore = Assesment::count();

        // Run #1: Duplicates 17 items
        $this->artisan('assessment:restore-playlist-relations', ['--execute' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('Duplicated                   : 17')
            ->expectsOutputToContain('Already Exists               : 0');

        $countAfterRun1 = Assesment::count();
        $this->assertSame($assessmentCountBefore + 17, $countAfterRun1);

        // Run #2: Must NOT duplicate anything, 17 already exist, database modified = 0
        $this->artisan('assessment:restore-playlist-relations', ['--execute' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('Duplicated                   : 0')
            ->expectsOutputToContain('Already Exists               : 17')
            ->expectsOutputToContain('Database Modified            : 0');

        $countAfterRun2 = Assesment::count();
        $this->assertSame($countAfterRun1, $countAfterRun2, "Assessment count must not increase on repeated execution");
    }
}
