<?php

namespace Tests\Feature;

use App\Models\Assesment;
use App\Models\User;
use Tests\TestCase;

class AssessmentDuplicateFeatureTest extends TestCase
{
    public function test_admin_can_duplicate_assessment_via_post(): void
    {
        $admin = User::where('role', '2')->first() ?? User::where('role', '3')->first() ?? User::first();

        $original = Assesment::create([
            'title'   => 'Feature Test Assessment ' . uniqid(),
            'status'  => 'OK',
            'type'    => 'global',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('assesment.duplicate', $original->slug));

        $response->assertRedirect(route('assesment.index'));
        $response->assertSessionHas('success', 'Assessment duplicated successfully.');

        $expectedTitle = $original->title . ' Copy 1';
        $duplicate = Assesment::where('title', $expectedTitle)->first();
        $this->assertNotNull($duplicate);

        // Cleanup
        if ($duplicate) {
            $duplicate->delete();
        }
        $original->delete();
    }

    public function test_admin_can_duplicate_assessment_via_clone_route_backward_compatibility(): void
    {
        $admin = User::where('role', '2')->first() ?? User::where('role', '3')->first() ?? User::first();

        $original = Assesment::create([
            'title'   => 'Backward Compat Test ' . uniqid(),
            'status'  => 'OK',
            'type'    => 'global',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('assesment.clone', $original->slug));

        $response->assertRedirect(route('assesment.index'));
        $response->assertSessionHas('success', 'Assessment duplicated successfully.');

        $expectedTitle = $original->title . ' Copy 1';
        $duplicate = Assesment::where('title', $expectedTitle)->first();
        $this->assertNotNull($duplicate);

        // Cleanup
        if ($duplicate) {
            $duplicate->delete();
        }
        $original->delete();
    }

    public function test_assessment_index_renders_duplicate_action_and_modal(): void
    {
        $admin = User::where('role', '2')->first() ?? User::where('role', '3')->first() ?? User::first();

        $response = $this->actingAs($admin)->get(route('assesment.index'));

        $response->assertStatus(200);
        $response->assertSee('Duplicate Assessment');
        $response->assertSee('modalDuplicateAssessment');
        $response->assertSee('Are you sure you want to duplicate this assessment?');
        $response->assertDontSee('onclick="return confirm(\'Clone this assessment ?\')"');
    }
}
