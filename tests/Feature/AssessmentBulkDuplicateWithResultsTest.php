<?php

namespace Tests\Feature;

use App\Models\Assesment;
use App\Models\AssesmentQuestion;
use App\Models\AssesmentResult;
use App\Models\Classification;
use App\Models\Content;
use App\Models\TrainingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class AssessmentBulkDuplicateWithResultsTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $studentA;
    protected User $studentB;
    protected User $studentC;
    protected TrainingPlan $playlist;
    protected Content $content;
    protected Classification $classification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->classification = Classification::firstOrCreate(
            ['classification_name' => 'Bulk Result Test Classification'],
            ['slug' => 'bulk-result-test-classification']
        );

        $this->admin = User::factory()->create([
            'team'       => 'Admin Team',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'adm-sso-' . Str::uuid(),
            'sap_code'   => 'SAP-ADM-' . rand(1000, 9999),
            'role'       => 2, // Admin with assessment_management
            'active'     => 1,
        ]);

        $this->studentA = User::factory()->create([
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'stu-a-' . Str::uuid(),
            'sap_code'   => 'SAP-A-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->studentB = User::factory()->create([
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'stu-b-' . Str::uuid(),
            'sap_code'   => 'SAP-B-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->studentC = User::factory()->create([
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'stu-c-' . Str::uuid(),
            'sap_code'   => 'SAP-C-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->content = Content::create([
            'title'             => 'Test Content ' . Str::random(5),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $this->playlist = TrainingPlan::create([
            'title'             => 'Target Playlist ' . Str::random(5),
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
        ]);
    }

    public function test_bulk_duplicate_automatically_copies_assessment_results_to_new_assessment(): void
    {
        // 1. Arrange: Source assessment with questions and 3 user results
        $source = Assesment::create([
            'title'         => 'Source Assessment With Results ' . Str::random(5),
            'status'        => 'OK',
            'type'          => 'content',
            'content_id'            => $this->content->id,
            'assasment_content_for' => 'content',
            'passing_grade'         => 80,
            'batch'         => 1,
        ]);

        $q1 = AssesmentQuestion::create([
            'assesment_id' => $source->id,
            'question'     => 'Question 1',
            'option'       => ['A' => 'Correct', 'B' => 'Wrong'],
            'answer'       => 'A',
            'is_used'      => true,
        ]);

        $q2 = AssesmentQuestion::create([
            'assesment_id' => $source->id,
            'question'     => 'Question 2',
            'option'       => ['A' => 'Wrong', 'B' => 'Correct'],
            'answer'       => 'B',
            'is_used'      => true,
        ]);

        // Student A: 100 Pass
        AssesmentResult::create([
            'assesment_id'      => $source->id,
            'user_id'           => $this->studentA->id,
            'score'             => 100,
            'pass'              => 1,
            'final'             => 1,
            'batch'             => 1,
            'user_answer'       => [(string) $q1->id => 'A', (string) $q2->id => 'B'],
            'question_snapshot' => [
                ['question_id' => $q1->id, 'order' => 1],
                ['question_id' => $q2->id, 'order' => 2],
            ],
        ]);

        // Student B: 80 Pass
        AssesmentResult::create([
            'assesment_id'      => $source->id,
            'user_id'           => $this->studentB->id,
            'score'             => 80,
            'pass'              => 1,
            'final'             => 1,
            'batch'             => 1,
            'user_answer'       => [(string) $q1->id => 'A', (string) $q2->id => 'A'],
            'question_snapshot' => [
                ['question_id' => $q1->id, 'order' => 1],
                ['question_id' => $q2->id, 'order' => 2],
            ],
        ]);

        // Student C: 50 Fail
        AssesmentResult::create([
            'assesment_id'      => $source->id,
            'user_id'           => $this->studentC->id,
            'score'             => 50,
            'pass'              => 0,
            'final'             => 1,
            'batch'             => 1,
            'user_answer'       => [(string) $q1->id => 'B', (string) $q2->id => 'A'],
            'question_snapshot' => null,
        ]);

        $this->assertSame(3, AssesmentResult::where('assesment_id', $source->id)->count());

        // 2. Act: Admin executes Bulk Duplicate via HTTP endpoint
        $response = $this->actingAs($this->admin)->postJson(route('assesment.bulk-duplicate'), [
            'target_context'  => 'playlist',
            'trainingplan_id' => $this->playlist->id,
            'source_ids'      => [$source->id],
        ]);

        // 3. Assert HTTP response
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertSame(1, $data['success']);
        $this->assertSame(0, $data['failed']);
        $newAssessmentId = $data['created_items'][0]['new_assessment_id'];

        // 4. Assert Results in New Assessment
        $newResults = AssesmentResult::where('assesment_id', $newAssessmentId)->get();
        $this->assertCount(3, $newResults);

        // Check Student A on new assessment
        $resA = $newResults->firstWhere('user_id', $this->studentA->id);
        $this->assertNotNull($resA);
        $this->assertSame(100, (int) $resA->score);
        $this->assertSame(1, (int) $resA->pass);
        $this->assertSame(1, (int) $resA->final);
        $this->assertSame(1, (int) $resA->batch);

        // Check Student B on new assessment
        $resB = $newResults->firstWhere('user_id', $this->studentB->id);
        $this->assertNotNull($resB);
        $this->assertSame(80, (int) $resB->score);
        $this->assertSame(1, (int) $resB->pass);
        $this->assertSame(1, (int) $resB->final);

        // Check Student C on new assessment
        $resC = $newResults->firstWhere('user_id', $this->studentC->id);
        $this->assertNotNull($resC);
        $this->assertSame(50, (int) $resC->score);
        $this->assertSame(0, (int) $resC->pass);
        $this->assertSame(1, (int) $resC->final);

        // 5. Assert questions and remapped answers on New Assessment
        $newQuestions = AssesmentQuestion::where('assesment_id', $newAssessmentId)->orderBy('id')->get();
        $this->assertCount(2, $newQuestions);
        $newQ1 = $newQuestions[0];
        $newQ2 = $newQuestions[1];

        $answersA = $resA->user_answer;
        $this->assertArrayHasKey((string) $newQ1->id, $answersA);
        $this->assertArrayHasKey((string) $newQ2->id, $answersA);
        $this->assertSame('A', $answersA[(string) $newQ1->id]);
        $this->assertSame('B', $answersA[(string) $newQ2->id]);

        // 6. Assert source assessment results remained completely intact
        $this->assertSame(3, AssesmentResult::where('assesment_id', $source->id)->count());
    }
}
