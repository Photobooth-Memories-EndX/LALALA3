<?php

namespace Tests\Unit;

use App\Models\Assesment;
use App\Models\AssesmentAssigned;
use App\Models\AssesmentQuestion;
use App\Models\AssesmentQuestionBank;
use App\Models\AssesmentResult;
use App\Models\QuestionBank;
use App\Services\AssessmentDuplicateService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AssessmentIndividualDuplicateTest extends TestCase
{
    use DatabaseTransactions;

    private AssessmentDuplicateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AssessmentDuplicateService();
    }

    public function test_duplicate_individual_source_global_status_ok_results_in_type_null_and_status_not(): void
    {
        $original = Assesment::create([
            'title'  => 'Individual Global Source ' . uniqid(),
            'type'   => 'global',
            'status' => 'OK',
        ]);

        $duplicate = $this->service->duplicateIndividual($original);

        $this->assertNotEquals($original->id, $duplicate->id);
        $this->assertNull($duplicate->type, 'Duplicate assessment must have type = NULL');
        $this->assertSame('NOT', $duplicate->status, 'Duplicate assessment must have status = NOT when source is OK');

        // Source assessment must remain untouched
        $original->refresh();
        $this->assertSame('global', $original->type, 'Source type must not change');
        $this->assertSame('OK', $original->status, 'Source status must not change');
    }

    public function test_duplicate_individual_source_content_status_ok_results_in_type_null_and_status_not(): void
    {
        $original = Assesment::create([
            'title'                 => 'Individual Content Source ' . uniqid(),
            'type'                  => 'content',
            'assasment_content_for' => 'content',
            'status'                => 'OK',
        ]);

        $duplicate = $this->service->duplicateIndividual($original);

        $this->assertNotEquals($original->id, $duplicate->id);
        $this->assertNull($duplicate->type, 'Duplicate assessment must have type = NULL');
        $this->assertSame('NOT', $duplicate->status, 'Duplicate assessment must have status = NOT when source is OK');

        // Source assessment must remain untouched
        $original->refresh();
        $this->assertSame('content', $original->type, 'Source type must not change');
        $this->assertSame('OK', $original->status, 'Source status must not change');
    }

    public function test_duplicate_individual_source_trainingplan_status_ok_results_in_type_null_and_status_not(): void
    {
        $original = Assesment::create([
            'title'                 => 'Individual TP Source ' . uniqid(),
            'type'                  => 'trainingplan',
            'assasment_content_for' => 'content',
            'status'                => 'OK',
        ]);

        $duplicate = $this->service->duplicateIndividual($original);

        $this->assertNotEquals($original->id, $duplicate->id);
        $this->assertNull($duplicate->type, 'Duplicate assessment must have type = NULL');
        $this->assertSame('NOT', $duplicate->status, 'Duplicate assessment must have status = NOT when source is OK');

        // Source assessment must remain untouched
        $original->refresh();
        $this->assertSame('trainingplan', $original->type, 'Source type must not change');
        $this->assertSame('OK', $original->status, 'Source status must not change');
    }

    public function test_duplicate_individual_source_status_not_ok_preserves_existing_status(): void
    {
        $original = Assesment::create([
            'title'  => 'Individual Inactive Source ' . uniqid(),
            'type'   => 'global',
            'status' => 'NOT',
        ]);

        $duplicate = $this->service->duplicateIndividual($original);

        $this->assertNotEquals($original->id, $duplicate->id);
        $this->assertNull($duplicate->type, 'Duplicate assessment must have type = NULL');
        $this->assertSame('NOT', $duplicate->status, 'Duplicate assessment status should remain NOT when source status is NOT');

        // Source assessment must remain untouched
        $original->refresh();
        $this->assertSame('global', $original->type);
        $this->assertSame('NOT', $original->status);
    }

    public function test_duplicate_individual_preserves_questions_question_banks_and_settings(): void
    {
        $baseTitle = 'Individual Full Settings Test ' . uniqid();

        $original = Assesment::create([
            'title'              => $baseTitle,
            'type'               => 'global',
            'duration'           => '60',
            'passing_grade'      => 85,
            'randomize'          => 'y',
            'timer'              => 'y',
            'time_limit_minutes' => 50,
            'has_access_period'  => true,
            'fromdate'           => '2026-08-01 08:00:00',
            'todate'             => '2026-08-31 17:00:00',
            'total_remedials'    => 3,
            'unique_code'        => 'IND-CODE',
            'status'             => 'OK',
        ]);

        // Direct question
        $question = AssesmentQuestion::create([
            'assesment_id' => $original->id,
            'question'     => 'Sample Individual Direct Question',
            'option'       => ['A' => 'Option 1', 'B' => 'Option 2'],
            'answer'       => 'A',
            'status'       => '1',
        ]);

        // Question bank if exists
        $qb = QuestionBank::first();
        if ($qb) {
            AssesmentQuestionBank::create([
                'assesment_id'     => $original->id,
                'question_bank_id' => $qb->id,
                'questions_amount' => 1,
            ]);

            AssesmentQuestion::create([
                'assesment_id'     => $original->id,
                'question_bank_id' => $qb->id,
                'question'         => 'Sample Individual Bank Question',
                'option'           => ['A' => 'Bank Opt 1', 'B' => 'Bank Opt 2'],
                'answer'           => 'B',
                'status'           => '1',
                'is_used'          => true,
            ]);
        }

        $duplicate = $this->service->duplicateIndividual($original);

        // Required Type and Status behavior
        $this->assertNull($duplicate->type, 'Duplicate must have type = NULL');
        $this->assertSame('NOT', $duplicate->status, 'Duplicate must have status = NOT');

        // Preserved settings
        $this->assertSame($original->passing_grade, $duplicate->passing_grade);
        $this->assertSame($original->timer, $duplicate->timer);
        $this->assertSame($original->time_limit_minutes, $duplicate->time_limit_minutes);
        $this->assertSame($original->has_access_period, $duplicate->has_access_period);
        $this->assertSame($original->total_remedials, $duplicate->total_remedials);

        // Reset fields
        $this->assertNull($duplicate->unique_code);
        $this->assertSame(0, AssesmentAssigned::where('assesment_id', $duplicate->id)->count());
        $this->assertSame(0, AssesmentResult::where('assesment_id', $duplicate->id)->count());

        // Questions duplicated
        $this->assertGreaterThanOrEqual(1, AssesmentQuestion::where('assesment_id', $duplicate->id)->count());

        // Source remains untouched
        $original->refresh();
        $this->assertSame('global', $original->type);
        $this->assertSame('OK', $original->status);
        $this->assertSame('IND-CODE', $original->unique_code);
    }

    public function test_non_individual_duplicate_caller_preserves_existing_behavior(): void
    {
        $original = Assesment::create([
            'title'  => 'Non Individual Caller ' . uniqid(),
            'type'   => 'global',
            'status' => 'OK',
        ]);

        // Default duplicate without is_individual override preserves type and status
        $duplicate = $this->service->duplicate($original);

        $this->assertSame('global', $duplicate->type);
        $this->assertSame('OK', $duplicate->status);
    }
}