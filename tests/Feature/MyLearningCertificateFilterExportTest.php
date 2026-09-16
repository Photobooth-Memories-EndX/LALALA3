<?php

namespace Tests\Feature;

use App\Exports\CertificateExport;
use App\Models\Certificate;
use App\Models\CertificateSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class MyLearningCertificateFilterExportTest extends TestCase
{
    use DatabaseTransactions;

    protected User $userA;
    protected User $userB;
    protected CertificateSetting $settingAcme;
    protected CertificateSetting $settingGlobal;
    protected Certificate $cert1;
    protected Certificate $cert2;
    protected Certificate $cert3;
    protected Certificate $certOther;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = User::factory()->create([
            'name'       => 'User A Learner',
            'team'       => 'QA Team',
            'department' => 'Operations',
            'division'   => 'Plant',
            'ssohash'    => 'cert-a-' . Str::uuid(),
            'sap_code'   => 'SAP-A-' . rand(1000, 9999),
            'id_emp'     => 'EMP-A-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->userB = User::factory()->create([
            'name'       => 'User B Other',
            'team'       => 'QA Team',
            'department' => 'Operations',
            'division'   => 'Plant',
            'ssohash'    => 'cert-b-' . Str::uuid(),
            'sap_code'   => 'SAP-B-' . rand(1000, 9999),
            'id_emp'     => 'EMP-B-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->settingAcme = CertificateSetting::create([
            'title'            => 'Acme Setting',
            'certificate_type' => 'content',
            'issuer_name'      => 'Acme Academy',
        ]);

        $this->settingGlobal = CertificateSetting::create([
            'title'            => 'Global Setting',
            'certificate_type' => 'playlist',
            'issuer_name'      => 'Global Institute',
        ]);

        // Cert 1: User A, Content, Aug 2026, Acme Academy
        $this->cert1 = Certificate::create([
            'user_id'                => $this->userA->id,
            'certificate_setting_id' => $this->settingAcme->id,
            'certificate_type'       => 'content',
            'certificate_number'     => 'OWL-CERT-TEST-A1-' . rand(100, 999),
            'credential_id'          => 'CRED-A1-' . Str::random(6),
            'title'                  => 'Laravel Advanced Security Course',
            'participant_name'       => $this->userA->name,
            'score'                  => 88,
            'issued_at'              => '2026-08-10 10:00:00',
        ]);

        // Cert 2: User A, Playlist, Sep 2026, Global Institute
        $this->cert2 = Certificate::create([
            'user_id'                => $this->userA->id,
            'certificate_setting_id' => $this->settingGlobal->id,
            'certificate_type'       => 'playlist',
            'certificate_number'     => 'OWL-CERT-TEST-A2-' . rand(100, 999),
            'credential_id'          => 'CRED-A2-' . Str::random(6),
            'title'                  => 'Quality Engineering Track',
            'participant_name'       => $this->userA->name,
            'score'                  => 95,
            'issued_at'              => '2026-09-02 10:00:00',
        ]);

        // Cert 3: User A, Content, May 2025, Acme Academy
        $this->cert3 = Certificate::create([
            'user_id'                => $this->userA->id,
            'certificate_setting_id' => $this->settingAcme->id,
            'certificate_type'       => 'content',
            'certificate_number'     => 'OWL-CERT-TEST-A3-' . rand(100, 999),
            'credential_id'          => 'CRED-A3-' . Str::random(6),
            'title'                  => 'Historical Basics 2025 Course',
            'participant_name'       => $this->userA->name,
            'score'                  => 75,
            'issued_at'              => '2025-05-15 10:00:00',
        ]);

        // Cert 4: User B (Other User), Playlist, Sep 2026
        $this->certOther = Certificate::create([
            'user_id'                => $this->userB->id,
            'certificate_setting_id' => $this->settingGlobal->id,
            'certificate_type'       => 'playlist',
            'certificate_number'     => 'OWL-CERT-TEST-B1-' . rand(100, 999),
            'credential_id'          => 'CRED-B1-' . Str::random(6),
            'title'                  => 'Secret Other User Certificate',
            'participant_name'       => $this->userB->name,
            'score'                  => 90,
            'issued_at'              => '2026-09-02 10:00:00',
        ]);
    }

    /** 1. Certificate Type filter */
    public function test_1_certificate_type_filter(): void
    {
        $resp = $this->actingAs($this->userA)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'certificate', 'certificate_filter_type' => 'playlist']));

        $resp->assertOk();
        $resp->assertSee($this->cert2->title);
        $resp->assertDontSee($this->cert1->title);
        $resp->assertDontSee($this->cert3->title);

        $respContent = $this->actingAs($this->userA)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'certificate', 'certificate_filter_type' => 'content']));

        $respContent->assertOk();
        $respContent->assertSee($this->cert1->title);
        $respContent->assertSee($this->cert3->title);
        $respContent->assertDontSee($this->cert2->title);
    }

    /** 2. Certificate Year filter */
    public function test_2_certificate_year_filter(): void
    {
        $resp2026 = $this->actingAs($this->userA)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'certificate', 'certificate_filter_year' => '2026']));

        $resp2026->assertOk();
        $resp2026->assertSee($this->cert1->title);
        $resp2026->assertSee($this->cert2->title);
        $resp2026->assertDontSee($this->cert3->title);

        $resp2025 = $this->actingAs($this->userA)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'certificate', 'certificate_filter_year' => '2025']));

        $resp2025->assertOk();
        $resp2025->assertSee($this->cert3->title);
        $resp2025->assertDontSee($this->cert1->title);
        $resp2025->assertDontSee($this->cert2->title);
    }

    /** 3. Certificate Month filter */
    public function test_3_certificate_month_filter(): void
    {
        // Month by digit: 8 (August)
        $respMonth8 = $this->actingAs($this->userA)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'certificate', 'certificate_filter_month' => '8']));

        $respMonth8->assertOk();
        $respMonth8->assertSee($this->cert1->title);
        $respMonth8->assertDontSee($this->cert2->title);
        $respMonth8->assertDontSee($this->cert3->title);

        // Month by name: September
        $respMonthSep = $this->actingAs($this->userA)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'certificate', 'month' => 'September']));

        $respMonthSep->assertOk();
        $respMonthSep->assertSee($this->cert2->title);
        $respMonthSep->assertDontSee($this->cert1->title);
        $respMonthSep->assertDontSee($this->cert3->title);
    }

    /** 4. Certificate Search */
    public function test_4_certificate_search(): void
    {
        $respSearch = $this->actingAs($this->userA)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'certificate', 'certificate_search' => 'Quality']));

        $respSearch->assertOk();
        $respSearch->assertSee($this->cert2->title);
        $respSearch->assertDontSee($this->cert1->title);
        $respSearch->assertDontSee($this->cert3->title);
    }

    /** 5. More Filter: Issuer */
    public function test_5_more_filter_issuer(): void
    {
        $respIssuer = $this->actingAs($this->userA)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'certificate', 'certificate_issuer' => 'Global']));

        $respIssuer->assertOk();
        $respIssuer->assertSee($this->cert2->title);
        $respIssuer->assertDontSee($this->cert1->title);
        $respIssuer->assertDontSee($this->cert3->title);
    }

    /** 6. More Filter: Date From */
    public function test_6_more_filter_date_from(): void
    {
        $respDateFrom = $this->actingAs($this->userA)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'certificate', 'certificate_date_from' => '2026-09-01']));

        $respDateFrom->assertOk();
        $respDateFrom->assertSee($this->cert2->title);
        $respDateFrom->assertDontSee($this->cert1->title);
        $respDateFrom->assertDontSee($this->cert3->title);
    }

    /** 7. More Filter: Date To */
    public function test_7_more_filter_date_to(): void
    {
        $respDateTo = $this->actingAs($this->userA)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'certificate', 'certificate_date_to' => '2025-12-31']));

        $respDateTo->assertOk();
        $respDateTo->assertSee($this->cert3->title);
        $respDateTo->assertDontSee($this->cert1->title);
        $respDateTo->assertDontSee($this->cert2->title);
    }

    /** 8. More Filter: Sorting */
    public function test_8_more_filter_sorting(): void
    {
        $respAsc = $this->actingAs($this->userA)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'certificate', 'certificate_sort' => 'issued_asc']));

        $respAsc->assertOk();
        $html = $respAsc->getContent();
        // In asc order, 2025 (cert3) appears before 2026 (cert1, cert2)
        $posCert3 = strpos($html, $this->cert3->title);
        $posCert1 = strpos($html, $this->cert1->title);
        $this->assertNotFalse($posCert3);
        $this->assertNotFalse($posCert1);
        $this->assertLessThan($posCert1, $posCert3);
    }

    /** 9. Kombinasi Type + Year + Month + Search */
    public function test_9_combined_filters(): void
    {
        $resp = $this->actingAs($this->userA)
            ->get(route('user.learning.dashboard.partial', [
                'tab'                     => 'certificate',
                'certificate_filter_type' => 'playlist',
                'certificate_filter_year' => '2026',
                'certificate_filter_month'=> '9',
                'certificate_search'      => 'Quality',
            ]));

        $resp->assertOk();
        $resp->assertSee($this->cert2->title);
        $resp->assertDontSee($this->cert1->title);
        $resp->assertDontSee($this->cert3->title);
    }

    /** 10. Tombol Download Excel tersedia di HTML */
    public function test_10_export_excel_button_rendered_in_html(): void
    {
        $resp = $this->actingAs($this->userA)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'certificate']));

        $resp->assertOk();
        $resp->assertSee('id="certificateDownloadExcel"', false);
        $resp->assertSee('title="Download Excel"', false);
    }

    /** 11. Export Excel mengikuti filter aktif */
    public function test_11_export_excel_respects_active_filters(): void
    {
        $resp = $this->actingAs($this->userA)
            ->get(route('user.learning.dashboard.partial', [
                'tab'                     => 'certificate',
                'certificate_export'      => '1',
                'certificate_filter_type' => 'playlist',
                'certificate_filter_year' => '2026',
            ]));

        $resp->assertOk();
        $this->assertStringContainsString('My_Certificates_', (string) $resp->headers->get('content-disposition'));
        $this->assertStringContainsString('.xlsx', (string) $resp->headers->get('content-disposition'));
        $this->assertEquals(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $resp->headers->get('content-type')
        );
    }

    /** 12. Export Excel row count sesuai filter dan hanya milik user login */
    public function test_12_export_row_count_and_user_isolation(): void
    {
        // User A exports type=content (should have cert1 & cert3 = 2 items)
        $ctrl = app(\App\Http\Controllers\LearningRecordController::class);
        $query = (new \ReflectionClass($ctrl))->getMethod('certificateListQuery')->invoke($ctrl, $this->userA->id);
        
        request()->query->replace(['certificate_filter_type' => 'content']);
        request()->request->replace(['certificate_filter_type' => 'content']);
        $filteredQuery = (new \ReflectionClass($ctrl))->getMethod('applyCertificateFilters')->invoke($ctrl, $query);

        $export = new CertificateExport($filteredQuery->get());
        $rows = $export->array();

        // 1 header row + 2 data rows
        $this->assertCount(3, $rows);
        $this->assertEquals('No', $rows[0][0]);
        $this->assertEquals('Certificate Number', $rows[0][1]);

        $exportedTitles = array_column(array_slice($rows, 1), 2);
        $this->assertContains($this->cert1->title, $exportedTitles);
        $this->assertContains($this->cert3->title, $exportedTitles);
        $this->assertNotContains($this->cert2->title, $exportedTitles);
        $this->assertNotContains($this->certOther->title, $exportedTitles);
    }

    /** 13. Bootstrap 5 attributes & no duplicate IDs */
    public function test_13_bootstrap_5_attributes_and_no_duplicate_ids(): void
    {
        $resp = $this->actingAs($this->userA)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'certificate']));

        $html = $resp->getContent();

        // Dropdown toggles must use data-bs-toggle
        $this->assertStringContainsString('data-bs-toggle="dropdown"', $html);
        $this->assertStringNotContainsString('data-toggle="dropdown"', $html);

        // Modal triggers must use data-bs-toggle & data-bs-target
        $this->assertStringContainsString('data-bs-toggle="modal"', $html);
        $this->assertStringContainsString('data-bs-target="#certificateMoreFiltersModal"', $html);
        $this->assertStringNotContainsString('data-toggle="modal"', $html);

        // Modal apply button must have unique ID certificateModalApplyFilter
        $this->assertStringContainsString('id="certificateModalApplyFilter"', $html);
        $this->assertStringContainsString('id="certificateApplyFilter"', $html);

        // Verify ID certificateApplyFilter appears exactly once
        $count = substr_count($html, 'id="certificateApplyFilter"');
        $this->assertEquals(1, $count, "id='certificateApplyFilter' must appear exactly once");
    }
}
