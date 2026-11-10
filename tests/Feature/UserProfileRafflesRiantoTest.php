<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class UserProfileRafflesRiantoTest extends TestCase
{
    protected User $admin;
    protected string $targetHash = 'bc160d4841e311cd804501423f977da26adcadd3';

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('role', '2')->first() ?? User::first();
    }

    public function test_target_user_profile_loads_successfully_with_compact_header(): void
    {
        $response = $this->actingAs($this->admin)->get(route('user.manage.profile', $this->targetHash));

        $response->assertStatus(200);
        $response->assertSee('RAFFLES RIANTO');
        $response->assertSee('132357');
        $response->assertSee('Employee Learning Profile');

        // Compact KPI stats
        $response->assertSee('Total Learning');
        $response->assertSee('Ongoing');
        $response->assertSee('Finished');
        $response->assertSee('Completion');

        // Toolbar and DataTable elements
        $response->assertSee('id="MyTable"', false);
        $response->assertSee('id="profile-table-entries-select"', false);
        $response->assertSee('id="profile-table-info-container"', false);
        $response->assertSee('id="profile-table-pagination-container"', false);
        $response->assertSee('id="profileMoreFiltersModal"', false);
    }

    public function test_target_user_learning_datatable_returns_valid_data(): void
    {
        $response = $this->actingAs($this->admin)->getJson(
            route('user.manage.profile.learning.datatable', [
                'hash' => $this->targetHash,
                'draw' => 1,
                'start' => 0,
                'length' => 10,
            ])
        );

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data',
        ]);
        $this->assertGreaterThan(0, $response->json('recordsTotal'));
    }

    public function test_target_user_exports_return_200(): void
    {
        // Excel
        $excel = $this->actingAs($this->admin)->get(route('user.manage.profile.export.excel', $this->targetHash));
        $excel->assertStatus(200);
        $excel->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        // CSV
        $csv = $this->actingAs($this->admin)->get(route('user.manage.profile.export.csv', $this->targetHash));
        $csv->assertStatus(200);
        $this->assertStringContainsString('.csv', $csv->headers->get('content-disposition'));

        // PDF
        $pdf = $this->actingAs($this->admin)->get(route('user.manage.profile.export.pdf', $this->targetHash));
        $pdf->assertStatus(200);
        $pdf->assertHeader('content-type', 'application/pdf');
    }
}
