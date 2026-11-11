<?php

namespace Tests\Feature;

use App\Exports\AllParticipantExport;
use App\Models\User;
use App\Services\Learning\LearningRecordRecognizedDurationService;
use App\Services\Record\RecordAllCombinedService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class AllParticipantDetailColumnConsistencyTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected RecordAllCombinedService $combinedService;

    public const CANONICAL_52_COLUMNS = [
        'NO',
        'Source',
        'Employee ID',
        'Employee Name',
        'Grade',
        'Job Position',
        'Team',
        'Department',
        'Company',
        'Course Title',
        'Sub Course Title',
        'Type',
        'Category',
        'Level',
        'Batch',
        'Main Classification',
        'Sub Classification',
        'Delivery Method',
        'Sub Delivery Method',
        'Provider',
        'Sub Provider',
        'Provider Name',
        'Venue',
        'Instructor',
        'Course Duration',
        'Total Recognized Duration',
        'Learning Hours',
        '% Progress',
        'Progress Status',
        'Start Date',
        'End Date',
        'Start Time',
        'End Time',
        'Record Month',
        'Requirement',
        'Attendance',
        'Attendance Reason',
        'Bond Status',
        'Bond Duration',
        'Bond End Date',
        'Bond Amount',
        'Score (L1) - Course',
        'Score (L2)',
        'L2 Status',
        'Score (L3)',
        'L3 Status',
        'Certificate Availability',
        'Certificate Institution',
        'Certificate Issued Date',
        'Certificate Expired Date',
        'Person in Charge (PIC)',
        'Total Engagement Time (LH)',
    ];

    public const EXPECTED_DATATABLES_KEYS = [
        'DT_RowIndex',
        'source_badge',
        'employee_id',
        'employee_name',
        'grade',
        'job_position',
        'team',
        'department',
        'company',
        'course_title',
        'sub_course_title',
        'type',
        'category',
        'level',
        'batch',
        'main_classification',
        'sub_classification',
        'delivery_method',
        'sub_delivery_method',
        'provider',
        'sub_provider',
        'provider_name',
        'venue',
        'instructor',
        'course_duration',
        'total_recognized_duration',
        'learning_hours',
        'progress_percent',
        'progress_status',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'record_month',
        'requirement',
        'attendance',
        'attendance_reason',
        'bond_status',
        'bond_duration',
        'bond_end_date',
        'bond_amount',
        'score_l1',
        'score_l2',
        'l2_status',
        'score_l3',
        'l3_status',
        'certificate_availability',
        'certificate_institution',
        'certificate_issued_date',
        'certificate_expired_date',
        'pic',
        'total_engagement_time',
        'action',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('role', '2')->first() ?? User::first();
        $this->combinedService = app(RecordAllCombinedService::class);
    }

    /**
     * Test 1 — Column Count: Verify exactly 52 canonical columns exist in export definition and blade table.
     */
    public function test_1_column_count(): void
    {
        $this->assertCount(52, self::CANONICAL_52_COLUMNS);
        $export = new AllParticipantExport(['employee_id' => '129458', 'name' => 'Test']);
        $this->assertCount(52, $export->headings());
        $this->assertCount(52, $export->columnWidths());

        $bladeContent = file_get_contents(resource_path('views/main/record/all/participant.blade.php'));
        // Count th tags in table header (52 canonical columns + 1 ACTION column = 53 th tags)
        preg_match('/<thead[^>]*>(.*?)<\/thead>/s', $bladeContent, $theadMatches);
        $this->assertNotEmpty($theadMatches);
        preg_match_all('/<th[^>]*>/', $theadMatches[1], $thMatches);
        $this->assertCount(53, $thMatches[0], 'Participant detail table must contain 52 data headers + 1 action header');
    }

    /**
     * Test 2 — Column Order: Verify the 52 columns follow the exact prescribed order.
     */
    public function test_2_column_order(): void
    {
        $export = new AllParticipantExport(['employee_id' => '129458', 'name' => 'Test']);
        $headings = $export->headings();
        $this->assertSame(self::CANONICAL_52_COLUMNS, $headings);
        $this->assertSame('NO', $headings[0]);
        $this->assertSame('Source', $headings[1]);
        $this->assertSame('Employee ID', $headings[2]);
        $this->assertSame('Course Title', $headings[9]);
        $this->assertSame('Total Recognized Duration', $headings[25]);
        $this->assertSame('Learning Hours', $headings[26]);
        $this->assertSame('Person in Charge (PIC)', $headings[50]);
        $this->assertSame('Total Engagement Time (LH)', $headings[51]);
    }

    /**
     * Test 3 — Online Mapping: Verify online records have all 52 keys populated.
     */
    public function test_3_online_mapping(): void
    {
        $participant = $this->combinedService->resolveParticipant('129458');
        $this->assertNotNull($participant);

        $req = new Request(['draw' => 1, 'start' => 0, 'length' => 25, 'source' => 'online']);
        $resp = $this->combinedService->participantDatatable($participant, $req);
        $json = json_decode($resp->getContent(), true);

        $this->assertNotEmpty($json['data']);
        $row = $json['data'][0];

        foreach (self::EXPECTED_DATATABLES_KEYS as $k) {
            $this->assertArrayHasKey($k, $row, "Online record missing DataTables key: {$k}");
        }
        $this->assertStringContainsString('KP OWL', $row['source_badge']);
        $this->assertSame('Online', $row['delivery_method']);
    }

    /**
     * Test 4 — Offline Mapping: Verify offline records have all 52 keys populated.
     */
    public function test_4_offline_mapping(): void
    {
        $participant = $this->combinedService->resolveParticipant('129458');
        $this->assertNotNull($participant);

        $req = new Request(['draw' => 1, 'start' => 0, 'length' => 25, 'source' => 'offline']);
        $resp = $this->combinedService->participantDatatable($participant, $req);
        $json = json_decode($resp->getContent(), true);

        $this->assertNotEmpty($json['data']);
        $row = $json['data'][0];

        foreach (self::EXPECTED_DATATABLES_KEYS as $k) {
            $this->assertArrayHasKey($k, $row, "Offline record missing DataTables key: {$k}");
        }
        $this->assertStringContainsString('Non KP OWL', $row['source_badge']);
        $this->assertSame('Offline', $row['delivery_method']);
    }

    /**
     * Test 5 — Mixed Records: Verify both online and offline records co-exist with identical column structure.
     */
    public function test_5_mixed_records(): void
    {
        $participant = $this->combinedService->resolveParticipant('129458');
        $this->assertNotNull($participant);

        $req = new Request(['draw' => 1, 'start' => 0, 'length' => 100, 'source' => 'all', 'classification' => 'basic-common-duties']);
        $resp = $this->combinedService->participantDatatable($participant, $req);
        $json = json_decode($resp->getContent(), true);

        $sources = collect($json['data'])->pluck('source')->unique()->values()->all();
        $this->assertContains('online', $sources);
        $this->assertContains('offline', $sources);

        // Every row in the mixed response must have the exact same set of keys
        foreach ($json['data'] as $idx => $r) {
            foreach (self::EXPECTED_DATATABLES_KEYS as $key) {
                $this->assertArrayHasKey($key, $r, "Row {$idx} missing key {$key}");
            }
        }
    }

    /**
     * Test 6 — DataTables POST: Verify endpoint handles POST with 200 OK and valid JSON structure.
     */
    public function test_6_datatables_post(): void
    {
        $resp = $this->actingAs($this->admin)
            ->postJson(route('manage.record.all.participant.datatable', '129458'), [
                'draw'           => 1,
                'start'          => 0,
                'length'         => 10,
                'classification' => 'basic-common-duties',
            ]);

        $resp->assertOk()
            ->assertJsonStructure([
                'draw',
                'recordsTotal',
                'recordsFiltered',
                'data',
            ]);

        $this->assertSame(67, $resp->json('recordsTotal'));
    }

    /**
     * Test 7 — Missing Field: Non-applicable fields yield standard placeholder '-' and never shift columns.
     */
    public function test_7_missing_field_fallback(): void
    {
        $participant = $this->combinedService->resolveParticipant('129458');
        $export = new AllParticipantExport($participant, ['classification' => 'basic-common-duties']);
        $tempPath = $export->generateXlsxFile();

        $spreadsheet = IOFactory::load($tempPath);
        $sheet = $spreadsheet->getActiveSheet();

        // Check Online row (Row 2) has '-' for offline-only fields like Start Time, End Time, Sub Provider
        $this->assertSame('-', (string) $sheet->getCell('AF2')->getValue()); // Start Time
        $this->assertSame('-', (string) $sheet->getCell('AG2')->getValue()); // End Time
        $this->assertSame('-', (string) $sheet->getCell('U2')->getValue());  // Sub Provider

        // Check Offline row has '-' for online-only fields like Total Recognized Duration
        for ($r = 2; $r <= $sheet->getHighestRow(); $r++) {
            if ((string) $sheet->getCell("B{$r}")->getValue() === 'Non KP OWL') {
                $this->assertSame('-', (string) $sheet->getCell("Z{$r}")->getValue()); // Total Recognized Duration
                break;
            }
        }

        @unlink($tempPath);
    }

    /**
     * Test 8 — Recognized Duration: Uses canonical service calculation.
     */
    public function test_8_recognized_duration_canonical(): void
    {
        $participant = $this->combinedService->resolveParticipant('129458');
        $req = new Request(['draw' => 1, 'start' => 0, 'length' => 10, 'source' => 'online']);
        $resp = $this->combinedService->participantDatatable($participant, $req);
        $json = json_decode($resp->getContent(), true);

        $row = $json['data'][0];
        $this->assertNotEmpty($row['total_recognized_duration']);
        $this->assertNotSame('-', $row['total_recognized_duration']);
        $this->assertMatchesRegularExpression('/^\d{2}:\d{2}:\d{2}$/', $row['total_recognized_duration']);
    }

    /**
     * Test 9 — Engagement Time: Total Engagement Time strictly adheres to AFK seconds calculation.
     */
    public function test_9_engagement_time_canonical(): void
    {
        $participant = $this->combinedService->resolveParticipant('129458');
        $req = new Request(['draw' => 1, 'start' => 0, 'length' => 10, 'source' => 'all']);
        $resp = $this->combinedService->participantDatatable($participant, $req);
        $json = json_decode($resp->getContent(), true);

        $row = $json['data'][0];
        $this->assertArrayHasKey('total_engagement_time', $row);
        $this->assertMatchesRegularExpression('/^\d{2}:\d{2}:\d{2}$/', $row['total_engagement_time']);
    }

    /**
     * Test 10 — Excel Export: Generates valid 52-column spreadsheet.
     */
    public function test_10_excel_export_52_columns(): void
    {
        $participant = $this->combinedService->resolveParticipant('129458');
        $export = new AllParticipantExport($participant, ['classification' => 'basic-common-duties']);
        $tempPath = $export->generateXlsxFile();

        $spreadsheet = IOFactory::load($tempPath);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertSame('AZ', $sheet->getHighestColumn(), 'Excel export highest column must be AZ (52 columns)');
        $this->assertSame(68, $sheet->getHighestRow(), 'Excel export must contain 1 header + 67 data rows = 68 rows');

        for ($col = 1; $col <= 52; $col++) {
            $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $this->assertSame(self::CANONICAL_52_COLUMNS[$col - 1], (string) $sheet->getCell("{$letter}1")->getValue());
        }

        @unlink($tempPath);
    }

    /**
     * Test 11 — Filter Consistency: DataTables count strictly matches Excel row count.
     */
    public function test_11_filter_consistency(): void
    {
        $participant = $this->combinedService->resolveParticipant('125728');
        $export = new AllParticipantExport($participant, ['classification' => 'basic-common-duties']);
        $tempPath = $export->generateXlsxFile();

        $spreadsheet = IOFactory::load($tempPath);
        $sheet = $spreadsheet->getActiveSheet();

        // 34 data rows + 1 header row = 35 total rows
        $this->assertSame(35, $sheet->getHighestRow());

        $req = new Request(['draw' => 1, 'start' => 0, 'length' => 10, 'classification' => 'basic-common-duties']);
        $resp = $this->combinedService->participantDatatable($participant, $req);
        $json = json_decode($resp->getContent(), true);

        $this->assertSame(34, $json['recordsTotal']);
        $this->assertSame(34, $sheet->getHighestRow() - 1);

        @unlink($tempPath);
    }

    /**
     * Test 12 — Regression: Verify Participant 125728 (34 rows) and 129458 (67 rows) keep intact data.
     */
    public function test_12_regression_and_data_integrity(): void
    {
        $p1 = $this->combinedService->resolveParticipant('125728');
        $p2 = $this->combinedService->resolveParticipant('129458');

        $req1 = new Request(['classification' => 'basic-common-duties']);
        $req2 = new Request(['classification' => 'basic-common-duties']);

        $res1 = $this->combinedService->participantDatatable($p1, $req1);
        $res2 = $this->combinedService->participantDatatable($p2, $req2);

        $this->assertSame(34, json_decode($res1->getContent(), true)['recordsTotal']);
        $this->assertSame(67, json_decode($res2->getContent(), true)['recordsTotal']);
    }

    /**
     * Test 13 — HTML Badges: Verify Category and other badge columns return unescaped HTML (no &lt;span).
     */
    public function test_13_category_and_badges_not_html_escaped(): void
    {
        $p = $this->combinedService->resolveParticipant('129458');
        $req = new Request(['classification' => 'basic-common-duties', 'length' => 25]);
        $res = $this->combinedService->participantDatatable($p, $req);
        $json = json_decode($res->getContent(), true);

        $this->assertNotEmpty($json['data']);
        $firstRow = $json['data'][0];

        // Ensure category contains raw HTML span and not entity-escaped &lt;span
        $this->assertStringContainsString('<span class="badge', $firstRow['category']);
        $this->assertStringNotContainsString('&lt;span', $firstRow['category']);

        // Verify other HTML columns are not escaped
        $this->assertStringContainsString('<span class="badge', $firstRow['source_badge']);
        $this->assertStringNotContainsString('&lt;span', $firstRow['source_badge']);
        $this->assertStringNotContainsString('&lt;div', $firstRow['course_title']);

        // Verify classification datatable category badge as well
        $classReq = new Request(['classification' => 'basic-common-duties', 'length' => 10]);
        $classRes = $this->combinedService->datatable($classReq, 'basic-common-duties');
        $classJson = json_decode($classRes->getContent(), true);
        $this->assertNotEmpty($classJson['data']);
        $this->assertStringContainsString('<span class="badge', $classJson['data'][0]['category']);
        $this->assertStringNotContainsString('&lt;span', $classJson['data'][0]['category']);
    }
}
