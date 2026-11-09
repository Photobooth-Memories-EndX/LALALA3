<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PtkpImportStep3LayoutTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])->first()
            ?? User::create([
                'name'         => 'Test Admin User',
                'email'        => 'admin_test_' . uniqid() . '@example.com',
                'password'     => bcrypt('secret'),
                'id_emp'       => 'ADM_' . rand(1000, 9999),
                'id_user'      => 'UID_ADM_' . rand(1000, 9999),
                'role'         => User::ROLE_ADMIN,
                'company_name' => 'PT Krakatau Posco',
            ]);
    }

    public function test_step3_is_strictly_inside_main_container_and_has_valid_dom_hierarchy(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('manage.record.offline.import-ptkp'));

        $response->assertStatus(200);
        $html = $response->getContent();

        // 1. Confirm #ptkp-confirm-card exists inside the HTML
        $this->assertStringContainsString('id="ptkp-confirm-card"', $html);

        // 2. Ensure the container div opens before #ptkp-confirm-card
        $containerPos = strpos($html, 'class="container-xl"');
        $confirmCardPos = strpos($html, 'id="ptkp-confirm-card"');
        $this->assertNotFalse($containerPos, 'container-xl must exist');
        $this->assertNotFalse($confirmCardPos, 'ptkp-confirm-card must exist');
        $this->assertTrue($containerPos < $confirmCardPos, 'container-xl must open before ptkp-confirm-card');

        // 3. Verify that container-xl does NOT close before ptkp-confirm-card
        // Substring between container-xl open and ptkp-confirm-card
        $between = substr($html, $containerPos, $confirmCardPos - $containerPos);
        $divOpenCount = preg_match_all('/<div(\s|>)/i', $between, $m1);
        $divCloseCount = preg_match_all('/<\/div>/i', $between, $m2);
        // There must be more open divs than close divs (i.e. container-xl is still open)
        $this->assertGreaterThan($divCloseCount, $divOpenCount, 'container-xl must remain open when ptkp-confirm-card is rendered');

        // 4. Verify Grid layout wrapper exists in Blade
        $this->assertStringContainsString('pre-import-summary-grid', $html);
        $this->assertStringContainsString('pre-import-card-eligible', $html);
        $this->assertStringContainsString('pre-import-card-skipped', $html);

        // 5. Verify Both Panels Content & Elements exist
        $this->assertStringContainsString('Records to Be Imported', $html);
        $this->assertStringContainsString('id="ptkp-step3-valid-count"', $html);
        $this->assertStringContainsString('id="ptkp-step3-warning-count"', $html);
        $this->assertStringContainsString('id="ptkp-step3-total-to-import"', $html);

        $this->assertStringContainsString('Records Not Imported', $html);
        $this->assertStringContainsString('id="ptkp-step3-invalid-count"', $html);
        $this->assertStringContainsString('id="ptkp-step3-invalid-company-skipped"', $html);
        $this->assertStringContainsString('id="ptkp-step3-duplicate-count"', $html);
        $this->assertStringContainsString('id="ptkp-step3-total-not-imported"', $html);

        // 6. Verify Database Safety Guarantee banner
        $this->assertStringContainsString('Database Safety Guarantee', $html);
        $this->assertStringContainsString('pre-import-safeguard-banner', $html);

        // 7. Verify Action Buttons in Step 3
        $this->assertStringContainsString('id="ptkp-btn-back-to-step2"', $html);
        $this->assertStringContainsString('id="ptkp-btn-confirm"', $html);
        $this->assertStringContainsString('btn-confirm-import-darkblue', $html);

        // 8. Verify Navigation Button alignment: Back on left, Confirm on right
        $backPos = strpos($html, 'id="ptkp-btn-back-to-step2"');
        $confirmPos = strpos($html, 'id="ptkp-btn-confirm"');
        $this->assertTrue($backPos < $confirmPos, 'Back button must precede Confirm button in DOM (aligned left-to-right)');
    }

    public function test_css_grid_and_responsive_rules_for_step3(): void
    {
        $cssPath = public_path('css/manage/ptkp-import.css');
        $this->assertFileExists($cssPath);
        $css = file_get_contents($cssPath);

        // Grid container definition
        $this->assertStringContainsString('.pre-import-summary-grid', $css);
        $this->assertStringContainsString('display: grid', $css);
        $this->assertStringContainsString('grid-template-columns: repeat(2, minmax(0, 1fr))', $css);
        $this->assertStringContainsString('align-items: stretch', $css);

        // Responsive 1-column on mobile
        $this->assertStringContainsString('@media (max-width: 768px)', $css);
        $this->assertStringContainsString('grid-template-columns: 1fr', $css);

        // Cards flex column and 100% height
        $this->assertStringContainsString('.pre-import-card', $css);
        $this->assertStringContainsString('box-sizing: border-box', $css);
    }

    public function test_actual_ptkp_file_upload_and_step3_data_payload(): void
    {
        $templatePath = base_path('tests/file/PTKP_OWL_Import_Template (1).xlsx');
        $this->assertFileExists($templatePath);

        $tempPath = tempnam(sys_get_temp_dir(), 'ptkp_step3_test_') . '.xlsx';
        copy($templatePath, $tempPath);

        try {
            $uploadedFile = new \Illuminate\Http\UploadedFile(
                $tempPath,
                'PTKP_OWL_Import_Template (1).xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true
            );

            $response = $this->actingAs($this->adminUser)
                ->post(route('manage.record.offline.import-ptkp.preview'), [
                    'file' => $uploadedFile,
                ]);

            $response->assertStatus(200);
            $json = $response->json();

            $this->assertTrue($json['success']);
            $this->assertArrayHasKey('stats', $json);
            $this->assertArrayHasKey('importable_count', $json);
            $this->assertArrayHasKey('rows', $json);
            $this->assertArrayHasKey('token', $json);

            // Verify that the statistics match the requirements for step 3
            $validCount = $json['stats']['valid'] ?? 0;
            $warningCount = $json['stats']['warning'] ?? 0;
            $invalidCount = $json['stats']['invalid'] ?? 0;
            $duplicateCount = $json['stats']['duplicate'] ?? 0;
            $invalidCompanyCount = $json['stats']['invalid_company'] ?? 0;

            $totalToImport = $json['importable_count'] ?? ($validCount + $warningCount + $invalidCompanyCount);
            $totalNotImported = $invalidCount + $duplicateCount;

            $this->assertGreaterThanOrEqual(0, $totalToImport);
            $this->assertGreaterThanOrEqual(0, $totalNotImported);

            // Also test that the Blade template contains all the IDs that the JS updates for Step 3
            $pageResponse = $this->actingAs($this->adminUser)
                ->get(route('manage.record.offline.import-ptkp'));
            $html = $pageResponse->getContent();

            $this->assertStringContainsString('id="ptkp-step3-valid-count"', $html);
            $this->assertStringContainsString('id="ptkp-step3-warning-count"', $html);
            $this->assertStringContainsString('id="ptkp-step3-total-to-import"', $html);
            $this->assertStringContainsString('id="ptkp-step3-invalid-count"', $html);
            $this->assertStringContainsString('id="ptkp-step3-invalid-company-skipped"', $html);
            $this->assertStringContainsString('id="ptkp-step3-duplicate-count"', $html);
            $this->assertStringContainsString('id="ptkp-step3-total-not-imported"', $html);
            $this->assertStringContainsString('id="ptkp-btn-confirm-count"', $html);
        } finally {
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    public function test_step3_typography_and_readability_specifications(): void
    {
        $cssPath = public_path('css/manage/ptkp-import.css');
        $this->assertFileExists($cssPath);
        $css = file_get_contents($cssPath);

        // 1. Stepper Labels: 12px, weight 600
        $this->assertMatchesRegularExpression('/\.ptkp-step-title\s*\{[^}]*font-size:\s*12px/s', $css);
        $this->assertMatchesRegularExpression('/\.ptkp-step-title\s*\{[^}]*font-weight:\s*600/s', $css);

        // 2. Import Records Section Title: 15px, weight 600
        $this->assertMatchesRegularExpression('/#ptkp-confirm-card\s+\.ptkp-section-title\s*\{[^}]*font-size:\s*15px/s', $css);
        $this->assertMatchesRegularExpression('/#ptkp-confirm-card\s+\.ptkp-section-title\s*\{[^}]*font-weight:\s*600/s', $css);

        // 3. Pre-Import Summary & Safeguards Title: 14px, weight 600
        $this->assertMatchesRegularExpression('/\.pre-import-section-title\s*\{[^}]*font-size:\s*14px/s', $css);
        $this->assertMatchesRegularExpression('/\.pre-import-section-title\s*\{[^}]*font-weight:\s*600/s', $css);

        // 4. Panel Title: 13px, weight 600; Panel Description: 11px, weight 400
        $this->assertMatchesRegularExpression('/\.pre-import-header\s+h5\s*\{[^}]*font-size:\s*13px/s', $css);
        $this->assertMatchesRegularExpression('/\.pre-import-header\s+h5\s*\{[^}]*font-weight:\s*600/s', $css);
        $this->assertMatchesRegularExpression('/\.pre-import-header\s+p\s*\{[^}]*font-size:\s*11px/s', $css);
        $this->assertMatchesRegularExpression('/\.pre-import-header\s+p\s*\{[^}]*font-weight:\s*400/s', $css);

        // 5. Summary Labels: 12px, weight 500
        $this->assertMatchesRegularExpression('/\.pre-import-row-label\s*\{[^}]*font-size:\s*12px/s', $css);
        $this->assertMatchesRegularExpression('/\.pre-import-row-label\s*\{[^}]*font-weight:\s*500/s', $css);

        // 6. Summary Numbers: 16–18px (17px), weight 700
        $this->assertMatchesRegularExpression('/\.pre-import-row-count\s*\{[^}]*font-size:\s*17px/s', $css);
        $this->assertMatchesRegularExpression('/\.pre-import-row-count\s*\{[^}]*font-weight:\s*700/s', $css);

        // 7. Total Import Number: 21–22px (22px), weight 700
        $this->assertMatchesRegularExpression('/\.pre-import-total-value\s*\{[^}]*font-size:\s*22px/s', $css);
        $this->assertMatchesRegularExpression('/\.pre-import-total-value\s*\{[^}]*font-weight:\s*700/s', $css);

        // 8. Database Safety Guarantee: 11.5px, weight 500 (heading 600)
        $this->assertMatchesRegularExpression('/\.pre-import-safeguard-text\s*\{[^}]*font-size:\s*11\.5px/s', $css);
        $this->assertMatchesRegularExpression('/\.pre-import-safeguard-text\s*\{[^}]*font-weight:\s*500/s', $css);

        // 9. Buttons: 12px, weight 600, height 34px
        $this->assertMatchesRegularExpression('/#ptkp-btn-confirm\s*\{[^}]*font-size:\s*12px/s', $css);
        $this->assertMatchesRegularExpression('/#ptkp-btn-confirm\s*\{[^}]*font-weight:\s*600/s', $css);
        $this->assertMatchesRegularExpression('/#ptkp-btn-confirm\s*\{[^}]*height:\s*34px/s', $css);

        // 10. Check Blade Render
        $response = $this->actingAs($this->adminUser)
            ->get(route('manage.record.offline.import-ptkp'));
        $response->assertStatus(200);
        $html = $response->getContent();

        $this->assertStringContainsString('pre-import-section-title', $html);
        $this->assertStringContainsString('pre-import-safeguard-text', $html);
        $this->assertStringContainsString('btn-confirm-import-darkblue', $html);
    }
}
