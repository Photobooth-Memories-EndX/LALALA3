<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PlaylistTotalDurationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();

        $this->user = User::factory()->create([
            'team'       => 'Quality Assurance',
            'department' => 'Technology',
            'division'   => 'Engineering',
            'ssohash'    => 'test-hash-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-TEST',
            'role'       => 1,
        ]);
        $this->actingAs($this->user);
    }

    /**
     * Test 1 — CREATE MANUAL
     * Admin creates a playlist with manual duration "02:30:00" and learning hours 2.5.
     * Expected: total_duration_seconds = 9000, learning_hours = 2.5
     */
    public function test_create_playlist_manual_duration_and_learning_hours(): void
    {
        $response = $this->post(route('playlist.store'), [
            'title'          => 'Test Playlist Create Manual',
            'total_duration' => '02:30:00',
            'learning_hours' => '2.5',
            'action'         => 'save',
        ]);

        $response->assertSessionHasNoErrors();

        $plan = TrainingPlan::where('title', 'Test Playlist Create Manual')->first();
        $this->assertNotNull($plan);
        $this->assertSame(9000, $plan->total_duration_seconds);
        $this->assertEquals(2.5, $plan->learning_hours);
    }

    /**
     * Test 2 — EDIT SYNC
     * Playlist has contents: 00:30:00 (1800s), 01:00:00 (3600s), 00:45:00 (2700s).
     * Total = 8100s, Duration = "02:15:00", Learning Hours = 2.5.
     */
    public function test_edit_playlist_synchronize_calculates_duration_and_hours(): void
    {
        $c1 = Content::create(['title' => 'C1', 'duration' => '00:30:00', 'active' => 'Active']);
        $c2 = Content::create(['title' => 'C2', 'duration' => '01:00:00', 'active' => 'Active']);
        $c3 = Content::create(['title' => 'C3', 'duration' => '00:45:00', 'active' => 'Active']);

        $plan = TrainingPlan::create([
            'title'                  => 'Test Edit Sync',
            'total_duration_seconds' => 0,
            'learning_hours'         => 0.0,
            'active'                 => 'Active',
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c3->id, 'content_order' => 2]);

        $response = $this->postJson(route('playlist.synchronize-learning-hours', $plan->slug));

        $response->assertStatus(200);
        $response->assertJson([
            'success'                => true,
            'total_duration_seconds' => 8100,
            'duration'               => '02:15:00',
            'learning_hours'         => 2.25,
        ]);

        $plan->refresh();
        $this->assertSame(8100, $plan->total_duration_seconds);
        $this->assertEquals(2.25, $plan->learning_hours);
    }

    /**
     * Test 3 & 4 — EDIT SYNC WITH CONTENT PAYLOAD
     */
    public function test_edit_playlist_synchronize_with_content_payload(): void
    {
        $c1 = Content::create(['title' => 'C1', 'duration' => '00:30:00', 'active' => 'Active']);
        $c2 = Content::create(['title' => 'C2', 'duration' => '01:00:00', 'active' => 'Active']);

        $plan = TrainingPlan::create(['title' => 'Test Content Payload', 'active' => 'Active']);

        // 00:30:00 + 01:00:00 = 5400s (01:30:00), 1.5 learning hours
        $response = $this->postJson(route('playlist.synchronize-learning-hours', $plan->slug), [
            'content' => [$c1->id, $c2->id],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success'                => true,
            'total_duration_seconds' => 5400,
            'duration'               => '01:30:00',
            'learning_hours'         => 1.5,
        ]);
    }

    /**
     * Test 5 — EMPTY PLAYLIST
     */
    public function test_synchronize_empty_playlist(): void
    {
        $plan = TrainingPlan::create([
            'title'                  => 'Empty Playlist',
            'total_duration_seconds' => 500,
            'learning_hours'         => 1.0,
            'active'                 => 'Active',
        ]);

        $response = $this->postJson(route('playlist.synchronize-learning-hours', $plan->slug));

        $response->assertStatus(200);
        $response->assertJson([
            'success'                => true,
            'total_duration_seconds' => 0,
            'duration'               => '00:00:00',
            'learning_hours'         => 0.0,
        ]);
    }

    /**
     * Test 6 — CONTENT WITHOUT DURATION
     */
    public function test_synchronize_content_without_duration(): void
    {
        $c1 = Content::create(['title' => 'C1', 'duration' => '00:30:00', 'active' => 'Active']);
        $c2 = Content::create(['title' => 'C2', 'duration' => null, 'active' => 'Active']);
        $c3 = Content::create(['title' => 'C3', 'duration' => '01:00:00', 'active' => 'Active']);

        $plan = TrainingPlan::create(['title' => 'Playlist Null Duration', 'active' => 'Active']);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c3->id, 'content_order' => 2]);

        $response = $this->postJson(route('playlist.synchronize-learning-hours', $plan->slug));

        $response->assertStatus(200);
        $response->assertJson([
            'success'                => true,
            'total_duration_seconds' => 5400,
            'duration'               => '01:30:00',
            'learning_hours'         => 1.5,
        ]);
    }
}