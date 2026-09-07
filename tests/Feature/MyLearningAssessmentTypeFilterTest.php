<?php

namespace Tests\Feature;

use App\Models\Assesment;
use App\Models\AssesmentAssigned;
use App\Models\Classification;
use App\Models\Content;
use App\Models\TrainingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class MyLearningAssessmentTypeFilterTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected User $otherUser;
    protected Classification $classification;
    protected Content $content;
    protected TrainingPlan $playlist;

    protected Assesment $assessmentDirectContent;
    protected Assesment $assessmentPlaylistContent;
    protected Assesment $assessmentPurePlaylist;
    protected Assesment $assessmentGlobal;
    protected Assesment $unauthorizedAssessment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'team'       => 'Learner Team',
            'department' => 'Operations',
            'division'   => 'Plant',
            'ssohash'    => 'lrn-' . Str::uuid(),
            'sap_code'   => 'SAP-LRN-' . rand(1000, 9999),
            'id_emp'     => 'EMP-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->otherUser = User::factory()->create([
            'team'       => 'Other Team',
            'department' => 'HR',
            'division'   => 'HQ',
            'ssohash'    => 'oth-' . Str::uuid(),
            'sap_code'   => 'SAP-OTH-' . rand(1000, 9999),
            'id_emp'     => 'EMP-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->classification = Classification::first() ?? Classification::create([
            'name'        => 'Test Class ' . Str::random(5),
            'description' => 'Test Desc',
        ]);

        $this->content = Content::create([
            'title'             => 'ML Content ' . Str::random(5),
            'type'              => 'video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
        ]);

        $this->playlist = TrainingPlan::create([
            'title'       => 'ML Playlist ' . Str::random(5),
            'description' => 'ML Playlist Description',
            'status'      => 'published',
        ]);
        $this->playlist->contents()->attach($this->content->id, ['content_order' => 1]);

        $tag = Str::random(8);

        // 1. Content (All Content): type=content, assasment_content_for=content
        $this->assessmentDirectContent = Assesment::create([
            'title'                 => "DirectContent_{$tag}",
            'type'                  => 'content',
            'assasment_content_for' => 'content',
            'content_id'            => $this->content->id,
            'status'                => 'OK',
            'time'                  => 30,
            'minimum_score'         => 70,
            'max_attempt'           => 3,
        ]);
        AssesmentAssigned::create([
            'assesment_id'         => $this->assessmentDirectContent->id,
            'assigned_id'          => $this->user->id_emp,
            'assigned_type'        => 'user',
            'assigned_description' => 'Test',
        ]);

        // 2. Content (Playlist): type=content, assasment_content_for=playlist
        $this->assessmentPlaylistContent = Assesment::create([
            'title'                 => "PlaylistContent_{$tag}",
            'type'                  => 'content',
            'assasment_content_for' => 'playlist',
            'trainingplan_id'       => $this->playlist->id,
            'content_id'            => $this->content->id,
            'status'                => 'OK',
            'time'                  => 30,
            'minimum_score'         => 70,
            'max_attempt'           => 3,
        ]);
        AssesmentAssigned::create([
            'assesment_id'         => $this->assessmentPlaylistContent->id,
            'assigned_id'          => $this->user->id_emp,
            'assigned_type'        => 'user',
            'assigned_description' => 'Test',
        ]);

        // 3. Playlist: type=trainingplan, assasment_content_for=content
        $this->assessmentPurePlaylist = Assesment::create([
            'title'                 => "PurePlaylist_{$tag}",
            'type'                  => 'trainingplan',
            'assasment_content_for' => 'content',
            'trainingplan_id'       => $this->playlist->id,
            'status'                => 'OK',
            'time'                  => 30,
            'minimum_score'         => 70,
            'max_attempt'           => 3,
        ]);
        AssesmentAssigned::create([
            'assesment_id'         => $this->assessmentPurePlaylist->id,
            'assigned_id'          => $this->user->id_emp,
            'assigned_type'        => 'user',
            'assigned_description' => 'Test',
        ]);

        // 4. Global: type=global, assasment_content_for=content
        $this->assessmentGlobal = Assesment::create([
            'title'                 => "Global_{$tag}",
            'type'                  => 'global',
            'assasment_content_for' => 'content',
            'status'                => 'OK',
            'time'                  => 30,
            'minimum_score'         => 70,
            'max_attempt'           => 3,
        ]);
        AssesmentAssigned::create([
            'assesment_id'         => $this->assessmentGlobal->id,
            'assigned_id'          => $this->user->id_emp,
            'assigned_type'        => 'user',
            'assigned_description' => 'Test',
        ]);

        // 5. Unauthorized assessment assigned ONLY to other user
        $this->unauthorizedAssessment = Assesment::create([
            'title'                 => "Unauthorized_{$tag}",
            'type'                  => 'content',
            'assasment_content_for' => 'content',
            'content_id'            => $this->content->id,
            'status'                => 'OK',
            'time'                  => 30,
            'minimum_score'         => 70,
            'max_attempt'           => 3,
        ]);
        AssesmentAssigned::create([
            'assesment_id'         => $this->unauthorizedAssessment->id,
            'assigned_id'          => $this->otherUser->id_emp,
            'assigned_type'        => 'user',
            'assigned_description' => 'Test',
        ]);
    }

    /**
     * TEST 1: Dropdown UI renders 4 types plus All, without single 'Content' option.
     */
    public function test_my_learning_assessment_dropdown_renders_four_categories_plus_all(): void
    {
        $response = $this->actingAs($this->user)->get(route('user.learning.dashboard.partial', [
            'tab' => 'assessment',
        ]));

        $response->assertStatus(200);
        $html = $response->getContent();

        // Check dropdown items exist
        $this->assertStringContainsString('data-value="all"', $html);
        $this->assertStringContainsString('All', $html);

        $this->assertStringContainsString('data-value="content_content"', $html);
        $this->assertStringContainsString('Content (All Content)', $html);

        $this->assertStringContainsString('data-value="content_playlist"', $html);
        $this->assertStringContainsString('Content (Playlist)', $html);

        $this->assertStringContainsString('data-value="trainingplan"', $html);
        $this->assertStringContainsString('Playlist', $html);

        $this->assertStringContainsString('data-value="global"', $html);
        $this->assertStringContainsString('Global', $html);

        // Ensure "Content" as a standalone quick-item does not exist in quick filter
        $this->assertStringNotContainsString('data-value="content"', $html);
    }

    /**
     * TEST 2: Filter All returns all assigned assessments for user, but not unauthorized.
     */
    public function test_filter_all_returns_all_authorized_assessments(): void
    {
        $response = $this->actingAs($this->user)->get(route('user.learning.dashboard.partial', [
            'tab' => 'assessment',
            'assessment_filter_type' => 'all',
        ]));

        $response->assertStatus(200);
        $assessments = $response->viewData('assessments');
        $ids = $assessments->pluck('id')->toArray();

        $this->assertContains($this->assessmentDirectContent->id, $ids);
        $this->assertContains($this->assessmentPlaylistContent->id, $ids);
        $this->assertContains($this->assessmentPurePlaylist->id, $ids);
        $this->assertContains($this->assessmentGlobal->id, $ids);
        $this->assertNotContains($this->unauthorizedAssessment->id, $ids);
    }

    /**
     * TEST 3: Filter Content (All Content) returns only type=content & assasment_content_for=content.
     */
    public function test_filter_content_content_returns_only_direct_content(): void
    {
        $response = $this->actingAs($this->user)->get(route('user.learning.dashboard.partial', [
            'tab' => 'assessment',
            'assessment_filter_type' => 'content_content',
        ]));

        $response->assertStatus(200);
        $ids = $response->viewData('assessments')->pluck('id')->toArray();

        $this->assertContains($this->assessmentDirectContent->id, $ids);
        $this->assertNotContains($this->assessmentPlaylistContent->id, $ids);
        $this->assertNotContains($this->assessmentPurePlaylist->id, $ids);
        $this->assertNotContains($this->assessmentGlobal->id, $ids);
        $this->assertNotContains($this->unauthorizedAssessment->id, $ids);

        // Also test using fallback query param ?type=content_content
        $response2 = $this->actingAs($this->user)->get(route('user.learning.dashboard.partial', [
            'tab'  => 'assessment',
            'type' => 'content_content',
        ]));
        $response2->assertStatus(200);
        $ids2 = $response2->viewData('assessments')->pluck('id')->toArray();
        $this->assertContains($this->assessmentDirectContent->id, $ids2);
        $this->assertNotContains($this->assessmentPlaylistContent->id, $ids2);
    }

    /**
     * TEST 4: Filter Content (Playlist) returns only type=content & assasment_content_for=playlist.
     */
    public function test_filter_content_playlist_returns_only_playlist_content(): void
    {
        $response = $this->actingAs($this->user)->get(route('user.learning.dashboard.partial', [
            'tab' => 'assessment',
            'assessment_filter_type' => 'content_playlist',
        ]));

        $response->assertStatus(200);
        $ids = $response->viewData('assessments')->pluck('id')->toArray();

        $this->assertNotContains($this->assessmentDirectContent->id, $ids);
        $this->assertContains($this->assessmentPlaylistContent->id, $ids);
        $this->assertNotContains($this->assessmentPurePlaylist->id, $ids);
        $this->assertNotContains($this->assessmentGlobal->id, $ids);

        // Also test fallback query param ?type=content_playlist
        $response2 = $this->actingAs($this->user)->get(route('user.learning.dashboard.partial', [
            'tab'  => 'assessment',
            'type' => 'content_playlist',
        ]));
        $response2->assertStatus(200);
        $ids2 = $response2->viewData('assessments')->pluck('id')->toArray();
        $this->assertContains($this->assessmentPlaylistContent->id, $ids2);
        $this->assertNotContains($this->assessmentDirectContent->id, $ids2);
    }

    /**
     * TEST 5: Filter Playlist returns only type=trainingplan (NOT content_playlist).
     */
    public function test_filter_trainingplan_returns_only_pure_playlist(): void
    {
        $response = $this->actingAs($this->user)->get(route('user.learning.dashboard.partial', [
            'tab' => 'assessment',
            'assessment_filter_type' => 'trainingplan',
        ]));

        $response->assertStatus(200);
        $ids = $response->viewData('assessments')->pluck('id')->toArray();

        $this->assertNotContains($this->assessmentDirectContent->id, $ids);
        $this->assertNotContains($this->assessmentPlaylistContent->id, $ids);
        $this->assertContains($this->assessmentPurePlaylist->id, $ids);
        $this->assertNotContains($this->assessmentGlobal->id, $ids);

        // Legacy value 'playlist' also maps to trainingplan
        $response2 = $this->actingAs($this->user)->get(route('user.learning.dashboard.partial', [
            'tab' => 'assessment',
            'assessment_filter_type' => 'playlist',
        ]));
        $response2->assertStatus(200);
        $ids2 = $response2->viewData('assessments')->pluck('id')->toArray();
        $this->assertContains($this->assessmentPurePlaylist->id, $ids2);
        $this->assertNotContains($this->assessmentPlaylistContent->id, $ids2);
    }

    /**
     * TEST 6: Filter Global returns only type=global.
     */
    public function test_filter_global_returns_only_global(): void
    {
        $response = $this->actingAs($this->user)->get(route('user.learning.dashboard.partial', [
            'tab' => 'assessment',
            'assessment_filter_type' => 'global',
        ]));

        $response->assertStatus(200);
        $ids = $response->viewData('assessments')->pluck('id')->toArray();

        $this->assertNotContains($this->assessmentDirectContent->id, $ids);
        $this->assertNotContains($this->assessmentPlaylistContent->id, $ids);
        $this->assertNotContains($this->assessmentPurePlaylist->id, $ids);
        $this->assertContains($this->assessmentGlobal->id, $ids);
    }

    /**
     * TEST 7: Legacy backward compatibility: ?assessment_filter_type=content returns both content variants.
     */
    public function test_legacy_content_filter_returns_both_content_types(): void
    {
        $response = $this->actingAs($this->user)->get(route('user.learning.dashboard.partial', [
            'tab' => 'assessment',
            'assessment_filter_type' => 'content',
        ]));

        $response->assertStatus(200);
        $ids = $response->viewData('assessments')->pluck('id')->toArray();

        $this->assertContains($this->assessmentDirectContent->id, $ids);
        $this->assertContains($this->assessmentPlaylistContent->id, $ids);
        $this->assertNotContains($this->assessmentPurePlaylist->id, $ids);
        $this->assertNotContains($this->assessmentGlobal->id, $ids);
    }

    /**
     * TEST 8: Row attributes for DataTables client-side filtering match exact 4 categories.
     */
    public function test_rendered_rows_have_accurate_data_assessment_type_attributes(): void
    {
        $response = $this->actingAs($this->user)->get(route('user.learning.dashboard.partial', [
            'tab' => 'assessment',
        ]));

        $response->assertStatus(200);
        $html = $response->getContent();

        $this->assertStringContainsString('data-assessment-type="content_content"', $html);
        $this->assertStringContainsString('data-assessment-type="content_playlist"', $html);
        $this->assertStringContainsString('data-assessment-type="trainingplan"', $html);
        $this->assertStringContainsString('data-assessment-type="global"', $html);
    }

    /**
     * TEST 9: Access restriction regression test.
     * Unauthorized assessment is never returned regardless of type filter used.
     */
    public function test_access_restriction_is_preserved_across_filters(): void
    {
        foreach (['all', 'content_content', 'content_playlist', 'trainingplan', 'global', 'content'] as $type) {
            $response = $this->actingAs($this->user)->get(route('user.learning.dashboard.partial', [
                'tab' => 'assessment',
                'assessment_filter_type' => $type,
            ]));

            $response->assertStatus(200);
            $ids = $response->viewData('assessments')->pluck('id')->toArray();
            $this->assertNotContains($this->unauthorizedAssessment->id, $ids, "Failed asserting unauthorized assessment is excluded for type: {$type}");
        }
    }

    /**
     * TEST 10: Selected option state in Quick Filter dropdown.
     */
    public function test_quick_filter_preserves_selected_state_for_each_type(): void
    {
        $response = $this->actingAs($this->user)->get(route('user.learning.dashboard.partial', [
            'tab'                    => 'assessment',
            'assessment_filter_type' => 'content_playlist',
        ]));

        $response->assertStatus(200);
        $html = $response->getContent();

        // Button chip text displays selected label
        $this->assertStringContainsString('Content (Playlist)', $html);
        // Container has data-selected-value="content_playlist"
        $this->assertStringContainsString('data-selected-value="content_playlist"', $html);
    }

    /**
     * TEST 11: Cross Filter: Type + Search.
     */
    public function test_cross_filter_type_and_search(): void
    {
        $response = $this->actingAs($this->user)->get(route('user.learning.dashboard.partial', [
            'tab'                    => 'assessment',
            'assessment_filter_type' => 'content_content',
            'assessment_search'      => $this->assessmentDirectContent->title,
        ]));

        $response->assertStatus(200);
        $ids = $response->viewData('assessments')->pluck('id')->toArray();
        $this->assertContains($this->assessmentDirectContent->id, $ids);
        $this->assertNotContains($this->assessmentPlaylistContent->id, $ids);

        // Searching for playlist assessment with content_content filter -> returns nothing
        $response2 = $this->actingAs($this->user)->get(route('user.learning.dashboard.partial', [
            'tab'                    => 'assessment',
            'assessment_filter_type' => 'content_content',
            'assessment_search'      => $this->assessmentPlaylistContent->title,
        ]));

        $response2->assertStatus(200);
        $ids2 = $response2->viewData('assessments')->pluck('id')->toArray();
        $this->assertEmpty($ids2);
    }

    /**
     * TEST 12: Cross Filter: Type + Status.
     */
    public function test_cross_filter_type_and_status(): void
    {
        $response = $this->actingAs($this->user)->get(route('user.learning.dashboard.partial', [
            'tab'                      => 'assessment',
            'assessment_filter_type'   => 'content_playlist',
            'assessment_filter_status' => 'not_started',
        ]));

        $response->assertStatus(200);
        $ids = $response->viewData('assessments')->pluck('id')->toArray();
        $this->assertContains($this->assessmentPlaylistContent->id, $ids);
        $this->assertNotContains($this->assessmentDirectContent->id, $ids);
    }
}

