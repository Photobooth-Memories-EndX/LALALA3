<?php

namespace Tests\Feature;

use App\Models\Assesment;
use App\Models\Content;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RestoreAssessmentPlaylistRelationsCommandTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test that the dry-run command runs successfully, verifies all 17 targets,
     * and performs zero database mutations.
     */
    public function test_dry_run_reports_ready_for_update_and_makes_zero_modifications(): void
    {
        // Snapshot current states of the 17 assessments
        $targetIds = [326, 325, 324, 260, 262, 263, 264, 265, 266, 177, 178, 179, 180, 181, 182, 183, 184];
        $initialStates = Assesment::whereIn('id', $targetIds)
            ->get(['id', 'content_for', 'trainingplan_id', 'updated_at'])
            ->keyBy('id');

        $this->artisan('assessment:restore-playlist-relations', ['--dry-run' => true])
            ->expectsOutputToContain('ASSESSMENT PLAYLIST RELATION RECOVERY — DRY RUN')
            ->expectsOutputToContain('Total Target       : 17')
            ->expectsOutputToContain('Assessment Found   : 17')
            ->expectsOutputToContain('Content Found      : 17')
            ->expectsOutputToContain('Playlist Found     : 17')
            ->expectsOutputToContain('Pivot Found        : 17')
            ->expectsOutputToContain('Ready for Update   : 17')
            ->expectsOutputToContain('Already Correct    : 0')
            ->expectsOutputToContain('Conflict           : 0')
            ->expectsOutputToContain('Database Modified  : 0')
            ->assertSuccessful();

        // Verify zero mutations occurred
        $postStates = Assesment::whereIn('id', $targetIds)
            ->get(['id', 'content_for', 'trainingplan_id', 'updated_at'])
            ->keyBy('id');

        foreach ($targetIds as $id) {
            $this->assertEquals(
                $initialStates[$id]->content_for,
                $postStates[$id]->content_for,
                "Assessment ID {$id} content_for was mutated during dry run"
            );
            $this->assertEquals(
                $initialStates[$id]->trainingplan_id,
                $postStates[$id]->trainingplan_id,
                "Assessment ID {$id} trainingplan_id was mutated during dry run"
            );
            $this->assertEquals(
                (string) $initialStates[$id]->updated_at,
                (string) $postStates[$id]->updated_at,
                "Assessment ID {$id} updated_at was mutated during dry run"
            );
        }
    }

    /**
     * Test idempotency recognition: when an assessment already has target trainingplan_id and content_for = 'playlist'.
     */
    public function test_dry_run_identifies_already_up_to_date_records(): void
    {
        // Temporarily simulate item 326 already updated
        Assesment::where('id', 326)->update([
            'trainingplan_id' => 32,
            'content_for' => 'playlist',
        ]);

        $this->artisan('assessment:restore-playlist-relations', ['--dry-run' => true])
            ->expectsOutputToContain('#1')
            ->expectsOutputToContain('ALREADY UP TO DATE')
            ->expectsOutputToContain('Already Correct    : 1')
            ->expectsOutputToContain('Ready for Update   : 16')
            ->assertSuccessful();
    }

    /**
     * Test that --execute successfully updates all 17 assessments with content_for = 'playlist'
     * and the correct trainingplan_id within a database transaction.
     */
    public function test_execute_restores_verified_assessments_within_database_transaction(): void
    {
        $targetMapping = \App\Console\Commands\RestoreAssessmentPlaylistRelationsCommand::TARGET_MAPPING;

        $this->artisan('assessment:restore-playlist-relations', ['--execute' => true])
            ->expectsOutputToContain('ASSESSMENT PLAYLIST RELATION RECOVERY — EXECUTE MODE')
            ->expectsOutputToContain('Updated            : 17')
            ->expectsOutputToContain('Already Correct    : 0')
            ->expectsOutputToContain('Conflict           : 0')
            ->expectsOutputToContain('Database Modified  : 17')
            ->assertSuccessful();

        // Verify all 17 assessments in the database
        foreach ($targetMapping as $item) {
            $assessment = Assesment::find($item['assessment_id']);
            $this->assertNotNull($assessment);
            $this->assertSame('playlist', $assessment->content_for, "Assessment ID {$item['assessment_id']} content_for was not updated to playlist");
            $this->assertSame((int) $item['trainingplan_id'], (int) $assessment->trainingplan_id, "Assessment ID {$item['assessment_id']} trainingplan_id mismatch");
            $this->assertSame((int) $item['content_id'], (int) $assessment->content_id, "Assessment ID {$item['assessment_id']} content_id was modified unexpectedly");
        }
    }

    /**
     * Test that --execute is idempotent: running it a second time makes zero changes
     * and reports all 17 as ALREADY UP TO DATE.
     */
    public function test_execute_is_idempotent(): void
    {
        // First execution: updates 17
        $this->artisan('assessment:restore-playlist-relations', ['--execute' => true])
            ->expectsOutputToContain('Updated            : 17')
            ->assertSuccessful();

        // Second execution: must detect all 17 already correct, 0 modifications
        $this->artisan('assessment:restore-playlist-relations', ['--execute' => true])
            ->expectsOutputToContain('ASSESSMENT PLAYLIST RELATION RECOVERY — EXECUTE MODE')
            ->expectsOutputToContain('Updated            : 0')
            ->expectsOutputToContain('Already Correct    : 17')
            ->expectsOutputToContain('Database Modified  : 0')
            ->expectsOutputToContain('ALREADY UP TO DATE')
            ->assertSuccessful();
    }

    /**
     * Test that any validation failure aborts execution and makes zero changes (rollback).
     */
    public function test_execute_aborts_and_rolls_back_when_validation_fails(): void
    {
        // Induce a content mismatch on item 326 using another existing content id (123 instead of 280)
        Assesment::where('id', 326)->update(['content_id' => 123]);

        // Attempt execute
        $this->artisan('assessment:restore-playlist-relations', ['--execute' => true])
            ->expectsOutputToContain('CONFLICT / SKIPPED: Content mismatch')
            ->expectsOutputToContain('Execution aborted')
            ->assertFailed();

        // Verify other valid assessments (e.g. ID 325) were NOT updated
        $assessment325 = Assesment::find(325);
        $this->assertSame('content', $assessment325->content_for);
        $this->assertNull($assessment325->trainingplan_id);
    }
}
