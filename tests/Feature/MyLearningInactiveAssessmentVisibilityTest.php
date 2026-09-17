<?php

namespace Tests\Feature;

use App\Models\Assesment;
use App\Models\AssesmentAssigned;
use App\Models\AssesmentResult;
use App\Models\Classification;
use App\Models\Content;
use App\Models\TrainingPlan;
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
    protected TrainingPlan $playlist;

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

        $this->playlist = TrainingPlan::create([
            'title'       => 'Playlist ' . Str::random(5),
            'description' => 'Desc',
            'status'      => 'published',
        ]);
        $this->playlist->contents()->attach($this->content->id, ['content_order' => 1]);

        $tag = Str::random(8);

        // 1. Active assessment (ON) - NOT taken by anyone
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
     * Goal: Inactive assessment appears ONLY if logged-in user has history in assesment_results.
     * Active assessments with NO result MUST NOT appear in My Learning.
     */
    public function test_inactive_assessment_appears_for_user_who_completed_it(): void
    {
        $response = $this->actingAs($this->userA)->get(route('user.learning.dashboard.partial', [
            'tab' => 'assessment',
        ]));

        $response->assertStatus(200);
        $assessments = $response->viewData('assessments');
        $ids = $assessments->pluck('id')->toArray();

        // Inactive assessment taken by userA appears
        $this->assertContains($this->inactiveAssessmentTaken->id, $ids);

        // Active assessment NOT taken does NOT appear
        $this->assertNotContains($this->activeAssessment->id, $ids);

        // Inactive assessment NOT taken does NOT appear
        $this->assertNotContains($this->inactiveAssessmentNotTaken->id, $ids);
    }

    /**
     * Untaken assessments (both active and inactive) do NOT appear for user who did not take them.
     */
    public function test_inactive_assessment_does_not_appear_for_user_who_did_not_take_it(): void
    {
        $response = $this->actingAs($this->userB)->get(route('user.learning.dashboard.partial', [
            'tab' => 'assessment',
        ]));

        $response->assertStatus(200);
        $assessments = $response->viewData('assessments');
        $ids = $assessments->pluck('id')->toArray();

        // Neither appears for userB
        $this->assertNotContains($this->activeAssessment->id, $ids);
        $this->assertNotContains($this->inactiveAssessmentTaken->id, $ids);
        $this->assertNotContains($this->inactiveAssessmentNotTaken->id, $ids);
    }

    /**
     * Inactive assessment with results displays score, status, and Inactive action button.
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
     * CASE 1: Global ACTIVE + NO result => HIDDEN
     */
    public function test_global_active_no_result_is_hidden(): void
    {
        $tag = Str::random(8);
        $a = Assesment::create([
            'title'                 => "Global_Active_NoResult_{$tag}",
            'type'                  => 'global',
            'assasment_content_for' => 'content',
            'status'                => 'OK',
            'passing_grade'         => 70,
        ]);

        $response = $this->actingAs($this->userA)->get(route('user.learning.dashboard.partial', ['tab' => 'assessment']));
        $ids = $response->viewData('assessments')->pluck('id')->toArray();

        $this->assertNotContains($a->id, $ids);
    }

    /**
     * CASE 2: Global INACTIVE + NO result => HIDDEN
     */
    public function test_global_inactive_no_result_is_hidden(): void
    {
        $tag = Str::random(8);
        $a = Assesment::create([
            'title'                 => "Global_Inactive_NoResult_{$tag}",
            'type'                  => 'global',
            'assasment_content_for' => 'content',
            'status'                => 'NOT',
            'passing_grade'         => 70,
        ]);

        $response = $this->actingAs($this->userA)->get(route('user.learning.dashboard.partial', ['tab' => 'assessment']));
        $ids = $response->viewData('assessments')->pluck('id')->toArray();

        $this->assertNotContains($a->id, $ids);
    }

    /**
     * CASE 3: Global EXPIRED + NO result => HIDDEN
     */
    public function test_global_expired_no_result_is_hidden(): void
    {
        $tag = Str::random(8);
        $a = Assesment::create([
            'title'                 => "Global_Expired_NoResult_{$tag}",
            'type'                  => 'global',
            'assasment_content_for' => 'content',
            'status'                => 'OK',
            'fromdate'              => now()->subDays(60)->format('Y-m-d H:i:s'),
            'todate'                => now()->subDays(30)->format('Y-m-d H:i:s'),
            'passing_grade'         => 70,
        ]);

        $response = $this->actingAs($this->userA)->get(route('user.learning.dashboard.partial', ['tab' => 'assessment']));
        $ids = $response->viewData('assessments')->pluck('id')->toArray();

        $this->assertNotContains($a->id, $ids);
    }

    /**
     * CASE 4 & 15: Global ACTIVE + HAS Passed result => VISIBLE, STATUS Passed, ACTION Passed (NOT Review).
     */
    public function test_global_active_has_passed_result_is_visible_with_passed_action(): void
    {
        $tag = Str::random(8);
        $a = Assesment::create([
            'title'                 => "Global_Active_Passed_{$tag}",
            'type'                  => 'global',
            'assasment_content_for' => 'content',
            'status'                => 'OK',
            'passing_grade'         => 70,
        ]);
        AssesmentResult::create([
            'user_id'      => $this->userA->id,
            'assesment_id' => $a->id,
            'score'        => 90,
            'pass'         => 1,
            'final'        => 1,
            'batch'        => 1,
            'user_answer'  => [],
        ]);

        $response = $this->actingAs($this->userA)->get(route('user.learning.dashboard.partial', ['tab' => 'assessment']));
        $html = $response->getContent();
        $ids = $response->viewData('assessments')->pluck('id')->toArray();

        $this->assertContains($a->id, $ids);
        $response->assertSee($a->title);

        // STATUS: Passed
        $this->assertStringContainsString('assessment-status-passed', $html);

        // ACTION: Passed (NOT Review)
        $this->assertStringContainsString('btn-assessment-passed', $html);
        $this->assertStringNotContainsString('btn-assessment-review', $html);
    }

    /**
     * CASE 5: Global INACTIVE + HAS Passed result => VISIBLE, STATUS Passed, ACTION Inactive
     */
    public function test_global_inactive_has_passed_result_is_visible_with_inactive_action(): void
    {
        $tag = Str::random(8);
        $a = Assesment::create([
            'title'                 => "Global_Inactive_Passed_{$tag}",
            'type'                  => 'global',
            'assasment_content_for' => 'content',
            'status'                => 'NOT',
            'passing_grade'         => 70,
        ]);
        AssesmentResult::create([
            'user_id'      => $this->userA->id,
            'assesment_id' => $a->id,
            'score'        => 90,
            'pass'         => 1,
            'final'        => 1,
            'batch'        => 1,
            'user_answer'  => [],
        ]);

        $response = $this->actingAs($this->userA)->get(route('user.learning.dashboard.partial', ['tab' => 'assessment']));
        $html = $response->getContent();
        $ids = $response->viewData('assessments')->pluck('id')->toArray();

        $this->assertContains($a->id, $ids);
        $response->assertSee($a->title);

        // STATUS: Passed
        $this->assertStringContainsString('assessment-status-passed', $html);

        // ACTION: Inactive
        $this->assertStringContainsString('btn-assessment-inactive', $html);
        $this->assertStringNotContainsString('btn-assessment-review', $html);
    }

    /**
     * CASE 6: Global INACTIVE + HAS Failed result => VISIBLE, STATUS Failed, ACTION Inactive
     */
    public function test_global_inactive_has_failed_result_is_visible_with_inactive_action(): void
    {
        $tag = Str::random(8);
        $a = Assesment::create([
            'title'                 => "Global_Inactive_Failed_{$tag}",
            'type'                  => 'global',
            'assasment_content_for' => 'content',
            'status'                => 'NOT',
            'passing_grade'         => 80,
        ]);
        AssesmentResult::create([
            'user_id'      => $this->userA->id,
            'assesment_id' => $a->id,
            'score'        => 45,
            'pass'         => 0,
            'final'        => 1,
            'batch'        => 1,
            'user_answer'  => [],
        ]);

        $response = $this->actingAs($this->userA)->get(route('user.learning.dashboard.partial', ['tab' => 'assessment']));
        $html = $response->getContent();
        $ids = $response->viewData('assessments')->pluck('id')->toArray();

        $this->assertContains($a->id, $ids);
        $response->assertSee($a->title);

        // STATUS: Failed
        $this->assertStringContainsString('assessment-status-failed', $html);

        // ACTION: Inactive
        $this->assertStringContainsString('btn-assessment-inactive', $html);
    }

    /**
     * CASE 7 & 8: Content Assessment + NO result => HIDDEN, Content Assessment + HAS result => VISIBLE
     */
    public function test_content_assessment_visibility_requires_user_result(): void
    {
        $tag = Str::random(8);
        $contentNoResult = Assesment::create([
            'title'                 => "Content_NoResult_{$tag}",
            'type'                  => 'content',
            'assasment_content_for' => 'content',
            'content_id'            => $this->content->id,
            'status'                => 'OK',
            'passing_grade'         => 75,
        ]);

        $contentHasResult = Assesment::create([
            'title'                 => "Content_HasResult_{$tag}",
            'type'                  => 'content',
            'assasment_content_for' => 'content',
            'content_id'            => $this->content->id,
            'status'                => 'OK',
            'passing_grade'         => 75,
        ]);
        AssesmentResult::create([
            'user_id'      => $this->userA->id,
            'assesment_id' => $contentHasResult->id,
            'score'        => 80,
            'pass'         => 1,
            'final'        => 1,
            'batch'        => 1,
            'user_answer'  => [],
        ]);

        $response = $this->actingAs($this->userA)->get(route('user.learning.dashboard.partial', ['tab' => 'assessment']));
        $ids = $response->viewData('assessments')->pluck('id')->toArray();

        $this->assertNotContains($contentNoResult->id, $ids);
        $this->assertContains($contentHasResult->id, $ids);
    }

    /**
     * CASE 9 & 10: Playlist Assessment + NO result => HIDDEN, Playlist Assessment + HAS result => VISIBLE
     */
    public function test_playlist_assessment_visibility_requires_user_result(): void
    {
        $tag = Str::random(8);
        $playlistNoResult = Assesment::create([
            'title'                 => "Playlist_NoResult_{$tag}",
            'type'                  => 'trainingplan',
            'assasment_content_for' => 'content',
            'trainingplan_id'       => $this->playlist->id,
            'status'                => 'OK',
            'passing_grade'         => 75,
        ]);

        $playlistHasResult = Assesment::create([
            'title'                 => "Playlist_HasResult_{$tag}",
            'type'                  => 'trainingplan',
            'assasment_content_for' => 'content',
            'trainingplan_id'       => $this->playlist->id,
            'status'                => 'OK',
            'passing_grade'         => 75,
        ]);
        AssesmentResult::create([
            'user_id'      => $this->userA->id,
            'assesment_id' => $playlistHasResult->id,
            'score'        => 80,
            'pass'         => 1,
            'final'        => 1,
            'batch'        => 1,
            'user_answer'  => [],
        ]);

        $response = $this->actingAs($this->userA)->get(route('user.learning.dashboard.partial', ['tab' => 'assessment']));
        $ids = $response->viewData('assessments')->pluck('id')->toArray();

        $this->assertNotContains($playlistNoResult->id, $ids);
        $this->assertContains($playlistHasResult->id, $ids);
    }

    /**
     * CASE 11: User A has result, User B does not => visible for A, hidden for B
     */
    public function test_user_isolation_for_assessment_visibility(): void
    {
        $tag = Str::random(8);
        $a = Assesment::create([
            'title'                 => "Isolation_Test_{$tag}",
            'type'                  => 'global',
            'assasment_content_for' => 'content',
            'status'                => 'OK',
            'passing_grade'         => 75,
        ]);
        AssesmentResult::create([
            'user_id'      => $this->userA->id,
            'assesment_id' => $a->id,
            'score'        => 80,
            'pass'         => 1,
            'final'        => 1,
            'batch'        => 1,
            'user_answer'  => [],
        ]);

        // Visible for User A
        $responseA = $this->actingAs($this->userA)->get(route('user.learning.dashboard.partial', ['tab' => 'assessment']));
        $idsA = $responseA->viewData('assessments')->pluck('id')->toArray();
        $this->assertContains($a->id, $idsA);

        // Hidden for User B
        $responseB = $this->actingAs($this->userB)->get(route('user.learning.dashboard.partial', ['tab' => 'assessment']));
        $idsB = $responseB->viewData('assessments')->pluck('id')->toArray();
        $this->assertNotContains($a->id, $idsB);
    }

    /**
     * CASE 12: Multiple results for same assessment => only one assessment row in list
     */
    public function test_multiple_results_for_same_assessment_renders_single_row(): void
    {
        $tag = Str::random(8);
        $a = Assesment::create([
            'title'                 => "MultiResult_{$tag}",
            'type'                  => 'content',
            'assasment_content_for' => 'content',
            'status'                => 'OK',
            'passing_grade'         => 75,
            'total_remedials'       => 2,
        ]);
        // Attempt 1: Failed
        AssesmentResult::create([
            'user_id'      => $this->userA->id,
            'assesment_id' => $a->id,
            'score'        => 50,
            'pass'         => 0,
            'final'        => 1,
            'batch'        => 1,
            'user_answer'  => [],
        ]);
        // Attempt 2: Passed
        AssesmentResult::create([
            'user_id'      => $this->userA->id,
            'assesment_id' => $a->id,
            'score'        => 90,
            'pass'         => 1,
            'final'        => 1,
            'batch'        => 1,
            'user_answer'  => [],
        ]);

        $response = $this->actingAs($this->userA)->get(route('user.learning.dashboard.partial', ['tab' => 'assessment']));
        $assessments = $response->viewData('assessments');
        $matching = $assessments->where('id', $a->id);

        $this->assertCount(1, $matching);
    }

    /**
     * CASE 13 & 14: Search behavior
     * Global NO result + search exact title => 0 row
     * Global HAS result + search exact title => 1 row
     */
    public function test_search_respects_result_requirement(): void
    {
        $tag = Str::random(8);
        $noResultTitle = "Search_NoResult_{$tag}";
        $hasResultTitle = "Search_HasResult_{$tag}";

        $noResult = Assesment::create([
            'title'                 => $noResultTitle,
            'type'                  => 'global',
            'assasment_content_for' => 'content',
            'status'                => 'OK',
            'passing_grade'         => 75,
        ]);

        $hasResult = Assesment::create([
            'title'                 => $hasResultTitle,
            'type'                  => 'global',
            'assasment_content_for' => 'content',
            'status'                => 'OK',
            'passing_grade'         => 75,
        ]);
        AssesmentResult::create([
            'user_id'      => $this->userA->id,
            'assesment_id' => $hasResult->id,
            'score'        => 80,
            'pass'         => 1,
            'final'        => 1,
            'batch'        => 1,
            'user_answer'  => [],
        ]);

        // Search for no result => 0 rows
        $response1 = $this->actingAs($this->userA)->get(route('user.learning.dashboard.partial', [
            'tab'               => 'assessment',
            'assessment_search' => $noResultTitle,
        ]));
        $this->assertCount(0, $response1->viewData('assessments'));

        // Search for has result => 1 row
        $response2 = $this->actingAs($this->userA)->get(route('user.learning.dashboard.partial', [
            'tab'               => 'assessment',
            'assessment_search' => $hasResultTitle,
        ]));
        $this->assertCount(1, $response2->viewData('assessments'));
        $this->assertSame($hasResult->id, $response2->viewData('assessments')->first()->id);
    }
}
