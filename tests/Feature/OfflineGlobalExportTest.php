<?php

namespace Tests\Feature;

use App\Exports\OfflineClassificationExport;
use App\Exports\OfflineTrainingExport;
use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use App\Services\Record\OfflineTrainingExcelSchema;
use App\Services\Record\RecordOfflineQueryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OfflineGlobalExportTest extends TestCase
{
    protected User $admin;
    protected ?User $regularUser;
    protected array $createdTrainingIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', '2')->first() ?? User::first();
        $this->regularUser = User::where('role', '1')->first();
    }

    protected function tearDown(): void
    {
        if (!empty($this->createdTrainingIds)) {
            DB::table('offline_training_users')->whereIn('offline_training_id', $this->createdTrainingIds)->delete();
            DB::table('offline_trainings')->whereIn('id', $this->createdTrainingIds)->delete();
        }

        parent::tearDown();
    }

    /**
     * Seed multiple trainings across different classifications for global export verification.
     */
    protected function seedMultiClassificationData(): array
    {
        // 1. Basic & Common Duties
        $t1 = OfflineTraining::create([
            'course_name'           => 'Corporate Core Values & Ethics',
            'sub_course_name'       => 'Integrity in Daily Operations',
            'type'                  => 'In-House',
            'category'              => 'Core Values',
            'level'                 => 'All',
            'batch'                 => 'Batch 1',
            'main_classification'   => 'Basic & Common Duties',
            'sub_classification'    => 'Ethics',
            'counted_corporate_kpi' => true,
            'delivery_method'       => 'Classroom',
            'provider'              => 'Internal',
            'duration_seconds'      => 14400,
            'learning_hours'        => 4.0,
            'start_date'            => '2026-01-10',
            'finish_date'           => '2026-01-10',
            'venue'                 => 'Auditorium Hall A',
            'instructor'            => 'HRDC Internal Team',
        ]);
        $this->createdTrainingIds[] = $t1->id;

        $p1 = OfflineTrainingUser::create([
            'offline_training_id' => $t1->id,
            'employee_id'         => 'EMPGLOBAL01',
            'name'                => 'ANDI WIJAYA',
            'grade'               => '3A',
            'job_position'        => 'Staff Officer',
            'team'                => 'General Affairs',
            'department'          => 'HR & GA',
            'company'             => 'PT Krakatau Posco',
            'learning_hours'      => 4.0,
            'progress'            => 100.0,
            'assessment_score'    => 88.0,
            'certificate'         => 'CERT-BCD-01',
            'attendance'          => true,
            'bond_status'         => 'No',
            'l1_program'          => '4.8 Very Good',
            'l2_numerical'        => 88.0,
            'l2_verbal'           => 'Passed',
            'l3_numerical'        => 85.0,
            'l3_verbal'           => 'Applied',
            'pic'                 => 'PIC GA',
        ]);

        // 2. Leadership
        $t2 = OfflineTraining::create([
            'course_name'           => 'Transformational Leadership Mastery',
            'sub_course_name'       => 'Leading High Performance Teams',
            'type'                  => 'Public',
            'category'              => 'Leadership',
            'level'                 => 'Advanced',
            'batch'                 => 'Batch 2',
            'main_classification'   => 'Leadership',
            'sub_classification'    => 'Executive Leadership',
            'counted_corporate_kpi' => true,
            'delivery_method'       => 'Workshop',
            'provider'              => 'External',
            'sub_provider'          => 'Vendor',
            'provider_name'         => 'Dale Carnegie Indo',
            'duration_seconds'      => 28800,
            'learning_hours'        => 8.0,
            'start_date'            => '2026-02-15',
            'finish_date'           => '2026-02-16',
            'venue'                 => 'Mercure Hotel Cilegon',
            'instructor'            => 'Senior Consultant',
        ]);
        $this->createdTrainingIds[] = $t2->id;

        $p2 = OfflineTrainingUser::create([
            'offline_training_id' => $t2->id,
            'employee_id'         => 'EMPGLOBAL02',
            'name'                => 'BAMBANG HERMANTO',
            'grade'               => '5A',
            'job_position'        => 'Department Head',
            'team'                => 'Steelmaking 1',
            'department'          => 'Production Dept',
            'company'             => 'PT Krakatau Posco',
            'learning_hours'      => 8.0,
            'progress'            => 100.0,
            'assessment_score'    => 94.5,
            'certificate'         => 'CERT-LEAD-02',
            'attendance'          => true,
            'bond_status'         => 'Yes',
            'bond_end_date'       => '2028-02-16',
            'bond_amount'         => 15000000.0,
            'l1_program'          => '5.0 Excellent',
            'l2_numerical'        => 94.5,
            'l2_verbal'           => 'High Honors',
            'l3_numerical'        => 92.0,
            'l3_verbal'           => 'Implemented',
            'pic'                 => 'HR Leadership Team',
        ]);

        // 3. Technology
        $t3 = OfflineTraining::create([
            'course_name'           => 'SCADA & Industrial IoT Architecture',
            'sub_course_name'       => 'Edge Computing in Steel Mill',
            'type'                  => 'Certification',
            'category'              => 'Industry 4.0',
            'level'                 => 'Intermediate',
            'batch'                 => 'Batch 1',
            'main_classification'   => 'Technology',
            'sub_classification'    => 'Automation',
            'counted_corporate_kpi' => false,
            'delivery_method'       => 'Practical',
            'provider'              => 'External',
            'sub_provider'          => 'Vendor',
            'provider_name'         => 'PT Siemens Indonesia',
            'duration_seconds'      => 21600,
            'learning_hours'        => 6.0,
            'start_date'            => '2026-03-20',
            'finish_date'           => '2026-03-21',
            'venue'                 => 'Automation Lab Room 3',
            'instructor'            => 'Ing. Hartono',
        ]);
        $this->createdTrainingIds[] = $t3->id;

        $p3 = OfflineTrainingUser::create([
            'offline_training_id' => $t3->id,
            'employee_id'         => 'EMPGLOBAL03',
            'name'                => 'CITRA DEWI',
            'grade'               => '4A',
            'job_position'        => 'Automation Engineer',
            'team'                => 'Smart Factory Team',
            'department'          => 'Information & Automation',
            'company'             => 'PT Krakatau Posco',
            'learning_hours'      => 6.0,
            'progress'            => 60.0,
            'assessment_score'    => 78.0,
            'certificate'         => null,
            'attendance'          => false,
            'attendance_reason'   => 'Plant emergency shift coverage',
            'bond_status'         => 'No',
            'l1_program'          => '4.5 Good',
            'l2_numerical'        => 78.0,
            'l2_verbal'           => 'Pending Re-evaluation',
            'l3_numerical'        => null,
            'l3_verbal'           => null,
            'pic'                 => 'PIC Tech',
        ]);

        return compact('t1', 'p1', 't2', 'p2', 't3', 'p3');
    }

    // =========================================================================
    // 1. UI BUTTON & LANDING PAGE INTEGRITY
    // =========================================================================

    public function test_offline_landing_renders_export_non_kp_owl_button(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.record.offline'));

        $response->assertStatus(200);
        $content = $response->getContent();
        $hasExport = str_contains($content, 'id="btn-export-offline-all"') || str_contains($content, 'id="btn-export-offline-xlsx"');
        $this->assertTrue($hasExport, 'Export button not found in offline landing page.');
        $response->assertSee('ti ti-file-spreadsheet');
    }

    public function test_export_button_points_to_correct_global_export_endpoint(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.record.offline'));

        $response->assertStatus(200);
        $expectedUrl = route('manage.record.offline.export');
        $response->assertSee('href="' . $expectedUrl . '"', false);
    }

    public function test_existing_landing_buttons_remain_intact(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.record.offline'));

        $response->assertStatus(200);
        // + Add Training
        $response->assertSee('id="btn-open-offline-add"', false);
        $response->assertSee(route('manage.record.offline.create'));
        $response->assertSee('Add Training');

        // Import Excel
        $response->assertSee('id="btn-open-offline-import"', false);
        $response->assertSee('Import Excel');

        // Download Template
        $response->assertSee('id="btn-download-offline-template"', false);
        $response->assertSee(route('manage.record.offline.import.template'));
        $response->assertSee('Download Template');
    }

    // =========================================================================
    // 2. ENDPOINT PERMISSIONS & AUTHENTICATION
    // =========================================================================

    public function test_export_endpoint_requires_authentication(): void
    {
        $response = $this->get(route('manage.record.offline.export'));
        $response->assertRedirect('/login');
    }

    public function test_export_endpoint_requires_record_management_permission(): void
    {
        if (!$this->regularUser) {
            $this->markTestSkipped('No regular user found.');
        }

        $response = $this->actingAs($this->regularUser)->get(route('manage.record.offline.export'));
        $response->assertStatus(403);
    }

    public function test_authorized_user_can_download_xlsx(): void
    {
        $this->seedMultiClassificationData();

        $response = $this->actingAs($this->admin)->get(route('manage.record.offline.export'));

        $response->assertStatus(200);
        $disposition = $response->headers->get('content-disposition');
        $this->assertNotNull($disposition);
        $this->assertStringContainsString('attachment', $disposition);
        $this->assertStringContainsString('.xlsx', $disposition);
    }

    public function test_response_filename_is_correct(): void
    {
        $this->seedMultiClassificationData();

        $response = $this->actingAs($this->admin)->get(route('manage.record.offline.export'));

        $disposition = $response->headers->get('content-disposition');
        $expectedPattern = sprintf('non-kp-owl-all-records-%s.xlsx', now()->format('Y-m-d'));
        $this->assertStringContainsString($expectedPattern, $disposition);
    }

    // =========================================================================
    // 3. EXPORT SCOPE: MULTI-CLASSIFICATION & UNRESTRICTED
    // =========================================================================

    public function test_export_contains_records_from_multiple_classifications(): void
    {
        $data = $this->seedMultiClassificationData();

        $export = new OfflineTrainingExport(null, new Request());
        $results = $export->query()->get();

        $employeeIds = $results->pluck('employee_id')->all();
        $this->assertContains('EMPGLOBAL01', $employeeIds);
        $this->assertContains('EMPGLOBAL02', $employeeIds);
        $this->assertContains('EMPGLOBAL03', $employeeIds);

        $classifications = $results->pluck('main_classification')->unique()->values()->all();
        $this->assertContains('Basic & Common Duties', $classifications);
        $this->assertContains('Leadership', $classifications);
        $this->assertContains('Technology', $classifications);
    }

    public function test_export_is_not_restricted_to_one_classification(): void
    {
        $this->seedMultiClassificationData();

        $export = new OfflineTrainingExport(null, new Request());
        $results = $export->query()->get();

        // Distinct classifications in result should include all 3 seeded classifications
        $classes = $results->pluck('main_classification')->unique()->values()->all();
        $this->assertContains('Basic & Common Duties', $classes);
        $this->assertContains('Leadership', $classes);
        $this->assertContains('Technology', $classes);
        $this->assertGreaterThanOrEqual(3, count($classes));
    }

    // =========================================================================
    // 4. SCHEMA INTEGRITY & 44-COLUMN CANONICAL MAPPING
    // =========================================================================

    public function test_export_uses_canonical_44_column_hrdc_schema(): void
    {
        $export = new OfflineTrainingExport(null, new Request());

        $headings = $export->headings();
        $this->assertCount(44, $headings);
        $this->assertEquals(OfflineTrainingExcelSchema::headers(), $headings);
    }

    public function test_column_order_matches_offline_training_excel_schema(): void
    {
        $export = new OfflineTrainingExport(null, new Request());

        $headings = $export->headings();
        $expected = OfflineTrainingExcelSchema::headers();

        foreach ($expected as $idx => $headerName) {
            $this->assertEquals($headerName, $headings[$idx], "Header at index {$idx} mismatch");
        }
    }

    public function test_training_and_participant_values_map_correctly(): void
    {
        $data = $this->seedMultiClassificationData();

        $export = new OfflineTrainingExport(null, new Request());
        $query = $export->query();
        $rows = $query->get();

        // 1. Verify Andi Wijaya (Basic & Common Duties, Present, Finished)
        $andi = $rows->firstWhere('employee_id', 'EMPGLOBAL01');
        $this->assertNotNull($andi);
        $mappedAndi = $export->map($andi);

        $this->assertCount(44, $mappedAndi);
        $this->assertEquals('EMPGLOBAL01', $mappedAndi[1]);                   // 2. ID
        $this->assertEquals('ANDI WIJAYA', $mappedAndi[2]);                   // 3. Name
        $this->assertEquals('3A', $mappedAndi[3]);                            // 4. Grade
        $this->assertEquals('Staff Officer', $mappedAndi[4]);                 // 5. Job Position
        $this->assertEquals('General Affairs', $mappedAndi[5]);               // 6. Team
        $this->assertEquals('HR & GA', $mappedAndi[6]);                       // 7. Department
        $this->assertEquals('PT Krakatau Posco', $mappedAndi[7]);             // 8. Company
        $this->assertEquals('Corporate Core Values & Ethics', $mappedAndi[8]);// 9. Course Name
        $this->assertEquals('Integrity in Daily Operations', $mappedAndi[9]); // 10. Sub Course
        $this->assertEquals('In-House', $mappedAndi[10]);                     // 11. Type
        $this->assertEquals('Core Values', $mappedAndi[11]);                  // 12. Category
        $this->assertEquals('All', $mappedAndi[12]);                          // 13. Level
        $this->assertEquals('Batch 1', $mappedAndi[13]);                      // 14. Batch
        $this->assertEquals('Basic & Common Duties', $mappedAndi[14]);        // 15. Main Classification
        $this->assertEquals('Ethics', $mappedAndi[15]);                       // 16. Sub Classification
        $this->assertEquals('Yes', $mappedAndi[16]);                          // 17. Corporate KPI
        $this->assertEquals('Offline', $mappedAndi[18]);                      // 19. Delivery Method
        $this->assertEquals('Internal', $mappedAndi[20]);                     // 21. Provider
        $this->assertEquals('04:00:00', $mappedAndi[22]);                     // 23. Duration
        $this->assertEquals(4.0, $mappedAndi[23]);                            // 24. Learning Hours
        $this->assertEquals(100.0, $mappedAndi[24]);                          // 25. Progress
        $this->assertEquals('2026-01-10', $mappedAndi[25]);                   // 26. Start Date
        $this->assertEquals('January', $mappedAndi[27]);                      // 28. Month
        $this->assertEquals('Auditorium Hall A', $mappedAndi[29]);            // 30. Venue
        $this->assertEquals('HRDC Internal Team', $mappedAndi[30]);           // 31. Instructor
        $this->assertEquals(88.0, $mappedAndi[31]);                           // 32. Assessment Score
        $this->assertEquals('CERT-BCD-01', $mappedAndi[32]);                  // 33. Certificate
        $this->assertEquals('Yes', $mappedAndi[33]);                          // 34. Attendance
        $this->assertEquals('No', $mappedAndi[35]);                           // 36. Bond Status
        $this->assertEquals('4.8 Very Good', $mappedAndi[38]);                // 39. L1 Program
        $this->assertEquals(88.0, $mappedAndi[39]);                           // 40. L2 Numerical
        $this->assertEquals('Passed', $mappedAndi[40]);                       // 41. L2 Verbal
        $this->assertEquals(85.0, $mappedAndi[41]);                           // 42. L3 Numerical
        $this->assertEquals('Applied', $mappedAndi[42]);                      // 43. L3 Verbal
        $this->assertEquals('PIC GA', $mappedAndi[43]);                       // 44. PIC

        // 2. Verify Bambang Hermanto (Bond Yes, Amount 15,000,000)
        $bambang = $rows->firstWhere('employee_id', 'EMPGLOBAL02');
        $this->assertNotNull($bambang);
        $mappedBambang = $export->map($bambang);

        $this->assertEquals('Yes', $mappedBambang[35]);                       // 36. Bond Status
        $this->assertEquals('2028-02-16', $mappedBambang[36]);                // 37. Bond End Date
        $this->assertEquals(15000000.0, $mappedBambang[37]);                  // 38. Bond Amount
        $this->assertEquals('Dale Carnegie Indo', $mappedBambang[28]);        // 29. Provider Name

        // 3. Verify Citra Dewi (Absent with Reason, Progress 60%)
        $citra = $rows->firstWhere('employee_id', 'EMPGLOBAL03');
        $this->assertNotNull($citra);
        $mappedCitra = $export->map($citra);

        $this->assertEquals(60.0, $mappedCitra[24]);                          // 25. Progress
        $this->assertEquals('No', $mappedCitra[33]);                          // 34. Attendance
        $this->assertEquals('Plant emergency shift coverage', $mappedCitra[34]); // 35. Reason
    }

    // =========================================================================
    // 5. SOFT-DELETE EXCLUSION
    // =========================================================================

    public function test_soft_deleted_participant_records_are_excluded(): void
    {
        $data = $this->seedMultiClassificationData();

        // Soft delete Citra Dewi
        $data['p3']->delete();

        $export = new OfflineTrainingExport(null, new Request());
        $results = $export->query()->get();

        $employeeIds = $results->pluck('employee_id')->all();
        $this->assertContains('EMPGLOBAL01', $employeeIds);
        $this->assertContains('EMPGLOBAL02', $employeeIds);
        $this->assertNotContains('EMPGLOBAL03', $employeeIds);
    }

    public function test_soft_deleted_training_records_are_excluded(): void
    {
        $data = $this->seedMultiClassificationData();

        // Soft delete Leadership training
        $data['t2']->delete();

        $export = new OfflineTrainingExport(null, new Request());
        $results = $export->query()->get();

        $employeeIds = $results->pluck('employee_id')->all();
        $this->assertContains('EMPGLOBAL01', $employeeIds);
        $this->assertNotContains('EMPGLOBAL02', $employeeIds); // Sibling participant under deleted training
        $this->assertContains('EMPGLOBAL03', $employeeIds);
    }

    // =========================================================================
    // 6. CLASSIFICATION EXPORT BACKWARD COMPATIBILITY
    // =========================================================================

    public function test_classification_specific_export_remains_functional(): void
    {
        $this->seedMultiClassificationData();

        // Scoped to 'leadership'
        $classExport = new OfflineClassificationExport('leadership', new Request());
        $results = $classExport->query()->get();

        $this->assertGreaterThanOrEqual(1, $results->count());
        $this->assertTrue($results->contains('employee_id', 'EMPGLOBAL02'));
        // Every single row must belong to Leadership
        foreach ($results as $row) {
            $this->assertEquals('Leadership', $row->main_classification);
        }
        $this->assertStringContainsString('non-kp-owl-leadership-', $classExport->getFileName());
    }

    // =========================================================================
    // 7. EMPTY DATA INTEGRITY
    // =========================================================================

    public function test_empty_data_handled_gracefully(): void
    {
        // Empty request on export
        $export = new OfflineTrainingExport(null, new Request());
        $this->assertNotNull($export->headings());
        $this->assertCount(44, $export->headings());
        $this->assertEquals(sprintf('non-kp-owl-all-records-%s.xlsx', now()->format('Y-m-d')), $export->getFileName());
    }
}
