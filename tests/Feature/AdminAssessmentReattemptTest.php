<?php

namespace Tests\Feature;

use App\Models\Assesment;
use App\Models\AssesmentQuestion;
use App\Models\AssesmentResult;
use App\Models\TrainingPlan;
use App\Models\User;
use App\Services\AssessmentAccessService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminAssessmentReattemptTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private User $superAdmin;
    private User $regularUser;
    private AssessmentAccessService $accessService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createTestUser(2, 'Admin Tester');
        $this->superAdmin = $this->createTestUser(3, 'Super Admin Tester');
        $this->regularUser = $this->createTestUser(1, 'Regular User Tester');
        $this->accessService = app(AssessmentAccessService::class);
    }

    private function createTestUser(int $role, string $namePrefix): User
    {
        return User::create([
            'name'       => $namePrefix . ' ' . uniqid(),
            'email'      => 'user_' . uniqid() . '@example.com',
            'password'   => bcrypt('secret'),
            'role'       => $role,
            'team'       => 'TEST-TEAM',
            'department' => 'TEST-DEPT',
            'division'   => 'TEST-DIV',
            'id_emp'     => 'EMP' . rand(10000, 99999),
            'ssohash'    => Str::random(32),
            'sap_code'   => 'SAP' . rand(1000, 9999),
            'active'     => 1,
        ]);
    }

    private function createGlobalAssessment(int $batch = 1, string $status = 'OK', ?string $fromdate = null, ?string $todate = null): Assesment
    {
        $assessment = Assesment::create([
            'title'           => 'Global Assessment Test ' . uniqid(),
            'type'            => 'global',
            'status'          => $status,
            'passing_grade'   => 70,
            'batch'           => $batch,
            'fromdate'        => $fromdate,
            'todate'          => $todate,
            'total_remedials' => 0,
            'unique_code'     => 'TG' . strtoupper(Str::random(6)),
        ]);

        AssesmentQuestion::create([
            'assesment_id' => $assessment->id,
            'question'     => 'Q1 Global',
            'option'       => ['a' => 'Ans 1', 'b' => 'Ans 2'],
            'answer'       => 'a',
        ]);
        AssesmentQuestion::create([
            'assesment_id' => $assessment->id,
            'question'     => 'Q2 Global',
            'option'       => ['a' => 'Ans 1', 'b' => 'Ans 2'],
            'answer'       => 'b',
        ]);

        return $assessment;
    }

    private function createGlobalAssessmentWith10Questions(int $batch = 1, int $passingGrade = 80, string $status = 'OK', ?string $fromdate = null, ?string $todate = null): Assesment
    {
        $assessment = Assesment::create([
            'title'           => 'Global Assessment 10Q ' . uniqid(),
            'type'            => 'global',
            'status'          => $status,
            'passing_grade'   => $passingGrade,
            'batch'           => $batch,
            'fromdate'        => $fromdate,
            'todate'          => $todate,
            'total_remedials' => 0,
            'unique_code'     => 'TG10' . strtoupper(Str::random(4)),
        ]);

        for ($i = 1; $i <= 10; $i++) {
            AssesmentQuestion::create([
                'assesment_id' => $assessment->id,
                'question'     => "Question $i Global 10Q",
                'option'       => ['a' => "Option A $i", 'b' => "Option B $i", 'c' => "Option C $i", 'd' => "Option D $i"],
                'answer'       => 'a',
            ]);
        }

        return $assessment;
    }

    private function createContentAssessment(int $remedials = 1): Assesment
    {
        $assessment = Assesment::create([
            'title'           => 'Content Assessment Test ' . uniqid(),
            'type'            => 'content',
            'status'          => 'OK',
            'passing_grade'   => 80,
            'batch'           => 1,
            'total_remedials' => $remedials,
            'unique_code'     => 'TC' . strtoupper(Str::random(6)),
        ]);

        AssesmentQuestion::create([
            'assesment_id' => $assessment->id,
            'question'     => 'Q1 Content',
            'option'       => ['a' => 'Ans 1', 'b' => 'Ans 2'],
            'answer'       => 'a',
        ]);
        AssesmentQuestion::create([
            'assesment_id' => $assessment->id,
            'question'     => 'Q2 Content',
            'option'       => ['a' => 'Ans 1', 'b' => 'Ans 2'],
            'answer'       => 'b',
        ]);

        return $assessment;
    }

    private function createPlaylistAssessment(int $remedials = 1): Assesment
    {
        $playlist = TrainingPlan::create([
            'title'  => 'Test Playlist ' . uniqid(),
            'status' => 'OK',
        ]);

        $assessment = Assesment::create([
            'title'            => 'Playlist Assessment Test ' . uniqid(),
            'type'             => 'trainingplan',
            'trainingplan_id'  => $playlist->id,
            'status'           => 'OK',
            'passing_grade'    => 75,
            'batch'            => 1,
            'total_remedials'  => $remedials,
            'unique_code'      => 'TP' . strtoupper(Str::random(6)),
        ]);

        AssesmentQuestion::create([
            'assesment_id' => $assessment->id,
            'question'     => 'Q1 Playlist',
            'option'       => ['a' => 'Ans 1', 'b' => 'Ans 2'],
            'answer'       => 'a',
        ]);

        return $assessment;
    }

    /**
     * TEST 1: Regular User + Global + Batch 1 -> Attempt 1 (10 Soal, 8 Benar -> Score 80)
     * Expected: PASS, Score = 80, Result Page renders 80.
     */
    public function test_1_regular_user_global_batch_1_attempt_1_scoring(): void
    {
        $assessment = $this->createGlobalAssessmentWith10Questions(1, 80);
        $questions = $assessment->questions()->get();

        $res = $this->actingAs($this->regularUser)->get(route('assesment.user.view', $assessment->slug));
        $res->assertStatus(200);

        $draft = AssesmentResult::where('user_id', $this->regularUser->id)
            ->where('assesment_id', $assessment->id)
            ->where('batch', 1)
            ->where('final', 0)
            ->firstOrFail();

        $submissionToken = session("assessment_submission_token.{$assessment->id}.{$assessment->batch}", (string) Str::uuid());
        $answers = ['submission_token' => $submissionToken];
        
        // 8 correct ('a'), 2 wrong ('b')
        foreach ($questions as $index => $q) {
            $answers[$q->id] = ($index < 8) ? 'a' : 'b';
        }

        $submitRes = $this->actingAs($this->regularUser)->post(route('assesment.user.store', $assessment->slug), $answers);
        $submitRes->assertRedirect(route('assesment.user.result', $assessment->slug));

        $draft->refresh();
        $this->assertEquals(1, $draft->final);
        $this->assertEquals(80, $draft->score);
        $this->assertEquals(1, $draft->pass);

        // Result page view renders score 80
        $resultView = $this->actingAs($this->regularUser)->get(route('assesment.user.result', $assessment->slug));
        $resultView->assertStatus(200);
        $resultView->assertSee('80');
        $resultView->assertSee('Pass');
    }

    /**
     * TEST 2: Regular User + Global + Batch 1 -> Attempt 2
     * Expected: BLOCKED.
     */
    public function test_2_regular_user_global_batch_1_attempt_2_blocked(): void
    {
        $assessment = $this->createGlobalAssessment();
        $questions = $assessment->questions()->get();

        // 1st attempt: fail
        $this->actingAs($this->regularUser)->get(route('assesment.user.view', $assessment->slug));
        $submissionToken = session("assessment_submission_token.{$assessment->id}.{$assessment->batch}", (string) Str::uuid());
        $this->actingAs($this->regularUser)->post(route('assesment.user.store', $assessment->slug), [
            'submission_token' => $submissionToken,
            $questions[0]->id => 'wrong',
            $questions[1]->id => 'wrong',
        ]);

        // Attempt 2: Must be blocked
        $reason = $this->accessService->denialReason($assessment, $this->regularUser);
        $this->assertEquals(AssessmentAccessService::REASON_MAX_ATTEMPTS, $reason);

        $viewAgain = $this->actingAs($this->regularUser)->get(route('assesment.user.view', $assessment->slug));
        $this->assertTrue(in_array($viewAgain->status(), [200, 403]));
        if ($viewAgain->status() === 200) {
            $viewAgain->assertViewIs('user.assesment.done');
        }
    }

    /**
     * TEST 3: Admin + Global + Batch 1 -> Attempt 1 (10 Soal, 8 Benar -> Score 80)
     * Expected: PASS, Score = 80.
     */
    public function test_3_admin_global_batch_1_attempt_1_scoring(): void
    {
        $assessment = $this->createGlobalAssessmentWith10Questions(1, 80);
        $questions = $assessment->questions()->get();

        $res = $this->actingAs($this->admin)->get(route('assesment.user.view', $assessment->slug));
        $res->assertStatus(200);

        $draft = AssesmentResult::where('user_id', $this->admin->id)
            ->where('assesment_id', $assessment->id)
            ->where('batch', 1)
            ->where('final', 0)
            ->firstOrFail();

        $submissionToken = session("assessment_submission_token.{$assessment->id}.{$assessment->batch}", (string) Str::uuid());
        $answers = ['submission_token' => $submissionToken];
        // 8 correct, 2 wrong
        foreach ($questions as $index => $q) {
            $answers[$q->id] = ($index < 8) ? 'a' : 'b';
        }

        $submitRes = $this->actingAs($this->admin)->post(route('assesment.user.store', $assessment->slug), $answers);
        $submitRes->assertRedirect(route('assesment.user.result', $assessment->slug));

        $draft->refresh();
        $this->assertEquals(1, $draft->final);
        $this->assertEquals(80, $draft->score);
        $this->assertEquals(1, $draft->pass);

        // Result page view renders score 80
        $resultView = $this->actingAs($this->admin)->get(route('assesment.user.result', $assessment->slug));
        $resultView->assertStatus(200);
        $resultView->assertSee('80');
    }

    /**
     * TEST 4: Admin + Global + Batch 1 -> Attempt 2 (Re-attempt, 10 Benar -> Score 100)
     * Expected: PASS, Score = 100 on Attempt 2.
     */
    public function test_4_admin_global_batch_1_attempt_2_scoring(): void
    {
        $assessment = $this->createGlobalAssessmentWith10Questions(1, 80);
        $questions = $assessment->questions()->get();

        // 1st attempt: 8 correct -> score 80
        $this->actingAs($this->admin)->get(route('assesment.user.view', $assessment->slug));
        $token1 = session("assessment_submission_token.{$assessment->id}.{$assessment->batch}", (string) Str::uuid());
        $answers1 = ['submission_token' => $token1];
        foreach ($questions as $index => $q) {
            $answers1[$q->id] = ($index < 8) ? 'a' : 'b';
        }
        $this->actingAs($this->admin)->post(route('assesment.user.store', $assessment->slug), $answers1);

        // 2nd attempt: 10 correct -> score 100
        $res2 = $this->actingAs($this->admin)->get(route('assesment.user.view', $assessment->slug));
        $res2->assertStatus(200);

        $draft2 = AssesmentResult::where('user_id', $this->admin->id)
            ->where('assesment_id', $assessment->id)
            ->where('batch', 1)
            ->where('final', 0)
            ->firstOrFail();

        $token2 = session("assessment_submission_token.{$assessment->id}.{$assessment->batch}", (string) Str::uuid());
        $answers2 = ['submission_token' => $token2];
        foreach ($questions as $q) {
            $answers2[$q->id] = 'a'; // all correct
        }
        $submit2 = $this->actingAs($this->admin)->post(route('assesment.user.store', $assessment->slug), $answers2);
        $submit2->assertRedirect(route('assesment.user.result', $assessment->slug));

        $draft2->refresh();
        $this->assertEquals(1, $draft2->final);
        $this->assertEquals(100, $draft2->score);

        // Result page view renders 100
        $resultView = $this->actingAs($this->admin)->get(route('assesment.user.result', $assessment->slug));
        $resultView->assertStatus(200);
        $resultView->assertSee('100');
    }

    /**
     * TEST 5: Admin + Global + Batch 1 -> Attempt 3
     * Expected: PASS, isolated 3 results.
     */
    public function test_5_admin_global_batch_1_attempt_3_pass(): void
    {
        $assessment = $this->createGlobalAssessment();
        $questions = $assessment->questions()->get();

        for ($i = 1; $i <= 3; $i++) {
            $this->actingAs($this->admin)->get(route('assesment.user.view', $assessment->slug));
            $draft = AssesmentResult::where('user_id', $this->admin->id)
                ->where('assesment_id', $assessment->id)
                ->where('batch', 1)
                ->where('final', 0)
                ->latest('id')
                ->firstOrFail();

            $token = session("assessment_submission_token.{$assessment->id}.{$assessment->batch}", (string) Str::uuid());
            $this->actingAs($this->admin)->post(route('assesment.user.store', $assessment->slug), [
                'submission_token' => $token,
                $questions[0]->id => $questions[0]->answer,
                $questions[1]->id => $questions[1]->answer,
            ]);

            $draft->refresh();
            $this->assertEquals(1, $draft->final);
        }

        $this->assertEquals(3, AssesmentResult::where('user_id', $this->admin->id)->where('assesment_id', $assessment->id)->where('final', 1)->count());
    }

    /**
     * TEST 6: Super Admin + Global + Batch 1 -> Attempt 1 (10 Soal, 9 Benar -> Score 90)
     * Expected: PASS, Score = 90.
     */
    public function test_6_super_admin_global_batch_1_attempt_1_scoring(): void
    {
        $assessment = $this->createGlobalAssessmentWith10Questions(1, 80);
        $questions = $assessment->questions()->get();

        $res = $this->actingAs($this->superAdmin)->get(route('assesment.user.view', $assessment->slug));
        $res->assertStatus(200);

        $draft = AssesmentResult::where('user_id', $this->superAdmin->id)
            ->where('assesment_id', $assessment->id)
            ->where('batch', 1)
            ->where('final', 0)
            ->firstOrFail();

        $token = session("assessment_submission_token.{$assessment->id}.{$assessment->batch}", (string) Str::uuid());
        $answers = ['submission_token' => $token];
        // 9 correct, 1 wrong
        foreach ($questions as $index => $q) {
            $answers[$q->id] = ($index < 9) ? 'a' : 'b';
        }

        $submitRes = $this->actingAs($this->superAdmin)->post(route('assesment.user.store', $assessment->slug), $answers);
        $submitRes->assertRedirect(route('assesment.user.result', $assessment->slug));

        $draft->refresh();
        $this->assertEquals(1, $draft->final);
        $this->assertEquals(90, $draft->score);

        $resultView = $this->actingAs($this->superAdmin)->get(route('assesment.user.result', $assessment->slug));
        $resultView->assertStatus(200);
        $resultView->assertSee('90');
    }

    /**
     * TEST 7: Super Admin + Global + Batch 1 -> Attempt 2
     * Expected: PASS.
     */
    public function test_7_super_admin_global_batch_1_attempt_2_pass(): void
    {
        $assessment = $this->createGlobalAssessment();
        $questions = $assessment->questions()->get();

        // Attempt 1
        $this->actingAs($this->superAdmin)->get(route('assesment.user.view', $assessment->slug));
        $token1 = session("assessment_submission_token.{$assessment->id}.{$assessment->batch}", (string) Str::uuid());
        $this->actingAs($this->superAdmin)->post(route('assesment.user.store', $assessment->slug), [
            'submission_token' => $token1,
            $questions[0]->id => $questions[0]->answer,
            $questions[1]->id => $questions[1]->answer,
        ]);

        // Attempt 2
        $res2 = $this->actingAs($this->superAdmin)->get(route('assesment.user.view', $assessment->slug));
        $res2->assertStatus(200);

        $draft2 = AssesmentResult::where('user_id', $this->superAdmin->id)
            ->where('assesment_id', $assessment->id)
            ->where('batch', 1)
            ->where('final', 0)
            ->latest('id')
            ->firstOrFail();

        $token2 = session("assessment_submission_token.{$assessment->id}.{$assessment->batch}", (string) Str::uuid());
        $this->actingAs($this->superAdmin)->post(route('assesment.user.store', $assessment->slug), [
            'submission_token' => $token2,
            $questions[0]->id => $questions[0]->answer,
            $questions[1]->id => $questions[1]->answer,
        ]);

        $draft2->refresh();
        $this->assertEquals(1, $draft2->final);
        $this->assertEquals(2, AssesmentResult::where('user_id', $this->superAdmin->id)->where('assesment_id', $assessment->id)->where('final', 1)->count());
    }

    /**
     * TEST 8: Super Admin + Global + Batch 1 -> Attempt 3
     * Expected: PASS.
     */
    public function test_8_super_admin_global_batch_1_attempt_3_pass(): void
    {
        $assessment = $this->createGlobalAssessment();
        $questions = $assessment->questions()->get();

        for ($i = 1; $i <= 3; $i++) {
            $this->actingAs($this->superAdmin)->get(route('assesment.user.view', $assessment->slug));
            $draft = AssesmentResult::where('user_id', $this->superAdmin->id)
                ->where('assesment_id', $assessment->id)
                ->where('batch', 1)
                ->where('final', 0)
                ->latest('id')
                ->firstOrFail();

            $token = session("assessment_submission_token.{$assessment->id}.{$assessment->batch}", (string) Str::uuid());
            $this->actingAs($this->superAdmin)->post(route('assesment.user.store', $assessment->slug), [
                'submission_token' => $token,
                $questions[0]->id => $questions[0]->answer,
                $questions[1]->id => $questions[1]->answer,
            ]);

            $draft->refresh();
            $this->assertEquals(1, $draft->final);
        }

        $this->assertEquals(3, AssesmentResult::where('user_id', $this->superAdmin->id)->where('assesment_id', $assessment->id)->where('final', 1)->count());
    }

    /**
     * TEST 9: Admin active draft -> Refresh
     * Expected: Draft ID sama.
     */
    public function test_9_admin_active_draft_refresh_preserves_same_draft_id(): void
    {
        $assessment = $this->createGlobalAssessment();
        $q1 = $assessment->questions()->first();

        // 1. Initial view -> creates draft
        $this->actingAs($this->admin)->get(route('assesment.user.view', $assessment->slug));
        $draft1 = AssesmentResult::where('user_id', $this->admin->id)
            ->where('assesment_id', $assessment->id)
            ->where('batch', 1)
            ->where('final', 0)
            ->firstOrFail();

        // 2. Autosave answer
        $token = session("assessment_submission_token.{$assessment->id}.{$assessment->batch}", (string) Str::uuid());
        $this->actingAs($this->admin)->postJson(route('assesment.user.autosave', $assessment->slug), [
            'submission_token' => $token,
            $q1->id => $q1->answer,
        ]);

        // 3. Refresh (view again)
        $this->actingAs($this->admin)->get(route('assesment.user.view', $assessment->slug));
        $draft2 = AssesmentResult::where('user_id', $this->admin->id)
            ->where('assesment_id', $assessment->id)
            ->where('batch', 1)
            ->where('final', 0)
            ->firstOrFail();

        $this->assertEquals($draft1->id, $draft2->id, 'Refreshing must reuse the same active draft ID');
        $this->assertEquals([$q1->id => $q1->answer], $draft2->user_answer);
    }

    /**
     * TEST 10: Refresh Result Page & Direct Visit
     * Expected: Result page persists exact score (e.g. 80) even without session flash.
     */
    public function test_10_refresh_result_page_and_direct_visit_maintains_score(): void
    {
        $assessment = $this->createGlobalAssessmentWith10Questions(1, 80);
        $questions = $assessment->questions()->get();

        // Submit 8 correct answers -> 80%
        $this->actingAs($this->admin)->get(route('assesment.user.view', $assessment->slug));
        $token = session("assessment_submission_token.{$assessment->id}.{$assessment->batch}", (string) Str::uuid());
        $answers = ['submission_token' => $token];
        foreach ($questions as $index => $q) {
            $answers[$q->id] = ($index < 8) ? 'a' : 'b';
        }
        $this->actingAs($this->admin)->post(route('assesment.user.store', $assessment->slug), $answers);

        // 1. First visit (has session flash)
        $res1 = $this->actingAs($this->admin)->get(route('assesment.user.result', $assessment->slug));
        $res1->assertStatus(200);
        $res1->assertSee('80');

        // 2. Direct visit / Refresh without session flash
        session()->forget('assesmentResultId');
        $res2 = $this->actingAs($this->admin)->get(route('assesment.user.result', $assessment->slug));
        $res2->assertStatus(200);
        $res2->assertSee('80');
        $res2->assertDontSee('>0</div>');
    }

    /**
     * TEST 11: Admin Attempt 1 vs Attempt 2
     * Expected: Snapshot dan user_answer terisolasi.
     */
    public function test_11_admin_attempt_1_vs_attempt_2_isolated_snapshots_and_answers(): void
    {
        $assessment = $this->createGlobalAssessment();
        $questions = $assessment->questions()->get();

        // Attempt 1: answer A for both
        $this->actingAs($this->admin)->get(route('assesment.user.view', $assessment->slug));
        $draft1 = AssesmentResult::where('user_id', $this->admin->id)
            ->where('assesment_id', $assessment->id)
            ->where('final', 0)
            ->firstOrFail();

        $token1 = session("assessment_submission_token.{$assessment->id}.{$assessment->batch}", (string) Str::uuid());
        $this->actingAs($this->admin)->post(route('assesment.user.store', $assessment->slug), [
            'submission_token' => $token1,
            $questions[0]->id => 'a',
            $questions[1]->id => 'a',
        ]);
        $draft1->refresh();

        // Attempt 2: answer B for both
        $this->actingAs($this->admin)->get(route('assesment.user.view', $assessment->slug));
        $draft2 = AssesmentResult::where('user_id', $this->admin->id)
            ->where('assesment_id', $assessment->id)
            ->where('final', 0)
            ->latest('id')
            ->firstOrFail();

        $token2 = session("assessment_submission_token.{$assessment->id}.{$assessment->batch}", (string) Str::uuid());
        $this->actingAs($this->admin)->post(route('assesment.user.store', $assessment->slug), [
            'submission_token' => $token2,
            $questions[0]->id => 'b',
            $questions[1]->id => 'b',
        ]);
        $draft2->refresh();

        // Assert attempt 1 remained unchanged
        $this->assertEquals(['a', 'a'], array_values($draft1->user_answer));
        $this->assertEquals(['b', 'b'], array_values($draft2->user_answer));
        $this->assertNotEmpty($draft1->question_snapshot);
        $this->assertNotEmpty($draft2->question_snapshot);
    }

    /**
     * TEST 12: Batch 1 vs Batch 2 Isolation
     * Expected: Score and attempts are segregated by batch.
     */
    public function test_12_batch_1_vs_batch_2_score_isolation(): void
    {
        $assessmentBatch1 = $this->createGlobalAssessmentWith10Questions(batch: 1);
        $assessmentBatch2 = $this->createGlobalAssessmentWith10Questions(batch: 2);
        $questions1 = $assessmentBatch1->questions()->get();
        $questions2 = $assessmentBatch2->questions()->get();

        // Batch 1: 10 correct -> score 100
        $this->actingAs($this->admin)->get(route('assesment.user.view', $assessmentBatch1->slug));
        $token1 = session("assessment_submission_token.{$assessmentBatch1->id}.{$assessmentBatch1->batch}", (string) Str::uuid());
        $answers1 = ['submission_token' => $token1];
        foreach ($questions1 as $q) {
            $answers1[$q->id] = 'a';
        }
        $this->actingAs($this->admin)->post(route('assesment.user.store', $assessmentBatch1->slug), $answers1);

        // Batch 2: 7 correct -> score 70
        $this->actingAs($this->admin)->get(route('assesment.user.view', $assessmentBatch2->slug));
        $token2 = session("assessment_submission_token.{$assessmentBatch2->id}.{$assessmentBatch2->batch}", (string) Str::uuid());
        $answers2 = ['submission_token' => $token2];
        foreach ($questions2 as $index => $q) {
            $answers2[$q->id] = ($index < 7) ? 'a' : 'b';
        }
        $this->actingAs($this->admin)->post(route('assesment.user.store', $assessmentBatch2->slug), $answers2);

        // Verify Batch 1 result
        $resB1 = AssesmentResult::where('user_id', $this->admin->id)->where('assesment_id', $assessmentBatch1->id)->where('batch', 1)->first();
        $this->assertEquals(100, $resB1->score);

        // Verify Batch 2 result
        $resB2 = AssesmentResult::where('user_id', $this->admin->id)->where('assesment_id', $assessmentBatch2->id)->where('batch', 2)->first();
        $this->assertEquals(70, $resB2->score);
    }

    /**
     * TEST 13: Content Assessment
     * Expected: Tidak berubah, mengikuti total_remedials.
     */
    public function test_13_content_assessment_enforces_remedials_formula_for_regular_user(): void
    {
        $assessment = $this->createContentAssessment(remedials: 1);
        $questions = $assessment->questions()->get();

        // Attempt 1 (Fail)
        $this->actingAs($this->regularUser)->get(route('assesment.user.view', $assessment->slug));
        $token1 = session("assessment_submission_token.{$assessment->id}.{$assessment->batch}", (string) Str::uuid());
        $this->actingAs($this->regularUser)->post(route('assesment.user.store', $assessment->slug), [
            'submission_token' => $token1,
            $questions[0]->id => 'wrong',
            $questions[1]->id => 'wrong',
        ]);

        // Attempt 2 (Remedial 1: Allowed)
        $reason2 = $this->accessService->denialReason($assessment, $this->regularUser);
        $this->assertNull($reason2);

        $this->actingAs($this->regularUser)->get(route('assesment.user.view', $assessment->slug));
        $token2 = session("assessment_submission_token.{$assessment->id}.{$assessment->batch}", (string) Str::uuid());
        $this->actingAs($this->regularUser)->post(route('assesment.user.store', $assessment->slug), [
            'submission_token' => $token2,
            $questions[0]->id => 'wrong',
            $questions[1]->id => 'wrong',
        ]);

        // Attempt 3 (Remedial 2: Blocked)
        $reason3 = $this->accessService->denialReason($assessment, $this->regularUser);
        $this->assertEquals(AssessmentAccessService::REASON_MAX_ATTEMPTS, $reason3);
    }

    /**
     * TEST 14: Playlist Assessment
     * Expected: Tidak berubah, mengikuti total_remedials.
     */
    public function test_14_playlist_assessment_enforces_remedials_formula_for_regular_user(): void
    {
        $assessment = $this->createPlaylistAssessment(remedials: 1);
        $questions = $assessment->questions()->get();

        // Attempt 1 (Fail)
        $this->actingAs($this->regularUser)->get(route('assesment.user.view', $assessment->slug));
        $token1 = session("assessment_submission_token.{$assessment->id}.{$assessment->batch}", (string) Str::uuid());
        $this->actingAs($this->regularUser)->post(route('assesment.user.store', $assessment->slug), [
            'submission_token' => $token1,
            $questions[0]->id => 'wrong',
        ]);

        // Attempt 2 (Remedial 1: Allowed)
        $reason2 = $this->accessService->denialReason($assessment, $this->regularUser);
        $this->assertNull($reason2);

        $this->actingAs($this->regularUser)->get(route('assesment.user.view', $assessment->slug));
        $token2 = session("assessment_submission_token.{$assessment->id}.{$assessment->batch}", (string) Str::uuid());
        $this->actingAs($this->regularUser)->post(route('assesment.user.store', $assessment->slug), [
            'submission_token' => $token2,
            $questions[0]->id => 'wrong',
        ]);

        // Attempt 3 (Remedial 2: Blocked)
        $reason3 = $this->accessService->denialReason($assessment, $this->regularUser);
        $this->assertEquals(AssessmentAccessService::REASON_MAX_ATTEMPTS, $reason3);
    }

    /**
     * TEST 15: Home Access Code
     * Expected: Admin/Super Admin dapat Start kembali (can_start=true, show_result=false),
     *           sedangkan Regular User yang sudah selesai mendapat Result Modal (can_start=false, show_result=true).
     */
    public function test_15_home_access_code_allows_admin_start_while_showing_result_modal_for_regular_user(): void
    {
        $assessment = $this->createGlobalAssessment();
        $questions = $assessment->questions()->get();

        // 1. Regular user completes attempt
        $this->actingAs($this->regularUser)->get(route('assesment.user.view', $assessment->slug));
        $tokenUser = session("assessment_submission_token.{$assessment->id}.{$assessment->batch}", (string) Str::uuid());
        $this->actingAs($this->regularUser)->post(route('assesment.user.store', $assessment->slug), [
            'submission_token' => $tokenUser,
            $questions[0]->id => $questions[0]->answer,
            $questions[1]->id => $questions[1]->answer,
        ]);

        // Regular user enters unique_code on home
        $resUser = $this->actingAs($this->regularUser)->postJson(route('home.user.access.assessment.by.unique.code'), [
            'unique_code' => $assessment->unique_code,
        ]);
        $resUser->assertStatus(200);
        $resUser->assertJson([
            'show_result' => true,
            'can_start'   => false,
        ]);

        // 2. Admin completes attempt
        $this->actingAs($this->admin)->get(route('assesment.user.view', $assessment->slug));
        $tokenAdmin = session("assessment_submission_token.{$assessment->id}.{$assessment->batch}", (string) Str::uuid());
        $this->actingAs($this->admin)->post(route('assesment.user.store', $assessment->slug), [
            'submission_token' => $tokenAdmin,
            $questions[0]->id => $questions[0]->answer,
            $questions[1]->id => $questions[1]->answer,
        ]);

        // Admin enters unique_code on home -> Must be allowed to Start again
        $resAdmin = $this->actingAs($this->admin)->postJson(route('home.user.access.assessment.by.unique.code'), [
            'unique_code' => $assessment->unique_code,
        ]);
        $resAdmin->assertStatus(200);
        $resAdmin->assertJson([
            'show_result' => false,
            'can_start'   => true,
            'action_type' => 'start',
        ]);
    }

    /**
     * TEST 16: Login Access Code
     * Expected: Admin/Super Admin dapat Start kembali, Regular User tetap blocked setelah 1 attempt per batch.
     */
    public function test_16_login_access_code_route_redirects_and_processes_access_consistently(): void
    {
        $assessment = $this->createGlobalAssessment();
        $questions = $assessment->questions()->get();

        // 1. Super Admin completes attempt
        $this->actingAs($this->superAdmin)->get(route('assesment.user.view', $assessment->slug));
        $tokenSA = session("assessment_submission_token.{$assessment->id}.{$assessment->batch}", (string) Str::uuid());
        $this->actingAs($this->superAdmin)->post(route('assesment.user.store', $assessment->slug), [
            'submission_token' => $tokenSA,
            $questions[0]->id => $questions[0]->answer,
            $questions[1]->id => $questions[1]->answer,
        ]);

        // Super Admin access code query returns can_start = true
        $resSA = $this->actingAs($this->superAdmin)->postJson(route('home.user.access.assessment.by.unique.code'), [
            'unique_code' => $assessment->unique_code,
        ]);
        $resSA->assertStatus(200);
        $resSA->assertJson([
            'show_result' => false,
            'can_start'   => true,
            'action_type' => 'start',
        ]);

        // Direct view route also allows Super Admin to start again
        $viewRes = $this->actingAs($this->superAdmin)->get(route('assesment.user.view', $assessment->slug));
        $viewRes->assertStatus(200);
        $viewRes->assertViewIs('user.assesment.view');
    }

    /**
     * TEST 17: Global + Admin + Expired -> Score DB = 80
     * Expected: Opening /result displays Score = 80 (not 0).
     */
    public function test_17_global_admin_expired_displays_correct_score(): void
    {
        $pastDate = now()->subDays(5)->format('Y-m-d H:i:s');
        $assessment = $this->createGlobalAssessmentWith10Questions(
            batch: 1,
            passingGrade: 80,
            status: 'OK',
            fromdate: now()->subDays(10)->format('Y-m-d H:i:s'),
            todate: $pastDate
        );

        // Pre-existing completed attempt in DB with score 80
        $result = AssesmentResult::create([
            'user_id'      => $this->admin->id,
            'assesment_id' => $assessment->id,
            'batch'        => 1,
            'final'        => 1,
            'score'        => 80,
            'pass'         => 1,
            'user_answer'  => ['51299' => 'Ans 1'],
        ]);

        // 1. Direct visit /result
        $res = $this->actingAs($this->admin)->get(route('assesment.user.result', $assessment->slug));
        $res->assertStatus(200);
        $res->assertSee('80');
        $res->assertDontSee('>0</div>');

        // 2. Even if Admin visits /view, fresh draft is NOT immediately killed with score 0
        $viewRes = $this->actingAs($this->admin)->get(route('assesment.user.view', $assessment->slug));
        $viewRes->assertStatus(200);
        $viewRes->assertViewIs('user.assesment.view');
    }

    /**
     * TEST 18: Global + Admin + Inactive -> Score DB = 80
     * Expected: Opening /result displays Score = 80.
     */
    public function test_18_global_admin_inactive_displays_correct_score(): void
    {
        $assessment = $this->createGlobalAssessmentWith10Questions(
            batch: 1,
            passingGrade: 80,
            status: 'NOT'
        );

        // Pre-existing completed attempt in DB with score 80
        AssesmentResult::create([
            'user_id'      => $this->admin->id,
            'assesment_id' => $assessment->id,
            'batch'        => 1,
            'final'        => 1,
            'score'        => 80,
            'pass'         => 1,
            'user_answer'  => ['51299' => 'Ans 1'],
        ]);

        $res = $this->actingAs($this->admin)->get(route('assesment.user.result', $assessment->slug));
        $res->assertStatus(200);
        $res->assertSee('80');
        $res->assertDontSee('>0</div>');
    }

    /**
     * TEST 19: Global + Super Admin + Expired -> Score DB = 90
     * Expected: Opening /result displays Score = 90.
     */
    public function test_19_global_super_admin_expired_displays_correct_score(): void
    {
        $pastDate = now()->subDays(5)->format('Y-m-d H:i:s');
        $assessment = $this->createGlobalAssessmentWith10Questions(
            batch: 1,
            passingGrade: 80,
            status: 'OK',
            fromdate: now()->subDays(10)->format('Y-m-d H:i:s'),
            todate: $pastDate
        );

        AssesmentResult::create([
            'user_id'      => $this->superAdmin->id,
            'assesment_id' => $assessment->id,
            'batch'        => 1,
            'final'        => 1,
            'score'        => 90,
            'pass'         => 1,
            'user_answer'  => ['51299' => 'Ans 1'],
        ]);

        $res = $this->actingAs($this->superAdmin)->get(route('assesment.user.result', $assessment->slug));
        $res->assertStatus(200);
        $res->assertSee('90');
        $res->assertDontSee('>0</div>');
    }

    /**
     * TEST 20: Global + Super Admin + Inactive -> Score DB = 90
     * Expected: Opening /result displays Score = 90.
     */
    public function test_20_global_super_admin_inactive_displays_correct_score(): void
    {
        $assessment = $this->createGlobalAssessmentWith10Questions(
            batch: 1,
            passingGrade: 80,
            status: 'NOT'
        );

        AssesmentResult::create([
            'user_id'      => $this->superAdmin->id,
            'assesment_id' => $assessment->id,
            'batch'        => 1,
            'final'        => 1,
            'score'        => 90,
            'pass'         => 1,
            'user_answer'  => ['51299' => 'Ans 1'],
        ]);

        $res = $this->actingAs($this->superAdmin)->get(route('assesment.user.result', $assessment->slug));
        $res->assertStatus(200);
        $res->assertSee('90');
        $res->assertDontSee('>0</div>');
    }

    /**
     * TEST 21: Global + Regular User + Expired -> Score DB = 75
     * Expected: Regular user can still see their score on /result.
     */
    public function test_21_global_regular_user_expired_displays_correct_score(): void
    {
        $pastDate = now()->subDays(5)->format('Y-m-d H:i:s');
        $assessment = $this->createGlobalAssessmentWith10Questions(
            batch: 1,
            passingGrade: 80,
            status: 'OK',
            fromdate: now()->subDays(10)->format('Y-m-d H:i:s'),
            todate: $pastDate
        );

        AssesmentResult::create([
            'user_id'      => $this->regularUser->id,
            'assesment_id' => $assessment->id,
            'batch'        => 1,
            'final'        => 1,
            'score'        => 75,
            'pass'         => 0,
            'user_answer'  => ['51299' => 'Ans 1'],
        ]);

        $res = $this->actingAs($this->regularUser)->get(route('assesment.user.result', $assessment->slug));
        $res->assertStatus(200);
        $res->assertSee('75');
    }
}
