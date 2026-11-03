<?php

namespace Tests\Feature;

use App\Models\Assesment;
use App\Models\AssesmentQuestion;
use App\Models\AssesmentQuestionBank;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AssessmentQuestionBankOptimizationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', '2')->first()
            ?? User::where('role', '3')->first()
            ?? User::factory()->create(['role' => '2']);
    }

    public function test_store_assessment_question_bank_batch_inserts_and_randomizes_questions(): void
    {
        $assessment = Assesment::create([
            'title'               => 'Test Assessment QB ' . uniqid(),
            'type'                => 'global',
            'status'              => 'OK',
            'passing_grade'       => 80,
            'time_limit_minutes'  => 60,
        ]);

        $questionBank = QuestionBank::create([
            'title'        => 'Test Question Bank ' . uniqid(),
            'created_date' => now()->toDateString(),
        ]);

        for ($i = 1; $i <= 10; $i++) {
            Question::create([
                'question_bank_id' => $questionBank->id,
                'question'         => "Question test $i",
                'option'           => ['A' => 'Option A', 'B' => 'Option B'],
                'answer'           => 'A',
            ]);
        }

        $response = $this->actingAs($this->admin)->postJson(route('assessment.store.question.bank', $assessment->slug), [
            'assessment_slug'  => $assessment->slug,
            'id_question_bank' => $questionBank->id,
            'amount_question'  => 4,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status'  => 200,
            'message' => 'Assessment Question have already added successfully',
        ]);

        $this->assertDatabaseHas('assesment_question_banks', [
            'assesment_id'     => $assessment->id,
            'question_bank_id' => $questionBank->id,
            'questions_amount' => 4,
        ]);

        $allQuestions = AssesmentQuestion::where('assesment_id', $assessment->id)
            ->where('question_bank_id', $questionBank->id)
            ->get();

        $this->assertCount(10, $allQuestions);
        $usedCount = $allQuestions->where('is_used', 1)->count();
        $this->assertSame(4, $usedCount);
        $unusedCount = $allQuestions->where('is_used', 0)->count();
        $this->assertSame(6, $unusedCount);
    }

    public function test_cannot_add_duplicate_question_bank_to_same_assessment(): void
    {
        $assessment = Assesment::create([
            'title'         => 'Test Assessment Duplicate QB ' . uniqid(),
            'type'          => 'global',
            'status'        => 'OK',
            'passing_grade' => 75,
        ]);

        $questionBank = QuestionBank::create([
            'title'        => 'Test Bank ' . uniqid(),
            'created_date' => now()->toDateString(),
        ]);

        Question::create([
            'question_bank_id' => $questionBank->id,
            'question'         => 'Sample Q',
            'option'           => ['A' => '1', 'B' => '2'],
            'answer'           => 'A',
        ]);

        $this->actingAs($this->admin)->postJson(route('assessment.store.question.bank', $assessment->slug), [
            'assessment_slug'  => $assessment->slug,
            'id_question_bank' => $questionBank->id,
            'amount_question'  => 1,
        ]);

        $duplicateResponse = $this->actingAs($this->admin)->postJson(route('assessment.store.question.bank', $assessment->slug), [
            'assessment_slug'  => $assessment->slug,
            'id_question_bank' => $questionBank->id,
            'amount_question'  => 1,
        ]);

        $duplicateResponse->assertJson([
            'status'  => 422,
            'message' => 'Question Bank already exist!',
        ]);
    }

    public function test_edit_view_renders_successfully_with_question_count(): void
    {
        $assessment = Assesment::create([
            'title'         => 'Test Assessment Edit Page ' . uniqid(),
            'type'          => 'global',
            'status'        => 'OK',
            'passing_grade' => 75,
        ]);

        $response = $this->actingAs($this->admin)->get(route('assesment.edit', $assessment->slug));

        $response->assertStatus(200);
        $response->assertSee('Edit Assessment');
        $response->assertSee('Add Question Bank');
        $response->assertSee('Question Bank List');
    }
}
