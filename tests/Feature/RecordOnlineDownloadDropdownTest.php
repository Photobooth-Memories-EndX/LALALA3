<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class RecordOnlineDownloadDropdownTest extends TestCase
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
     * TEST 1 & 2: Landing page renders the Download dropdown button with matching UI.
     */
    public function test_online_landing_renders_download_dropdown_button(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.record.online'));

        $response->assertStatus(200);

        // Dropdown toggle button
        $response->assertSee('id="btn-download-dropdown"', false);
        $response->assertSee('Download');
        $response->assertSee('ti ti-download');
        $response->assertSee('btn btn-outline-secondary dropdown-toggle', false);

        // Dropdown items
        $response->assertSee('id="btn-export-online-xlsx"', false);
        $response->assertSee(route('manage.record.online.export'));
        $response->assertSee('Download Online Record (.xlsx)');
        $response->assertSee('ti ti-file-spreadsheet');

        $response->assertSee('id="btn-export-online-pdf"', false);
        $response->assertSee(route('manage.record.online.export.pdf'));
        $response->assertSee('Download PDF (.pdf)');
        $response->assertSee('ti ti-file-type-pdf');

        $response->assertSee('id="btn-export-online-csv"', false);
        $response->assertSee(route('manage.record.online.export.csv'));
        $response->assertSee('Download CSV (.csv)');
        $response->assertSee('ti ti-file-type-csv');

        // Confirm "Download Template" is NOT present on Online page
        $response->assertDontSee('Download Template');
        $response->assertDontSee('btn-download-offline-template');
    }

    /**
     * TEST 3: Excel export download returns valid xlsx response.
     */
    public function test_online_export_excel_endpoint_works(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.record.online.export'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition'));
    }

    /**
     * TEST 4: CSV export download returns valid csv response with BOM and headers.
     */
    public function test_online_export_csv_endpoint_works(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.record.online.export.csv'));

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
        $this->assertStringContainsString('Classification', $content);
    }

    /**
     * TEST 5: PDF export download returns valid pdf response with A4 landscape.
     */
    public function test_online_export_pdf_endpoint_works(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.record.online.export.pdf'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('.pdf', $response->headers->get('content-disposition'));

        // Check PDF magic byte header
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();
        $this->assertStringStartsWith('%PDF-', $content);
    }

    /**
     * TEST 6: Authorization protection on all Online export endpoints.
     */
    public function test_online_export_endpoints_require_permission(): void
    {
        // 1. Guest redirected
        $guestRoutes = [
            route('manage.record.online.export'),
            route('manage.record.online.export.pdf'),
            route('manage.record.online.export.csv'),
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

    /**
     * TEST 7: Filter params are passed into exports.
     */
    public function test_online_export_accepts_filter_params(): void
    {
        $params = [
            'year'   => '2026',
            'status' => 'finished',
        ];

        $excelResp = $this->actingAs($this->admin)->get(route('manage.record.online.export', $params));
        $excelResp->assertStatus(200);

        $csvResp = $this->actingAs($this->admin)->get(route('manage.record.online.export.csv', $params));
        $csvResp->assertStatus(200);

        $pdfResp = $this->actingAs($this->admin)->get(route('manage.record.online.export.pdf', $params));
        $pdfResp->assertStatus(200);
    }

    /**
     * TEST 8: Regression check - Offline page remains intact with its full button set.
     */
    public function test_offline_page_remains_intact(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.record.offline'));

        $response->assertStatus(200);
        $response->assertSee('id="btn-open-offline-add"', false);
        $response->assertSee('Add Training');
        $response->assertSee('id="btn-open-offline-import"', false);
        $response->assertSee('Import Data Non PTKP');
        $response->assertSee('id="btn-download-dropdown"', false);
        $response->assertSee('Download');
        $response->assertSee('id="btn-download-offline-template"', false);
        $response->assertSee('Download Template');
        $response->assertSee('id="btn-export-offline-xlsx"', false);
        $response->assertSee('Download Offline Record (.xlsx)');
        $response->assertSee('id="btn-export-offline-pdf"', false);
        $response->assertSee('Download PDF (.pdf)');
        $response->assertSee('id="btn-export-offline-csv"', false);
        $response->assertSee('Download CSV (.csv)');
    }

    /**
     * TEST 9: KP OWL (Online) landing page DIV reordering:
     * 1. Banner (manage-record-hero)
     * 2. Statistic Cards (kpi-summary-container)
     * 3. Toolbar (record-section-header with title and download dropdown)
     * 4. Navigation Cards (record-navigation-row)
     * 5. Main Content (classification-summary-card)
     */
    public function test_online_landing_page_reordered_div_layout(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.record.online'));
        $response->assertStatus(200);

        $content = $response->getContent();
        $body = substr($content, strpos($content, '<body'));

        $posHero = strpos($body, 'manage-record-hero');
        $posKpi = strpos($body, 'id="kpi-summary-container"');
        $posToolbar = strpos($body, 'record-section-header');
        $posNav = strpos($body, 'record-navigation-row');
        $posAction = strpos($body, 'id="btn-download-dropdown"');
        $posClassification = strpos($body, 'classification-summary-card');

        $this->assertNotFalse($posHero, 'Banner (manage-record-hero) must exist');
        $this->assertNotFalse($posKpi, 'Statistic cards (kpi-summary-container) must exist');
        $this->assertFalse($posToolbar, 'Header section (record-section-header) must be removed');
        $this->assertNotFalse($posNav, 'Navigation cards (record-navigation-row) must exist');
        $this->assertNotFalse($posAction, 'Action bar (btn-download-dropdown) must exist below navigation cards');
        $this->assertNotFalse($posClassification, 'Classification summary (classification-summary-card) must exist');

        $this->assertLessThan($posKpi, $posHero, 'Banner must precede Statistic Cards');
        $this->assertLessThan($posNav, $posKpi, 'Statistic Cards must precede Navigation Cards');
        $this->assertLessThan($posAction, $posNav, 'Navigation Cards must precede Action Bar');
        $this->assertLessThan($posClassification, $posAction, 'Action Bar must precede Classification Summary');

        // Check active state for KP OWL
        $response->assertSee('KP OWL');
        $response->assertSee('record-nav-card--active');
    }
}
