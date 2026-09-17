<?php

namespace Tests\Feature;

use App\Models\Assesment;
use App\Models\AssesmentAssigned;
use App\Models\AssesmentResult;
use App\Models\Classification;
use App\Models\Content;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class MyLearningInactiveAssessmentVisibilityTest extends TestCase
{
    use DatabaseTransactions;

    protected User $userA;
    protected User $userB;
    protected Content $content;

    protected Assesment $activeAssessment;
    protected Assesment $inactiveAssessmentTaken;
    protected Assesment $inactiveAssessmentNotTaken;

    protected AssesmentResult $userAResult;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = User::factory()->create([
            'team'       => 'Plant Team A',
            'department' => 'Operations',
            'division'   => 'Plant',
            'ssohash'    => 'userA-' . Str::uuid(),
            'sap_code'   => 'SAP-A-' . rand(1000, 9999),
            'id_emp'     => 'EMP-A-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->userB = User::factory()->create([
            'team'       => 'Plant Team B',
            'department' => 'Operations',
            'division'   => 'Plant',
            'ssohash'    => 'userB-' . Str::uuid(),
            'sap_code'   => 'SAP-B-' . rand(1000, 9999),
            'id_emp'     => 'EMP-B-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $classification = Classification::first() ?? Classification::create([
            'name'        => 'Classification ' . Str::random(5),
            'description' => 'Desc',
        ]);

        $this->content = Content::create([
            'title'             => 'Content ' . Str::random(5),
            'type'              => 'video',
            'classification_id' => $classification->id,
            'active'            => 'Active',
        ]);

        $tag = Str::random(8);

        // 1. Active assessment (ON)
        $this->activeAssessment = Assesment::create([
            'title'                 => "Active_Assessment_{$tag}",
            'type'                  => 'global',
            'assasment_content_for' => 'content',
            'status'                => 'OK',
            'passing_grade'         => 75,
            'time'                  => 30,
            'max_attempt'           => 3,
        ]);
        AssesmentAssigned::create([
            'assesment_id'         => $this->activeAssessment->id,
            'assigned_id'          => $this->userA->id_emp,
            'assigned_type'        => 'user',
            'assigned_description' => 'Test',
        ]);
        AssesmentAssigned::create([
            'assesment_id'         => $this->activeAssessment->id,
            'assigned_id'          => $this->userB->id_emp,
            'assigned_type'        => 'user',
            'assigned_description' => 'Test',
        ]);

        // 2. Inactive assessment (OFF) - Taken by User A
        $this->inactiveAssessmentTaken = Assesment::create([
            'title'                 => "Inactive_Taken_{$tag}",
            'type'                  => 'global',
            'assasment_content_for' => 'content',
            'status'                => 'NOT',
            'passing_grade'         => 80,
            'time'                  => 30,
            'max_attempt'           => 3,
        ]);
        AssesmentAssigned::create([
            'assesment_id'         => $this->inactiveAssessmentTaken->id,
            'assigned_id'          => $this->userA->id_emp,
            'assigned_type'        => 'user',
            'assigned_description' => 'Test',
        ]);
        AssesmentAssigned::create([
            'assesment_id'         => $this->inactiveAssessmentTaken->id,
            'assigned_id'          => $this->userB->id_emp,
            'assigned_type'        => 'user',
            'assigned_description' => 'Test',
        ]);

        // User A has taken this inactive assessment
        $this->userAResult = AssesmentResult::create([
            'user_id'      => $this->userA->id,
            'assesment_id' => $this->inactiveAssessmentTaken->id,
            'user_answer'  => [],
            'score'        => 85,
            'pass'         => 1,
            'final'        => 1,
            'batch'        => 1,
        ]);

        // 3. Inactive assessment (OFF) - NOT taken by anyone
        $this->inactiveAssessmentNotTaken = Assesment::create([
            'title'                 => "Inactive_NotTaken_{$tag}",
            'type'                  => 'global',
            'assasment_content_for' => 'content',
            'status'                => 'NOT',
            'passing_grade'         => 70,
            'time'                  => 30,
            'max_attempt'           => 3,
        ]);
        AssesmentAssigned::create([
            'assesment_id'         => $this->inactiveAssessmentNotTaken->id,
            'assigned_id'          => $this->userA->id_emp,
            'assigned_type'        => 'user',
            'assigned_description' => 'Test',
        ]);
        AssesmentAssigned::create([
            'assesment_id'         => $this->inactiveAssessmentNotTaken->id,
            'assigned_id'          => $this->userB->id_emp,
            'assigned_type'        => 'user',
            'assigned_description' => 'Test',
        ]);
    }

    /**
     * Goal 1 & 2: Active assessments appear as normal.
     * Inactive assessments appear ONLY if logged-in user has history in assessment_results.
     */
    public function test_inactive_assessment_appears_for_user_who_completed_it(): void
    {
        $response = $this->actingAs($this->userA)->get(route('user.learning.dashboard.partial', [
            'tab' => 'assessment',
        ]));

        $response->assertStatus(200);
        $assessments = $response->viewData('assessments');
        $ids = $assessments->pluck('id')->toArray();

        // Active assessment appears
        $this->assertContains($this->activeAssessment->id, $ids);

        // Inactive assessment taken by userA appears
        $this->assertContains($this->inactiveAssessmentTaken->id, $ids);

        // Inactive assessment NOT taken does NOT appear
        $this->assertNotContains($this->inactiveAssessmentNotTaken->id, $ids);
    }

    /**
     * Goal 3: Inactive assessment does NOT appear for user who has NOT taken it.
     */
    public function test_inactive_assessment_does_not_appear_for_user_who_did_not_take_it(): void
    {
        $response = $this->actingAs($this->userB)->get(route('user.learning.dashboard.partial', [
            'tab' => 'assessment',
        ]));

        $response->assertStatus(200);
        $assessments = $response->viewData('assessments');
        $ids = $assessments->pluck('id')->toArray();

        // Active assessment appears
        $this->assertContains($this->activeAssessment->id, $ids);

        // Inactive assessment taken by userA does NOT appear for userB
        $this->assertNotContains($this->inactiveAssessmentTaken->id, $ids);

        // Inactive assessment NOT taken does NOT appear
        $this->assertNotContains($this->inactiveAssessmentNotTaken->id, $ids);
    }

    /**
     * Goal 4 & UI 1 & UI 2: Eager loading and display of score, status, attempt, date,
     * hover score popover, and Action button shows disabled Inactive action.
     */
    public function test_inactive_assessment_renders_result_data_and_inactive_button(): void
    {
        $response = $this->actingAs($this->userA)->get(route('user.learning.dashboard.partial', [
            'tab' => 'assessment',
        ]));

        $response->assertStatus(200);
        $html = $response->getContent();

        // Assessment title appears
        $response->assertSee($this->inactiveAssessmentTaken->title);

        // Score 85 is rendered
        $this->assertStringContainsString('data-assessment-score="85"', $html);

        // Status Passed is rendered
        $this->assertStringContainsString('Passed', $html);

        // Action button renders Inactive disabled button
        $this->assertStringContainsString('btn-assessment-inactive', $html);
        $this->assertStringContainsString('Inactive', $html);

        // Score popover template is rendered with score history
        $this->assertStringContainsString('assessment-score-popover-card', $html);
        $this->assertStringContainsString('assessment-mini-history-table', $html);

        // Retake / Start button must NOT be present for this inactive assessment
        $this->assertStringNotContainsString(
            'data-assessment-id="' . $this->inactiveAssessmentTaken->id . '" data-assessment-action-type="start"',
            $html
        );
        $this->assertStringNotContainsString(
            'data-assessment-id="' . $this->inactiveAssessmentTaken->id . '" data-assessment-action-type="remedial"',
            $html
        );
    }

    /**
     * Badge counter in dashboard syncs with the list including the inactive assessment.
     */
    public function test_badge_counter_syncs_with_inactive_assessments_taken(): void
    {
        $response = $this->actingAs($this->userA)->get(route('user.learning.dashboard.partial', [
            'tab' => 'assessment',
        ]));

        $response->assertStatus(200);
        $stats = collect($response->viewData('assessmentStats'))->pluck('value', 'label')->toArray();
        $assessments = $response->viewData('assessments');

        // Total assessments must match list count exactly
        $this->assertSame($assessments->count(), $stats['Total Assessment']);

        // Sum of breakdown badges must equal Total Assessment exactly (100% sync)
        $this->assertSame($stats['Total Assessment'], $stats['Started'] + $stats['Passed'] + $stats['Failed']);

        // Inactive assessment taken by User A is counted in Passed
        $this->assertGreaterThanOrEqual(1, $stats['Passed']);
    }

    /**
     * User can access the result page of the inactive assessment without access denied error.
     */
    public function test_user_can_access_result_page_for_inactive_assessment(): void
    {
        $response = $this->actingAs($this->userA)->get(route('assesment.user.result', $this->inactiveAssessmentTaken->slug));

        $response->assertStatus(200);
        $response->assertSee('Assessment Result');
        $response->assertSee('85');
        $response->assertSee($this->inactiveAssessmentTaken->title);
    }

    /**
     * User cannot take / retake an inactive assessment (viewing test interface is denied).
     */
    public function test_user_cannot_start_or_retake_inactive_assessment(): void
    {
        $response = $this->actingAs($this->userA)->get(route('assesment.user.view', $this->inactiveAssessmentTaken->slug));

        // Inactive assessment is blocked with access denied response
        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect() || str_contains($response->getContent(), 'inactive'),
            'Expected access to be denied for inactive assessment view'
        );
    }

    /**
     * REGRESSION TEST:
     * Active assessment where user has Passed result:
     * STATUS must be Passed, ACTION must be Passed (NOT Review).
     */
    public function test_active_passed_assessment_renders_passed_action_and_not_review(): void
    {
        $tag = Str::random(8);
        $passedAssessment = Assesment::create([
            'title'                 => "Active_Passed_{$tag}",
            'type'                  => 'global',
            'assasment_content_for' => 'content',
            'status'                => 'OK',
            'passing_grade'         => 70,
            'time'                  => 30,
            'max_attempt'           => 3,
        ]);
        AssesmentAssigned::create([
            'assesment_id'         => $passedAssessment->id,
            'assigned_id'          => $this->userA->id_emp,
            'assigned_type'        => 'user',
            'assigned_description' => 'Test',
        ]);
        AssesmentResult::create([
            'user_id'      => $this->userA->id,
            'assesment_id' => $passedAssessment->id,
            'user_answer'  => [],
            'score'        => 90,
            'pass'         => 1,
            'final'        => 1,
            'batch'        => 1,
        ]);

        $response = $this->actingAs($this->userA)->get(route('user.learning.dashboard.partial', [
            'tab' => 'assessment',
        ]));

        $response->assertStatus(200);
        $html = $response->getContent();

        // Must see assessment title
        $response->assertSee($passedAssessment->title);

        // Action column must render Passed button
        $this->assertStringContainsString('btn-assessment-passed', $html);

        // Action column MUST NOT render Review button
        $this->assertStringNotContainsString('btn-assessment-review', $html);
        $this->assertStringNotContainsString('btn-view-result', $html);
    }

    /**
     * Active assessment where user has Failed result and has attempts remaining:
     * STATUS must be Failed, ACTION must be Remedial.
     */
    public function test_active_failed_assessment_with_attempts_remaining_renders_remedial_action(): void
    {
        $tag = Str::random(8);
        $remedialAssessment = Assesment::create([
            'title'                 => "Active_Remedial_{$tag}",
            'type'                  => 'content',
            'assasment_content_for' => 'content',
            'status'                => 'OK',
            'passing_grade'         => 75,
            'time'                  => 30,
            'total_remedials'       => 2,
            'max_attempt'           => 3,
        ]);
        AssesmentAssigned::create([
            'assesment_id'         => $remedialAssessment->id,
            'assigned_id'          => $this->userA->id_emp,
            'assigned_type'        => 'user',
            'assigned_description' => 'Test',
        ]);
        AssesmentResult::create([
            'user_id'      => $this->userA->id,
            'assesment_id' => $remedialAssessment->id,
            'user_answer'  => [],
            'score'        => 50,
            'pass'         => 0,
            'final'        => 1,
            'batch'        => 1,
        ]);

        $response = $this->actingAs($this->userA)->get(route('user.learning.dashboard.partial', [
            'tab' => 'assessment',
        ]));

        $response->assertStatus(200);
        $html = $response->getContent();

        $response->assertSee($remedialAssessment->title);
        $this->assertStringContainsString('btn-assessment-remedial', $html);
        $this->assertStringContainsString('Remedial', $html);
    }

    /**
     * Active assessment not yet started by user:
     * STATUS must be Pending, ACTION must be Start Test.
     */
    public function test_active_not_started_assessment_renders_start_test_action(): void
    {
        $response = $this->actingAs($this->userA)->get(route('user.learning.dashboard.partial', [
            'tab' => 'assessment',
        ]));

        $response->assertStatus(200);
        $html = $response->getContent();

        $response->assertSee($this->activeAssessment->title);
        $this->assertStringContainsString('btn-assessment-start', $html);
        $this->assertStringContainsString('Start Test', $html);
    }

    /**
     * Inactive assessment where user has Failed result:
     * Assessment remains visible, STATUS must be Failed, ACTION must be Inactive.
     */
    public function test_inactive_failed_assessment_renders_inactive_action(): void
    {
        $tag = Str::random(8);
        $inactiveFailed = Assesment::create([
            'title'                 => "Inactive_Failed_{$tag}",
            'type'                  => 'global',
            'assasment_content_for' => 'content',
            'status'                => 'NOT',
            'passing_grade'         => 80,
            'time'                  => 30,
            'max_attempt'           => 3,
        ]);
        AssesmentAssigned::create([
            'assesment_id'         => $inactiveFailed->id,
            'assigned_id'          => $this->userA->id_emp,
            'assigned_type'        => 'user',
            'assigned_description' => 'Test',
        ]);
        AssesmentResult::create([
            'user_id'      => $this->userA->id,
            'assesment_id' => $inactiveFailed->id,
            'user_answer'  => [],
            'score'        => 45,
            'pass'         => 0,
            'final'        => 1,
            'batch'        => 1,
        ]);

        $response = $this->actingAs($this->userA)->get(route('user.learning.dashboard.partial', [
            'tab' => 'assessment',
        ]));

        $response->assertStatus(200);
        $html = $response->getContent();

        // Must be visible
        $response->assertSee($inactiveFailed->title);

        // Status is Failed
        $this->assertStringContainsString('assessment-status-failed', $html);

        // Action is Inactive (NOT Failed, NOT Review, NOT Passed)
        $this->assertStringContainsString('btn-assessment-inactive', $html);
        $this->assertStringContainsString('Inactive', $html);
    }
}
