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
}
