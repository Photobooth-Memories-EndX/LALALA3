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

class AssessmentSimplifiedTypeDropdownTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected Classification $classification;
    protected TrainingPlan $playlist;
    protected Content $content;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'team'       => 'Admin Team',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'adm-simp-' . Str::uuid(),
            'sap_code'   => 'SAP-SIMP-' . rand(1000, 9999),
            'role'       => 2,
            'active'     => 1,
        ]);

        $this->classification = Classification::first() ?? Classification::create([
            'name'        => 'Test Class ' . Str::random(5),
            'description' => 'Test Desc',
        ]);

        $this->content = Content::create([
            'title'             => 'Test Content ' . Str::random(5),
            'type'              => 'video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
        ]);

        $this->playlist = TrainingPlan::create([
            'title'       => 'Test Playlist ' . Str::random(5),
            'description' => 'Playlist Description',
            'status'      => 'published',
        ]);
        $this->playlist->contents()->attach($this->content->id, ['content_order' => 1]);
    }

    /**
     * 1. CREATE VIEW: Renders single unified dropdown with 4 options and hidden inputs.
     */
    public function test_create_view_renders_single_dropdown_with_four_options(): void
    {
        $response = $this->actingAs($this->admin)->get(route('assesment.create'));

        $response->assertStatus(200);
        $html = $response->getContent();

        // Single unified dropdown exists
        $this->assertStringContainsString('id="assessment_combined_type"', $html);

        // 4 Options with exact labels exist
        $this->assertStringContainsString('value="content_content"', $html);
        $this->assertStringContainsString('Assessment Content For Content', $html);

        $this->assertStringContainsString('value="content_playlist"', $html);
        $this->assertStringContainsString('Assessment Content For Playlist', $html);

        $this->assertStringContainsString('value="trainingplan"', $html);
        $this->assertStringContainsString('Assessment Playlist', $html);

        $this->assertStringContainsString('value="global"', $html);
        $this->assertStringContainsString('Assessment Global', $html);

        // Hidden inputs exist for backend contract
        $this->assertStringContainsString('type="hidden" name="type" id="assessment_type"', $html);
        $this->assertStringContainsString('type="hidden" name="assasment_content_for" id="assasment_content_for"', $html);

        // Separate visible dropdown is NOT rendered
        $this->assertStringNotContainsString('id="content_for_wrapper"', $html);
    }

    /**
     * 2. STORE: Assessment Content For Content (type=content, assasment_content_for=content).
     */
    public function test_store_assessment_content_for_content(): void
    {
        $title = 'Test Content For Content ' . Str::random(5);
        $response = $this->actingAs($this->admin)->post(route('assesment.store'), [
            'type'                  => 'content',
            'assasment_content_for' => 'content',
            'content_id'            => $this->content->id,
            'title'                 => $title,
            'passing_grade'         => 80,
            'total_remedials'       => 1,
            'has_access_period'     => '0',
        ]);

        $response->assertSessionHasNoErrors();
        $created = Assesment::where('title', $title)->firstOrFail();

        $this->assertSame('content', $created->type);
        $this->assertSame('content', $created->assasment_content_for);
        $this->assertEquals($this->content->id, (int) $created->content_id);
        $this->assertNull($created->trainingplan_id);
    }

    /**
     * 3. STORE: Assessment Content For Playlist (type=content, assasment_content_for=playlist).
     */
    public function test_store_assessment_content_for_playlist(): void
    {
        $title = 'Test Content For Playlist ' . Str::random(5);
        $response = $this->actingAs($this->admin)->post(route('assesment.store'), [
            'type'                  => 'content',
            'assasment_content_for' => 'playlist',
            'trainingplan_id'       => $this->playlist->id,
            'content_id'            => $this->content->id,
            'title'                 => $title,
            'passing_grade'         => 85,
            'total_remedials'       => 2,
            'has_access_period'     => '0',
        ]);

        $response->assertSessionHasNoErrors();
        $created = Assesment::where('title', $title)->firstOrFail();

        $this->assertSame('content', $created->type);
        $this->assertSame('playlist', $created->assasment_content_for);
        $this->assertEquals($this->playlist->id, (int) $created->trainingplan_id);
        $this->assertEquals($this->content->id, (int) $created->content_id);
    }

    /**
     * 4. STORE: Assessment Playlist (type=trainingplan, assasment_content_for=content).
     */
    public function test_store_assessment_playlist(): void
    {
        $title = 'Test Assessment Playlist ' . Str::random(5);
        $response = $this->actingAs($this->admin)->post(route('assesment.store'), [
            'type'                  => 'trainingplan',
            'assasment_content_for' => 'content',
            'trainingplan_id'       => $this->playlist->id,
            'title'                 => $title,
            'passing_grade'         => 75,
            'total_remedials'       => 1,
            'has_access_period'     => '0',
        ]);

        $response->assertSessionHasNoErrors();
        $created = Assesment::where('title', $title)->firstOrFail();

        $this->assertSame('trainingplan', $created->type);
        $this->assertSame('content', $created->assasment_content_for);
        $this->assertEquals($this->playlist->id, (int) $created->trainingplan_id);
        $this->assertNull($created->content_id);
    }

    /**
     * 5. STORE: Assessment Global (type=global, assasment_content_for=content).
     */
    public function test_store_assessment_global(): void
    {
        $title = 'Test Global Assessment ' . Str::random(5);
        $response = $this->actingAs($this->admin)->post(route('assesment.store'), [
            'type'                  => 'global',
            'assasment_content_for' => 'content',
            'title'                 => $title,
            'passing_grade'         => 70,
            'has_access_period'     => '0',
        ]);

        $response->assertSessionHasNoErrors();
        $created = Assesment::where('title', $title)->firstOrFail();

        $this->assertSame('global', $created->type);
        $this->assertSame('content', $created->assasment_content_for);
        $this->assertNull($created->trainingplan_id);
        $this->assertNull($created->content_id);
        $this->assertEquals(0, (int) $created->total_remedials);
    }

    /**
     * 6. EDIT: Existing Content For Content selects "Assessment Content For Content".
     */
    public function test_edit_view_selects_content_for_content(): void
    {
        $assessment = Assesment::create([
            'title'                 => 'Edit C-C ' . Str::random(5),
            'type'                  => 'content',
            'assasment_content_for' => 'content',
            'content_id'            => $this->content->id,
            'status'                => 'OK',
            'passing_grade'         => 75,
            'total_remedials'       => 1,
        ]);

        $response = $this->actingAs($this->admin)->get(route('assesment.edit', $assessment->slug));

        $response->assertStatus(200);
        $response->assertSee('value="content_content" selected', false);
    }

    /**
     * 7. EDIT: Existing Content For Playlist selects "Assessment Content For Playlist".
     */
    public function test_edit_view_selects_content_for_playlist(): void
    {
        $assessment = Assesment::create([
            'title'                 => 'Edit C-P ' . Str::random(5),
            'type'                  => 'content',
            'assasment_content_for' => 'playlist',
            'trainingplan_id'       => $this->playlist->id,
            'content_id'            => $this->content->id,
            'status'                => 'OK',
            'passing_grade'         => 75,
            'total_remedials'       => 1,
        ]);

        $response = $this->actingAs($this->admin)->get(route('assesment.edit', $assessment->slug));

        $response->assertStatus(200);
        $response->assertSee('value="content_playlist" selected', false);
    }

    /**
     * 8. EDIT: Existing trainingplan selects "Assessment Playlist".
     */
    public function test_edit_view_selects_trainingplan(): void
    {
        $assessment = Assesment::create([
            'title'                 => 'Edit TP ' . Str::random(5),
            'type'                  => 'trainingplan',
            'assasment_content_for' => 'content',
            'trainingplan_id'       => $this->playlist->id,
            'status'                => 'OK',
            'passing_grade'         => 75,
            'total_remedials'       => 1,
        ]);

        $response = $this->actingAs($this->admin)->get(route('assesment.edit', $assessment->slug));

        $response->assertStatus(200);
        $response->assertSee('value="trainingplan" selected', false);
    }

    /**
     * 9. EDIT: Existing global selects "Assessment Global".
     */
    public function test_edit_view_selects_global(): void
    {
        $assessment = Assesment::create([
            'title'                 => 'Edit Global ' . Str::random(5),
            'type'                  => 'global',
            'assasment_content_for' => 'content',
            'status'                => 'OK',
            'passing_grade'         => 75,
            'total_remedials'       => 0,
        ]);

        $response = $this->actingAs($this->admin)->get(route('assesment.edit', $assessment->slug));

        $response->assertStatus(200);
        $response->assertSee('value="global" selected', false);
    }

    /**
     * 10. VALIDATION: Redirect back preserves selected combined option via old('type') and old('assasment_content_for').
     */
    public function test_validation_error_preserves_selected_combined_dropdown_option(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession([
                '_old_input' => [
                    'type'                  => 'content',
                    'assasment_content_for' => 'playlist',
                ],
            ])
            ->get(route('assesment.create'));

        $response->assertStatus(200);
        $response->assertSee('value="content_playlist" selected', false);
    }

    /**
     * 11. UPDATE: Persists changes across types accurately.
     */
    public function test_update_assessment_type_and_context(): void
    {
        $assessment = Assesment::create([
            'title'                 => 'Original Title ' . Str::random(5),
            'type'                  => 'content',
            'assasment_content_for' => 'content',
            'content_id'            => $this->content->id,
            'status'                => 'OK',
            'passing_grade'         => 75,
            'total_remedials'       => 1,
        ]);

        $response = $this->actingAs($this->admin)->put(route('assesment.update', $assessment->slug), [
            'title'                 => 'Updated Title',
            'type'                  => 'content',
            'assasment_content_for' => 'playlist',
            'trainingplan_id'       => $this->playlist->id,
            'content_id'            => $this->content->id,
            'passing_grade'         => 85,
            'total_remedials'       => 2,
            'has_access_period'     => '0',
        ]);

        $response->assertSessionHasNoErrors();
        $assessment->refresh();

        $this->assertSame('content', $assessment->type);
        $this->assertSame('playlist', $assessment->assasment_content_for);
        $this->assertEquals($this->playlist->id, (int) $assessment->trainingplan_id);
    }

    /**
     * 12. VALIDATION: Assessment Playlist requires trainingplan_id.
     */
    public function test_validation_requires_trainingplan_for_assessment_playlist(): void
    {
        $response = $this->actingAs($this->admin)->post(route('assesment.store'), [
            'type'                  => 'trainingplan',
            'assasment_content_for' => 'content',
            'title'                 => 'Missing PL ' . Str::random(5),
            'passing_grade'         => 75,
            'total_remedials'       => 1,
            'has_access_period'     => '0',
        ]);

        $response->assertSessionHasErrors(['trainingplan_id']);
    }
}
