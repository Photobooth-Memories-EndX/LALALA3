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

    public function test_duplicate_content_as_direct_content(): void
    {
        $original = Assesment::create([
            'title'           => 'Unit Direct Test ' . uniqid(),
            'type'            => 'content',
            'content_id'      => 45,
            'content_for'     => 'content',
            'trainingplan_id' => null,
            'status'          => 'OK',
        ]);

        $contentCountBefore = \App\Models\Content::count();

        // Single duplicate without overrides
        $duplicated = $this->service->duplicate($original);

        $this->assertNotEquals($original->id, $duplicated->id);
        $this->assertSame('content', $duplicated->type);
        $this->assertNull($duplicated->content_id);
        $this->assertNull($duplicated->trainingplan_id);

        // No new Content record is created
        $this->assertSame($contentCountBefore, \App\Models\Content::count());

        // Original assessment and content remain untouched
        $original->refresh();
        $this->assertSame('content', $original->content_for);
        $this->assertNull($original->trainingplan_id);
        $this->assertSame(45, (int) $original->content_id);

        $duplicated->delete();
        $original->delete();
    }

    public function test_duplicate_content_as_playlist_content(): void
    {
        $original = Assesment::create([
            'title'           => 'Unit Playlist Test ' . uniqid(),
            'type'            => 'content',
            'content_id'      => 45,
            'content_for'     => 'playlist',
            'trainingplan_id' => 10,
            'status'          => 'OK',
        ]);

        $contentCountBefore = \App\Models\Content::count();

        // Single duplicate without overrides: preserves playlist and content
        $duplicated = $this->service->duplicate($original);

        $this->assertNotEquals($original->id, $duplicated->id);
        $this->assertSame('content', $duplicated->type);
        $this->assertSame('playlist', $duplicated->content_for);
        $this->assertSame(10, (int) $duplicated->trainingplan_id);
        $this->assertSame(45, (int) $duplicated->content_id);

        // No new Content record is created
        $this->assertSame($contentCountBefore, \App\Models\Content::count());

        // TrainingPlanDetail is ensured for (10, 45)
        $this->assertTrue(\App\Models\TrainingPlanDetail::where('trainingplan_id', 10)->where('content_id', 45)->exists());

        // Test idempotency: duplicating again does not create duplicate pivot
        $pivotCountBefore = \App\Models\TrainingPlanDetail::where('trainingplan_id', 10)->where('content_id', 45)->count();
        $duplicated2 = $this->service->duplicate($original);
        $pivotCountAfter = \App\Models\TrainingPlanDetail::where('trainingplan_id', 10)->where('content_id', 45)->count();
        $this->assertSame($pivotCountBefore, $pivotCountAfter);

        // Original remains untouched
        $original->refresh();
        $this->assertSame('playlist', $original->content_for);
        $this->assertSame(10, (int) $original->trainingplan_id);
        $this->assertSame(45, (int) $original->content_id);

        $duplicated2->delete();
        $duplicated->delete();
        $original->delete();
    }

    public function test_duplicate_existing_playlist_content_to_another_playlist(): void
    {
        $original = Assesment::create([
            'title'           => 'Unit Repath Test ' . uniqid(),
            'type'            => 'content',
            'content_id'      => 45,
            'content_for'     => 'playlist',
            'trainingplan_id' => 10,
            'status'          => 'OK',
        ]);

        // Explicit override specifies target playlist and preserves content relation
        $duplicated = $this->service->duplicate($original, [
            'content_for'     => 'playlist',
            'trainingplan_id' => 20,
        ]);

        $this->assertSame('playlist', $duplicated->content_for);
        $this->assertSame(20, (int) $duplicated->trainingplan_id);
        $this->assertSame(45, (int) $duplicated->content_id);

        // TrainingPlanDetail is ensured in target playlist 20
        $this->assertTrue(\App\Models\TrainingPlanDetail::where('trainingplan_id', 20)->where('content_id', 45)->exists());

        // Original remains bound to Playlist 10 and original content 45
        $original->refresh();
        $this->assertSame(10, (int) $original->trainingplan_id);
        $this->assertSame(45, (int) $original->content_id);

        $duplicated->delete();
        $original->delete();
    }

    public function test_duplicate_with_explicit_content_id_override_uses_specified_content(): void
    {
        $original = Assesment::create([
            'title'           => 'Explicit Content Override Test ' . uniqid(),
            'type'            => 'content',
            'content_id'      => 45,
            'content_for'     => 'content',
            'status'          => 'OK',
        ]);

        $contentCountBefore = \App\Models\Content::count();

        // Pass explicit content_id (Bulk Duplicate behavior)
        $duplicated = $this->service->duplicate($original, [
            'content_id'      => 45,
            'content_for'     => 'playlist',
            'trainingplan_id' => 10,
        ]);

        $this->assertSame(45, (int) $duplicated->content_id);
        $this->assertSame($contentCountBefore, \App\Models\Content::count());

        $duplicated->delete();
        $original->delete();
    }

    public function test_duplicate_non_content_with_playlist_override_throws_exception(): void
    {
        $global = Assesment::create([
            'title'  => 'Unit Global Override ' . uniqid(),
            'type'   => 'global',
            'status' => 'OK',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        try {
            $this->service->duplicate($global, [
                'content_for'     => 'playlist',
                'trainingplan_id' => 10,
            ]);
        } finally {
            $global->delete();
        }
    }

    public function test_duplicate_does_not_copy_results_histories_or_assignments(): void
    {
        $original = Assesment::create([
            'title'   => 'Unit Results Safety ' . uniqid(),
            'type'    => 'content',
            'content_id' => 45,
            'status'  => 'OK',
        ]);

        // Add dummy result to original
        $result = AssesmentResult::create([
            'assesment_id' => $original->id,
            'user_id'      => 1,
            'batch'        => '1',
            'user_answer'  => [],
            'score'        => 90,
            'pass'         => 1,
            'final'        => 1,
        ]);

        $duplicated = $this->service->duplicate($original);

        // Check duplicated assessment results count is strictly 0
        $this->assertSame(0, AssesmentResult::where('assesment_id', $duplicated->id)->count());
        $this->assertSame(0, AssesmentAssigned::where('assesment_id', $duplicated->id)->count());

        // Original still has its result
        $this->assertSame(1, AssesmentResult::where('assesment_id', $original->id)->count());

        // Cleanup
        $result->delete();
        $duplicated->delete();
        $original->delete();
    }

    public function test_duplicate_assessment_with_zero_questions_succeeds(): void
    {
        $original = Assesment::create([
            'title'           => 'Unit Zero Questions ' . uniqid(),
            'type'            => 'content',
            'content_id'      => 45,
            'content_for'     => 'content',
            'trainingplan_id' => null,
            'status'          => 'OK',
            'passing_grade'   => 80,
        ]);

        $duplicated = $this->service->duplicate($original);

        $this->assertNotEquals($original->id, $duplicated->id);
        $this->assertSame(0, AssesmentQuestion::where('assesment_id', $duplicated->id)->count());
        $this->assertSame(0, AssesmentQuestionBank::where('assesment_id', $duplicated->id)->count());
        $this->assertSame(80, (int) $duplicated->passing_grade);

        $duplicated->delete();
        $original->delete();
    }

    public function test_duplicate_trainingplan_with_content_overrides_throws_exception(): void
    {
        $tp = Assesment::create([
            'title'           => 'Unit TP Override ' . uniqid(),
            'type'            => 'trainingplan',
            'trainingplan_id' => 10,
            'status'          => 'OK',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        try {
            $this->service->duplicate($tp, [
                'content_for'     => 'playlist',
                'trainingplan_id' => 10,
            ]);
        } finally {
            $tp->delete();
        }
    }

    public function test_duplicate_playlist_content_without_trainingplan_id_throws_exception(): void
    {
        $original = Assesment::create([
            'title'       => 'Unit Missing TP ID ' . uniqid(),
            'type'        => 'content',
            'content_id'  => 45,
            'content_for' => 'content',
            'status'      => 'OK',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        try {
            $this->service->duplicate($original, [
                'content_for' => 'playlist',
                // trainingplan_id missing
            ]);
        } finally {
            $original->delete();
        }
    }

    public function test_transaction_rollback_leaves_no_partial_records(): void
    {
        $countBefore = Assesment::count();

        $original = Assesment::create([
            'title'       => 'Unit Rollback Test ' . uniqid(),
            'type'        => 'content',
            'content_id'  => 45,
            'content_for' => 'content',
            'status'      => 'OK',
        ]);

        // Attempt duplicate with invalid content_for to trigger exception inside transaction
        try {
            $this->service->duplicate($original, [
                'content_for' => 'invalid_context',
            ]);
            $this->fail('Expected exception was not thrown');
        } catch (\InvalidArgumentException $e) {
            // Expected
        }

        // Clean up original
        $original->delete();

        // Count must be back to exactly countBefore
        $this->assertSame($countBefore, Assesment::count());
    }
}
