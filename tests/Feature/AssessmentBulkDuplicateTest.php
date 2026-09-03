<?php

namespace Tests\Feature;

use App\Models\Assesment;
use App\Models\AssesmentAssigned;
use App\Models\AssesmentQuestion;
use App\Models\AssesmentQuestionBank;
use App\Models\AssesmentResult;
use App\Models\AssesmentScoreHistory;
use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\QuestionBank;
use App\Models\TrainingPlan;
use App\Models\User;
use App\Services\AssessmentBulkDuplicateService;
use App\Services\AssessmentDuplicateService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class AssessmentBulkDuplicateTest extends TestCase
{
    use DatabaseTransactions;

    protected AssessmentBulkDuplicateService $bulkService;
    protected AssessmentDuplicateService $duplicateService;
    protected TrainingPlan $playlist1;
    protected TrainingPlan $playlist2;
    protected Content $content1;
    protected Content $content2;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->duplicateService = new AssessmentDuplicateService();
        $this->bulkService = new AssessmentBulkDuplicateService($this->duplicateService);

        $classification = Classification::first() ?? Classification::create([
            'name'        => 'Test Classification ' . Str::random(5),
            'description' => 'Test Desc',
        ]);

        $this->playlist1 = TrainingPlan::create([
            'title'             => 'Bulk Test Playlist 1 ' . Str::random(5),
            'classification_id' => $classification->id,
            'description'       => 'Test Playlist Description 1',
        ]);

        $this->playlist2 = TrainingPlan::create([
            'title'             => 'Bulk Test Playlist 2 ' . Str::random(5),
            'classification_id' => $classification->id,
            'description'       => 'Test Playlist Description 2',
        ]);

        $this->content1 = Content::create([
            'title'             => 'Bulk Content 1 ' . Str::random(5),
            'classification_id' => $classification->id,
            'type'              => 'video',
            'visibility'        => 'public',
        ]);

        $this->content2 = Content::create([
            'title'             => 'Bulk Content 2 ' . Str::random(5),
            'classification_id' => $classification->id,
            'type'              => 'video',
            'visibility'        => 'public',
        ]);

        $this->user = User::factory()->create([
            'team'       => 'QA Team',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'bulk-u-' . Str::uuid(),
            'sap_code'   => 'SAP-BULK-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);
    }

    public function test_duplicate_single_active_content_assessment_to_playlist(): void
    {
        $assessment = Assesment::create([
            'title'                 => 'Direct Source ' . Str::random(5),
            'type'                  => 'content',
            'content_id'            => $this->content1->id,
            'assasment_content_for' => 'content',
            'status'                => 'OK',
            'passing_grade'         => 75,
        ]);

        $qb = QuestionBank::first();
        if ($qb) {
            AssesmentQuestionBank::create([
                'assesment_id'     => $assessment->id,
                'question_bank_id' => $qb->id,
                'questions_amount' => 1,
            ]);

            AssesmentQuestion::create([
                'assesment_id'     => $assessment->id,
                'question_bank_id' => $qb->id,
                'question'         => 'Sample question text?',
                'option'           => ['A' => 'Opt A', 'B' => 'Opt B'],
                'answer'           => 'A',
                'is_used'          => true,
            ]);
        } else {
            AssesmentQuestion::create([
                'assesment_id' => $assessment->id,
                'question'     => 'Direct question text?',
                'option'       => ['A' => 'Opt A', 'B' => 'Opt B'],
                'answer'       => 'A',
                'is_used'      => true,
            ]);
        }

        $result = $this->bulkService->duplicateMany([$assessment->id], [
            'target_context'  => 'playlist',
            'trainingplan_id' => $this->playlist1->id,
        ]);

        $this->assertSame(1, $result['total']);
        $this->assertSame(1, $result['success']);
        $this->assertSame(0, $result['skipped']);
        $this->assertSame(0, $result['failed']);

        $newAssessmentId = $result['created_items'][0]['new_assessment_id'];
        $newAssessment = Assesment::findOrFail($newAssessmentId);

        $this->assertNotEquals($assessment->id, $newAssessment->id);
        $this->assertSame('content', $newAssessment->type);
        $this->assertSame('playlist', $newAssessment->assasment_content_for);
        $this->assertSame($this->playlist1->id, (int) $newAssessment->trainingplan_id);
        $this->assertSame($this->content1->id, (int) $newAssessment->content_id);
        $this->assertSame(75, (int) $newAssessment->passing_grade);
        $this->assertNull($newAssessment->unique_code);

        // Child question banks and questions exist on duplicate
        $this->assertSame(1, AssesmentQuestionBank::where('assesment_id', $newAssessment->id)->count());
        $this->assertSame(1, AssesmentQuestion::where('assesment_id', $newAssessment->id)->count());

        // Source assessment unchanged
        $assessment->refresh();
        $this->assertSame('content', $assessment->assasment_content_for);
        $this->assertNull($assessment->trainingplan_id);
    }

    public function test_duplicate_multiple_selected_active_content_assessments_to_playlist(): void
    {
        $a1 = Assesment::create([
            'title'                 => 'Multi Source 1 ' . Str::random(5),
            'type'                  => 'content',
            'content_id'            => $this->content1->id,
            'assasment_content_for' => 'content',
            'status'                => 'OK',
        ]);

        $a2 = Assesment::create([
            'title'                 => 'Multi Source 2 ' . Str::random(5),
            'type'                  => 'content',
            'content_id'            => $this->content2->id,
            'assasment_content_for' => 'content',
            'status'                => 'OK',
        ]);

        $result = $this->bulkService->duplicateMany([$a1->id, $a2->id], [
            'target_context'  => 'playlist',
            'trainingplan_id' => $this->playlist1->id,
        ]);

        $this->assertSame(2, $result['total']);
        $this->assertSame(2, $result['success']);
        $this->assertSame(0, $result['skipped']);
        $this->assertSame(0, $result['failed']);
        $this->assertCount(2, $result['created_items']);
    }

    public function test_select_all_active_only_duplicates_active_content_assessments(): void
    {
        // Preview all active
        $preview = $this->bulkService->previewAllActive([
            'target_context'  => 'playlist',
            'trainingplan_id' => $this->playlist1->id,
        ]);

        $this->assertGreaterThanOrEqual(1, $preview['total']);
        // Verify every eligible item is type = content and status = OK
        foreach ($preview['eligible_items'] as $item) {
            $record = Assesment::find($item['source_assessment_id']);
            $this->assertSame('content', $record->type);
            $this->assertSame('OK', $record->status);
        }
    }

    public function test_global_assessment_is_excluded_from_bulk_duplicate(): void
    {
        $global = Assesment::create([
            'title'  => 'Global Assesment ' . Str::random(5),
            'type'   => 'global',
            'status' => 'OK',
        ]);

        $result = $this->bulkService->duplicateMany([$global->id], [
            'target_context'  => 'playlist',
            'trainingplan_id' => $this->playlist1->id,
        ]);

        $this->assertSame(1, $result['total']);
        $this->assertSame(0, $result['success']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame('INVALID_TYPE', $result['skipped_items'][0]['reason']);
    }

    public function test_trainingplan_assessment_is_excluded_from_bulk_duplicate(): void
    {
        $tpAssessment = Assesment::create([
            'title'           => 'Final Exam Playlist ' . Str::random(5),
            'type'            => 'trainingplan',
            'trainingplan_id' => $this->playlist1->id,
            'status'          => 'OK',
        ]);

        $result = $this->bulkService->duplicateMany([$tpAssessment->id], [
            'target_context'  => 'playlist',
            'trainingplan_id' => $this->playlist2->id,
        ]);

        $this->assertSame(1, $result['total']);
        $this->assertSame(0, $result['success']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame('INVALID_TYPE', $result['skipped_items'][0]['reason']);
    }

    public function test_inactive_assessment_is_excluded_from_bulk_duplicate(): void
    {
        $inactive = Assesment::create([
            'title'                 => 'Inactive Content ' . Str::random(5),
            'type'                  => 'content',
            'content_id'            => $this->content1->id,
            'assasment_content_for' => 'content',
            'status'                => 'NOT',
        ]);

        $result = $this->bulkService->duplicateMany([$inactive->id], [
            'target_context'  => 'playlist',
            'trainingplan_id' => $this->playlist1->id,
        ]);

        $this->assertSame(1, $result['total']);
        $this->assertSame(0, $result['success']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame('INACTIVE', $result['skipped_items'][0]['reason']);
    }

    public function test_playlist_content_source_can_target_another_playlist(): void
    {
        $source = Assesment::create([
            'title'                 => 'Playlist 1 Content Assessment ' . Str::random(5),
            'type'                  => 'content',
            'content_id'            => $this->content1->id,
            'assasment_content_for' => 'playlist',
            'trainingplan_id'       => $this->playlist1->id,
            'status'                => 'OK',
        ]);

        $result = $this->bulkService->duplicateMany([$source->id], [
            'target_context'  => 'playlist',
            'trainingplan_id' => $this->playlist2->id,
        ]);

        $this->assertSame(1, $result['success']);
        $newAssessmentId = $result['created_items'][0]['new_assessment_id'];
        $newAssessment = Assesment::findOrFail($newAssessmentId);

        $this->assertSame('playlist', $newAssessment->assasment_content_for);
        $this->assertSame($this->playlist2->id, (int) $newAssessment->trainingplan_id);

        // Source remains in Playlist 1
        $source->refresh();
        $this->assertSame($this->playlist1->id, (int) $source->trainingplan_id);
    }

    public function test_target_playlist_requires_valid_trainingplan_id(): void
    {
        $assessment = Assesment::create([
            'title'                 => 'Valid Content ' . Str::random(5),
            'type'                  => 'content',
            'content_id'            => $this->content1->id,
            'assasment_content_for' => 'content',
            'status'                => 'OK',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $this->bulkService->duplicateMany([$assessment->id], [
            'target_context'  => 'playlist',
            'trainingplan_id' => 99999999, // non-existent
        ]);
    }

    public function test_existing_active_playlist_assessment_is_skipped_due_to_idempotency(): void
    {
        // First, create an active playlist assessment for content1 in playlist1
        Assesment::create([
            'title'                 => 'Existing Active PL Assessment ' . Str::random(5),
            'type'                  => 'content',
            'content_id'            => $this->content1->id,
            'assasment_content_for' => 'playlist',
            'trainingplan_id'       => $this->playlist1->id,
            'status'                => 'OK',
        ]);

        // Source assessment for same content1
        $source = Assesment::create([
            'title'                 => 'Source Direct Content ' . Str::random(5),
            'type'                  => 'content',
            'content_id'            => $this->content1->id,
            'assasment_content_for' => 'content',
            'status'                => 'OK',
        ]);

        $result = $this->bulkService->duplicateMany([$source->id], [
            'target_context'  => 'playlist',
            'trainingplan_id' => $this->playlist1->id,
        ]);

        $this->assertSame(1, $result['total']);
        $this->assertSame(0, $result['success']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame('ALREADY_EXISTS', $result['skipped_items'][0]['reason']);
    }

    public function test_existing_inactive_playlist_assessment_does_not_block_duplication(): void
    {
        // Inactive playlist assessment for content1 in playlist1
        Assesment::create([
            'title'                 => 'Existing Inactive PL Assessment ' . Str::random(5),
            'type'                  => 'content',
            'content_id'            => $this->content1->id,
            'assasment_content_for' => 'playlist',
            'trainingplan_id'       => $this->playlist1->id,
            'status'                => 'NOT',
        ]);

        // Source assessment
        $source = Assesment::create([
            'title'                 => 'Source Content ' . Str::random(5),
            'type'                  => 'content',
            'content_id'            => $this->content1->id,
            'assasment_content_for' => 'content',
            'status'                => 'OK',
        ]);

        $result = $this->bulkService->duplicateMany([$source->id], [
            'target_context'  => 'playlist',
            'trainingplan_id' => $this->playlist1->id,
        ]);

        $this->assertSame(1, $result['success']);
        $this->assertSame(0, $result['skipped']);
    }

    public function test_duplicate_does_not_copy_results_histories_or_assignments(): void
    {
        $source = Assesment::create([
            'title'                 => 'Source With Results ' . Str::random(5),
            'type'                  => 'content',
            'content_id'            => $this->content1->id,
            'assasment_content_for' => 'content',
            'status'                => 'OK',
        ]);

        $resultRecord = AssesmentResult::create([
            'assesment_id' => $source->id,
            'user_id'      => $this->user->id,
            'batch'        => '1',
            'score'        => 95,
            'pass'         => 1,
            'final'        => 1,
            'user_answer'  => [],
        ]);

        $assignedRecord = AssesmentAssigned::create([
            'assesment_id'         => $source->id,
            'assigned_id'          => $this->user->id,
            'assigned_type'        => 'user',
            'assigned_description' => 'Test Desc',
        ]);

        $bulkResult = $this->bulkService->duplicateMany([$source->id], [
            'target_context'  => 'playlist',
            'trainingplan_id' => $this->playlist1->id,
        ]);

        $newAssessmentId = $bulkResult['created_items'][0]['new_assessment_id'];

        $this->assertSame(0, AssesmentResult::where('assesment_id', $newAssessmentId)->count());
        $this->assertSame(0, AssesmentScoreHistory::where('assesment_id', $newAssessmentId)->count());
        $this->assertSame(0, AssesmentAssigned::where('assesment_id', $newAssessmentId)->count());

        // Original retains its result and assignment
        $this->assertSame(1, AssesmentResult::where('assesment_id', $source->id)->count());
        $this->assertSame(1, AssesmentAssigned::where('assesment_id', $source->id)->count());
    }

    public function test_progress_tables_remain_untouched_during_bulk_duplicate(): void
    {
        // Seed a playlist progress and content user progress
        $pcu = PlaylistContentUser::create([
            'user_id'         => $this->user->id,
            'trainingplan_id' => $this->playlist1->id,
            'content_id'      => $this->content1->id,
            'progress'        => 100,
            'finish'          => '1',
            'learning_status' => 'finished',
        ]);

        $cu = ContentUser::create([
            'user_id'         => $this->user->id,
            'content_id'      => $this->content1->id,
            'status'          => 'mandatory',
            'finish'          => '1',
            'learning_status' => 'finished',
            'progress'        => 100,
        ]);

        $source = Assesment::create([
            'title'                 => 'Source Progress Test ' . Str::random(5),
            'type'                  => 'content',
            'content_id'            => $this->content1->id,
            'assasment_content_for' => 'content',
            'status'                => 'OK',
        ]);

        $bulkResult = $this->bulkService->duplicateMany([$source->id], [
            'target_context'  => 'playlist',
            'trainingplan_id' => $this->playlist1->id,
        ]);

        $this->assertSame(1, $bulkResult['success']);

        // Assert PCU and CU are unchanged
        $pcu->refresh();
        $this->assertSame(100, $pcu->progress);
        $this->assertSame('1', (string) $pcu->finish);

        $cu->refresh();
        $this->assertSame(100, $cu->progress);
        $this->assertSame('1', (string) $cu->finish);
    }

    public function test_repeated_bulk_operation_is_idempotent_for_playlist_target(): void
    {
        $a1 = Assesment::create([
            'title'                 => 'Idempotent 1 ' . Str::random(5),
            'type'                  => 'content',
            'content_id'            => $this->content1->id,
            'assasment_content_for' => 'content',
            'status'                => 'OK',
        ]);

        $a2 = Assesment::create([
            'title'                 => 'Idempotent 2 ' . Str::random(5),
            'type'                  => 'content',
            'content_id'            => $this->content2->id,
            'assasment_content_for' => 'content',
            'status'                => 'OK',
        ]);

        // First run: both should be created
        $run1 = $this->bulkService->duplicateMany([$a1->id, $a2->id], [
            'target_context'  => 'playlist',
            'trainingplan_id' => $this->playlist1->id,
        ]);

        $this->assertSame(2, $run1['success']);
        $this->assertSame(0, $run1['skipped']);

        // Second run: both must be skipped
        $run2 = $this->bulkService->duplicateMany([$a1->id, $a2->id], [
            'target_context'  => 'playlist',
            'trainingplan_id' => $this->playlist1->id,
        ]);

        $this->assertSame(0, $run2['success']);
        $this->assertSame(2, $run2['skipped']);
        $this->assertSame('ALREADY_EXISTS', $run2['skipped_items'][0]['reason']);
        $this->assertSame('ALREADY_EXISTS', $run2['skipped_items'][1]['reason']);
    }

    public function test_preview_accurately_reflects_counts(): void
    {
        $a1 = Assesment::create([
            'title'                 => 'Preview 1 ' . Str::random(5),
            'type'                  => 'content',
            'content_id'            => $this->content1->id,
            'assasment_content_for' => 'content',
            'status'                => 'OK',
        ]);

        // Create an existing one in playlist for content2
        Assesment::create([
            'title'                 => 'Preview Existing ' . Str::random(5),
            'type'                  => 'content',
            'content_id'            => $this->content2->id,
            'assasment_content_for' => 'playlist',
            'trainingplan_id'       => $this->playlist1->id,
            'status'                => 'OK',
        ]);

        $a2 = Assesment::create([
            'title'                 => 'Preview 2 ' . Str::random(5),
            'type'                  => 'content',
            'content_id'            => $this->content2->id,
            'assasment_content_for' => 'content',
            'status'                => 'OK',
        ]);

        $global = Assesment::create([
            'title'  => 'Preview Global ' . Str::random(5),
            'type'   => 'global',
            'status' => 'OK',
        ]);

        $inactive = Assesment::create([
            'title'                 => 'Preview Inactive ' . Str::random(5),
            'type'                  => 'content',
            'content_id'            => $this->content1->id,
            'assasment_content_for' => 'content',
            'status'                => 'NOT',
        ]);

        $preview = $this->bulkService->preview([$a1->id, $a2->id, $global->id, $inactive->id], [
            'target_context'  => 'playlist',
            'trainingplan_id' => $this->playlist1->id,
        ]);

        $this->assertSame(4, $preview['total']);
        $this->assertSame(1, $preview['will_be_created']); // a1
        $this->assertSame(1, $preview['already_exists']);  // a2
        $this->assertSame(2, $preview['ineligible']);      // global, inactive
    }
}
