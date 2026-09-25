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
    private AssesmentQuestion $question1;
    private AssesmentQuestion $question2;

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
        $this->question1 = AssesmentQuestion::create([
            'assesment_id' => $this->assessmentOk->id,
            'question'     => 'What is 2 + 2?',
            'option'       => ['3', '4', '5'],
            'answer'       => '4',
            'status'       => true,
        ]);

        $this->question2 = AssesmentQuestion::create([
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

    public function test_unauthenticated_user_cannot_access_preview_result(): void
    {
        $response = $this->post(route('assesment.preview.result', $this->assessmentOk->slug), [
            'answers' => [$this->question1->id => '4'],
        ]);

        $response->assertRedirect();
    }

    public function test_regular_user_cannot_access_preview_result(): void
    {
        $this->assertNotNull($this->regularUser, 'Regular user must exist');

        $response = $this->actingAs($this->regularUser)->post(route('assesment.preview.result', $this->assessmentOk->slug), [
            'answers' => [$this->question1->id => '4'],
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_access_preview_result(): void
    {
        $this->assertNotNull($this->admin, 'Admin user must exist');

        $response = $this->actingAs($this->admin)->post(route('assesment.preview.result', $this->assessmentOk->slug), [
            'answers' => [
                $this->question1->id => '4',
                $this->question2->id => 'Jakarta',
            ],
        ]);

        $response->assertOk();
        $response->assertSee('THIS IS A PREVIEW RESULT', false);
        $response->assertSee('This result is temporary and has not been saved', false);
        $response->assertSee('Preview Answer Review', false);
        $response->assertSee('Back to Preview', false);
        $response->assertSee('Back to Assessment Management', false);
    }

    public function test_super_admin_can_access_preview_result(): void
    {
        $this->assertNotNull($this->superAdmin, 'Super Admin user must exist');

        $response = $this->actingAs($this->superAdmin)->post(route('assesment.preview.result', $this->assessmentOk->slug), [
            'answers' => [
                $this->question1->id => '4',
                $this->question2->id => 'Jakarta',
            ],
        ]);

        $response->assertOk();
        $response->assertSee('THIS IS A PREVIEW RESULT', false);
    }

    public function test_preview_result_scoring_all_correct_gives_100_and_passed(): void
    {
        $this->assertNotNull($this->admin);

        $response = $this->actingAs($this->admin)->post(route('assesment.preview.result', $this->assessmentOk->slug), [
            'answers' => [
                $this->question1->id => '4',
                $this->question2->id => 'Jakarta',
            ],
        ]);

        $response->assertOk();
        $resultData = $response->viewData('resultData');

        $this->assertEquals(2, $resultData['total_questions']);
        $this->assertEquals(2, $resultData['correct_count']);
        $this->assertEquals(0, $resultData['incorrect_count']);
        $this->assertEquals(0, $resultData['unanswered_count']);
        $this->assertEquals(100, $resultData['score']);
        $this->assertTrue($resultData['is_passed']);
        $response->assertSee('PASSED', false);
    }

    public function test_preview_result_scoring_all_wrong_gives_0_and_failed(): void
    {
        $this->assertNotNull($this->admin);

        $response = $this->actingAs($this->admin)->post(route('assesment.preview.result', $this->assessmentOk->slug), [
            'answers' => [
                $this->question1->id => '3',
                $this->question2->id => 'Bandung',
            ],
        ]);

        $response->assertOk();
        $resultData = $response->viewData('resultData');

        $this->assertEquals(2, $resultData['total_questions']);
        $this->assertEquals(0, $resultData['correct_count']);
        $this->assertEquals(2, $resultData['incorrect_count']);
        $this->assertEquals(0, $resultData['unanswered_count']);
        $this->assertEquals(0, $resultData['score']);
        $this->assertFalse($resultData['is_passed']);
        $response->assertSee('FAILED', false);
    }

    public function test_preview_result_scoring_partial_correct_and_passing_grade_boundary(): void
    {
        $this->assertNotNull($this->admin);

        // 1 correct out of 2 => score 50. Passing grade is 70 => Failed
        $response = $this->actingAs($this->admin)->post(route('assesment.preview.result', $this->assessmentOk->slug), [
            'answers' => [
                $this->question1->id => '4',
                $this->question2->id => 'Bandung',
            ],
        ]);

        $response->assertOk();
        $resultData = $response->viewData('resultData');

        $this->assertEquals(1, $resultData['correct_count']);
        $this->assertEquals(1, $resultData['incorrect_count']);
        $this->assertEquals(0, $resultData['unanswered_count']);
        $this->assertEquals(50, $resultData['score']);
        $this->assertFalse($resultData['is_passed']);

        // Now change passing grade to 50 => Passed (boundary inclusive)
        $this->assessmentOk->passing_grade = 50;
        $this->assessmentOk->save();

        $response2 = $this->actingAs($this->admin)->post(route('assesment.preview.result', $this->assessmentOk->slug), [
            'answers' => [
                $this->question1->id => '4',
                $this->question2->id => 'Bandung',
            ],
        ]);

        $response2->assertOk();
        $resultData2 = $response2->viewData('resultData');
        $this->assertTrue($resultData2['is_passed']);
    }

    public function test_preview_result_handles_unanswered_questions(): void
    {
        $this->assertNotNull($this->admin);

        // Only answer question 1, question 2 omitted
        $response = $this->actingAs($this->admin)->post(route('assesment.preview.result', $this->assessmentOk->slug), [
            'answers' => [
                $this->question1->id => '4',
            ],
        ]);

        $response->assertOk();
        $resultData = $response->viewData('resultData');

        $this->assertEquals(1, $resultData['correct_count']);
        $this->assertEquals(0, $resultData['incorrect_count']);
        $this->assertEquals(1, $resultData['unanswered_count']);
        $this->assertEquals(50, $resultData['score']);

        // Find review entry for question 2
        $q2Review = collect($resultData['reviews'])->firstWhere('id', $this->question2->id);
        $this->assertNotNull($q2Review);
        $this->assertEquals('unanswered', $q2Review['status']);
        $this->assertFalse($q2Review['is_correct']);
        $this->assertNull($q2Review['user_answer']);
    }

    public function test_preview_result_direct_form_inputs_compatibility(): void
    {
        $this->assertNotNull($this->admin);

        // Direct inputs where keys are question IDs
        $response = $this->actingAs($this->admin)->post(route('assesment.preview.result', $this->assessmentOk->slug), [
            (string) $this->question1->id => '4',
            (string) $this->question2->id => 'Jakarta',
        ]);

        $response->assertOk();
        $resultData = $response->viewData('resultData');

        $this->assertEquals(2, $resultData['correct_count']);
        $this->assertEquals(100, $resultData['score']);
    }

    public function test_preview_result_tampered_question_id_does_not_affect_scoring(): void
    {
        $this->assertNotNull($this->admin);

        // Submit answer with rogue question ID that does not belong to this assessment
        $response = $this->actingAs($this->admin)->post(route('assesment.preview.result', $this->assessmentOk->slug), [
            'answers' => [
                '99999999'           => 'Tampered Answer',
                $this->question1->id => '4',
                $this->question2->id => 'Jakarta',
            ],
        ]);

        $response->assertOk();
        $resultData = $response->viewData('resultData');

        $this->assertEquals(2, $resultData['total_questions'], 'Total questions must remain 2');
        $this->assertEquals(2, $resultData['correct_count']);
        $this->assertEquals(0, $resultData['incorrect_count']);
        $this->assertEquals(0, $resultData['unanswered_count']);
        $this->assertEquals(100, $resultData['score']);
        $this->assertCount(2, $resultData['reviews'], 'Reviews must only contain assessment questions');
    }

    public function test_preview_result_strictly_produces_zero_database_write(): void
    {
        $this->assertNotNull($this->admin);

        $initialResultsCount = AssesmentResult::count();
        $initialHistoryCount = \Illuminate\Support\Facades\DB::table('assesment_score_histories')->count();
        $initialContentUsers = \Illuminate\Support\Facades\DB::table('content_users')->count();
        $initialPlaylistContentUsers = \Illuminate\Support\Facades\DB::table('playlist_content_users')->count();

        $response = $this->actingAs($this->admin)->post(route('assesment.preview.result', $this->assessmentOk->slug), [
            'answers' => [
                $this->question1->id => '4',
                $this->question2->id => 'Jakarta',
            ],
        ]);

        $response->assertOk();

        $this->assertEquals($initialResultsCount, AssesmentResult::count(), 'Zero write to assesment_results');
        $this->assertEquals($initialHistoryCount, \Illuminate\Support\Facades\DB::table('assesment_score_histories')->count(), 'Zero write to assesment_score_histories');
        $this->assertEquals($initialContentUsers, \Illuminate\Support\Facades\DB::table('content_users')->count(), 'Zero write to content_users');
        $this->assertEquals($initialPlaylistContentUsers, \Illuminate\Support\Facades\DB::table('playlist_content_users')->count(), 'Zero write to playlist_content_users');
    }
}

