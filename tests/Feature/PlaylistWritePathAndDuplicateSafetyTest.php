<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PlaylistWritePathAndDuplicateSafetyTest extends TestCase
{
    use DatabaseTransactions;

    protected User $userA;
    protected User $userB;
    protected Classification $classification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = User::factory()->create([
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-hash-wp-a-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-WP-A-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->userB = User::factory()->create([
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-hash-wp-b-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-WP-B-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->classification = Classification::firstOrCreate(
            ['classification_name' => 'WritePath Safety Classification'],
            ['slug' => 'writepath-safety-classification']
        );
    }

    /**
     * TEST 1: Unique context constraint prevents duplicate (user, trainingplan, content) insertion
     */
    public function test_1_unique_context_constraint_prevents_duplicate_insertion(): void
    {
        $plan = TrainingPlan::create(['title' => 'WP Plan 1', 'slug' => 'wp-plan-1-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create(['title' => 'WP Content 1', 'slug' => 'wp-content-1-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        PlaylistContentUser::create([
            'user_id'         => $this->userA->id,
            'trainingplan_id' => $plan->id,
            'content_id'      => $content->id,
            'progress'        => 50,
            'finish'          => '0',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        // Attempt direct duplicate insertion must be blocked by DB unique index
        PlaylistContentUser::create([
            'user_id'         => $this->userA->id,
            'trainingplan_id' => $plan->id,
            'content_id'      => $content->id,
            'progress'        => 80,
            'finish'          => '0',
        ]);
    }

    /**
     * TEST 2: Repeated player opens (5x) results in exactly 1 PCU record (idempotency)
     */
    public function test_2_repeated_player_opens_creates_exactly_one_pcu_record(): void
    {
        $plan = TrainingPlan::create(['title' => 'Repeated Open Plan', 'slug' => 'rep-open-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create(['title' => 'Rep Open Content', 'slug' => 'rep-open-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        for ($i = 1; $i <= 5; $i++) {
            $response = $this->actingAs($this->userA)->get(route('playlist.user.view', [
                'slug'    => $plan->slug,
                'vidslug' => $content->slug,
            ]));
            $response->assertStatus(200);
        }

        $pcuCount = PlaylistContentUser::where('user_id', $this->userA->id)
            ->where('trainingplan_id', $plan->id)
            ->where('content_id', $content->id)
            ->count();

        $this->assertEquals(1, $pcuCount);
    }

    /**
     * TEST 3: PCU with progress = 0% is preserved and not overwritten on player open
     */
    public function test_3_pcu_zero_percent_is_preserved_on_player_open(): void
    {
        $plan = TrainingPlan::create(['title' => 'Zero Preserve Plan', 'slug' => 'zero-pres-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create(['title' => 'Zero Content', 'slug' => 'zero-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        // Existing PCU with 0%
        $pcu = PlaylistContentUser::create([
            'user_id'         => $this->userA->id,
            'trainingplan_id' => $plan->id,
            'content_id'      => $content->id,
            'progress'        => 0,
            'finish'          => '0',
            'learning_status' => 'not_started',
        ]);

        $response = $this->actingAs($this->userA)->get(route('playlist.user.view', [
            'slug'    => $plan->slug,
            'vidslug' => $content->slug,
        ]));
        $response->assertStatus(200);

        $this->assertEquals(0, $pcu->fresh()->progress);
        $this->assertEquals('0', $pcu->fresh()->finish);
        $this->assertEquals('not_started', $pcu->fresh()->learning_status);
    }

    /**
     * TEST 4: Multi-Playlist update isolation: updating Playlist A does NOT mutate Playlist B
     */
    public function test_4_multi_playlist_update_isolation(): void
    {
        $planA = TrainingPlan::create(['title' => 'Multi Plan A', 'slug' => 'm-plan-a-' . rand(1000, 9999), 'active' => 'Active']);
        $planB = TrainingPlan::create(['title' => 'Multi Plan B', 'slug' => 'm-plan-b-' . rand(1000, 9999), 'active' => 'Active']);

        $content = Content::create(['title' => 'Shared Content', 'slug' => 'shared-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y', 'duration' => '00:10:00']);
        TrainingPlanDetail::create(['trainingplan_id' => $planA->id, 'content_id' => $content->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $planB->id, 'content_id' => $content->id, 'content_order' => 0]);

        $pcuA = PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $planA->id, 'content_id' => $content->id, 'progress' => 30, 'finish' => '0']);
        $pcuB = PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $planB->id, 'content_id' => $content->id, 'progress' => 70, 'finish' => '0']);

        // Update progress on Playlist A only
        $response = $this->actingAs($this->userA)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $planA->id,
            'content_id'       => $content->id,
            'type'             => 'video',
            'current_time'     => 480,
            'duration_seconds' => 600,
            'status'           => 'in_progress',
        ]);
        $response->assertStatus(200);

        // PCU A is updated to 80% (480/600)
        $this->assertEquals(80, $pcuA->fresh()->progress);

        // PCU B remains 100% untouched at 70%
        $this->assertEquals(70, $pcuB->fresh()->progress);
    }

    /**
     * TEST 5: User update isolation: updating User A does NOT mutate User B
     */
    public function test_5_user_update_isolation(): void
    {
        $plan = TrainingPlan::create(['title' => 'User Iso Plan', 'slug' => 'u-iso-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create(['title' => 'User Iso Content', 'slug' => 'u-iso-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        $pcuA = PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $plan->id, 'content_id' => $content->id, 'progress' => 20, 'finish' => '0']);
        $pcuB = PlaylistContentUser::create(['user_id' => $this->userB->id, 'trainingplan_id' => $plan->id, 'content_id' => $content->id, 'progress' => 0, 'finish' => '0']);

        // User A updates progress
        $response = $this->actingAs($this->userA)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $plan->id,
            'content_id'       => $content->id,
            'type'             => 'video',
            'current_time'     => 300,
            'duration_seconds' => 600,
            'status'           => 'in_progress',
        ]);
        $response->assertStatus(200);

        $this->assertEquals(50, $pcuA->fresh()->progress);
        $this->assertEquals(0, $pcuB->fresh()->progress);
    }

    /**
     * TEST 6: Direct Content progress update NEVER touches playlist_content_users
     */
    public function test_6_direct_content_update_does_not_touch_pcu(): void
    {
        $content = Content::create([
            'title'             => 'Direct Only Content',
            'slug'              => 'dir-only-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'N',
            'has_deadline'      => 0,
        ]);

        $initialPcuCount = PlaylistContentUser::count();

        // Direct Content update
        $response = $this->actingAs($this->userA)->postJson(route('content.user.progress.update'), [
            'content_id'       => $content->id,
            'type'             => 'video',
            'current_time'     => 300,
            'duration_seconds' => 600,
            'status'           => 'in_progress',
        ]);
        $response->assertStatus(200);

        // Assert content_users was updated
        $this->assertDatabaseHas('content_users', [
            'user_id'    => $this->userA->id,
            'content_id' => $content->id,
            'progress'   => 50,
        ]);

        // Assert playlist_content_users was NOT mutated
        $this->assertEquals($initialPcuCount, PlaylistContentUser::count());
    }

    /**
     * TEST 7: Playlist progress update rejects content that does not belong to the playlist
     */
    public function test_7_playlist_progress_update_rejects_foreign_content(): void
    {
        $plan = TrainingPlan::create(['title' => 'Strict Plan', 'slug' => 'strict-p-' . rand(1000, 9999), 'active' => 'Active']);
        $foreignContent = Content::create(['title' => 'Foreign Content', 'slug' => 'foreign-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        // NOT added to TrainingPlanDetail for this plan

        $response = $this->actingAs($this->userA)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $plan->id,
            'content_id'       => $foreignContent->id,
            'type'             => 'video',
            'current_time'     => 300,
            'duration_seconds' => 600,
            'status'           => 'in_progress',
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseMissing('playlist_content_users', [
            'user_id'         => $this->userA->id,
            'trainingplan_id' => $plan->id,
            'content_id'      => $foreignContent->id,
        ]);
    }
}
