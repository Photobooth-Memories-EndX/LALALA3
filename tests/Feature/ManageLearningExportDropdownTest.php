<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Learning\LearningDatatableService;
use Tests\TestCase;

class ManageLearningExportDropdownTest extends TestCase
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
     * TEST 1: All Learning page renders the Download Dropdown with Excel, PDF, CSV items.
     */
    public function test_all_learning_renders_download_dropdown(): void
    {
        $response = $this->actingAs($this->admin)->get(route('learning.manage.all'));

        $response->assertStatus(200);
        $response->assertSee('id="btn-download-dropdown"', false);
        $response->assertSee('Download');
        $response->assertSee('ti ti-download');
        $response->assertSee('btn-download');

        // Dropdown Items
        $response->assertSee('id="btn-download-export"', false);
        $response->assertSee('Download Excel (.xlsx)');
        $response->assertSee('ti ti-file-spreadsheet');

        $response->assertSee('id="btn-download-pdf"', false);
        $response->assertSee('Download PDF (.pdf)');
        $response->assertSee('ti ti-file-type-pdf');

        $response->assertSee('id="btn-download-csv"', false);
        $response->assertSee('Download CSV (.csv)');
        $response->assertSee('ti ti-file-type-csv');
    }

    /**
     * TEST 2: Classification (Category) page renders Download Dropdown and preserves context.
     */
    public function test_category_learning_renders_download_dropdown(): void
    {
        $response = $this->actingAs($this->admin)->get(route('learning.manage.category', 'basic-common-duties'));

        $response->assertStatus(200);
        $response->assertSee('quick-filter-toolbar', false);
        $response->assertSee('flex-xl-nowrap', false);
        $response->assertSee('quick-filter-employee', false);
        $response->assertSee('quick-filter-title', false);
        $response->assertSee('quick-filter-category', false);
        $response->assertSee('quick-filter-status', false);
        $response->assertSee('quick-filter-type', false);
        $response->assertSee('quick-filter-year', false);
        $response->assertSee('quick-filter-action-btn', false);
        $response->assertSee('quick-filter-download', false);
        $response->assertSee('id="btn-download-dropdown"', false);
        $response->assertSee('id="btn-download-export"', false);
        $response->assertSee('id="btn-download-pdf"', false);
        $response->assertSee('id="btn-download-csv"', false);
        $response->assertSee('value="basic-common-duties"', false);
    }

    /**
     * TEST 3: Content Detail page renders Download Dropdown and preserves context.
     */
    public function test_content_learning_renders_download_dropdown(): void
    {
        $slug = 'hr-a3-kompensasi-dan-kesejahteraaan-karyawan-2025';
        $response = $this->actingAs($this->admin)->get(route('learning.manage.content', $slug));

        $response->assertStatus(200);
        $response->assertSee('quick-filter-toolbar', false);
        $response->assertSee('flex-xl-nowrap', false);
        $response->assertSee('quick-filter-employee', false);
        $response->assertSee('quick-filter-title', false);
        $response->assertSee('quick-filter-category', false);
        $response->assertSee('quick-filter-status', false);
        $response->assertSee('quick-filter-type', false);
        $response->assertSee('quick-filter-year', false);
        $response->assertSee('quick-filter-action-btn', false);
        $response->assertSee('quick-filter-download', false);
        $response->assertSee('id="btn-download-dropdown"', false);
        $response->assertSee('id="btn-download-export"', false);
        $response->assertSee('id="btn-download-pdf"', false);
        $response->assertSee('id="btn-download-csv"', false);
        $response->assertSee('value="' . $slug . '"', false);
    }

    /**
     * TEST 4: Regression test for existing Excel export (must be untouched & working).
     */
    public function test_excel_export_regression_untouched(): void
    {
        // 1. Base download route
        $respBase = $this->actingAs($this->admin)->get(route('manage.learning.download', ['action' => 'download']));
        $respBase->assertStatus(200);
        $respBase->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('.xlsx', $respBase->headers->get('content-disposition'));

        // 2. Direct action=download on All Learning
        $respAll = $this->actingAs($this->admin)->get(route('learning.manage.all', ['action' => 'download']));
        $respAll->assertStatus(200);
        $respAll->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        // 3. Direct action=download on Category
        $respCat = $this->actingAs($this->admin)->get(route('learning.manage.category', ['slug' => 'basic-common-duties', 'action' => 'download']));
        $respCat->assertStatus(200);
        $respCat->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        // 4. Direct action=download on Content
        $slug = 'hr-a3-kompensasi-dan-kesejahteraaan-karyawan-2025';
        $respContent = $this->actingAs($this->admin)->get(route('learning.manage.content', ['slug' => $slug, 'action' => 'download']));
        $respContent->assertStatus(200);
        $respContent->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    /**
     * TEST 5: PDF download endpoint works across all 3 contexts and returns valid PDF.
     */
    public function test_pdf_export_endpoints_work(): void
    {
        // 1. Base route
        $resp = $this->actingAs($this->admin)->get(route('manage.learning.download.pdf'));
        $resp->assertStatus(200);
        $resp->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('.pdf', $resp->headers->get('content-disposition'));

        ob_start();
        $resp->sendContent();
        $content = ob_get_clean();
        $this->assertStringStartsWith('%PDF-', $content);

        // 2. Category route
        $respCat = $this->actingAs($this->admin)->get(route('manage.learning.download.pdf', [
            'classification_slug' => 'basic-common-duties',
        ]));
        $respCat->assertStatus(200);
        $this->assertStringContainsString('basic-common-duties', $respCat->headers->get('content-disposition'));

        // 3. Content route
        $slug = 'hr-a3-kompensasi-dan-kesejahteraaan-karyawan-2025';
        $respContent = $this->actingAs($this->admin)->get(route('manage.learning.download.pdf', [
            'content_slug' => $slug,
        ]));
        $respContent->assertStatus(200);
        $this->assertStringContainsString('hr-a3', $respContent->headers->get('content-disposition'));
    }

    /**
     * TEST 6: CSV download endpoint works across all 3 contexts and returns valid CSV.
     */
    public function test_csv_export_endpoints_work(): void
    {
        $slug = 'hr-a3-kompensasi-dan-kesejahteraaan-karyawan-2025';

        // 1. Base route with filter
        $resp = $this->actingAs($this->admin)->get(route('manage.learning.download.csv', [
            'content_slug' => $slug,
            'status'       => 'finished',
            'year'         => '2026',
        ]));
        $resp->assertStatus(200);
        $resp->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('.csv', $resp->headers->get('content-disposition'));

        $content = file_get_contents($resp->getFile()->getPathname());

        // Check BOM
        $this->assertStringStartsWith(chr(0xEF) . chr(0xBB) . chr(0xBF), $content);
        // Check headings
        $this->assertStringContainsString('Employee ID', $content);
        $this->assertStringContainsString('Status', $content);
        $this->assertStringContainsString('Classification', $content);
        $this->assertStringContainsString('Duration', $content);

        // 2. Category route
        $respCat = $this->actingAs($this->admin)->get(route('manage.learning.download.csv', [
            'classification_slug' => 'basic-common-duties',
            'status'              => 'finished',
            'year'                => '2026',
        ]));
        $respCat->assertStatus(200);
        $this->assertStringContainsString('basic-common-duties', $respCat->headers->get('content-disposition'));

        // 3. Content route
        $respContent = $this->actingAs($this->admin)->get(route('manage.learning.download.csv', [
            'content_slug' => $slug,
        ]));
        $respContent->assertStatus(200);
        $this->assertStringContainsString('hr-a3', $respContent->headers->get('content-disposition'));
    }

    /**
     * TEST 7: Authorization - Guest and unauthorized users cannot access export endpoints.
     */
    public function test_authorization_protection(): void
    {
        $routes = [
            route('manage.learning.download'),
            route('manage.learning.download.pdf'),
            route('manage.learning.download.csv'),
        ];

        foreach ($routes as $url) {
            $guestResp = $this->get($url);
            $this->assertTrue($guestResp->isRedirect() || in_array($guestResp->getStatusCode(), [401, 403], true));

            if ($this->regularUser) {
                $unauthResp = $this->actingAs($this->regularUser)->get($url);
                $unauthResp->assertStatus(403);
            }
        }
    }

    /**
     * TEST 8: Data Parity - Ensure Excel query and CSV/PDF query use the exact same base query builder.
     */
    public function test_filter_and_data_parity(): void
    {
        $filters = [
            'content_slug' => 'hr-a3-kompensasi-dan-kesejahteraaan-karyawan-2025',
            'status'       => 'finished',
            'year'         => '2026',
        ];

        // 1. Count from shared query builder (source of truth for Excel, CSV, PDF)
        $expectedCount = LearningDatatableService::buildBaseQuery($filters)->count();

        // 2. Fetch CSV rows for this filter
        $respCsv = $this->actingAs($this->admin)->get(route('manage.learning.download.csv', $filters));
        $respCsv->assertStatus(200);

        $csvContent = file_get_contents($respCsv->getFile()->getPathname());

        // Count lines minus BOM/header and empty trailing line
        $csvLines = array_filter(explode("\n", trim($csvContent)));
        $csvRowCount = max(0, count($csvLines) - 1); // 1 header line

        $this->assertEquals($expectedCount, $csvRowCount, "CSV row count must match query builder count");
    }
}
