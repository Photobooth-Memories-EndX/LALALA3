<?php

namespace Tests\Feature;

use App\Models\OfflineTrainingOption;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TrainingTypeFilterTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', '2')->first() ?? User::factory()->create([
            'role' => '2',
        ]);
    }

    /**
     * Scenario 1: Display all Training Types by default.
     */
    public function test_scenario_1_displays_all_training_types_by_default(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/classification?type=type');

        $response->assertStatus(200);
        $response->assertSee('List Training Type');
        $response->assertSee('12 row(s) displayed');
        $response->assertSee('Document');
        $response->assertSee('Video');
        $response->assertSee('MOOC');
        $response->assertSee('Playlist');
        $response->assertSee('In-House');
        $response->assertSee('Public');
        $response->assertSee('Certification');
        $response->assertSee('Workshop');
        $response->assertSee('Seminar');
        $response->assertSee('Classroom');
        $response->assertSee('External');
        $response->assertSee('Overseas');
    }

    /**
     * Scenario 2: Filter by Option Value (e.g. Document).
     */
    public function test_scenario_2_filter_by_option_value(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/classification?type=type&option_value=Document');

        $response->assertStatus(200);
        $response->assertSee('Document');
        $response->assertSee('1 row(s) displayed');
        $response->assertDontSee('item-value-text">Video', false);
        $response->assertDontSee('item-value-text">Playlist', false);
        $response->assertDontSee('item-value-text">Classroom', false);
    }

    /**
     * Scenario 3: Filter by Source (OWL, Shared, Non KP-OWL, All Sources).
     */
    public function test_scenario_3_filter_by_source(): void
    {
        // 1. OWL Source
        $owlResponse = $this->actingAs($this->admin)->get('/manage/classification?type=type&source=owl');
        $owlResponse->assertStatus(200);
        $owlResponse->assertSee('4 row(s) displayed');
        $owlResponse->assertSee('Document');
        $owlResponse->assertSee('Video');
        $owlResponse->assertSee('MOOC');
        $owlResponse->assertSee('Playlist');
        $owlResponse->assertDontSee('item-value-text">In-House', false);
        $owlResponse->assertDontSee('item-value-text">Classroom', false);

        // 2. Shared Source
        $sharedResponse = $this->actingAs($this->admin)->get('/manage/classification?type=type&source=shared');
        $sharedResponse->assertStatus(200);
        $sharedResponse->assertSee('8 row(s) displayed');
        $sharedResponse->assertSee('In-House');
        $sharedResponse->assertSee('Public');
        $sharedResponse->assertSee('Certification');
        $sharedResponse->assertSee('Workshop');
        $sharedResponse->assertSee('Seminar');
        $sharedResponse->assertSee('Classroom');
        $sharedResponse->assertSee('External');
        $sharedResponse->assertSee('Overseas');
        $sharedResponse->assertDontSee('item-value-text">Document', false);
        $sharedResponse->assertDontSee('item-value-text">Video', false);

        // 3. Non KP-OWL Source (Expect 0 items)
        $nonKpResponse = $this->actingAs($this->admin)->get('/manage/classification?type=type&source=non_kp_owl');
        $nonKpResponse->assertStatus(200);
        $nonKpResponse->assertSee('No Training Type Options Found');
        $nonKpResponse->assertSee('Reset Filters');

        // 4. All Sources (Empty source)
        $allResponse = $this->actingAs($this->admin)->get('/manage/classification?type=type&source=');
        $allResponse->assertStatus(200);
        $allResponse->assertSee('12 row(s) displayed');
    }

    /**
     * Scenario 4: Filter by Active Status.
     */
    public function test_scenario_4_filter_active_status(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/classification?type=type&status=1');

        $response->assertStatus(200);
        $response->assertSee('12 row(s) displayed');
        $response->assertSee('Document');
        $response->assertSee('Active');
    }

    /**
     * Scenario 5: Filter by Inactive Status.
     */
    public function test_scenario_5_filter_inactive_status(): void
    {
        try {
            OfflineTrainingOption::where('option_type', 'type')->where('value', 'External')->update(['is_active' => false]);

            $response = $this->actingAs($this->admin)->get('/manage/classification?type=type&status=0');

            $response->assertStatus(200);
            $response->assertSee('1 row(s) displayed');
            $response->assertSee('External');
            $response->assertSee('Inactive');
            $response->assertDontSee('item-value-text">Document', false);
        } finally {
            OfflineTrainingOption::where('option_type', 'type')->where('value', 'External')->update(['is_active' => true]);
        }
    }

    /**
     * Scenario 6: Search Training Type by keyword.
     */
    public function test_scenario_6_search_training_type_by_keyword(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/classification?type=type&q=Video');

        $response->assertStatus(200);
        $response->assertSee('1 row(s) displayed');
        $response->assertSee('Video');
        $response->assertDontSee('item-value-text">Document', false);
        $response->assertDontSee('item-value-text">Seminar', false);
    }

    /**
     * Scenario 7: Combined filters.
     */
    public function test_scenario_7_combination_of_filters(): void
    {
        // Combined match
        $matchResponse = $this->actingAs($this->admin)->get(
            '/manage/classification?type=type&entries=25&option_value=Document&source=owl&status=1&q=Doc'
        );
        $matchResponse->assertStatus(200);
        $matchResponse->assertSee('1 row(s) displayed');
        $matchResponse->assertSee('Document');

        // Combined mismatch: Document exists, but source is shared -> returns 0
        $mismatchResponse = $this->actingAs($this->admin)->get(
            '/manage/classification?type=type&option_value=Document&source=shared'
        );
        $mismatchResponse->assertStatus(200);
        $mismatchResponse->assertSee('No Training Type Options Found');
    }

    /**
     * Scenario 8: Sorting by Order and Value.
     */
    public function test_scenario_8_sorting(): void
    {
        // Sort by value ascending
        $ascResponse = $this->actingAs($this->admin)->get('/manage/classification?type=type&sort_by=value&sort_dir=asc');
        $ascResponse->assertStatus(200);
        $ascItems = $ascResponse->viewData('items');
        $this->assertEquals('Certification', $ascItems->first()->value);
        $this->assertEquals('Workshop', $ascItems->last()->value);

        // Sort by value descending
        $descResponse = $this->actingAs($this->admin)->get('/manage/classification?type=type&sort_by=value&sort_dir=desc');
        $descResponse->assertStatus(200);
        $descItems = $descResponse->viewData('items');
        $this->assertEquals('Workshop', $descItems->first()->value);
        $this->assertEquals('Certification', $descItems->last()->value);
    }

    /**
     * Scenario 9: Pagination Entries selection.
     */
    public function test_scenario_9_pagination_entries_selection(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/classification?type=type&entries=10');

        $response->assertStatus(200);
        $response->assertSee('value="10" selected', false);
        $response->assertSee('pageLength: 10', false);
    }

    /**
     * Scenario 10: State persistence on page refresh with query params.
     */
    public function test_scenario_10_filter_state_preserved_with_query_params(): void
    {
        $url = '/manage/classification?type=type&entries=25&option_value=Document&source=Shared&status=1&q=Doc';
        $response = $this->actingAs($this->admin)->get($url);

        $response->assertStatus(200);
        // Training type select has Document selected
        $response->assertSee('value="Document" selected', false);
        // Source select has shared selected
        $response->assertSee('value="shared" selected', false);
        // Status select has 1 selected
        $response->assertSee('value="1" selected', false);
        // Search input retains keyword
        $response->assertSee('value="Doc"', false);
    }

    /**
     * Scenario 11: Reset filters returns all Training Types.
     */
    public function test_scenario_11_reset_filters(): void
    {
        // First filter
        $filteredResponse = $this->actingAs($this->admin)->get('/manage/classification?type=type&option_value=Document');
        $filteredResponse->assertSee('1 row(s) displayed');

        // Reset navigates to clean /manage/classification?type=type
        $resetResponse = $this->actingAs($this->admin)->get('/manage/classification?type=type');
        $resetResponse->assertStatus(200);
        $resetResponse->assertSee('12 row(s) displayed');
        $resetResponse->assertSee('Document');
        $resetResponse->assertSee('Workshop');
    }

    /**
     * Scenario 12: Light mode and Dark mode UI support.
     */
    public function test_scenario_12_light_and_dark_mode_support(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/classification?type=type');

        $response->assertStatus(200);
        $response->assertSee('[data-bs-theme="dark"] .quick-filter-card', false);
        $response->assertSee('body.dark-mode .quick-filter-card', false);
        $response->assertSee('body.theme-dark .quick-filter-card', false);
        $response->assertSee('quick-filter-card', false);
    }
}
