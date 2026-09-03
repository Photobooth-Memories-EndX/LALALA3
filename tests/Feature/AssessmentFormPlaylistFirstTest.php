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

class AssessmentFormPlaylistFirstTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected Classification $classification;
    protected TrainingPlan $playlistA;
    protected TrainingPlan $playlistB;
    protected Content $contentA1;
    protected Content $contentA2;
    protected Content $contentB1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'team'       => 'Admin Team',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'form-adm-' . Str::uuid(),
            'sap_code'   => 'SAP-FORM-' . rand(1000, 9999),
            'role'       => 2,
            'active'     => 1,
        ]);

        $this->classification = Classification::first() ?? Classification::create([
            'name'        => 'Form Test Class ' . Str::random(5),
            'description' => 'Test Desc',
        ]);

        $this->contentA1 = Content::create([
            'title'             => 'Content A1 ' . Str::random(5),
            'type'              => 'video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
        ]);

        $this->contentA2 = Content::create([
            'title'             => 'Content A2 ' . Str::random(5),
            'type'              => 'video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
        ]);

        $this->contentB1 = Content::create([
            'title'             => 'Content B1 ' . Str::random(5),
            'type'              => 'video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
        ]);

        $this->playlistA = TrainingPlan::create([
            'title'       => 'Playlist A ' . Str::random(5),
            'description' => 'Playlist A Description',
            'status'      => 'published',
        ]);
        $this->playlistA->contents()->attach($this->contentA1->id, ['content_order' => 1]);
        $this->playlistA->contents()->attach($this->contentA2->id, ['content_order' => 2]);

        $this->playlistB = TrainingPlan::create([
            'title'       => 'Playlist B ' . Str::random(5),
            'description' => 'Playlist B Description',
            'status'      => 'published',
        ]);
        $this->playlistB->contents()->attach($this->contentB1->id, ['content_order' => 1]);
    }

    public function test_create_view_renders_playlist_wrapper_before_content_wrapper(): void
    {
        $response = $this->actingAs($this->admin)->get(route('assesment.create'));

        $response->assertStatus(200);
        $content = $response->getContent();

        $posPlaylistWrapper = strpos($content, 'id="trainingplan_id_wrapper"');
        $posContentWrapper  = strpos($content, 'id="content_id_wrapper"');

        $this->assertNotFalse($posPlaylistWrapper, 'trainingplan_id_wrapper must be present in HTML');
        $this->assertNotFalse($posContentWrapper, 'content_id_wrapper must be present in HTML');
        $this->assertLessThan(
            $posContentWrapper,
            $posPlaylistWrapper,
            'Playlist wrapper must appear BEFORE Learning Content wrapper in DOM'
        );
    }

    public function test_create_view_renders_searchable_select2_and_placeholders(): void
    {
        $response = $this->actingAs($this->admin)->get(route('assesment.create'));

        $response->assertStatus(200);
        $response->assertSee('data-placeholder="Search playlist..."', false);
        $response->assertSee('select2', false);
    }

    public function test_create_view_renders_playlist_options_in_dropdown(): void
    {
        $response = $this->actingAs($this->admin)->get(route('assesment.create'));

        $response->assertStatus(200);
        $html = $response->getContent();

        $this->assertStringContainsString('id="trainingplan_id"', $html);
        $this->assertStringContainsString('value="' . $this->playlistA->id . '"', $html);
        $this->assertStringContainsString($this->playlistA->title, $html);
        $this->assertStringContainsString('value="' . $this->playlistB->id . '"', $html);
        $this->assertStringContainsString($this->playlistB->title, $html);
    }

    public function test_route_alias_assessment_add_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('assessment.add'));

        $response->assertStatus(200);
        $response->assertSee('Create Assessment');
    }

    public function test_edit_view_playlist_mode_loads_existing_playlist_and_only_its_contents(): void
    {
        $assessment = Assesment::create([
            'title'           => 'Existing Playlist Ass ' . Str::random(5),
            'type'            => 'content',
            'content_for'     => 'playlist',
            'trainingplan_id' => $this->playlistA->id,
            'content_id'      => $this->contentA1->id,
            'status'          => 'OK',
            'passing_grade'   => 75,
            'total_remedials' => 1,
        ]);

        $response = $this->actingAs($this->admin)->get(route('assesment.edit', $assessment->slug));

        $response->assertStatus(200);

        // Content A1 and A2 should be in the options
        $response->assertSee('value="' . $this->contentA1->id . '" selected', false);
        $response->assertSee('value="' . $this->contentA2->id . '"', false);

        // Content B1 (from playlist B) should NOT be in content options
        $this->assertStringNotContainsString(
            '<option value="' . $this->contentB1->id . '"',
            $response->getContent()
        );

        // Playlist A should be selected
        $response->assertSee('value="' . $this->playlistA->id . '" selected', false);
    }

    public function test_edit_view_direct_content_mode_loads_global_contents(): void
    {
        $assessment = Assesment::create([
            'title'           => 'Existing Direct Content Ass ' . Str::random(5),
            'type'            => 'content',
            'content_for'     => 'content',
            'content_id'      => $this->contentA1->id,
            'status'          => 'OK',
            'passing_grade'   => 75,
            'total_remedials' => 1,
        ]);

        $response = $this->actingAs($this->admin)->get(route('assesment.edit', $assessment->slug));

        $response->assertStatus(200);
        $response->assertSee('value="' . $this->contentA1->id . '" selected', false);

        // All contents are available in direct content mode
        $response->assertSee('value="' . $this->contentB1->id . '"', false);
    }

    public function test_playlist_contents_endpoint_returns_contents_for_dependent_dropdown(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson(route('assesment.playlist.contents', $this->playlistA->id));

        $response->assertStatus(200);
        $response->assertJson([
            'success'  => true,
            'contents' => [
                [
                    'id'    => $this->contentA1->id,
                    'title' => $this->contentA1->title,
                    'text'  => $this->contentA1->title,
                ],
                [
                    'id'    => $this->contentA2->id,
                    'title' => $this->contentA2->title,
                    'text'  => $this->contentA2->title,
                ],
            ],
            'data' => [
                [
                    'id'    => $this->contentA1->id,
                    'title' => $this->contentA1->title,
                    'text'  => $this->contentA1->title,
                ],
            ],
            'results' => [
                [
                    'id'    => $this->contentA1->id,
                    'title' => $this->contentA1->title,
                    'text'  => $this->contentA1->title,
                ],
            ],
        ]);

        // Content B1 should not be in Playlist A response
        $contentIds = collect($response->json('contents'))->pluck('id')->all();
        $this->assertNotContains($this->contentB1->id, $contentIds);

        // Also test the alias route /manage/assessment/playlist/{id}/contents (two 's'es)
        $aliasResponse = $this->actingAs($this->admin)
            ->getJson('/manage/assessment/playlist/' . $this->playlistA->id . '/contents');
        $aliasResponse->assertStatus(200);
        $aliasResponse->assertJson(['success' => true]);
    }

    public function test_backend_validation_requires_playlist_when_content_for_is_playlist(): void
    {
        $response = $this->actingAs($this->admin)->post(route('assesment.store'), [
            'type'              => 'content',
            'content_for'       => 'playlist',
            'content_id'        => $this->contentA1->id,
            'title'             => 'Test Missing Playlist',
            'passing_grade'     => 75,
            'total_remedials'   => 1,
            'has_access_period' => '0',
        ]);

        $response->assertSessionHasErrors(['trainingplan_id']);
    }

    public function test_backend_validation_requires_content_when_type_is_content(): void
    {
        $response = $this->actingAs($this->admin)->post(route('assesment.store'), [
            'type'              => 'content',
            'content_for'       => 'playlist',
            'trainingplan_id'   => $this->playlistA->id,
            'title'             => 'Test Missing Content',
            'passing_grade'     => 75,
            'total_remedials'   => 1,
            'has_access_period' => '0',
        ]);

        $response->assertSessionHasErrors(['content_id']);
    }

    public function test_backend_validation_does_not_require_playlist_when_content_for_is_content(): void
    {
        $response = $this->actingAs($this->admin)->post(route('assesment.store'), [
            'type'              => 'content',
            'content_for'       => 'content',
            'content_id'        => $this->contentA1->id,
            'title'             => 'Valid Direct Content ' . Str::random(5),
            'passing_grade'     => 75,
            'total_remedials'   => 1,
            'has_access_period' => '0',
        ]);

        $response->assertSessionHasNoErrors();
    }

    public function test_store_and_update_playlist_assessment_persists_correctly(): void
    {
        // 1. Store
        $title = 'Store Test PL ' . Str::random(5);
        $response = $this->actingAs($this->admin)->post(route('assesment.store'), [
            'type'              => 'content',
            'content_for'       => 'playlist',
            'trainingplan_id'   => $this->playlistA->id,
            'content_id'        => $this->contentA1->id,
            'title'             => $title,
            'passing_grade'     => 80,
            'total_remedials'   => 2,
            'has_access_period' => '0',
        ]);

        $response->assertSessionHasNoErrors();
        $created = Assesment::where('title', $title)->firstOrFail();

        $this->assertEquals('content', $created->type);
        $this->assertEquals('playlist', $created->content_for);
        $this->assertEquals($this->playlistA->id, (int) $created->trainingplan_id);
        $this->assertEquals($this->contentA1->id, (int) $created->content_id);

        // 2. Update to Playlist B with Content B1
        $updateResponse = $this->actingAs($this->admin)->put(route('assesment.update', $created->slug), [
            'type'              => 'content',
            'content_for'       => 'playlist',
            'trainingplan_id'   => $this->playlistB->id,
            'content_id'        => $this->contentB1->id,
            'title'             => $title . ' Updated',
            'passing_grade'     => 85,
            'total_remedials'   => 3,
            'has_access_period' => '0',
        ]);

        $updateResponse->assertSessionHasNoErrors();
        $created->refresh();

        $this->assertEquals($this->playlistB->id, (int) $created->trainingplan_id);
        $this->assertEquals($this->contentB1->id, (int) $created->content_id);
    }

    public function test_create_view_with_playlist_mode_without_playlist_disables_content_select(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession([
                '_old_input' => [
                    'type'        => 'content',
                    'content_for' => 'playlist',
                ],
            ])
            ->get(route('assesment.create'));

        $response->assertStatus(200);
        $response->assertSee('Please select a Playlist first');
        $response->assertSee('disabled', false);
    }

    public function test_create_view_with_playlist_selected_loads_only_playlist_contents(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession([
                '_old_input' => [
                    'type'            => 'content',
                    'content_for'     => 'playlist',
                    'trainingplan_id' => $this->playlistA->id,
                ],
            ])
            ->get(route('assesment.create'));

        $response->assertStatus(200);
        $response->assertSee('value="' . $this->contentA1->id . '"', false);
        $response->assertSee('value="' . $this->contentA2->id . '"', false);
        $this->assertStringNotContainsString(
            '<option value="' . $this->contentB1->id . '"',
            $response->getContent()
        );
    }
}
