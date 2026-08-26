<?php

namespace Tests\Unit;

use App\Models\Assesment;
use App\Models\AssesmentAssigned;
use App\Models\AssesmentQuestion;
use App\Models\AssesmentQuestionBank;
use App\Models\AssesmentResult;
use App\Models\QuestionBank;
use App\Services\AssessmentDuplicateService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AssessmentDuplicateServiceTest extends TestCase
{
    private AssessmentDuplicateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AssessmentDuplicateService();
    }

    public function test_extract_base_title_strips_existing_copy_suffix(): void
    {
        $this->assertSame('Assessment A', $this->service->extractBaseTitle('Assessment A'));
        $this->assertSame('Assessment A', $this->service->extractBaseTitle('Assessment A Copy 1'));
        $this->assertSame('Assessment A', $this->service->extractBaseTitle('Assessment A Copy 2'));
        $this->assertSame('Assessment A', $this->service->extractBaseTitle('Assessment A Copy 99'));
        $this->assertSame(
            'Post-test - Steel Industry Technology Class 2026 (SMTI Yogyakarta)',
            $this->service->extractBaseTitle('Post-test - Steel Industry Technology Class 2026 (SMTI Yogyakarta)')
        );
        $this->assertSame(
            'Post-test - Steel Industry Technology Class 2026 (SMTI Yogyakarta)',
            $this->service->extractBaseTitle('Post-test - Steel Industry Technology Class 2026 (SMTI Yogyakarta) Copy 3')
        );
    }

    public function test_generate_duplicate_title_first_copy(): void
    {
        $baseTitle = 'Test Suite Assessment ' . uniqid();

        // Create initial assessment
        $original = Assesment::create([
            'title' => $baseTitle,
            'status' => 'OK',
        ]);

        $copy1Title = $this->service->generateDuplicateTitle($original->title);
        $this->assertSame("{$baseTitle} Copy 1", $copy1Title);

        $original->delete();
    }

    public function test_generate_duplicate_title_sequential_copies(): void
    {
        $baseTitle = 'Seq Test ' . uniqid();

        $original = Assesment::create(['title' => $baseTitle, 'status' => 'OK']);
        $copy1 = Assesment::create(['title' => "{$baseTitle} Copy 1", 'status' => 'OK']);

        // When original is duplicated again
        $copy2Title = $this->service->generateDuplicateTitle($original->title);
        $this->assertSame("{$baseTitle} Copy 2", $copy2Title);

        // When copy1 is duplicated
        $copy2FromCopy1 = $this->service->generateDuplicateTitle($copy1->title);
        $this->assertSame("{$baseTitle} Copy 2", $copy2FromCopy1);

        $copy2 = Assesment::create(['title' => "{$baseTitle} Copy 2", 'status' => 'OK']);

        // When copy2 is duplicated
        $copy3Title = $this->service->generateDuplicateTitle($copy2->title);
        $this->assertSame("{$baseTitle} Copy 3", $copy3Title);

        // Cleanup
        $original->delete();
        $copy1->delete();
        $copy2->delete();
    }

    public function test_generate_duplicate_title_handles_deleted_gap_without_collision(): void
    {
        $baseTitle = 'Gap Test ' . uniqid();

        $original = Assesment::create(['title' => $baseTitle, 'status' => 'OK']);
        $copy1 = Assesment::create(['title' => "{$baseTitle} Copy 1", 'status' => 'OK']);
        // Copy 2 was deleted, Copy 3 exists
        $copy3 = Assesment::create(['title' => "{$baseTitle} Copy 3", 'status' => 'OK']);

        $nextTitle = $this->service->generateDuplicateTitle($original->title);

        // Must be unique and not collide with any existing title
        $this->assertNotSame("{$baseTitle} Copy 1", $nextTitle);
        $this->assertNotSame("{$baseTitle} Copy 3", $nextTitle);
        $this->assertSame("{$baseTitle} Copy 4", $nextTitle);

        // Cleanup
        $original->delete();
        $copy1->delete();
        $copy3->delete();
    }

    public function test_full_assessment_duplication_preserves_data_and_resets_attempts_and_unique_code(): void
    {
        $baseTitle = 'Full Duplication Test ' . uniqid();

        // 1. Create original assessment with full configuration
        $original = Assesment::create([
            'title'              => $baseTitle,
            'type'               => 'global',
            'duration'           => '60',
            'passing_grade'      => 80,
            'randomize'          => 'y',
            'timer'              => 'y',
            'time_limit_minutes' => 45,
            'has_access_period'  => true,
            'fromdate'           => '2026-08-01 08:00:00',
            'todate'             => '2026-08-31 17:00:00',
            'batch'              => 1,
            'total_remedials'    => 2,
            'unique_code'        => 'UNIQUE-ORIGINAL-123',
            'status'             => 'OK',
        ]);

        // 2. Attach direct question
        $question = AssesmentQuestion::create([
            'assesment_id' => $original->id,
            'question'     => 'What is OWL LMS?',
            'option'       => ['A' => 'Option 1', 'B' => 'Option 2'],
            'answer'       => 'A',
            'status'       => '1',
        ]);

        // 3. Attach question bank if available or create dummy
        $qb = QuestionBank::first();
        if ($qb) {
            $aqb = AssesmentQuestionBank::create([
                'assesment_id'     => $original->id,
                'question_bank_id' => $qb->id,
                'questions_amount' => 1,
            ]);

            AssesmentQuestion::create([
                'assesment_id'     => $original->id,
                'question_bank_id' => $qb->id,
                'question'         => 'Sample Bank Question',
                'option'           => ['A' => 'Bank Opt 1', 'B' => 'Bank Opt 2'],
                'answer'           => 'B',
                'status'           => '1',
                'is_used'          => true,
            ]);
        }

        // 4. Perform duplication
        $duplicated = $this->service->duplicate($original);

        // Assertions on duplicated assessment
        $this->assertNotEquals($original->id, $duplicated->id);
        $this->assertSame("{$baseTitle} Copy 1", $duplicated->title);
        $this->assertNotEmpty($duplicated->slug);
        $this->assertNotSame($original->slug, $duplicated->slug);
        $this->assertNull($duplicated->unique_code);

        // Preserved configuration
        $this->assertSame($original->type, $duplicated->type);
        $this->assertSame($original->passing_grade, $duplicated->passing_grade);
        $this->assertSame($original->timer, $duplicated->timer);
        $this->assertSame($original->time_limit_minutes, $duplicated->time_limit_minutes);
        $this->assertSame($original->has_access_period, $duplicated->has_access_period);
        $this->assertSame($original->status, $duplicated->status);

        // Questions duplicated
        $duplicatedQuestions = AssesmentQuestion::where('assesment_id', $duplicated->id)->get();
        $this->assertGreaterThanOrEqual(1, $duplicatedQuestions->count());

        // Check assigned and results are empty for duplicated assessment
        $this->assertSame(0, AssesmentAssigned::where('assesment_id', $duplicated->id)->count());
        $this->assertSame(0, AssesmentResult::where('assesment_id', $duplicated->id)->count());

        // Cleanup
        AssesmentQuestion::where('assesment_id', $duplicated->id)->delete();
        AssesmentQuestionBank::where('assesment_id', $duplicated->id)->delete();
        $duplicated->delete();

        AssesmentQuestion::where('assesment_id', $original->id)->delete();
        AssesmentQuestionBank::where('assesment_id', $original->id)->delete();
        $original->delete();
    }
}
