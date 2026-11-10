<?php

namespace Tests\Feature;

use App\Exports\OfflineClassificationCsvExport;
use App\Exports\OfflineClassificationExport;
use App\Models\User;
use App\Services\Record\OfflineTrainingExcelSchema;
use App\Services\Record\RecordOfflineQueryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class OfflineClassificationDownloadMultiFormatTest extends TestCase
{
    protected User $admin;
    protected ?User $regularUser;
    protected array $createdTrainingIds = [];
    protected string $testClassification = 'Download Test Duties';
    protected string $testSlug = 'download-test-duties';

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
     * Helper to read response content whether BinaryFileResponse or regular Response.
     */
    protected function getResponseContent($response): string
    {
        $baseResponse = $response->baseResponse;
        if ($baseResponse instanceof BinaryFileResponse) {
            return file_get_contents($baseResponse->getFile()->getPathname());
        }

        return $response->getContent();
    }

    /**
     * Helper to seed controlled test records under test classification.
     */
    protected function seedDownloadTestData(): array
    {
        // Training 1: In-House, 2026, Duration 7200s (gt60)
        $t1Id = DB::table('offline_trainings')->insertGetId([
            'course_name'            => 'Corporate Ethics & Integrity',
            'sub_course_name'        => 'Code of Conduct Compliance',
            'type'                   => 'In-House',
            'category'               => 'Compliance Track',
            'level'                  => 'Fundamental',
            'batch'                  => 'Batch 101',
            'main_classification'    => $this->testClassification,
            'sub_classification'     => 'Ethics & Integrity',
            'counted_corporate_kpi'  => true,
            'duration_seconds'       => 7200,
            'learning_hours'         => 8.0,
            'start_date'             => '2026-02-10',
            'finish_date'            => '2026-02-12',
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);
        $this->createdTrainingIds[] = $t1Id;

        // Participant 1: Finished, Present, Score 96.0, Hours 8.0, Potential formula injection in name
        $p1Id = DB::table('offline_training_users')->insertGetId([
            'offline_training_id' => $t1Id,
            'employee_id'         => 'NIP1001',
            'name'                => '=CMD() Alert Participant',
            'job_position'        => 'Safety Engineer',
            'department'          => 'HSE Dept',
            'company'             => 'PT Krakatau Posco',
            'learning_hours'      => 8.0,
            'progress'            => 100.0,
            'attendance'          => true,
            'assessment_score'    => 96.0,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // Participant 2: Ongoing (50%), Present, Score 78.0, Hours 4.0
        $p2Id = DB::table('offline_training_users')->insertGetId([
            'offline_training_id' => $t1Id,
            'employee_id'         => 'NIP1002',
            'name'                => 'Budi Pratama',
            'job_position'        => 'Quality Auditor',
            'department'          => 'Quality Control',
            'company'             => 'PT Krakatau Posco',
            'learning_hours'      => 4.0,
            'progress'            => 50.0,
            'attendance'          => true,
            'assessment_score'    => 78.0,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // Training 2: Public, 2025, Duration 900s (10-30)
        $t2Id = DB::table('offline_trainings')->insertGetId([
            'course_name'            => 'General Safety Orientation',
            'sub_course_name'        => 'Emergency Response Basics',
            'type'                   => 'Public',
            'category'               => 'Safety Track',
            'level'                  => 'Basic',
            'batch'                  => 'Batch 102',
            'main_classification'    => $this->testClassification,
            'sub_classification'     => 'Workplace Safety',
            'counted_corporate_kpi'  => false,
            'duration_seconds'       => 900,
            'learning_hours'         => 2.0,
            'start_date'             => '2025-08-01',
            'finish_date'            => '2025-08-01',
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);
        $this->createdTrainingIds[] = $t2Id;

        // Participant 3: Not Started (0%), Absent
        $p3Id = DB::table('offline_training_users')->insertGetId([
            'offline_training_id' => $t2Id,
            'employee_id'         => 'NIP1003',
            'name'                => 'Charlie Susanto',
            'job_position'        => 'Maintenance Specialist',
            'department'          => 'Rolling Mill',
            'company'             => 'PT Krakatau Posco',
            'learning_hours'      => 0.0,
            'progress'            => 0.0,
            'attendance'          => false,
            'assessment_score'    => 0.0,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // Training 3: DIFFERENT CLASSIFICATION (Download Test Leadership) - must NEVER leak
        $t3Id = DB::table('offline_trainings')->insertGetId([
            'course_name'            => 'Executive Leadership Bootcamp',
            'sub_course_name'        => 'Strategic Decision',
            'type'                   => 'In-House',
            'category'               => 'Executive Track',
            'level'                  => 'Master',
            'batch'                  => 'Batch 999',
            'main_classification'    => 'Download Test Leadership',
            'sub_classification'     => 'Executive Leadership',
            'counted_corporate_kpi'  => true,
            'duration_seconds'       => 14400,
            'learning_hours'         => 16.0,
            'start_date'             => '2026-05-10',
            'finish_date'            => '2026-05-12',
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);
        $this->createdTrainingIds[] = $t3Id;

        // Participant 4 under Leadership
        $p4Id = DB::table('offline_training_users')->insertGetId([
            'offline_training_id' => $t3Id,
            'employee_id'         => 'NIP9999',
            'name'                => 'Executive Leader One',
            'job_position'        => 'General Manager',
            'department'          => 'Executive Board',
            'company'             => 'PT Krakatau Posco',
            'learning_hours'      => 16.0,
            'progress'            => 100.0,
            'attendance'          => true,
            'assessment_score'    => 99.0,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        return [
            't1Id' => $t1Id,
            't2Id' => $t2Id,
            't3Id' => $t3Id,
            'p1Id' => $p1Id,
            'p2Id' => $p2Id,
            'p3Id' => $p3Id,
            'p4Id' => $p4Id,
        ];
    }

    public function test_download_excel_returns_valid_xlsx_and_proper_disposition_header(): void
    {
        $this->seedDownloadTestData();

        $response = $this->actingAs($this->admin)->get("/manage/record/offline/classification/{$this->testSlug}/download");
        $response->assertStatus(200);

        $disposition = (string) $response->headers->get('content-disposition');
        $this->assertStringContainsString('attachment', $disposition);
        $this->assertStringContainsString("non-kp-owl-{$this->testSlug}-", $disposition);
        $this->assertStringContainsString('.xlsx', $disposition);
    }

    public function test_download_csv_returns_valid_csv_with_utf8_bom_and_headers(): void
    {
        $this->seedDownloadTestData();

        $response = $this->actingAs($this->admin)->get("/manage/record/offline/classification/{$this->testSlug}/download/csv");
        $response->assertStatus(200);

        $disposition = (string) $response->headers->get('content-disposition');
        $this->assertStringContainsString('attachment', $disposition);
        $this->assertStringContainsString("offline-record-{$this->testSlug}-", $disposition);
        $this->assertStringContainsString('.csv', $disposition);

        $content = $this->getResponseContent($response);
        // UTF-8 BOM check (EF BB BF)
        $this->assertStringStartsWith(chr(0xEF) . chr(0xBB) . chr(0xBF), $content);

        // Check canonical header presence
        $this->assertStringContainsString('Job Position', $content);
        $this->assertStringContainsString('Main Classification', $content);
        $this->assertStringContainsString('Duration', $content);
        $this->assertStringContainsString('Learning (Hours)', $content);
    }

    public function test_download_csv_sanitizes_formula_injection(): void
    {
        $this->seedDownloadTestData();

        $response = $this->actingAs($this->admin)->get("/manage/record/offline/classification/{$this->testSlug}/download?format=csv");
        $response->assertStatus(200);
        $content = $this->getResponseContent($response);

        // The malicious participant name '=CMD() Alert Participant' must be sanitized by prefixing with '
        $this->assertStringContainsString("'=CMD() Alert Participant", $content);
        // And never exported as raw unquoted formula
        $this->assertStringNotContainsString(',=CMD() Alert Participant', $content);
    }

    public function test_download_pdf_returns_valid_pdf_in_a3_landscape(): void
    {
        $this->seedDownloadTestData();

        $response = $this->actingAs($this->admin)->get("/manage/record/offline/classification/{$this->testSlug}/download/pdf");
        $response->assertStatus(200);

        $disposition = (string) $response->headers->get('content-disposition');
        $this->assertStringContainsString('attachment', $disposition);
        $this->assertStringContainsString("offline-record-{$this->testSlug}-", $disposition);
        $this->assertStringContainsString('.pdf', $disposition);

        $content = $this->getResponseContent($response);
        $this->assertStringStartsWith('%PDF-', $content);
    }

    public function test_download_without_filter_exports_all_data_for_classification(): void
    {
        $this->seedDownloadTestData();

        $export = new OfflineClassificationCsvExport($this->testSlug);
        $records = $export->query()->get();

        // Must contain all 3 participants of this classification
        $this->assertCount(3, $records);
        $employeeIds = $records->pluck('employee_id')->all();
        $this->assertContains('NIP1001', $employeeIds);
        $this->assertContains('NIP1002', $employeeIds);
        $this->assertContains('NIP1003', $employeeIds);

        // Must NOT contain Leadership participant
        $this->assertNotContains('NIP9999', $employeeIds);
    }

    public function test_download_with_search_filters_only_matching_data(): void
    {
        $this->seedDownloadTestData();

        // Search by employee NIP
        $reqEmp = new Request(['employee_id' => 'NIP1002']);
        $exportEmp = new OfflineClassificationCsvExport($this->testSlug, $reqEmp);
        $resEmp = $exportEmp->query()->get();
        $this->assertCount(1, $resEmp);
        $this->assertEquals('NIP1002', $resEmp[0]->employee_id);
        $this->assertEquals('Budi Pratama', $resEmp[0]->participant_name);

        // Search by course name
        $reqCourse = new Request(['course_name' => 'General Safety']);
        $exportCourse = new OfflineClassificationCsvExport($this->testSlug, $reqCourse);
        $resCourse = $exportCourse->query()->get();
        $this->assertCount(1, $resCourse);
        $this->assertEquals('NIP1003', $resCourse[0]->employee_id);
    }

    public function test_download_with_type_filter(): void
    {
        $this->seedDownloadTestData();

        $reqType = new Request(['type' => 'Public']);
        $export = new OfflineClassificationCsvExport($this->testSlug, $reqType);
        $records = $export->query()->get();

        $this->assertCount(1, $records);
        $this->assertEquals('Public', $records[0]->type);
        $this->assertEquals('NIP1003', $records[0]->employee_id);
    }

    public function test_download_with_status_filter(): void
    {
        $this->seedDownloadTestData();

        // Finished
        $reqFin = new Request(['status' => 'finished']);
        $exportFin = new OfflineClassificationCsvExport($this->testSlug, $reqFin);
        $recordsFin = $exportFin->query()->get();
        $this->assertCount(1, $recordsFin);
        $this->assertEquals('NIP1001', $recordsFin[0]->employee_id);

        // Ongoing
        $reqOng = new Request(['status' => 'ongoing']);
        $exportOng = new OfflineClassificationCsvExport($this->testSlug, $reqOng);
        $recordsOng = $exportOng->query()->get();
        $this->assertCount(1, $recordsOng);
        $this->assertEquals('NIP1002', $recordsOng[0]->employee_id);

        // Not Started
        $reqNs = new Request(['status' => 'not_started']);
        $exportNs = new OfflineClassificationCsvExport($this->testSlug, $reqNs);
        $recordsNs = $exportNs->query()->get();
        $this->assertCount(1, $recordsNs);
        $this->assertEquals('NIP1003', $recordsNs[0]->employee_id);
    }

    public function test_download_with_year_filter(): void
    {
        $this->seedDownloadTestData();

        $req2026 = new Request(['year' => '2026']);
        $export2026 = new OfflineClassificationCsvExport($this->testSlug, $req2026);
        $records2026 = $export2026->query()->get();

        $this->assertCount(2, $records2026);
        $this->assertEquals(['NIP1001', 'NIP1002'], $records2026->pluck('employee_id')->all());

        $req2025 = new Request(['year' => '2025']);
        $export2025 = new OfflineClassificationCsvExport($this->testSlug, $req2025);
        $records2025 = $export2025->query()->get();

        $this->assertCount(1, $records2025);
        $this->assertEquals('NIP1003', $records2025[0]->employee_id);
    }

    public function test_download_with_more_filters(): void
    {
        $this->seedDownloadTestData();

        // Score filter: min 90
        $reqScore = new Request(['score_min' => '90']);
        $exportScore = new OfflineClassificationCsvExport($this->testSlug, $reqScore);
        $recordsScore = $exportScore->query()->get();
        $this->assertCount(1, $recordsScore);
        $this->assertEquals('NIP1001', $recordsScore[0]->employee_id);

        // Attendance: absent
        $reqAtt = new Request(['attendance' => 'absent']);
        $exportAtt = new OfflineClassificationCsvExport($this->testSlug, $reqAtt);
        $recordsAtt = $exportAtt->query()->get();
        $this->assertCount(1, $recordsAtt);
        $this->assertEquals('NIP1003', $recordsAtt[0]->employee_id);

        // Duration: gt60 (> 1 hour)
        $reqDur = new Request(['duration' => 'gt60']);
        $exportDur = new OfflineClassificationCsvExport($this->testSlug, $reqDur);
        $recordsDur = $exportDur->query()->get();
        $this->assertCount(2, $recordsDur);
    }

    public function test_download_with_combined_filters(): void
    {
        $this->seedDownloadTestData();

        // Combined: Type: In-House + Year: 2026 + Status: Ongoing
        $req = new Request([
            'type'   => 'In-House',
            'year'   => '2026',
            'status' => 'ongoing',
        ]);
        $export = new OfflineClassificationCsvExport($this->testSlug, $req);
        $records = $export->query()->get();

        $this->assertCount(1, $records);
        $this->assertEquals('NIP1002', $records[0]->employee_id);
        $this->assertEquals('Budi Pratama', $records[0]->participant_name);
    }

    public function test_download_when_no_matching_records_returns_valid_files(): void
    {
        $this->seedDownloadTestData();

        // CSV with non-matching filter
        $responseCsv = $this->actingAs($this->admin)->get("/manage/record/offline/classification/{$this->testSlug}/download/csv?year=1990");
        $responseCsv->assertStatus(200);
        $csvContent = $this->getResponseContent($responseCsv);
        $this->assertStringContainsString('Job Position', $csvContent);
        $this->assertStringNotContainsString('NIP1001', $csvContent);

        // PDF with non-matching filter
        $responsePdf = $this->actingAs($this->admin)->get("/manage/record/offline/classification/{$this->testSlug}/download/pdf?year=1990");
        $responsePdf->assertStatus(200);
        $this->assertStringStartsWith('%PDF-', $this->getResponseContent($responsePdf));

        // Excel with non-matching filter
        $responseExcel = $this->actingAs($this->admin)->get("/manage/record/offline/classification/{$this->testSlug}/download?year=1990");
        $responseExcel->assertStatus(200);
        $disposition = (string) $responseExcel->headers->get('content-disposition');
        $this->assertStringContainsString('.xlsx', $disposition);
    }

    public function test_download_routes_parity_between_download_and_export_endpoints(): void
    {
        $this->seedDownloadTestData();

        // 1. Excel via /download?format=xlsx and /export/excel
        $resXlsx1 = $this->actingAs($this->admin)->get("/manage/record/offline/classification/{$this->testSlug}/download?format=xlsx");
        $resXlsx2 = $this->actingAs($this->admin)->get("/manage/record/offline/classification/{$this->testSlug}/export/excel");
        $resXlsx1->assertStatus(200);
        $resXlsx2->assertStatus(200);
        $this->assertStringContainsString('.xlsx', (string) $resXlsx1->headers->get('content-disposition'));
        $this->assertStringContainsString('.xlsx', (string) $resXlsx2->headers->get('content-disposition'));

        // 2. CSV via /download/csv and /export/csv
        $resCsv1 = $this->actingAs($this->admin)->get("/manage/record/offline/classification/{$this->testSlug}/download/csv");
        $resCsv2 = $this->actingAs($this->admin)->get("/manage/record/offline/classification/{$this->testSlug}/export/csv");
        $resCsv1->assertStatus(200);
        $resCsv2->assertStatus(200);
        $this->assertStringContainsString('.csv', (string) $resCsv1->headers->get('content-disposition'));
        $this->assertStringContainsString('.csv', (string) $resCsv2->headers->get('content-disposition'));

        // 3. PDF via /download/pdf and /export/pdf
        $resPdf1 = $this->actingAs($this->admin)->get("/manage/record/offline/classification/{$this->testSlug}/download/pdf");
        $resPdf2 = $this->actingAs($this->admin)->get("/manage/record/offline/classification/{$this->testSlug}/export/pdf");
        $resPdf1->assertStatus(200);
        $resPdf2->assertStatus(200);
        $this->assertStringContainsString('.pdf', (string) $resPdf1->headers->get('content-disposition'));
        $this->assertStringContainsString('.pdf', (string) $resPdf2->headers->get('content-disposition'));
    }

    public function test_classification_isolation_guarantees_no_cross_leakage(): void
    {
        $this->seedDownloadTestData();

        // Export test classification
        $exportTest = new OfflineClassificationCsvExport($this->testSlug);
        $testRecords = $exportTest->query()->get();
        $this->assertCount(3, $testRecords);
        foreach ($testRecords as $r) {
            $this->assertEquals($this->testClassification, $r->main_classification);
        }

        // Export leadership (should only contain leadership records)
        $exportLead = new OfflineClassificationCsvExport('download-test-leadership');
        $leadRecords = $exportLead->query()->get();
        $this->assertCount(1, $leadRecords);
        $this->assertEquals('Download Test Leadership', $leadRecords[0]->main_classification);
        $this->assertEquals('NIP9999', $leadRecords[0]->employee_id);
    }

    public function test_target_page_basic_common_duties_renders_download_dropdown_and_options(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/record/offline/classification/basic-common-duties');
        $response->assertStatus(200);

        // Assert dropdown toggle exists
        $response->assertSee('id="btn-download-export"', false);
        $response->assertSee('data-bs-toggle="dropdown"', false);
        $response->assertSee('Download');

        // Assert dropdown options exist
        $response->assertSee('id="btn-export-classification-excel"', false);
        $response->assertSee('Excel (.xlsx)');
        $response->assertSee('id="btn-export-classification-csv"', false);
        $response->assertSee('CSV (.csv)');
        $response->assertSee('id="btn-export-classification-pdf"', false);
        $response->assertSee('PDF Landscape (.pdf)');

        // Assert icons
        $response->assertSee('ti-file-spreadsheet');
        $response->assertSee('ti-file-text');
        $response->assertSee('ti-file-type-pdf');
    }

    public function test_target_basic_common_duties_downloads_excel_csv_and_pdf(): void
    {
        // 1. Excel
        $resExcel = $this->actingAs($this->admin)->get('/manage/record/offline/classification/basic-common-duties/download');
        $resExcel->assertStatus(200);
        $this->assertStringContainsString('.xlsx', (string) $resExcel->headers->get('content-disposition'));
        $this->assertStringContainsString('non-kp-owl-basic-common-duties-', (string) $resExcel->headers->get('content-disposition'));

        // 2. CSV
        $resCsv = $this->actingAs($this->admin)->get('/manage/record/offline/classification/basic-common-duties/download/csv');
        $resCsv->assertStatus(200);
        $this->assertStringContainsString('.csv', (string) $resCsv->headers->get('content-disposition'));
        $this->assertStringContainsString('offline-record-basic-common-duties-', (string) $resCsv->headers->get('content-disposition'));

        // 3. PDF
        $resPdf = $this->actingAs($this->admin)->get('/manage/record/offline/classification/basic-common-duties/download/pdf');
        $resPdf->assertStatus(200);
        $this->assertStringContainsString('.pdf', (string) $resPdf->headers->get('content-disposition'));
        $this->assertStringContainsString('offline-record-basic-common-duties-', (string) $resPdf->headers->get('content-disposition'));
    }

    public function test_target_basic_common_duties_respects_filter_combinations(): void
    {
        // Query with filters that match
        $queryFilters = [
            'type'   => 'In-House',
            'status' => 'all',
        ];

        // Service query count
        $expectedCount = RecordOfflineQueryService::buildClassificationQuery('basic-common-duties', $queryFilters)->count();

        // Datatable count
        $dtRes = $this->actingAs($this->admin)->getJson('/manage/record/offline/classification/basic-common-duties/datatable?type=In-House&status=all');
        $dtRes->assertStatus(200);
        $this->assertEquals($expectedCount, $dtRes->json('recordsFiltered'));

        // Export count
        $csvExport = new OfflineClassificationCsvExport('basic-common-duties', new Request($queryFilters));
        $this->assertEquals($expectedCount, $csvExport->query()->count());

        $excelExport = new OfflineClassificationExport('basic-common-duties', new Request($queryFilters));
        $this->assertEquals($expectedCount, $excelExport->query()->count());
    }

    public function test_unauthenticated_and_unauthorized_users_cannot_access_export(): void
    {
        // Guest user is redirected
        $guestRes = $this->get('/manage/record/offline/classification/basic-common-duties/download/csv');
        $guestRes->assertRedirect();
    }
}
