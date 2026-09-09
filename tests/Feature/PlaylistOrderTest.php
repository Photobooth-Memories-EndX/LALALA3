<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Content;
use App\Models\PlaylistContentUser;
use App\Models\RoleAccessPermission;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlaylistOrderTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected User $unauthorizedUser;
    protected Classification $classification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->classification = Classification::firstOrCreate(
            ['slug' => 'test-classification-pl-order'],
            ['classification_name' => 'Testing Classification PL Order']
        );

        $this->adminUser = User::factory()->create([
            'role'       => User::ROLE_SUPER_ADMIN,
            'team'       => 'Quality Assurance',
            'department' => 'Technology',
            'division'   => 'Engineering',
            'ssohash'    => 'test-admin-order-' . Str::uuid(),
            'sap_code'   => 'SAP-ADMIN-ORDER',
        ]);

        $this->unauthorizedUser = User::factory()->create([
            'role'       => User::ROLE_USER,
            'team'       => 'Operations',
            'department' => 'Manufacturing',
            'division'   => 'Production',
            'ssohash'    => 'test-user-order-noperm-' . Str::uuid(),
            'sap_code'   => 'SAP-USER-ORDER-NOPERM',
        ]);

        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_SUPER_ADMIN, 'module_key' => 'playlist_learning_path'],
            ['can_access' => true]
        );
    }

    protected function createContent(string $title, string $duration = '00:15:00', float $lh = 0.25): Content
    {
        return Content::create([
            'title'             => $title,
            'duration'          => $duration,
            'learning_hours'    => $lh,
            'id_classification' => $this->classification->id,
            'type'              => 'video',
            'active'            => 'Active',
        ]);
    }

    protected function createPlaylist(string $title, string $active = 'Inactive', ?int $duration = null, ?float $lh = null): TrainingPlan
    {
        $plan = TrainingPlan::create([
            'title'                  => $title,
            'active'                 => $active,
            'total_duration_seconds' => $duration ?? 0,
            'learning_hours'         => $lh ?? 0.0,
        ]);

        return $plan;
    }

    /**
     * 1. Authorized user can open ordering page.
     */
    public function test_authorized_user_can_open_ordering_page(): void
    {
        $plan = $this->createPlaylist('Order View Test');

        $response = $this->actingAs($this->adminUser)
            ->get(route('manage.playlist.order', $plan->slug));

        $response->assertStatus(200);
        $response->assertSee('Order Playlist Content');
    }

    /**
     * 2. Unauthenticated user rejected.
     */
    public function test_unauthenticated_user_rejected(): void
    {
        $plan = $this->createPlaylist('Unauth Order Test');

        $response = $this->get(route('manage.playlist.order', $plan->slug));

        $response->assertRedirect('/login');
    }

    /**
     * 3. Unauthorized user gets 403.
     */
    public function test_unauthorized_user_gets_403(): void
    {
        $plan = $this->createPlaylist('Forbidden Order Test');

        $response = $this->actingAs($this->unauthorizedUser)
            ->get(route('manage.playlist.order', $plan->slug));

        $response->assertStatus(403);
    }

    /**
     * 4. Nonexistent playlist returns 404.
     */
    public function test_nonexistent_playlist_returns_404(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('manage.playlist.order', 'nonexistent-playlist-' . Str::random(10)));

        $response->assertStatus(404);
    }

    /**
     * 5. Soft-deleted playlist returns 404.
     */
    public function test_soft_deleted_playlist_returns_404(): void
    {
        $plan = $this->createPlaylist('Soft Deleted Plan');
        $slug = $plan->slug;
        $plan->delete();

        $response = $this->actingAs($this->adminUser)
            ->get(route('manage.playlist.order', $slug));

        $response->assertStatus(404);
    }

    /**
     * 6. Actual playlist title is displayed.
     */
    public function test_actual_playlist_title_is_displayed(): void
    {
        $title = 'Custom Distinct Playlist Title ' . Str::random(5);
        $plan = $this->createPlaylist($title);

        $response = $this->actingAs($this->adminUser)
            ->get(route('manage.playlist.order', $plan->slug));

        $response->assertStatus(200);
        $response->assertSee($title);
    }

    /**
     * 7. Empty playlist works.
     */
    public function test_empty_playlist_works(): void
    {
        $plan = $this->createPlaylist('Empty Materials Plan');

        $response = $this->actingAs($this->adminUser)
            ->get(route('manage.playlist.order', $plan->slug));

        $response->assertStatus(200);
        $response->assertSee('No content has been added to this playlist yet.');
    }

    /**
     * 8. Content order is displayed correctly.
     */
    public function test_content_order_is_displayed_correctly(): void
    {
        $plan = $this->createPlaylist('Ordered Display Plan');
        $c1 = $this->createContent('Alpha Material');
        $c2 = $this->createContent('Beta Material');

        $d1 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        $d2 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('manage.playlist.order', $plan->slug));

        $response->assertStatus(200);
        $response->assertSee('Alpha Material');
        $response->assertSee('Beta Material');
        $response->assertSee('data-detail-id="' . $d1->id . '"', false);
        $response->assertSee('data-detail-id="' . $d2->id . '"', false);
    }

    /**
     * 9. Reorder persists sequential content_order.
     */
    public function test_reorder_persists_sequential_content_order(): void
    {
        $plan = $this->createPlaylist('Reorder Persistence Plan');
        $c1 = $this->createContent('Material 1');
        $c2 = $this->createContent('Material 2');

        $d1 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        $d2 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);

        // Swap order: d2 first, then d1
        $response = $this->actingAs($this->adminUser)
            ->put(route('playlist.update', $plan->slug), [
                'savetype' => 'reorder',
                'order'    => [
                    $d2->id => 0,
                    $d1->id => 1,
                ],
            ]);

        $response->assertRedirect(route('manage.playlist.order', $plan->slug));
        $this->assertEquals(0, (int) $d2->fresh()->content_order);
        $this->assertEquals(1, (int) $d1->fresh()->content_order);
    }

    /**
     * 10. Moving first item to last produces correct sequence.
     */
    public function test_moving_first_item_to_last_produces_correct_sequence(): void
    {
        $plan = $this->createPlaylist('Move First to Last');
        $c1 = $this->createContent('C1');
        $c2 = $this->createContent('C2');
        $c3 = $this->createContent('C3');

        $d1 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        $d2 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);
        $d3 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c3->id, 'content_order' => 2]);

        // C2, C3, C1
        $this->actingAs($this->adminUser)
            ->put(route('playlist.update', $plan->slug), [
                'savetype' => 'reorder',
                'order'    => [
                    $d2->id => 0,
                    $d3->id => 1,
                    $d1->id => 2,
                ],
            ]);

        $this->assertEquals(0, (int) $d2->fresh()->content_order);
        $this->assertEquals(1, (int) $d3->fresh()->content_order);
        $this->assertEquals(2, (int) $d1->fresh()->content_order);
    }

    /**
     * 11. Moving last item to first produces correct sequence.
     */
    public function test_moving_last_item_to_first_produces_correct_sequence(): void
    {
        $plan = $this->createPlaylist('Move Last to First');
        $c1 = $this->createContent('C1');
        $c2 = $this->createContent('C2');
        $c3 = $this->createContent('C3');

        $d1 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        $d2 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);
        $d3 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c3->id, 'content_order' => 2]);

        // C3, C1, C2
        $this->actingAs($this->adminUser)
            ->put(route('playlist.update', $plan->slug), [
                'savetype' => 'reorder',
                'order'    => [
                    $d3->id => 0,
                    $d1->id => 1,
                    $d2->id => 2,
                ],
            ]);

        $this->assertEquals(0, (int) $d3->fresh()->content_order);
        $this->assertEquals(1, (int) $d1->fresh()->content_order);
        $this->assertEquals(2, (int) $d2->fresh()->content_order);
    }

    /**
     * 12. Moving middle item produces correct sequence.
     */
    public function test_moving_middle_item_produces_correct_sequence(): void
    {
        $plan = $this->createPlaylist('Move Middle Item');
        $c1 = $this->createContent('C1');
        $c2 = $this->createContent('C2');
        $c3 = $this->createContent('C3');
        $c4 = $this->createContent('C4');

        $d1 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        $d2 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);
        $d3 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c3->id, 'content_order' => 2]);
        $d4 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c4->id, 'content_order' => 3]);

        // Move C3 to pos 1: C1, C3, C2, C4
        $this->actingAs($this->adminUser)
            ->put(route('playlist.update', $plan->slug), [
                'savetype' => 'reorder',
                'order'    => [
                    $d1->id => 0,
                    $d3->id => 1,
                    $d2->id => 2,
                    $d4->id => 3,
                ],
            ]);

        $this->assertEquals(0, (int) $d1->fresh()->content_order);
        $this->assertEquals(1, (int) $d3->fresh()->content_order);
        $this->assertEquals(2, (int) $d2->fresh()->content_order);
        $this->assertEquals(3, (int) $d4->fresh()->content_order);
    }

    /**
     * 13. Repeated reorder before save works (normalized sequential 0,1,2).
     */
    public function test_repeated_reorder_before_save_works(): void
    {
        $plan = $this->createPlaylist('Repeated Drag Simulation');
        $c1 = $this->createContent('C1');
        $c2 = $this->createContent('C2');
        $c3 = $this->createContent('C3');

        $d1 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        $d2 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);
        $d3 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c3->id, 'content_order' => 2]);

        // Arbitrary monotonic sequences get normalized to 0, 1, 2
        $this->actingAs($this->adminUser)
            ->put(route('playlist.update', $plan->slug), [
                'savetype' => 'reorder',
                'order'    => [
                    $d2->id => 10,
                    $d3->id => 20,
                    $d1->id => 30,
                ],
            ]);

        $this->assertEquals(0, (int) $d2->fresh()->content_order);
        $this->assertEquals(1, (int) $d3->fresh()->content_order);
        $this->assertEquals(2, (int) $d1->fresh()->content_order);
    }

    /**
     * 14. Duplicate sequence values are rejected.
     */
    public function test_duplicate_sequence_values_are_rejected(): void
    {
        $plan = $this->createPlaylist('Dup Seq Test');
        $c1 = $this->createContent('C1');
        $c2 = $this->createContent('C2');

        $d1 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        $d2 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);

        $response = $this->actingAs($this->adminUser)
            ->putJson(route('playlist.update', $plan->slug), [
                'savetype' => 'reorder',
                'order'    => [
                    $d1->id => 0,
                    $d2->id => 0, // Duplicate!
                ],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('order');
    }

    /**
     * 15. Invalid detail IDs are rejected.
     */
    public function test_invalid_detail_ids_are_rejected(): void
    {
        $plan = $this->createPlaylist('Invalid ID Test');

        $response = $this->actingAs($this->adminUser)
            ->putJson(route('playlist.update', $plan->slug), [
                'savetype' => 'reorder',
                'order'    => [
                    '999999' => 0,
                ],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('order');
    }

    /**
     * 16. Cross-playlist detail IDs are rejected (CRITICAL).
     */
    public function test_cross_playlist_detail_ids_are_rejected(): void
    {
        // Playlist A
        $planA = $this->createPlaylist('Plan A');
        $cA1 = $this->createContent('CA1');
        $dA1 = TrainingPlanDetail::create(['trainingplan_id' => $planA->id, 'content_id' => $cA1->id, 'content_order' => 0]);

        // Playlist B
        $planB = $this->createPlaylist('Plan B');
        $cB1 = $this->createContent('CB1');
        $dB1 = TrainingPlanDetail::create(['trainingplan_id' => $planB->id, 'content_id' => $cB1->id, 'content_order' => 0]);

        // Attempt to submit Playlist B's detail ID into Playlist A's reorder
        $response = $this->actingAs($this->adminUser)
            ->putJson(route('playlist.update', $planA->slug), [
                'savetype' => 'reorder',
                'order'    => [
                    $dA1->id => 0,
                    $dB1->id => 1, // Malicious cross-playlist insertion!
                ],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('order');

        // Verify Playlist B detail order remains unchanged
        $this->assertEquals(0, (int) $dB1->fresh()->content_order);
    }

    /**
     * 17. Malformed payload is rejected.
     */
    public function test_malformed_payload_is_rejected(): void
    {
        $plan = $this->createPlaylist('Malformed Payload Plan');

        $response = $this->actingAs($this->adminUser)
            ->putJson(route('playlist.update', $plan->slug), [
                'savetype' => 'reorder',
                'order'    => 'not-an-array',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('order');
    }

    /**
     * 18. Active playlist can reorder content (ACTIVE != ORDERING LOCKED).
     */
    public function test_active_playlist_can_reorder(): void
    {
        $plan = $this->createPlaylist('Active Allowed Plan', 'Active');
        $c1 = $this->createContent('C1');
        $c2 = $this->createContent('C2');
        $d1 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        $d2 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);

        // Swap order on Active playlist: d2 first (0), d1 second (1)
        $response = $this->actingAs($this->adminUser)
            ->putJson(route('playlist.update', $plan->slug), [
                'savetype' => 'reorder',
                'order'    => [
                    $d2->id => 0,
                    $d1->id => 1,
                ],
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status'  => 200,
            'success' => true,
            'message' => 'Playlist order updated successfully.',
        ]);

        // Verify order persisted correctly in database
        $this->assertEquals(0, (int) $d2->fresh()->content_order);
        $this->assertEquals(1, (int) $d1->fresh()->content_order);

        // Verify playlist active status remains untouched
        $this->assertEquals('Active', $plan->fresh()->active);
    }

    /**
     * 19. Inactive playlist can reorder.
     */
    public function test_inactive_playlist_can_reorder(): void
    {
        $plan = $this->createPlaylist('Inactive Allowed Plan', 'Inactive');
        $c1 = $this->createContent('C1');
        $d1 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);

        $response = $this->actingAs($this->adminUser)
            ->putJson(route('playlist.update', $plan->slug), [
                'savetype' => 'reorder',
                'order'    => [
                    $d1->id => 0,
                ],
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Playlist order updated successfully.',
        ]);
    }

    /**
     * 20. total_duration_seconds remains unchanged during reorder.
     */
    public function test_total_duration_seconds_remains_unchanged(): void
    {
        $plan = $this->createPlaylist('Duration Invariant Plan', 'Inactive', 3600);
        $c1 = $this->createContent('C1', '00:15:00');
        $c2 = $this->createContent('C2', '00:45:00');

        $d1 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        $d2 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);

        $this->actingAs($this->adminUser)
            ->put(route('playlist.update', $plan->slug), [
                'savetype' => 'reorder',
                'order'    => [
                    $d2->id => 0,
                    $d1->id => 1,
                ],
            ]);

        $this->assertEquals(3600, $plan->fresh()->total_duration_seconds);
    }

    /**
     * 21. learning_hours remains unchanged during reorder.
     */
    public function test_learning_hours_remains_unchanged(): void
    {
        $plan = $this->createPlaylist('LH Invariant Plan', 'Inactive', 3600, 1.2500);
        $c1 = $this->createContent('C1', '00:15:00', 0.25);
        $c2 = $this->createContent('C2', '00:45:00', 0.75);

        $d1 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        $d2 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);

        $this->actingAs($this->adminUser)
            ->put(route('playlist.update', $plan->slug), [
                'savetype' => 'reorder',
                'order'    => [
                    $d2->id => 0,
                    $d1->id => 1,
                ],
            ]);

        $this->assertEquals(1.2500, (float) $plan->fresh()->learning_hours);
    }

    /**
     * 22. Manually customized duration remains unchanged during reorder.
     */
    public function test_manually_customized_duration_remains_unchanged(): void
    {
        // Custom negotiated duration = 7200s (2 hrs) even though content sum is 1800s
        $plan = $this->createPlaylist('Custom Duration Plan', 'Inactive', 7200);
        $c1 = $this->createContent('C1', '00:10:00');
        $c2 = $this->createContent('C2', '00:20:00');

        $d1 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        $d2 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);

        // Manually customize duration to 7200 seconds after initial content assignment
        TrainingPlan::where('id', $plan->id)->update(['total_duration_seconds' => 7200]);
        $this->assertEquals(7200, $plan->fresh()->total_duration_seconds);

        $this->actingAs($this->adminUser)
            ->put(route('playlist.update', $plan->slug), [
                'savetype' => 'reorder',
                'order'    => [
                    $d2->id => 0,
                    $d1->id => 1,
                ],
            ]);

        $this->assertEquals(7200, $plan->fresh()->total_duration_seconds);
    }

    /**
     * 23. Manually customized learning hours remains unchanged during reorder.
     */
    public function test_manually_customized_learning_hours_remains_unchanged(): void
    {
        $plan = $this->createPlaylist('Custom LH Plan', 'Inactive', 1800, 3.5000);
        $c1 = $this->createContent('C1', '00:10:00', 0.1667);
        $c2 = $this->createContent('C2', '00:20:00', 0.3333);

        $d1 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        $d2 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);

        // Manually customize learning hours to 3.5000 after initial content assignment
        $plan->update(['learning_hours' => 3.5000]);

        $this->actingAs($this->adminUser)
            ->put(route('playlist.update', $plan->slug), [
                'savetype' => 'reorder',
                'order'    => [
                    $d2->id => 0,
                    $d1->id => 1,
                ],
            ]);

        $this->assertEquals(3.5000, (float) $plan->fresh()->learning_hours);
    }

    /**
     * 24. User progress remains unchanged.
     */
    public function test_user_progress_remains_unchanged(): void
    {
        $plan = $this->createPlaylist('Progress Invariant Plan');
        $c1 = $this->createContent('C1', '00:15:00', 0.2500);
        $c2 = $this->createContent('C2', '00:15:00', 0.2500);

        $d1 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        $d2 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);

        $pcu = PlaylistContentUser::create([
            'user_id'          => $this->adminUser->id,
            'trainingplan_id'  => $plan->id,
            'content_id'       => $c1->id,
            'progress'         => 100,
            'finish'           => '1',
            'learning_status'  => 'completed',
            'duration_seconds' => 900,
            'completed_at'     => now(),
            'learning_hour'    => 0.2500,
        ]);

        $this->actingAs($this->adminUser)
            ->put(route('playlist.update', $plan->slug), [
                'savetype' => 'reorder',
                'order'    => [
                    $d2->id => 0,
                    $d1->id => 1,
                ],
            ]);

        $freshPcu = $pcu->fresh();
        $this->assertEquals(100, $freshPcu->progress);
        $this->assertEquals('completed', $freshPcu->learning_status);
        $this->assertEquals('1', (string) $freshPcu->finish);
        $this->assertEquals(0.2500, (float) $freshPcu->learning_hour);
    }

    /**
     * 25. Underlying Content remains unchanged.
     */
    public function test_underlying_content_remains_unchanged(): void
    {
        $plan = $this->createPlaylist('Content Safety Plan');
        $c1 = $this->createContent('Safe Content Item', '00:12:34', 0.2094);
        $d1 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);

        $this->actingAs($this->adminUser)
            ->put(route('playlist.update', $plan->slug), [
                'savetype' => 'reorder',
                'order'    => [
                    $d1->id => 0,
                ],
            ]);

        $freshContent = $c1->fresh();
        $this->assertNotNull($freshContent);
        $this->assertEquals('Safe Content Item', $freshContent->title);
        $this->assertEquals('00:12:34', $freshContent->duration);
        $this->assertEquals(0.2094, (float) $freshContent->learning_hours);
        $this->assertNull($freshContent->deleted_at);
    }

    /**
     * 26. Soft-deleted Content handled safely without blank/500 error.
     */
    public function test_soft_deleted_content_handled_safely(): void
    {
        $plan = $this->createPlaylist('Deleted Content In Plan');
        $c1 = $this->createContent('Available Content');
        $c2 = $this->createContent('Deleted Content');

        $d1 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        $d2 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);

        // Soft delete C2
        $c2->delete();

        $response = $this->actingAs($this->adminUser)
            ->get(route('manage.playlist.order', $plan->slug));

        $response->assertStatus(200);
        $response->assertSee('Available Content');
        $response->assertSee('Unavailable');
    }

    /**
     * 27. Save is transactional.
     */
    public function test_save_is_transactional(): void
    {
        $plan = $this->createPlaylist('Transaction Test Plan');
        $c1 = $this->createContent('C1');
        $c2 = $this->createContent('C2');

        $d1 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        $d2 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);

        // Include invalid foreign ID to force validation failure
        $this->actingAs($this->adminUser)
            ->putJson(route('playlist.update', $plan->slug), [
                'savetype' => 'reorder',
                'order'    => [
                    $d2->id  => 0,
                    '999999' => 1,
                ],
            ]);

        // Initial positions should remain unchanged
        $this->assertEquals(0, (int) $d1->fresh()->content_order);
        $this->assertEquals(1, (int) $d2->fresh()->content_order);
    }

    /**
     * 28. AJAX and normal response work according to implementation.
     */
    public function test_ajax_response_works(): void
    {
        $plan = $this->createPlaylist('Ajax Reorder Plan');
        $c1 = $this->createContent('C1');
        $d1 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);

        $response = $this->actingAs($this->adminUser)
            ->putJson(route('playlist.update', $plan->slug), [
                'savetype' => 'reorder',
                'order'    => [
                    $d1->id => 0,
                ],
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status'  => 200,
            'success' => true,
            'message' => 'Playlist order updated successfully.',
        ]);
    }

    /**
     * 29. Large playlist with 20+ items works.
     */
    public function test_large_playlist_20_plus_items_works(): void
    {
        $plan = $this->createPlaylist('Large Playlist 20 Items');
        $details = [];

        for ($i = 0; $i < 22; $i++) {
            $c = $this->createContent("Material Item {$i}");
            $details[] = TrainingPlanDetail::create([
                'trainingplan_id' => $plan->id,
                'content_id'      => $c->id,
                'content_order'   => $i,
            ]);
        }

        // Reverse the entire array
        $reversed = array_reverse($details);
        $payload = [];
        foreach ($reversed as $index => $d) {
            $payload[$d->id] = $index;
        }

        $response = $this->actingAs($this->adminUser)
            ->putJson(route('playlist.update', $plan->slug), [
                'savetype' => 'reorder',
                'order'    => $payload,
            ]);

        $response->assertStatus(200);

        // Verify the first item in reversed array is now 0
        $this->assertEquals(0, (int) $reversed[0]->fresh()->content_order);
        $this->assertEquals(21, (int) $reversed[21]->fresh()->content_order);
    }

    /**
     * 30. No unused master Content query in order().
     */
    public function test_no_unused_master_content_query(): void
    {
        $plan = $this->createPlaylist('Query Optimization Plan');

        DB::enableQueryLog();

        $this->actingAs($this->adminUser)
            ->get(route('manage.playlist.order', $plan->slug));

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Ensure there is NO query like: select * from `content` where `deleted_at` is null order by `title` asc
        $hasUnusedMasterContentQuery = false;
        foreach ($queries as $q) {
            $sql = strtolower($q['query']);
            if (str_contains($sql, 'from `content`') && str_contains($sql, 'order by `title`')) {
                $hasUnusedMasterContentQuery = true;
                break;
            }
        }

        $this->assertFalse($hasUnusedMasterContentQuery, 'Unused master content query must not be executed in order().');
    }

    /**
     * 31. No N+1 relationship loading in order().
     */
    public function test_no_n_plus_one_relationship_loading(): void
    {
        $plan = $this->createPlaylist('N Plus One Test Plan');

        for ($i = 0; $i < 5; $i++) {
            $c = $this->createContent("N1 Material {$i}");
            TrainingPlanDetail::create([
                'trainingplan_id' => $plan->id,
                'content_id'      => $c->id,
                'content_order'   => $i,
            ]);
        }

        DB::enableQueryLog();

        $this->actingAs($this->adminUser)
            ->get(route('manage.playlist.order', $plan->slug));

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Query count for loading order page with 5 items should be bounded (< 15 queries)
        $this->assertLessThan(15, count($queries));
    }

    /**
     * 32. Successful save returns clear English message.
     */
    public function test_successful_save_returns_clear_english_message(): void
    {
        $plan = $this->createPlaylist('English Message Plan');
        $c1 = $this->createContent('C1');
        $d1 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);

        $response = $this->actingAs($this->adminUser)
            ->put(route('playlist.update', $plan->slug), [
                'savetype' => 'reorder',
                'order'    => [
                    $d1->id => 0,
                ],
            ]);

        $response->assertSessionHas('success', 'Playlist order updated successfully.');
    }

    /**
     * 33. Canonical route name manage.playlist.order produces expected URL.
     */
    public function test_canonical_route_manage_playlist_order_produces_expected_url(): void
    {
        $plan = $this->createPlaylist('Route Verification Plan');
        $expectedUrl = url('/manage/playlist/' . $plan->slug . '/order');

        $this->assertEquals($expectedUrl, route('manage.playlist.order', $plan->slug));
    }

    /**
     * 34. Active playlist order page renders unlocked UI with drag handles and save button.
     */
    public function test_active_playlist_order_page_renders_unlocked_ui(): void
    {
        $plan = $this->createPlaylist('Active Order UI Plan', 'Active');
        $c1 = $this->createContent('C1');
        $d1 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('manage.playlist.order', $plan->slug));

        $response->assertStatus(200);
        $response->assertDontSee('Ordering is locked');
        $response->assertDontSee('Unpublish the playlist before changing the content order');
        $response->assertSee('Save Order');
        $response->assertSee('trainingplan-drag-handle');
    }

    /**
     * 35. Active playlist edit page displays re-order content button.
     */
    public function test_active_playlist_edit_page_displays_reorder_content_button(): void
    {
        $plan = $this->createPlaylist('Active Edit Action Plan', 'Active');

        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.edit', $plan->slug));

        $response->assertStatus(200);
        $response->assertSee(route('manage.playlist.order', $plan->slug));
        $response->assertSee('Re-order Content');
    }
}