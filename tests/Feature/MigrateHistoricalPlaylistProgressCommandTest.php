<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class MigrateHistoricalPlaylistProgressCommandTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user1;
    protected User $user2;
    protected Classification $classification;
    protected string $cutoff = '2026-08-28 23:59:59';

    protected function setUp(): void
    {
        parent::setUp();

        $this->user1 = User::factory()->create([
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-sso-' . Str::uuid(),
            'sap_code'   => 'SAP-TEST-' . rand(10000, 99999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->user2 = User::factory()->create([
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-sso-' . Str::uuid(),
            'sap_code'   => 'SAP-TEST-' . rand(10000, 99999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->classification = Classification::firstOrCreate(
            ['classification_name' => 'Test Migration Classification'],
            ['slug' => 'test-migration-classification']
        );
    }

    private function createOldPlaylist(string $title): TrainingPlan
    {
        return TrainingPlan::create([
            'title'      => $title,
            'slug'       => Str::slug($title) . '-' . rand(1000, 9999),
            'active'     => 'Active',
            'created_at' => Carbon::parse('2026-08-01 10:00:00'),
            'updated_at' => Carbon::parse('2026-08-01 10:00:00'),
        ]);
    }

    private function createNewPlaylist(string $title): TrainingPlan
    {
        return TrainingPlan::create([
            'title'      => $title,
            'slug'       => Str::slug($title) . '-' . rand(1000, 9999),
            'active'     => 'Active',
            'created_at' => Carbon::parse('2026-08-29 10:00:00'),
            'updated_at' => Carbon::parse('2026-08-29 10:00:00'),
        ]);
    }

    private function createContent(string $title, string $playlistOnly = 'Y'): Content
    {
        return Content::create([
            'title'             => $title,
            'slug'              => Str::slug($title) . '-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => $playlistOnly,
        ]);
    }

    /**
     * 1. Historical single playlist migration
     */
    public function test_1_historical_single_playlist_migration(): void
    {
        $plan = $this->createOldPlaylist('Old Plan 1');
        $content = $this->createContent('Content 1');
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        ContentUser::create([
            'user_id'          => $this->user1->id,
            'content_id'       => $content->id,
            'progress'         => 75,
            'finish'           => '0',
            'learning_status'  => 'in_progress',
            'current_time'     => 300,
            'duration_seconds' => 400,
        ]);

        $this->artisan('learning:migrate-playlist-progress', [
            '--playlist' => $plan->id,
            '--cutoff'   => $this->cutoff,
            '--force'    => true,
        ])->assertSuccessful();

        $pcu = PlaylistContentUser::where('user_id', $this->user1->id)
            ->where('trainingplan_id', $plan->id)
            ->where('content_id', $content->id)
            ->first();

        $this->assertNotNull($pcu);
        $this->assertEquals(75, $pcu->progress);
        $this->assertEquals('0', $pcu->finish);
        $this->assertEquals('in_progress', $pcu->learning_status);
        $this->assertEquals(300, $pcu->current_time);
        $this->assertEquals(400, $pcu->duration_seconds);
    }

    /**
     * 2. Historical multi-playlist migration
     */
    public function test_2_historical_multi_playlist_migration(): void
    {
        $planA = $this->createOldPlaylist('Old Plan A');
        $planB = $this->createOldPlaylist('Old Plan B');
        $content = $this->createContent('Shared Content');

        TrainingPlanDetail::create(['trainingplan_id' => $planA->id, 'content_id' => $content->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $planB->id, 'content_id' => $content->id, 'content_order' => 0]);

        ContentUser::create([
            'user_id'          => $this->user1->id,
            'content_id'       => $content->id,
            'progress'         => 100,
            'finish'           => '1',
            'learning_status'  => 'completed',
            'current_time'     => 600,
            'duration_seconds' => 600,
        ]);

        $this->artisan('learning:migrate-playlist-progress', [
            '--playlist' => $planA->id,
            '--cutoff'   => $this->cutoff,
            '--force'    => true,
        ])->assertSuccessful();

        $this->artisan('learning:migrate-playlist-progress', [
            '--playlist' => $planB->id,
            '--cutoff'   => $this->cutoff,
            '--force'    => true,
        ])->assertSuccessful();

        $pcuA = PlaylistContentUser::where('user_id', $this->user1->id)
            ->where('trainingplan_id', $planA->id)
            ->where('content_id', $content->id)
            ->first();

        $pcuB = PlaylistContentUser::where('user_id', $this->user1->id)
            ->where('trainingplan_id', $planB->id)
            ->where('content_id', $content->id)
            ->first();

        $this->assertNotNull($pcuA);
        $this->assertNotNull($pcuB);
        $this->assertEquals(100, $pcuA->progress);
        $this->assertEquals('1', $pcuA->finish);
        $this->assertEquals(100, $pcuB->progress);
        $this->assertEquals('1', $pcuB->finish);
    }

    /**
     * 3. New playlist excluded from migration
     */
    public function test_3_new_playlist_excluded_from_migration(): void
    {
        $planOld = $this->createOldPlaylist('Old Plan');
        $planNew = $this->createNewPlaylist('New Plan Post Cutoff');
        $content = $this->createContent('Common Content');

        TrainingPlanDetail::create(['trainingplan_id' => $planOld->id, 'content_id' => $content->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $planNew->id, 'content_id' => $content->id, 'content_order' => 0]);

        ContentUser::create([
            'user_id'          => $this->user1->id,
            'content_id'       => $content->id,
            'progress'         => 100,
            'finish'           => '1',
            'learning_status'  => 'completed',
        ]);

        $this->artisan('learning:migrate-playlist-progress', [
            '--playlist' => $planNew->id,
            '--cutoff'   => $this->cutoff,
            '--force'    => true,
        ])->assertSuccessful();

        $pcuNew = PlaylistContentUser::where('user_id', $this->user1->id)
            ->where('trainingplan_id', $planNew->id)
            ->where('content_id', $content->id)
            ->first();

        $this->assertNull($pcuNew, 'New playlist must NOT have PCU records created during historical migration');
    }

    /**
     * 4. Existing PCU is never overwritten (PCU authoritative)
     */
    public function test_4_existing_pcu_is_never_overwritten(): void
    {
        $plan = $this->createOldPlaylist('Plan with Existing PCU');
        $content = $this->createContent('Content with PCU');
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        ContentUser::create([
            'user_id'          => $this->user1->id,
            'content_id'       => $content->id,
            'progress'         => 100,
            'finish'           => '1',
            'learning_status'  => 'completed',
        ]);

        // Pre-existing PCU with 40%
        PlaylistContentUser::create([
            'user_id'          => $this->user1->id,
            'trainingplan_id'  => $plan->id,
            'content_id'       => $content->id,
            'progress'         => 40,
            'finish'           => '0',
            'learning_status'  => 'in_progress',
        ]);

        $this->artisan('learning:migrate-playlist-progress', [
            '--playlist' => $plan->id,
            '--cutoff'   => $this->cutoff,
            '--force'    => true,
        ])->assertSuccessful();

        $pcu = PlaylistContentUser::where('user_id', $this->user1->id)
            ->where('trainingplan_id', $plan->id)
            ->where('content_id', $content->id)
            ->first();

        $this->assertNotNull($pcu);
        $this->assertEquals(40, $pcu->progress, 'Existing PCU progress must remain 40% and never overwritten to 100%');
        $this->assertEquals('0', $pcu->finish);
    }

    /**
     * 5. content_users remains unchanged
     */
    public function test_5_content_users_remains_unchanged(): void
    {
        $plan = $this->createOldPlaylist('Plan Integrity Test');
        $content = $this->createContent('Content Integrity Test');
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        $cu = ContentUser::create([
            'user_id'          => $this->user1->id,
            'content_id'       => $content->id,
            'progress'         => 90,
            'finish'           => '0',
            'learning_status'  => 'in_progress',
            'current_time'     => 180,
            'duration_seconds' => 200,
        ]);

        $this->artisan('learning:migrate-playlist-progress', [
            '--playlist' => $plan->id,
            '--cutoff'   => $this->cutoff,
            '--force'    => true,
        ])->assertSuccessful();

        $freshCu = ContentUser::find($cu->id);
        $this->assertNotNull($freshCu);
        $this->assertEquals(90, $freshCu->progress);
        $this->assertEquals('0', $freshCu->finish);
        $this->assertEquals(180, $freshCu->current_time);
    }

    /**
     * 6. Duplicate PCU protection
     */
    public function test_6_duplicate_pcu_protection(): void
    {
        $plan = $this->createOldPlaylist('Plan Duplicate Check');
        $content = $this->createContent('Content Duplicate Check');
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        ContentUser::create([
            'user_id'          => $this->user1->id,
            'content_id'       => $content->id,
            'progress'         => 100,
            'finish'           => '1',
            'learning_status'  => 'completed',
        ]);

        // Run migration twice
        $this->artisan('learning:migrate-playlist-progress', ['--playlist' => $plan->id, '--cutoff' => $this->cutoff, '--force' => true])->assertSuccessful();
        $this->artisan('learning:migrate-playlist-progress', ['--playlist' => $plan->id, '--cutoff' => $this->cutoff, '--force' => true])->assertSuccessful();

        $count = PlaylistContentUser::where('user_id', $this->user1->id)
            ->where('trainingplan_id', $plan->id)
            ->where('content_id', $content->id)
            ->count();

        $this->assertEquals(1, $count);
    }

    /**
     * 7. Progress 0 migration behavior
     */
    public function test_7_progress_zero_migration_behavior(): void
    {
        $plan = $this->createOldPlaylist('Plan Prog 0');
        $content = $this->createContent('Content Prog 0');
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        ContentUser::create([
            'user_id'          => $this->user1->id,
            'content_id'       => $content->id,
            'progress'         => 0,
            'finish'           => '0',
            'learning_status'  => 'not_started',
        ]);

        $this->artisan('learning:migrate-playlist-progress', ['--playlist' => $plan->id, '--cutoff' => $this->cutoff, '--force' => true])->assertSuccessful();

        $pcu = PlaylistContentUser::where('user_id', $this->user1->id)
            ->where('trainingplan_id', $plan->id)
            ->where('content_id', $content->id)
            ->first();

        $this->assertNotNull($pcu);
        $this->assertEquals(0, $pcu->progress);
        $this->assertEquals('0', $pcu->finish);
        $this->assertEquals('not_started', $pcu->learning_status);
    }

    /**
     * 8. Finished progress migration
     */
    public function test_8_finished_progress_migration(): void
    {
        $plan = $this->createOldPlaylist('Plan Finished');
        $content = $this->createContent('Content Finished');
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        ContentUser::create([
            'user_id'          => $this->user1->id,
            'content_id'       => $content->id,
            'progress'         => 100,
            'finish'           => '1',
            'learning_status'  => 'completed',
        ]);

        $this->artisan('learning:migrate-playlist-progress', ['--playlist' => $plan->id, '--cutoff' => $this->cutoff, '--force' => true])->assertSuccessful();

        $pcu = PlaylistContentUser::where('user_id', $this->user1->id)
            ->where('trainingplan_id', $plan->id)
            ->where('content_id', $content->id)
            ->first();

        $this->assertNotNull($pcu);
        $this->assertEquals(100, $pcu->progress);
        $this->assertEquals('1', $pcu->finish);
        $this->assertEquals('completed', $pcu->learning_status);
    }

    /**
     * 9. User isolation
     */
    public function test_9_user_isolation(): void
    {
        $plan = $this->createOldPlaylist('Plan User Isolation');
        $content = $this->createContent('Content User Isolation');
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        // User 1 finished 100%
        ContentUser::create([
            'user_id'          => $this->user1->id,
            'content_id'       => $content->id,
            'progress'         => 100,
            'finish'           => '1',
            'learning_status'  => 'completed',
        ]);

        // User 2 has 20%
        ContentUser::create([
            'user_id'          => $this->user2->id,
            'content_id'       => $content->id,
            'progress'         => 20,
            'finish'           => '0',
            'learning_status'  => 'in_progress',
        ]);

        $this->artisan('learning:migrate-playlist-progress', ['--playlist' => $plan->id, '--cutoff' => $this->cutoff, '--force' => true])->assertSuccessful();

        $pcuUser1 = PlaylistContentUser::where('user_id', $this->user1->id)
            ->where('trainingplan_id', $plan->id)
            ->where('content_id', $content->id)
            ->first();

        $pcuUser2 = PlaylistContentUser::where('user_id', $this->user2->id)
            ->where('trainingplan_id', $plan->id)
            ->where('content_id', $content->id)
            ->first();

        $this->assertEquals(100, $pcuUser1->progress);
        $this->assertEquals(20, $pcuUser2->progress);
    }

    /**
     * 10. Playlist isolation
     */
    public function test_10_playlist_isolation(): void
    {
        $planA = $this->createOldPlaylist('Plan A Isolation');
        $planB = $this->createOldPlaylist('Plan B Isolation');
        $contentA = $this->createContent('Content A Only');
        $contentB = $this->createContent('Content B Only');

        TrainingPlanDetail::create(['trainingplan_id' => $planA->id, 'content_id' => $contentA->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $planB->id, 'content_id' => $contentB->id, 'content_order' => 0]);

        // User 1 only has progress on Content A
        ContentUser::create([
            'user_id'          => $this->user1->id,
            'content_id'       => $contentA->id,
            'progress'         => 100,
            'finish'           => '1',
            'learning_status'  => 'completed',
        ]);

        $this->artisan('learning:migrate-playlist-progress', ['--playlist' => $planA->id, '--cutoff' => $this->cutoff, '--force' => true])->assertSuccessful();
        $this->artisan('learning:migrate-playlist-progress', ['--playlist' => $planB->id, '--cutoff' => $this->cutoff, '--force' => true])->assertSuccessful();

        $pcuA = PlaylistContentUser::where('user_id', $this->user1->id)
            ->where('trainingplan_id', $planA->id)
            ->where('content_id', $contentA->id)
            ->first();

        $pcuB = PlaylistContentUser::where('user_id', $this->user1->id)
            ->where('trainingplan_id', $planB->id)
            ->where('content_id', $contentB->id)
            ->first();

        $this->assertNotNull($pcuA);
        $this->assertEquals(100, $pcuA->progress);
        $this->assertNull($pcuB, 'User has no CU record for Content B, so no PCU record for Plan B should exist');
    }

    /**
     * 11. Dry-run performs zero mutations
     */
    public function test_11_dry_run_performs_zero_mutations(): void
    {
        $plan = $this->createOldPlaylist('Plan Dry Run');
        $content = $this->createContent('Content Dry Run');
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        ContentUser::create([
            'user_id'          => $this->user1->id,
            'content_id'       => $content->id,
            'progress'         => 100,
            'finish'           => '1',
            'learning_status'  => 'completed',
        ]);

        $countBefore = PlaylistContentUser::count();

        $this->artisan('learning:migrate-playlist-progress', [
            '--dry-run'  => true,
            '--playlist' => $plan->id,
            '--cutoff'   => $this->cutoff,
        ])->assertSuccessful();

        $countAfter = PlaylistContentUser::count();

        $this->assertEquals($countBefore, $countAfter, 'Dry run must not perform any inserts or mutations');
    }

    /**
     * 12. Duplicate content_users prioritizes best progress (finish=1, progress=100)
     */
    public function test_12_duplicate_content_users_prioritizes_best_progress(): void
    {
        $plan = $this->createOldPlaylist('Plan Duplicate Priority Test');
        $content = $this->createContent('Content Duplicate Priority Test');
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        // Older record with lower ID, lower progress, finish = 0
        $olderCu = ContentUser::create([
            'user_id'          => $this->user1->id,
            'content_id'       => $content->id,
            'progress'         => 35,
            'finish'           => '0',
            'learning_status'  => 'in_progress',
            'current_time'     => 120,
            'duration_seconds' => 400,
        ]);

        // Newer record with higher ID, 100% progress, finish = 1
        $newerCu = ContentUser::create([
            'user_id'          => $this->user1->id,
            'content_id'       => $content->id,
            'progress'         => 100,
            'finish'           => '1',
            'learning_status'  => 'completed',
            'current_time'     => 400,
            'duration_seconds' => 400,
        ]);

        $this->assertTrue($olderCu->id < $newerCu->id, 'Precondition: older record has smaller ID than newer record');

        $this->artisan('learning:migrate-playlist-progress', [
            '--playlist' => $plan->id,
            '--cutoff'   => $this->cutoff,
            '--force'    => true,
        ])->assertSuccessful();

        $pcu = PlaylistContentUser::where('user_id', $this->user1->id)
            ->where('trainingplan_id', $plan->id)
            ->where('content_id', $content->id)
            ->first();

        $this->assertNotNull($pcu);
        $this->assertEquals(100, $pcu->progress, 'PCU must receive the best progress (100%) rather than the older record (35%)');
        $this->assertEquals('1', $pcu->finish, 'PCU must receive finish=1');
        $this->assertEquals('completed', $pcu->learning_status);
        $this->assertEquals(400, $pcu->current_time);
    }
}
