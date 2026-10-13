<?php

namespace Tests\Feature;

use App\Exports\ExportMyAllLearningRecord;
use App\Exports\ExportMyLearningRecord;
use App\Exports\ExportMyOfflineLearningRecord;
use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class MyLearningRecordExportStandardizationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $userA;
    protected User $userB;
    protected Classification $classificationTech;
    protected Classification $classificationLead;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = User::factory()->create([
            'name'       => 'User A Learner ' . rand(1000, 9999),
            'id_emp'     => 'EA' . rand(1000, 9999),
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'sso-a-' . Str::uuid(),
            'sap_code'   => 'SAP-A-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->userB = User::factory()->create([
            'name'       => 'User B Learner ' . rand(1000, 9999),
            'id_emp'     => 'EB' . rand(1000, 9999),
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'sso-b-' . Str::uuid(),
            'sap_code'   => 'SAP-B-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->classificationTech = Classification::firstOrCreate(
            ['classification_name' => 'Technical Skills'],
            ['slug' => 'technical-skills']
        );

        $this->classificationLead = Classification::firstOrCreate(
            ['classification_name' => 'Leadership'],
            ['slug' => 'leadership']
        );
    }

    /**
     * TEST 1: Column structure and header matching exact table specifications across all 3 modes.
     */
    public function test_exact_export_columns_for_all_three_modes(): void
    {
        $this->actingAs($this->userA);

        // 1. KP OWL (Online): Exactly 11 columns matching record-section.blade.php
        $exportOnline = new ExportMyLearningRecord();
        $this->assertEquals([
            'No',
            'Course',
            'Type',
            'Category',
            'Classification',
            'Duration',
            'Learning Hour',
            'Progress',
            'Date Time',
            'Assessment Score',
            'Certificate',
        ], $exportOnline->headings());

        // 2. Non KP OWL (Offline): Exactly 12 columns matching learning-record-offline.blade.php
        $exportOffline = new ExportMyOfflineLearningRecord();
        $this->assertEquals([
            'No',
            'Training Title',
            'Type',
            'Category',
            'Classification',
            'Duration',
            'Learning Hour',
            'Progress',
            'Attendance',
            'Date',
            'Score',
            'Certificate',
        ], $exportOffline->headings());

        // 3. All Record: Exactly 12 columns matching learning-record-all.blade.php (No 'Source' column)
        $exportAll = new ExportMyAllLearningRecord();
        $this->assertEquals([
            'No',
            'Training Title',
            'Type',
            'Category',
            'Classification',
            'Duration',
            'Learning Hour',
            'Progress',
            'Attendance',
            'Date',
            'Score',
            'Certificate',
        ], $exportAll->headings());
    }

    /**
     * TEST 2: Standardization constraints - exactly 1 header row, data on row 2, no TOTAL row, no empty rows.
     */
    public function test_standardization_no_total_row_and_single_header_in_all_modes(): void
    {
        // Seed Online record
        $content = Content::create([
            'title'             => 'Standard Online Course',
            'slug'              => 'std-online-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classificationTech->id,
            'duration'          => '00:30:00',
            'learning_hours'    => 0.5,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);
        ContentUser::create([
            'user_id'          => $this->userA->id,
            'content_id'       => $content->id,
            'current_time'     => 1800,
            'duration_seconds' => 1800,
            'progress'         => 100,
            'finish'           => '1',
            'completed_at'     => Carbon::parse('2026-03-01 10:00:00'),
        ]);

        // Seed Offline record
        $offline = OfflineTraining::create([
            'course_name'         => 'Standard Offline Workshop',
            'category'            => 'Offline Training',
            'type'                => 'In-House',
            'main_classification' => 'Leadership',
            'duration_seconds'    => 28800,
            'learning_hours'      => 8.0,
            'start_date'          => '2026-03-10',
            'finish_date'         => '2026-03-10',
        ]);
        OfflineTrainingUser::create([
            'offline_training_id' => $offline->id,
            'user_id'             => $this->userA->id,
            'employee_id'         => $this->userA->id_emp,
            'progress'            => 100,
            'attendance'          => 1,
            'assessment_score'    => 88,
        ]);

        $this->actingAs($this->userA);

        // A. Online Check
        $onlineData = (new ExportMyLearningRecord())->array();
        $this->assertCount(2, $onlineData); // 1 header + 1 data row
        $this->assertNull(collect($onlineData)->firstWhere(0, 'TOTAL'), 'Online export must NOT have TOTAL row');
        $this->assertEquals('Standard Online Course', $onlineData[1][1]);
        $this->assertEquals('00:30:00', $onlineData[1][5]);
        $this->assertEquals('0.50 hrs', $onlineData[1][6]);
        $this->assertEquals('100%', $onlineData[1][7]);

        // B. Offline Check
        $offlineData = (new ExportMyOfflineLearningRecord())->array();
        $this->assertCount(2, $offlineData); // 1 header + 1 data row
        $this->assertNull(collect($offlineData)->firstWhere(0, 'TOTAL'), 'Offline export must NOT have TOTAL row');
        $this->assertEquals('Standard Offline Workshop', $offlineData[1][1]);
        $this->assertEquals('08:00:00', $offlineData[1][5]);
        $this->assertEquals('8.00 hrs', $offlineData[1][6]);
        $this->assertEquals('100%', $offlineData[1][7]);
        $this->assertEquals('Present', $offlineData[1][8]);

        // C. All Record Check
        $allData = (new ExportMyAllLearningRecord())->array();
        $this->assertCount(3, $allData); // 1 header + 2 data rows
        $this->assertNull(collect($allData)->firstWhere(0, 'TOTAL'), 'All Record export must NOT have TOTAL row');
    }

    /**
     * TEST 3: User Data Security - strictly user-scoped, no cross-user data leakage.
     */
    public function test_cross_user_isolation_strictly_enforced(): void
    {
        // Record for User B
        $contentB = Content::create([
            'title'             => 'User B Secret Training',
            'slug'              => 'user-b-secret-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classificationTech->id,
            'duration'          => '00:15:00',
            'learning_hours'    => 0.25,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);
        ContentUser::create([
            'user_id'          => $this->userB->id,
            'content_id'       => $contentB->id,
            'progress'         => 100,
            'finish'           => '1',
            'duration_seconds' => 900,
            'completed_at'     => Carbon::parse('2026-02-15 10:00:00'),
        ]);

        $offlineB = OfflineTraining::create([
            'course_name'         => 'User B Secret Offline Event',
            'category'            => 'Offline Training',
            'type'                => 'In-House',
            'main_classification' => 'Leadership',
            'duration_seconds'    => 14400,
            'learning_hours'      => 4.0,
            'start_date'          => '2026-02-15',
            'finish_date'         => '2026-02-15',
        ]);
        OfflineTrainingUser::create([
            'offline_training_id' => $offlineB->id,
            'user_id'             => $this->userB->id,
            'employee_id'         => $this->userB->id_emp,
            'progress'            => 100,
            'attendance'          => 1,
        ]);

        // Logged in as User A
        $this->actingAs($this->userA);

        $onlineData = (new ExportMyLearningRecord())->array();
        $this->assertFalse(collect($onlineData)->pluck(1)->contains('User B Secret Training'));

        $offlineData = (new ExportMyOfflineLearningRecord())->array();
        $this->assertFalse(collect($offlineData)->pluck(1)->contains('User B Secret Offline Event'));

        $allData = (new ExportMyAllLearningRecord())->array();
        $this->assertFalse(collect($allData)->pluck(1)->contains('User B Secret Training'));
        $this->assertFalse(collect($allData)->pluck(1)->contains('User B Secret Offline Event'));
    }

    /**
     * TEST 4: Export respects applied Quick Filters (Type, Classification, Year, Search) in Offline mode.
     */
    public function test_export_offline_respects_applied_quick_filters(): void
    {
        $offline1 = OfflineTraining::create([
            'course_name'         => 'Effective Business Communication',
            'category'            => 'Offline Training',
            'type'                => 'In-House',
            'main_classification' => 'Leadership',
            'duration_seconds'    => 28800,
            'learning_hours'      => 8.0,
            'start_date'          => '2026-05-10',
            'finish_date'         => '2026-05-10',
        ]);
        OfflineTrainingUser::create([
            'offline_training_id' => $offline1->id,
            'user_id'             => $this->userA->id,
            'employee_id'         => $this->userA->id_emp,
            'progress'            => 100,
            'attendance'          => 1,
            'assessment_score'    => 95,
        ]);

        $offline2 = OfflineTraining::create([
            'course_name'         => 'Advanced Technical Safety',
            'category'            => 'Offline Training',
            'type'                => 'Public',
            'main_classification' => 'Technical Skills',
            'duration_seconds'    => 14400,
            'learning_hours'      => 4.0,
            'start_date'          => '2025-04-10',
            'finish_date'         => '2025-04-10',
        ]);
        OfflineTrainingUser::create([
            'offline_training_id' => $offline2->id,
            'user_id'             => $this->userA->id,
            'employee_id'         => $this->userA->id_emp,
            'progress'            => 100,
            'attendance'          => 1,
            'assessment_score'    => 80,
        ]);

        $this->actingAs($this->userA);

        // Combined filter matching offline1 only: Type=In-House, Classification=Leadership, Year=2026, Search=Effective Business
        $filters = [
            'type'           => 'In-House',
            'classification' => 'Leadership',
            'year'           => '2026',
            'search'         => 'Effective Business',
        ];
        $export = new ExportMyOfflineLearningRecord($filters);
        $data = $export->array();
        $items = array_slice($data, 1);

        $this->assertCount(1, $items);
        $this->assertEquals('Effective Business Communication', $items[0][1]);
        $this->assertEquals('In-House', $items[0][2]);
        $this->assertEquals('Leadership', $items[0][4]);

        // Non-matching search yields header only
        $emptyExport = new ExportMyOfflineLearningRecord(['search' => 'NonExistentKeywordXYZ']);
        $emptyData = $emptyExport->array();
        $this->assertCount(1, $emptyData); // Only header
    }

    /**
     * TEST 5: Export respects applied More Filters (e.g. Category, Attendance, Delivery Method).
     */
    public function test_export_respects_more_filters(): void
    {
        $offline1 = OfflineTraining::create([
            'course_name'         => 'First Aid Practical Course',
            'category'            => 'Health & Safety',
            'type'                => 'In-House',
            'main_classification' => 'Technical Skills',
            'delivery_method'     => 'Classroom',
            'duration_seconds'    => 21600,
            'learning_hours'      => 6.0,
            'start_date'          => '2026-06-01',
            'finish_date'         => '2026-06-01',
        ]);
        OfflineTrainingUser::create([
            'offline_training_id' => $offline1->id,
            'user_id'             => $this->userA->id,
            'employee_id'         => $this->userA->id_emp,
            'progress'            => 100,
            'attendance'          => 1,
        ]);

        $offline2 = OfflineTraining::create([
            'course_name'         => 'Fire Safety Drill',
            'category'            => 'Health & Safety',
            'type'                => 'In-House',
            'main_classification' => 'Technical Skills',
            'delivery_method'     => 'Field Simulation',
            'duration_seconds'    => 7200,
            'learning_hours'      => 2.0,
            'start_date'          => '2026-06-02',
            'finish_date'         => '2026-06-02',
        ]);
        OfflineTrainingUser::create([
            'offline_training_id' => $offline2->id,
            'user_id'             => $this->userA->id,
            'employee_id'         => $this->userA->id_emp,
            'progress'            => 50,
            'attendance'          => 0,
        ]);

        $this->actingAs($this->userA);

        // Filter by Attendance: Present
        $exportPresent = new ExportMyOfflineLearningRecord(['attendance' => 'Present']);
        $itemsPresent = array_slice($exportPresent->array(), 1);
        $this->assertCount(1, $itemsPresent);
        $this->assertEquals('First Aid Practical Course', $itemsPresent[0][1]);

        // Filter by Delivery Method: Field Simulation
        $exportField = new ExportMyOfflineLearningRecord(['delivery_method' => 'Field Simulation']);
        $itemsField = array_slice($exportField->array(), 1);
        $this->assertCount(1, $itemsField);
        $this->assertEquals('Fire Safety Drill', $itemsField[0][1]);
    }

    /**
     * TEST 6: All Record export exports all filtered rows (all pagination items, not just first page).
     */
    public function test_export_exports_all_filtered_rows_not_just_current_page(): void
    {
        // Create 15 offline records for User A
        for ($i = 1; $i <= 15; $i++) {
            $t = OfflineTraining::create([
                'course_name'         => "Pagination Course {$i}",
                'category'            => 'Offline Training',
                'type'                => 'In-House',
                'main_classification' => 'Leadership',
                'duration_seconds'    => 3600,
                'learning_hours'      => 1.0,
                'start_date'          => '2026-01-01',
                'finish_date'         => '2026-01-01',
            ]);
            OfflineTrainingUser::create([
                'offline_training_id' => $t->id,
                'user_id'             => $this->userA->id,
                'employee_id'         => $this->userA->id_emp,
                'progress'            => 100,
                'attendance'          => 1,
            ]);
        }

        $this->actingAs($this->userA);

        // In web UI pagination is e.g. 10 items per page, but export must return all 15 records
        $export = new ExportMyOfflineLearningRecord(['page' => 2]); // Even if request has page=2
        $data = $export->array();
        $items = array_slice($data, 1);

        $this->assertCount(15, $items, 'Export must include all filtered rows across pagination');
    }

    /**
     * TEST 7: HTTP Controller download endpoint functionality and security.
     */
    public function test_http_export_controller_download_routes(): void
    {
        $this->actingAs($this->userA);

        // 1. Online export download
        $onlineResp = $this->get(route('user.learning.record.export', ['source' => 'online']));
        $onlineResp->assertStatus(200);
        $this->assertTrue(
            str_contains($onlineResp->headers->get('content-disposition', ''), 'MyLearningRecord_') ||
            str_contains($onlineResp->headers->get('content-disposition', ''), '.xlsx')
        );

        // 2. Offline export download
        $offlineResp = $this->get(route('user.learning.record.export', ['source' => 'offline']));
        $offlineResp->assertStatus(200);
        $this->assertTrue(
            str_contains($offlineResp->headers->get('content-disposition', ''), 'MyOfflineTrainingRecord_') ||
            str_contains($offlineResp->headers->get('content-disposition', ''), '.xlsx')
        );

        // 3. All record export download
        $allResp = $this->get(route('user.learning.record.export', ['source' => 'all']));
        $allResp->assertStatus(200);
        $this->assertTrue(
            str_contains($allResp->headers->get('content-disposition', ''), 'MyAllLearningRecord_') ||
            str_contains($allResp->headers->get('content-disposition', ''), '.xlsx')
        );
    }
}
