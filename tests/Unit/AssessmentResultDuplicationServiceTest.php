<?php

namespace Tests\Unit;

use App\Models\Assesment;
use App\Models\AssesmentQuestion;
use App\Models\AssesmentQuestionBank;
use App\Models\AssesmentResult;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\User;
use App\Services\AssessmentDuplicateService;
use App\Services\AssessmentResultDuplicationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AssessmentResultDuplicationServiceTest extends TestCase
{
    use DatabaseTransactions;

    private AssessmentResultDuplicationService $service;
    private AssessmentDuplicateService $duplicateService;
    private User $user1;
    private User $user2;
    private User $user3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AssessmentResultDuplicationService();
        $this->duplicateService = new AssessmentDuplicateService();

        $this->user1 = User::factory()->create([
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'sso-1-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-1-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->user2 = User::factory()->create([
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'sso-2-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-2-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->user3 = User::factory()->create([
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'sso-3-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-3-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);
    }

    private function createSourceWithQuestionsAndResults(): array
    {
        $source = Assesment::create([
            'title'         => 'Source Assessment ' . uniqid(),
            'type'          => 'content',
            'status'        => 'OK',
            'passing_grade' => 80,
            'batch'         => 1,
        ]);

        $q1 = AssesmentQuestion::create([
            'assesment_id' => $source->id,
            'question'     => 'What is 2 + 2?',
            'option'       => ['A' => '3', 'B' => '4'],
            'answer'       => 'B',
            'is_used'      => true,
        ]);

        $q2 = AssesmentQuestion::create([
            'assesment_id' => $source->id,
            'question'     => 'What is capital of France?',
            'option'       => ['A' => 'Paris', 'B' => 'London'],
            'answer'       => 'A',
            'is_used'      => true,
        ]);

        $r1 = AssesmentResult::create([
            'assesment_id'      => $source->id,
            'user_id'           => $this->user1->id,
            'user_answer'       => [(string) $q1->id => 'B', (string) $q2->id => 'A'],
            'question_snapshot' => [
                ['question_id' => $q1->id, 'order' => 1],
                ['question_id' => $q2->id, 'order' => 2],
            ],
            'score'             => 100,
            'pass'              => 1,
            'final'             => 1,
            'batch'             => 1,
            'paper_based'       => 0,
        ]);

        $r2 = AssesmentResult::create([
            'assesment_id'         => $source->id,
            'user_id'              => $this->user2->id,
            'user_answer'          => [(string) $q1->id => 'B', (string) $q2->id => 'B'],
            'question_snapshot'    => [
                ['question_id' => $q1->id, 'order' => 1],
                ['question_id' => $q2->id, 'order' => 2],
            ],
            'score'                => 80,
            'pass'                 => 1,
            'final'                => 1,
            'batch'                => 1,
            'paper_based'          => 1,
            'evidence_paper_based' => 'offline_exam.pdf',
        ]);

        $r3 = AssesmentResult::create([
            'assesment_id'      => $source->id,
            'user_id'           => $this->user3->id,
            'user_answer'       => [(string) $q1->id => 'A', (string) $q2->id => 'B'],
            'question_snapshot' => null, // older result format without snapshot
            'score'             => 50,
            'pass'              => 0,
            'final'             => 1,
            'batch'             => 1,
            'paper_based'       => 0,
        ]);

        // Duplicate source structure using existing AssessmentDuplicateService
        $duplicate = $this->duplicateService->duplicate($source, [
            'assasment_content_for' => 'content',
            'preserve_is_used'      => true,
        ]);

        return [$source, $duplicate, [$r1, $r2, $r3], [$q1, $q2]];
    }

    /**
     * Test 1: 3 source results -> 3 target results
     */
    public function test_1_three_source_results_copies_three_target_results(): void
    {
        [$source, $duplicate, $sourceResults] = $this->createSourceWithQuestionsAndResults();

        $this->assertSame(0, AssesmentResult::where('assesment_id', $duplicate->id)->count());

        $copiedCount = $this->service->copyResults($source->id, $duplicate->id);

        $this->assertSame(3, $copiedCount);
        $this->assertSame(3, AssesmentResult::where('assesment_id', $duplicate->id)->count());
    }

    /**
     * Test 2: Two source results with identical signature (double submits) are BOTH copied (multiset)
     */
    public function test_2_two_identical_source_results_both_copied(): void
    {
        $source = Assesment::create([
            'title'  => 'Identical Results Source ' . uniqid(),
            'type'   => 'content',
            'status' => 'OK',
            'batch'  => 1,
        ]);

        $duplicate = Assesment::create([
            'title'  => 'Identical Results Target ' . uniqid(),
            'type'   => 'content',
            'status' => 'OK',
            'batch'  => 1,
        ]);

        $fixedTime = Carbon::parse('2025-03-20 13:47:35');

        // Create two twin rows in source (exact same user, score, answers, timestamps)
        $r1 = AssesmentResult::create([
            'assesment_id' => $source->id,
            'user_id'      => $this->user1->id,
            'score'        => 80,
            'pass'         => 1,
            'final'        => 1,
            'batch'        => 1,
            'user_answer'  => ['10' => 'A'],
            'created_at'   => $fixedTime,
            'updated_at'   => $fixedTime,
        ]);

        $r2 = AssesmentResult::create([
            'assesment_id' => $source->id,
            'user_id'      => $this->user1->id,
            'score'        => 80,
            'pass'         => 1,
            'final'        => 1,
            'batch'        => 1,
            'user_answer'  => ['10' => 'A'],
            'created_at'   => $fixedTime,
            'updated_at'   => $fixedTime,
        ]);

        $this->assertSame(2, AssesmentResult::where('assesment_id', $source->id)->count());

        $copied = $this->service->copyResults($source->id, $duplicate->id);

        // BOTH rows must be copied (target = 2)
        $this->assertSame(2, $copied);
        $this->assertSame(2, AssesmentResult::where('assesment_id', $duplicate->id)->count());
    }

    /**
     * Test 3: Run 2 with identical rows: source = 2, target = 2 -> copies 0 (Idempotent)
     */
    public function test_3_idempotency_second_run_with_identical_rows_copies_zero(): void
    {
        $source = Assesment::create([
            'title'  => 'Identical Run 2 Source ' . uniqid(),
            'type'   => 'content',
            'status' => 'OK',
            'batch'  => 1,
        ]);

        $duplicate = Assesment::create([
            'title'  => 'Identical Run 2 Target ' . uniqid(),
            'type'   => 'content',
            'status' => 'OK',
            'batch'  => 1,
        ]);

        $fixedTime = Carbon::parse('2025-03-20 13:47:35');

        AssesmentResult::create([
            'assesment_id' => $source->id,
            'user_id'      => $this->user1->id,
            'score'        => 90,
            'pass'         => 1,
            'final'        => 1,
            'batch'        => 1,
            'user_answer'  => ['10' => 'A'],
            'created_at'   => $fixedTime,
        ]);

        AssesmentResult::create([
            'assesment_id' => $source->id,
            'user_id'      => $this->user1->id,
            'score'        => 90,
            'pass'         => 1,
            'final'        => 1,
            'batch'        => 1,
            'user_answer'  => ['10' => 'A'],
            'created_at'   => $fixedTime,
        ]);

        $run1 = $this->service->copyResults($source->id, $duplicate->id);
        $this->assertSame(2, $run1);
        $this->assertSame(2, AssesmentResult::where('assesment_id', $duplicate->id)->count());

        $run2 = $this->service->copyResults($source->id, $duplicate->id);
        $this->assertSame(0, $run2);
        $this->assertSame(2, AssesmentResult::where('assesment_id', $duplicate->id)->count());
    }

    /**
     * Test 4: Partial target: source = 2, target = 1 -> copies 1
     */
    public function test_4_partial_target_copies_only_missing_count(): void
    {
        $source = Assesment::create([
            'title'  => 'Partial Source ' . uniqid(),
            'type'   => 'content',
            'status' => 'OK',
            'batch'  => 1,
        ]);

        $duplicate = Assesment::create([
            'title'  => 'Partial Target ' . uniqid(),
            'type'   => 'content',
            'status' => 'OK',
            'batch'  => 1,
        ]);

        $fixedTime = Carbon::parse('2025-03-20 13:47:35');

        // Source has 2 identical rows
        AssesmentResult::create([
            'assesment_id' => $source->id,
            'user_id'      => $this->user1->id,
            'score'        => 85,
            'pass'         => 1,
            'final'        => 1,
            'batch'        => 1,
            'user_answer'  => ['10' => 'A'],
            'created_at'   => $fixedTime,
        ]);

        AssesmentResult::create([
            'assesment_id' => $source->id,
            'user_id'      => $this->user1->id,
            'score'        => 85,
            'pass'         => 1,
            'final'        => 1,
            'batch'        => 1,
            'user_answer'  => ['10' => 'A'],
            'created_at'   => $fixedTime,
        ]);

        // Pre-populate target with 1 matching row
        AssesmentResult::create([
            'assesment_id' => $duplicate->id,
            'user_id'      => $this->user1->id,
            'score'        => 85,
            'pass'         => 1,
            'final'        => 1,
            'batch'        => 1,
            'user_answer'  => ['10' => 'A'],
            'created_at'   => $fixedTime,
        ]);

        $this->assertSame(2, AssesmentResult::where('assesment_id', $source->id)->count());
        $this->assertSame(1, AssesmentResult::where('assesment_id', $duplicate->id)->count());

        $copied = $this->service->copyResults($source->id, $duplicate->id);

        $this->assertSame(1, $copied);
        $this->assertSame(2, AssesmentResult::where('assesment_id', $duplicate->id)->count());
    }

    /**
     * Test 5: Direct questions with IDENTICAL TEXT are mapped deterministically via sequential order
     */
    public function test_5_direct_questions_with_identical_text_mapped_deterministically(): void
    {
        $source = Assesment::create([
            'title'  => 'Duplicate Text Source ' . uniqid(),
            'type'   => 'content',
            'status' => 'OK',
        ]);

        // Create 2 direct questions with exact same text
        $qA = AssesmentQuestion::create([
            'assesment_id' => $source->id,
            'question'     => 'Apa nama Type Joint berikut :',
            'option'       => ['A' => 'Welded', 'B' => 'Bolted'],
            'answer'       => 'Welded',
            'is_used'      => true,
        ]);

        $qB = AssesmentQuestion::create([
            'assesment_id' => $source->id,
            'question'     => 'Apa nama Type Joint berikut :',
            'option'       => ['A' => 'Welded', 'B' => 'Bolted'],
            'answer'       => 'Welded',
            'is_used'      => true,
        ]);

        $duplicate = $this->duplicateService->duplicate($source, [
            'assasment_content_for' => 'content',
            'preserve_is_used'      => true,
        ]);

        $mapping = $this->service->buildQuestionMapping($source->id, $duplicate->id);

        $this->assertCount(2, $mapping);
        $this->assertArrayHasKey($qA->id, $mapping);
        $this->assertArrayHasKey($qB->id, $mapping);
        $this->assertNotSame($mapping[$qA->id], $mapping[$qB->id]);

        $dupQuestions = AssesmentQuestion::where('assesment_id', $duplicate->id)->orderBy('id')->get();
        $this->assertSame($dupQuestions[0]->id, $mapping[$qA->id]);
        $this->assertSame($dupQuestions[1]->id, $mapping[$qB->id]);
    }

    /**
     * Test 6: Bank questions mapping is deterministic via (question_bank_id, question_id)
     */
    public function test_6_bank_questions_mapping_deterministic(): void
    {
        $qb = QuestionBank::first() ?? QuestionBank::create([
            'title'        => 'Test QB ' . uniqid(),
            'description'  => 'Test Desc',
            'created_date' => date('Y-m-d'),
        ]);

        $qUnderlying1 = Question::create([
            'question_bank_id' => $qb->id,
            'question'         => 'Bank Question 1',
            'option'           => ['A' => 'Yes', 'B' => 'No'],
            'answer'           => 'Yes',
        ]);

        $qUnderlying2 = Question::create([
            'question_bank_id' => $qb->id,
            'question'         => 'Bank Question 2',
            'option'           => ['A' => 'True', 'B' => 'False'],
            'answer'           => 'True',
        ]);

        $source = Assesment::create([
            'title'  => 'Bank Assessment Source ' . uniqid(),
            'type'   => 'content',
            'status' => 'OK',
        ]);

        AssesmentQuestionBank::create([
            'assesment_id'     => $source->id,
            'question_bank_id' => $qb->id,
            'questions_amount' => 2,
        ]);

        $sq1 = AssesmentQuestion::create([
            'assesment_id'     => $source->id,
            'question_bank_id' => $qb->id,
            'question_id'      => $qUnderlying1->id,
            'question'         => $qUnderlying1->question,
            'option'           => $qUnderlying1->option,
            'answer'           => $qUnderlying1->answer,
            'is_used'          => true,
        ]);

        $sq2 = AssesmentQuestion::create([
            'assesment_id'     => $source->id,
            'question_bank_id' => $qb->id,
            'question_id'      => $qUnderlying2->id,
            'question'         => $qUnderlying2->question,
            'option'           => $qUnderlying2->option,
            'answer'           => $qUnderlying2->answer,
            'is_used'          => true,
        ]);

        $duplicate = $this->duplicateService->duplicate($source, [
            'assasment_content_for' => 'content',
            'preserve_is_used'      => true,
        ]);

        $mapping = $this->service->buildQuestionMapping($source->id, $duplicate->id);

        $this->assertCount(2, $mapping);
        $this->assertArrayHasKey($sq1->id, $mapping);
        $this->assertArrayHasKey($sq2->id, $mapping);

        $targetQ1 = AssesmentQuestion::find($mapping[$sq1->id]);
        $targetQ2 = AssesmentQuestion::find($mapping[$sq2->id]);

        $this->assertSame((int) $qb->id, (int) $targetQ1->question_bank_id);
        $this->assertSame((int) $qUnderlying1->id, (int) $targetQ1->question_id);
        $this->assertSame((int) $qb->id, (int) $targetQ2->question_bank_id);
        $this->assertSame((int) $qUnderlying2->id, (int) $targetQ2->question_id);
    }

    /**
     * Test 7: user_answer keys use new question IDs
     */
    public function test_7_user_answer_uses_new_question_ids(): void
    {
        [$source, $duplicate, $sourceResults, $sourceQuestions] = $this->createSourceWithQuestionsAndResults();

        $this->service->copyResults($source->id, $duplicate->id);

        $newQuestions = AssesmentQuestion::where('assesment_id', $duplicate->id)->orderBy('id')->get();
        $r1 = AssesmentResult::where('assesment_id', $duplicate->id)->where('user_id', $this->user1->id)->first();

        $this->assertArrayHasKey((string) $newQuestions[0]->id, $r1->user_answer);
        $this->assertArrayHasKey((string) $newQuestions[1]->id, $r1->user_answer);
        $this->assertSame('B', $r1->user_answer[(string) $newQuestions[0]->id]);
        $this->assertSame('A', $r1->user_answer[(string) $newQuestions[1]->id]);
    }

    /**
     * Test 8: question_snapshot uses new question IDs
     */
    public function test_8_question_snapshot_uses_new_question_ids(): void
    {
        [$source, $duplicate] = $this->createSourceWithQuestionsAndResults();

        $this->service->copyResults($source->id, $duplicate->id);

        $newQuestions = AssesmentQuestion::where('assesment_id', $duplicate->id)->orderBy('id')->get();
        $r1 = AssesmentResult::where('assesment_id', $duplicate->id)->where('user_id', $this->user1->id)->first();

        $this->assertSame((int) $newQuestions[0]->id, (int) $r1->question_snapshot[0]['question_id']);
        $this->assertSame((int) $newQuestions[1]->id, (int) $r1->question_snapshot[1]['question_id']);
    }

    /**
     * Test 9: NULL question_snapshot remains NULL
     */
    public function test_9_null_question_snapshot_remains_null(): void
    {
        [$source, $duplicate] = $this->createSourceWithQuestionsAndResults();

        $this->service->copyResults($source->id, $duplicate->id);

        $r3 = AssesmentResult::where('assesment_id', $duplicate->id)->where('user_id', $this->user3->id)->first();
        $this->assertNull($r3->question_snapshot);
    }

    /**
     * Test 10: Source results remain completely unchanged
     */
    public function test_10_source_results_remain_completely_unchanged(): void
    {
        [$source, $duplicate, $sourceResults] = $this->createSourceWithQuestionsAndResults();

        $sourceCountBefore = AssesmentResult::where('assesment_id', $source->id)->count();

        $this->service->copyResults($source->id, $duplicate->id);

        $this->assertSame($sourceCountBefore, AssesmentResult::where('assesment_id', $source->id)->count());

        foreach ($sourceResults as $sr) {
            $fresh = AssesmentResult::find($sr->id);
            $this->assertNotNull($fresh);
            $this->assertSame((int) $source->id, (int) $fresh->assesment_id);
            $this->assertSame($sr->score, $fresh->score);
            $this->assertSame($sr->pass, $fresh->pass);
            $this->assertSame($sr->final, $fresh->final);
        }
    }

    /**
     * Test 11, 12, 13, 14, 15: Score, pass, final, user_id, paper_based remain identical
     */
    public function test_11_to_15_all_result_attributes_remain_identical(): void
    {
        [$source, $duplicate] = $this->createSourceWithQuestionsAndResults();

        $this->service->copyResults($source->id, $duplicate->id);

        // User 2: score 80, pass 1, final 1, paper_based 1, evidence_paper_based
        $res2 = AssesmentResult::where('assesment_id', $duplicate->id)->where('user_id', $this->user2->id)->first();

        $this->assertSame($this->user2->id, (int) $res2->user_id);
        $this->assertSame(80, (int) $res2->score);
        $this->assertSame(1, (int) $res2->pass);
        $this->assertSame(1, (int) $res2->final);
        $this->assertTrue((bool) $res2->paper_based);
        $this->assertSame('offline_exam.pdf', $res2->evidence_paper_based);

        // User 3: score 50, pass 0, final 1, paper_based 0
        $res3 = AssesmentResult::where('assesment_id', $duplicate->id)->where('user_id', $this->user3->id)->first();

        $this->assertSame($this->user3->id, (int) $res3->user_id);
        $this->assertSame(50, (int) $res3->score);
        $this->assertSame(0, (int) $res3->pass);
        $this->assertSame(1, (int) $res3->final);
        $this->assertFalse((bool) $res3->paper_based);
    }
}
