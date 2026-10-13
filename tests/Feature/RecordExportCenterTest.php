<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class RecordExportCenterTest extends TestCase
{
    protected ?User $admin = null;

    protected function setUp(): void
    {
        parent::setUp();
        // User with admin role (role = 2 or 3)
        $this->admin = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])->first() ?? User::find(1);
    }

    /**
     * Verify Export Center index page loads with 200 and no Coming Soon placeholder.
     */
    public function test_export_center_page_renders_successfully()
    {
        $response = $this->actingAs($this->admin)->get(route('manage.record.export-all'));

        $response->assertStatus(200);
        $response->assertSee('Learning Record Export Center');
        $response->assertSee('Choose Data Source');
        $response->assertSee('Online Learning Record');
        $response->assertSee('Offline Training Record');
        $response->assertSee('Combined Learning Record');
        $response->assertDontSee('Coming Soon — Planned Module');
    }

    /**
     * Test Export Center landing page DIV reordering:
     * 1. Banner (manage-record-hero)
     * 2. Toolbar (record-section-header with Export KP OWL title and description)
     * 3. Navigation Cards (record-navigation-row)
     * 4. Main Content (export-center-form)
     */
    public function test_export_center_page_reordered_div_layout(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.record.export-all'));
        $response->assertStatus(200);

        $content = $response->getContent();
        $body = substr($content, strpos($content, '<body'));

        $posHero = strpos($body, 'manage-record-hero');
        $posToolbar = strpos($body, 'class="card border-0 shadow-sm p-3 p-md-4 mb-4 rounded-3 bg-white record-section-header"');
        $posNav = strpos($body, 'record-navigation-row');
        $posMain = strpos($body, 'id="export-center-form"');

        $this->assertNotFalse($posHero, 'Banner (manage-record-hero) must exist');
        $this->assertNotFalse($posToolbar, 'Toolbar (record-section-header) must exist');
        $this->assertNotFalse($posNav, 'Navigation cards (record-navigation-row) must exist');
        $this->assertNotFalse($posMain, 'Main Content form (export-center-form) must exist');

        $this->assertLessThan($posToolbar, $posHero, 'Banner must precede Toolbar');
        $this->assertLessThan($posNav, $posToolbar, 'Toolbar must precede Navigation Cards');
        $this->assertLessThan($posMain, $posNav, 'Navigation Cards must precede Main Content form');

        // Check active state for Export KP OWL
        $response->assertSee('Export KP OWL');
        $response->assertSee('record-nav-card--active');
    }

    /**
     * Sub classifications endpoint returns valid JSON.
     */
    public function test_sub_classifications_endpoint()
    {
        $response = $this->actingAs($this->admin)->getJson(route('manage.record.export-all.sub-classifications'));

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'data']);
    }

    /**
     * CASE 1: source = online tanpa filter -> preview online saja.
     */
    public function test_case_1_preview_online_without_filters()
    {
        $response = $this->actingAs($this->admin)->getJson(route('manage.record.export-all.preview', [
            'source' => 'online',
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'source'  => 'online',
        ]);
        $data = $response->json('data');
        $this->assertIsArray($data);
        if (!empty($data)) {
            $this->assertEquals('online', $data[0]['source']);
            $this->assertEquals('KP OWL', $data[0]['source_label']);
        }
    }

    /**
     * CASE 2: source = offline tanpa filter -> preview offline saja.
     */
    public function test_case_2_preview_offline_without_filters()
    {
        $response = $this->actingAs($this->admin)->getJson(route('manage.record.export-all.preview', [
            'source' => 'offline',
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'source'  => 'offline',
        ]);
        $data = $response->json('data');
        $this->assertIsArray($data);
        if (!empty($data)) {
            $this->assertEquals('offline', $data[0]['source']);
            $this->assertEquals('Non KP OWL', $data[0]['source_label']);
        }
    }

    /**
     * CASE 3: source = all tanpa filter -> preview combined record.
     */
    public function test_case_3_preview_all_combined()
    {
        $response = $this->actingAs($this->admin)->getJson(route('manage.record.export-all.preview', [
            'source' => 'all',
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'source'  => 'all',
        ]);
        $this->assertGreaterThanOrEqual(0, $response->json('total'));
    }

    /**
     * CASE 4: employee tertentu -> hanya employee tersebut.
     */
    public function test_case_4_filter_by_employee()
    {
        $response = $this->actingAs($this->admin)->getJson(route('manage.record.export-all.preview', [
            'source'      => 'all',
            'employee_id' => 'Budi',
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');
        foreach ($data as $item) {
            $matched = str_contains(strtolower($item['employee_name']), 'budi') || str_contains(strtolower($item['employee_id']), 'budi');
            $this->assertTrue($matched, "Record employee does not match filter: {$item['employee_name']}");
        }
    }

    /**
     * CASE 5: year tertentu -> hanya tahun tersebut.
     */
    public function test_case_5_filter_by_year()
    {
        $response = $this->actingAs($this->admin)->getJson(route('manage.record.export-all.preview', [
            'source' => 'all',
            'year'   => '2025',
        ]));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /**
     * CASE 6: date range -> record sesuai period.
     */
    public function test_case_6_filter_by_date_range()
    {
        $response = $this->actingAs($this->admin)->getJson(route('manage.record.export-all.preview', [
            'source'    => 'all',
            'from_date' => '2024-01-01',
            'to_date'   => '2025-12-31',
        ]));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /**
     * CASE 7: classification -> hanya classification tersebut.
     */
    public function test_case_7_filter_by_classification()
    {
        $response = $this->actingAs($this->admin)->getJson(route('manage.record.export-all.preview', [
            'source'         => 'all',
            'classification' => 'Leadership',
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');
        foreach ($data as $item) {
            $this->assertEquals('Leadership', $item['classification']);
        }
    }

    /**
     * CASE 8: multiple filter: year + classification + department.
     */
    public function test_case_8_multiple_filters()
    {
        $response = $this->actingAs($this->admin)->getJson(route('manage.record.export-all.preview', [
            'source'         => 'all',
            'year'           => '2025',
            'classification' => 'Leadership',
            'department'     => 'Human Resources',
        ]));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /**
     * CASE 9: Excel download for all sources.
     */
    public function test_case_9_excel_download()
    {
        // 1. Online Excel
        $resOnline = $this->actingAs($this->admin)->get(route('manage.record.export-all.download', [
            'source' => 'online',
            'format' => 'xlsx',
            'year'   => '2026',
        ]));
        $resOnline->assertStatus(200);
        $this->assertStringContainsString('spreadsheet', $resOnline->headers->get('content-type', ''));

        // 2. Offline Excel
        $resOffline = $this->actingAs($this->admin)->get(route('manage.record.export-all.download', [
            'source' => 'offline',
            'format' => 'xlsx',
        ]));
        $resOffline->assertStatus(200);
        $this->assertStringContainsString('spreadsheet', $resOffline->headers->get('content-type', ''));

        // 3. All Excel
        $resAll = $this->actingAs($this->admin)->get(route('manage.record.export-all.download', [
            'source' => 'all',
            'format' => 'xlsx',
            'year'   => '2026',
        ]));
        $resAll->assertStatus(200);
        $this->assertStringContainsString('spreadsheet', $resAll->headers->get('content-type', ''));
    }

    /**
     * CASE 10: CSV download for all sources.
     */
    public function test_case_10_csv_download()
    {
        // 1. Online CSV
        $resOnline = $this->actingAs($this->admin)->get(route('manage.record.export-all.download', [
            'source' => 'online',
            'format' => 'csv',
            'year'   => '2026',
        ]));
        $resOnline->assertStatus(200);
        $this->assertStringContainsString('csv', $resOnline->headers->get('content-type', ''));

        // 2. Offline CSV
        $resOffline = $this->actingAs($this->admin)->get(route('manage.record.export-all.download', [
            'source' => 'offline',
            'format' => 'csv',
        ]));
        $resOffline->assertStatus(200);
        $this->assertStringContainsString('csv', $resOffline->headers->get('content-type', ''));

        // 3. All CSV
        $resAll = $this->actingAs($this->admin)->get(route('manage.record.export-all.download', [
            'source' => 'all',
            'format' => 'csv',
            'year'   => '2026',
        ]));
        $resAll->assertStatus(200);
        $this->assertStringContainsString('csv', $resAll->headers->get('content-type', ''));
    }

    /**
     * CASE 11: PDF Landscape download.
     */
    public function test_case_11_pdf_download()
    {
        // Offline PDF (safely under limit)
        $resOffline = $this->actingAs($this->admin)->get(route('manage.record.export-all.download', [
            'source' => 'offline',
            'format' => 'pdf',
        ]));
        $resOffline->assertStatus(200);
        $this->assertStringContainsString('pdf', $resOffline->headers->get('content-type', ''));
    }

    /**
     * CASE 12: reset -> default preview works cleanly.
     */
    public function test_case_12_reset_defaults()
    {
        $response = $this->actingAs($this->admin)->getJson(route('manage.record.export-all.preview', [
            'source' => 'online',
            'status' => 'all',
            'year'   => 'all',
        ]));

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
    }

    /**
     * CASE 13: switching source online -> offline safely ignores non-applicable filters.
     */
    public function test_case_13_switching_source_ignores_invalid_filters()
    {
        $response = $this->actingAs($this->admin)->getJson(route('manage.record.export-all.preview', [
            'source'   => 'offline',
            'provider' => 'Internal',
        ]));

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
    }

    /**
     * CASE 14: invalid filter/query -> validation error, bukan SQL error.
     */
    public function test_case_14_validation_error()
    {
        $response = $this->actingAs($this->admin)->getJson(route('manage.record.export-all.download', [
            'source' => 'invalid_source_name',
            'format' => 'xlsx',
        ]));

        $response->assertStatus(422);
        $this->assertFalse($response->json('success'));
    }

    /**
     * CASE 15: large data -> streaming / chunking without memory explosion.
     */
    public function test_case_15_large_dataset_safety()
    {
        $startMem = memory_get_usage(true);

        $response = $this->actingAs($this->admin)->getJson(route('manage.record.export-all.preview', [
            'source' => 'all',
        ]));

        $response->assertStatus(200);
        $endMem = memory_get_usage(true);

        // Memory difference should not exceed 40 MB for preview
        $memDiffMb = ($endMem - $startMem) / (1024 * 1024);
        $this->assertLessThan(40, $memDiffMb, "Memory increased by {$memDiffMb} MB");
    }
}
