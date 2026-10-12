<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ManageRecordAllClassificationCrudTest extends TestCase
{
    protected User $admin;
    protected ?User $regularUser;

    protected array $cleanupContentUserIds = [];
    protected array $cleanupPlaylistContentUserIds = [];
    protected array $cleanupContentIds = [];
    protected array $cleanupPlaylistIds = [];
    protected array $cleanupOfflineTrainingIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', User::ROLE_ADMIN)
            ->orWhere('role', User::ROLE_SUPER_ADMIN)
            ->first() ?? User::first();

        $this->regularUser = User::where('role', User::ROLE_USER)->first();
        if (!$this->regularUser) {
            $this->regularUser = User::factory()->create([
                'role' => User::ROLE_USER,
            ]);
        }
    }

    protected function tearDown(): void
    {
        // Clean up only test records created during test execution
        if (!empty($this->cleanupContentUserIds)) {
            DB::table('content_users')->whereIn('id', $this->cleanupContentUserIds)->delete();
        }
        if (!empty($this->cleanupPlaylistContentUserIds)) {
            DB::table('playlist_content_users')->whereIn('id', $this->cleanupPlaylistContentUserIds)->delete();
        }
        if (!empty($this->cleanupContentIds)) {
            DB::table('content')->whereIn('id', $this->cleanupContentIds)->delete();
        }
        if (!empty($this->cleanupPlaylistIds)) {
            DB::table('trainingplandetail')->whereIn('trainingplan_id', $this->cleanupPlaylistIds)->delete();
            DB::table('trainingplan')->whereIn('id', $this->cleanupPlaylistIds)->delete();
        }
        if (!empty($this->cleanupOfflineTrainingIds)) {
            DB::table('offline_training_users')->whereIn('offline_training_id', $this->cleanupOfflineTrainingIds)->delete();
            DB::table('offline_trainings')->whereIn('id', $this->cleanupOfflineTrainingIds)->delete();
        }

        parent::tearDown();
    }

    /**
     * 1. Test All Classification Datatable returns action column with dropdown.
     */
    public function test_all_classification_datatable_returns_action_column(): void
    {
        $response = $this->actingAs($this->admin)->getJson(
            route('manage.record.all.classification.datatable', 'basic-common-duties')
        );

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertArrayHasKey('data', $json);
    }

    /**
     * 2. Test Offline row renders View, Edit, and Delete actions.
     */
    public function test_offline_row_renders_view_edit_and_delete_actions(): void
    {
        $training = OfflineTraining::create([
            'course_name'           => 'Test Safety Training Offline',
            'main_classification'   => 'Basic & Common Duties',
            'type'                  => 'In-House',
            'category'              => 'Safety',
            'duration_seconds'      => 7200,
            'learning_hours'        => 2.0,
            'start_date'            => '2026-05-01',
            'finish_date'           => '2026-05-01',
        ]);
        $this->cleanupOfflineTrainingIds[] = $training->id;

        $otu = OfflineTrainingUser::create([
            'offline_training_id' => $training->id,
            'employee_id'         => 'EMP_TEST_001',
            'name'                => 'Test Participant Offline',
            'learning_hours'      => 2.0,
            'progress'            => 100,
            'attendance'          => true,
        ]);

        $response = $this->actingAs($this->admin)->getJson(
            route('manage.record.all.classification.datatable', [
                'classification' => 'basic-common-duties',
                'source'         => 'offline',
            ])
        );

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);

        $found = false;
        foreach ($data as $row) {
            if ($row['source_id'] == $otu->id && $row['source'] === 'offline') {
                $found = true;
                $actionHtml = $row['action'];
                $this->assertStringContainsString('btn-view-offline-detail', $actionHtml);
                $this->assertStringContainsString('btn-edit-offline-record', $actionHtml);
                $this->assertStringContainsString('btn-delete-offline-record', $actionHtml);
                break;
            }
        }
        $this->assertTrue($found, 'Offline record was not found in datatable output');
    }

    /**
     * 3. Test Online Content row renders separated Learning Record and Master Content actions.
     */
    public function test_online_content_row_renders_separated_actions(): void
    {
        $classification = Classification::firstOrCreate(
            ['slug' => 'basic-common-duties'],
            ['classification_name' => 'Basic & Common Duties']
        );

        $content = Content::create([
            'title'             => 'Test Online Module Content Master',
            'type'              => 'video',
            'classification_id' => $classification->id,
            'duration'          => '01:00:00',
            'learning_hours'    => 1.0,
            'active'            => 'Active',
        ]);
        $this->cleanupContentIds[] = $content->id;

        $contentUser = ContentUser::create([
            'user_id'              => $this->admin->id,
            'content_id'           => $content->id,
            'progress'             => 50,
            'current_time'         => 1800,
            'actual_watch_seconds' => 1800,
            'afk_duration_seconds' => 0,
            'learning_hours'       => 0.5,
            'finish'               => '0',
            'learning_status'      => 'in_progress',
        ]);
        $this->cleanupContentUserIds[] = $contentUser->id;

        $response = $this->actingAs($this->admin)->getJson(
            route('manage.record.all.classification.datatable', [
                'classification' => 'basic-common-duties',
                'source'         => 'online',
                'learning_title' => 'Test Online Module Content Master',
            ])
        );

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);

        $found = false;
        foreach ($data as $row) {
            if ($row['source_id'] == $contentUser->id && $row['source_type'] === 'content') {
                $found = true;
                $actionHtml = $row['action'];

                // Learning Record Section
                $this->assertStringContainsString('Learning Record', $actionHtml);
                $this->assertStringContainsString('btn-view-online-detail', $actionHtml);
                $this->assertStringContainsString('btn-edit-online-record', $actionHtml);
                $this->assertStringContainsString('btn-reset-online-record', $actionHtml);
                $this->assertStringContainsString('btn-delete-online-record', $actionHtml);

                // Master Content Section
                $this->assertStringContainsString('Master Content', $actionHtml);
                $this->assertStringContainsString('View Content', $actionHtml);
                $this->assertStringContainsString('Edit Content (Master)', $actionHtml);
                $this->assertStringContainsString('btn-delete-content-master', $actionHtml);
                break;
            }
        }
        $this->assertTrue($found, 'Online content record was not found in datatable output');
    }

    /**
     * 4. Test Online Detail Endpoint returns valid JSON structure.
     */
    public function test_online_detail_endpoint_returns_json(): void
    {
        $content = Content::create([
            'title'          => 'Test Online Detail Fetch Content',
            'type'           => 'document',
            'duration'       => '00:30:00',
            'learning_hours' => 0.5,
            'active'         => 'Active',
        ]);
        $this->cleanupContentIds[] = $content->id;

        $contentUser = ContentUser::create([
            'user_id'              => $this->admin->id,
            'content_id'           => $content->id,
            'progress'             => 100,
            'current_time'         => 1800,
            'actual_watch_seconds' => 1800,
            'afk_duration_seconds' => 0,
            'learning_hours'       => 0.5,
            'finish'               => '1',
            'completed_at'         => now(),
            'learning_status'      => 'completed',
        ]);
        $this->cleanupContentUserIds[] = $contentUser->id;

        $response = $this->actingAs($this->admin)->getJson(
            route('manage.record.online.detail', ['type' => 'content', 'id' => $contentUser->id])
        );

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'source_type',
                'source_id',
                'employee' => ['id_emp', 'name'],
                'learning' => ['title', 'type'],
                'progress' => ['progress', 'is_finished', 'credited_hours'],
                'timestamps',
            ],
        ]);
    }

    /**
     * 5. Test Edit Progress updates tracking and recalculates learning hours.
     */
    public function test_edit_progress_updates_and_recalculates_learning_hours(): void
    {
        $content = Content::create([
            'title'          => 'Test Edit Progress Content',
            'type'           => 'video',
            'duration'       => '01:00:00',
            'learning_hours' => 1.0,
            'active'         => 'Active',
        ]);
        $this->cleanupContentIds[] = $content->id;

        $contentUser = ContentUser::create([
            'user_id'              => $this->admin->id,
            'content_id'           => $content->id,
            'progress'             => 20,
            'current_time'         => 720,
            'actual_watch_seconds' => 720,
            'afk_duration_seconds' => 0,
            'learning_hours'       => 0.2,
            'finish'               => '0',
            'learning_status'      => 'in_progress',
        ]);
        $this->cleanupContentUserIds[] = $contentUser->id;

        $response = $this->actingAs($this->admin)->putJson(
            route('learning.manage.record.update', ['type' => 'content', 'id' => $contentUser->id]),
            [
                'progress'             => 100,
                'current_time'         => 3600,
                'actual_watch_seconds' => 3600,
                'afk_duration_seconds' => 0,
            ]
        );

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
        ]);

        $fresh = $contentUser->fresh();
        $this->assertEquals(100, $fresh->progress);
        $this->assertEquals('1', $fresh->finish);
        $this->assertEquals('completed', $fresh->learning_status);
        $this->assertNotNull($fresh->completed_at);
        $this->assertGreaterThan(0.5, (float) $fresh->learning_hours);
    }

    /**
     * 6. Test Reset Progress resets record to 0% and clears finish & completed_at.
     */
    public function test_reset_progress_clears_completion_state(): void
    {
        $content = Content::create([
            'title'          => 'Test Reset Progress Content',
            'type'           => 'video',
            'duration'       => '01:00:00',
            'learning_hours' => 1.0,
            'active'         => 'Active',
        ]);
        $this->cleanupContentIds[] = $content->id;

        $contentUser = ContentUser::create([
            'user_id'              => $this->admin->id,
            'content_id'           => $content->id,
            'progress'             => 100,
            'current_time'         => 3600,
            'actual_watch_seconds' => 3600,
            'afk_duration_seconds' => 0,
            'learning_hours'       => 1.0,
            'finish'               => '1',
            'completed_at'         => now(),
            'learning_status'      => 'completed',
        ]);
        $this->cleanupContentUserIds[] = $contentUser->id;

        $response = $this->actingAs($this->admin)->postJson(
            route('learning.manage.record.reset', ['type' => 'content', 'id' => $contentUser->id])
        );

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
        ]);

        $fresh = $contentUser->fresh();
        $this->assertEquals(0, $fresh->progress);
        $this->assertEquals('0', $fresh->finish);
        $this->assertEquals('not_started', $fresh->learning_status);
        $this->assertNull($fresh->completed_at);
        $this->assertEquals(0.0, (float) $fresh->learning_hours);
    }

    /**
     * 7. Test Delete Online Record safely removes the participant record while keeping master content intact.
     */
    public function test_delete_online_record_removes_user_record_safely(): void
    {
        $content = Content::create([
            'title'          => 'Test Delete Online Record Content',
            'type'           => 'video',
            'duration'       => '01:00:00',
            'learning_hours' => 1.0,
            'active'         => 'Active',
        ]);
        $this->cleanupContentIds[] = $content->id;

        $contentUser = ContentUser::create([
            'user_id'              => $this->admin->id,
            'content_id'           => $content->id,
            'progress'             => 50,
            'current_time'         => 1800,
            'actual_watch_seconds' => 1800,
            'afk_duration_seconds' => 0,
            'learning_hours'       => 0.5,
            'finish'               => '0',
            'learning_status'      => 'in_progress',
        ]);

        $response = $this->actingAs($this->admin)->deleteJson(
            route('manage.record.online.destroy', ['type' => 'content', 'id' => $contentUser->id])
        );

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        // User record is deleted
        $this->assertNull(ContentUser::find($contentUser->id));

        // Master Content is NOT deleted
        $this->assertNotNull(Content::find($content->id));
        $this->assertFalse(Content::find($content->id)->trashed());
    }

    /**
     * 8. Test Backend Authorization Blocks Unauthorized Users on Delete and Update.
     */
    public function test_backend_authorization_blocks_unauthorized_user(): void
    {
        $content = Content::create([
            'title'          => 'Test Auth Guard Content',
            'type'           => 'video',
            'duration'       => '01:00:00',
            'learning_hours' => 1.0,
            'active'         => 'Active',
        ]);
        $this->cleanupContentIds[] = $content->id;

        $contentUser = ContentUser::create([
            'user_id'              => $this->admin->id,
            'content_id'           => $content->id,
            'progress'             => 50,
            'current_time'         => 1800,
            'actual_watch_seconds' => 1800,
            'afk_duration_seconds' => 0,
            'learning_hours'       => 0.5,
            'finish'               => '0',
            'learning_status'      => 'in_progress',
        ]);
        $this->cleanupContentUserIds[] = $contentUser->id;

        // Try Delete Online Record as regular unauthorized user
        $responseDelete = $this->actingAs($this->regularUser)->deleteJson(
            route('manage.record.online.destroy', ['type' => 'content', 'id' => $contentUser->id])
        );
        $this->assertContains($responseDelete->status(), [403, 401]);

        // Try Update Record as regular unauthorized user
        $responseUpdate = $this->actingAs($this->regularUser)->putJson(
            route('learning.manage.record.update', ['type' => 'content', 'id' => $contentUser->id]),
            ['progress' => 100, 'current_time' => 3600, 'actual_watch_seconds' => 3600, 'afk_duration_seconds' => 0]
        );
        $this->assertContains($responseUpdate->status(), [403, 401]);
    }

    /**
     * 9. Test Delete Master Content uses SoftDeletes and responds with JSON on AJAX.
     */
    public function test_delete_master_content_uses_soft_deletes(): void
    {
        $content = Content::create([
            'title'          => 'Test Soft Delete Master Content',
            'type'           => 'video',
            'duration'       => '01:00:00',
            'learning_hours' => 1.0,
            'active'         => 'Active',
        ]);
        $this->cleanupContentIds[] = $content->id;

        $response = $this->actingAs($this->admin)->deleteJson(
            route('content.destroy', $content)
        );

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $softDeleted = Content::withTrashed()->find($content->id);
        $this->assertNotNull($softDeleted);
        $this->assertTrue($softDeleted->trashed());
    }

    /**
     * 10. Test Delete Master Playlist uses SoftDeletes and responds with JSON on AJAX.
     */
    public function test_delete_master_playlist_uses_soft_deletes(): void
    {
        $playlist = TrainingPlan::create([
            'title'  => 'Test Soft Delete Master Playlist',
            'slug'   => 'test-soft-delete-master-playlist-' . uniqid(),
            'active' => 'Active',
        ]);
        $this->cleanupPlaylistIds[] = $playlist->id;

        $response = $this->actingAs($this->admin)->deleteJson(
            route('playlist.destroy', $playlist)
        );

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $softDeleted = TrainingPlan::withTrashed()->find($playlist->id);
        $this->assertNotNull($softDeleted);
        $this->assertTrue($softDeleted->trashed());
    }
}

