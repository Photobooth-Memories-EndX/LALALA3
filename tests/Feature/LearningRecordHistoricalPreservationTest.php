<?php

namespace Tests\Feature;

use App\Exports\ExportMyLearningRecord;
use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use App\Services\Learning\CompletedPlaylistService;
use App\Services\Learning\LearningRecordLearningHourService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LearningRecordHistoricalPreservationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Classification $classification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-hist-pres-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-HP-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->classification = Classification::firstOrCreate(
            ['classification_name' => 'Historical Test Category'],
            ['slug' => 'historical-test-category']
        );
    }

    /**
     * Test 1: Completed Direct Content remains visible in Learning Record even if Content is Inactive.
     */
    public function test_1_direct_content_inactive_remains_visible(): void
    {
        $content = Content::create([
            'title'             => 'Direct Content Inactive Test',
            'slug'              => 'dc-inactive-' . rand(1000, 9999),
            'type'              => 'video',
            'duration'          => '00:30:00',
            'classification_id' => $this->classification->id,
            'active'            => 'Inactive', // Inactive!
            'playlistonly'      => 'N',
        ]);

        $cu = ContentUser::create([
            'user_id'          => $this->user->id,
            'content_id'       => $content->id,
            'progress'         => 100,
            'finish'           => '1',
            'duration_seconds' => 1800,
            'completed_at'     => Carbon::parse('2026-08-01 10:00:00'),
        ]);

        $this->actingAs($this->user);

        $response = $this->get(route('user.learning.dashboard.partial', ['tab' => 'record']));
        $response->assertStatus(200);

        $html = $response->getContent();
        $this->assertStringContainsString('Direct Content Inactive Test', $html);
        $this->assertStringContainsString('01/08/26 10:00:00', $html);
    }

    /**
     * Test 2: Completed Direct Content remains visible in Learning Record even if deadline is Expired.
     */
    public function test_2_direct_content_expired_remains_visible(): void
    {
        $content = Content::create([
            'title'             => 'Direct Content Expired Test',
            'slug'              => 'dc-expired-' . rand(1000, 9999),
            'type'              => 'video',
            'duration'          => '00:20:00',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'has_deadline'      => true,
            'from_date'         => Carbon::parse('2026-07-01'),
            'to_date'           => Carbon::parse('2026-07-31'), // Expired!
            'playlistonly'      => 'N',
        ]);

        ContentUser::create([
            'user_id'          => $this->user->id,
            'content_id'       => $content->id,
            'progress'         => 100,
            'finish'           => '1',
            'duration_seconds' => 1200,
            'completed_at'     => Carbon::parse('2026-07-20 15:30:00'),
        ]);

        $this->actingAs($this->user);

        $response = $this->get(route('user.learning.dashboard.partial', ['tab' => 'record']));
        $response->assertStatus(200);

        $html = $response->getContent();
        $this->assertStringContainsString('Direct Content Expired Test', $html);
        $this->assertStringContainsString('20/07/26 15:30:00', $html);
    }

    /**
     * Test 3: Completed Playlist remains visible when Playlist becomes Inactive,
     * with progress 100%, completion timestamp unchanged, and Learning Hours unchanged.
     */
    public function test_3_playlist_inactive_remains_visible_with_unchanged_hours_and_timestamp(): void
    {
        $tCompleted = Carbon::parse('2026-08-15 14:00:00');

        $plan = TrainingPlan::create([
            'title'                  => 'Completed Playlist Inactive Test',
            'slug'                   => 'plan-inactive-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 3600,
            'learning_hours'         => 1.0,
        ]);

        $moduleA = Content::create([
            'title'             => 'Module A',
            'slug'              => 'mod-a-' . rand(1000, 9999),
            'type'              => 'video',
            'duration'          => '00:30:00',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);

        $moduleB = Content::create([
            'title'             => 'Module B',
            'slug'              => 'mod-b-' . rand(1000, 9999),
            'type'              => 'video',
            'duration'          => '00:30:00',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $moduleA->id, 'content_order' => 1]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $moduleB->id, 'content_order' => 2]);

        PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $plan->id,
            'content_id'           => $moduleA->id,
            'progress'             => 100,
            'finish'               => '1',
            'duration_seconds'     => 1800,
            'actual_watch_seconds' => 1800,
            'completed_at'         => Carbon::parse('2026-08-15 12:00:00'),
        ]);

        PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $plan->id,
            'content_id'           => $moduleB->id,
            'progress'             => 100,
            'finish'               => '1',
            'duration_seconds'     => 1800,
            'actual_watch_seconds' => 1800,
            'completed_at'         => $tCompleted,
        ]);

        // Baseline: Verify when Active
        $service = app(CompletedPlaylistService::class);
        $completedBefore = $service->forLearningRecord($this->user->id);
        $this->assertTrue($completedBefore->contains('id', $plan->id));

        $lhService = app(LearningRecordLearningHourService::class);
        $hoursBefore = $lhService->calculatePlaylistSingle($this->user->id, $plan);
        $this->assertEquals(1.0, $hoursBefore);

        // MUTATION: Admin unpublishes / sets Playlist to Inactive
        $plan->update(['active' => 'Inactive']);

        // Assert: forLearningRecord still returns it as completed
        $completedAfter = $service->forLearningRecord($this->user->id);
        $this->assertTrue($completedAfter->contains('id', $plan->id));
        $resolvedPlan = $completedAfter->firstWhere('id', $plan->id);
        $this->assertEquals(100, $resolvedPlan->progress_percent);
        $this->assertEquals($tCompleted->format('Y-m-d H:i:s'), Carbon::parse($resolvedPlan->last_finished_at)->format('Y-m-d H:i:s'));

        // Assert: Learning Hours unchanged
        $hoursAfter = $lhService->calculatePlaylistSingle($this->user->id, $resolvedPlan);
        $this->assertEquals($hoursBefore, $hoursAfter);

        // Assert: UI renders it correctly
        $this->actingAs($this->user);
        $response = $this->get(route('user.learning.dashboard.partial', ['tab' => 'record']));
        $response->assertStatus(200);
        $html = $response->getContent();
        $this->assertStringContainsString('Completed Playlist Inactive Test', $html);
        $this->assertStringContainsString('15/08/26 14:00:00', $html);
    }

    /**
     * Test 4: Playlist Child Becomes Inactive after completion.
     * Playlist still appears, progress remains completed, Learning Hours do not decrease.
     */
    public function test_4_playlist_child_becomes_inactive_remains_completed_with_unchanged_hours(): void
    {
        $tCompleted = Carbon::parse('2026-08-20 16:00:00');

        $plan = TrainingPlan::create([
            'title'                  => 'Child Inactive Playlist Test',
            'slug'                   => 'plan-child-inact-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 5400,
            'learning_hours'         => 1.5,
        ]);

        $mod1 = Content::create([
            'title'             => 'Child Mod 1',
            'slug'              => 'cm-1-' . rand(1000, 9999),
            'type'              => 'video',
            'duration'          => '00:30:00',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);

        $mod2 = Content::create([
            'title'             => 'Child Mod 2',
            'slug'              => 'cm-2-' . rand(1000, 9999),
            'type'              => 'video',
            'duration'          => '01:00:00',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $mod1->id, 'content_order' => 1]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $mod2->id, 'content_order' => 2]);

        PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $plan->id,
            'content_id'           => $mod1->id,
            'progress'             => 100,
            'finish'               => '1',
            'duration_seconds'     => 1800,
            'actual_watch_seconds' => 1800,
            'completed_at'         => Carbon::parse('2026-08-20 15:00:00'),
        ]);

        PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $plan->id,
            'content_id'           => $mod2->id,
            'progress'             => 100,
            'finish'               => '1',
            'duration_seconds'     => 3600,
            'actual_watch_seconds' => 3600,
            'completed_at'         => $tCompleted,
        ]);

        $service = app(CompletedPlaylistService::class);
        $lhService = app(LearningRecordLearningHourService::class);

        $hoursBefore = $lhService->calculatePlaylistSingle($this->user->id, $plan);
        $this->assertEquals(1.5, $hoursBefore);

        // MUTATION: Admin sets Mod 2 to Inactive!
        $mod2->update(['active' => 'Inactive']);

        // Assert: Playlist still resolves as completed in Learning Record
        $completedList = $service->forLearningRecord($this->user->id);
        $this->assertTrue($completedList->contains('id', $plan->id));

        // Assert: Learning hours do NOT decrease from 1.5 to 0.5
        $resolvedPlan = $completedList->firstWhere('id', $plan->id);
        $hoursAfter = $lhService->calculatePlaylistSingle($this->user->id, $resolvedPlan);
        $this->assertEquals(1.5, $hoursAfter);
    }

    /**
     * Test 5: Completed Playlist remains visible when deadline has expired.
     */
    public function test_5_playlist_expired_remains_visible(): void
    {
        $plan = TrainingPlan::create([
            'title'                  => 'Expired Completed Playlist Test',
            'slug'                   => 'plan-exp-' . rand(1000, 9999),
            'active'                 => 'Active',
            'has_deadline'           => true,
            'from_date'              => Carbon::parse('2026-06-01'),
            'to_date'                => Carbon::parse('2026-06-30'), // Expired!
            'total_duration_seconds' => 1800,
            'learning_hours'         => 0.5,
        ]);

        $mod = Content::create([
            'title'             => 'Expired Mod',
            'slug'              => 'exp-mod-' . rand(1000, 9999),
            'type'              => 'video',
            'duration'          => '00:30:00',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $mod->id, 'content_order' => 1]);

        PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $plan->id,
            'content_id'           => $mod->id,
            'progress'             => 100,
            'finish'               => '1',
            'duration_seconds'     => 1800,
            'actual_watch_seconds' => 1800,
            'completed_at'         => Carbon::parse('2026-06-25 10:00:00'),
        ]);

        $this->actingAs($this->user);
        $response = $this->get(route('user.learning.dashboard.partial', ['tab' => 'record']));
        $response->assertStatus(200);

        $html = $response->getContent();
        $this->assertStringContainsString('Expired Completed Playlist Test', $html);
    }

    /**
     * Test 6: CRITICAL — Incomplete Inactive Playlist MUST NOT appear in Learning Record.
     * Including inactive entities must NEVER bypass completion requirements.
     */
    public function test_6_incomplete_inactive_playlist_must_not_appear(): void
    {
        $plan = TrainingPlan::create([
            'title'                  => 'Incomplete Inactive Plan Should Not Appear',
            'slug'                   => 'plan-incomp-inact-' . rand(1000, 9999),
            'active'                 => 'Inactive', // Inactive AND Incomplete!
            'total_duration_seconds' => 3600,
            'learning_hours'         => 1.0,
        ]);

        $modA = Content::create([
            'title'             => 'Incomp Mod A',
            'slug'              => 'inc-mod-a-' . rand(1000, 9999),
            'type'              => 'video',
            'duration'          => '00:30:00',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);

        $modB = Content::create([
            'title'             => 'Incomp Mod B',
            'slug'              => 'inc-mod-b-' . rand(1000, 9999),
            'type'              => 'video',
            'duration'          => '00:30:00',
            'classification_id' => $this->classification->id,
            'active'            => 'Inactive',
            'playlistonly'      => 'Y',
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $modA->id, 'content_order' => 1]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $modB->id, 'content_order' => 2]);

        // User only finished Mod A, did NOT finish Mod B (progress 50%)
        PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $plan->id,
            'content_id'           => $modA->id,
            'progress'             => 100,
            'finish'               => '1',
            'duration_seconds'     => 1800,
            'actual_watch_seconds' => 1800,
            'completed_at'         => Carbon::parse('2026-08-01 10:00:00'),
        ]);

        PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $plan->id,
            'content_id'           => $modB->id,
            'progress'             => 30, // NOT finished!
            'finish'               => '0',
            'duration_seconds'     => 1800,
            'actual_watch_seconds' => 540,
        ]);

        $service = app(CompletedPlaylistService::class);
        $completedList = $service->forLearningRecord($this->user->id);

        // MUST NOT be completed
        $this->assertFalse($completedList->contains('id', $plan->id));

        // UI MUST NOT display it
        $this->actingAs($this->user);
        $response = $this->get(route('user.learning.dashboard.partial', ['tab' => 'record']));
        $response->assertStatus(200);

        $html = $response->getContent();
        $this->assertStringNotContainsString('Incomplete Inactive Plan Should Not Appear', $html);
    }

    /**
     * Test 7: Export consistency — Inactive/expired completed playlist appears in both UI and Excel Export,
     * with matching completion timestamp and learning hours.
     */
    public function test_7_export_consistency(): void
    {
        $plan = TrainingPlan::create([
            'title'                  => 'Export Consistency Inactive Plan',
            'slug'                   => 'plan-exp-cons-' . rand(1000, 9999),
            'active'                 => 'Inactive', // Inactive completed plan
            'total_duration_seconds' => 3600,
            'learning_hours'         => 1.0,
        ]);

        $mod = Content::create([
            'title'             => 'Consistency Module',
            'slug'              => 'cons-mod-' . rand(1000, 9999),
            'type'              => 'video',
            'duration'          => '01:00:00',
            'classification_id' => $this->classification->id,
            'active'            => 'Inactive',
            'playlistonly'      => 'Y',
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $mod->id, 'content_order' => 1]);

        PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $plan->id,
            'content_id'           => $mod->id,
            'progress'             => 100,
            'finish'               => '1',
            'duration_seconds'     => 3600,
            'actual_watch_seconds' => 3600,
            'completed_at'         => Carbon::parse('2026-08-10 11:15:00'),
        ]);

        $this->actingAs($this->user);

        // 1. UI Check
        $response = $this->get(route('user.learning.dashboard.partial', ['tab' => 'record']));
        $response->assertStatus(200);
        $this->assertStringContainsString('Export Consistency Inactive Plan', $response->getContent());

        // 2. Export Check
        $export = new ExportMyLearningRecord();
        $data = $export->array();

        $titles = collect($data)->pluck(1)->filter()->values()->all();
        $this->assertContains('Export Consistency Inactive Plan', $titles);

        // Find exported row for this plan
        $planRow = collect($data)->first(fn ($row) => ($row[1] ?? '') === 'Export Consistency Inactive Plan');
        $this->assertNotNull($planRow);
        $this->assertEquals('Playlist', $planRow[2]); // Type
        $this->assertEquals(1.0, (float) $planRow[6]); // Learning Hour
        $this->assertEquals('100%', $planRow[7]); // Progress
        $this->assertStringContainsString('11:15:00', $planRow[8]); // Date Time column
    }

    /**
     * Test 8: Assessment non-regression — Verify assessment callers continue using default availability
     * behavior and do not receive inactive playlists.
     */
    public function test_8_assessment_callers_retain_availability_behavior(): void
    {
        $planActive = TrainingPlan::create([
            'title'                  => 'Active Completed Plan for Assessment',
            'slug'                   => 'plan-act-ass-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 1800,
        ]);

        $planInactive = TrainingPlan::create([
            'title'                  => 'Inactive Completed Plan for Assessment',
            'slug'                   => 'plan-inact-ass-' . rand(1000, 9999),
            'active'                 => 'Inactive',
            'total_duration_seconds' => 1800,
        ]);

        $modActive = Content::create([
            'title'             => 'Mod For Assessment Active',
            'slug'              => 'mod-ass-act-' . rand(1000, 9999),
            'type'              => 'video',
            'duration'          => '00:30:00',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);

        $modInactive = Content::create([
            'title'             => 'Mod For Assessment Inactive',
            'slug'              => 'mod-ass-inact-' . rand(1000, 9999),
            'type'              => 'video',
            'duration'          => '00:30:00',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $planActive->id, 'content_id' => $modActive->id, 'content_order' => 1]);
        TrainingPlanDetail::create(['trainingplan_id' => $planInactive->id, 'content_id' => $modInactive->id, 'content_order' => 1]);

        PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $planActive->id,
            'content_id'           => $modActive->id,
            'progress'             => 100,
            'finish'               => '1',
            'duration_seconds'     => 1800,
            'actual_watch_seconds' => 1800,
        ]);

        PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $planInactive->id,
            'content_id'           => $modInactive->id,
            'progress'             => 100,
            'finish'               => '1',
            'duration_seconds'     => 1800,
            'actual_watch_seconds' => 1800,
        ]);

        $service = app(CompletedPlaylistService::class);

        // Assessment caller: forUser($this->user->id) with default $includeInactive = false
        $assessmentPlaylists = $service->forUser($this->user->id);
        $this->assertTrue($assessmentPlaylists->contains('id', $planActive->id));
        $this->assertFalse($assessmentPlaylists->contains('id', $planInactive->id));

        // Learning Record caller: forLearningRecord($this->user->id)
        $recordPlaylists = $service->forLearningRecord($this->user->id);
        $this->assertTrue($recordPlaylists->contains('id', $planActive->id));
        $this->assertTrue($recordPlaylists->contains('id', $planInactive->id));
    }

    /**
     * Test 9: Completed Soft Deleted Playlist remains visible as historical record with blocked access,
     * while completely excluded from Assessment callers.
     */
    public function test_9_completed_soft_deleted_playlist_behavior(): void
    {
        $plan = TrainingPlan::create([
            'title'                  => 'Completed Soft Deleted Plan Test',
            'slug'                   => 'plan-soft-del-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 1800,
            'learning_hours'         => 0.5,
        ]);

        $mod = Content::create([
            'title'             => 'Soft Del Mod',
            'slug'              => 'sd-mod-' . rand(1000, 9999),
            'type'              => 'video',
            'duration'          => '00:30:00',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $mod->id, 'content_order' => 1]);

        PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $plan->id,
            'content_id'           => $mod->id,
            'progress'             => 100,
            'finish'               => '1',
            'duration_seconds'     => 1800,
            'actual_watch_seconds' => 1800,
            'completed_at'         => Carbon::parse('2026-08-18 09:00:00'),
        ]);

        // Soft delete the plan!
        $plan->delete();
        $this->assertSoftDeleted('trainingplan', ['id' => $plan->id]);

        $service = app(CompletedPlaylistService::class);

        // Assessment caller: must NOT receive soft deleted plan
        $assessmentPlaylists = $service->forUser($this->user->id);
        $this->assertFalse($assessmentPlaylists->contains('id', $plan->id));

        // Learning Record caller: MUST receive completed soft deleted plan
        $recordPlaylists = $service->forLearningRecord($this->user->id);
        $this->assertTrue($recordPlaylists->contains('id', $plan->id));

        // UI Check: verify it appears in table with blocked access
        $this->actingAs($this->user);
        $response = $this->get(route('user.learning.dashboard.partial', ['tab' => 'record']));
        $response->assertStatus(200);
        $html = $response->getContent();
        $this->assertStringContainsString('Completed Soft Deleted Plan Test', $html);
    }

    /**
     * Test 10: Incomplete Soft Deleted Playlist MUST NOT appear in Learning Record.
     */
    public function test_10_incomplete_soft_deleted_playlist_must_not_appear(): void
    {
        $plan = TrainingPlan::create([
            'title'                  => 'Incomplete Soft Deleted Plan',
            'slug'                   => 'incomp-soft-del-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 1800,
            'learning_hours'         => 0.5,
        ]);

        $mod = Content::create([
            'title'             => 'Incomp Mod For Soft Del',
            'slug'              => 'inc-sd-mod-' . rand(1000, 9999),
            'type'              => 'video',
            'duration'          => '00:30:00',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $mod->id, 'content_order' => 1]);

        // Incomplete progress
        PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $plan->id,
            'content_id'           => $mod->id,
            'progress'             => 40,
            'finish'               => '0',
            'duration_seconds'     => 1800,
            'actual_watch_seconds' => 720,
        ]);

        // Soft delete the plan
        $plan->delete();

        $service = app(CompletedPlaylistService::class);
        $recordPlaylists = $service->forLearningRecord($this->user->id);
        $this->assertFalse($recordPlaylists->contains('id', $plan->id));

        // UI Check
        $this->actingAs($this->user);
        $response = $this->get(route('user.learning.dashboard.partial', ['tab' => 'record']));
        $response->assertStatus(200);
        $this->assertStringNotContainsString('Incomplete Soft Deleted Plan', $response->getContent());
    }
}

