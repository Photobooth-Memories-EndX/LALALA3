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

class AssessmentBulkDuplicateHttpTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $regularUser;
    protected TrainingPlan $playlist;
    protected Content $content1;
    protected Content $content2;

    protected function setUp(): void
    {
        parent::setUp();

        $classification = Classification::first() ?? Classification::create([
            'name'        => 'HTTP Test Class ' . Str::random(5),
            'description' => 'Test Desc',
        ]);

        $this->admin = User::factory()->create([
            'team'       => 'Admin Team',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'http-adm-' . Str::uuid(),
            'sap_code'   => 'SAP-H-ADM-' . rand(1000, 9999),
            'role'       => 2, // Admin role has assessment_management permission
            'active'     => 1,
        ]);

        $this->regularUser = User::factory()->create([
            'team'       => 'User Team',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'http-u-' . Str::uuid(),
            'sap_code'   => 'SAP-H-U-' . rand(1000, 9999),
            'role'       => 1, // Regular user has no manage permission
            'active'     => 1,
        ]);

        $this->playlist = TrainingPlan::create([
            'title'             => 'HTTP Test Playlist ' . Str::random(5),
            'classification_id' => $classification->id,
            'description'       => 'Desc',
        ]);

        $this->content1 = Content::create([
            'title'             => 'HTTP Content 1 ' . Str::random(5),
            'classification_id' => $classification->id,
            'type'              => 'video',
            'visibility'        => 'public',
        ]);

        $this->content2 = Content::create([
            'title'             => 'HTTP Content 2 ' . Str::random(5),
            'classification_id' => $classification->id,
            'type'              => 'video',
            'visibility'        => 'public',
        ]);
    }

    public function test_post_bulk_duplicate_requires_authentication(): void
    {
        $response = $this->postJson(route('assesment.bulk-duplicate'), [
            'target_context'  => 'playlist',
            'trainingplan_id' => $this->playlist->id,
            'source_ids'      => [1],
        ]);

        // Unauthenticated returns 401 or redirects to login
        $this->assertTrue(in_array($response->status(), [401, 302], true));
    }

    public function test_post_preview_requires_authentication(): void
    {
        $response = $this->postJson(route('assesment.bulk-duplicate.preview'), [
            'target_context'  => 'playlist',
            'trainingplan_id' => $this->playlist->id,
            'source_ids'      => [1],
        ]);

        $this->assertTrue(in_array($response->status(), [401, 302], true));
    }

    public function test_unauthorized_user_cannot_access_endpoint(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->postJson(route('assesment.bulk-duplicate'), [
                'target_context'  => 'playlist',
                'trainingplan_id' => $this->playlist->id,
                'source_ids'      => [1],
            ]);

        $response->assertStatus(403);
    }

    public function test_missing_target_context_returns_422(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('assesment.bulk-duplicate'), [
                'trainingplan_id' => $this->playlist->id,
                'source_ids'      => [1],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['target_context']);
    }

    public function test_invalid_target_context_returns_422(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('assesment.bulk-duplicate'), [
                'target_context'  => 'invalid_context',
                'trainingplan_id' => $this->playlist->id,
                'source_ids'      => [1],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['target_context']);
    }

    public function test_playlist_target_without_trainingplan_id_returns_422(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('assesment.bulk-duplicate'), [
                'target_context' => 'playlist',
                'source_ids'     => [1],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['trainingplan_id']);
    }

    public function test_invalid_trainingplan_id_returns_422(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('assesment.bulk-duplicate'), [
                'target_context'  => 'playlist',
                'trainingplan_id' => 999999999, // does not exist
                'source_ids'      => [1],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['trainingplan_id']);
    }

    public function test_empty_source_selection_and_select_all_false_returns_422(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('assesment.bulk-duplicate'), [
                'target_context'  => 'playlist',
                'trainingplan_id' => $this->playlist->id,
                'select_all'      => false,
                'source_ids'      => [],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['source_ids']);
    }

    public function test_source_ids_must_contain_valid_integers(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('assesment.bulk-duplicate'), [
                'target_context'  => 'playlist',
                'trainingplan_id' => $this->playlist->id,
                'source_ids'      => ['abc', 'def'],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['source_ids.0', 'source_ids.1']);
    }

    public function test_single_source_request_reaches_service_and_returns_success(): void
    {
        $assessment = Assesment::create([
            'title'       => 'HTTP Test Single ' . Str::random(5),
            'type'        => 'content',
            'content_id'  => $this->content1->id,
            'content_for' => 'content',
            'status'      => 'OK',
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('assesment.bulk-duplicate'), [
                'target_context'  => 'playlist',
                'trainingplan_id' => $this->playlist->id,
                'source_ids'      => [$assessment->id],
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data'    => [
                'total'   => 1,
                'success' => 1,
                'skipped' => 0,
                'failed'  => 0,
            ],
        ]);

        $responseData = $response->json('data');
        $newId = $responseData['created_items'][0]['new_assessment_id'];
        $newAssessment = Assesment::findOrFail($newId);

        $this->assertSame('content', $newAssessment->type);
        $this->assertSame('playlist', $newAssessment->content_for);
        $this->assertSame($this->playlist->id, (int) $newAssessment->trainingplan_id);
        $this->assertSame($this->content1->id, (int) $newAssessment->content_id);
    }

    public function test_multiple_source_request_reaches_service_and_returns_summary(): void
    {
        $a1 = Assesment::create([
            'title'       => 'HTTP Multi 1 ' . Str::random(5),
            'type'        => 'content',
            'content_id'  => $this->content1->id,
            'content_for' => 'content',
            'status'      => 'OK',
        ]);

        $a2 = Assesment::create([
            'title'       => 'HTTP Multi 2 ' . Str::random(5),
            'type'        => 'content',
            'content_id'  => $this->content2->id,
            'content_for' => 'content',
            'status'      => 'OK',
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('assesment.bulk-duplicate'), [
                'target_context'  => 'playlist',
                'trainingplan_id' => $this->playlist->id,
                'source_ids'      => [$a1->id, $a2->id],
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data'    => [
                'total'   => 2,
                'success' => 2,
                'skipped' => 0,
                'failed'  => 0,
            ],
        ]);
        $this->assertCount(2, $response->json('data.created_items'));
    }

    public function test_select_all_request_reaches_duplicate_all_active(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('assesment.bulk-duplicate'), [
                'target_context'  => 'playlist',
                'trainingplan_id' => $this->playlist->id,
                'select_all'      => true,
            ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertGreaterThanOrEqual(1, $response->json('data.total'));
    }

    public function test_preview_selected_returns_json(): void
    {
        $a1 = Assesment::create([
            'title'       => 'HTTP Prev 1 ' . Str::random(5),
            'type'        => 'content',
            'content_id'  => $this->content1->id,
            'content_for' => 'content',
            'status'      => 'OK',
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('assesment.bulk-duplicate.preview'), [
                'target_context'  => 'playlist',
                'trainingplan_id' => $this->playlist->id,
                'source_ids'      => [$a1->id],
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data'    => [
                'total'           => 1,
                'will_be_created' => 1,
                'already_exists'  => 0,
                'ineligible'      => 0,
            ],
        ]);
    }

    public function test_preview_select_all_returns_json(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('assesment.bulk-duplicate.preview'), [
                'target_context'  => 'playlist',
                'trainingplan_id' => $this->playlist->id,
                'select_all'      => true,
            ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertGreaterThanOrEqual(1, $response->json('data.total'));
    }

    public function test_global_source_does_not_get_duplicated(): void
    {
        $global = Assesment::create([
            'title'  => 'HTTP Global ' . Str::random(5),
            'type'   => 'global',
            'status' => 'OK',
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('assesment.bulk-duplicate'), [
                'target_context'  => 'playlist',
                'trainingplan_id' => $this->playlist->id,
                'source_ids'      => [$global->id],
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data'    => [
                'total'   => 1,
                'success' => 0,
                'skipped' => 1,
            ],
        ]);
        $this->assertSame('INVALID_TYPE', $response->json('data.skipped_items.0.reason'));
    }

    public function test_trainingplan_source_does_not_get_duplicated(): void
    {
        $tp = Assesment::create([
            'title'           => 'HTTP TP ' . Str::random(5),
            'type'            => 'trainingplan',
            'trainingplan_id' => $this->playlist->id,
            'status'          => 'OK',
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('assesment.bulk-duplicate'), [
                'target_context'  => 'content',
                'source_ids'      => [$tp->id],
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data'    => [
                'total'   => 1,
                'success' => 0,
                'skipped' => 1,
            ],
        ]);
        $this->assertSame('INVALID_TYPE', $response->json('data.skipped_items.0.reason'));
    }

    public function test_inactive_source_does_not_get_duplicated(): void
    {
        $inactive = Assesment::create([
            'title'       => 'HTTP Inactive ' . Str::random(5),
            'type'        => 'content',
            'content_id'  => $this->content1->id,
            'content_for' => 'content',
            'status'      => 'NOT',
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('assesment.bulk-duplicate'), [
                'target_context'  => 'playlist',
                'trainingplan_id' => $this->playlist->id,
                'source_ids'      => [$inactive->id],
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data'    => [
                'total'   => 1,
                'success' => 0,
                'skipped' => 1,
            ],
        ]);
        $this->assertSame('INACTIVE', $response->json('data.skipped_items.0.reason'));
    }

    public function test_playlist_existing_assessment_returns_skipped_already_exists(): void
    {
        // Pre-create an active assessment in playlist
        Assesment::create([
            'title'           => 'Existing In Playlist ' . Str::random(5),
            'type'            => 'content',
            'content_id'      => $this->content1->id,
            'content_for'     => 'playlist',
            'trainingplan_id' => $this->playlist->id,
            'status'          => 'OK',
        ]);

        $source = Assesment::create([
            'title'       => 'Source Assessment ' . Str::random(5),
            'type'        => 'content',
            'content_id'  => $this->content1->id,
            'content_for' => 'content',
            'status'      => 'OK',
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('assesment.bulk-duplicate'), [
                'target_context'  => 'playlist',
                'trainingplan_id' => $this->playlist->id,
                'source_ids'      => [$source->id],
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data'    => [
                'total'   => 1,
                'success' => 0,
                'skipped' => 1,
            ],
        ]);
        $this->assertSame('ALREADY_EXISTS', $response->json('data.skipped_items.0.reason'));
    }

    public function test_validation_errors_do_not_create_assessment_records(): void
    {
        $countBefore = Assesment::count();

        $response = $this->actingAs($this->admin)
            ->postJson(route('assesment.bulk-duplicate'), [
                'target_context'  => 'playlist',
                // missing trainingplan_id
                'source_ids'      => [1],
            ]);

        $response->assertStatus(422);
        $this->assertSame($countBefore, Assesment::count());
    }

    public function test_assessment_index_renders_bulk_duplicate_button_and_modal(): void
    {
        $response = $this->actingAs($this->admin)->get(route('assesment.index'));

        $response->assertStatus(200);
        $response->assertSee('Bulk Duplicate', false);
        $response->assertSee('Edit Score', false);
        $response->assertSee('checkAllAssessments', false);
        $response->assertSee('modalBulkDuplicateAssessment', false);
        $response->assertSee('Bulk Duplicate Assessments', false);
        $response->assertSee('Selection Mode', false);
        $response->assertSee('Direct Content', false);
        $response->assertSee('Playlist Content', false);
    }

    public function test_assessment_index_renders_checkbox_eligible_for_active_content_and_disabled_for_others(): void
    {
        $activeContent = Assesment::create([
            'title'       => 'Active Content ' . Str::random(5),
            'type'        => 'content',
            'content_id'  => $this->content1->id,
            'content_for' => 'content',
            'status'      => 'OK',
        ]);

        $inactiveContent = Assesment::create([
            'title'       => 'Inactive Content ' . Str::random(5),
            'type'        => 'content',
            'content_id'  => $this->content1->id,
            'content_for' => 'content',
            'status'      => 'NOT',
        ]);

        $global = Assesment::create([
            'title'  => 'Global Assesment ' . Str::random(5),
            'type'   => 'global',
            'status' => 'OK',
        ]);

        $response = $this->actingAs($this->admin)->get(route('assesment.index'));

        $response->assertStatus(200);
        $response->assertSee('assessment-select-checkbox', false);
        $response->assertSee('assessment-select-checkbox-disabled', false);
    }
}
