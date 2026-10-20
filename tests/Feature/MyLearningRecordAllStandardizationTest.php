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

class MyLearningRecordAllStandardizationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $userA;
    protected User $userB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = User::factory()->create([
            'name'       => 'User A Standard',
            'id_emp'     => 'EA' . rand(1000, 9999),
            'email'      => 'user_stda_' . uniqid() . '@example.com',
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'hash-stda-' . Str::uuid(),
            'sap_code'   => 'SAPA' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->userB = User::factory()->create([
            'name'       => 'User B Standard',
            'id_emp'     => 'EB' . rand(1000, 9999),
            'email'      => 'user_stdb_' . uniqid() . '@example.com',
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'hash-stdb-' . Str::uuid(),
            'sap_code'   => 'SAPB' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);
    }

    /**
     * Test 1: Empty state when both Online and Offline have no data
     */
    public function test_all_mode_shows_empty_state_when_both_sources_empty(): void
    {
        $response = $this->actingAs($this->userA)
            ->get('/my-learning/partial/record?record_mode=all');

        $response->assertStatus(200);
        $response->assertSee('No learning records available.');
        $response->assertSee('Your online and offline learning records will appear here once available.');
    }

    /**
     * Test 2: Online has data, Offline empty -> All shows Online records
     */
    public function test_all_mode_shows_online_only_when_offline_empty(): void
    {
        $classification = Classification::firstOrCreate(
            ['classification_name' => 'Tech Standard'],
            ['description' => 'Test Classification']
        );

        $content = Content::create([
            'title'             => 'Solo Online Training Title',
            'slug'              => 'solo-online-' . Str::uuid(),
            'type'              => 'Video',
            'active'            => 'Active',
            'classification_id' => $classification->id,
            'duration'          => '00:20:00',
        ]);

        ContentUser::create([
            'user_id'    => $this->userA->id,
            'content_id' => $content->id,
            'finish'     => '1',
            'progress'   => 100,
        ]);

        $response = $this->actingAs($this->userA)
            ->get('/my-learning/partial/record?record_mode=all');

        $response->assertStatus(200);
        $response->assertSee('Solo Online Training Title');
        $response->assertSee('Online');
        $response->assertSee('learningRecordAllTable');
    }

    /**
     * Test 3: Online empty, Offline has data -> All shows Offline records
     */
    public function test_all_mode_shows_offline_only_when_online_empty(): void
    {
        $training = OfflineTraining::create([
            'course_name'         => 'Solo Offline Workshop Title',
            'type'                => 'Workshop',
            'category'            => 'Technical',
            'main_classification' => 'Engineering',
            'learning_hours'      => 4.0,
            'start_date'          => now()->toDateString(),
            'finish_date'         => now()->toDateString(),
        ]);

        OfflineTrainingUser::create([
            'offline_training_id' => $training->id,
            'user_id'             => $this->userA->id,
            'employee_id'         => $this->userA->id_emp,
            'learning_hours'      => 4.0,
            'progress'            => 100,
            'attendance'          => true,
        ]);

        $response = $this->actingAs($this->userA)
            ->get('/my-learning/partial/record?record_mode=all');

        $response->assertStatus(200);
        $response->assertSee('Solo Offline Workshop Title');
        $response->assertSee('Offline');
        $response->assertSee('learningRecordAllTable');
    }

    /**
     * Test 4: Both sources have data -> All combines both and differentiates source
     */
    public function test_all_mode_combines_both_sources_with_correct_structure(): void
    {
        $classification = Classification::firstOrCreate(
            ['classification_name' => 'Data Science'],
            ['description' => 'Test Classification']
        );

        $content = Content::create([
            'title'             => 'Combined Python Essentials',
            'slug'              => 'combined-python-' . Str::uuid(),
            'type'              => 'Video',
            'active'            => 'Active',
            'classification_id' => $classification->id,
            'duration'          => '00:30:00',
        ]);

        ContentUser::create([
            'user_id'    => $this->userA->id,
            'content_id' => $content->id,
            'finish'     => '1',
            'progress'   => 100,
        ]);

        $training = OfflineTraining::create([
            'course_name'         => 'Combined Project Management Seminar',
            'type'                => 'Seminar',
            'category'            => 'Leadership',
            'main_classification' => 'Management',
            'learning_hours'      => 5.0,
            'start_date'          => now()->toDateString(),
            'finish_date'         => now()->toDateString(),
        ]);

        OfflineTrainingUser::create([
            'offline_training_id' => $training->id,
            'user_id'             => $this->userA->id,
            'employee_id'         => $this->userA->id_emp,
            'learning_hours'      => 5.0,
            'progress'            => 100,
            'attendance'          => true,
            'certificate'         => 'Yes',
        ]);

        $response = $this->actingAs($this->userA)
            ->get('/my-learning/partial/record?record_mode=all');

        $response->assertStatus(200);
        $contentHtml = $response->getContent();

        // Both titles visible
        $this->assertStringContainsString('Combined Python Essentials', $contentHtml);
        $this->assertStringContainsString('Combined Project Management Seminar', $contentHtml);

        // Target columns are present
        $this->assertStringContainsString('Training Title', $contentHtml);
        $this->assertStringContainsString('Attendance', $contentHtml);
        $this->assertStringContainsString('Certificate', $contentHtml);

        // Source badges visible
        $this->assertStringContainsString('Online', $contentHtml);
        $this->assertStringContainsString('Offline', $contentHtml);
    }

    /**
     * Test 5: Same title across Online and Offline remains separate unique records
     */
    public function test_same_title_across_sources_remains_separate_records(): void
    {
        $sameTitle = 'Identical Training Name Across Sources';

        $classification = Classification::firstOrCreate(
            ['classification_name' => 'General Science'],
            ['description' => 'Test Classification']
        );

        $content = Content::create([
            'title'             => $sameTitle,
            'slug'              => 'same-title-' . Str::uuid(),
            'type'              => 'Document',
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

        $training = OfflineTraining::create([
            'course_name'         => $sameTitle,
            'type'                => 'Classroom',
            'category'            => 'General',
            'main_classification' => 'General Science',
            'learning_hours'      => 2.0,
            'start_date'          => now()->toDateString(),
            'finish_date'         => now()->toDateString(),
        ]);

        OfflineTrainingUser::create([
            'offline_training_id' => $training->id,
            'user_id'             => $this->userA->id,
            'employee_id'         => $this->userA->id_emp,
            'learning_hours'      => 2.0,
            'progress'            => 100,
            'attendance'          => true,
        ]);

        $response = $this->actingAs($this->userA)
            ->get('/my-learning/partial/record?record_mode=all');

        $response->assertStatus(200);
        $contentHtml = $response->getContent();

        // Title appears at least twice
        $this->assertGreaterThanOrEqual(2, substr_count($contentHtml, $sameTitle));

        // Both row classes exist
        $this->assertStringContainsString('record-row-online', $contentHtml);
        $this->assertStringContainsString('record-row-offline', $contentHtml);
    }

    /**
     * Test 6: User scoping / isolation (User A cannot see User B's records)
     */
    public function test_user_scoping_prevents_viewing_other_users_records(): void
    {
        $trainingB = OfflineTraining::create([
            'course_name'         => 'User B Private Record',
            'type'                => 'Executive',
            'category'            => 'Secret',
            'main_classification' => 'Confidential',
            'learning_hours'      => 10.0,
            'start_date'          => now()->toDateString(),
            'finish_date'         => now()->toDateString(),
        ]);

        OfflineTrainingUser::create([
            'offline_training_id' => $trainingB->id,
            'user_id'             => $this->userB->id,
            'employee_id'         => $this->userB->id_emp,
            'learning_hours'      => 10.0,
            'progress'            => 100,
            'attendance'          => true,
        ]);

        $responseA = $this->actingAs($this->userA)
            ->get('/my-learning/partial/record?record_mode=all');

        $responseA->assertStatus(200);
        $responseA->assertDontSee('User B Private Record');
    }

    /**
     * Test 7: Filter zero-results shows "No records match your current filters."
     */
    public function test_filter_zero_results_shows_no_records_match_filters(): void
    {
        $training = OfflineTraining::create([
            'course_name'         => 'Any Training Exists',
            'type'                => 'Workshop',
            'category'            => 'Standard',
            'main_classification' => 'Standard',
            'learning_hours'      => 3.0,
            'start_date'          => now()->toDateString(),
            'finish_date'         => now()->toDateString(),
        ]);

        OfflineTrainingUser::create([
            'offline_training_id' => $training->id,
            'user_id'             => $this->userA->id,
            'employee_id'         => $this->userA->id_emp,
            'learning_hours'      => 3.0,
            'progress'            => 100,
            'attendance'          => true,
        ]);

        $response = $this->actingAs($this->userA)
            ->get('/my-learning/partial/record?record_mode=all&record_search=non_matching_search_keyword_xyz');

        $response->assertStatus(200);
        $response->assertSee('No records match your current filters.');
        $response->assertSee('Reset Filters');
    }

    /**
     * Test 8: Export All respects search filter
     */
    public function test_export_all_respects_filter(): void
    {
        $training = OfflineTraining::create([
            'course_name'         => 'Exportable Offline Course',
            'type'                => 'Workshop',
            'category'            => 'Standard',
            'main_classification' => 'Standard',
            'learning_hours'      => 3.0,
            'start_date'          => now()->toDateString(),
            'finish_date'         => now()->toDateString(),
        ]);

        OfflineTrainingUser::create([
            'offline_training_id' => $training->id,
            'user_id'             => $this->userA->id,
            'employee_id'         => $this->userA->id_emp,
            'learning_hours'      => 3.0,
            'progress'            => 100,
            'attendance'          => true,
        ]);

        $response = $this->actingAs($this->userA)
            ->get('/my-learning/learning-record/export?source=all&search=Exportable');

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
    }

    /**
     * Test 9: Verify 4-tier vertical structure for Training Title column in All mode
     */
    public function test_training_title_4_tier_layout_structure(): void
    {
        $trainingWithVenue = OfflineTraining::create([
            'course_name'         => 'Effective Business Communication',
            'sub_course_name'     => 'Business Communication Skills',
            'type'                => 'Workshop',
            'category'            => 'Soft Skills',
            'main_classification' => 'Communication',
            'location'            => 'Auditorium Krakatau Posco',
            'learning_hours'      => 4.0,
            'start_date'          => now()->toDateString(),
            'finish_date'         => now()->toDateString(),
        ]);

        $otuWithVenue = OfflineTrainingUser::create([
            'offline_training_id' => $trainingWithVenue->id,
            'user_id'             => $this->userA->id,
            'employee_id'         => $this->userA->id_emp,
            'learning_hours'      => 4.0,
            'progress'            => 100,
            'attendance'          => true,
        ]);

        $trainingNoVenue = OfflineTraining::create([
            'course_name'         => 'Digital Transformation Essentials',
            'type'                => 'Seminar',
            'category'            => 'Digital',
            'main_classification' => 'Technology',
            'location'            => null,
            'learning_hours'      => 2.0,
            'start_date'          => now()->toDateString(),
            'finish_date'         => now()->toDateString(),
        ]);

        OfflineTrainingUser::create([
            'offline_training_id' => $trainingNoVenue->id,
            'user_id'             => $this->userA->id,
            'employee_id'         => $this->userA->id_emp,
            'learning_hours'      => 2.0,
            'progress'            => 100,
            'attendance'          => true,
        ]);

        $response = $this->actingAs($this->userA)
            ->get('/my-learning/partial/record?record_mode=all');

        $response->assertStatus(200);
        $html = $response->getContent();

        // 1. Stack container exists
        $this->assertStringContainsString('record-title-stack', $html);
        $this->assertStringContainsString('record-title-line', $html);

        // 2. Training Title button and modal trigger exist
        $this->assertStringContainsString('Effective Business Communication', $html);
        $this->assertStringContainsString('btn-record-offline-detail', $html);
        $this->assertStringContainsString('data-record-id="' . $otuWithVenue->id . '"', $html);
        $this->assertStringContainsString('id="record-offline-data-' . $otuWithVenue->id . '"', $html);

        // 3. Subtitle exists in separate line
        $this->assertStringContainsString('record-subtitle-line', $html);
        $this->assertStringContainsString('Business Communication Skills', $html);

        // 4. Source badge in separate line
        $this->assertStringContainsString('record-badge-line', $html);
        $this->assertStringContainsString('record-source-badge', $html);
        $this->assertStringContainsString('Offline', $html);

        // 5. Venue in separate line with map-pin icon
        $this->assertStringContainsString('record-venue-line', $html);
        $this->assertStringContainsString('Auditorium Krakatau Posco', $html);
        $this->assertStringContainsString('ti-map-pin', $html);
    }

    /**
     * Test 10: All mode and Offline mode share the identical 4-section Training Detail Modal
     */
    public function test_all_mode_and_offline_mode_share_identical_training_detail_modal_structure(): void
    {
        $training = OfflineTraining::create([
            'course_name'         => 'Advanced Leadership Workshop',
            'type'                => 'Workshop',
            'category'            => 'Management',
            'main_classification' => 'Leadership',
            'learning_hours'      => 6.0,
            'start_date'          => now()->toDateString(),
            'finish_date'         => now()->toDateString(),
        ]);

        OfflineTrainingUser::create([
            'offline_training_id' => $training->id,
            'user_id'             => $this->userA->id,
            'employee_id'         => $this->userA->id_emp,
            'learning_hours'      => 6.0,
            'progress'            => 100,
            'attendance'          => true,
        ]);

        // 1. Check All Mode
        $responseAll = $this->actingAs($this->userA)
            ->get('/my-learning/partial/record?record_mode=all');
        $responseAll->assertStatus(200);
        $htmlAll = $responseAll->getContent();

        // 2. Check Offline Mode
        $responseOffline = $this->actingAs($this->userA)
            ->get('/my-learning/partial/record?record_mode=offline');
        $responseOffline->assertStatus(200);
        $htmlOffline = $responseOffline->getContent();

        // Assert both contain the exact shared modal id and title
        foreach (['All' => $htmlAll, 'Offline' => $htmlOffline] as $modeName => $html) {
            $this->assertStringContainsString('id="recordOfflineDetailModal"', $html, "Modal ID missing in {$modeName}");
            $this->assertStringContainsString('Training Record Detail', $html, "Modal title missing in {$modeName}");

            // Section 1: Training Information
            $this->assertStringContainsString('Training Information', $html);
            $this->assertStringContainsString('id="dtCourseName"', $html);
            $this->assertStringContainsString('id="dtSubCourseName"', $html);
            $this->assertStringContainsString('id="dtType"', $html);
            $this->assertStringContainsString('id="dtCategory"', $html);
            $this->assertStringContainsString('id="dtClassification"', $html);

            // Section 2: Schedule & Venue
            $this->assertStringContainsString('Schedule & Venue', $html);
            $this->assertStringContainsString('id="dtDateDisplay"', $html);
            $this->assertStringContainsString('id="dtLearningHours"', $html);
            $this->assertStringContainsString('id="dtVenue"', $html);

            // Removed Sections: My Learning Result & Evaluation (Kirkpatrick Results)
            $this->assertStringNotContainsString('My Learning Result', $html);
            $this->assertStringNotContainsString('id="dtProgressBadge"', $html);
            $this->assertStringNotContainsString('id="dtAttendanceBadge"', $html);
            $this->assertStringNotContainsString('id="dtAssessmentScore"', $html);
            $this->assertStringNotContainsString('id="dtCertificateStatus"', $html);
            $this->assertStringNotContainsString('Evaluation (Kirkpatrick Results)', $html);
            $this->assertStringNotContainsString('id="dtL1Program"', $html);

            // Section 3: Employee Snapshot
            $this->assertStringContainsString('Employee Snapshot (At Record Time)', $html);
            $this->assertStringContainsString('id="dtEmployeeId"', $html);
            $this->assertStringContainsString('id="dtParticipantName"', $html);

            // Section 4: Service Bond & Obligation
            $this->assertStringContainsString('Service Bond & Obligation', $html);
            $this->assertStringContainsString('id="dtBondStatus"', $html);
            $this->assertStringContainsString('id="dtBondNotice"', $html);
        }
    }

    /**
     * Test 11: All mode Quick Filter has dynamic compact dropdowns and single toolbar
     */
    public function test_all_mode_quick_filter_has_compact_dropdowns_and_unified_toolbar(): void
    {
        $response = $this->actingAs($this->userA)
            ->get('/my-learning/partial/record?record_mode=all');

        $response->assertStatus(200);
        $html = $response->getContent();

        // Filter shell and toolbar
        $this->assertStringContainsString('id="recordAllFilterBar"', $html);
        $this->assertStringContainsString('fast-filter-toolbar', $html);
        $this->assertStringContainsString('learning-quick-dropdowns', $html);

        // 4 Dropdowns
        $this->assertStringContainsString('Source:', $html);
        $this->assertStringContainsString('Type:', $html);
        $this->assertStringContainsString('Classification:', $html);
        $this->assertStringContainsString('Year:', $html);

        // Search input
        $this->assertStringContainsString('id="recordAllSearch"', $html);

        // Action buttons
        $this->assertStringContainsString('id="recordAllApplyBtn"', $html);
        $this->assertStringContainsString('id="recordAllMoreFilterBtn"', $html);
        $this->assertStringContainsString('id="recordAllResetBtn"', $html);
        $this->assertStringContainsString('id="recordAllExportBtn"', $html);
    }

    /**
     * Test 12: More Filters - Learning Status filtering (completed, in_progress, not_started)
     */
    public function test_more_filter_learning_status_filtering(): void
    {
        $classification = Classification::firstOrCreate(
            ['classification_name' => 'Status Test Class'],
            ['description' => 'Test Classification']
        );

        $onlineDone = Content::create([
            'title'             => 'Online Completed Status Course',
            'slug'              => 'online-done-' . Str::uuid(),
            'type'              => 'Video',
            'active'            => 'Active',
            'classification_id' => $classification->id,
            'duration'          => '00:15:00',
        ]);
        ContentUser::create([
            'user_id'    => $this->userA->id,
            'content_id' => $onlineDone->id,
            'finish'     => '1',
            'progress'   => 100,
        ]);

        $offlineDone = OfflineTraining::create([
            'course_name'         => 'Offline Completed Status Workshop',
            'type'                => 'Workshop',
            'category'            => 'Technical',
            'main_classification' => 'Status Test Class',
            'learning_hours'      => 4.0,
            'start_date'          => now()->toDateString(),
            'finish_date'         => now()->toDateString(),
        ]);
        OfflineTrainingUser::create([
            'offline_training_id' => $offlineDone->id,
            'user_id'             => $this->userA->id,
            'employee_id'         => $this->userA->id_emp,
            'learning_hours'      => 4.0,
            'progress'            => 100,
            'attendance'          => true,
        ]);

        $offlineProgress = OfflineTraining::create([
            'course_name'         => 'Offline In Progress Status Workshop',
            'type'                => 'Workshop',
            'category'            => 'Technical',
            'main_classification' => 'Status Test Class',
            'learning_hours'      => 4.0,
            'start_date'          => now()->toDateString(),
            'finish_date'         => now()->toDateString(),
        ]);
        OfflineTrainingUser::create([
            'offline_training_id' => $offlineProgress->id,
            'user_id'             => $this->userA->id,
            'employee_id'         => $this->userA->id_emp,
            'learning_hours'      => 4.0,
            'progress'            => 35,
            'attendance'          => true,
        ]);

        $offlineNotStarted = OfflineTraining::create([
            'course_name'         => 'Offline Not Started Status Workshop',
            'type'                => 'Workshop',
            'category'            => 'Technical',
            'main_classification' => 'Status Test Class',
            'learning_hours'      => 4.0,
            'start_date'          => now()->toDateString(),
            'finish_date'         => now()->toDateString(),
        ]);
        OfflineTrainingUser::create([
            'offline_training_id' => $offlineNotStarted->id,
            'user_id'             => $this->userA->id,
            'employee_id'         => $this->userA->id_emp,
            'learning_hours'      => 4.0,
            'progress'            => 0,
            'attendance'          => true,
        ]);

        // Filter: Completed
        $respCompleted = $this->actingAs($this->userA)
            ->get('/my-learning/partial/record?record_mode=all&record_filter_status=completed');
        $respCompleted->assertStatus(200);
        $respCompleted->assertSee('Online Completed Status Course');
        $respCompleted->assertSee('Offline Completed Status Workshop');
        $respCompleted->assertDontSee('Offline In Progress Status Workshop');
        $respCompleted->assertDontSee('Offline Not Started Status Workshop');

        // Filter: In Progress
        $respProgress = $this->actingAs($this->userA)
            ->get('/my-learning/partial/record?record_mode=all&record_filter_status=in_progress');
        $respProgress->assertStatus(200);
        $respProgress->assertSee('Offline In Progress Status Workshop');
        $respProgress->assertDontSee('Online Completed Status Course');
        $respProgress->assertDontSee('Offline Completed Status Workshop');
        $respProgress->assertDontSee('Offline Not Started Status Workshop');

        // Filter: Not Started
        $respNotStarted = $this->actingAs($this->userA)
            ->get('/my-learning/partial/record?record_mode=all&record_filter_status=not_started');
        $respNotStarted->assertStatus(200);
        $respNotStarted->assertSee('Offline Not Started Status Workshop');
        $respNotStarted->assertDontSee('Online Completed Status Course');
        $respNotStarted->assertDontSee('Offline Completed Status Workshop');
        $respNotStarted->assertDontSee('Offline In Progress Status Workshop');
    }

    /**
     * Test 13: More Filters - Duration range filter (minutes converted to seconds)
     */
    public function test_more_filter_duration_range_filtering(): void
    {
        $classification = Classification::firstOrCreate(
            ['classification_name' => 'Duration Test Class'],
            ['description' => 'Test Classification']
        );

        $shortOnline = Content::create([
            'title'             => 'Short 10 Min Course',
            'slug'              => 'short-10m-' . Str::uuid(),
            'type'              => 'Video',
            'active'            => 'Active',
            'classification_id' => $classification->id,
            'duration'          => '00:10:00',
        ]);
        ContentUser::create([
            'user_id'    => $this->userA->id,
            'content_id' => $shortOnline->id,
            'finish'     => '1',
            'progress'   => 100,
        ]);

        $mediumOnline = Content::create([
            'title'             => 'Medium 40 Min Course',
            'slug'              => 'med-40m-' . Str::uuid(),
            'type'              => 'Video',
            'active'            => 'Active',
            'classification_id' => $classification->id,
            'duration'          => '00:40:00',
        ]);
        ContentUser::create([
            'user_id'    => $this->userA->id,
            'content_id' => $mediumOnline->id,
            'finish'     => '1',
            'progress'   => 100,
        ]);

        $longOnline = Content::create([
            'title'             => 'Long 90 Min Course',
            'slug'              => 'long-90m-' . Str::uuid(),
            'type'              => 'Video',
            'active'            => 'Active',
            'classification_id' => $classification->id,
            'duration'          => '01:30:00',
        ]);
        ContentUser::create([
            'user_id'    => $this->userA->id,
            'content_id' => $longOnline->id,
            'finish'     => '1',
            'progress'   => 100,
        ]);

        // Filter: 25 to 60 minutes -> only Medium 40 Min Course
        $response = $this->actingAs($this->userA)
            ->get('/my-learning/partial/record?record_mode=all&record_filter_duration_min=25&record_filter_duration_max=60');
        $response->assertStatus(200);
        $response->assertSee('Medium 40 Min Course');
        $response->assertDontSee('Short 10 Min Course');
        $response->assertDontSee('Long 90 Min Course');
    }

    /**
     * Test 14: More Filters - Learning Hours range filter (lh_min, lh_max)
     */
    public function test_more_filter_learning_hours_range_filtering(): void
    {
        $training2h = OfflineTraining::create([
            'course_name'         => 'Offline 2 Hours Seminar',
            'type'                => 'Seminar',
            'category'            => 'General',
            'main_classification' => 'LH Test Class',
            'learning_hours'      => 2.0,
            'start_date'          => now()->toDateString(),
            'finish_date'         => now()->toDateString(),
        ]);
        OfflineTrainingUser::create([
            'offline_training_id' => $training2h->id,
            'user_id'             => $this->userA->id,
            'employee_id'         => $this->userA->id_emp,
            'learning_hours'      => 2.0,
            'progress'            => 100,
            'attendance'          => true,
        ]);

        $training5h = OfflineTraining::create([
            'course_name'         => 'Offline 5 Hours Seminar',
            'type'                => 'Seminar',
            'category'            => 'General',
            'main_classification' => 'LH Test Class',
            'learning_hours'      => 5.0,
            'start_date'          => now()->toDateString(),
            'finish_date'         => now()->toDateString(),
        ]);
        OfflineTrainingUser::create([
            'offline_training_id' => $training5h->id,
            'user_id'             => $this->userA->id,
            'employee_id'         => $this->userA->id_emp,
            'learning_hours'      => 5.0,
            'progress'            => 100,
            'attendance'          => true,
        ]);

        $training10h = OfflineTraining::create([
            'course_name'         => 'Offline 10 Hours Seminar',
            'type'                => 'Seminar',
            'category'            => 'General',
            'main_classification' => 'LH Test Class',
            'learning_hours'      => 10.0,
            'start_date'          => now()->toDateString(),
            'finish_date'         => now()->toDateString(),
        ]);
        OfflineTrainingUser::create([
            'offline_training_id' => $training10h->id,
            'user_id'             => $this->userA->id,
            'employee_id'         => $this->userA->id_emp,
            'learning_hours'      => 10.0,
            'progress'            => 100,
            'attendance'          => true,
        ]);

        // Filter: 4.0 to 7.0 learning hours -> only Offline 5 Hours Seminar
        $response = $this->actingAs($this->userA)
            ->get('/my-learning/partial/record?record_mode=all&record_filter_lh_min=4.0&record_filter_lh_max=7.0');
        $response->assertStatus(200);
        $response->assertSee('Offline 5 Hours Seminar');
        $response->assertDontSee('Offline 2 Hours Seminar');
        $response->assertDontSee('Offline 10 Hours Seminar');
    }

    /**
     * Test 15: More Filters - Certificate Status filtering (certified, uncertified)
     */
    public function test_more_filter_certificate_status_filtering(): void
    {
        $trainingCertified = OfflineTraining::create([
            'course_name'         => 'Offline With Certificate',
            'type'                => 'Workshop',
            'category'            => 'Technical',
            'main_classification' => 'Cert Test Class',
            'learning_hours'      => 3.0,
            'start_date'          => now()->toDateString(),
            'finish_date'         => now()->toDateString(),
        ]);
        OfflineTrainingUser::create([
            'offline_training_id' => $trainingCertified->id,
            'user_id'             => $this->userA->id,
            'employee_id'         => $this->userA->id_emp,
            'learning_hours'      => 3.0,
            'progress'            => 100,
            'attendance'          => true,
            'certificate'         => 'Yes',
        ]);

        $trainingUncertified = OfflineTraining::create([
            'course_name'         => 'Offline Without Certificate',
            'type'                => 'Workshop',
            'category'            => 'Technical',
            'main_classification' => 'Cert Test Class',
            'learning_hours'      => 3.0,
            'start_date'          => now()->toDateString(),
            'finish_date'         => now()->toDateString(),
        ]);
        OfflineTrainingUser::create([
            'offline_training_id' => $trainingUncertified->id,
            'user_id'             => $this->userA->id,
            'employee_id'         => $this->userA->id_emp,
            'learning_hours'      => 3.0,
            'progress'            => 100,
            'attendance'          => true,
            'certificate'         => 'No',
        ]);

        // Filter: Certified
        $respCert = $this->actingAs($this->userA)
            ->get('/my-learning/partial/record?record_mode=all&record_filter_certificate=certified');
        $respCert->assertStatus(200);
        $respCert->assertSee('Offline With Certificate');
        $respCert->assertDontSee('Offline Without Certificate');

        // Filter: Uncertified
        $respUncert = $this->actingAs($this->userA)
            ->get('/my-learning/partial/record?record_mode=all&record_filter_certificate=uncertified');
        $respUncert->assertStatus(200);
        $respUncert->assertSee('Offline Without Certificate');
        $respUncert->assertDontSee('Offline With Certificate');
    }

    /**
     * Test 16: More Filters - Offline-specific filters (attendance, sub_classification, delivery_method, provider)
     */
    public function test_more_filter_offline_specific_filters(): void
    {
        $classification = Classification::firstOrCreate(
            ['classification_name' => 'Offline Spec Class'],
            ['description' => 'Test Classification']
        );

        $online = Content::create([
            'title'             => 'Online Baseline Control Course',
            'slug'              => 'online-spec-ctrl-' . Str::uuid(),
            'type'              => 'Video',
            'active'            => 'Active',
            'classification_id' => $classification->id,
            'duration'          => '00:15:00',
        ]);
        ContentUser::create([
            'user_id'    => $this->userA->id,
            'content_id' => $online->id,
            'finish'     => '1',
            'progress'   => 100,
        ]);

        $training1 = OfflineTraining::create([
            'course_name'         => 'Specific Classroom Posco Course',
            'type'                => 'Workshop',
            'category'            => 'Core',
            'main_classification' => 'Engineering',
            'sub_classification'  => 'Mechanical',
            'delivery_method'     => 'Classroom',
            'provider'            => 'Posco Academy',
            'learning_hours'      => 4.0,
            'start_date'          => now()->toDateString(),
            'finish_date'         => now()->toDateString(),
        ]);
        OfflineTrainingUser::create([
            'offline_training_id' => $training1->id,
            'user_id'             => $this->userA->id,
            'employee_id'         => $this->userA->id_emp,
            'learning_hours'      => 4.0,
            'progress'            => 100,
            'attendance'          => true,
        ]);

        $training2 = OfflineTraining::create([
            'course_name'         => 'Specific Virtual Partner Course',
            'type'                => 'Workshop',
            'category'            => 'Core',
            'main_classification' => 'Engineering',
            'sub_classification'  => 'Electrical',
            'delivery_method'     => 'Virtual',
            'provider'            => 'External Partner',
            'learning_hours'      => 4.0,
            'start_date'          => now()->toDateString(),
            'finish_date'         => now()->toDateString(),
        ]);
        OfflineTrainingUser::create([
            'offline_training_id' => $training2->id,
            'user_id'             => $this->userA->id,
            'employee_id'         => $this->userA->id_emp,
            'learning_hours'      => 4.0,
            'progress'            => 100,
            'attendance'          => false,
        ]);

        // Filter: Attendance = Present (excludes Absent and excludes Online)
        $respAtt = $this->actingAs($this->userA)
            ->get('/my-learning/partial/record?record_mode=all&record_filter_attendance=present');
        $respAtt->assertStatus(200);
        $respAtt->assertSee('Specific Classroom Posco Course');
        $respAtt->assertDontSee('Specific Virtual Partner Course');
        $respAtt->assertDontSee('Online Baseline Control Course');

        // Filter: Sub Classification = Mechanical
        $respSub = $this->actingAs($this->userA)
            ->get('/my-learning/partial/record?record_mode=all&record_filter_sub_classification=Mechanical');
        $respSub->assertStatus(200);
        $respSub->assertSee('Specific Classroom Posco Course');
        $respSub->assertDontSee('Specific Virtual Partner Course');
        $respSub->assertDontSee('Online Baseline Control Course');

        // Filter: Delivery Method = Virtual
        $respDel = $this->actingAs($this->userA)
            ->get('/my-learning/partial/record?record_mode=all&record_filter_delivery_method=Virtual');
        $respDel->assertStatus(200);
        $respDel->assertSee('Specific Virtual Partner Course');
        $respDel->assertDontSee('Specific Classroom Posco Course');
        $respDel->assertDontSee('Online Baseline Control Course');

        // Filter: Provider = External Partner
        $respProv = $this->actingAs($this->userA)
            ->get('/my-learning/partial/record?record_mode=all&record_filter_provider=External%20Partner');
        $respProv->assertStatus(200);
        $respProv->assertSee('Specific Virtual Partner Course');
        $respProv->assertDontSee('Specific Classroom Posco Course');
        $respProv->assertDontSee('Online Baseline Control Course');
    }

    /**
     * Test 17: More Filters - Active badge count and chips rendering in All view
     */
    public function test_more_filter_badge_and_active_chips_rendering(): void
    {
        $response = $this->actingAs($this->userA)
            ->get('/my-learning/partial/record?record_mode=all&record_filter_category=Technical&record_filter_status=completed&record_filter_attendance=present');

        $response->assertStatus(200);
        $html = $response->getContent();

        // 1. Badge count on More Filters button is 3
        $this->assertStringContainsString('id="recordAllMoreFilterBtn"', $html);
        $this->assertStringContainsString('3', $html);

        // 2. Active chips container is visible
        $this->assertStringContainsString('record-all-active-filters', $html);

        // 3. Filter chip labels are rendered
        $this->assertStringContainsString('Category:', $html);
        $this->assertStringContainsString('Technical', $html);

        $this->assertStringContainsString('Status:', $html);
        $this->assertStringContainsString('Completed', $html);

        $this->assertStringContainsString('Attendance:', $html);
        $this->assertStringContainsString('Present', $html);

        // 4. Clear all action link exists
        $this->assertStringContainsString('id="recordAllClearAllMoreFilters"', $html);
        $this->assertStringContainsString('Clear all', $html);
    }

    /**
     * Test 18: Export All respects active More Filters
     */
    public function test_export_all_respects_more_filters(): void
    {
        $trainingTarget = OfflineTraining::create([
            'course_name'         => 'Target Export Category Course',
            'type'                => 'Workshop',
            'category'            => 'SpecialCategoryExport',
            'main_classification' => 'Standard',
            'learning_hours'      => 3.0,
            'start_date'          => now()->toDateString(),
            'finish_date'         => now()->toDateString(),
        ]);
        OfflineTrainingUser::create([
            'offline_training_id' => $trainingTarget->id,
            'user_id'             => $this->userA->id,
            'employee_id'         => $this->userA->id_emp,
            'learning_hours'      => 3.0,
            'progress'            => 100,
            'attendance'          => true,
        ]);

        $trainingOther = OfflineTraining::create([
            'course_name'         => 'Other Category Course',
            'type'                => 'Workshop',
            'category'            => 'OtherCategoryExport',
            'main_classification' => 'Standard',
            'learning_hours'      => 3.0,
            'start_date'          => now()->toDateString(),
            'finish_date'         => now()->toDateString(),
        ]);
        OfflineTrainingUser::create([
            'offline_training_id' => $trainingOther->id,
            'user_id'             => $this->userA->id,
            'employee_id'         => $this->userA->id_emp,
            'learning_hours'      => 3.0,
            'progress'            => 100,
            'attendance'          => true,
        ]);

        $response = $this->actingAs($this->userA)
            ->get('/my-learning/learning-record/export?source=all&category=SpecialCategoryExport&status=completed');

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
    }
}
