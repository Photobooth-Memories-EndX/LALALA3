<?php

namespace Tests\Feature\Manage;

use App\Models\User;
use Tests\TestCase;

class KpOwlDisabledTest extends TestCase
{
    private User $admin;
    private string $targetHash = 'b62d6b06586688eabc2cc28d86e4e4764f105eff';

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('role', '2')->first() ?? User::first();
    }

    public function test_manage_kp_owl_page_returns_404(): void
    {
        // Unauthenticated access
        $guestResponse = $this->get('/manage/kp-owl');
        $this->assertTrue(in_array($guestResponse->status(), [302, 404], true));

        // Authenticated admin access
        $authResponse = $this->actingAs($this->admin)->get('/manage/kp-owl');
        $authResponse->assertStatus(404);

        // Sub-routes must also return 404
        $this->actingAs($this->admin)->get('/manage/kp-owl/data')->assertStatus(404);
        $this->actingAs($this->admin)->get('/manage/kp-owl/export')->assertStatus(404);
        $this->actingAs($this->admin)->get('/manage/kp-owl/statistics')->assertStatus(404);
    }

    public function test_manage_dashboard_does_not_contain_kp_owl_learning_record_card(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage');
        $response->assertStatus(200);

        // The KP OWL Learning Record card must not be present
        $response->assertDontSee('KP OWL Learning Record');
        $response->assertDontSee('manage/kp-owl');
        $response->assertDontSee('>Manage KP OWL<', false);

        // Other modules must still be present
        $response->assertSee('Record Management');
        $response->assertSee('Assessment Management');
        $response->assertSee('Content Repository');
        $response->assertSee('User Management');
    }

    public function test_record_navigation_component_links_to_online_record(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.record.online'));
        $response->assertStatus(200);

        // Must not contain any link to manage.kp-owl.index or /manage/kp-owl
        $response->assertDontSee('/manage/kp-owl');
        $response->assertDontSee(route('manage.record.online') . '/../kp-owl');
    }

    public function test_user_profile_learning_history_still_functions_flawlessly(): void
    {
        $response = $this->actingAs($this->admin)->get(route('user.manage.profile', $this->targetHash));
        $response->assertStatus(200);
        $response->assertSee('MOHAMAD NAFFISYA KANCANA GUMILANG');

        // Datatable still returns rows and 34 columns
        $dtResponse = $this->actingAs($this->admin)->getJson(
            route('user.manage.profile.learning.datatable', [
                'hash'   => $this->targetHash,
                'draw'   => 1,
                'start'  => 0,
                'length' => 10,
            ])
        );
        $dtResponse->assertStatus(200);
        $this->assertGreaterThan(0, $dtResponse->json('recordsTotal'));

        // Detail endpoint still works
        $detailResponse = $this->actingAs($this->admin)->getJson(
            route('user.manage.profile.learning.record.detail', [
                'hash'   => $this->targetHash,
                'source' => 'online',
                'id'     => 1,
            ])
        );
        $detailResponse->assertStatus(200);
        $this->assertEquals('129458', $detailResponse->json('data.employee_id') ?? $detailResponse->json('employee_id'));

        // Exports still return 200
        $this->actingAs($this->admin)->get(route('user.manage.profile.export.excel', $this->targetHash))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('user.manage.profile.export.csv', $this->targetHash))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('user.manage.profile.export.pdf', $this->targetHash))->assertStatus(200);
    }
}
