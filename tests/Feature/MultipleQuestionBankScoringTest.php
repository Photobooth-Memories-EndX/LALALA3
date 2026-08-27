<?php

namespace Tests\Feature;

use App\Models\Assesment;
use App\Models\AssesmentQuestion;
use App\Models\AssesmentQuestionBank;
use App\Models\AssesmentResult;
use App\Models\QuestionBank;
use App\Models\User;
use App\Services\AssessmentAccessService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class MultipleQuestionBankScoringTest extends TestCase
{
    use DatabaseTransactions;

    private User $userA;
    private User $userB;
    private User $admin;
    private User $superAdmin;
    private AssessmentAccessService $accessService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = $this->createTestUser(1, 'User A');
        $this->userB = $this->createTestUser(1, 'User B');
        $this->admin = $this->createTestUser(2, 'Admin QA');
        $this->superAdmin = $this->createTestUser(3, 'Super Admin QA');
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

    /**
     * Creates an assessment configured with multiple question banks.
     * $banksConfig: array of ['total_questions' => int, 'questions_amount' => int]
     */
    private function createAssessmentWithBanks(array $banksConfig, string $type = 'global', int $passingGrade = 80): Assesment
    {
        $assessment = Assesment::create([
            'title'           => 'Multi-Bank Assessment ' . uniqid(),
            'type'            => $type,
            'status'          => 'OK',
            'passing_grade'   => $passingGrade,
            'batch'           => 1,
            'total_remedials' => 1,
            'unique_code'     => 'MB' . strtoupper(Str::random(6)),
        ]);

        foreach ($banksConfig as $index => $cfg) {
            $bank = QuestionBank::create([
                'title'        => 'Bank ' . chr(65 + $index) . ' ' . uniqid(),
                'created_date' => now()->toDateString(),
            ]);

            AssesmentQuestionBank::create([
                'assesment_id'     => $assessment->id,
                'question_bank_id' => $bank->id,
                'questions_amount' => $cfg['questions_amount'],
            ]);

            for ($q = 1; $q <= $cfg['total_questions']; $q++) {
                AssesmentQuestion::create([
                    'assesment_id'     => $assessment->id,
                    'question_bank_id' => $bank->id,
                    'question'         => "Bank " . chr(65 + $index) . " Question $q",
                    'option'           => ['a' => 'Answer A', 'b' => 'Answer B', 'c' => 'Answer C', 'd' => 'Answer D'],
                    'answer'           => 'a',
                ]);
            }
        }

        return $assessment;
    }

    /**
     * TEST 1: 3 Banks × 25 questions (75 available), 10 used each (30 used total).
     * 24 correct answers -> Score MUST be 24/30 * 100 = 80, NOT 24/75 * 100 = 32.
     */
    public function test_1_three_banks_25_questions_each_using_10_each_calculates_80_percent(): void
    {
        $assessment = $this->createAssessmentWithBanks([
            ['total_questions' => 25, 'questions_amount' => 10], // Bank A: 25 avail, 10 used
            ['total_questions' => 25, 'questions_amount' => 10], // Bank B: 25 avail, 10 used
            ['total_questions' => 25, 'questions_amount' => 10], // Bank C: 25 avail, 10 used
        ]);

        // Total available questions in DB for this assessment is 75
        $this->assertEquals(75, AssesmentQuestion::where('assesment_id', $assessment->id)->count());

        // 1. User starts assessment
        $viewRes = $this->actingAs($this->userA)->get(route('assesment.user.view', $assessment->slug));
        $viewRes->assertStatus(200);

        $draft = AssesmentResult::where('user_id', $this->userA->id)
            ->where('assesment_id', $assessment->id)
            ->where('final', 0)
            ->firstOrFail();

        // 2. Assert snapshot contains EXACTLY 30 questions (not 75)
        $snapshot = $draft->question_snapshot;
        $this->assertIsArray($snapshot);
        $this->assertCount(30, $snapshot, 'Snapshot must contain exactly 30 questions');

        // Verify that 10 questions came from each bank
        $snapshotQids = array_column($snapshot, 'question_id');
        $snapshotQuestions = AssesmentQuestion::whereIn('id', $snapshotQids)->get();
        $bankGroups = $snapshotQuestions->groupBy('question_bank_id');
        $this->assertCount(3, $bankGroups);
        foreach ($bankGroups as $bankId => $qList) {
            $this->assertCount(10, $qList, "Bank $bankId must contribute exactly 10 questions");
        }

        // 3. User submits 24 correct and 6 incorrect answers
        $submissionToken = session("assessment_submission_token.{$assessment->id}.{$assessment->batch}", (string) Str::uuid());
        $answers = ['submission_token' => $submissionToken];
        foreach ($snapshotQids as $idx => $qid) {
            // First 24 correct ('a'), last 6 incorrect ('b')
            $answers[$qid] = ($idx < 24) ? 'a' : 'b';
        }

        $submitRes = $this->actingAs($this->userA)->post(route('assesment.user.store', $assessment->slug), $answers);
        $submitRes->assertRedirect(route('assesment.user.result', $assessment->slug));

        $draft->refresh();
        $this->assertEquals(1, $draft->final);
        $this->assertEquals(80, $draft->score, 'Score must be 24/30*100 = 80, not 24/75*100 = 32');
        $this->assertEquals(1, $draft->pass);

        // 4. Result page view renders 80
        $resultView = $this->actingAs($this->userA)->get(route('assesment.user.result', $assessment->slug));
        $resultView->assertStatus(200);
        $resultView->assertSee('80');
        $this->assertNotEquals(32, $draft->score, 'Score must not be calculated using available questions (75)');
    }

    /**
     * TEST 2: Asymmetric banks: Bank A (20/5), Bank B (20/10), Bank C (20/15) -> Total used = 30.
     * 30 correct -> Score = 100.
     */
    public function test_2_asymmetric_banks_5_10_15_total_30_questions(): void
    {
        $assessment = $this->createAssessmentWithBanks([
            ['total_questions' => 20, 'questions_amount' => 5],
            ['total_questions' => 20, 'questions_amount' => 10],
            ['total_questions' => 20, 'questions_amount' => 15],
        ]);

        $this->assertEquals(60, AssesmentQuestion::where('assesment_id', $assessment->id)->count());

        $this->actingAs($this->userA)->get(route('assesment.user.view', $assessment->slug));
        $draft = AssesmentResult::where('user_id', $this->userA->id)
            ->where('assesment_id', $assessment->id)
            ->where('final', 0)
            ->firstOrFail();

        $snapshot = $draft->question_snapshot;
        $this->assertCount(30, $snapshot);

        // Submit all 30 correct
        $snapshotQids = array_column($snapshot, 'question_id');
        $submissionToken = session("assessment_submission_token.{$assessment->id}.{$assessment->batch}", (string) Str::uuid());
        $answers = ['submission_token' => $submissionToken];
        foreach ($snapshotQids as $qid) {
            $answers[$qid] = 'a';
        }

        $this->actingAs($this->userA)->post(route('assesment.user.store', $assessment->slug), $answers);
        $draft->refresh();
        $this->assertEquals(100, $draft->score);
    }

    /**
     * TEST 3: Extreme scenario: 1 question used from each of 3 banks (1+1+1 = 3 questions).
     * 2 correct out of 3 -> Score = 67 (rounded in int column).
     */
    public function test_3_extreme_scenario_1_plus_1_plus_1_equals_3_questions(): void
    {
        $assessment = $this->createAssessmentWithBanks([
            ['total_questions' => 10, 'questions_amount' => 1],
            ['total_questions' => 10, 'questions_amount' => 1],
            ['total_questions' => 10, 'questions_amount' => 1],
        ]);

        $this->actingAs($this->userA)->get(route('assesment.user.view', $assessment->slug));
        $draft = AssesmentResult::where('user_id', $this->userA->id)
            ->where('assesment_id', $assessment->id)
            ->where('final', 0)
            ->firstOrFail();

        $snapshot = $draft->question_snapshot;
        $this->assertCount(3, $snapshot);

        $snapshotQids = array_column($snapshot, 'question_id');
        $submissionToken = session("assessment_submission_token.{$assessment->id}.{$assessment->batch}", (string) Str::uuid());
        $answers = ['submission_token' => $submissionToken];
        $answers[$snapshotQids[0]] = 'a'; // correct
        $answers[$snapshotQids[1]] = 'a'; // correct
        $answers[$snapshotQids[2]] = 'b'; // wrong

        $this->actingAs($this->userA)->post(route('assesment.user.store', $assessment->slug), $answers);
        $draft->refresh();
        $this->assertEquals(67, $draft->score, '2 out of 3 correct must give score 67 (2/3*100 = 66.67)');
    }

    /**
     * TEST 4: Full scenario: 25+25+25 = 75 available, all 75 used (25+25+25).
     */
    public function test_4_all_available_questions_used_75_questions(): void
    {
        $assessment = $this->createAssessmentWithBanks([
            ['total_questions' => 25, 'questions_amount' => 25],
            ['total_questions' => 25, 'questions_amount' => 25],
            ['total_questions' => 25, 'questions_amount' => 25],
        ]);

        $this->actingAs($this->userA)->get(route('assesment.user.view', $assessment->slug));
        $draft = AssesmentResult::where('user_id', $this->userA->id)
            ->where('assesment_id', $assessment->id)
            ->where('final', 0)
            ->firstOrFail();

        $snapshot = $draft->question_snapshot;
        $this->assertCount(75, $snapshot);
    }

    /**
     * TEST 5: Independent Randomization between User A and User B.
     * Both get 30 questions, independent sampling and independent snapshots.
     */
    public function test_5_independent_randomization_for_multiple_users(): void
    {
        $assessment = $this->createAssessmentWithBanks([
            ['total_questions' => 25, 'questions_amount' => 10],
            ['total_questions' => 25, 'questions_amount' => 10],
            ['total_questions' => 25, 'questions_amount' => 10],
        ]);

        // User A starts
        $this->actingAs($this->userA)->get(route('assesment.user.view', $assessment->slug));
        $draftA = AssesmentResult::where('user_id', $this->userA->id)
            ->where('assesment_id', $assessment->id)
            ->where('final', 0)
            ->firstOrFail();

        // User B starts
        $this->actingAs($this->userB)->get(route('assesment.user.view', $assessment->slug));
        $draftB = AssesmentResult::where('user_id', $this->userB->id)
            ->where('assesment_id', $assessment->id)
            ->where('final', 0)
            ->firstOrFail();

        $this->assertNotEquals($draftA->id, $draftB->id);
        $this->assertCount(30, $draftA->question_snapshot);
        $this->assertCount(30, $draftB->question_snapshot);

        // Refresh User A -> snapshot must stay identical
        $this->actingAs($this->userA)->get(route('assesment.user.view', $assessment->slug));
        $draftA->refresh();
        $this->assertCount(30, $draftA->question_snapshot);
    }

    /**
     * TEST 6: Admin QA Unlimited Re-attempts with 3 Question Banks (30 questions each).
     * Every attempt gets an independent 30-question snapshot and proper scoring denominator of 30.
     */
    public function test_6_admin_qa_attempts_consistently_use_30_questions(): void
    {
        $assessment = $this->createAssessmentWithBanks([
            ['total_questions' => 25, 'questions_amount' => 10],
            ['total_questions' => 25, 'questions_amount' => 10],
            ['total_questions' => 25, 'questions_amount' => 10],
        ]);

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->actingAs($this->admin)->get(route('assesment.user.view', $assessment->slug));
            $draft = AssesmentResult::where('user_id', $this->admin->id)
                ->where('assesment_id', $assessment->id)
                ->where('final', 0)
                ->latest('id')
                ->firstOrFail();

            $snapshot = $draft->question_snapshot;
            $this->assertCount(30, $snapshot, "Attempt $attempt must contain exactly 30 questions");

            $snapshotQids = array_column($snapshot, 'question_id');
            $token = session("assessment_submission_token.{$assessment->id}.{$assessment->batch}", (string) Str::uuid());
            $answers = ['submission_token' => $token];
            
            // Score 15/30 = 50% for attempt 1, 24/30 = 80% for attempt 2, 30/30 = 100% for attempt 3
            $correctNeeded = match ($attempt) {
                1 => 15,
                2 => 24,
                3 => 30,
            };
            foreach ($snapshotQids as $idx => $qid) {
                $answers[$qid] = ($idx < $correctNeeded) ? 'a' : 'b';
            }

            $this->actingAs($this->admin)->post(route('assesment.user.store', $assessment->slug), $answers);
            $draft->refresh();
            $this->assertEquals(1, $draft->final);
            $expectedScore = match ($attempt) {
                1 => 50,
                2 => 80,
                3 => 100,
            };
            $this->assertEquals($expectedScore, $draft->score);
        }

        $this->assertEquals(3, AssesmentResult::where('user_id', $this->admin->id)->where('assesment_id', $assessment->id)->where('final', 1)->count());
    }

    /**
     * TEST 7: Super Admin QA with Multiple Banks.
     */
    public function test_7_super_admin_qa_multiple_banks_scoring(): void
    {
        $assessment = $this->createAssessmentWithBanks([
            ['total_questions' => 25, 'questions_amount' => 10],
            ['total_questions' => 25, 'questions_amount' => 10],
            ['total_questions' => 25, 'questions_amount' => 10],
        ]);

        $this->actingAs($this->superAdmin)->get(route('assesment.user.view', $assessment->slug));
        $draft = AssesmentResult::where('user_id', $this->superAdmin->id)
            ->where('assesment_id', $assessment->id)
            ->where('final', 0)
            ->firstOrFail();

        $snapshot = $draft->question_snapshot;
        $this->assertCount(30, $snapshot);

        // 27 correct out of 30 -> 90%
        $snapshotQids = array_column($snapshot, 'question_id');
        $token = session("assessment_submission_token.{$assessment->id}.{$assessment->batch}", (string) Str::uuid());
        $answers = ['submission_token' => $token];
        foreach ($snapshotQids as $idx => $qid) {
            $answers[$qid] = ($idx < 27) ? 'a' : 'b';
        }

        $this->actingAs($this->superAdmin)->post(route('assesment.user.store', $assessment->slug), $answers);
        $draft->refresh();
        $this->assertEquals(90, $draft->score);
    }
}
