<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\User;
use Database\Seeders\ClassificationSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MainClassificationPageTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ClassificationSeeder::class);
        $this->admin = User::where('role', '2')->first() ?? User::factory()->create(['role' => '2']);
    }

    /**
     * Test 1: Page is accessible and returns 200.
     */
    public function test_1_page_is_accessible(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/classification/classification');

        $response->assertStatus(200);
        $response->assertSee('List Classification');
        $response->assertSee('Manage main training classifications registered');
    }

    /**
     * Test 2: All 13 classifications are rendered from the classification table.
     */
    public function test_2_renders_all_13_canonical_classifications(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/classification/classification');

        $response->assertStatus(200);
        $response->assertSee('13 row(s) displayed');
        $response->assertSee('Basic & Common Duties');
        $response->assertSee('Compliance');
        $response->assertSee('Innovation');
        $response->assertSee('Job Functional');
        $response->assertSee('Language');
        $response->assertSee('Leadership');
        $response->assertSee('Assessment/Test');
        $response->assertSee('Basic mindset');
        $response->assertSee('Maintenance');
        $response->assertSee('SHE');
        $response->assertSee('ISO Standard');
        $response->assertSee('Technology');
        $response->assertSee('Other');
    }

    /**
     * Test 3: Search filter works by name and slug.
     */
    public function test_3_search_filter(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/classification/classification?q=Technology');

        $response->assertStatus(200);
        $response->assertSee('1 row(s) displayed');
        $response->assertSee('Technology');
        $response->assertDontSee('item-name-text">Compliance', false);
    }

    /**
     * Test 4: Filter by active and inactive status.
     */
    public function test_4_status_filter(): void
    {
        // All 13 are active by default
        $activeRes = $this->actingAs($this->admin)->get('/manage/classification/classification?status=1');
        $activeRes->assertStatus(200);
        $activeRes->assertSee('13 row(s) displayed');

        // Create an inactive classification
        $inactive = Classification::create([
            'classification_name' => 'Legacy Inactive Classification',
            'slug'                => 'legacy-inactive-classification',
            'is_active'           => 0,
        ]);

        $inactiveRes = $this->actingAs($this->admin)->get('/manage/classification/classification?status=0');
        $inactiveRes->assertStatus(200);
        $inactiveRes->assertSee('1 row(s) displayed');
        $inactiveRes->assertSee('Legacy Inactive Classification');
        $inactiveRes->assertDontSee('item-name-text">Compliance', false);
    }

    /**
     * Test 5: Pagination entries selection.
     */
    public function test_5_pagination_entries(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/classification/classification?entries=10');

        $response->assertStatus(200);
        $response->assertSee('value="10" selected', false);
        $response->assertSee('pageLength: 10', false);
    }

    /**
     * Test 6: Add Classification successfully creates record in classification table.
     */
    public function test_6_add_classification_success(): void
    {
        $payload = [
            'classification_name' => 'Data Science & AI',
            'slug'                => 'data-science-ai',
            'is_active'           => 1,
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/manage/classification/classification', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('classification', [
            'classification_name' => 'Data Science & AI',
            'slug'                => 'data-science-ai',
            'is_active'           => 1,
        ]);
    }

    /**
     * Test 7: Edit Classification updates record in classification table.
     */
    public function test_7_edit_classification_success(): void
    {
        $target = Classification::create([
            'classification_name' => 'Temporary Editable Category',
            'slug'                => 'temporary-editable-category',
            'is_active'           => 1,
        ]);

        $payload = [
            'classification_name' => 'Renamed Edited Category',
            'slug'                => 'renamed-edited-category',
            'is_active'           => 0,
        ];

        $response = $this->actingAs($this->admin)
            ->putJson("/manage/classification/classification/{$target->id}", $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('classification', [
            'id'                  => $target->id,
            'classification_name' => 'Renamed Edited Category',
            'slug'                => 'renamed-edited-category',
            'is_active'           => 0,
        ]);
    }

    /**
     * Test 8: Auto slug generation and uniqueness guarantee.
     */
    public function test_8_slug_uniqueness_guarantee(): void
    {
        $first = Classification::create([
            'classification_name' => 'Unique Topic',
            'slug'                => 'unique-topic',
            'is_active'           => 1,
        ]);

        // Attempt to create another with empty slug but different name
        $response = $this->actingAs($this->admin)->postJson('/manage/classification/classification', [
            'classification_name' => 'Unique Topic 2',
            'slug'                => 'unique-topic', // duplicate slug
        ]);

        // Handled by generating suffix e.g. unique-topic-1 or unique slug
        $response->assertStatus(200);
        $created = Classification::where('classification_name', 'Unique Topic 2')->firstOrFail();
        $this->assertNotEquals('unique-topic', $created->slug);
        $this->assertTrue(str_starts_with($created->slug, 'unique-topic-'));
    }

    /**
     * Test 9: Delete has safety usage check (blocks deletion of in-use classification).
     */
    public function test_9_delete_safety_usage_check(): void
    {
        // Basic & Common Duties has 6 sub classifications linked
        $inUse = Classification::where('slug', 'basic-common-duties')->firstOrFail();

        // 1. Usage API endpoint reports is_used = true
        $usageRes = $this->actingAs($this->admin)->getJson("/manage/classification/classification/{$inUse->id}/usage");
        $usageRes->assertStatus(200);
        $usageRes->assertJson([
            'success' => true,
            'data'    => [
                'is_used' => true,
            ],
        ]);

        // 2. Delete attempt is blocked with 422
        $deleteRes = $this->actingAs($this->admin)->deleteJson("/manage/classification/classification/{$inUse->id}");
        $deleteRes->assertStatus(422);
        $deleteRes->assertJson([
            'success' => false,
            'is_used' => true,
        ]);

        // Record still exists in database
        $this->assertDatabaseHas('classification', ['id' => $inUse->id]);

        // 3. Unused classification can be safely deleted
        $unused = Classification::create([
            'classification_name' => 'Completely Unused Topic',
            'slug'                => 'completely-unused-topic',
            'is_active'           => 1,
        ]);

        $unusedDeleteRes = $this->actingAs($this->admin)->deleteJson("/manage/classification/classification/{$unused->id}");
        $unusedDeleteRes->assertStatus(200);
        $this->assertDatabaseMissing('classification', ['id' => $unused->id]);
    }

    /**
     * Test 10: Duplicate classification name is prevented.
     */
    public function test_10_duplicate_name_prevention(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/manage/classification/classification', [
            'classification_name' => 'Compliance', // existing name
            'slug'                => 'compliance-new',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['classification_name']);
    }

    /**
     * Test 11: State is preserved on refresh with query parameters.
     */
    public function test_11_state_preserved_with_query_params(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/classification/classification?entries=50&status=1&q=Leadership');

        $response->assertStatus(200);
        $response->assertSee('value="50" selected', false);
        $response->assertSee('value="1" selected', false);
        $response->assertSee('value="Leadership"', false);
    }

    /**
     * Test 12: Light and Dark mode UI classes are present.
     */
    public function test_12_light_and_dark_mode_support(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/classification/classification');

        $response->assertStatus(200);
        $response->assertSee('[data-bs-theme="dark"] .quick-filter-card', false);
        $response->assertSee('body.dark-mode .quick-filter-card', false);
        $response->assertSee('body.theme-dark .quick-filter-card', false);
    }
}
