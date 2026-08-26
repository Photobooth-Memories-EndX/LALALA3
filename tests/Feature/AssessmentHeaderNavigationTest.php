<?php

namespace Tests\Feature;

use App\Models\Assesment;
use App\Models\AssesmentQuestion;
use App\Models\User;
use Tests\TestCase;

class AssessmentHeaderNavigationTest extends TestCase
{
    private User $admin;
    private Assesment $assessment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('role', '2')->first() ?? User::where('role', '3')->first() ?? User::first();

        $this->assessment = Assesment::create([
            'title'   => 'Header Layout Nav Test ' . uniqid(),
            'status'  => 'OK',
            'type'    => 'global',
        ]);
    }

    protected function tearDown(): void
    {
        if (isset($this->assessment) && $this->assessment->exists) {
            AssesmentQuestion::where('assesment_id', $this->assessment->id)->delete();
            $this->assessment->delete();
        }
        parent::tearDown();
    }

    public function test_create_page_header_layout(): void
    {
        $response = $this->actingAs($this->admin)->get(route('assesment.create'));

        $response->assertOk();
        $response->assertSee('assessment-back', false);
        $response->assertSee('assessment-breadcrumb', false);
        $response->assertSee('Back');
        $response->assertSee('Create');
        $response->assertSee('Create Assessment');
    }

    public function test_edit_page_header_layout(): void
    {
        $response = $this->actingAs($this->admin)->get(route('assesment.edit', $this->assessment->slug));

        $response->assertOk();
        $response->assertSee('assessment-back', false);
        $response->assertSee('assessment-breadcrumb', false);
        $response->assertSee('Back');
        $response->assertSee('Edit');
        $response->assertSee($this->assessment->title);
    }

    public function test_newlink_page_header_layout(): void
    {
        $response = $this->actingAs($this->admin)->get(route('assesment.newlink', $this->assessment->slug));

        $response->assertOk();
        $response->assertSee('assessment-back', false);
        $response->assertSee('assessment-breadcrumb', false);
        $response->assertSee('Back');
        $response->assertSee('New Link');
        $response->assertSee('Generate New Link');
    }

    public function test_question_page_header_layout(): void
    {
        $response = $this->actingAs($this->admin)->get(route('assesment.question', $this->assessment->slug));

        $response->assertOk();
        $response->assertSee('assessment-back', false);
        $response->assertSee('assessment-breadcrumb', false);
        $response->assertSee('Back');
        $response->assertSee('Question');
        $response->assertSee('Question List');
    }

    public function test_result_page_header_layout(): void
    {
        $response = $this->actingAs($this->admin)->get(route('assesment.result', $this->assessment->slug));

        $response->assertOk();
        $response->assertSee('assessment-back', false);
        $response->assertSee('assessment-breadcrumb', false);
        $response->assertSee('Back');
        $response->assertSee('Result');
        $response->assertSee('Assessment Result');
    }

    public function test_assign_users_page_header_layout(): void
    {
        $response = $this->actingAs($this->admin)->get(route('assesment.assign.users.index', $this->assessment->slug));

        $response->assertOk();
        $response->assertSee('assessment-back', false);
        $response->assertSee('assessment-breadcrumb', false);
        $response->assertSee('Back');
        $response->assertSee('Assign Users');
    }

    public function test_edit_score_page_header_layout(): void
    {
        $response = $this->actingAs($this->admin)->get(route('assesment.edit.score.index'));

        $response->assertOk();
        $response->assertSee('assessment-back', false);
        $response->assertSee('assessment-breadcrumb', false);
        $response->assertSee('Back');
        $response->assertSee('Edit Score');
        $response->assertSee('Assessment Edit Score');
    }

    public function test_index_page_header_layout(): void
    {
        $response = $this->actingAs($this->admin)->get(route('assesment.index'));

        $response->assertOk();
        $response->assertSee('assessment-back', false);
        $response->assertSee('assessment-breadcrumb', false);
        $response->assertSee('Back');
        $response->assertSee('Assessment Management');
        $response->assertSee(route('manage.dashboard'));
    }
}
