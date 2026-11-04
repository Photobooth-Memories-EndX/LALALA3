<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class PtkpImportUiUxImprovementTest extends TestCase
{
    protected ?User $admin = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])->first()
            ?? User::first();

        if ($this->admin) {
            $this->actingAs($this->admin);
        }
    }

    /** @test */
    public function ptkp_import_page_is_completely_in_english_with_no_indonesian_text()
    {
        $response = $this->get(route('manage.record.offline.import-ptkp'));
        $response->assertStatus(200);

        // Pre-Import Step 3 Labels
        $response->assertSee('Records to Be Imported', false);
        $response->assertSee('Records Not Imported', false);
        $response->assertSee('Invalid Employee ID or Course', false);
        $response->assertSee('Invalid Company (Skipped)', false);
        $response->assertSee('Total to Import', false);
        $response->assertSee('Total Not Imported', false);
        $response->assertSee('Database Safety Guarantee:', false);
        $response->assertSee('Only records that pass validation will be imported into KP-OWL. Existing enrolled participants and duplicate entries will not be overwritten.', false);

        // Assert no Indonesian text in labels or headings
        $response->assertDontSee('Jumlah Record yang Akan Di-import', false);
        $response->assertDontSee('Jumlah Record yang Tidak Di-import', false);
        $response->assertDontSee('Konfirmasi & Import', false);
        $response->assertDontSee('di-import', false);

        // Assert old generic tooltip "Click to filter:" is completely removed
        $response->assertDontSee('title="Click to filter:', false);
    }

    /** @test */
    public function step2_indicators_have_compact_info_icons_and_accurate_tooltips()
    {
        $response = $this->get(route('manage.record.offline.import-ptkp'));
        $response->assertStatus(200);

        // Tooltip templates container & IDs
        $response->assertSee('id="ptkp-tooltip-templates"', false);
        $response->assertSee('id="ptkp-tt-content-total"', false);
        $response->assertSee('id="ptkp-tt-content-valid"', false);
        $response->assertSee('id="ptkp-tt-content-warning"', false);
        $response->assertSee('id="ptkp-tt-content-duplicate"', false);
        $response->assertSee('id="ptkp-tt-content-invalid"', false);
        $response->assertSee('id="ptkp-tt-content-invalid-company"', false);

        // 1. Total Rows
        $response->assertSee('Total Rows', false);
        $response->assertSee('Total Uploaded Records', false);
        $response->assertSee('The total number of data rows detected in the uploaded Excel file, excluding the header row.', false);
        $response->assertSee('Total dataset evaluated', false);
        $response->assertSee('data-tooltip-id="ptkp-tt-content-total"', false);

        // 2. VALID (Standardized Minimal Tooltip)
        $response->assertSee('VALID', false);
        $response->assertSee('Valid Records', false);
        $response->assertSee('These records have passed all validation checks and are ready to be imported into the system.', false);
        $response->assertDontSee('Employee ID exists and is recognized.', false);
        $response->assertDontSee('Validation Criteria', false);
        $response->assertSee('data-tooltip-id="ptkp-tt-content-valid"', false);

        // 3. WARNING (Standardized Minimal Tooltip)
        $response->assertSee('WARNING', false);
        $response->assertSee('Warning Records', false);
        $response->assertSee('These records contain potential issues or incomplete information that may require attention before importing.', false);
        $response->assertDontSee('The record has passed mandatory validation.', false);
        $response->assertDontSee('Will be imported with warnings', false);
        $response->assertSee('data-tooltip-id="ptkp-tt-content-warning"', false);

        // 4. DUPLICATE (Standardized Minimal Tooltip)
        $response->assertSee('DUPLICATE', false);
        $response->assertSee('Duplicate Records', false);
        $response->assertSee('These records have been identified as duplicates based on the system\'s existing duplicate detection rules. Importing them again could create duplicate training records.', false);
        $response->assertDontSee('The record matches an existing record or duplicate condition.', false);
        $response->assertDontSee('Duplicate records are excluded from the import process.', false);
        $response->assertSee('data-tooltip-id="ptkp-tt-content-duplicate"', false);

        // 5. INVALID (Standardized Minimal Tooltip)
        $response->assertSee('INVALID', false);
        $response->assertSee('Invalid Records', false);
        $response->assertSee('These records contain invalid or incomplete information and cannot be imported until the required data is corrected.', false);
        $response->assertDontSee('Employee ID is missing or cannot be recognized', false);
        $response->assertDontSee('How to Fix:', false);
        $response->assertSee('data-tooltip-id="ptkp-tt-content-invalid"', false);

        // 6. INVALID COMPANY (Standardized Minimal Tooltip)
        $response->assertSee('INVALID COMPANY', false);
        $response->assertSee('Invalid Company Records', false);
        $response->assertSee('These records contain missing or unsupported company information and cannot be imported under the applicable rules.', false);
        $response->assertDontSee('Company code is invalid, unmapped, or missing.', false);
        $response->assertDontSee('Verify that the company code matches', false);
        $response->assertSee('data-tooltip-id="ptkp-tt-content-invalid-company"', false);

        // Ensure info icons have tooltip attributes and html support
        $response->assertSee('ptkp-info-icon', false);
        $response->assertSee('data-bs-toggle="tooltip"', false);
        $response->assertSee('data-bs-html="true"', false);
    }

    /** @test */
    public function step3_import_success_has_dynamic_metrics_and_exact_three_action_buttons()
    {
        $response = $this->get(route('manage.record.offline.import-ptkp'));
        $response->assertStatus(200);

        // Success Notification Banner
        $response->assertSee('id="res-success-banner"', false);
        $response->assertSee('id="res-header-title"', false);
        $response->assertSee('id="res-header-desc"', false);

        // Import Success Rate
        $response->assertSee('Import Success Rate', false);
        $response->assertSee('id="res-rate-percentage"', false);
        $response->assertSee('id="res-rate-progress-bar"', false);
        $response->assertSee('id="res-rate-imported-count"', false);
        $response->assertSee('id="res-rate-skipped-count"', false);

        // 6 Summary Cards
        $response->assertSee('Total Records Processed', false);
        $response->assertSee('id="res-total-processed"', false);
        $response->assertSee('Successfully Imported', false);
        $response->assertSee('id="res-imported-participants"', false);
        $response->assertSee('Records Imported with Warnings', false);
        $response->assertSee('id="res-warnings-imported"', false);
        $response->assertSee('Duplicate Records Skipped', false);
        $response->assertSee('id="res-duplicates-skipped"', false);
        $response->assertSee('Invalid Employee ID / Course', false);
        $response->assertSee('id="res-invalid-skipped"', false);
        $response->assertSee('Invalid Company Records', false);
        $response->assertSee('id="res-invalid-company-imported"', false);

        // Additional Session Metadata
        $response->assertSee('Training Sessions Created:', false);
        $response->assertSee('id="res-trainings-created"', false);
        $response->assertSee('Sessions Reused:', false);
        $response->assertSee('id="res-trainings-reused"', false);

        // Exactly Three Navigation Buttons in Left and Right Groups
        $response->assertSee('id="ptkp-result-actions"', false);
        $response->assertSee('ptkp-result-action-left', false);
        $response->assertSee('ptkp-result-action-right', false);

        $response->assertSee('id="ptkp-btn-import-another"', false);
        $response->assertSee('Import Another File', false);
        $response->assertSee('ti-upload', false);

        $response->assertSee('id="ptkp-btn-view-records"', false);
        $response->assertSee('View Imported Records', false);
        $response->assertSee('ti-table', false);

        $response->assertSee('id="ptkp-btn-view-log"', false);
        $response->assertSee('View Import Log', false);
        $response->assertSee('ti-clipboard-list', false);
    }

    /** @test */
    public function ptkp_javascript_implements_tooltip_isolation_actual_success_rate_and_batch_log_navigation()
    {
        $jsPath = public_path('js/manage/ptkp-import.js');
        $this->assertFileExists($jsPath);
        $js = file_get_contents($jsPath);

        // Header Actions - Task 1: Import History hidden on Step 3
        $this->assertStringContainsString('updateHeaderActions', $js);
        $this->assertStringContainsString("importHistory.classList.add('d-none')", $js);

        // Tooltip initialization
        $this->assertStringContainsString('initTooltips', $js);
        $this->assertStringContainsString('bootstrap.Tooltip', $js);
        $this->assertStringContainsString('data-tooltip-id', $js);
        $this->assertStringContainsString('hideAllStatusTooltips', $js);

        // Desktop hover on summary cards
        $this->assertStringContainsString("mouseenter', '.ptkp-summary-filter-card'", $js);
        $this->assertStringContainsString("mouseleave', '.ptkp-summary-filter-card'", $js);

        // Tooltip click / keyboard isolation
        $this->assertStringContainsString('.ptkp-info-icon', $js);
        $this->assertStringContainsString('e.stopPropagation()', $js);

        // Success rate calculation with zero handling
        $this->assertStringContainsString('imported / total', $js);
        $this->assertStringContainsString('#res-rate-percentage', $js);
        $this->assertStringContainsString('#res-rate-progress-bar', $js);

        // Batch detail navigation for Button 3
        $this->assertStringContainsString('#ptkp-btn-view-log', $js);
        $this->assertStringContainsString('window.ptkpRoutes.historyDetail', $js);
        $this->assertStringContainsString('/detail', $js);

        // Clean reset for Button 1
        $this->assertStringContainsString('resetToStage1', $js);
    }

    /** @test */
    public function ptkp_css_defines_tooltips_and_responsive_three_button_layout()
    {
        $cssPath = public_path('css/manage/ptkp-import.css');
        $this->assertFileExists($cssPath);
        $css = file_get_contents($cssPath);

        // Tooltip styling
        $this->assertStringContainsString('.ptkp-info-icon', $css);
        $this->assertStringContainsString('.tooltip .tooltip-inner', $css);
        $this->assertStringContainsString('z-index: 1090 !important', $css);

        // Dark mode tooltip
        $this->assertStringContainsString('[data-bs-theme="dark"] .tooltip .tooltip-inner', $css);

        // Reorganized bottom action buttons (Left & Right groups, flexbox, consistent height)
        $this->assertStringContainsString('#ptkp-result-actions', $css);
        $this->assertStringContainsString('justify-content: space-between', $css);
        $this->assertStringContainsString('.ptkp-result-action-left', $css);
        $this->assertStringContainsString('.ptkp-result-action-right', $css);
        $this->assertStringContainsString('min-height: 42px', $css);
        $this->assertStringContainsString('@media (max-width: 767.98px)', $css);
    }
}
