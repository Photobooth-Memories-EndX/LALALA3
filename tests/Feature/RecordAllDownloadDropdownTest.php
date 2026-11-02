<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class RecordAllDownloadDropdownTest extends TestCase
{
    protected User $admin;
    protected ?User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', '2')->first() ?? User::first();
        $this->regularUser = User::where('role', '1')->first();
    }

    /**
     * TEST 1: Landing page renders the Download dropdown button with matching UI and choices.
     */
    public function test_all_record_landing_renders_download_dropdown_button(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.record.all'));

        $response->assertStatus(200);

        // Action bar wrapper
        $response->assertSee('id="btn-export-all-combined"', false);

        // Dropdown toggle button
        $response->assertSee('id="btn-download-dropdown"', false);
        $response->assertSee('Download');
        $response->assertSee('ti ti-download');
        $response->assertSee('btn btn-outline-secondary dropdown-toggle', false);

        // 1. Excel item
        $response->assertSee('id="btn-all-export-excel"', false);
        $response->assertSee(route('manage.record.all.export'));
        $response->assertSee('Download Offline Record (.xlsx)');
        $response->assertSee('ti ti-file-spreadsheet');

        // 2. PDF item
        $response->assertSee('id="btn-export-all-pdf"', false);
        $response->assertSee(route('manage.record.all.export.pdf'));
        $response->assertSee('Download PDF (.pdf)');
        $response->assertSee('ti ti-file-type-pdf');

        // 3. CSV item
        $response->assertSee('id="btn-export-all-csv"', false);
        $response->assertSee(route('manage.record.all.export.csv'));
        $response->assertSee('Download CSV (.csv)');
        $response->assertSee('ti ti-file-type-csv');
    }

    /**
     * TEST 2: Excel export download returns valid xlsx response.
     */
    public function test_all_export_excel_endpoint_works(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.record.all.export'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition'));
    }

    /**
     * TEST 3: CSV export download returns valid csv response with BOM and headers.
     */
    public function test_all_export_csv_endpoint_works(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.record.all.export.csv'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('.csv', $response->headers->get('content-disposition'));

        // Capture streamed content
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        // Check BOM
        $this->assertStringStartsWith(chr(0xEF) . chr(0xBB) . chr(0xBF), $content);
        // Check headings
        $this->assertStringContainsString('Employee ID', $content);
        $this->assertStringContainsString('Status', $content);
    }

    /**
     * TEST 4: PDF export endpoint is accessible and either downloads PDF or enforces 200 limit guard.
     */
    public function test_all_export_pdf_endpoint_works(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.record.all.export.pdf'));

        // Depending on existing record count, either 200 (PDF binary) or 302/redirect with limit warning
        $this->assertTrue(in_array($response->getStatusCode(), [200, 302]));

        if ($response->getStatusCode() === 200) {
            $response->assertHeader('content-type', 'application/pdf');
            $this->assertStringContainsString('.pdf', $response->headers->get('content-disposition'));
        }
    }

    /**
     * TEST 5: Authorization protection on all All Record export endpoints.
     */
    public function test_all_export_endpoints_require_permission(): void
    {
        // 1. Guest redirected
        $guestRoutes = [
            route('manage.record.all.export'),
            route('manage.record.all.export.pdf'),
            route('manage.record.all.export.csv'),
        ];

        foreach ($guestRoutes as $url) {
            $guestResp = $this->get($url);
            $this->assertTrue($guestResp->isRedirect() || $guestResp->getStatusCode() === 401);
        }

        // 2. Regular user (no record_management) gets 403
        if ($this->regularUser) {
            foreach ($guestRoutes as $url) {
                $unauthResp = $this->actingAs($this->regularUser)->get($url);
                $unauthResp->assertStatus(403);
            }
        }
    }
}
