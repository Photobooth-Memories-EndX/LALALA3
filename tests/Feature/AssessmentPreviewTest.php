<?php

namespace Tests\Feature;

use App\Models\Assesment;
use App\Models\AssesmentQuestion;
use App\Models\AssesmentResult;
use App\Models\User;
use Tests\TestCase;

class AssessmentPreviewTest extends TestCase
{
    private ?User $admin = null;
    private ?User $superAdmin = null;
    private ?User $regularUser = null;
    private Assesment $assessmentOk;
    private Assesment $assessmentNot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', User::ROLE_ADMIN)->first();
        $this->superAdmin = User::where('role', User::ROLE_SUPER_ADMIN)->first();
        $this->regularUser = User::where('role', User::ROLE_USER)->first();

        // Create test assessments (one active 'OK', one inactive 'NOT')
        $this->assessmentOk = Assesment::create([
            'title'               => 'Test Preview OK ' . uniqid(),
            'status'              => 'OK',
            'type'                => 'global',
            'passing_grade'       => 70,
            'timer'               => 'y',
            'time_limit_minutes'  => 30,
        ]);

        $this->assessmentNot = Assesment::create([
            'title'               => 'Test Preview Inactive ' . uniqid(),
            'status'              => 'NOT',
            'type'                => 'global',
            'passing_grade'       => 80,
            'timer'               => 'n',
        ]);

        // Add dummy questions to assessmentOk
        AssesmentQuestion::create([
            'assesment_id' => $this->assessmentOk->id,
            'question'     => 'What is 2 + 2?',
            'option'       => ['3', '4', '5'],
            'answer'       => '4',
            'status'       => true,
        ]);

        AssesmentQuestion::create([
            'assesment_id' => $this->assessmentOk->id,
            'question'     => 'What is the capital of Indonesia?',
            'option'       => ['Jakarta', 'Bandung', 'Surabaya'],
            'answer'       => 'Jakarta',
            'status'       => true,
        ]);
    }

    protected function tearDown(): void
    {
        if (isset($this->assessmentOk) && $this->assessmentOk->exists) {
            AssesmentQuestion::where('assesment_id', $this->assessmentOk->id)->delete();
            $this->assessmentOk->delete();
        }

        if (isset($this->assessmentNot) && $this->assessmentNot->exists) {
            AssesmentQuestion::where('assesment_id', $this->assessmentNot->id)->delete();
            $this->assessmentNot->delete();
        }

        parent::tearDown();
    }

    public function test_unauthenticated_user_cannot_access_preview(): void
    {
        $response = $this->get(route('assesment.preview', $this->assessmentOk->slug));

        $response->assertRedirect();
    }

    public function test_regular_user_cannot_access_preview(): void
    {
        $this->assertNotNull($this->regularUser, 'Regular user must exist');

        $response = $this->actingAs($this->regularUser)->get(route('assesment.preview', $this->assessmentOk->slug));

        $response->assertStatus(403);
    }

    public function test_admin_can_access_preview_mode(): void
    {
        $this->assertNotNull($this->admin, 'Admin user must exist');

        $response = $this->actingAs($this->admin)->get(route('assesment.preview', $this->assessmentOk->slug));

        $response->assertOk();
    }

    public function test_super_admin_can_access_preview_mode(): void
    {
        $this->assertNotNull($this->superAdmin, 'Super Admin user must exist');

        $response = $this->actingAs($this->superAdmin)->get(route('assesment.preview', $this->assessmentOk->slug));

        $response->assertOk();
    }

    public function test_preview_renders_mandatory_banner_and_buttons(): void
    {
        $this->assertNotNull($this->admin);

        $response = $this->actingAs($this->admin)->get(route('assesment.preview', $this->assessmentOk->slug));

        $response->assertOk();
        $response->assertSee('THIS ASSESSMENT IS IN PREVIEW MODE', false);
        $response->assertSee('Responses and results will not be saved.', false);
        $response->assertSee('Back to Assessment Management', false);
        $response->assertSee(route('assesment.index'), false);
        $response->assertSee('Finish Preview', false);
        $response->assertSee('id="finishPreviewModal"', false);
    }

    public function test_preview_works_for_both_active_and_inactive_assessments(): void
    {
        $this->assertNotNull($this->admin);

        // Active assessment (status = 'OK')
        $responseOk = $this->actingAs($this->admin)->get(route('assesment.preview', $this->assessmentOk->slug));
        $responseOk->assertOk();
        $responseOk->assertSee($this->assessmentOk->title);

        // Inactive assessment (status = 'NOT')
        $responseNot = $this->actingAs($this->admin)->get(route('assesment.preview', $this->assessmentNot->slug));
        $responseNot->assertOk();
        $responseNot->assertSee($this->assessmentNot->title);
    }

    public function test_preview_produces_zero_database_write(): void
    {
        $this->assertNotNull($this->admin);

        $initialResultsCount = AssesmentResult::count();

        // 1. Visit preview page
        $response = $this->actingAs($this->admin)->get(route('assesment.preview', $this->assessmentOk->slug));
        $response->assertOk();

        // 2. Assert zero results created
        $this->assertEquals($initialResultsCount, AssesmentResult::count(), 'Preview mode must produce 0 INSERT to assesment_results');
    }

    public function test_action_dropdown_renders_preview_assessment_link(): void
    {
        $this->assertNotNull($this->admin);

        $response = $this->actingAs($this->admin)->get(route('assesment.index'));

        $response->assertOk();
        $response->assertSee('Preview Assessment', false);
        $response->assertSee(route('assesment.preview', $this->assessmentOk->slug), false);
    }
}
