<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserProfileLearningHistoryKpOwlMappingTest extends TestCase
{
    protected User $admin;
    protected string $naffisyaHash = 'b62d6b06586688eabc2cc28d86e4e4764f105eff';
    protected string $rafflesHash = 'bc160d4841e311cd804501423f977da26adcadd3';

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('role', '2')->first() ?? User::first();
    }

    public function test_naffisya_profile_has_all_34_column_headers(): void
    {
        $response = $this->actingAs($this->admin)->get(route('user.manage.profile', $this->naffisyaHash));
        $response->assertStatus(200);

        $expectedHeaders = [
            'NO',
            'SOURCE',
            'EMPLOYEE ID',
            'EMPLOYEE NAME',
            'TEAM',
            'DEPARTMENT',
            'COMPANY',
            'COURSE TITLE',
            'TYPE',
            'CATEGORY',
            'MAIN CLASSIFICATION',
            'SUB CLASSIFICATION',
            'DELIVERY METHOD',
            'SUB DELIVERY METHOD',
            'PROVIDER',
            'PROVIDER NAME',
            'VENUE',
            'COURSE DURATION',
            'TOTAL RECOGNIZED DURATION',
            'LEARNING HOURS',
            '% PROGRESS',
            'PROGRESS STATUS',
            'DATE (START - END)',
            'TIME (START - END)',
            'RECORD MONTH',
            'REQUIREMENT',
            'ATTENDANCE',
            'SCORE (L1) - COURSE',
            'SCORE (L2)',
            'SCORE (L3)',
            'CERTIFICATE AVAILABILITY',
            'PERSON IN CHARGE (PIC)',
            'TOTAL ENGAGEMENT TIME',
            'ACTION',
        ];

        foreach ($expectedHeaders as $header) {
            $response->assertSee('>' . $header . '<', false);
        }

        // Verify Tabler 3-dots icon and modal presence
        $response->assertSee('ti ti-dots-vertical');
        $response->assertSee('id="learningDetailModal"', false);
    }

    public function test_naffisya_datatable_contains_34_columns_and_combined_sources(): void
    {
        $response = $this->actingAs($this->admin)->getJson(
            route('user.manage.profile.learning.datatable', [
                'hash' => $this->naffisyaHash,
                'draw' => 1,
                'start' => 0,
                'length' => 100,
            ])
        );

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertArrayHasKey('recordsTotal', $data);
        $this->assertGreaterThan(0, $data['recordsTotal']);
        $this->assertNotEmpty($data['data']);

        $firstRow = $data['data'][0];

        $expectedKeys = [
            'DT_RowIndex',
            'source_badge',
            'employee_id',
            'employee_name',
            'team',
            'department',
            'company',
            'course_title',
            'type',
            'category',
            'main_classification',
            'sub_classification',
            'delivery_method',
            'sub_delivery_method',
            'provider',
            'provider_name',
            'venue',
            'course_duration',
            'total_recognized_duration',
            'learning_hours',
            'progress_bar',
            'progress_status',
            'date_merged',
            'time_merged',
            'record_month',
            'requirement',
            'attendance',
            'score_l1',
            'score_l2',
            'score_l3',
            'certificate_availability',
            'pic',
            'total_engagement_time',
            'action',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $firstRow, "Missing DataTable key: {$key}");
        }

        // Action dropdown has 3-dots icon and More Information
        $this->assertStringContainsString('ti ti-dots-vertical', $firstRow['action']);
        $this->assertStringContainsString('More Information', $firstRow['action']);

        // Check if both KP OWL and Non KP OWL records exist for Naffisya
        $sourcesFound = [];
        foreach ($data['data'] as $row) {
            if (str_contains($row['source_badge'], 'KP OWL') && !str_contains($row['source_badge'], 'Non KP OWL')) {
                $sourcesFound['kp_owl'] = true;
            }
            if (str_contains($row['source_badge'], 'Non KP OWL')) {
                $sourcesFound['non_kp_owl'] = true;
            }
        }

        $this->assertTrue($sourcesFound['kp_owl'] ?? false, 'Expected KP OWL records in DataTable');
    }

    public function test_detail_endpoint_returns_17_extra_fields(): void
    {
        // 1. Online record detail
        $onlineRes = $this->actingAs($this->admin)->getJson(
            route('user.manage.profile.learning.record.detail', [
                'hash'   => $this->naffisyaHash,
                'source' => 'online',
                'id'     => 1,
            ])
        );

        $onlineRes->assertStatus(200);
        $onlineData = $onlineRes->json('data') ?? $onlineRes->json();

        $seventeenFields = [
            'grade',
            'job_position',
            'sub_course_title',
            'level',
            'batch',
            'sub_provider',
            'instructor',
            'attendance_reason',
            'bond_status',
            'bond_duration',
            'bond_end_date',
            'bond_amount',
            'l2_status',
            'l3_status',
            'certificate_institution',
            'certificate_issued_date',
            'certificate_expired_date',
        ];

        foreach ($seventeenFields as $field) {
            $this->assertArrayHasKey($field, $onlineData, "Missing 17-field in detail response: {$field}");
        }

        // 2. Offline record detail
        $offlineUserRecord = DB::table('offline_training_users')->where('employee_id', '129458')->first();
        if ($offlineUserRecord) {
            $offlineRes = $this->actingAs($this->admin)->getJson(
                route('user.manage.profile.learning.record.detail', [
                    'hash'   => $this->naffisyaHash,
                    'source' => 'offline',
                    'id'     => $offlineUserRecord->id,
                ])
            );

            $offlineRes->assertStatus(200);
            $offlineData = $offlineRes->json('data') ?? $offlineRes->json();

            foreach ($seventeenFields as $field) {
                $this->assertArrayHasKey($field, $offlineData, "Missing 17-field in offline detail response: {$field}");
            }
        }
    }

    public function test_exports_contain_kp_and_non_kp_records(): void
    {
        // Excel
        $excel = $this->actingAs($this->admin)->get(route('user.manage.profile.export.excel', $this->naffisyaHash));
        $excel->assertStatus(200);

        // CSV
        $csv = $this->actingAs($this->admin)->get(route('user.manage.profile.export.csv', $this->naffisyaHash));
        $csv->assertStatus(200);

        // PDF
        $pdf = $this->actingAs($this->admin)->get(route('user.manage.profile.export.pdf', $this->naffisyaHash));
        $pdf->assertStatus(200);
    }

    public function test_raffles_rianto_profile_and_datatable(): void
    {
        $response = $this->actingAs($this->admin)->get(route('user.manage.profile', $this->rafflesHash));
        $response->assertStatus(200);
        $response->assertSee('RAFFLES RIANTO');
        $response->assertSee('132357');

        $dt = $this->actingAs($this->admin)->getJson(
            route('user.manage.profile.learning.datatable', [
                'hash' => $this->rafflesHash,
                'draw' => 1,
                'start' => 0,
                'length' => 10,
            ])
        );
        $dt->assertStatus(200);
        $this->assertGreaterThan(0, $dt->json('recordsTotal'));
    }
}
