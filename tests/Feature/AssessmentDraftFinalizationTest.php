<?php

namespace Tests\Feature;

use App\Models\Assesment;
use App\Models\AssesmentQuestion;
use App\Models\AssesmentResult;
use App\Models\User;
use App\Services\AssessmentAccessService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Tests\TestCase;

class AssessmentDraftFinalizationTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private User $admin;
    private AssessmentAccessService $accessService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createTestUser(1, 'Student Tester');
        $this->admin = $this->createTestUser(2, 'Admin Tester');
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

    private function createAssessmentWithQuestions(int $passingGrade = 70, int $durationMinutes = 60): array
    {
        $assessment = Assesment::create([
            'title'               => 'Test Draft Assessment ' . uniqid(),
            'slug'                => 'test-draft-assessment-' . uniqid(),
            'type'                => 'global',
            'passing_grade'       => $passingGrade,
            'status'              => 'OK',
            'batch'               => 1,
            'timer'               => 'y',
            'time_limit_minutes'  => $durationMinutes,
            'has_access_period'   => false,
        ]);

        $q1 = AssesmentQuestion::create([
            'assesment_id' => $assessment->id,
            'question'     => 'Question 1',
            'option'       => ['a' => 'Ans 1', 'b' => 'Ans 2'],
            'answer'       => 'a',
        ]);

        $q2 = AssesmentQuestion::create([
            'assesment_id' => $assessment->id,
            'question'     => 'Question 2',
            'option'       => ['a' => 'Ans 1', 'b' => 'Ans 2'],
            'answer'       => 'b',
        ]);

        return [$assessment, $q1, $q2];
    }

    public function test_active_draft_with_passing_score_does_not_render_as_passed_and_shows_continue_button(): void
    {
        [$assessment, $q1, $q2] = $this->createAssessmentWithQuestions(50, 60);

        // User answered Q1 correctly (score = 50, which >= passing grade 50), Q2 unanswered.
        // It is saved by autosave as final = 0.
        $draft = AssesmentResult::create([
            'user_id'           => $this->user->id,
            'assesment_id'      => $assessment->id,
            'batch'             => 1,
            'user_answer'       => [$q1->id => 'A'],
            'score'             => 50,
            'pass'              => 1,
            'final'             => 0,
            'question_snapshot' => [
                ['question_id' => $q1->id, 'order' => 1],
                ['question_id' => $q2->id, 'order' => 2],
            ],
            'created_at'        => now()->subMinutes(10),
            'updated_at'        => now()->subMinutes(5),
        ]);

        $assessment->load(['scores', 'trainingplan', 'contents']);

        $this->actingAs($this->user);

        $html = Blade::render(
            '<x-learning.assessment.assessment-row :assessment="$assessment" :index="1" />',
            ['assessment' => $assessment]
        );

        // Must NOT show Passed badge
        $this->assertStringNotContainsString('assessment-status-passed', $html);
        $this->assertStringNotContainsString('btn-assessment-passed', $html);

        // MUST show Pending status and Continue action button
        $this->assertStringContainsString('assessment-status-pending', $html);
        $this->assertStringContainsString('btn-assessment-continue', $html);
        $this->assertStringContainsString('Continue', $html);
    }

    public function test_admin_result_page_auto_finalizes_expired_drafts_and_displays_them(): void
    {
        [$assessment, $q1, $q2] = $this->createAssessmentWithQuestions(50, 30);

        // Draft was created 2 hours ago with 30 min duration -> EXPIRED
        $draft = AssesmentResult::create([
            'user_id'           => $this->user->id,
            'assesment_id'      => $assessment->id,
            'batch'             => 1,
            'user_answer'       => [$q1->id => 'a', $q2->id => 'b'],
            'score'             => 100,
            'pass'              => 1,
            'final'             => 0,
            'question_snapshot' => [
                ['question_id' => $q1->id, 'order' => 1],
                ['question_id' => $q2->id, 'order' => 2],
            ],
            'created_at'        => now()->subHours(2),
            'updated_at'        => now()->subHours(2),
        ]);

        // Prior to admin viewing, draft is final = 0
        $this->assertSame(0, (int) $draft->final);

        // Admin opens result page
        $this->actingAs($this->admin);
        $response = $this->get(route('assesment.result', $assessment->slug));
        $response->assertOk();

        // Draft should now be finalized to final = 1
        $draft->refresh();
        $this->assertSame(1, (int) $draft->final);
        $this->assertSame(1, (int) $draft->pass);
        $this->assertEquals(100, (float) $draft->score);

        // Result page contains student's name
        $response->assertSee($this->user->name);
    }

    public function test_user_index_auto_finalizes_expired_draft_and_renders_final_status(): void
    {
        [$assessment, $q1, $q2] = $this->createAssessmentWithQuestions(50, 15);

        // Draft created 45 minutes ago with 15 min duration -> EXPIRED
        $draft = AssesmentResult::create([
            'user_id'           => $this->user->id,
            'assesment_id'      => $assessment->id,
            'batch'             => 1,
            'user_answer'       => [$q1->id => 'a', $q2->id => 'b'],
            'score'             => 100,
            'pass'              => 1,
            'final'             => 0,
            'question_snapshot' => [
                ['question_id' => $q1->id, 'order' => 1],
                ['question_id' => $q2->id, 'order' => 2],
            ],
            'created_at'        => now()->subMinutes(45),
            'updated_at'        => now()->subMinutes(45),
        ]);

        $this->actingAs($this->user);
        $response = $this->get(route('assesment.user.index'));
        $response->assertOk();

        $draft->refresh();
        $this->assertSame(1, (int) $draft->final);
        $this->assertSame(1, (int) $draft->pass);
    }
}
