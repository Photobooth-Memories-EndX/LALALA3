<?php

namespace Tests\Feature;

use App\Exports\AssessmentExport;
use App\Http\Controllers\LearningAssessmentController;
use App\Models\Assesment;
use App\Models\AssesmentAssigned;
use App\Models\AssesmentResult;
use App\Models\Classification;
use App\Models\Content;
use App\Models\TrainingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class MyLearningAssessmentScoreAndFilterAuditTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Content $content;
    protected TrainingPlan $playlist;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'team'       => 'Audit Team',
            'department' => 'Engineering',
            'division'   => 'Core',
            'ssohash'    => 'audit-' . Str::uuid(),
            'sap_code'   => 'SAP-AUD-' . rand(1000, 9999),
            'id_emp'     => 'EMP-AUD-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $classification = Classification::first() ?? Classification::create([
            'name'        => 'Audit Class ' . Str::random(5),
            'description' => 'Desc',
        ]);

        $this->content = Content::create([
            'title'             => 'Audit Content ' . Str::random(5),
            'type'              => 'video',
            'classification_id' => $classification->id,
            'active'            => 'Active',
        ]);

        $this->playlist = TrainingPlan::create([
            'title'       => 'Audit Playlist ' . Str::random(5),
            'description' => 'Desc',
            'status'      => 'published',
        ]);
        $this->playlist->contents()->attach($this->content->id, ['content_order' => 1]);
    }

    private function createAssessmentWithResult(string $title, ?int $score, int $batch = 1, int $final = 1, string $fromdate = '2026-05-10 09:00:00', string $type = 'content'): Assesment
    {
        $assessment = Assesment::create([
            'title'                 => $title,
            'type'                  => $type,
            'assasment_content_for' => 'content',
            'content_id'            => $this->content->id,
            'status'                => 'OK',
            'batch'                 => $batch,
            'fromdate'              => $fromdate,
            'todate'                => '2026-12-31 23:59:59',
            'passing_grade'         => 75,
        ]);

        AssesmentAssigned::create([
            'assesment_id'         => $assessment->id,
            'assigned_id'          => $this->user->id_emp,
            'assigned_type'        => 'user',
            'assigned_description' => 'Audit Test',
        ]);

        if ($score !== null || $final === 1) {
            AssesmentResult::create([
                'user_id'      => $this->user->id,
                'assesment_id' => $assessment->id,
                'score'        => $score,
                'pass'         => ($score !== null && $score >= 75) ? 1 : 0,
                'final'        => $final,
                'batch'        => $batch,
                'user_answer'  => [],
            ]);
        }

        return $assessment;
    }

    /**
     * TEST A: SCORE BOUNDARY
     * score 0   -> < 60
     * score 59  -> < 60
     * score 60  -> 60-79
     * score 79  -> 60-79
     * score 80  -> 80-100
     * score 100 -> 80-100
     * NULL      -> not in any range
     */
    public function test_score_boundary_conditions_and_null_handling(): void
    {
        $tag = Str::random(6);
        $a0   = $this->createAssessmentWithResult("Score0_{$tag}", 0);
        $a59  = $this->createAssessmentWithResult("Score59_{$tag}", 59);
        $a60  = $this->createAssessmentWithResult("Score60_{$tag}", 60);
        $a79  = $this->createAssessmentWithResult("Score79_{$tag}", 79);
        $a80  = $this->createAssessmentWithResult("Score80_{$tag}", 80);
        $a100 = $this->createAssessmentWithResult("Score100_{$tag}", 100);
        $aNull = $this->createAssessmentWithResult("ScoreNull_{$tag}", null, 1, 0);

        $controller = app(LearningAssessmentController::class);

        // 1. Filter < 60 ('0_59')
        $reqLt60 = Request::create('/my-learning/partial/assessment', 'GET', ['assessment_filter_score' => '0_59']);
        $resultsLt60 = $controller->getFilteredUserAssessments($reqLt60, $this->user->id);
        $idsLt60 = $resultsLt60->pluck('id')->all();

        $this->assertContains($a0->id, $idsLt60, 'Score 0 must be in < 60');
        $this->assertContains($a59->id, $idsLt60, 'Score 59 must be in < 60');
        $this->assertNotContains($a60->id, $idsLt60, 'Score 60 must NOT be in < 60');
        $this->assertNotContains($a79->id, $idsLt60, 'Score 79 must NOT be in < 60');
        $this->assertNotContains($a80->id, $idsLt60, 'Score 80 must NOT be in < 60');
        $this->assertNotContains($a100->id, $idsLt60, 'Score 100 must NOT be in < 60');
        $this->assertNotContains($aNull->id, $idsLt60, 'Score NULL must NOT be in < 60');

        // 2. Filter 60 - 79 ('60_79')
        $req6079 = Request::create('/my-learning/partial/assessment', 'GET', ['assessment_filter_score' => '60_79']);
        $results6079 = $controller->getFilteredUserAssessments($req6079, $this->user->id);
        $ids6079 = $results6079->pluck('id')->all();

        $this->assertNotContains($a0->id, $ids6079, 'Score 0 must NOT be in 60-79');
        $this->assertNotContains($a59->id, $ids6079, 'Score 59 must NOT be in 60-79');
        $this->assertContains($a60->id, $ids6079, 'Score 60 must be in 60-79');
        $this->assertContains($a79->id, $ids6079, 'Score 79 must be in 60-79');
        $this->assertNotContains($a80->id, $ids6079, 'Score 80 must NOT be in 60-79');
        $this->assertNotContains($a100->id, $ids6079, 'Score 100 must NOT be in 60-79');
        $this->assertNotContains($aNull->id, $ids6079, 'Score NULL must NOT be in 60-79');

        // 3. Filter 80 - 100 ('80_100')
        $req80100 = Request::create('/my-learning/partial/assessment', 'GET', ['assessment_filter_score' => '80_100']);
        $results80100 = $controller->getFilteredUserAssessments($req80100, $this->user->id);
        $ids80100 = $results80100->pluck('id')->all();

        $this->assertNotContains($a0->id, $ids80100, 'Score 0 must NOT be in 80-100');
        $this->assertNotContains($a59->id, $ids80100, 'Score 59 must NOT be in 80-100');
        $this->assertNotContains($a60->id, $ids80100, 'Score 60 must NOT be in 80-100');
        $this->assertNotContains($a79->id, $ids80100, 'Score 79 must NOT be in 80-100');
        $this->assertContains($a80->id, $ids80100, 'Score 80 must be in 80-100');
        $this->assertContains($a100->id, $ids80100, 'Score 100 must be in 80-100');
        $this->assertNotContains($aNull->id, $ids80100, 'Score NULL must NOT be in 80-100');
    }

    /**
     * TEST B: TABLE DATASET === EXPORT DATASET
     * Verify identical IDs and ordering for all score filters.
     */
    public function test_table_dataset_strictly_matches_export_dataset_for_score_filters(): void
    {
        $tag = Str::random(6);
        $this->createAssessmentWithResult("ScoreLt_{$tag}", 45);
        $this->createAssessmentWithResult("ScoreMid_{$tag}", 68);
        $this->createAssessmentWithResult("ScoreHigh_{$tag}", 92);
        $this->createAssessmentWithResult("ScoreNull_{$tag}", null, 1, 0);

        $controller = app(LearningAssessmentController::class);

        foreach (['0_59', '60_79', '80_100', 'all'] as $scoreFilter) {
            $params = ['assessment_filter_score' => $scoreFilter];
            $request = Request::create('/my-learning/partial/assessment', 'GET', $params);

            // Table dataset
            $this->actingAs($this->user);
            $view = $controller->partial($request);
            $tableAssessments = $view->getData()['assessments'];
            $tableIds = $tableAssessments->pluck('id')->all();

            // Export dataset
            $exportAssessments = $controller->getFilteredUserAssessments($request, $this->user->id);
            $exportIds = $exportAssessments->pluck('id')->all();

            $this->assertSame($tableIds, $exportIds, "Table and Export IDs must be strictly identical for score filter [{$scoreFilter}]");
        }
    }

    /**
     * TEST C: COMBINATION FILTERS
     * Score + Batch, Score + Year, Score + Period, Score + Search, Score + Type
     */
    public function test_combined_filters_execute_with_and_logic(): void
    {
        $tag = Str::random(6);
        // Batch 1, Year 2025, Type content, Score 50
        $a1 = $this->createAssessmentWithResult("MatchItem_{$tag}", 50, 1, 1, '2025-05-15 10:00:00', 'content');
        // Batch 2, Year 2025, Type content, Score 50 (Different Batch)
        $a2 = $this->createAssessmentWithResult("DiffBatch_{$tag}", 50, 2, 1, '2025-05-15 10:00:00', 'content');
        // Batch 1, Year 2026, Type content, Score 50 (Different Year)
        $a3 = $this->createAssessmentWithResult("DiffYear_{$tag}", 50, 1, 1, '2026-05-15 10:00:00', 'content');
        // Batch 1, Year 2025, Type content, Score 85 (Different Score)
        $a4 = $this->createAssessmentWithResult("DiffScore_{$tag}", 85, 1, 1, '2025-05-15 10:00:00', 'content');

        $controller = app(LearningAssessmentController::class);

        // Combination 1: Score < 60 + Batch 1
        $req1 = Request::create('/my-learning/partial/assessment', 'GET', [
            'assessment_filter_score' => '0_59',
            'assessment_filter_batch' => '1',
        ]);
        $res1 = $controller->getFilteredUserAssessments($req1, $this->user->id)->pluck('id')->all();
        $this->assertContains($a1->id, $res1);
        $this->assertNotContains($a2->id, $res1);
        $this->assertNotContains($a4->id, $res1);

        // Combination 2: Score < 60 + Year 2025
        $req2 = Request::create('/my-learning/partial/assessment', 'GET', [
            'assessment_filter_score' => '0_59',
            'assessment_filter_year'  => '2025',
        ]);
        $res2 = $controller->getFilteredUserAssessments($req2, $this->user->id)->pluck('id')->all();
        $this->assertContains($a1->id, $res2);
        $this->assertNotContains($a3->id, $res2);

        // Combination 3: Score < 60 + Period (From 2025-05-01 to 2025-05-31)
        $req3 = Request::create('/my-learning/partial/assessment', 'GET', [
            'assessment_filter_score'     => '0_59',
            'assessment_filter_from_date' => '2025-05-01',
            'assessment_filter_to_date'   => '2025-05-31',
        ]);
        $res3 = $controller->getFilteredUserAssessments($req3, $this->user->id)->pluck('id')->all();
        $this->assertContains($a1->id, $res3);
        $this->assertNotContains($a3->id, $res3);

        // Combination 4: Score < 60 + Search keyword
        $req4 = Request::create('/my-learning/partial/assessment', 'GET', [
            'assessment_filter_score' => '0_59',
            'assessment_search'       => "MatchItem_{$tag}",
        ]);
        $res4 = $controller->getFilteredUserAssessments($req4, $this->user->id)->pluck('id')->all();
        $this->assertContains($a1->id, $res4);
        $this->assertNotContains($a2->id, $res4);
    }

    /**
     * TEST D: RESET BEHAVIOR
     * When reset is performed, all filters return to all/empty and export includes full dataset.
     */
    public function test_reset_removes_stale_filter_state_and_restores_dataset(): void
    {
        $tag = Str::random(6);
        $aLow = $this->createAssessmentWithResult("ResetLow_{$tag}", 40);
        $aHigh = $this->createAssessmentWithResult("ResetHigh_{$tag}", 95);

        $controller = app(LearningAssessmentController::class);

        // Filter active: Score < 60
        $filteredReq = Request::create('/my-learning/assessment/export', 'GET', [
            'assessment_filter_score' => '0_59',
        ]);
        $filteredList = $controller->getFilteredUserAssessments($filteredReq, $this->user->id)->pluck('id')->all();
        $this->assertContains($aLow->id, $filteredList);
        $this->assertNotContains($aHigh->id, $filteredList);

        // After Reset: Score returns to all
        $resetReq = Request::create('/my-learning/assessment/export', 'GET', [
            'assessment_filter_score' => 'all',
        ]);
        $resetList = $controller->getFilteredUserAssessments($resetReq, $this->user->id)->pluck('id')->all();
        $this->assertContains($aLow->id, $resetList);
        $this->assertContains($aHigh->id, $resetList);
    }

    /**
     * TEST E: REMEDIAL AND MULTIPLE ATTEMPTS
     * Displayed score === Filtered score === Exported score.
     */
    public function test_remedial_uses_canonical_best_final_score_across_table_filter_and_export(): void
    {
        $tag = Str::random(6);

        // Assessment with 2 attempts: Attempt 1 = 50, Remedial = 85
        $assessment = Assesment::create([
            'title'                 => "RemedialAssessment_{$tag}",
            'type'                  => 'content',
            'assasment_content_for' => 'content',
            'content_id'            => $this->content->id,
            'status'                => 'OK',
            'batch'                 => 1,
            'fromdate'              => '2026-05-10 09:00:00',
            'todate'                => '2026-12-31 23:59:59',
            'passing_grade'         => 75,
            'total_remedials'       => 2,
        ]);

        AssesmentAssigned::create([
            'assesment_id'         => $assessment->id,
            'assigned_id'          => $this->user->id_emp,
            'assigned_type'        => 'user',
            'assigned_description' => 'Remedial Test',
        ]);

        // Attempt 1: score 50 (failed)
        AssesmentResult::create([
            'user_id'      => $this->user->id,
            'assesment_id' => $assessment->id,
            'score'        => 50,
            'pass'         => 0,
            'final'        => 1,
            'batch'        => 1,
            'user_answer'  => [],
        ]);

        // Attempt 2 (remedial): score 85 (passed)
        AssesmentResult::create([
            'user_id'      => $this->user->id,
            'assesment_id' => $assessment->id,
            'score'        => 85,
            'pass'         => 1,
            'final'        => 1,
            'batch'        => 2,
            'user_answer'  => [],
        ]);

        $controller = app(LearningAssessmentController::class);

        // Canonical score must be 85!
        $canonical = LearningAssessmentController::resolveCanonicalAssessmentResult($assessment, $this->user->id);
        $this->assertEquals(85, $canonical->score);
        $this->assertEquals(1, $canonical->pass);

        // Filter < 60 must NOT include this assessment
        $reqLt60 = Request::create('/my-learning/partial/assessment', 'GET', ['assessment_filter_score' => '0_59']);
        $listLt60 = $controller->getFilteredUserAssessments($reqLt60, $this->user->id)->pluck('id')->all();
        $this->assertNotContains($assessment->id, $listLt60, 'Remedial with final score 85 must NOT appear in < 60 filter');

        // Filter 80 - 100 MUST include this assessment
        $req80100 = Request::create('/my-learning/partial/assessment', 'GET', ['assessment_filter_score' => '80_100']);
        $list80100 = $controller->getFilteredUserAssessments($req80100, $this->user->id)->pluck('id')->all();
        $this->assertContains($assessment->id, $list80100, 'Remedial with final score 85 MUST appear in 80-100 filter');

        // Verify Excel export output row displays score 85
        Excel::fake();
        $this->actingAs($this->user)->get(route('user.learning.assessment.export', [
            'assessment_filter_score' => '80_100',
            'assessment_search'       => "RemedialAssessment_{$tag}",
        ]));

        Excel::assertDownloaded('Assessment_List_' . now()->format('Y-m-d') . '.xlsx', function ($export) {
            $array = $export->array();
            $this->assertCount(2, $array); // header + 1 row
            $this->assertEquals(85, $array[1][5], 'Exported score must be the canonical remedial score 85');
            $this->assertEquals('Passed', $array[1][7]);
            return true;
        });
    }
}
