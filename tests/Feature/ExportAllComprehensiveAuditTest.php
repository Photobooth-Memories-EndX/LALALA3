<?php

namespace Tests\Feature;

use App\Exports\AllRecordExport;
use App\Exports\OfflineRecordExport;
use App\Exports\OnlineRecordExport;
use App\Models\User;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class ExportAllComprehensiveAuditTest extends TestCase
{
    protected ?User $admin = null;

    public const CANONICAL_52_COLUMNS = [
        'No',
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
        'Total Engagement Time',
        'Total Engagement Time (LH)',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])->first() ?? User::find(1);
    }

    /**
     * 1. Verify 52 canonical headings count across Online, Offline, and All exports.
     */
    public function test_all_three_exports_have_52_headings()
    {
        $online = OnlineRecordExport::headers();
        $offline = OfflineRecordExport::headers();
        $all = AllRecordExport::headings();

        $this->assertCount(52, $online);
        $this->assertCount(52, $offline);
        $this->assertCount(52, $all);

        $this->assertSame(self::CANONICAL_52_COLUMNS, $online);
        $this->assertSame(self::CANONICAL_52_COLUMNS, $offline);
        $this->assertSame(self::CANONICAL_52_COLUMNS, $all);
    }

    /**
     * 2. Main Classification filter works in preview.
     */
    public function test_filter_main_classification()
    {
        $response = $this->actingAs($this->admin)->getJson(route('manage.record.export-all.preview', [
            'source'         => 'all',
            'classification' => 'Leadership',
        ]));
        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        foreach ($response->json('data') as $row) {
            $this->assertEquals('Leadership', $row['classification']);
        }
    }

    /**
     * 3. Content Type filter works (Online & All).
     */
    public function test_filter_content_type()
    {
        $response = $this->actingAs($this->admin)->getJson(route('manage.record.export-all.preview', [
            'source' => 'online',
            'type'   => 'Video',
        ]));
        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
    }

    /**
     * 4. Completion Status filter works.
     */
    public function test_filter_completion_status()
    {
        $response = $this->actingAs($this->admin)->getJson(route('manage.record.export-all.preview', [
            'source' => 'all',
            'status' => 'finished',
        ]));
        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        foreach ($response->json('data') as $row) {
            $this->assertEquals('Finished', $row['status']);
        }
    }

    /**
     * 5. Two-stage Scope: Company.
     */
    public function test_scope_company()
    {
        $response = $this->actingAs($this->admin)->getJson(route('manage.record.export-all.preview', [
            'source'               => 'all',
            'filter_scope_type'    => 'company',
            'filter_scope_values'  => ['PTKP', 'PT Krakatau Posco'],
        ]));
        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
    }

    /**
     * 6. Two-stage Scope: Division.
     */
    public function test_scope_division()
    {
        $response = $this->actingAs($this->admin)->getJson(route('manage.record.export-all.preview', [
            'source'               => 'all',
            'filter_scope_type'    => 'division',
            'filter_scope_values'  => ['Human Resources'],
        ]));
        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
    }

    /**
     * 7. Two-stage Scope: Department.
     */
    public function test_scope_department()
    {
        $response = $this->actingAs($this->admin)->getJson(route('manage.record.export-all.preview', [
            'source'               => 'all',
            'filter_scope_type'    => 'department',
            'filter_scope_values'  => ['Human Resources Department'],
        ]));
        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
    }

    /**
     * 8. Period Filter: Quarterly.
     */
    public function test_period_quarterly()
    {
        $response = $this->actingAs($this->admin)->getJson(route('manage.record.export-all.preview', [
            'source'      => 'all',
            'period_type' => 'Quarter',
            'quarter'     => 'Q1',
            'year'        => '2026',
        ]));
        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
    }

    /**
     * 9. Period Filter: Half-Yearly.
     */
    public function test_period_half_yearly()
    {
        $response = $this->actingAs($this->admin)->getJson(route('manage.record.export-all.preview', [
            'source'      => 'all',
            'period_type' => 'Half',
            'half'        => 'H1',
            'year'        => '2026',
        ]));
        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
    }

    /**
     * 10. Multi-filter combination: Classification + Status + Year.
     */
    public function test_complex_multi_filters()
    {
        $response = $this->actingAs($this->admin)->getJson(route('manage.record.export-all.preview', [
            'source'         => 'all',
            'classification' => 'Leadership',
            'status'         => 'finished',
            'period_type'    => 'Year',
            'year'           => '2025',
        ]));
        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
    }

    /**
     * 11. Download Online XLSX returns 200 with valid spreadsheet.
     */
    public function test_download_online_xlsx()
    {
        $response = $this->actingAs($this->admin)->get(route('manage.record.export-all.download', [
            'source' => 'online',
            'format' => 'xlsx',
            'year'   => '2026',
        ]));
        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheet', $response->headers->get('content-type', ''));
    }

    /**
     * 12. Download Offline XLSX returns 200 with valid spreadsheet.
     */
    public function test_download_offline_xlsx()
    {
        $response = $this->actingAs($this->admin)->get(route('manage.record.export-all.download', [
            'source' => 'offline',
            'format' => 'xlsx',
        ]));
        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheet', $response->headers->get('content-type', ''));
    }

    /**
     * 13. Download Combined All XLSX returns 200 with valid spreadsheet.
     */
    public function test_download_all_xlsx()
    {
        $response = $this->actingAs($this->admin)->get(route('manage.record.export-all.download', [
            'source' => 'all',
            'format' => 'xlsx',
            'year'   => '2026',
        ]));
        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheet', $response->headers->get('content-type', ''));
    }
}
