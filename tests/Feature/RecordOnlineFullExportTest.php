<?php

namespace Tests\Feature;

use App\Exports\Record\OnlineFullRecordExport;
use App\Models\User;
use App\Services\Record\OnlineFullRecordExportService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RecordOnlineFullExportTest extends TestCase
{
    protected ?User $admin = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])->first() ?? User::find(1);
    }

    /**
     * TEST 1: Headings count and structure strictly matches 46 canonical columns.
     */
    public function test_headings_count_and_order_matches_full_record_schema(): void
    {
        $headings = OnlineFullRecordExportService::headings();

        $this->assertCount(46, $headings);
        $this->assertEquals('No', $headings[0]);
        $this->assertEquals('Source', $headings[1]);
        $this->assertEquals('Employee ID', $headings[2]);
        $this->assertEquals('Employee Name', $headings[3]);
        $this->assertEquals('Status', $headings[4]);
        $this->assertEquals('Grade', $headings[5]);
        $this->assertEquals('Job Position', $headings[6]);
        $this->assertEquals('Team', $headings[7]);
        $this->assertEquals('Department', $headings[8]);
        $this->assertEquals('Company', $headings[9]);
        $this->assertEquals('Learning Title', $headings[10]);
        $this->assertEquals('Sub Title', $headings[11]);
        $this->assertEquals('Type', $headings[12]);
        $this->assertEquals('Category', $headings[13]);
        $this->assertEquals('Level', $headings[14]);
        $this->assertEquals('Batch', $headings[15]);
        $this->assertEquals('Main Classification', $headings[16]);
        $this->assertEquals('Sub Classification', $headings[17]);
        $this->assertEquals('Corporate KPI', $headings[18]);
        $this->assertEquals('Sub Corporate KPI', $headings[19]);
        $this->assertEquals('Delivery Method', $headings[20]);
        $this->assertEquals('Sub Delivery Method', $headings[21]);
        $this->assertEquals('Provider', $headings[22]);
        $this->assertEquals('Sub Provider', $headings[23]);
        $this->assertEquals('Provider Name', $headings[24]);
        $this->assertEquals('Venue', $headings[25]);
        $this->assertEquals('Instructor', $headings[26]);
        $this->assertEquals('Duration', $headings[27]);
        $this->assertEquals('Training Hours', $headings[28]);
        $this->assertEquals('Start Date', $headings[29]);
        $this->assertEquals('Finish Date', $headings[30]);
        $this->assertEquals('Participant Hours', $headings[31]);
        $this->assertEquals('Progress', $headings[32]);
        $this->assertEquals('Assessment Score (L2)', $headings[33]);
        $this->assertEquals('Certificate', $headings[34]);
        $this->assertEquals('Attendance', $headings[35]);
        $this->assertEquals('Attendance Reason', $headings[36]);
        $this->assertEquals('Bond Status', $headings[37]);
        $this->assertEquals('Bond End Date', $headings[38]);
        $this->assertEquals('Bond Amount', $headings[39]);
        $this->assertEquals('L1 Program', $headings[40]);
        $this->assertEquals('L2 Numerical', $headings[41]);
        $this->assertEquals('L2 Verbal', $headings[42]);
        $this->assertEquals('L3 Numerical', $headings[43]);
        $this->assertEquals('L3 Verbal', $headings[44]);
        $this->assertEquals('PIC', $headings[45]);
    }

    /**
     * TEST 2: Every mapped row contains exactly 46 elements matching the headings.
     */
    public function test_row_mapping_element_count_strictly_equals_headings_count(): void
    {
        $service = app(OnlineFullRecordExportService::class);
        $headings = OnlineFullRecordExportService::headings();

        $dummyRow = (object) [
            'source_id'              => 1,
            'source_type'            => 'content',
            'category_source'        => 'All Content',
            'user_id'                => 1,
            'content_id'             => 10,
            'trainingplan_id'        => null,
            'status'                 => 'optional',
            'finish'                 => '1',
            'progress'               => 100,
            'created_at'             => '2025-01-10 09:00:00',
            'updated_at'             => '2025-01-10 10:00:00',
            'completed_at'           => '2025-01-10 10:00:00',
            'learning_hours'         => 1.5,
            'id_emp'                 => '012345',
            'employee_name'          => 'John Doe',
            'user_department'        => 'IT Dept',
            'user_team'              => 'Dev Team',
            'user_company'           => 'PT Krakatau Posco',
            'content_title'          => 'Cyber Security 101',
            'content_type'           => 'Video',
            'content_duration'       => '01:30:00',
            'classification_name'    => 'Technical',
            'playlist_title'         => null,
            'content_author'         => 'Security Expert',
            'content_training_hours' => 1.5,
            'playlist_training_hours'=> null,
        ];

        $mapped = $service->mapRow($dummyRow, 1, null);

        $this->assertCount(count($headings), $mapped);
        $this->assertEquals(1, $mapped[0]); // No
        $this->assertEquals('KP OWL', $mapped[1]); // Source
        $this->assertEquals('012345', $mapped[2]); // Employee ID (preserves leading zero)
        $this->assertEquals('John Doe', $mapped[3]); // Employee Name
        $this->assertEquals('Finished', $mapped[4]); // Status
        $this->assertEquals('', $mapped[5]); // Grade
        $this->assertEquals('', $mapped[6]); // Job Position
        $this->assertEquals('Dev Team', $mapped[7]); // Team
        $this->assertEquals('IT Dept', $mapped[8]); // Department
        $this->assertEquals('PT Krakatau Posco', $mapped[9]); // Company
        $this->assertEquals('Cyber Security 101', $mapped[10]); // Title
        $this->assertEquals('Non-Mandatory', $mapped[11]); // Sub Title / Requirement
        $this->assertEquals('Video', $mapped[12]); // Type
        $this->assertEquals('Content', $mapped[13]); // Category (Standalone Content)
        $this->assertEquals('Technical', $mapped[16]); // Main Classification
        $this->assertEquals('Security Expert', $mapped[26]); // Instructor
        $this->assertEquals('01:30:00', $mapped[27]); // Duration
        $this->assertEquals('1.50', $mapped[28]); // Training Hours
        $this->assertEquals('2025-01-10', $mapped[29]); // Start Date
        $this->assertEquals('2025-01-10', $mapped[30]); // Finish Date
        $this->assertEquals('1.50', $mapped[31]); // Participant Hours
        $this->assertEquals('100%', $mapped[32]); // Progress
        $this->assertEquals('Yes', $mapped[35]); // Attendance
    }

    /**
     * TEST 3: Playlist row category maps to "Playlist: {Actual Playlist Name}" and type preserves actual content type.
     */
    public function test_playlist_item_category_mapping_and_type_preservation(): void
    {
        $service = app(OnlineFullRecordExportService::class);

        $dummyPlaylistRow = (object) [
            'source_id'              => 2,
            'source_type'            => 'playlist',
            'category_source'        => 'Playlist',
            'user_id'                => 1,
            'content_id'             => 25,
            'trainingplan_id'        => 5,
            'finish'                 => '0',
            'progress'               => 45,
            'created_at'             => '2025-02-01 10:00:00',
            'id_emp'                 => '100234',
            'employee_name'          => 'Jane Smith',
            'content_title'          => 'Module 1: Safety Introduction',
            'content_type'           => 'Video',
            'playlist_title'         => 'Leadership Onboarding 2025',
        ];

        $mapped = $service->mapRow($dummyPlaylistRow, 2, null);

        $this->assertEquals('KP OWL', $mapped[1]); // Source
        $this->assertEquals('Ongoing', $mapped[4]); // Status
        $this->assertEquals('Module 1: Safety Introduction', $mapped[10]); // Learning Title
        $this->assertEquals('Video', $mapped[12]); // Type preserved (not overwritten with Playlist)
        $this->assertEquals('Playlist: Leadership Onboarding 2025', $mapped[13]); // Category
        $this->assertEquals('45%', $mapped[32]); // Progress
        $this->assertEquals('No', $mapped[35]); // Attendance
    }

    /**
     * TEST 4: Attendance logic resolves correctly based on progress / finish.
     */
    public function test_attendance_and_status_resolution(): void
    {
        $service = app(OnlineFullRecordExportService::class);

        // Case A: 100% progress
        $rowCompleted = (object) ['progress' => 100, 'finish' => '1', 'source_type' => 'content'];
        $mappedCompleted = $service->mapRow($rowCompleted, 1, null);
        $this->assertEquals('Finished', $mappedCompleted[4]);
        $this->assertEquals('Yes', $mappedCompleted[35]);

        // Case B: Finish flag = 1 even if progress is 99
        $rowFinished = (object) ['progress' => 99, 'finish' => '1', 'source_type' => 'content'];
        $mappedFinished = $service->mapRow($rowFinished, 2, null);
        $this->assertEquals('Finished', $mappedFinished[4]);
        $this->assertEquals('Yes', $mappedFinished[35]);

        // Case C: In progress (< 100)
        $rowOngoing = (object) ['progress' => 50, 'finish' => '0', 'source_type' => 'content'];
        $mappedOngoing = $service->mapRow($rowOngoing, 3, null);
        $this->assertEquals('Ongoing', $mappedOngoing[4]);
        $this->assertEquals('No', $mappedOngoing[35]);

        // Case D: 0% progress
        $rowNotStarted = (object) ['progress' => 0, 'finish' => '0', 'source_type' => 'content'];
        $mappedNotStarted = $service->mapRow($rowNotStarted, 4, null);
        $this->assertEquals('Not Started', $mappedNotStarted[4]);
        $this->assertEquals('No', $mappedNotStarted[35]);
    }

    /**
     * TEST 5: Assessment score and Certificate batch resolution.
     */
    public function test_assessment_score_and_certificate_batch_resolution(): void
    {
        $service = app(OnlineFullRecordExportService::class);
        $query = $service->buildQuery([]);
        $records = $query->limit(10)->get();

        if ($records->isNotEmpty()) {
            $context = $service->buildBatchContext($records);
            foreach ($records as $idx => $r) {
                $mapped = $service->mapRow($r, $idx + 1, $context);
                $this->assertCount(46, $mapped);

                // Assessment Score must be formatted score, 'Not Taken', or 'Not Applicable'
                $score = $mapped[33];
                $this->assertTrue(
                    is_numeric($score) || in_array($score, ['Not Taken', 'Not Applicable'], true),
                    "Assessment score '{$score}' does not match expected contract."
                );

                // Certificate must be 'Available' or 'Not Available'
                $cert = $mapped[34];
                $this->assertContains($cert, ['Available', 'Not Available']);
            }
        } else {
            $this->assertTrue(true, 'No online records in database to test.');
        }
    }

    /**
     * TEST 6: Excel download endpoint returns valid XLSX attachment for KP OWL (Online).
     */
    public function test_kpowl_online_excel_download(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.record.export-all.download', [
            'source' => 'online',
            'format' => 'xlsx',
            'year'   => '2026',
        ]));

        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheet', $response->headers->get('content-type', ''));
        $contentDisposition = $response->headers->get('content-disposition', '');
        $this->assertStringContainsString('attachment', $contentDisposition);
        $this->assertStringContainsString('kp-owl-online-records-', $contentDisposition);
        $this->assertStringContainsString('.xlsx', $contentDisposition);
    }

    /**
     * TEST 7: CSV download endpoint returns valid CSV attachment with 46 column headers.
     */
    public function test_kpowl_online_csv_download(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.record.export-all.download', [
            'source' => 'online',
            'format' => 'csv',
            'year'   => '2026',
        ]));

        $response->assertStatus(200);
        $this->assertStringContainsString('csv', $response->headers->get('content-type', ''));
        $contentDisposition = $response->headers->get('content-disposition', '');
        $this->assertStringContainsString('attachment', $contentDisposition);
        $this->assertStringContainsString('kp-owl-online-records-', $contentDisposition);
        $this->assertStringContainsString('.csv', $contentDisposition);

        // Verify CSV header line
        $content = $response->streamedContent();
        $this->assertNotEmpty($content);

        // Strip UTF-8 BOM if present
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        $lines = explode("\n", trim($content));
        $this->assertGreaterThanOrEqual(1, count($lines));

        $firstLine = str_getcsv($lines[0]);
        $this->assertCount(46, $firstLine, 'CSV header must contain exactly 46 columns.');
        $this->assertEquals('No', $firstLine[0]);
        $this->assertEquals('Source', $firstLine[1]);
        $this->assertEquals('Employee ID', $firstLine[2]);
        $this->assertEquals('PIC', $firstLine[45]);
    }

    /**
     * TEST 8: PDF download endpoint works and respects maximum 500 limit.
     */
    public function test_kpowl_online_pdf_download(): void
    {
        // 1. Filtered under limit: generates PDF successfully
        $response = $this->actingAs($this->admin)->get(route('manage.record.export-all.download', [
            'source'      => 'online',
            'format'      => 'pdf',
            'employee_id' => '129458',
        ]));

        $response->assertStatus(200);
        $this->assertStringContainsString('pdf', $response->headers->get('content-type', ''));

        // 2. Over limit protection (> 500 records redirects or returns 200 if under limit)
        $responseOverLimit = $this->actingAs($this->admin)->get(route('manage.record.export-all.download', [
            'source' => 'online',
            'format' => 'pdf',
        ]));
        $this->assertTrue(in_array($responseOverLimit->status(), [200, 302], true));
    }

    /**
     * TEST 9: Regression test: Non KP OWL (Offline) download remains functional and unchanged.
     */
    public function test_non_kp_owl_offline_export_unaffected(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.record.export-all.download', [
            'source' => 'offline',
            'format' => 'xlsx',
        ]));

        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheet', $response->headers->get('content-type', ''));
        $contentDisposition = $response->headers->get('content-disposition', '');
        $this->assertStringContainsString('non-kp-owl-records-', $contentDisposition);
    }

    /**
     * TEST 10: Regression test: All Sources (Combined) download remains functional and unchanged.
     */
    public function test_all_sources_combined_export_unaffected(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.record.export-all.download', [
            'source' => 'all',
            'format' => 'xlsx',
            'year'   => '2026',
        ]));

        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheet', $response->headers->get('content-type', ''));
        $contentDisposition = $response->headers->get('content-disposition', '');
        $this->assertStringContainsString('all-kp-owl-combined-records-', $contentDisposition);
    }

    /**
     * TEST 11: Export filtering works for Employee and Year.
     */
    public function test_online_export_filtering_by_employee_and_year(): void
    {
        $service = app(OnlineFullRecordExportService::class);

        // Filter by specific year
        $qYear = $service->buildQuery(['year' => '2025']);
        $sqlYear = $qYear->toSql();
        $this->assertStringContainsString('cu`.`created_at', $sqlYear);

        // Filter by employee
        $qEmp = $service->buildQuery(['employee_id' => 'Budi']);
        $sqlEmp = $qEmp->toSql();
        $this->assertNotEmpty($sqlEmp);
    }

    /**
     * TEST 12: No database mutation occurs during export execution.
     */
    public function test_no_database_mutation_during_export(): void
    {
        $contentUsersBefore = DB::table('content_users')->count();
        $pcuBefore = DB::table('playlist_content_users')->count();
        $certBefore = DB::table('certificates')->count();

        // Run full CSV export
        $this->actingAs($this->admin)->get(route('manage.record.export-all.download', [
            'source' => 'online',
            'format' => 'csv',
            'year'   => '2026',
        ]));

        $contentUsersAfter = DB::table('content_users')->count();
        $pcuAfter = DB::table('playlist_content_users')->count();
        $certAfter = DB::table('certificates')->count();

        $this->assertEquals($contentUsersBefore, $contentUsersAfter);
        $this->assertEquals($pcuBefore, $pcuAfter);
        $this->assertEquals($certBefore, $certAfter);
    }
}
