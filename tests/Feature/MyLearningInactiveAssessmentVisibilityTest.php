<?php

namespace Tests\Feature;

use App\Models\Assesment;
use App\Models\AssesmentAssigned;
use App\Models\AssesmentResult;
use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
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
            'active'      => 'Active',
        ]);
        $this->playlist->contents()->attach($this->content->id, ['content_order' => 1]);

        $tag = Str::random(8);

        // 1. Active assessment (ON) - Global, NOT taken by anyone
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

        // Active Global assessment NOT taken does NOT appear
        $this->assertNotContains($this->activeAssessment->id, $ids);

        // Inactive assessment NOT taken does NOT appear
        $this->assertNotContains($this->inactiveAssessmentNotTaken->id, $ids);
    }

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

    public function test_inactive_assessment_renders_result_data_and_inactive_button(): void
    {
        $response = $this->actingAs($this->userA)->get(route('user.learning.dashboard.partial', [
            'tab' => 'assessment',
        ]));

        $response->assertStatus(200);
        $html = $response->getContent();

        $response->assertSee($this->inactiveAssessmentTaken->title);
        $this->assertStringContainsString('data-assessment-score="85"', $html);
        $this->assertStringContainsString('Passed', $html);
        $this->assertStringContainsString('btn-assessment-inactive', $html);
        $this->assertStringContainsString('Inactive', $html);
        $this->assertStringContainsString('assessment-score-popover-card', $html);
        $this->assertStringContainsString('assessment-mini-history-table', $html);
    }

    public function test_badge_counter_syncs_with_inactive_assessments_taken(): void
    {
        $response = $this->actingAs($this->userA)->get(route('user.learning.dashboard.partial', [
            'tab' => 'assessment',
        ]));

        $stats = $response->viewData('assessmentStats');
        $statsByLabel = collect($stats)->pluck('value', 'label');

        $this->assertGreaterThanOrEqual(1, $statsByLabel->get('Total Assessment', 0));
        $this->assertGreaterThanOrEqual(1, $statsByLabel->get('Passed', 0));
    }

    public function test_user_can_access_result_page_for_inactive_assessment(): void
    {
        $response = $this->actingAs($this->userA)->get(
            route('assesment.user.result', $this->inactiveAssessmentTaken->slug)
        );

        $response->assertStatus(200);
    }

    public function test_user_cannot_start_or_retake_inactive_assessment(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException::class);

        $accessService = app(\App\Services\AssessmentAccessService::class);
        $accessService->authorize($this->inactiveAssessmentTaken, $this->userA);
    }

    /**
     * TEST 1: Content 0% + Active + No Result => HIDDEN
     */
    public function test_content_0_percent_active_no_result_is_hidden(): void
    {
        $tag = Str::random(8);
        $c = Content::create(['title' => "Content_0_{$tag}", 'type' => 'video', 'active' => 'Active']);
        ContentUser::create(['user_id' => $this->userA->id, 'content_id' => $c->id, 'progress' => 0, 'finish' => '0']);

        $a = Assesment::create([
            'title'                 => "Assesment_C0_{$tag}",
            'type'                  => 'content',
            'assasment_content_for' => 'content',
            'content_id'            => $c->id,
            'status'                => 'OK',
            'passing_grade'         => 75,
        ]);

        $response = $this->actingAs($this->userA)->get(route('user.learning.dashboard.partial', ['tab' => 'assessment']));
        $ids = $response->viewData('assessments')->pluck('id')->toArray();

        $this->assertNotContains($a->id, $ids);
    }

    /**
     * TEST 2: Content 99% + Active + No Result => HIDDEN
     */
    public function test_content_99_percent_active_no_result_is_hidden(): void
    {
        $tag = Str::random(8);
        $c = Content::create(['title' => "Content_99_{$tag}", 'type' => 'video', 'active' => 'Active']);
        ContentUser::create(['user_id' => $this->userA->id, 'content_id' => $c->id, 'progress' => 99, 'finish' => '0']);

        $a = Assesment::create([
            'title'                 => "Assesment_C99_{$tag}",
            'type'                  => 'content',
            'assasment_content_for' => 'content',
            'content_id'            => $c->id,
            'status'                => 'OK',
            'passing_grade'         => 75,
        ]);

        $response = $this->actingAs($this->userA)->get(route('user.learning.dashboard.partial', ['tab' => 'assessment']));
        $ids = $response->viewData('assessments')->pluck('id')->toArray();

        $this->assertNotContains($a->id, $ids);
    }

    /**
     * TEST 3: Content 100% + Active + No Result => VISIBLE, Pending, Score -, Start Test
     */
    public function test_content_100_percent_active_no_result_is_visible_with_pending_score_dash_and_start_test(): void
    {
        $tag = Str::random(8);
        $c = Content::create(['title' => "Content_100_{$tag}", 'type' => 'video', 'active' => 'Active']);
        ContentUser::create(['user_id' => $this->userA->id, 'content_id' => $c->id, 'progress' => 100, 'finish' => '1']);

        $a = Assesment::create([
            'title'                 => "Assesment_C100_{$tag}",
            'type'                  => 'content',
            'assasment_content_for' => 'content',
            'content_id'            => $c->id,
            'status'                => 'OK',
            'passing_grade'         => 75,
        ]);

        $response = $this->actingAs($this->userA)->get(route('user.learning.dashboard.partial', ['tab' => 'assessment']));
        $ids = $response->viewData('assessments')->pluck('id')->toArray();
        $html = $response->getContent();

        $this->assertContains($a->id, $ids);
        $response->assertSee($a->title);

        // STATUS: Pending
        $this->assertStringContainsString('assessment-status-pending', $html);
        $this->assertStringContainsString('Pending', $html);

        // SCORE: -
        $this->assertStringContainsString('data-assessment-score=""', $html);

        // ACTION: Start Test
        $this->assertStringContainsString('btn-assessment-start', $html);
        $this->assertStringContainsString('Start Test', $html);
        $this->assertStringNotContainsString('btn-assessment-review', $html);
    }

    /**
     * TEST 4: Content 100% + Passed Result => VISIBLE, Passed
     */
    public function test_content_100_percent_active_has_passed_result_is_visible_with_passed_action(): void
    {
        $tag = Str::random(8);
        $c = Content::create(['title' => "Content_Passed_{$tag}", 'type' => 'video', 'active' => 'Active']);
        ContentUser::create(['user_id' => $this->userA->id, 'content_id' => $c->id, 'progress' => 100, 'finish' => '1']);

        $a = Assesment::create([
            'title'                 => "Assesment_CPassed_{$tag}",
            'type'                  => 'content',
            'assasment_content_for' => 'content',
            'content_id'            => $c->id,
            'status'                => 'OK',
            'passing_grade'         => 75,
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
        $ids = $response->viewData('assessments')->pluck('id')->toArray();
        $html = $response->getContent();

        $this->assertContains($a->id, $ids);
        $this->assertStringContainsString('btn-assessment-passed', $html);
        $this->assertStringContainsString('Passed', $html);
        $this->assertStringNotContainsString('btn-assessment-review', $html);
    }

    /**
     * TEST 5: Content 100% + Inactive + No Result => HIDDEN
     */
    public function test_content_100_percent_inactive_no_result_is_hidden(): void
    {
        $tag = Str::random(8);
        $c = Content::create(['title' => "Content_Inactive_{$tag}", 'type' => 'video', 'active' => 'Active']);
        ContentUser::create(['user_id' => $this->userA->id, 'content_id' => $c->id, 'progress' => 100, 'finish' => '1']);

        $a = Assesment::create([
            'title'                 => "Assesment_CInactive_{$tag}",
            'type'                  => 'content',
            'assasment_content_for' => 'content',
            'content_id'            => $c->id,
            'status'                => 'NOT',
            'passing_grade'         => 75,
        ]);

        $response = $this->actingAs($this->userA)->get(route('user.learning.dashboard.partial', ['tab' => 'assessment']));
        $ids = $response->viewData('assessments')->pluck('id')->toArray();

        $this->assertNotContains($a->id, $ids);
    }

    /**
     * TEST 6: Content Inactive + Has Result => VISIBLE, Inactive
     */
    public function test_content_inactive_has_result_is_visible_with_inactive_action(): void
    {
        $tag = Str::random(8);
        $c = Content::create(['title' => "Content_InacRes_{$tag}", 'type' => 'video', 'active' => 'Active']);

        $a = Assesment::create([
            'title'                 => "Assesment_CInacRes_{$tag}",
            'type'                  => 'content',
            'assasment_content_for' => 'content',
            'content_id'            => $c->id,
            'status'                => 'NOT',
            'passing_grade'         => 75,
        ]);
        AssesmentResult::create([
            'user_id'      => $this->userA->id,
            'assesment_id' => $a->id,
            'score'        => 65,
            'pass'         => 0,
            'final'        => 1,
            'batch'        => 1,
            'user_answer'  => [],
        ]);

        $response = $this->actingAs($this->userA)->get(route('user.learning.dashboard.partial', ['tab' => 'assessment']));
        $ids = $response->viewData('assessments')->pluck('id')->toArray();
        $html = $response->getContent();

        $this->assertContains($a->id, $ids);
        $this->assertStringContainsString('btn-assessment-inactive', $html);
        $this->assertStringContainsString('Inactive', $html);
    }

    /**
     * TEST 7: Playlist child progress partial (100 / 100 / 80) + No Result => HIDDEN
     */
    public function test_playlist_child_progress_partial_100_100_80_no_result_is_hidden(): void
    {
        $tag = Str::random(8);
        $c1 = Content::create(['title' => "PL_C1_{$tag}", 'active' => 'Active']);
        $c2 = Content::create(['title' => "PL_C2_{$tag}", 'active' => 'Active']);
        $c3 = Content::create(['title' => "PL_C3_{$tag}", 'active' => 'Active']);

        $tp = TrainingPlan::create(['title' => "TP_Partial_{$tag}", 'active' => 'Active', 'status' => 'published']);
        TrainingPlanDetail::create(['trainingplan_id' => $tp->id, 'content_id' => $c1->id, 'content_order' => 1]);
        TrainingPlanDetail::create(['trainingplan_id' => $tp->id, 'content_id' => $c2->id, 'content_order' => 2]);
        TrainingPlanDetail::create(['trainingplan_id' => $tp->id, 'content_id' => $c3->id, 'content_order' => 3]);

        // 100 / 100 / 80
        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $tp->id, 'content_id' => $c1->id, 'progress' => 100, 'finish' => '1']);
        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $tp->id, 'content_id' => $c2->id, 'progress' => 100, 'finish' => '1']);
        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $tp->id, 'content_id' => $c3->id, 'progress' => 80, 'finish' => '0']);

        $a = Assesment::create([
            'title'                 => "Assesment_TP_Partial_{$tag}",
            'type'                  => 'trainingplan',
            'assasment_content_for' => 'playlist',
            'trainingplan_id'       => $tp->id,
            'status'                => 'OK',
            'passing_grade'         => 75,
        ]);

        $response = $this->actingAs($this->userA)->get(route('user.learning.dashboard.partial', ['tab' => 'assessment']));
        $ids = $response->viewData('assessments')->pluck('id')->toArray();

        $this->assertNotContains($a->id, $ids);
    }

    /**
     * TEST 8: Playlist child progress all 100% (100 / 100 / 100) + Active + No Result => VISIBLE, Pending, Start Test
     */
    public function test_playlist_child_progress_all_100_active_no_result_is_visible_with_pending_and_start_test(): void
    {
        $tag = Str::random(8);
        $c1 = Content::create(['title' => "PL_C1_{$tag}", 'active' => 'Active']);
        $c2 = Content::create(['title' => "PL_C2_{$tag}", 'active' => 'Active']);
        $c3 = Content::create(['title' => "PL_C3_{$tag}", 'active' => 'Active']);

        $tp = TrainingPlan::create(['title' => "TP_Full_{$tag}", 'active' => 'Active', 'status' => 'published']);
        TrainingPlanDetail::create(['trainingplan_id' => $tp->id, 'content_id' => $c1->id, 'content_order' => 1]);
        TrainingPlanDetail::create(['trainingplan_id' => $tp->id, 'content_id' => $c2->id, 'content_order' => 2]);
        TrainingPlanDetail::create(['trainingplan_id' => $tp->id, 'content_id' => $c3->id, 'content_order' => 3]);

        // 100 / 100 / 100
        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $tp->id, 'content_id' => $c1->id, 'progress' => 100, 'finish' => '1']);
        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $tp->id, 'content_id' => $c2->id, 'progress' => 100, 'finish' => '1']);
        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $tp->id, 'content_id' => $c3->id, 'progress' => 100, 'finish' => '1']);

        $a = Assesment::create([
            'title'                 => "Assesment_TP_Full_{$tag}",
            'type'                  => 'trainingplan',
            'assasment_content_for' => 'playlist',
            'trainingplan_id'       => $tp->id,
            'status'                => 'OK',
            'passing_grade'         => 75,
        ]);

        $response = $this->actingAs($this->userA)->get(route('user.learning.dashboard.partial', ['tab' => 'assessment']));
        $ids = $response->viewData('assessments')->pluck('id')->toArray();
        $html = $response->getContent();

        $this->assertContains($a->id, $ids);
        $this->assertStringContainsString('assessment-status-pending', $html);
        $this->assertStringContainsString('Pending', $html);
        $this->assertStringContainsString('btn-assessment-start', $html);
        $this->assertStringContainsString('Start Test', $html);
    }

    /**
     * TEST 9: Playlist 100% + Inactive + No Result => HIDDEN
     */
    public function test_playlist_100_percent_inactive_no_result_is_hidden(): void
    {
        $tag = Str::random(8);
        $c1 = Content::create(['title' => "PL_C1_{$tag}", 'active' => 'Active']);
        $tp = TrainingPlan::create(['title' => "TP_Inac_{$tag}", 'active' => 'Active', 'status' => 'published']);
        TrainingPlanDetail::create(['trainingplan_id' => $tp->id, 'content_id' => $c1->id, 'content_order' => 1]);

        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $tp->id, 'content_id' => $c1->id, 'progress' => 100, 'finish' => '1']);

        $a = Assesment::create([
            'title'                 => "Assesment_TP_Inac_{$tag}",
            'type'                  => 'trainingplan',
            'assasment_content_for' => 'playlist',
            'trainingplan_id'       => $tp->id,
            'status'                => 'NOT',
            'passing_grade'         => 75,
        ]);

        $response = $this->actingAs($this->userA)->get(route('user.learning.dashboard.partial', ['tab' => 'assessment']));
        $ids = $response->viewData('assessments')->pluck('id')->toArray();

        $this->assertNotContains($a->id, $ids);
    }

    /**
     * TEST 10: Playlist Inactive + Has Result => VISIBLE, Inactive
     */
    public function test_playlist_inactive_has_result_is_visible_with_inactive_action(): void
    {
        $tag = Str::random(8);
        $tp = TrainingPlan::create(['title' => "TP_InacRes_{$tag}", 'active' => 'Active', 'status' => 'published']);

        $a = Assesment::create([
            'title'                 => "Assesment_TP_InacRes_{$tag}",
            'type'                  => 'trainingplan',
            'assasment_content_for' => 'playlist',
            'trainingplan_id'       => $tp->id,
            'status'                => 'NOT',
            'passing_grade'         => 75,
        ]);
        AssesmentResult::create([
            'user_id'      => $this->userA->id,
            'assesment_id' => $a->id,
            'score'        => 85,
            'pass'         => 1,
            'final'        => 1,
            'batch'        => 1,
            'user_answer'  => [],
        ]);

        $response = $this->actingAs($this->userA)->get(route('user.learning.dashboard.partial', ['tab' => 'assessment']));
        $ids = $response->viewData('assessments')->pluck('id')->toArray();
        $html = $response->getContent();

        $this->assertContains($a->id, $ids);
        $this->assertStringContainsString('btn-assessment-inactive', $html);
        $this->assertStringContainsString('Inactive', $html);
    }

    /**
     * TEST 11: Global Active + No Result => HIDDEN
     */
    public function test_global_active_no_result_is_hidden(): void
    {
        $response = $this->actingAs($this->userA)->get(route('user.learning.dashboard.partial', ['tab' => 'assessment']));
        $ids = $response->viewData('assessments')->pluck('id')->toArray();

        $this->assertNotContains($this->activeAssessment->id, $ids);
    }

    /**
     * TEST 12: Global Inactive + No Result => HIDDEN
     */
    public function test_global_inactive_no_result_is_hidden(): void
    {
        $response = $this->actingAs($this->userA)->get(route('user.learning.dashboard.partial', ['tab' => 'assessment']));
        $ids = $response->viewData('assessments')->pluck('id')->toArray();

        $this->assertNotContains($this->inactiveAssessmentNotTaken->id, $ids);
    }

    /**
     * Global Expired + No Result => HIDDEN
     */
    public function test_global_expired_no_result_is_hidden(): void
    {
        $tag = Str::random(8);
        $expired = Assesment::create([
            'title'                 => "Global_Expired_{$tag}",
            'type'                  => 'global',
            'assasment_content_for' => 'content',
            'status'                => 'OK',
            'passing_grade'         => 75,
            'fromdate'              => now()->subDays(10),
            'todate'                => now()->subDays(2),
            'has_access_period'     => 1,
        ]);

        $response = $this->actingAs($this->userA)->get(route('user.learning.dashboard.partial', ['tab' => 'assessment']));
        $ids = $response->viewData('assessments')->pluck('id')->toArray();

        $this->assertNotContains($expired->id, $ids);
    }

    /**
     * TEST 13: Global + Has Result => VISIBLE according to history rule
     */
    public function test_global_has_result_is_visible_according_to_history_rule(): void
    {
        $tag = Str::random(8);
        $a = Assesment::create([
            'title'                 => "Global_HasResult_{$tag}",
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

        $response = $this->actingAs($this->userA)->get(route('user.learning.dashboard.partial', ['tab' => 'assessment']));
        $ids = $response->viewData('assessments')->pluck('id')->toArray();

        $this->assertContains($a->id, $ids);
    }

    /**
     * TEST 14: User isolation: User A complete, User B incomplete => only A sees ready assessment
     */
    public function test_user_isolation_for_assessment_visibility(): void
    {
        $tag = Str::random(8);
        $c = Content::create(['title' => "Content_Iso_{$tag}", 'type' => 'video', 'active' => 'Active']);
        // Only User A completed 100%
        ContentUser::create(['user_id' => $this->userA->id, 'content_id' => $c->id, 'progress' => 100, 'finish' => '1']);

        $a = Assesment::create([
            'title'                 => "Isolation_Test_{$tag}",
            'type'                  => 'content',
            'assasment_content_for' => 'content',
            'content_id'            => $c->id,
            'status'                => 'OK',
            'passing_grade'         => 75,
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
     * TEST 15: Multiple assesment_results => one assessment row
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
     * TEST 16: Search ready Content assessment is found
     */
    public function test_search_ready_content_assessment_is_found(): void
    {
        $tag = Str::random(8);
        $c = Content::create(['title' => "Content_SearchC_{$tag}", 'type' => 'video', 'active' => 'Active']);
        ContentUser::create(['user_id' => $this->userA->id, 'content_id' => $c->id, 'progress' => 100, 'finish' => '1']);

        $title = "SearchReadyContent_{$tag}";
        $a = Assesment::create([
            'title'                 => $title,
            'type'                  => 'content',
            'assasment_content_for' => 'content',
            'content_id'            => $c->id,
            'status'                => 'OK',
            'passing_grade'         => 75,
        ]);

        $response = $this->actingAs($this->userA)->get(route('user.learning.dashboard.partial', [
            'tab'               => 'assessment',
            'assessment_search' => $title,
        ]));

        $assessments = $response->viewData('assessments');
        $this->assertCount(1, $assessments);
        $this->assertSame($a->id, $assessments->first()->id);
    }

    /**
     * TEST 17: Search ready Playlist assessment is found
     */
    public function test_search_ready_playlist_assessment_is_found(): void
    {
        $tag = Str::random(8);
        $c = Content::create(['title' => "PL_Search_{$tag}", 'active' => 'Active']);
        $tp = TrainingPlan::create(['title' => "TP_Search_{$tag}", 'active' => 'Active', 'status' => 'published']);
        TrainingPlanDetail::create(['trainingplan_id' => $tp->id, 'content_id' => $c->id, 'content_order' => 1]);

        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $tp->id, 'content_id' => $c->id, 'progress' => 100, 'finish' => '1']);

        $title = "SearchReadyPlaylist_{$tag}";
        $a = Assesment::create([
            'title'                 => $title,
            'type'                  => 'trainingplan',
            'assasment_content_for' => 'playlist',
            'trainingplan_id'       => $tp->id,
            'status'                => 'OK',
            'passing_grade'         => 75,
        ]);

        $response = $this->actingAs($this->userA)->get(route('user.learning.dashboard.partial', [
            'tab'               => 'assessment',
            'assessment_search' => $title,
        ]));

        $assessments = $response->viewData('assessments');
        $this->assertCount(1, $assessments);
        $this->assertSame($a->id, $assessments->first()->id);
    }

    /**
     * TEST 18: Pending filter returns ready assessments
     */
    public function test_pending_filter_returns_ready_assessments(): void
    {
        $tag = Str::random(8);
        $c = Content::create(['title' => "Content_FilterPend_{$tag}", 'type' => 'video', 'active' => 'Active']);
        ContentUser::create(['user_id' => $this->userA->id, 'content_id' => $c->id, 'progress' => 100, 'finish' => '1']);

        $a = Assesment::create([
            'title'                 => "Assesment_FilterPend_{$tag}",
            'type'                  => 'content',
            'assasment_content_for' => 'content',
            'content_id'            => $c->id,
            'status'                => 'OK',
            'passing_grade'         => 75,
        ]);

        $response = $this->actingAs($this->userA)->get(route('user.learning.dashboard.partial', [
            'tab'                      => 'assessment',
            'assessment_filter_status' => 'pending',
        ]));

        $ids = $response->viewData('assessments')->pluck('id')->toArray();
        $this->assertContains($a->id, $ids);
    }

    /**
     * TEST 19: Active Passed => ACTION Passed, NOT Review
     */
    public function test_active_passed_action_is_passed_not_review(): void
    {
        $tag = Str::random(8);
        $a = Assesment::create([
            'title'                 => "Global_Passed_{$tag}",
            'type'                  => 'global',
            'assasment_content_for' => 'content',
            'status'                => 'OK',
            'passing_grade'         => 75,
        ]);
        AssesmentResult::create([
            'user_id'      => $this->userA->id,
            'assesment_id' => $a->id,
            'score'        => 85,
            'pass'         => 1,
            'final'        => 1,
            'batch'        => 1,
            'user_answer'  => [],
        ]);

        $response = $this->actingAs($this->userA)->get(route('user.learning.dashboard.partial', ['tab' => 'assessment']));
        $html = $response->getContent();

        $this->assertStringContainsString('btn-assessment-passed', $html);
        $this->assertStringContainsString('Passed', $html);
        $this->assertStringNotContainsString('btn-assessment-review', $html);
    }

    /**
     * TEST 20: Historical score popover and result logic remains intact
     */
    public function test_historical_score_popover_and_result_logic_remains_intact(): void
    {
        $tag = Str::random(8);
        $a = Assesment::create([
            'title'                 => "History_Popover_{$tag}",
            'type'                  => 'global',
            'assasment_content_for' => 'content',
            'status'                => 'OK',
            'passing_grade'         => 75,
        ]);
        AssesmentResult::create([
            'user_id'      => $this->userA->id,
            'assesment_id' => $a->id,
            'score'        => 88,
            'pass'         => 1,
            'final'        => 1,
            'batch'        => 1,
            'user_answer'  => [],
        ]);

        $response = $this->actingAs($this->userA)->get(route('user.learning.dashboard.partial', ['tab' => 'assessment']));
        $html = $response->getContent();

        $response->assertSee($a->title);
        $this->assertStringContainsString('data-assessment-score="88"', $html);
        $this->assertStringContainsString('assessment-score-popover-card', $html);
        $this->assertStringContainsString('assessment-mini-history-table', $html);
    }
}
