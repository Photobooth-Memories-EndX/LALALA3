<?php

namespace Tests\Feature;

use App\Models\Assesment;
use App\Models\AssesmentQuestion;
use App\Models\AssesmentResult;
use App\Models\User;
use Illuminate\Support\Carbon;
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

    public function test_preview_timer_with_access_period_only(): void
    {
        $this->assertNotNull($this->admin);

        $targetToDate = Carbon::now()->addDays(5)->format('Y-m-d H:i:s');
        $periodAssessment = Assesment::create([
            'title'              => 'Period Only Test ' . uniqid(),
            'status'             => 'OK',
            'type'               => 'global',
            'has_access_period'  => true,
            'fromdate'           => Carbon::now()->subDay()->format('Y-m-d H:i:s'),
            'todate'             => $targetToDate,
            'timer'              => 'n',
            'time_limit_minutes' => null,
        ]);

        $response = $this->actingAs($this->admin)->get(route('assesment.preview', $periodAssessment->slug));

        $response->assertOk();
        $this->assertEquals($targetToDate, $response->viewData('countDownDate'));
        $this->assertNotNull($response->viewData('expiresAtIso'));
        $response->assertSee('id="timing-card"', false);
        $response->assertSee('Time Remaining', false);

        $periodAssessment->delete();
    }

    public function test_preview_timer_with_test_duration_only(): void
    {
        $this->assertNotNull($this->admin);

        $durationAssessment = Assesment::create([
            'title'              => 'Duration Only Test ' . uniqid(),
            'status'             => 'OK',
            'type'               => 'content',
            'has_access_period'  => false,
            'fromdate'           => null,
            'todate'             => null,
            'timer'              => 'y',
            'time_limit_minutes' => 45,
        ]);

        $response = $this->actingAs($this->admin)->get(route('assesment.preview', $durationAssessment->slug));

        $response->assertOk();
        $this->assertNotNull($response->viewData('countDownDate'));
        $this->assertNotNull($response->viewData('expiresAtIso'));
        $response->assertSee('id="timing-card"', false);
        $response->assertSee('Time Remaining', false);

        $durationAssessment->delete();
    }

    public function test_preview_timer_with_access_period_and_test_duration(): void
    {
        $this->assertNotNull($this->admin);

        $farPeriodToDate = Carbon::now()->addDays(3)->format('Y-m-d H:i:s');
        $combinedAssessment = Assesment::create([
            'title'              => 'Combined Test ' . uniqid(),
            'status'             => 'OK',
            'type'               => 'content',
            'has_access_period'  => true,
            'fromdate'           => Carbon::now()->subDay()->format('Y-m-d H:i:s'),
            'todate'             => $farPeriodToDate,
            'timer'              => 'y',
            'time_limit_minutes' => 30, // 30 minutes is earlier than 3 days
        ]);

        $response = $this->actingAs($this->admin)->get(route('assesment.preview', $combinedAssessment->slug));

        $response->assertOk();
        $countDownDate = $response->viewData('countDownDate');
        $this->assertNotNull($countDownDate);
        // Effective deadline must be the earlier deadline (approximately 30 mins from now, NOT 3 days)
        $diffMinutes = Carbon::parse($countDownDate)->diffInMinutes(Carbon::now());
        $this->assertLessThanOrEqual(31, $diffMinutes);
        $this->assertGreaterThanOrEqual(29, $diffMinutes);

        $combinedAssessment->delete();
    }

    public function test_preview_timer_without_deadline(): void
    {
        $this->assertNotNull($this->admin);

        $noDeadlineAssessment = Assesment::create([
            'title'              => 'No Deadline Test ' . uniqid(),
            'status'             => 'OK',
            'type'               => 'content',
            'has_access_period'  => false,
            'fromdate'           => null,
            'todate'             => null,
            'timer'              => 'n',
            'time_limit_minutes' => null,
        ]);

        $response = $this->actingAs($this->admin)->get(route('assesment.preview', $noDeadlineAssessment->slug));

        $response->assertOk();
        $this->assertNull($response->viewData('countDownDate'));
        $this->assertNull($response->viewData('expiresAtIso'));
        $response->assertDontSee('id="timing-card"', false);
        $response->assertSee('No Deadline', false);

        $noDeadlineAssessment->delete();
    }

    public function test_real_ptkp_global_assessment_preview_shows_time_remaining_timer(): void
    {
        $this->assertNotNull($this->admin);

        $realAssessment = Assesment::where('slug', 'ptkp-global-assessment-2025-f-general-18-nov-2025-copy-2')->first();
        if ($realAssessment) {
            $response = $this->actingAs($this->admin)->get(route('assesment.preview', $realAssessment->slug));

            $response->assertOk();
            $this->assertEquals($realAssessment->todate, $response->viewData('countDownDate'));
            $this->assertNotNull($response->viewData('expiresAtIso'));
            $response->assertSee('id="timing-card"', false);
            $response->assertSee('Time Remaining', false);
            $response->assertSee('id="timer-days"', false);
            $response->assertSee('id="timer-clock"', false);
        }
    }
}
