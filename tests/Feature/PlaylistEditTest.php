<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Content;
use App\Models\RoleAccessPermission;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PlaylistEditTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected User $unauthorizedUser;
    protected Classification $classification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->classification = Classification::firstOrCreate(
            ['slug' => 'test-classification-pl-edit'],
            ['classification_name' => 'Testing Classification PL Edit']
        );

        $this->adminUser = User::factory()->create([
            'role'       => User::ROLE_SUPER_ADMIN,
            'team'       => 'Quality Assurance',
            'department' => 'Technology',
            'division'   => 'Engineering',
            'ssohash'    => 'test-admin-edit-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-ADMIN-EDIT',
        ]);

        $this->unauthorizedUser = User::factory()->create([
            'role'       => User::ROLE_USER,
            'team'       => 'Operations',
            'department' => 'Manufacturing',
            'division'   => 'Production',
            'ssohash'    => 'test-user-edit-noperm-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-USER-EDIT-NOPERM',
        ]);

        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_SUPER_ADMIN, 'module_key' => 'playlist_learning_path'],
            ['can_access' => true]
        );
    }

    /**
     * Test 1 — Active playlist displays duration as locked.
     */
    public function test_active_playlist_displays_duration_as_locked(): void
    {
        $plan = TrainingPlan::create([
            'title'                  => 'Active Locked Duration Plan',
            'active'                 => 'Active',
            'total_duration_seconds' => 906,
            'learning_hours'         => 0.2517,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.edit', $plan->slug));

        $response->assertStatus(200);
        $response->assertSee('00:15:06');
        $response->assertSee('Automatically calculated from the selected playlist content. Unpublish the playlist to edit this value manually.');
    }

    /**
     * Test 2 — Active playlist displays learning hours as locked.
     */
    public function test_active_playlist_displays_learning_hours_as_locked(): void
    {
        $plan = TrainingPlan::create([
            'title'                  => 'Active Locked LH Plan',
            'active'                 => 'Active',
            'total_duration_seconds' => 906,
            'learning_hours'         => 0.2517,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.edit', $plan->slug));

        $response->assertStatus(200);
        $response->assertSee('0.2517');
        $response->assertSee('Calculated based on playlist duration. Unpublish the playlist to edit this value manually.');
    }

    /**
     * Test 3 — Active playlist cannot manually modify duration through HTTP.
     */
    public function test_active_playlist_cannot_manually_modify_duration_through_http(): void
    {
        $plan = TrainingPlan::create([
            'title'                  => 'Active Locked Security Plan',
            'active'                 => 'Active',
            'total_duration_seconds' => 906,
            'learning_hours'         => 0.2517,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->put(route('playlist.update', $plan->slug), [
                'title'          => 'Tampered Title',
                'total_duration' => '05:00:00',
                'learning_hours' => '5.0000',
                'action'         => 'draft',
            ]);

        $response->assertRedirect(route('playlist.edit', $plan->slug));
        $response->assertSessionHas('warning');

        $plan->refresh();
        $this->assertSame(906, $plan->total_duration_seconds);
        $this->assertEquals(0.2517, $plan->learning_hours);
        $this->assertSame('Active', $plan->active);
    }

    /**
     * Test 4 — Active playlist cannot manually modify learning hours through HTTP.
     */
    public function test_active_playlist_cannot_manually_modify_learning_hours_through_http(): void
    {
        $plan = TrainingPlan::create([
            'title'                  => 'Active Locked Security Plan 2',
            'active'                 => 'Active',
            'total_duration_seconds' => 906,
            'learning_hours'         => 0.2517,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->put(route('playlist.update', $plan->slug), [
                'title'          => 'Tampered Title 2',
                'learning_hours' => '9.9999',
                'action'         => 'publish',
            ]);

        $response->assertRedirect(route('playlist.edit', $plan->slug));
        $response->assertSessionHas('warning');

        $plan->refresh();
        $this->assertEquals(0.2517, $plan->learning_hours);
        $this->assertSame('Active', $plan->active);
    }

    /**
     * Test 5 — Inactive playlist can edit duration.
     */
    public function test_inactive_playlist_can_edit_duration(): void
    {
        $plan = TrainingPlan::create([
            'title'                  => 'Draft Editable Duration Plan',
            'active'                 => 'Inactive',
            'total_duration_seconds' => 1200,
            'learning_hours'         => 0.3333,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.edit', $plan->slug));

        $response->assertStatus(200);
        $response->assertSee('00:20:00');
        $response->assertSee('placeholder="HH:MM:SS"', false);
        $response->assertSee('Format: <code>HH:MM:SS</code>. Enter duration manually or click Synchronize', false);
    }

    /**
     * Test 6 — Inactive playlist can edit learning hours.
     */
    public function test_inactive_playlist_can_edit_learning_hours(): void
    {
        $plan = TrainingPlan::create([
            'title'                  => 'Draft Editable LH Plan',
            'active'                 => 'Inactive',
            'total_duration_seconds' => 1200,
            'learning_hours'         => 0.3333,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.edit', $plan->slug));

        $response->assertStatus(200);
        $response->assertSee('0.3333');
        $response->assertSee('step="0.0001"', false);
        $response->assertSee('Calculated using: <code>round(total seconds / 3600, 4)</code>. Enter manually or click Synchronize.', false);
    }

    /**
     * Test 7 — Duration HH:MM:SS converts correctly to integer seconds.
     */
    public function test_duration_hh_mm_ss_converts_correctly(): void
    {
        $plan = TrainingPlan::create([
            'title'                  => 'Convert Seconds Plan',
            'active'                 => 'Inactive',
            'total_duration_seconds' => 0,
        ]);

        // 01:30:15 = 1*3600 + 30*60 + 15 = 5415 seconds
        $response = $this->actingAs($this->adminUser)
            ->put(route('playlist.update', $plan->slug), [
                'title'          => 'Convert Seconds Plan Updated',
                'total_duration' => '01:30:15',
                'learning_hours' => '1.5042',
                'action'         => 'draft',
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(5415, $plan->fresh()->total_duration_seconds);
    }

    /**
     * Test 8 — Invalid duration format is rejected with validation error.
     */
    public function test_invalid_duration_is_rejected(): void
    {
        $plan = TrainingPlan::create([
            'title'  => 'Invalid Duration Plan',
            'active' => 'Inactive',
        ]);

        // Missing seconds: 1:30
        $response1 = $this->actingAs($this->adminUser)
            ->put(route('playlist.update', $plan->slug), [
                'title'          => 'Invalid Duration Plan',
                'total_duration' => '1:30',
                'action'         => 'draft',
            ]);
        $response1->assertSessionHasErrors('total_duration');

        // Invalid minutes: 01:60:00
        $response2 = $this->actingAs($this->adminUser)
            ->put(route('playlist.update', $plan->slug), [
                'title'          => 'Invalid Duration Plan',
                'total_duration' => '01:60:00',
                'action'         => 'draft',
            ]);
        $response2->assertSessionHasErrors('total_duration');

        // Text string: abc
        $response3 = $this->actingAs($this->adminUser)
            ->put(route('playlist.update', $plan->slug), [
                'title'          => 'Invalid Duration Plan',
                'total_duration' => 'abc',
                'action'         => 'draft',
            ]);
        $response3->assertSessionHasErrors('total_duration');
    }

    /**
     * Test 9 — Learning hours accepts 0.2517 accurately.
     */
    public function test_learning_hours_accepts_0_2517(): void
    {
        $plan = TrainingPlan::create([
            'title'  => 'LH 0.2517 Plan',
            'active' => 'Inactive',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->put(route('playlist.update', $plan->slug), [
                'title'          => 'LH 0.2517 Plan Updated',
                'total_duration' => '00:15:06',
                'learning_hours' => '0.2517',
                'action'         => 'draft',
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals(0.2517, $plan->fresh()->learning_hours);
    }

    /**
     * Test 10 — Learning hours accepts 4 decimal places.
     */
    public function test_learning_hours_accepts_four_decimal_places(): void
    {
        $plan = TrainingPlan::create([
            'title'  => 'LH 4 Decimals Plan',
            'active' => 'Inactive',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->put(route('playlist.update', $plan->slug), [
                'title'          => 'LH 4 Decimals Plan Updated',
                'total_duration' => '01:30:15',
                'learning_hours' => '1.5042',
                'action'         => 'draft',
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals(1.5042, $plan->fresh()->learning_hours);
    }

    /**
     * Test 11 — Learning hours does not require 0.5 increments.
     */
    public function test_learning_hours_does_not_require_half_hour_increments(): void
    {
        $plan = TrainingPlan::create([
            'title'  => 'No Half Hour Req Plan',
            'active' => 'Inactive',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->put(route('playlist.update', $plan->slug), [
                'title'          => 'No Half Hour Req Plan Updated',
                'total_duration' => '01:45:00',
                'learning_hours' => '1.7500',
                'action'         => 'draft',
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals(1.7500, $plan->fresh()->learning_hours);
    }

    /**
     * Test 12 — Manual duration is persisted.
     */
    public function test_manual_duration_is_persisted(): void
    {
        $plan = TrainingPlan::create([
            'title'                  => 'Manual Duration Plan',
            'active'                 => 'Inactive',
            'total_duration_seconds' => 600,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->put(route('playlist.update', $plan->slug), [
                'title'          => 'Manual Duration Plan Updated',
                'total_duration' => '02:30:00',
                'learning_hours' => '2.5000',
                'action'         => 'draft',
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(9000, $plan->fresh()->total_duration_seconds);
    }

    /**
     * Test 13 — Manual learning hours is persisted.
     */
    public function test_manual_learning_hours_is_persisted(): void
    {
        $plan = TrainingPlan::create([
            'title'          => 'Manual LH Plan',
            'active'         => 'Inactive',
            'learning_hours' => 0.0,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->put(route('playlist.update', $plan->slug), [
                'title'          => 'Manual LH Plan Updated',
                'total_duration' => '01:15:00',
                'learning_hours' => '1.2500',
                'action'         => 'draft',
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals(1.2500, $plan->fresh()->learning_hours);
    }

    /**
     * Test 14 — Manual duration is not overwritten during normal save.
     */
    public function test_manual_duration_is_not_overwritten_during_normal_save(): void
    {
        $c1 = Content::create(['title' => 'Content 1', 'duration' => '00:10:00', 'active' => 'Active', 'classification_id' => $this->classification->id]);
        $c2 = Content::create(['title' => 'Content 2', 'duration' => '00:20:00', 'active' => 'Active', 'classification_id' => $this->classification->id]);

        $plan = TrainingPlan::create([
            'title'                  => 'Preserve Manual Duration Plan',
            'active'                 => 'Inactive',
            'total_duration_seconds' => 1800,
            'learning_hours'         => 0.5000,
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);

        // Materials sum to 1800s, but admin intentionally overrides duration to 03:00:00 (10800s)
        $response = $this->actingAs($this->adminUser)
            ->put(route('playlist.update', $plan->slug), [
                'title'          => 'Preserve Manual Duration Plan Updated',
                'total_duration' => '03:00:00',
                'learning_hours' => '3.0000',
                'content'        => [$c1->id, $c2->id],
                'action'         => 'draft',
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(10800, $plan->fresh()->total_duration_seconds);
    }

    /**
     * Test 15 — Manual learning hours is not overwritten during normal save.
     */
    public function test_manual_learning_hours_is_not_overwritten_during_normal_save(): void
    {
        $c1 = Content::create(['title' => 'Content 1', 'duration' => '00:10:00', 'active' => 'Active', 'classification_id' => $this->classification->id]);
        $c2 = Content::create(['title' => 'Content 2', 'duration' => '00:20:00', 'active' => 'Active', 'classification_id' => $this->classification->id]);

        $plan = TrainingPlan::create([
            'title'                  => 'Preserve Manual LH Plan',
            'active'                 => 'Inactive',
            'total_duration_seconds' => 1800,
            'learning_hours'         => 0.5000,
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);

        // Materials calculate to 0.5h, but admin manually overrides LH to 2.7500
        $response = $this->actingAs($this->adminUser)
            ->put(route('playlist.update', $plan->slug), [
                'title'          => 'Preserve Manual LH Plan Updated',
                'total_duration' => '00:30:00',
                'learning_hours' => '2.7500',
                'content'        => [$c1->id, $c2->id],
                'action'         => 'draft',
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals(2.7500, $plan->fresh()->learning_hours);
    }

    /**
     * Test 16 — Synchronize still recalculates both values.
     */
    public function test_synchronize_still_recalculates_both_values(): void
    {
        $c1 = Content::create(['title' => 'Sync C1', 'duration' => '00:20:00', 'active' => 'Active', 'classification_id' => $this->classification->id]);
        $c2 = Content::create(['title' => 'Sync C2', 'duration' => '00:40:00', 'active' => 'Active', 'classification_id' => $this->classification->id]);

        $plan = TrainingPlan::create([
            'title'                  => 'Sync Recalculate Plan',
            'active'                 => 'Inactive',
            'total_duration_seconds' => 0,
            'learning_hours'         => 0.0,
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);

        // Call synchronize endpoint
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('playlist.synchronize-learning-hours', $plan->slug));

        $response->assertStatus(200);
        $response->assertJson([
            'success'                => true,
            'total_duration_seconds' => 3600,
            'duration'               => '01:00:00',
            'learning_hours'         => 1.0,
        ]);
    }

    /**
     * Test 17 — Synchronize uses current TomSelect content IDs.
     */
    public function test_synchronize_uses_current_tomselect_content_ids(): void
    {
        $c1 = Content::create(['title' => 'TS C1', 'duration' => '00:15:00', 'active' => 'Active', 'classification_id' => $this->classification->id]);
        $c2 = Content::create(['title' => 'TS C2', 'duration' => '00:45:00', 'active' => 'Active', 'classification_id' => $this->classification->id]);

        $plan = TrainingPlan::create([
            'title'  => 'Sync TomSelect Plan',
            'active' => 'Inactive',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('playlist.synchronize-learning-hours', $plan->slug), [
                'content' => [$c1->id, $c2->id],
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success'                => true,
            'total_duration_seconds' => 3600,
            'duration'               => '01:00:00',
            'learning_hours'         => 1.0,
        ]);
    }

    /**
     * Test 18 — Synchronize does not trigger twice (namespaced event and loading check).
     */
    public function test_synchronize_does_not_trigger_twice(): void
    {
        $plan = TrainingPlan::create([
            'title'  => 'Sync Double Trigger Plan',
            'active' => 'Inactive',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.edit', $plan->slug));

        $response->assertStatus(200);
        $response->assertSee('click.playlistSync');
        $response->assertSee('$btn.data(\'loading\', true)', false);
    }

    /**
     * Test 19 — Unpublish allows editing.
     */
    public function test_unpublish_allows_editing(): void
    {
        $plan = TrainingPlan::create([
            'title'                  => 'Unpublish Allows Edit Plan',
            'active'                 => 'Active',
            'total_duration_seconds' => 906,
            'learning_hours'         => 0.2517,
        ]);

        // 1. Unpublish
        $unpublishResponse = $this->actingAs($this->adminUser)
            ->put(route('playlist.update', $plan->slug), [
                'action' => 'unpublish',
            ]);

        $unpublishResponse->assertRedirect(route('playlist.edit', $plan->slug));
        $this->assertSame('Inactive', $plan->fresh()->active);

        // 2. Following GET request renders editable form
        $editResponse = $this->actingAs($this->adminUser)
            ->get(route('playlist.edit', $plan->slug));

        $editResponse->assertStatus(200);
        $editResponse->assertSee('Playlist is Currently Unpublished (Draft Mode)');
        $editResponse->assertSee('Save as Draft');
        $editResponse->assertSee('Save &amp; Publish', false);
    }

    /**
     * Test 20 — Save after unpublish persists changes.
     */
    public function test_save_after_unpublish_persists_changes(): void
    {
        $plan = TrainingPlan::create([
            'title'                  => 'To Be Unpublished And Saved',
            'active'                 => 'Active',
            'total_duration_seconds' => 906,
            'learning_hours'         => 0.2517,
        ]);

        // Unpublish
        $this->actingAs($this->adminUser)
            ->put(route('playlist.update', $plan->slug), ['action' => 'unpublish']);

        $this->assertSame('Inactive', $plan->fresh()->active);

        // Now save new manual values
        $response = $this->actingAs($this->adminUser)
            ->put(route('playlist.update', $plan->slug), [
                'title'          => 'Unpublished And Saved Title',
                'total_duration' => '02:00:00',
                'learning_hours' => '2.0000',
                'action'         => 'draft',
            ]);

        $response->assertSessionHasNoErrors();
        $plan->refresh();
        $this->assertSame('Unpublished And Saved Title', $plan->title);
        $this->assertSame(7200, $plan->total_duration_seconds);
        $this->assertEquals(2.0000, $plan->learning_hours);
    }

    /**
     * Test 21 — Existing publish workflow still works.
     */
    public function test_existing_publish_workflow_still_works(): void
    {
        $plan = TrainingPlan::create([
            'title'  => 'Draft to Publish Plan',
            'active' => 'Inactive',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->put(route('playlist.update', $plan->slug), [
                'title'          => 'Published Successfully Plan',
                'total_duration' => '01:00:00',
                'learning_hours' => '1.0000',
                'action'         => 'publish',
            ]);

        $this->assertSame('Active', $plan->fresh()->active);
        $this->assertSame(3600, $plan->fresh()->total_duration_seconds);
        $this->assertEquals(1.0000, $plan->fresh()->learning_hours);
    }

    /**
     * Test 22 — Existing deadline behavior still works.
     */
    public function test_existing_deadline_behavior_still_works(): void
    {
        $plan = TrainingPlan::create([
            'title'  => 'Deadline Behavior Plan',
            'active' => 'Inactive',
        ]);

        // Invalid deadline: to_date before from_date
        $invalidResp = $this->actingAs($this->adminUser)
            ->put(route('playlist.update', $plan->slug), [
                'title'        => 'Deadline Behavior Plan',
                'has_deadline' => '1',
                'from_date'    => '2026-12-31',
                'to_date'      => '2026-01-01',
                'action'       => 'draft',
            ]);
        $invalidResp->assertSessionHasErrors('to_date');

        // Valid deadline
        $validResp = $this->actingAs($this->adminUser)
            ->put(route('playlist.update', $plan->slug), [
                'title'        => 'Deadline Behavior Plan',
                'has_deadline' => '1',
                'from_date'    => '2026-01-01',
                'to_date'      => '2026-12-31',
                'action'       => 'draft',
            ]);
        $validResp->assertSessionHasNoErrors();
        $this->assertTrue($plan->fresh()->has_deadline);
    }

    /**
     * Test 23 — Existing thumbnail behavior still works (preserve, upload, remove).
     */
    public function test_existing_thumbnail_behavior_still_works(): void
    {
        $plan = TrainingPlan::create([
            'title'     => 'Thumbnail Behavior Plan',
            'active'    => 'Inactive',
            'thumbnail' => 'files/training_plan/preserve-thumb.jpg',
        ]);

        // 1. Preserved when no new file uploaded
        $this->actingAs($this->adminUser)
            ->put(route('playlist.update', $plan->slug), [
                'title'  => 'Thumbnail Behavior Plan',
                'action' => 'draft',
            ]);
        $this->assertSame('files/training_plan/preserve-thumb.jpg', $plan->fresh()->thumbnail);

        // 2. Remove thumbnail
        $this->actingAs($this->adminUser)
            ->put(route('playlist.update', $plan->slug), [
                'title'            => 'Thumbnail Behavior Plan',
                'remove_thumbnail' => '1',
                'action'           => 'draft',
            ]);
        $this->assertNull($plan->fresh()->thumbnail);

        // 3. Upload non-image is rejected
        $fakePdf = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');
        $errResp = $this->actingAs($this->adminUser)
            ->put(route('playlist.update', $plan->slug), [
                'title'     => 'Thumbnail Behavior Plan',
                'thumbnail' => $fakePdf,
                'action'    => 'draft',
            ]);
        $errResp->assertSessionHasErrors('thumbnail');
    }

    /**
     * Test 24 — Existing playlist content ordering still works.
     */
    public function test_existing_playlist_content_ordering_still_works(): void
    {
        $c1 = Content::create(['title' => 'Mat A', 'duration' => '00:10:00', 'active' => 'Active', 'classification_id' => $this->classification->id]);
        $c2 = Content::create(['title' => 'Mat B', 'duration' => '00:20:00', 'active' => 'Active', 'classification_id' => $this->classification->id]);

        $plan = TrainingPlan::create([
            'title'  => 'Order Persistence Plan',
            'active' => 'Inactive',
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);

        // Reorder: B first (0), A second (1)
        $response = $this->actingAs($this->adminUser)
            ->put(route('playlist.update', $plan->slug), [
                'title'   => 'Order Persistence Plan Updated',
                'content' => [$c2->id, $c1->id],
                'action'  => 'draft',
            ]);

        $response->assertSessionHasNoErrors();

        $details = TrainingPlanDetail::where('trainingplan_id', $plan->id)
            ->orderByRaw('CAST(content_order AS UNSIGNED) ASC')
            ->get();

        $this->assertCount(2, $details);
        $this->assertEquals($c2->id, $details[0]->content_id);
        $this->assertEquals(0, $details[0]->content_order);
        $this->assertEquals($c1->id, $details[1]->content_id);
        $this->assertEquals(1, $details[1]->content_order);
    }

    /**
     * Test 25 — Edit page renders with 6 numbered sections in English.
     */
    public function test_edit_page_renders_with_six_numbered_sections(): void
    {
        $plan = TrainingPlan::create([
            'title'  => 'Test 6 Sections English PL',
            'active' => 'Inactive',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.edit', $plan->slug));

        $response->assertStatus(200);
        $response->assertSee('Publish Status');
        $response->assertSee('Playlist Information');
        $response->assertSee('Thumbnail &amp; Live Preview', false);
        $response->assertSee('Duration &amp; Learning Hours', false);
        $response->assertSee('Playlist Content');
        $response->assertSee('Deadline &amp; Action', false);
        $response->assertSee('max-width: 1100px', false);
    }

    /**
     * Test 26 — Active playlist rejects full form update without unpublishing.
     * Prevents wiping playlist materials when content[] is omitted on published playlist.
     */
    public function test_active_playlist_rejects_full_form_update_without_unpublishing(): void
    {
        $c1 = Content::create(['title' => 'Protected Content 1', 'duration' => '00:15:00', 'active' => 'Active', 'classification_id' => $this->classification->id]);
        $c2 = Content::create(['title' => 'Protected Content 2', 'duration' => '00:30:00', 'active' => 'Active', 'classification_id' => $this->classification->id]);

        $plan = TrainingPlan::create([
            'title'                  => 'Active Protected Plan',
            'active'                 => 'Active',
            'total_duration_seconds' => 2700,
            'learning_hours'         => 0.7500,
        ]);

        $d1 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        $d2 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);

        // 1. Test AJAX/JSON submission without content[]
        $ajaxResponse = $this->actingAs($this->adminUser)
            ->putJson(route('playlist.update', $plan->slug), [
                'title'  => 'Maliciously Modified Title',
                'action' => 'publish',
            ]);

        $ajaxResponse->assertStatus(422);
        $ajaxResponse->assertJson([
            'status'  => 422,
            'success' => false,
            'message' => 'Cannot modify an active playlist. Please unpublish the playlist before making changes.',
        ]);

        // Assert no data modified after AJAX request
        $freshPlan = $plan->fresh();
        $this->assertSame('Active', $freshPlan->active);
        $this->assertSame('Active Protected Plan', $freshPlan->title);
        $this->assertSame(2700, $freshPlan->total_duration_seconds);
        $this->assertEquals(0.7500, $freshPlan->learning_hours);

        $details = TrainingPlanDetail::where('trainingplan_id', $plan->id)->orderBy('content_order')->get();
        $this->assertCount(2, $details);
        $this->assertEquals($c1->id, $details[0]->content_id);
        $this->assertEquals($c2->id, $details[1]->content_id);

        // 2. Test standard form submission without content[]
        $formResponse = $this->actingAs($this->adminUser)
            ->put(route('playlist.update', $plan->slug), [
                'title'  => 'Form Modified Title',
                'action' => 'publish',
            ]);

        $formResponse->assertRedirect(route('playlist.edit', $plan->slug));
        $formResponse->assertSessionHas('warning', 'Cannot modify an active playlist. Please unpublish the playlist before making changes.');

        // Assert all materials and attributes remain completely intact
        $freshPlan2 = $plan->fresh();
        $this->assertSame('Active', $freshPlan2->active);
        $this->assertSame('Active Protected Plan', $freshPlan2->title);
        $this->assertSame(2700, $freshPlan2->total_duration_seconds);
        $this->assertEquals(0.7500, $freshPlan2->learning_hours);

        $details2 = TrainingPlanDetail::where('trainingplan_id', $plan->id)->orderBy('content_order')->get();
        $this->assertCount(2, $details2);
        $this->assertEquals($c1->id, $details2[0]->content_id);
        $this->assertEquals($c2->id, $details2[1]->content_id);
    }

    /**
     * Test 27 — Active playlist rejects direct synchronize learning hours request.
     */
    public function test_active_playlist_rejects_direct_synchronize_learning_hours(): void
    {
        $c1 = Content::create(['title' => 'Sync C1', 'duration' => '01:00:00', 'active' => 'Active', 'classification_id' => $this->classification->id]);
        $c2 = Content::create(['title' => 'Sync C2', 'duration' => '00:30:00', 'active' => 'Active', 'classification_id' => $this->classification->id]);

        $plan = TrainingPlan::create([
            'title'                  => 'Active Sync Protected Plan',
            'active'                 => 'Active',
            'total_duration_seconds' => 3600,
            'learning_hours'         => 1.0000,
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);

        // Attempt direct POST to sync endpoint with different content payload (c2 = 1800s, 0.5h)
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('playlist.synchronize-learning-hours', $plan->slug), [
                'content' => [$c2->id],
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status'  => 422,
            'success' => false,
            'message' => 'Cannot synchronize duration or learning hours while playlist is active. Please unpublish first.',
        ]);

        // Verify total_duration_seconds and learning_hours remain completely unchanged
        $freshPlan = $plan->fresh();
        $this->assertSame(3600, $freshPlan->total_duration_seconds);
        $this->assertEquals(1.0000, $freshPlan->learning_hours);
        $this->assertSame('Active', $freshPlan->active);

        // Verify detail remains unchanged
        $this->assertDatabaseHas('trainingplandetail', [
            'trainingplan_id' => $plan->id,
            'content_id'      => $c1->id,
        ]);
        $this->assertDatabaseMissing('trainingplandetail', [
            'trainingplan_id' => $plan->id,
            'content_id'      => $c2->id,
        ]);
    }
}