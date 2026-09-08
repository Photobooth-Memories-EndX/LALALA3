<?php

namespace Tests\Feature;

use App\Models\Assesment;
use App\Models\User;
use Tests\TestCase;

class AssessmentDeleteModalTest extends TestCase
{
    private User $admin;
    private Assesment $assessment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('role', '2')->first() ?? User::where('role', '3')->first() ?? User::first();

        $this->assessment = Assesment::create([
            'title'   => 'Delete Modal Test Assessment ' . uniqid(),
            'status'  => 'OK',
            'type'    => 'global',
        ]);
    }

    protected function tearDown(): void
    {
        if (isset($this->assessment) && $this->assessment->exists) {
            $this->assessment->delete();
        }
        parent::tearDown();
    }

    public function test_manage_assessment_index_renders_delete_modal_and_no_native_confirm(): void
    {
        $response = $this->actingAs($this->admin)->get(route('assesment.index'));

        $response->assertOk();

        // 1. Shared Modal exists in DOM
        $response->assertSee('id="deleteAssessmentModal"', false);
        $response->assertSee('id="deleteAssessmentModalTitle"', false);
        $response->assertSee('id="deleteAssessmentItemNameContainer"', false);
        $response->assertSee('id="deleteAssessmentItemTitle"', false);
        $response->assertSee('id="btnCancelDeleteAssessment"', false);
        $response->assertSee('id="btnConfirmDeleteAssessment"', false);

        // 2. Action dropdown has modal trigger button with assessment title
        $response->assertSee('btn-trigger-delete-assessment', false);
        $response->assertSee('data-title="' . e($this->assessment->title) . '"', false);

        // 3. Native confirm is completely removed from delete action
        $response->assertDontSee("confirm('Delete this assessment ?')", false);
        $response->assertDontSee('onclick="return confirm', false);

        // 4. Form uses the exact existing route and DELETE method
        $expectedFormAction = route('assesment.destroy', $this->assessment->slug);
        $response->assertSee('action="' . $expectedFormAction . '"', false);
    }

    public function test_existing_delete_mechanism_remains_functional(): void
    {
        $target = Assesment::create([
            'title'   => 'Target Delete Assessment ' . uniqid(),
            'status'  => 'OK',
            'type'    => 'global',
        ]);

        $deleteUrl = route('assesment.destroy', $target->slug);

        $response = $this->actingAs($this->admin)->delete($deleteUrl);

        $response->assertRedirect(route('assesment.index'));
        $response->assertSessionHas('success', 'Assesment deleted successfully');

        $this->assertDatabaseMissing('assesments', [
            'id' => $target->id,
        ]);
    }
}
