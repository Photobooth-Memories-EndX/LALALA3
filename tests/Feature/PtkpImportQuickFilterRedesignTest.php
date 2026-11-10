<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class PtkpImportQuickFilterRedesignTest extends TestCase
{
    protected ?User $admin = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::whereIn('role', [2, 3])->first() ?? User::first();
        if ($this->admin) {
            $this->actingAs($this->admin);
        }
    }

    /** @test */
    public function ptkp_import_page_loads_with_clickable_summary_cards_and_no_old_pills()
    {
        $response = $this->get(route('manage.record.offline.import-ptkp'));
        $response->assertStatus(200);

        // Verify Summary Cards container & 6 filter cards are present
        $response->assertSee('id="ptkp-summary-cards-container"', false);
        $response->assertSee('ptkp-summary-filter-card', false);
        $response->assertSee('data-filter="all"', false);
        $response->assertSee('data-filter="valid"', false);
        $response->assertSee('data-filter="warning"', false);
        $response->assertSee('data-filter="duplicate"', false);
        $response->assertSee('data-filter="invalid"', false);
        $response->assertSee('data-filter="invalid_company"', false);

        // Verify Default Active Card is Total Rows (data-filter="all")
        $response->assertSee('data-filter="all" role="button" tabindex="0"', false);

        // Verify Old Quick Filter Pills are REMOVED
        $response->assertDontSee('ptkp-filter-tab', false);
        $response->assertDontSee('count-tab-all', false);
        $response->assertDontSee('count-tab-valid', false);

        // Verify Table Loading Overlay is present
        $response->assertSee('id="ptkp-table-loading"', false);
        $response->assertSee('ptkp-table-loading-overlay', false);

        // Verify Filter Inputs & Reset Button are present
        $response->assertSee('id="ptkp-search-empid"', false);
        $response->assertSee('id="ptkp-search-name"', false);
        $response->assertSee('id="ptkp-search-course"', false);
        $response->assertSee('id="ptkp-search-pic"', false);
        $response->assertSee('id="ptkp-btn-reset-filters"', false);

        // Verify Show Entries dropdown and Pagination are present
        $response->assertSee('id="ptkp-page-size"', false);
        $response->assertSee('id="ptkp-pagination-nav"', false);
        $response->assertSee('id="ptkp-pagination-info"', false);
    }

    /** @test */
    public function ptkp_javascript_handles_card_filtering_loading_and_combined_queries()
    {
        $jsPath = public_path('js/manage/ptkp-import.js');
        $this->assertFileExists($jsPath);
        $js = file_get_contents($jsPath);

        // 1. Summary Card click handler
        $this->assertStringContainsString('.ptkp-summary-filter-card', $js);
        $this->assertStringContainsString('isFiltering', $js);
        $this->assertStringContainsString('$tableLoading.stop(true, true).fadeIn', $js);
        $this->assertStringContainsString('$tableLoading.stop(true, true).fadeOut', $js);

        // 2. Keyboard accessibility (Enter / Space)
        $this->assertStringContainsString("e.key === 'Enter'", $js);

        // 3. Reset filters handler
        $this->assertStringContainsString('#ptkp-btn-reset-filters', $js);

        // 4. Combined filtering logic for all 5 statuses
        $this->assertStringContainsString("row.status !== 'VALID'", $js);
        $this->assertStringContainsString("row.status !== 'WARNING'", $js);
        $this->assertStringContainsString("row.status !== 'DUPLICATE'", $js);
        $this->assertStringContainsString("row.status !== 'INVALID'", $js);
        $this->assertStringContainsString("row.status !== 'INVALID COMPANY'", $js);

        // 5. Combined text filters
        $this->assertStringContainsString('searchQuery.empId', $js);
        $this->assertStringContainsString('searchQuery.name', $js);
        $this->assertStringContainsString('searchQuery.course', $js);
        $this->assertStringContainsString('searchQuery.pic', $js);

        // 6. Reset to Total Rows on preview load
        $this->assertStringContainsString("currentFilter = 'all'", $js);
        $this->assertStringContainsString('.ptkp-summary-filter-card[data-filter="all"]', $js);
    }

    /** @test */
    public function ptkp_css_defines_active_states_loading_overlay_and_dark_mode()
    {
        $cssPath = public_path('css/manage/ptkp-import.css');
        $this->assertFileExists($cssPath);
        $css = file_get_contents($cssPath);

        // Summary filter card styles
        $this->assertStringContainsString('.ptkp-summary-filter-card', $css);
        $this->assertStringContainsString('cursor: pointer', $css);
        $this->assertStringContainsString('.ptkp-summary-filter-card:hover', $css);

        // Active state borders and rings
        $this->assertStringContainsString('.ptkp-summary-filter-card[data-filter="all"].active', $css);
        $this->assertStringContainsString('.ptkp-summary-filter-card.ptkp-kpi-valid.active', $css);
        $this->assertStringContainsString('.ptkp-summary-filter-card.ptkp-kpi-warning.active', $css);
        $this->assertStringContainsString('.ptkp-summary-filter-card.ptkp-kpi-duplicate.active', $css);
        $this->assertStringContainsString('.ptkp-summary-filter-card.ptkp-kpi-invalid.active', $css);
        $this->assertStringContainsString('.ptkp-summary-filter-card.ptkp-kpi-invalid-company.active', $css);

        // Loading overlay
        $this->assertStringContainsString('.ptkp-table-loading-overlay', $css);
        $this->assertStringContainsString('backdrop-filter: blur(2px)', $css);

        // Dark mode support
        $this->assertStringContainsString('[data-bs-theme="dark"] .ptkp-summary-filter-card', $css);
        $this->assertStringContainsString('[data-bs-theme="dark"] .ptkp-table-loading-overlay', $css);
    }
}
