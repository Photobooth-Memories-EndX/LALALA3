<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Content;
use App\Models\RoleAccessPermission;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlaylistDetailTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected User $unauthorizedUser;
    protected Classification $classification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->classification = Classification::firstOrCreate(
            ['slug' => 'test-classification-pl-detail'],
            ['classification_name' => 'Testing Classification PL Detail']
        );

        $this->adminUser = User::factory()->create([
            'role'       => User::ROLE_SUPER_ADMIN,
            'team'       => 'Quality Assurance',
            'department' => 'Technology',
            'division'   => 'Engineering',
            'ssohash'    => 'test-admin-detail-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-ADMIN-DETAIL',
        ]);

        $this->unauthorizedUser = User::factory()->create([
            'role'       => User::ROLE_USER,
            'team'       => 'Operations',
            'department' => 'Manufacturing',
            'division'   => 'Production',
            'ssohash'    => 'test-user-detail-noperm-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-USER-DETAIL-NOPERM',
        ]);

        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_SUPER_ADMIN, 'module_key' => 'playlist_learning_path'],
            ['can_access' => true]
        );

        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_USER, 'module_key' => 'playlist_learning_path'],
            ['can_access' => false]
        );
    }

    /**
     * Test 1 — Authorized user can retrieve playlist details successfully.
     */
    public function test_authorized_user_can_retrieve_playlist_details(): void
    {
        $plan = TrainingPlan::create([
            'title'                  => 'Detail Test Playlist',
            'active'                 => 'Active',
            'learning_hours'         => 1.5000,
            'total_duration_seconds' => 5400,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('manage.playlist.details', $plan->id));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'title',
                'slug',
                'status',
                'active',
                'has_deadline',
                'deadline',
                'deadline_formatted',
                'is_expired',
                'from_date',
                'to_date',
                'url_link',
                'created_at',
                'created_at_formatted',
                'updated_at',
                'thumbnail_url',
                'total_duration',
                'total_duration_seconds',
                'learning_hours',
                'learning_hours_value',
                'materials_count',
                'materials',
                'edit_url',
                'order_url',
            ],
        ]);
        $this->assertTrue($response->json('success'));
        $this->assertSame($plan->id, $response->json('data.id'));
        $this->assertSame('Detail Test Playlist', $response->json('data.title'));
    }

    /**
     * Test 2 — Unauthenticated user is rejected.
     */
    public function test_unauthenticated_user_is_rejected(): void
    {
        $plan = TrainingPlan::create([
            'title'  => 'Unauth Check Playlist',
            'active' => 'Active',
        ]);

        $response = $this->getJson(route('manage.playlist.details', $plan->id));
        $response->assertStatus(401);
    }

    /**
     * Test 3 — Unauthorized user lacking permission is rejected (403 or 302 redirect).
     */
    public function test_unauthorized_user_is_rejected(): void
    {
        $plan = TrainingPlan::create([
            'title'  => 'Permission Check Playlist',
            'active' => 'Active',
        ]);

        $response = $this->actingAs($this->unauthorizedUser)
            ->getJson(route('manage.playlist.details', $plan->id));

        $this->assertTrue(in_array($response->status(), [302, 403], true));
    }

    /**
     * Test 4 — Non-existent playlist returns 404 with error message.
     */
    public function test_nonexistent_playlist_returns_404(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('manage.playlist.details', 99999999));

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'Playlist not found.',
        ]);
    }

    /**
     * Test 5 — Playlist detail can also be retrieved by slug.
     */
    public function test_playlist_detail_can_be_retrieved_by_slug(): void
    {
        $plan = TrainingPlan::create([
            'title'  => 'Playlist By Slug Unique',
            'active' => 'Active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('manage.playlist.details', $plan->slug));

        $response->assertStatus(200);
        $this->assertSame($plan->id, $response->json('data.id'));
        $this->assertSame($plan->slug, $response->json('data.slug'));
    }

    /**
     * Test 6 — Correct status and title are returned.
     */
    public function test_correct_status_and_title_are_returned(): void
    {
        $plan = TrainingPlan::create([
            'title'  => 'Specific Title Test',
            'active' => 'Inactive',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('manage.playlist.details', $plan->id));

        $response->assertStatus(200);
        $this->assertSame('Specific Title Test', $response->json('data.title'));
        $this->assertSame('Inactive', $response->json('data.status'));
    }

    /**
     * Test 7 — Deadline formatting when has_deadline is true.
     */
    public function test_deadline_formatting_when_has_deadline_is_true(): void
    {
        $plan = TrainingPlan::create([
            'title'        => 'Playlist With Deadline',
            'active'       => 'Active',
            'has_deadline' => true,
            'from_date'    => '2026-01-10',
            'to_date'      => '2026-03-25',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('manage.playlist.details', $plan->id));

        $response->assertStatus(200);
        $this->assertTrue($response->json('data.has_deadline'));
        $this->assertSame('10/01/2026 – 25/03/2026', $response->json('data.deadline'));
        $this->assertSame('10/01/2026', $response->json('data.from_date'));
        $this->assertSame('25/03/2026', $response->json('data.to_date'));
    }

    /**
     * Test 8 — Deadline formatting when has_deadline is false.
     */
    public function test_deadline_formatting_when_has_deadline_is_false(): void
    {
        $plan = TrainingPlan::create([
            'title'        => 'Playlist No Deadline',
            'active'       => 'Active',
            'has_deadline' => false,
            'from_date'    => null,
            'to_date'      => null,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('manage.playlist.details', $plan->id));

        $response->assertStatus(200);
        $this->assertFalse($response->json('data.has_deadline'));
        $this->assertSame('No Deadline', $response->json('data.deadline'));
        $this->assertFalse($response->json('data.is_expired'));
    }

    /**
     * Test 9 — Expired deadline is detected accurately.
     */
    public function test_expired_deadline_is_detected(): void
    {
        $plan = TrainingPlan::create([
            'title'        => 'Expired Playlist',
            'active'       => 'Active',
            'has_deadline' => true,
            'from_date'    => '2020-01-01',
            'to_date'      => '2020-06-01',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('manage.playlist.details', $plan->id));

        $response->assertStatus(200);
        $this->assertTrue($response->json('data.is_expired'));
    }

    /**
     * Test 10 — Total duration and learning hours formatting.
     */
    public function test_total_duration_and_learning_hours_formatting(): void
    {
        $plan = TrainingPlan::create([
            'title'                  => 'Duration Formatting Playlist',
            'active'                 => 'Active',
            'learning_hours'         => 2.7500,
            'total_duration_seconds' => 9900, // 2h 45m 00s = 02:45:00
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('manage.playlist.details', $plan->id));

        $response->assertStatus(200);
        $this->assertSame('02:45:00', $response->json('data.total_duration'));
        $this->assertSame(9900, $response->json('data.total_duration_seconds'));
        $this->assertSame('2.7500 h', $response->json('data.learning_hours'));
        $this->assertSame(2.75, $response->json('data.learning_hours_value'));
    }

    /**
     * Test 11 — Materials are returned in correct syllabus order (content_order).
     */
    public function test_materials_are_returned_in_correct_order(): void
    {
        $c1 = Content::create(['title' => 'First Material', 'duration' => '00:10:00', 'active' => 'Active', 'classification_id' => $this->classification->id]);
        $c2 = Content::create(['title' => 'Second Material', 'duration' => '00:20:00', 'active' => 'Active', 'classification_id' => $this->classification->id]);
        $c3 = Content::create(['title' => 'Third Material', 'duration' => '00:30:00', 'active' => 'Active', 'classification_id' => $this->classification->id]);

        $plan = TrainingPlan::create([
            'title'  => 'Ordered Materials Playlist',
            'active' => 'Active',
        ]);

        // Insert in reverse order to ensure DB ordering respects content_order
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c3->id, 'content_order' => 3]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 1]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 2]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('manage.playlist.details', $plan->id));

        $response->assertStatus(200);
        $materials = $response->json('data.materials');
        $this->assertCount(3, $materials);
        $this->assertSame('First Material', $materials[0]['title']);
        $this->assertSame(1, $materials[0]['order']);
        $this->assertSame('Second Material', $materials[1]['title']);
        $this->assertSame(2, $materials[1]['order']);
        $this->assertSame('Third Material', $materials[2]['title']);
        $this->assertSame(3, $materials[2]['order']);
        $this->assertSame(3, $response->json('data.materials_count'));
    }

    /**
     * Test 12 — Material fields and type label are accurately mapped.
     */
    public function test_material_fields_and_type_label_are_accurately_mapped(): void
    {
        $content = Content::create([
            'title'             => 'Video Tutorial Material',
            'type'              => 'video',
            'duration'          => '00:15:30',
            'learning_hours'    => 0.2583,
            'active'            => 'Active',
            'classification_id' => $this->classification->id,
        ]);

        $plan = TrainingPlan::create([
            'title'  => 'Single Material Playlist',
            'active' => 'Active',
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => $plan->id,
            'content_id'      => $content->id,
            'content_order'   => 1,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('manage.playlist.details', $plan->id));

        $response->assertStatus(200);
        $materials = $response->json('data.materials');
        $this->assertCount(1, $materials);

        $mat = $materials[0];
        $this->assertSame($content->id, $mat['id']);
        $this->assertSame('Video Tutorial Material', $mat['title']);
        $this->assertSame('video', $mat['type']);
        $this->assertNotEmpty($mat['type_label']);
        $this->assertSame('00:15:30', $mat['duration']);
        $this->assertSame('0.2583 h', $mat['learning_hours']);
        $this->assertSame('Active', $mat['active']);
    }

    /**
     * Test 13 — Empty playlist returns empty materials list without errors.
     */
    public function test_empty_playlist_returns_empty_materials_list(): void
    {
        $emptyPlan = TrainingPlan::create([
            'title'                  => 'Empty Playlist Test',
            'active'                 => 'Active',
            'learning_hours'         => 0.0,
            'total_duration_seconds' => 0,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('manage.playlist.details', $emptyPlan->id));

        $response->assertStatus(200);
        $this->assertSame(0, $response->json('data.materials_count'));
        $this->assertEmpty($response->json('data.materials'));
        $this->assertSame('00:00:00', $response->json('data.total_duration'));
        $this->assertSame('0.0000 h', $response->json('data.learning_hours'));
    }

    /**
     * Test 14 — Soft-deleted content is excluded from materials list.
     */
    public function test_soft_deleted_content_is_excluded_from_materials(): void
    {
        $activeContent = Content::create([
            'title'             => 'Active Material',
            'duration'          => '00:10:00',
            'active'            => 'Active',
            'classification_id' => $this->classification->id,
        ]);

        $deletedContent = Content::create([
            'title'             => 'Deleted Material',
            'duration'          => '00:20:00',
            'active'            => 'Active',
            'classification_id' => $this->classification->id,
        ]);

        $plan = TrainingPlan::create([
            'title'  => 'Soft Delete Test Playlist',
            'active' => 'Active',
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $activeContent->id, 'content_order' => 1]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $deletedContent->id, 'content_order' => 2]);

        // Soft delete the second content
        $deletedContent->delete();

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('manage.playlist.details', $plan->id));

        $response->assertStatus(200);
        $materials = $response->json('data.materials');
        $this->assertCount(1, $materials);
        $this->assertSame('Active Material', $materials[0]['title']);
    }

    /**
     * Test 15 — Endpoint performs ZERO database mutations (read-only verification).
     */
    public function test_endpoint_performs_zero_database_mutations(): void
    {
        $content = Content::create([
            'title'             => 'Mutation Guard Content',
            'duration'          => '00:25:00',
            'active'            => 'Active',
            'classification_id' => $this->classification->id,
        ]);

        $plan = TrainingPlan::create([
            'title'                  => 'Mutation Guard Playlist',
            'active'                 => 'Active',
            'learning_hours'         => 0.4167,
            'total_duration_seconds' => 1500,
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => $plan->id,
            'content_id'      => $content->id,
            'content_order'   => 1,
        ]);

        $originalPlan = $plan->fresh()->toArray();
        $originalDetailCount = TrainingPlanDetail::count();

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('manage.playlist.details', $plan->id));

        $response->assertStatus(200);

        // Verify trainingplan record is identical
        $freshPlan = $plan->fresh()->toArray();
        $this->assertEquals($originalPlan, $freshPlan);

        // Verify no new rows created or deleted
        $this->assertSame($originalDetailCount, TrainingPlanDetail::count());
    }

    /**
     * Test 16 — Endpoint does NOT mutate learning hours or total duration seconds.
     */
    public function test_endpoint_does_not_mutate_learning_hours_or_total_duration(): void
    {
        $plan = TrainingPlan::create([
            'title'                  => 'Isolation Protection Playlist',
            'active'                 => 'Active',
            'learning_hours'         => 3.1415,
            'total_duration_seconds' => 11309,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('manage.playlist.details', $plan->id));

        $response->assertStatus(200);

        $plan->refresh();
        $this->assertEquals(3.1415, $plan->learning_hours);
        $this->assertSame(11309, $plan->total_duration_seconds);
    }

    /**
     * Test 17 — No N+1 queries occur when loading playlist with multiple materials.
     */
    public function test_query_count_is_optimized_and_prevents_n_plus_one(): void
    {
        $plan = TrainingPlan::create([
            'title'  => 'N+1 Query Test Playlist',
            'active' => 'Active',
        ]);

        for ($i = 1; $i <= 5; $i++) {
            $c = Content::create([
                'title'             => "Content Batch {$i}",
                'duration'          => '00:10:00',
                'active'            => 'Active',
                'classification_id' => $this->classification->id,
            ]);
            TrainingPlanDetail::create([
                'trainingplan_id' => $plan->id,
                'content_id'      => $c->id,
                'content_order'   => $i,
            ]);
        }

        DB::enableQueryLog();
        DB::flushQueryLog();

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('manage.playlist.details', $plan->id));

        $response->assertStatus(200);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // The endpoint should eager-load trainingplandetail and content in fixed queries (at most 5 queries: auth/perm check, trainingplan, trainingplandetail, content)
        $this->assertLessThanOrEqual(5, count($queries), 'Potential N+1 detected: too many database queries executed.');
    }

    /**
     * Test 18 — Metadata fields URL link, created_at, and updated_at are returned accurately.
     */
    public function test_metadata_fields_url_and_timestamps_are_returned_accurately(): void
    {
        $plan = TrainingPlan::create([
            'title'    => 'Metadata Verification Playlist',
            'active'   => 'Active',
            'url_link' => 'https://example.com/playlist/test',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('manage.playlist.details', $plan->id));

        $response->assertStatus(200);
        $this->assertSame('https://example.com/playlist/test', $response->json('data.url_link'));
        $this->assertNotNull($response->json('data.created_at'));
        $this->assertNotNull($response->json('data.created_at_formatted'));
        $this->assertSame('Active', $response->json('data.active'));
    }

    /**
     * Test 19 — Playlist index view contains Total Duration & Learning Hours columns, and does NOT contain Slug column.
     */
    public function test_playlist_index_view_columns_refactor(): void
    {
        $plan = TrainingPlan::create([
            'title'                  => 'Unique Playlist For Table Test',
            'active'                 => 'Active',
            'learning_hours'         => 3.5000,
            'total_duration_seconds' => 12600, // 03:30:00
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.index'));

        $response->assertStatus(200);
        $html = $response->getContent();

        // 1. Slug header must NOT exist in the table
        $this->assertStringNotContainsString('<th>Slug</th>', $html);

        // 2. Total Duration & Learning Hours headers must exist
        $this->assertStringContainsString('Total Duration', $html);
        $this->assertStringContainsString('Learning Hours', $html);

        // 3. Compact Title hover tooltip title attribute exists
        $this->assertStringContainsString('title="Unique Playlist For Table Test"', $html);

        // 4. Formatted duration and learning hours appear
        $this->assertStringContainsString('03:30:00', $html);
        $this->assertStringContainsString('3.5000 h', $html);

        // 5. 4 Compact action buttons are present
        $this->assertStringContainsString('btn-view-playlist-detail', $html);
        $this->assertStringContainsString('ti ti-eye', $html);
        $this->assertStringContainsString('ti ti-edit', $html);
        $this->assertStringContainsString('ti ti-arrows-sort', $html);
        $this->assertStringContainsString('ti ti-trash', $html);
    }
}
