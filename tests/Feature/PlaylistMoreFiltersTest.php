<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Content;
use App\Models\RoleAccessPermission;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PlaylistMoreFiltersTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected Classification $classification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->classification = Classification::firstOrCreate(
            ['slug' => 'test-classification-pl-filter'],
            ['classification_name' => 'Testing Classification PL Filter']
        );

        $this->adminUser = User::factory()->create([
            'role'       => User::ROLE_SUPER_ADMIN,
            'team'       => 'Quality Assurance',
            'department' => 'Technology',
            'division'   => 'Engineering',
            'ssohash'    => 'test-admin-filter-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-ADMIN-FLT',
        ]);

        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_SUPER_ADMIN, 'module_key' => 'playlist_learning_path'],
            ['can_access' => true]
        );
    }

    /**
     * Test 1 — Duration: duration_from only matches playlists with duration >= from.
     */
    public function test_duration_from_only(): void
    {
        $short = TrainingPlan::create(['title' => 'Short 20m Playlist', 'active' => 'Active', 'total_duration_seconds' => 1200]);
        $long = TrainingPlan::create(['title' => 'Long 90m Playlist', 'active' => 'Active', 'total_duration_seconds' => 5400]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.index', ['duration_from' => '01:00:00']));

        $response->assertStatus(200);
        $response->assertSee('Long 90m Playlist');
        $response->assertDontSee('Short 20m Playlist');
    }

    /**
     * Test 2 — Duration: duration_to only matches playlists with duration <= to.
     */
    public function test_duration_to_only(): void
    {
        $short = TrainingPlan::create(['title' => 'Under One Hour PL', 'active' => 'Active', 'total_duration_seconds' => 2400]);
        $long = TrainingPlan::create(['title' => 'Over One Hour PL', 'active' => 'Active', 'total_duration_seconds' => 7200]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.index', ['duration_to' => '01:00:00']));

        $response->assertStatus(200);
        $response->assertSee('Under One Hour PL');
        $response->assertDontSee('Over One Hour PL');
    }

    /**
     * Test 3 — Duration: duration_from + duration_to range filter.
     */
    public function test_duration_from_and_duration_to_range(): void
    {
        $below = TrainingPlan::create(['title' => 'Below Range 15m', 'active' => 'Active', 'total_duration_seconds' => 900]);
        $inside = TrainingPlan::create(['title' => 'Inside Range 45m', 'active' => 'Active', 'total_duration_seconds' => 2700]);
        $above = TrainingPlan::create(['title' => 'Above Range 120m', 'active' => 'Active', 'total_duration_seconds' => 7200]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.index', ['duration_from' => '00:30:00', 'duration_to' => '01:00:00']));

        $response->assertStatus(200);
        $response->assertSee('Inside Range 45m');
        $response->assertDontSee('Below Range 15m');
        $response->assertDontSee('Above Range 120m');
    }

    /**
     * Test 4 — Duration: exact boundary matches inclusive.
     */
    public function test_exact_duration_boundary(): void
    {
        $exact = TrainingPlan::create(['title' => 'Exact Boundary 3600s', 'active' => 'Active', 'total_duration_seconds' => 3600]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.index', ['duration_from' => '01:00:00', 'duration_to' => '01:00:00']));

        $response->assertStatus(200);
        $response->assertSee('Exact Boundary 3600s');
    }

    /**
     * Test 5 — Duration: items outside range are excluded.
     */
    public function test_duration_outside_range(): void
    {
        $outside = TrainingPlan::create(['title' => 'Completely Outside Range', 'active' => 'Active', 'total_duration_seconds' => 10000]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.index', ['duration_from' => '00:10:00', 'duration_to' => '00:20:00']));

        $response->assertStatus(200);
        $response->assertDontSee('Completely Outside Range');
    }

    /**
     * Test 6 — Duration: HH:MM:SS conversion correctly handles complex hours and minutes.
     */
    public function test_hh_mm_ss_conversion(): void
    {
        // 02:15:30 = 2*3600 + 15*60 + 30 = 7200 + 900 + 30 = 8130 seconds
        $planA = TrainingPlan::create(['title' => 'Exact 8130s Playlist', 'active' => 'Active', 'total_duration_seconds' => 8130]);
        $planB = TrainingPlan::create(['title' => 'Slightly Less 8129s', 'active' => 'Active', 'total_duration_seconds' => 8129]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.index', ['duration_from' => '02:15:30']));

        $response->assertStatus(200);
        $response->assertSee('Exact 8130s Playlist');
        $response->assertDontSee('Slightly Less 8129s');
    }

    /**
     * Test 7 — Duration: invalid duration format triggers validation error.
     */
    public function test_invalid_duration_format(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.index', ['duration_from' => 'invalid-format']));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('duration_from');
    }

    /**
     * Test 8 — Duration: From > To triggers validation error.
     */
    public function test_duration_from_greater_than_to_validation(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.index', ['duration_from' => '02:00:00', 'duration_to' => '01:00:00']));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('duration_from');
    }

    /**
     * Test 9 — Duration: Zero duration handling does not match minimum filter.
     */
    public function test_null_duration_handling(): void
    {
        $zeroDuration = TrainingPlan::create(['title' => 'Zero Duration Playlist', 'active' => 'Active', 'total_duration_seconds' => 0]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.index', ['duration_from' => '00:01:00']));

        $response->assertStatus(200);
        $response->assertDontSee('Zero Duration Playlist');
    }

    /**
     * Test 10 — Learning Hours: learning_hours_from only.
     */
    public function test_learning_hours_from_only(): void
    {
        $small = TrainingPlan::create(['title' => 'Small LH 0.5', 'active' => 'Active', 'learning_hours' => 0.5]);
        $large = TrainingPlan::create(['title' => 'Large LH 3.0', 'active' => 'Active', 'learning_hours' => 3.0]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.index', ['learning_hours_from' => '2.0']));

        $response->assertStatus(200);
        $response->assertSee('Large LH 3.0');
        $response->assertDontSee('Small LH 0.5');
    }

    /**
     * Test 11 — Learning Hours: learning_hours_to only.
     */
    public function test_learning_hours_to_only(): void
    {
        $small = TrainingPlan::create(['title' => 'Small LH 0.75', 'active' => 'Active', 'learning_hours' => 0.75]);
        $large = TrainingPlan::create(['title' => 'Large LH 5.25', 'active' => 'Active', 'learning_hours' => 5.25]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.index', ['learning_hours_to' => '1.5']));

        $response->assertStatus(200);
        $response->assertSee('Small LH 0.75');
        $response->assertDontSee('Large LH 5.25');
    }

    /**
     * Test 12 — Learning Hours: learning_hours_from + learning_hours_to range.
     */
    public function test_learning_hours_range(): void
    {
        $below = TrainingPlan::create(['title' => 'LH Below 0.5', 'active' => 'Active', 'learning_hours' => 0.5]);
        $inside = TrainingPlan::create(['title' => 'LH Inside 2.5', 'active' => 'Active', 'learning_hours' => 2.5]);
        $above = TrainingPlan::create(['title' => 'LH Above 6.0', 'active' => 'Active', 'learning_hours' => 6.0]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.index', ['learning_hours_from' => '1.0', 'learning_hours_to' => '4.0']));

        $response->assertStatus(200);
        $response->assertSee('LH Inside 2.5');
        $response->assertDontSee('LH Below 0.5');
        $response->assertDontSee('LH Above 6.0');
    }

    /**
     * Test 13 — Learning Hours: exact boundary matches inclusive.
     */
    public function test_exact_learning_hours_boundary(): void
    {
        $exact = TrainingPlan::create(['title' => 'LH Exact 2.75', 'active' => 'Active', 'learning_hours' => 2.75]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.index', ['learning_hours_from' => '2.75', 'learning_hours_to' => '2.75']));

        $response->assertStatus(200);
        $response->assertSee('LH Exact 2.75');
    }

    /**
     * Test 14 — Learning Hours: items outside range excluded.
     */
    public function test_learning_hours_outside_range(): void
    {
        $outside = TrainingPlan::create(['title' => 'LH Completely Outside', 'active' => 'Active', 'learning_hours' => 10.0]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.index', ['learning_hours_from' => '1.0', 'learning_hours_to' => '3.0']));

        $response->assertStatus(200);
        $response->assertDontSee('LH Completely Outside');
    }

    /**
     * Test 15 — Learning Hours: decimal values are handled with full precision.
     */
    public function test_decimal_values_precision(): void
    {
        $plan = TrainingPlan::create(['title' => 'Decimal LH 0.8333', 'active' => 'Active', 'learning_hours' => 0.8333]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.index', ['learning_hours_from' => '0.8300', 'learning_hours_to' => '0.8400']));

        $response->assertStatus(200);
        $response->assertSee('Decimal LH 0.8333');
    }

    /**
     * Test 16 — Learning Hours: invalid numeric input triggers validation error.
     */
    public function test_invalid_numeric_learning_hours_input(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.index', ['learning_hours_from' => 'not-a-number']));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('learning_hours_from');
    }

    /**
     * Test 17 — Learning Hours: From > To triggers validation error.
     */
    public function test_learning_hours_from_greater_than_to_validation(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.index', ['learning_hours_from' => '5.0', 'learning_hours_to' => '2.0']));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('learning_hours_from');
    }

    /**
     * Test 18 — Learning Hours: NULL learning_hours handling does not match minimum filter.
     */
    public function test_null_learning_hours_handling(): void
    {
        $nullLh = TrainingPlan::create(['title' => 'Null LH Playlist', 'active' => 'Active', 'learning_hours' => null]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.index', ['learning_hours_from' => '0.1']));

        $response->assertStatus(200);
        $response->assertDontSee('Null LH Playlist');
    }

    /**
     * Test 19 — Combination: Duration + Learning Hours together.
     */
    public function test_duration_and_learning_hours_combined(): void
    {
        $match = TrainingPlan::create(['title' => 'Combo Match PL', 'active' => 'Active', 'total_duration_seconds' => 3600, 'learning_hours' => 1.0]);
        $durationMismatch = TrainingPlan::create(['title' => 'Duration Mismatch PL', 'active' => 'Active', 'total_duration_seconds' => 600, 'learning_hours' => 1.0]);
        $lhMismatch = TrainingPlan::create(['title' => 'LH Mismatch PL', 'active' => 'Active', 'total_duration_seconds' => 3600, 'learning_hours' => 4.0]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.index', [
                'duration_from'       => '00:50:00',
                'duration_to'         => '01:10:00',
                'learning_hours_from' => '0.8',
                'learning_hours_to'   => '1.5',
            ]));

        $response->assertStatus(200);
        $response->assertSee('Combo Match PL');
        $response->assertDontSee('Duration Mismatch PL');
        $response->assertDontSee('LH Mismatch PL');
    }

    /**
     * Test 20 — Combination: Duration + existing Status filter.
     */
    public function test_duration_and_status_combined(): void
    {
        $activeMatch = TrainingPlan::create(['title' => 'Active 3600s PL', 'active' => 'Active', 'total_duration_seconds' => 3600]);
        $inactiveMismatch = TrainingPlan::create(['title' => 'Inactive 3600s PL', 'active' => 'Inactive', 'total_duration_seconds' => 3600]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.index', [
                'status'        => 'Active',
                'duration_from' => '00:50:00',
            ]));

        $response->assertStatus(200);
        $response->assertSee('Active 3600s PL');
        $response->assertDontSee('Inactive 3600s PL');
    }

    /**
     * Test 21 — Combination: Learning Hours + Author filter.
     */
    public function test_learning_hours_and_author_combined(): void
    {
        $contentA = Content::create(['title' => 'Content Author Alpha', 'author' => 'Author Alpha', 'active' => 'Active', 'classification_id' => $this->classification->id]);
        $contentB = Content::create(['title' => 'Content Author Beta', 'author' => 'Author Beta', 'active' => 'Active', 'classification_id' => $this->classification->id]);

        $planA = TrainingPlan::create(['title' => 'Alpha 2LH Playlist', 'active' => 'Active', 'learning_hours' => 2.0]);
        TrainingPlanDetail::create(['trainingplan_id' => $planA->id, 'content_id' => $contentA->id, 'content_order' => 1]);

        $planB = TrainingPlan::create(['title' => 'Beta 2LH Playlist', 'active' => 'Active', 'learning_hours' => 2.0]);
        TrainingPlanDetail::create(['trainingplan_id' => $planB->id, 'content_id' => $contentB->id, 'content_order' => 1]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.index', [
                'author'              => 'Author Alpha',
                'learning_hours_from' => '1.5',
            ]));

        $response->assertStatus(200);
        $response->assertSee('Alpha 2LH Playlist');
        $response->assertDontSee('Beta 2LH Playlist');
    }

    /**
     * Test 22 — Combination: All filters together (Status, Duration, Learning Hours).
     */
    public function test_all_filters_combined(): void
    {
        $perfectMatch = TrainingPlan::create([
            'title'                  => 'Perfect Match Super Playlist',
            'active'                 => 'Active',
            'total_duration_seconds' => 5400,
            'learning_hours'         => 1.5,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.index', [
                'status'              => 'Active',
                'duration_from'       => '01:00:00',
                'duration_to'         => '02:00:00',
                'learning_hours_from' => '1.0',
                'learning_hours_to'   => '2.0',
            ]));

        $response->assertStatus(200);
        $response->assertSee('Perfect Match Super Playlist');
    }

    /**
     * Test 23 — Regression: Existing status filter still works standalone.
     */
    public function test_existing_status_filter_still_works(): void
    {
        $active = TrainingPlan::create(['title' => 'Status Active Only', 'active' => 'Active']);
        $inactive = TrainingPlan::create(['title' => 'Status Inactive Only', 'active' => 'Inactive']);

        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.index', ['status' => 'Active']));

        $response->assertStatus(200);
        $response->assertSee('Status Active Only');
        $response->assertDontSee('Status Inactive Only');
    }

    /**
     * Test 24 — Regression: Existing sorting still works standalone.
     */
     public function test_existing_sorting_still_works(): void
     {
         $planZ = TrainingPlan::create(['title' => 'SortTest Zebra Alpha', 'active' => 'Active']);
         $planA = TrainingPlan::create(['title' => 'SortTest Apple Beta', 'active' => 'Active']);

         $response = $this->actingAs($this->adminUser)
             ->get(route('playlist.index', ['sort' => 'title_asc', 'q' => 'SortTest']));

         $response->assertStatus(200);
         $content = $response->getContent();
         $posA = strpos($content, 'SortTest Apple Beta');
         $posZ = strpos($content, 'SortTest Zebra Alpha');
         $this->assertTrue($posA !== false && $posZ !== false && $posA < $posZ);
     }

    /**
     * Test 25 — Reset: reset route clears all query params and returns all playlists.
     */
    public function test_reset_route_clears_filters(): void
    {
        $planA = TrainingPlan::create(['title' => 'Reset Visibility Plan A', 'active' => 'Active']);
        $planB = TrainingPlan::create(['title' => 'Reset Visibility Plan B', 'active' => 'Inactive']);

        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.index'));

        $response->assertStatus(200);
        $response->assertSee('Reset Visibility Plan A');
        $response->assertSee('Reset Visibility Plan B');
    }

    /**
     * Test 26 — UI: More filters modal preserves inputs and displays active filter indicator.
     */
    public function test_filter_state_persists_in_modal_and_active_indicator_renders(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.index', [
                'duration_from'       => '00:30:00',
                'duration_to'         => '01:30:00',
                'learning_hours_from' => '1.25',
                'learning_hours_to'   => '3.75',
            ]));

        $response->assertStatus(200);
        $html = $response->getContent();

        // 1. Inputs maintain their values
        $this->assertStringContainsString('value="00:30:00"', $html);
        $this->assertStringContainsString('value="01:30:00"', $html);
        $this->assertStringContainsString('value="1.25"', $html);
        $this->assertStringContainsString('value="3.75"', $html);

        // 2. Active filter indicator is rendered
        $this->assertStringContainsString('Active filter', $html);
    }
}
