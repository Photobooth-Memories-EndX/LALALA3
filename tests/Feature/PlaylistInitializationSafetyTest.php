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

class PlaylistInitializationSafetyTest extends TestCase
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
            'ssohash'    => 'test-hash-safety-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-SAFE-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->classification = Classification::firstOrCreate(
            ['classification_name' => 'Safety Audit Classification'],
            ['slug' => 'safety-audit-classification']
        );
    }

    /**
     * CASE 1 — Untouched Playlist Initialization
     * Given CU = 80%, No PCU.
     * When opened, PCU is initialized with 0% (clean isolation, no runtime fallback).
     */
    public function test_case_1_untouched_playlist_initialization_starts_at_zero(): void
    {
        $plan = TrainingPlan::create(['title' => 'Case 1 Plan', 'slug' => 'case-1-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create(['title' => 'Case 1 Content', 'slug' => 'case-1-content-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        ContentUser::create([
            'user_id'          => $this->user->id,
            'content_id'       => $content->id,
            'progress'         => 80,
            'finish'           => '0',
            'learning_status'  => 'in_progress',
            'current_time'     => 480,
            'duration_seconds' => 600,
        ]);

        $response = $this->actingAs($this->user)->get(route('playlist.user.view', [
            'slug'    => $plan->slug,
            'vidslug' => $content->slug,
        ]));
        $response->assertStatus(200);

        $this->assertDatabaseHas('playlist_content_users', [
            'user_id'         => $this->user->id,
            'trainingplan_id' => $plan->id,
            'content_id'      => $content->id,
            'progress'        => 0,
            'finish'          => '0',
        ]);
    }

    /**
     * CASE 2 — Authoritative PCU Must Win (PCU = 25%, CU = 80%)
     * When opened, PCU must remain 25% and must NOT be overwritten to 80%.
     */
    public function test_case_2_authoritative_pcu_must_win(): void
    {
        $plan = TrainingPlan::create(['title' => 'Case 2 Plan', 'slug' => 'case-2-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create(['title' => 'Case 2 Content', 'slug' => 'case-2-content-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        // Historical 80% in CU
        ContentUser::create([
            'user_id'    => $this->user->id,
            'content_id' => $content->id,
            'progress'   => 80,
            'finish'     => '0',
        ]);

        // Authoritative 25% in PCU
        $pcu = PlaylistContentUser::create([
            'user_id'         => $this->user->id,
            'trainingplan_id' => $plan->id,
            'content_id'      => $content->id,
            'progress'        => 25,
            'finish'          => '0',
            'learning_status' => 'in_progress',
        ]);

        $response = $this->actingAs($this->user)->get(route('playlist.user.view', [
            'slug'    => $plan->slug,
            'vidslug' => $content->slug,
        ]));
        $response->assertStatus(200);

        // Assert DB record has NOT changed
        $this->assertDatabaseHas('playlist_content_users', [
            'id'       => $pcu->id,
            'progress' => 25,
            'finish'   => '0',
        ]);

        $this->assertDatabaseMissing('playlist_content_users', [
            'id'       => $pcu->id,
            'progress' => 80,
        ]);
    }

    /**
     * CASE 3 — Completed PCU Must Win (PCU = 100%, CU = 50%)
     * Opening the page must not downgrade or replace completed PCU.
     */
    public function test_case_3_completed_pcu_must_win(): void
    {
        $plan = TrainingPlan::create(['title' => 'Case 3 Plan', 'slug' => 'case-3-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create(['title' => 'Case 3 Content', 'slug' => 'case-3-content-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        ContentUser::create([
            'user_id'    => $this->user->id,
            'content_id' => $content->id,
            'progress'   => 50,
            'finish'     => '0',
        ]);

        $pcu = PlaylistContentUser::create([
            'user_id'         => $this->user->id,
            'trainingplan_id' => $plan->id,
            'content_id'      => $content->id,
            'progress'        => 100,
            'finish'          => '1',
            'learning_status' => 'completed',
        ]);

        $response = $this->actingAs($this->user)->get(route('playlist.user.view', [
            'slug'    => $plan->slug,
            'vidslug' => $content->slug,
        ]));
        $response->assertStatus(200);

        $this->assertDatabaseHas('playlist_content_users', [
            'id'       => $pcu->id,
            'progress' => 100,
            'finish'   => '1',
        ]);
    }

    /**
     * CASE 4 — Multi-Playlist Ambiguity (Category B)
     * Content X belongs to Plan A and Plan B. CU = 80%. No PCU.
     * When opening Plan A, verify exact behavior (creates PCU with progress = 0).
     */
    public function test_case_4_multi_playlist_ambiguity_behavior(): void
    {
        $planA = TrainingPlan::create(['title' => 'Case 4 Plan A', 'slug' => 'case-4-plan-a-' . rand(1000, 9999), 'active' => 'Active']);
        $planB = TrainingPlan::create(['title' => 'Case 4 Plan B', 'slug' => 'case-4-plan-b-' . rand(1000, 9999), 'active' => 'Active']);

        $content = Content::create(['title' => 'Case 4 Shared Content', 'slug' => 'case-4-shared-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $planA->id, 'content_id' => $content->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $planB->id, 'content_id' => $content->id, 'content_order' => 0]);

        ContentUser::create([
            'user_id'    => $this->user->id,
            'content_id' => $content->id,
            'progress'   => 80,
            'finish'     => '0',
        ]);

        $response = $this->actingAs($this->user)->get(route('playlist.user.view', [
            'slug'    => $planA->slug,
            'vidslug' => $content->slug,
        ]));
        $response->assertStatus(200);

        // When opened, firstOrCreate initializes a new PCU row with progress = 0 (because resolver returns ambiguous/0)
        $this->assertDatabaseHas('playlist_content_users', [
            'user_id'         => $this->user->id,
            'trainingplan_id' => $planA->id,
            'content_id'      => $content->id,
            'progress'        => 0,
            'finish'          => '0',
        ]);

        // Plan B remains untouched
        $this->assertDatabaseMissing('playlist_content_users', [
            'user_id'         => $this->user->id,
            'trainingplan_id' => $planB->id,
            'content_id'      => $content->id,
        ]);
    }

    /**
     * CASE 5 — Duplicate Protection on Multiple Opens
     * Opening the same playlist content multiple times must NOT create duplicate records.
     */
    public function test_case_5_duplicate_protection_on_multiple_opens(): void
    {
        $plan = TrainingPlan::create(['title' => 'Case 5 Plan', 'slug' => 'case-5-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create(['title' => 'Case 5 Content', 'slug' => 'case-5-content-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        // Open 1st time
        $this->actingAs($this->user)->get(route('playlist.user.view', ['slug' => $plan->slug, 'vidslug' => $content->slug]))->assertStatus(200);
        $countAfter1 = PlaylistContentUser::where('user_id', $this->user->id)->where('trainingplan_id', $plan->id)->where('content_id', $content->id)->count();
        $this->assertEquals(1, $countAfter1);

        // Open 2nd time
        $this->actingAs($this->user)->get(route('playlist.user.view', ['slug' => $plan->slug, 'vidslug' => $content->slug]))->assertStatus(200);
        $countAfter2 = PlaylistContentUser::where('user_id', $this->user->id)->where('trainingplan_id', $plan->id)->where('content_id', $content->id)->count();
        $this->assertEquals(1, $countAfter2);

        // Open 3rd time
        $this->actingAs($this->user)->get(route('playlist.user.view', ['slug' => $plan->slug, 'vidslug' => $content->slug]))->assertStatus(200);
        $countAfter3 = PlaylistContentUser::where('user_id', $this->user->id)->where('trainingplan_id', $plan->id)->where('content_id', $content->id)->count();
        $this->assertEquals(1, $countAfter3);
    }

    /**
     * CASE 6 — No Progress Regression across Variations
     * (PCU = 70%, CU = 30% -> stays 70%) & (PCU = 30%, CU = 70% -> stays 30%)
     */
    public function test_case_6_no_progress_regression(): void
    {
        $plan = TrainingPlan::create(['title' => 'Case 6 Plan', 'slug' => 'case-6-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $c1 = Content::create(['title' => 'Case 6 C1', 'slug' => 'case-6-c1-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        $c2 = Content::create(['title' => 'Case 6 C2', 'slug' => 'case-6-c2-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);

        // C1: PCU = 70%, CU = 30%
        ContentUser::create(['user_id' => $this->user->id, 'content_id' => $c1->id, 'progress' => 30]);
        PlaylistContentUser::create(['user_id' => $this->user->id, 'trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'progress' => 70]);

        // C2: PCU = 30%, CU = 70%
        ContentUser::create(['user_id' => $this->user->id, 'content_id' => $c2->id, 'progress' => 70]);
        PlaylistContentUser::create(['user_id' => $this->user->id, 'trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'progress' => 30]);

        $res1 = $this->actingAs($this->user)->get(route('playlist.user.view', ['slug' => $plan->slug, 'vidslug' => $c1->slug]));
        $res1->assertStatus(200);
        $res1->assertViewHas('progressByContentId', function ($map) use ($c1) {
            return ($map[$c1->id] ?? null) === 70;
        });

        $res2 = $this->actingAs($this->user)->get(route('playlist.user.view', ['slug' => $plan->slug, 'vidslug' => $c2->slug]));
        $res2->assertStatus(200);
        $res2->assertViewHas('progressByContentId', function ($map) use ($c2) {
            return ($map[$c2->id] ?? null) === 30;
        });
    }

    /**
     * CASE 7 — Direct Content Regression
     * /content/{slug} still uses content_users and does not touch playlist_content_users
     */
    public function test_case_7_direct_content_regression(): void
    {
        $directContent = Content::create([
            'title'             => 'Case 7 Direct Content',
            'slug'              => 'case-7-direct-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $res = $this->actingAs($this->user)->get(route('content.user.view', $directContent->slug));
        $res->assertStatus(200);

        // Update direct content progress
        $updateRes = $this->actingAs($this->user)->post(route('content.user.progress.update'), [
            'content_id'       => $directContent->id,
            'type'             => 'video',
            'current_time'     => 120,
            'duration_seconds' => 600,
            'status'           => 'in_progress',
        ]);
        $updateRes->assertStatus(200);

        // Must exist in content_users
        $this->assertDatabaseHas('content_users', [
            'user_id'    => $this->user->id,
            'content_id' => $directContent->id,
        ]);

        // Must NOT exist in playlist_content_users
        $this->assertDatabaseMissing('playlist_content_users', [
            'user_id'    => $this->user->id,
            'content_id' => $directContent->id,
        ]);
    }
}
