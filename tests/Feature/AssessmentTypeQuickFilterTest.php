<?php

namespace Tests\Feature;

use App\Models\Assesment;
use App\Models\Classification;
use App\Models\Content;
use App\Models\TrainingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class AssessmentTypeQuickFilterTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected Classification $classification;
    protected Content $content;
    protected TrainingPlan $playlist;

    protected Assesment $assessmentContentDirect;
    protected Assesment $assessmentContentPlaylist;
    protected Assesment $assessmentPlaylist;
    protected Assesment $assessmentGlobal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'team'       => 'Admin Team',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'adm-filt-' . Str::uuid(),
            'sap_code'   => 'SAP-FILT-' . rand(1000, 9999),
            'role'       => 2,
            'active'     => 1,
        ]);

        $this->classification = Classification::first() ?? Classification::create([
            'name'        => 'Test Class ' . Str::random(5),
            'description' => 'Test Desc',
        ]);

        $this->content = Content::create([
            'title'             => 'Filter Content ' . Str::random(5),
            'type'              => 'video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
        ]);

        $this->playlist = TrainingPlan::create([
            'title'       => 'Filter Playlist ' . Str::random(5),
            'description' => 'Playlist Description',
            'status'      => 'published',
        ]);
        $this->playlist->contents()->attach($this->content->id, ['content_order' => 1]);

        $uniqueTag = Str::random(8);

        // 1. Content (All Content) -> type=content, assasment_content_for=content
        $this->assessmentContentDirect = Assesment::create([
            'title'                 => "DirectContent_{$uniqueTag}",
            'type'                  => 'content',
            'assasment_content_for' => 'content',
            'content_id'            => $this->content->id,
            'status'                => 'OK',
            'time'                  => 30,
            'minimum_score'         => 70,
            'max_attempt'           => 3,
        ]);

        // 2. Content (Playlist) -> type=content, assasment_content_for=playlist
        $this->assessmentContentPlaylist = Assesment::create([
            'title'                 => "PlaylistContent_{$uniqueTag}",
            'type'                  => 'content',
            'assasment_content_for' => 'playlist',
            'trainingplan_id'       => $this->playlist->id,
            'content_id'            => $this->content->id,
            'status'                => 'OK',
            'time'                  => 30,
            'minimum_score'         => 70,
            'max_attempt'           => 3,
        ]);

        // 3. Playlist -> type=trainingplan, assasment_content_for=content
        $this->assessmentPlaylist = Assesment::create([
            'title'                 => "PurePlaylist_{$uniqueTag}",
            'type'                  => 'trainingplan',
            'assasment_content_for' => 'content',
            'trainingplan_id'       => $this->playlist->id,
            'status'                => 'OK',
            'time'                  => 30,
            'minimum_score'         => 70,
            'max_attempt'           => 3,
        ]);

        // 4. Global -> type=global, assasment_content_for=content
        $this->assessmentGlobal = Assesment::create([
            'title'                 => "Global_{$uniqueTag}",
            'type'                  => 'global',
            'assasment_content_for' => 'content',
            'status'                => 'OK',
            'time'                  => 30,
            'minimum_score'         => 70,
            'max_attempt'           => 3,
        ]);
    }

    /**
     * Test quick filter dropdown renders all expected options.
     */
    public function test_quick_filter_dropdown_renders_four_categories_plus_all(): void
    {
        $response = $this->actingAs($this->admin)->get(route('assesment.index'));

        $response->assertStatus(200);
        $html = $response->getContent();

        $this->assertStringContainsString('id="assessmentQuickType"', $html);
        $this->assertStringContainsString('<option value="">All Types</option>', $html);
        $this->assertStringContainsString('value="content_content"', $html);
        $this->assertStringContainsString('Content (All Content)', $html);
        $this->assertStringContainsString('value="content_playlist"', $html);
        $this->assertStringContainsString('Content (Playlist)', $html);
        $this->assertStringContainsString('value="trainingplan"', $html);
        $this->assertStringContainsString('Playlist', $html);
        $this->assertStringContainsString('value="global"', $html);
        $this->assertStringContainsString('Global', $html);
    }

    /**
     * Test selected state is preserved based on request('type').
     */
    public function test_quick_filter_preserves_selected_option(): void
    {
        $response = $this->actingAs($this->admin)->get(route('assesment.index', ['type' => 'content_playlist']));
        $response->assertStatus(200);
        $html = $response->getContent();

        $this->assertMatchesRegularExpression('/<option\s+value="content_playlist"\s+selected>/', $html);
    }

    /**
     * Test filter: content_content returns only direct content assessments.
     */
    public function test_filter_content_content(): void
    {
        $response = $this->actingAs($this->admin)->get(route('assesment.index', [
            'type' => 'content_content',
        ]));

        $response->assertStatus(200);
        $ids = $response->viewData('assesment')->pluck('id')->toArray();

        $this->assertContains($this->assessmentContentDirect->id, $ids);
        $this->assertNotContains($this->assessmentContentPlaylist->id, $ids);
        $this->assertNotContains($this->assessmentPlaylist->id, $ids);
        $this->assertNotContains($this->assessmentGlobal->id, $ids);
    }

    /**
     * Test filter: content_playlist returns only playlist content assessments.
     */
    public function test_filter_content_playlist(): void
    {
        $response = $this->actingAs($this->admin)->get(route('assesment.index', [
            'type' => 'content_playlist',
        ]));

        $response->assertStatus(200);
        $ids = $response->viewData('assesment')->pluck('id')->toArray();

        $this->assertNotContains($this->assessmentContentDirect->id, $ids);
        $this->assertContains($this->assessmentContentPlaylist->id, $ids);
        $this->assertNotContains($this->assessmentPlaylist->id, $ids);
        $this->assertNotContains($this->assessmentGlobal->id, $ids);
    }

    /**
     * Test filter: trainingplan returns only pure playlist assessments.
     */
    public function test_filter_trainingplan(): void
    {
        $response = $this->actingAs($this->admin)->get(route('assesment.index', [
            'type' => 'trainingplan',
        ]));

        $response->assertStatus(200);
        $ids = $response->viewData('assesment')->pluck('id')->toArray();

        $this->assertNotContains($this->assessmentContentDirect->id, $ids);
        $this->assertNotContains($this->assessmentContentPlaylist->id, $ids);
        $this->assertContains($this->assessmentPlaylist->id, $ids);
        $this->assertNotContains($this->assessmentGlobal->id, $ids);
    }

    /**
     * Test filter: global returns only global assessments.
     */
    public function test_filter_global(): void
    {
        $response = $this->actingAs($this->admin)->get(route('assesment.index', [
            'type' => 'global',
        ]));

        $response->assertStatus(200);
        $ids = $response->viewData('assesment')->pluck('id')->toArray();

        $this->assertNotContains($this->assessmentContentDirect->id, $ids);
        $this->assertNotContains($this->assessmentContentPlaylist->id, $ids);
        $this->assertNotContains($this->assessmentPlaylist->id, $ids);
        $this->assertContains($this->assessmentGlobal->id, $ids);
    }

    /**
     * Test legacy backward compatibility: ?type=content returns both direct and playlist content.
     */
    public function test_legacy_filter_type_content_returns_both(): void
    {
        $response = $this->actingAs($this->admin)->get(route('assesment.index', [
            'type' => 'content',
        ]));

        $response->assertStatus(200);
        $ids = $response->viewData('assesment')->pluck('id')->toArray();

        $this->assertContains($this->assessmentContentDirect->id, $ids);
        $this->assertContains($this->assessmentContentPlaylist->id, $ids);
        $this->assertNotContains($this->assessmentPlaylist->id, $ids);
        $this->assertNotContains($this->assessmentGlobal->id, $ids);
    }

    /**
     * Test combined filter with status.
     */
    public function test_combined_filter_type_and_status(): void
    {
        $inactiveContent = Assesment::create([
            'title'                 => 'InactiveContent_' . Str::random(8),
            'type'                  => 'content',
            'assasment_content_for' => 'content',
            'content_id'            => $this->content->id,
            'status'                => 'NOT',
            'time'                  => 30,
            'minimum_score'         => 70,
            'max_attempt'           => 3,
        ]);

        // Filter type=content_content and status=OK -> should NOT include InactiveContent
        $response = $this->actingAs($this->admin)->get(route('assesment.index', [
            'type'   => 'content_content',
            'status' => 'OK',
        ]));
        $response->assertStatus(200);
        $ids = $response->viewData('assesment')->pluck('id')->toArray();
        $this->assertContains($this->assessmentContentDirect->id, $ids);
        $this->assertNotContains($inactiveContent->id, $ids);

        // Filter type=content_content and status=NOT -> should include InactiveContent and NOT OK
        $response2 = $this->actingAs($this->admin)->get(route('assesment.index', [
            'type'   => 'content_content',
            'status' => 'NOT',
        ]));
        $response2->assertStatus(200);
        $ids2 = $response2->viewData('assesment')->pluck('id')->toArray();
        $this->assertContains($inactiveContent->id, $ids2);
        $this->assertNotContains($this->assessmentContentDirect->id, $ids2);
    }

    /**
     * Test pagination preserves query string.
     */
    public function test_pagination_preserves_type_query_string(): void
    {
        $response = $this->actingAs($this->admin)->get(route('assesment.index', [
            'type' => 'content_playlist',
        ]));

        $response->assertStatus(200);
        $paginator = $response->viewData('assesment');
        $this->assertStringContainsString('type=content_playlist', $paginator->url(1));
    }
}

