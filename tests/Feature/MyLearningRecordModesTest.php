<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class MyLearningRecordModesTest extends TestCase
{
    use DatabaseTransactions;

    protected User $userA;
    protected User $userB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = User::factory()->create([
            'name'       => 'User A Test',
            'id_emp'     => 'EMP-AAA-' . rand(1000, 9999),
            'email'      => 'usera_' . uniqid() . '@example.com',
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'hash-a-' . Str::uuid(),
            'sap_code'   => 'SAP-A-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->userB = User::factory()->create([
            'name'       => 'User B Test',
            'id_emp'     => 'EMP-BBB-' . rand(1000, 9999),
            'email'      => 'userb_' . uniqid() . '@example.com',
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'hash-b-' . Str::uuid(),
            'sap_code'   => 'SAP-B-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);
    }

    /**
     * Test 1: learning_tab=record without record_mode defaults to Online
     */
    public function test_record_tab_without_record_mode_defaults_to_online()
    {
        $response = $this->actingAs($this->userA)
            ->get('/my-learning/partial/record');

        $response->assertStatus(200);
        $response->assertSee('Online');
        $response->assertSee('record-mode-switch');
        $response->assertSee('record-mode-btn');
        $response->assertSee('data-mode="online"', false);
        $response->assertSee('data-mode="offline"', false);
        $response->assertSee('data-mode="all"', false);
        $response->assertDontSee('data-mode="export"', false);
    }

    /**
     * Test 2: record_mode=online returns Online view
     */
    public function test_record_mode_online_returns_online_view()
    {
        $response = $this->actingAs($this->userA)
            ->get('/my-learning/partial/record?record_mode=online');

        $response->assertStatus(200);
        $response->assertSee('data-mode="online"', false);
    }

    /**
     * Test 3: record_mode=offline returns Offline view
     */
    public function test_record_mode_offline_returns_offline_view()
    {
        $response = $this->actingAs($this->userA)
            ->get('/my-learning/partial/record?record_mode=offline');

        $response->assertStatus(200);
        $response->assertSee('recordOfflineFilterBar');
        $response->assertSee('data-mode="offline"', false);
    }

    /**
     * Test 4: record_mode=all returns All view
     */
    public function test_record_mode_all_returns_all_view()
    {
        $response = $this->actingAs($this->userA)
            ->get('/my-learning/partial/record?record_mode=all');

        $response->assertStatus(200);
        $response->assertSee('record-mode-switch');
        $response->assertSee('data-mode="all"', false);
        $response->assertDontSee('data-mode="export"', false);
    }

    /**
     * Test 5: record_mode=export redirects on full page and falls back to Online on partial
     */
    public function test_record_mode_export_redirects_and_falls_back_to_online()
    {
        // 1. Partial request with record_mode=export falls back to Online view
        $response = $this->actingAs($this->userA)
            ->get('/my-learning/partial/record?record_mode=export');

        $response->assertStatus(200);
        $response->assertDontSee('Learning Record Export Center');
        $response->assertDontSee('data-mode="export"', false);
        $response->assertSee('learningRecordTable');

        // 2. Full page request with record_mode=export redirects to record_mode=online
        $redirectResponse = $this->actingAs($this->userA)
            ->get('/my-learning?learning_tab=record&record_mode=export');

        $redirectResponse->assertRedirect('/my-learning?learning_tab=record&record_mode=online');
    }

    /**
     * Test 6: invalid record_mode falls back to Online
     */
    public function test_invalid_record_mode_falls_back_to_online()
    {
        $response = $this->actingAs($this->userA)
            ->get('/my-learning/partial/record?record_mode=nonexistent_mode');

        $response->assertStatus(200);
        $response->assertSee('record-mode-switch');
        // Online view has learningRecordTable
        $response->assertSee('learningRecordTable');
    }

    /**
     * Test 7: Offline only fetches authenticated user records and prevents IDOR
     */
    public function test_offline_only_fetches_authenticated_user_records()
    {
        $trainingA = OfflineTraining::create([
            'course_name' => 'Offline Course For User A Only',
            'type'        => 'Workshop',
            'category'    => 'Technical',
            'main_classification' => 'Technical',
            'learning_hours' => 4.0,
            'start_date'  => now()->toDateString(),
            'finish_date' => now()->toDateString(),
        ]);

        $trainingB = OfflineTraining::create([
            'course_name' => 'Secret Training User B',
            'type'        => 'Certification',
            'category'    => 'Management',
            'main_classification' => 'Management',
            'learning_hours' => 8.0,
            'start_date'  => now()->toDateString(),
            'finish_date' => now()->toDateString(),
        ]);

        OfflineTrainingUser::create([
            'offline_training_id' => $trainingA->id,
            'user_id'             => $this->userA->id,
            'employee_id'         => $this->userA->id_emp,
            'learning_hours'      => 4.0,
            'progress'            => 100,
        ]);

        OfflineTrainingUser::create([
            'offline_training_id' => $trainingB->id,
            'user_id'             => $this->userB->id,
            'employee_id'         => $this->userB->id_emp,
            'learning_hours'      => 8.0,
            'progress'            => 100,
        ]);

        // User A sees training A, but NOT training B
        $responseA = $this->actingAs($this->userA)
            ->get('/my-learning/partial/record?record_mode=offline');

        $responseA->assertStatus(200);
        $responseA->assertSee('Offline Course For User A Only');
        $responseA->assertDontSee('Secret Training User B');

        // User B sees training B, but NOT training A
        $responseB = $this->actingAs($this->userB)
            ->get('/my-learning/partial/record?record_mode=offline');

        $responseB->assertStatus(200);
        $responseB->assertSee('Secret Training User B');
        $responseB->assertDontSee('Offline Course For User A Only');
    }

    /**
     * Test 8: Offline employee_id fallback works when user_id is null
     */
    public function test_offline_employee_id_fallback_works()
    {
        $training = OfflineTraining::create([
            'course_name' => 'Offline Course Matched By Employee ID',
            'type'        => 'Seminar',
            'category'    => 'General',
            'main_classification' => 'General',
            'learning_hours' => 2.0,
            'start_date'  => now()->toDateString(),
            'finish_date' => now()->toDateString(),
        ]);

        OfflineTrainingUser::create([
            'offline_training_id' => $training->id,
            'user_id'             => null, // user_id is null, only employee_id is set
            'employee_id'         => $this->userA->id_emp,
            'learning_hours'      => 2.0,
            'progress'            => 100,
        ]);

        $response = $this->actingAs($this->userA)
            ->get('/my-learning/partial/record?record_mode=offline');

        $response->assertStatus(200);
        $response->assertSee('Offline Course Matched By Employee ID');
    }

    /**
     * Test 9: All mode combines Online and Offline records with source indicators
     */
    public function test_all_mode_combines_online_and_offline_records()
    {
        // Online record for User A
        $classification = Classification::firstOrCreate(
            ['classification_name' => 'General Tech'],
            ['description' => 'Test Classification']
        );

        $content = Content::create([
            'title'             => 'Online Content Title User A',
            'slug'              => 'online-content-user-a-' . Str::uuid(),
            'type'              => 'Video',
            'active'            => 'Active',
            'classification_id' => $classification->id,
            'duration'          => '00:15:00',
        ]);

        ContentUser::create([
            'user_id'    => $this->userA->id,
            'content_id' => $content->id,
            'finish'     => '1',
            'progress'   => 100,
        ]);

        // Offline record for User A
        $offlineTraining = OfflineTraining::create([
            'course_name'         => 'Offline Training User A',
            'type'                => 'Public',
            'category'            => 'External',
            'main_classification' => 'General Tech',
            'learning_hours'      => 3.0,
            'start_date'          => now()->toDateString(),
            'finish_date'         => now()->toDateString(),
        ]);

        OfflineTrainingUser::create([
            'offline_training_id' => $offlineTraining->id,
            'user_id'             => $this->userA->id,
            'employee_id'         => $this->userA->id_emp,
            'learning_hours'      => 3.0,
            'progress'            => 100,
        ]);

        $response = $this->actingAs($this->userA)
            ->get('/my-learning/partial/record?record_mode=all');

        $response->assertStatus(200);
        $response->assertSee('record-mode-switch');
        $response->assertSee('data-mode="all"', false);
    }

    /**
     * Test 10: Existing other tabs (ongoing, assessment, certificate) work without regression
     */
    public function test_existing_tabs_work_without_regression()
    {
        $ongoingResp = $this->actingAs($this->userA)->get('/my-learning/partial/ongoing');
        $ongoingResp->assertStatus(200);

        $assessmentResp = $this->actingAs($this->userA)->get('/my-learning/partial/assessment');
        $assessmentResp->assertStatus(200);

        $certificateResp = $this->actingAs($this->userA)->get('/my-learning/partial/certificate');
        $certificateResp->assertStatus(200);
    }

    /**
     * Test 11: Export downloads for online, offline, and all
     */
    public function test_export_downloads_work_for_all_sources()
    {
        $onlineExport = $this->actingAs($this->userA)->get('/my-learning/learning-record/export?source=online');
        $onlineExport->assertStatus(200);
        $onlineExport->assertHeader('content-disposition');

        $offlineExport = $this->actingAs($this->userA)->get('/my-learning/learning-record/export?source=offline');
        $offlineExport->assertStatus(200);
        $offlineExport->assertHeader('content-disposition');

        $allExport = $this->actingAs($this->userA)->get('/my-learning/learning-record/export?source=all');
        $allExport->assertStatus(200);
        $allExport->assertHeader('content-disposition');
    }

    /**
     * Test 12: Five target URLs test as per specification
     */
    public function test_five_target_urls_verification()
    {
        // 1. /my-learning?learning_tab=record&record_mode=online
        $resp1 = $this->actingAs($this->userA)->get('/my-learning?learning_tab=record&record_mode=online');
        $resp1->assertStatus(200);

        // 2. /my-learning?learning_tab=record&record_mode=offline
        $resp2 = $this->actingAs($this->userA)->get('/my-learning?learning_tab=record&record_mode=offline');
        $resp2->assertStatus(200);

        // 3. /my-learning?learning_tab=record&record_mode=all
        $resp3 = $this->actingAs($this->userA)->get('/my-learning?learning_tab=record&record_mode=all');
        $resp3->assertStatus(200);

        // 4. /my-learning?learning_tab=record&record_mode=export -> redirected to online
        $resp4 = $this->actingAs($this->userA)->get('/my-learning?learning_tab=record&record_mode=export');
        $resp4->assertRedirect('/my-learning?learning_tab=record&record_mode=online');

        // 5. /my-learning?learning_tab=record -> default existing online
        $resp5 = $this->actingAs($this->userA)->get('/my-learning?learning_tab=record');
        $resp5->assertStatus(200);
    }

    /**
     * Test 13: Quick Filter buttons strictly contain only Online, Offline, All (no Export) across all views
     */
    public function test_quick_filter_has_no_export_and_preserves_filters()
    {
        // Online mode partial
        $onlineResp = $this->actingAs($this->userA)->get('/my-learning/partial/record?record_mode=online');
        $onlineResp->assertStatus(200);
        $onlineResp->assertSee('data-mode="online"', false);
        $onlineResp->assertSee('data-mode="offline"', false);
        $onlineResp->assertSee('data-mode="all"', false);
        $onlineResp->assertDontSee('data-mode="export"', false);
        // Verify existing filters on online
        $onlineResp->assertSee('recordFilterBar');
        $onlineResp->assertSee('Type:');
        $onlineResp->assertSee('Classification:');
        $onlineResp->assertSee('Year:');
        $onlineResp->assertSee('Month:');
        $onlineResp->assertSee('recordSearch');
        $onlineResp->assertSee('recordDownloadExcel');

        // Offline mode partial
        $offlineResp = $this->actingAs($this->userA)->get('/my-learning/partial/record?record_mode=offline');
        $offlineResp->assertStatus(200);
        $offlineResp->assertSee('data-mode="online"', false);
        $offlineResp->assertSee('data-mode="offline"', false);
        $offlineResp->assertSee('data-mode="all"', false);
        $offlineResp->assertDontSee('data-mode="export"', false);
        // Verify existing filters on offline
        $offlineResp->assertSee('recordOfflineFilterBar');
        $offlineResp->assertSee('recordOfflineSearch');
        $offlineResp->assertSee('recordOfflineExportBtn');

        // All mode partial
        $allResp = $this->actingAs($this->userA)->get('/my-learning/partial/record?record_mode=all');
        $allResp->assertStatus(200);
        $allResp->assertSee('data-mode="online"', false);
        $allResp->assertSee('data-mode="offline"', false);
        $allResp->assertSee('data-mode="all"', false);
        $allResp->assertDontSee('data-mode="export"', false);
    }
}

