<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Content;
use App\Models\RoleAccessPermission;
use App\Models\TrainingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ContentManagementLearningHourAndDetailTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected Classification $classification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->classification = Classification::create([
            'classification_name' => 'IT Engineering ' . \Illuminate\Support\Str::random(5),
            'slug'                => 'it-eng-' . \Illuminate\Support\Str::random(5),
        ]);

        $this->adminUser = User::factory()->create([
            'role'       => User::ROLE_SUPER_ADMIN,
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'L&D',
            'ssohash'    => 'test-admin-lh-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-ADMIN-LH',
        ]);

        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_SUPER_ADMIN, 'module_key' => 'content_repository'],
            ['can_access' => true]
        );
    }

    /**
     * Test 1 — Unauthenticated user cannot access content detail endpoint.
     */
    public function test_unauthenticated_user_cannot_access_content_detail_endpoint(): void
    {
        $content = Content::create([
            'title'             => 'Test Content ' . \Illuminate\Support\Str::random(5),
            'type'              => 'Document',
            'active'            => 'Active',
            'classification_id' => $this->classification->id,
            'learning_hours'    => 0.2500,
        ]);

        $response = $this->getJson(route('content.details', $content->id));
        $this->assertTrue(in_array($response->status(), [401, 302], true));
    }

    /**
     * Test 2 — User without content_repository permission cannot access content detail endpoint.
     */
    public function test_user_without_permission_cannot_access_content_detail_endpoint(): void
    {
        $user = User::factory()->create([
            'role'       => User::ROLE_ADMIN,
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'L&D',
            'ssohash'    => 'test-no-perm-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-NO-PERM',
        ]);

        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_ADMIN, 'module_key' => 'content_repository'],
            ['can_access' => false]
        );

        $content = Content::create([
            'title'             => 'Test Content ' . \Illuminate\Support\Str::random(5),
            'type'              => 'Document',
            'active'            => 'Active',
            'classification_id' => $this->classification->id,
            'learning_hours'    => 0.2500,
        ]);

        $response = $this->actingAs($user)->getJson(route('content.details', $content->id));
        $this->assertTrue(in_array($response->status(), [403, 302], true));
    }

    /**
     * Test 3 — Content index table renders Learning Hour column, values with 4 decimals and '-' for null.
     */
    public function test_content_index_table_renders_learning_hour_column_and_detail_button(): void
    {
        $contentWithLh = Content::create([
            'title'             => 'Content With LH ' . \Illuminate\Support\Str::random(5),
            'type'              => 'Document',
            'active'            => 'Active',
            'classification_id' => $this->classification->id,
            'duration'          => '00:04:38',
            'learning_hours'    => 0.0772,
        ]);

        $contentWithoutLh = Content::create([
            'title'             => 'Content Without LH ' . \Illuminate\Support\Str::random(5),
            'type'              => 'Document',
            'active'            => 'Active',
            'classification_id' => $this->classification->id,
            'duration'          => '00:05:00',
            'learning_hours'    => null,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('content.index', [
            'classification' => $this->classification->id,
        ]));

        $response->assertOk();
        // Learning Hour column header
        $response->assertSee('Learning Hour');
        // Formatted 4-decimal learning hour badge
        $response->assertSee('0.0772 h');
        // View Detail button with data-id
        $response->assertSee('btn-view-content-detail');
        $response->assertSee('data-id="' . $contentWithLh->id . '"', false);
        $response->assertSee('data-id="' . $contentWithoutLh->id . '"', false);
        // Modal placeholder rendered
        $response->assertSee('contentDetailModal');
    }

    /**
     * Test 4 — Range filter min_learning_hour & max_learning_hour in content query.
     */
    public function test_learning_hour_range_filters_work(): void
    {
        $contentA = Content::create([
            'title'             => 'Doc Range Low ' . \Illuminate\Support\Str::random(5),
            'type'              => 'Document',
            'active'            => 'Active',
            'classification_id' => $this->classification->id,
            'learning_hours'    => 0.2000,
        ]);

        $contentB = Content::create([
            'title'             => 'Doc Range Mid ' . \Illuminate\Support\Str::random(5),
            'type'              => 'Document',
            'active'            => 'Active',
            'classification_id' => $this->classification->id,
            'learning_hours'    => 1.5000,
        ]);

        $contentC = Content::create([
            'title'             => 'Doc Range High ' . \Illuminate\Support\Str::random(5),
            'type'              => 'Document',
            'active'            => 'Active',
            'classification_id' => $this->classification->id,
            'learning_hours'    => 5.0000,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('content.index', [
            'classification'    => $this->classification->id,
            'min_learning_hour' => '1.0',
            'max_learning_hour' => '2.0',
        ]));

        $response->assertOk();
        $response->assertSee($contentB->title);
        $response->assertDontSee($contentA->title);
        $response->assertDontSee($contentC->title);
    }

    /**
     * Test 5 — Preset filters has_lh and missing_lh.
     */
    public function test_learning_hour_has_and_missing_filters(): void
    {
        $contentWith = Content::create([
            'title'             => 'Doc Has LH ' . \Illuminate\Support\Str::random(5),
            'type'              => 'Document',
            'active'            => 'Active',
            'classification_id' => $this->classification->id,
            'learning_hours'    => 0.7500,
        ]);

        $contentMissing = Content::create([
            'title'             => 'Doc Missing LH ' . \Illuminate\Support\Str::random(5),
            'type'              => 'Document',
            'active'            => 'Active',
            'classification_id' => $this->classification->id,
            'learning_hours'    => null,
        ]);

        // has_lh filter
        $responseHas = $this->actingAs($this->adminUser)->get(route('content.index', [
            'classification' => $this->classification->id,
            'learning_hour'  => 'has_lh',
        ]));
        $responseHas->assertOk();
        $responseHas->assertSee($contentWith->title);
        $responseHas->assertDontSee($contentMissing->title);

        // missing_lh filter
        $responseMissing = $this->actingAs($this->adminUser)->get(route('content.index', [
            'classification' => $this->classification->id,
            'learning_hour'  => 'missing_lh',
        ]));
        $responseMissing->assertOk();
        $responseMissing->assertSee($contentMissing->title);
        $responseMissing->assertDontSee($contentWith->title);
    }

    /**
     * Test 6 — Sorting by learning hours ascending and descending.
     */
    public function test_learning_hour_numeric_sorting_asc_and_desc(): void
    {
        $lowLh = Content::create([
            'title'             => 'Low LH Sort ' . \Illuminate\Support\Str::random(5),
            'type'              => 'Document',
            'active'            => 'Active',
            'classification_id' => $this->classification->id,
            'learning_hours'    => 0.0500,
        ]);

        $highLh = Content::create([
            'title'             => 'High LH Sort ' . \Illuminate\Support\Str::random(5),
            'type'              => 'Document',
            'active'            => 'Active',
            'classification_id' => $this->classification->id,
            'learning_hours'    => 4.5000,
        ]);

        // Ascending
        $responseAsc = $this->actingAs($this->adminUser)->get(route('content.index', [
            'classification' => $this->classification->id,
            'sort'           => 'lh_asc',
        ]));
        $responseAsc->assertOk();
        $contentAsc = $responseAsc->getContent();
        $posLow = strpos($contentAsc, $lowLh->title);
        $posHigh = strpos($contentAsc, $highLh->title);
        $this->assertNotFalse($posLow);
        $this->assertNotFalse($posHigh);
        $this->assertLessThan($posHigh, $posLow, 'Low LH should appear before High LH in ascending order.');

        // Descending
        $responseDesc = $this->actingAs($this->adminUser)->get(route('content.index', [
            'classification' => $this->classification->id,
            'sort'           => 'lh_desc',
        ]));
        $responseDesc->assertOk();
        $contentDesc = $responseDesc->getContent();
        $posLowDesc = strpos($contentDesc, $lowLh->title);
        $posHighDesc = strpos($contentDesc, $highLh->title);
        $this->assertNotFalse($posLowDesc);
        $this->assertNotFalse($posHighDesc);
        $this->assertLessThan($posLowDesc, $posHighDesc, 'High LH should appear before Low LH in descending order.');
    }

    /**
     * Test 7 — Detail endpoint returns accurate JSON by ID and Slug with relations.
     */
    public function test_content_detail_endpoint_returns_accurate_json(): void
    {
        $content = Content::create([
            'title'             => 'Detail Modal Target ' . \Illuminate\Support\Str::random(5),
            'type'              => 'Document',
            'active'            => 'Active',
            'classification_id' => $this->classification->id,
            'duration'          => '00:08:30',
            'learning_hours'    => 0.1417,
            'page_duration'     => 34,
            'total_page'        => 15,
            'author'            => 'Dr. Tester',
            'description'       => 'Test detailed description for modal.',
            'views'             => 42,
            'exam'              => 'OK',
            'playlistonly'      => 'N',
        ]);

        $playlist = TrainingPlan::create([
            'title'       => 'QA Engineering Track ' . \Illuminate\Support\Str::random(5),
            'description' => 'Track description',
        ]);
        $content->trainingPlans()->attach($playlist->id, ['content_order' => 1]);

        // Query by numeric ID
        $responseById = $this->actingAs($this->adminUser)->getJson(route('content.details', $content->id));
        $responseById->assertOk();
        $responseById->assertJson([
            'success' => true,
            'data'    => [
                'id'                       => $content->id,
                'title'                    => $content->title,
                'slug'                     => $content->slug,
                'type'                     => 'Document',
                'type_label'               => 'Document',
                'classification'           => $this->classification->classification_name,
                'duration'                 => '00:08:30',
                'learning_hours'           => 0.1417,
                'learning_hours_formatted' => '0.1417 h',
                'page_duration'            => 34,
                'total_page'               => 15,
                'author'                   => 'Dr. Tester',
                'description'              => 'Test detailed description for modal.',
                'views'                    => 42,
                'exam'                     => 'OK',
                'playlistonly'             => 'N',
            ],
        ]);
        $responseById->assertJsonCount(1, 'data.playlists');
        $responseById->assertJsonPath('data.playlists.0.title', $playlist->title);

        // Query by slug
        $responseBySlug = $this->actingAs($this->adminUser)->getJson(route('content.details', $content->slug));
        $responseBySlug->assertOk();
        $responseBySlug->assertJsonPath('data.slug', $content->slug);
        $responseBySlug->assertJsonPath('data.learning_hours', 0.1417);
        $responseBySlug->assertJsonPath('data.learning_hours_formatted', '0.1417 h');
    }

    /**
     * Test 8 — Content detail endpoint returns 404 for nonexistent content.
     */
    public function test_content_detail_endpoint_returns_404_when_not_found(): void
    {
        $response = $this->actingAs($this->adminUser)->getJson(route('content.details', 99999999));
        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'Content not found.',
        ]);
    }

    /**
     * Test 9 — Content detail modal uses in-modal Tabler error UI and zero native alert().
     */
    public function test_content_detail_modal_uses_in_modal_error_and_does_not_contain_native_alert(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('content.index', [
            'classification' => $this->classification->id,
        ]));
        $response->assertOk();

        $content = $response->getContent();

        // Modal and UI state markup assertions
        $this->assertStringContainsString('id="contentDetailModal"', $content);
        $this->assertStringContainsString('id="contentDetailLoading"', $content);
        $this->assertStringContainsString('id="contentDetailError"', $content);
        $this->assertStringContainsString('id="detailError"', $content);
        $this->assertStringContainsString('id="contentDetailErrorMessage"', $content);
        $this->assertStringContainsString('id="btnRetryContentDetail"', $content);
        $this->assertStringContainsString('id="btnRetryText"', $content);
        $this->assertStringContainsString('id="contentDetailContent"', $content);

        // Status-specific in-modal error messages
        $this->assertStringContainsString('You do not have permission to view this content.', $content);
        $this->assertStringContainsString('Content not found.', $content);
        $this->assertStringContainsString('Unable to load content details. Please try again.', $content);
        $this->assertStringContainsString('Unable to connect to the server. Please try again.', $content);

        // Extract the Content Detail script block and ensure no native popup calls exist
        $scriptStart = strpos($content, '// View Content Detail Handler');
        $this->assertNotFalse($scriptStart, 'Content Detail Handler script must be present.');
        $detailScript = substr($content, $scriptStart);

        $this->assertDoesNotMatchRegularExpression('/\balert\s*\(/i', $detailScript, 'Content Detail flow must not call native alert().');
        $this->assertDoesNotMatchRegularExpression('/\bconfirm\s*\(/i', $detailScript, 'Content Detail flow must not call native confirm().');
        $this->assertDoesNotMatchRegularExpression('/\bprompt\s*\(/i', $detailScript, 'Content Detail flow must not call native prompt().');
    }

    /**
     * Test 10 — More Filters modal preserves quick search 'q' parameter.
     */
    public function test_more_filters_modal_preserves_quick_search_parameter(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('content.index', [
            'q' => 'Machine Learning',
        ]));
        $response->assertOk();

        $content = $response->getContent();

        // More Filters modal must preserve 'q' query parameter
        $this->assertStringContainsString(
            '<input type="hidden" name="q" value="Machine Learning">',
            $content,
            'More Filters modal must contain hidden input name="q" with the active search value.'
        );
    }

    /**
     * Test 11 — Quick filter form preserves active More Filter parameters via hidden inputs.
     */
    public function test_quick_filter_form_preserves_more_filter_parameters(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('content.index', [
            'min_learning_hour' => '0.2500',
            'max_learning_hour' => '1.5000',
            'learning_hour'     => '1-3h',
            'duration'          => '10_30',
            'author'            => 'John Doe',
            'sort'              => 'lh_asc',
        ]));
        $response->assertOk();

        $content = $response->getContent();

        // Quick Filter form must contain hidden inputs for active More Filter parameters
        $this->assertStringContainsString('<input type="hidden" name="min_learning_hour" value="0.2500">', $content);
        $this->assertStringContainsString('<input type="hidden" name="max_learning_hour" value="1.5000">', $content);
        $this->assertStringContainsString('<input type="hidden" name="learning_hour" value="1-3h">', $content);
        $this->assertStringContainsString('<input type="hidden" name="duration" value="10_30">', $content);
        $this->assertStringContainsString('<input type="hidden" name="author" value="John Doe">', $content);
        $this->assertStringContainsString('<input type="hidden" name="sort" value="lh_asc">', $content);
    }

    /**
     * Test 12 — Responsive layout and dark-mode CSS classes are applied and dead CSS is removed.
     */
    public function test_responsive_and_dark_mode_classes_present_on_content_management(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('content.index'));
        $response->assertOk();

        $content = $response->getContent();

        // Hero card & icon classes
        $this->assertStringContainsString('content-hero-card', $content);
        $this->assertStringContainsString('content-hero-icon', $content);

        // Header buttons flex-wrap
        $this->assertStringContainsString('flex-wrap align-items-center gap-2', $content);

        // Stat cards responsive 2x2 grid & card class
        $this->assertStringContainsString('col-6 col-md-3', $content);
        $this->assertStringContainsString('content-stat-card', $content);

        // Autocomplete containers must not have hardcoded inline background-color: #ffffff
        $this->assertDoesNotMatchRegularExpression(
            '/id="manage-content-search-results"[^>]*background-color:\s*#ffffff/i',
            $content
        );
        $this->assertDoesNotMatchRegularExpression(
            '/id="manage-content-autocomplete-list"[^>]*background-color:\s*#ffffff/i',
            $content
        );
        $this->assertDoesNotMatchRegularExpression(
            '/id="manage-content-search-footer"[^>]*background-color:\s*#f8f9fa/i',
            $content
        );

        // Dead CSS classes must be removed from the page style block
        $styleStart = strpos($content, '<style>');
        $styleEnd = strpos($content, '</style>', $styleStart);
        $this->assertNotFalse($styleStart);
        $this->assertNotFalse($styleEnd);
        $styleContent = substr($content, $styleStart, $styleEnd - $styleStart);

        $this->assertStringNotContainsString('.btn-back', $styleContent);
        $this->assertStringNotContainsString('.btn-quick-reset', $styleContent);
        $this->assertStringNotContainsString('.admin-gallery-stat', $styleContent);
        $this->assertStringNotContainsString('.content-manage-filter', $styleContent);
    }

    public function test_content_create_view_renders_learning_hours_field_and_synchronize_button(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('content.create'));
        $response->assertOk();

        $content = $response->getContent();

        // Must render learning hours input with name and ID
        $this->assertMatchesRegularExpression(
            '/<input[^>]*name="learning_hours"[^>]*id="learning_hours"/i',
            $content
        );

        // Input on create must NOT be readonly (preserves manual input)
        $this->assertDoesNotMatchRegularExpression(
            '/<input[^>]*name="learning_hours"[^>]*readonly/i',
            $content
        );

        // Must render Synchronize button with ID #btn-sync-content-lh
        $this->assertMatchesRegularExpression(
            '/<button[^>]*id="btn-sync-content-lh"[^>]*>.*?Synchronize.*?<\/button>/s',
            $content
        );
    }

    public function test_content_store_saves_synchronized_learning_hours(): void
    {
        $this->actingAs($this->adminUser);

        $title = 'Test Synchronized LH Content ' . \Illuminate\Support\Str::random(5);
        $response = $this->post(route('content.store'), [
            'title'                 => $title,
            'type'                  => 'Video',
            'classification'        => $this->classification->id,
            'duration'              => '00:02:30',
            'learning_hours'        => 0.0417,
            'date'                  => '2026-09-08',
            'action'                => 'save',
        ]);

        $response->assertRedirect(route('content.index'));

        $content = Content::where('title', $title)->first();
        $this->assertNotNull($content);
        $this->assertEquals(0.0417, (float) $content->learning_hours);
        $this->assertSame('00:02:30', $content->duration);
    }
}

