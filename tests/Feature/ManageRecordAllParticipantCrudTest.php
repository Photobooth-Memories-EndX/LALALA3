<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ManageRecordAllParticipantCrudTest extends TestCase
{
    protected User $admin;
    protected ?User $regularUser;

    protected array $cleanupContentUserIds = [];
    protected array $cleanupPlaylistContentUserIds = [];
    protected array $cleanupContentIds = [];
    protected array $cleanupPlaylistIds = [];
    protected array $cleanupOfflineTrainingIds = [];
    protected array $cleanupUserIds = [];

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
            $this->cleanupUserIds[] = $this->regularUser->id;
        }
    }

    protected function tearDown(): void
    {
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
        if (!empty($this->cleanupUserIds)) {
            DB::table('users')->whereIn('id', $this->cleanupUserIds)->delete();
        }

        parent::tearDown();
    }

    protected function createTestUser(string $idEmp, string $name = 'Test User'): User
    {
        $user = User::factory()->create([
            'id_user'      => (string) rand(10000, 99999),
            'id_emp'       => $idEmp,
            'id_org_unit'  => 'OU78',
            'name'         => $name,
            'team'         => 'Engineering',
            'division'     => 'Technology Div',
            'department'   => 'Technology',
            'ssohash'      => md5($idEmp),
            'sap_code'     => 'SAP' . $idEmp,
            'company_name' => 'PT Krakatau Posco',
        ]);
        $this->cleanupUserIds[] = $user->id;
        return $user;
    }

    /**
     * 1. Test Participant Datatable renders 3-dots dropdown with full CRUD for Offline rows.
     */
    public function test_participant_datatable_renders_full_crud_dropdown_for_offline_row(): void
    {
        $testEmpId = (string) rand(910000, 989999);
        $user = $this->createTestUser($testEmpId, 'Participant Offline Test');

        $training = OfflineTraining::create([
            'course_name'         => 'Test Offline Course For Participant',
            'main_classification' => 'Basic & Common Duties',
            'type'                => 'In-House',
            'category'            => 'Technical',
            'duration_seconds'    => 3600,
            'learning_hours'      => 1.0,
            'start_date'          => '2026-06-01',
            'finish_date'         => '2026-06-01',
        ]);
        $this->cleanupOfflineTrainingIds[] = $training->id;

        $otu = OfflineTrainingUser::create([
            'offline_training_id' => $training->id,
            'user_id'             => $user->id,
            'employee_id'         => $testEmpId,
            'name'                => 'Participant Offline Test',
            'learning_hours'      => 1.0,
            'progress'            => 100,
            'attendance'          => true,
        ]);

        $response = $this->actingAs($this->admin)->getJson(
            route('manage.record.all.participant.datatable', [
                'identifier'     => $testEmpId,
                'classification' => 'basic-common-duties',
            ])
        );

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);

        $row = $data[0];
        $actionHtml = $row['action'];

        // Must have 3-dots trigger button
        $this->assertStringContainsString('btn-action-trigger', $actionHtml);
        $this->assertStringContainsString('ti-dots-vertical', $actionHtml);

        // Must have View Detail, Edit Record, Delete Record
        $this->assertStringContainsString('btn-view-offline-detail', $actionHtml);
        $this->assertStringContainsString('btn-edit-offline-record', $actionHtml);
        $this->assertStringContainsString('btn-delete-offline-record', $actionHtml);

        // Edit link must have return=all_participant
        $this->assertStringContainsString('return=all_participant', $actionHtml);
        $this->assertStringContainsString('identifier=' . $testEmpId, $actionHtml);
    }

    /**
     * 2. Test Offline edit and update flow preserves return=all_participant back to participant detail.
     */
    public function test_offline_edit_and_update_flow_preserves_all_participant_return(): void
    {
        $testEmpId = (string) rand(910000, 989999);
        $user = $this->createTestUser($testEmpId, 'Participant Return Test');

        $training = OfflineTraining::create([
            'course_name'         => 'Test Offline Course Edit Return',
            'main_classification' => 'Basic & Common Duties',
            'type'                => 'In-House',
            'category'            => 'Technical',
            'duration_seconds'    => 3600,
            'learning_hours'      => 1.0,
            'start_date'          => '2026-06-01',
            'finish_date'         => '2026-06-01',
        ]);
        $this->cleanupOfflineTrainingIds[] = $training->id;

        $otu = OfflineTrainingUser::create([
            'offline_training_id' => $training->id,
            'user_id'             => $user->id,
            'employee_id'         => $testEmpId,
            'name'                => 'Participant Return Test',
            'learning_hours'      => 1.0,
            'progress'            => 100,
            'attendance'          => true,
        ]);

        // 1. Visit Edit Page with return=all_participant
        $editResponse = $this->actingAs($this->admin)->get(
            route('manage.record.offline.edit', [
                'id'             => $otu->id,
                'return'         => 'all_participant',
                'identifier'     => $testEmpId,
                'classification' => 'basic-common-duties',
            ])
        );

        $editResponse->assertStatus(200);
        $editContent = $editResponse->getContent();
        $this->assertStringContainsString('name="return_type" value="all_participant"', $editContent);
        $this->assertStringContainsString('Back to All KP OWL Participant', $editContent);

        // 2. Submit Update
        $updateResponse = $this->actingAs($this->admin)->putJson(
            route('manage.record.offline.update', $otu->id),
            [
                'course_name'           => 'Test Offline Course Edit Return Updated',
                'name'                  => 'Participant Return Test Updated',
                'employee_id'           => $testEmpId,
                'progress'              => 100,
                'learning_hours'        => 1.5,
                'attendance'            => 1,
                'return_type'           => 'all_participant',
                'return_participant_id' => $testEmpId,
                'return_classification' => 'basic-common-duties',
            ]
        );

        $updateResponse->assertStatus(200);
        $redirectUrl = $updateResponse->json('redirect_url');
        $this->assertStringContainsString('/manage/record/all/participant/' . $testEmpId, $redirectUrl);
        $this->assertStringContainsString('classification=basic-common-duties', $redirectUrl);
    }

    /**
     * 3. Test Participant Datatable renders separated actions for Online Content row.
     */
    public function test_participant_datatable_renders_separated_actions_for_online_content_row(): void
    {
        $testEmpId = (string) rand(910000, 989999);
        $user = $this->createTestUser($testEmpId, 'Participant Online Test');

        $content = Content::create([
            'title'          => 'Test Online Content For Participant',
            'slug'           => 'test-online-content-part-' . time(),
            'description'    => 'Test Content Description',
            'type'           => 'video',
            'active'         => 'Active',
            'duration'       => '00:30:00',
            'learning_hours' => 0.5,
        ]);
        $this->cleanupContentIds[] = $content->id;

        $cu = ContentUser::create([
            'user_id'              => $user->id,
            'content_id'           => $content->id,
            'progress'             => 50,
            'finish'               => '0',
            'current_time'         => 900,
            'actual_watch_seconds' => 900,
            'learning_hours'       => 0.25,
            'learning_status'      => 'in_progress',
        ]);
        $this->cleanupContentUserIds[] = $cu->id;

        $response = $this->actingAs($this->admin)->getJson(
            route('manage.record.all.participant.datatable', [
                'identifier' => $testEmpId,
                'source'     => 'online',
            ])
        );

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);

        $row = $data[0];
        $actionHtml = $row['action'];

        // 3-dots trigger
        $this->assertStringContainsString('btn-action-trigger', $actionHtml);
        $this->assertStringContainsString('ti-dots-vertical', $actionHtml);

        // Learning Record Section
        $this->assertStringContainsString('Learning Record', $actionHtml);
        $this->assertStringContainsString('btn-view-online-detail', $actionHtml);
        $this->assertStringContainsString('btn-edit-online-record', $actionHtml);
        $this->assertStringContainsString('btn-reset-online-record', $actionHtml);
        $this->assertStringContainsString('btn-delete-online-record', $actionHtml);

        // Master Content Section
        $this->assertStringContainsString('Master Content', $actionHtml);
        $this->assertStringContainsString('btn-edit-content-master', $actionHtml);
        $this->assertStringContainsString('btn-delete-content-master', $actionHtml);
    }

    /**
     * 4. Test Participant Datatable renders separated actions for Online Playlist row.
     */
    public function test_participant_datatable_renders_separated_actions_for_online_playlist_row(): void
    {
        $testEmpId = (string) rand(910000, 989999);
        $user = $this->createTestUser($testEmpId, 'Participant Playlist Test');

        $content = Content::create([
            'title'          => 'Test Video In Playlist',
            'slug'           => 'test-video-in-playlist-' . time(),
            'type'           => 'video',
            'active'         => 'Active',
            'duration'       => '00:30:00',
            'learning_hours' => 0.5,
        ]);
        $this->cleanupContentIds[] = $content->id;

        $tp = TrainingPlan::create([
            'title'       => 'Test Playlist Path Participant',
            'slug'        => 'test-playlist-path-part-' . time(),
            'description' => 'Test Playlist Path Description',
            'active'      => 'Active',
        ]);
        $this->cleanupPlaylistIds[] = $tp->id;

        $pcu = PlaylistContentUser::create([
            'user_id'              => $user->id,
            'trainingplan_id'      => $tp->id,
            'content_id'           => $content->id,
            'progress'             => 75,
            'finish'               => '0',
            'learning_hour'        => 0.75,
        ]);
        $this->cleanupPlaylistContentUserIds[] = $pcu->id;

        $response = $this->actingAs($this->admin)->getJson(
            route('manage.record.all.participant.datatable', [
                'identifier' => $testEmpId,
                'source'     => 'online',
            ])
        );

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);

        $row = $data[0];
        $actionHtml = $row['action'];

        // 3-dots trigger
        $this->assertStringContainsString('btn-action-trigger', $actionHtml);

        // Learning Record Section
        $this->assertStringContainsString('Learning Record', $actionHtml);
        $this->assertStringContainsString('btn-view-online-detail', $actionHtml);
        $this->assertStringContainsString('btn-edit-online-record', $actionHtml);
        $this->assertStringContainsString('btn-reset-online-record', $actionHtml);
        $this->assertStringContainsString('btn-delete-online-record', $actionHtml);

        // Master Playlist Section
        $this->assertStringContainsString('Master Playlist', $actionHtml);
        $this->assertStringContainsString('btn-edit-playlist-master', $actionHtml);
        $this->assertStringContainsString('btn-delete-playlist-master', $actionHtml);
    }

    /**
     * 5. Test participant KPI summary AJAX endpoint.
     */
    public function test_participant_summary_ajax_endpoint_returns_json(): void
    {
        $testEmpId = (string) rand(910000, 989999);
        $user = $this->createTestUser($testEmpId, 'Participant Summary Test');

        $response = $this->actingAs($this->admin)->getJson(
            route('manage.record.all.participant.summary', [
                'identifier'     => $testEmpId,
                'classification' => 'basic-common-duties',
            ])
        );

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'total_learning',
                'finished',
                'ongoing',
                'learning_hours',
            ],
            'total_learning',
            'finished',
            'ongoing',
            'learning_hours',
        ]);
    }

    /**
     * 6. Test participant summary endpoint requires authentication.
     */
    public function test_participant_summary_endpoint_requires_auth(): void
    {
        $response = $this->getJson(
            route('manage.record.all.participant.summary', [
                'identifier' => '102068',
            ])
        );

        $this->assertTrue(in_array($response->getStatusCode(), [302, 401, 403]));
    }
}
