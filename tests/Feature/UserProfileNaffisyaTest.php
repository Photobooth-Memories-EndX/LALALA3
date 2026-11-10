<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class UserProfileNaffisyaTest extends TestCase
{
    protected User $admin;
    protected string $targetHash = 'b62d6b06586688eabc2cc28d86e4e4764f105eff';

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('role', '2')->first() ?? User::first();
    }

    public function test_naffisya_profile_loads_with_action_column_and_modal(): void
    {
        $response = $this->actingAs($this->admin)->get(route('user.manage.profile', $this->targetHash));

        $response->assertStatus(200);
        $response->assertSee('MOHAMAD NAFFISYA KANCANA GUMILANG');
        $response->assertSee('129458');

        // Check Action column header
        $response->assertSee('>ACTION<', false);

        // Check Action button icon in script render
        $response->assertSee('ti ti-dots-vertical');

        // Check Action menu items in script render
        $response->assertSee('More Information');
        $response->assertSee('View Content');
        $response->assertSee('Edit Record');

        // Check Learning Detail Modal container in DOM
        $response->assertSee('id="learningDetailModal"', false);
        $response->assertSee('id="profile-table-entries-select"', false);
    }

    public function test_naffisya_datatable_returns_rows(): void
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

    public function test_naffisya_exports_succeed(): void
    {
        $excel = $this->actingAs($this->admin)->get(route('user.manage.profile.export.excel', $this->targetHash));
        $excel->assertStatus(200);

        $csv = $this->actingAs($this->admin)->get(route('user.manage.profile.export.csv', $this->targetHash));
        $csv->assertStatus(200);

        $pdf = $this->actingAs($this->admin)->get(route('user.manage.profile.export.pdf', $this->targetHash));
        $pdf->assertStatus(200);
    }
}
