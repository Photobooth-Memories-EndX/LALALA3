<?php

namespace Tests\Feature;

use App\Models\Assesment;
use App\Models\AssesmentQuestion;
use App\Models\AssesmentResult;
use App\Models\AssesmentScoreHistory;
use App\Models\Certificate;
use App\Models\CertificateSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class AssessmentResultEditScoreTest extends TestCase
{
    use DatabaseTransactions;

    private User $superAdmin;
    private User $admin;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin  = $this->createTestUser(User::ROLE_SUPER_ADMIN, 'Super Admin Tester');
        $this->admin       = $this->createTestUser(User::ROLE_ADMIN, 'Admin Tester');
        $this->regularUser = $this->createTestUser(User::ROLE_USER, 'Regular User Tester');
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

    private function createAssessment(int $passingGrade = 75, int $batch = 1): Assesment
    {
        $assessment = Assesment::create([
            'title'           => 'Assessment Edit Score Test ' . uniqid(),
            'type'            => 'global',
            'status'          => 'OK',
            'passing_grade'   => $passingGrade,
            'batch'           => $batch,
            'total_remedials' => 0,
            'unique_code'     => 'ES' . strtoupper(Str::random(6)),
        ]);

        AssesmentQuestion::create([
            'assesment_id' => $assessment->id,
            'question'     => 'Sample Question 1',
            'option'       => ['a' => 'Ans 1', 'b' => 'Ans 2'],
            'answer'       => 'a',
        ]);

        return $assessment;
    }

    private function makeAssessmentResult(Assesment $assessment, User $user, int $score = 70, int $pass = 0): AssesmentResult
    {
        return AssesmentResult::create([
            'user_id'      => $user->id,
            'assesment_id' => $assessment->id,
            'batch'        => $assessment->batch,
            'final'        => 1,
            'score'        => $score,
            'pass'         => $pass,
            'user_answer'  => ['1' => 'a'],
        ]);
    }

    /**
     * TEST 1: Edit button appears on the Assessment Result page with correct attributes.
     */
    public function test_assessment_result_page_renders_edit_score_button(): void
    {
        $assessment = $this->createAssessment(75);
        $result = $this->makeAssessmentResult($assessment, $this->regularUser, 70, 0);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('assesment.result', $assessment->slug));

        $response->assertStatus(200);
        $response->assertSee('btn-edit-score');
        $response->assertSee('fa-edit');
        $response->assertSee('title="Edit Score"', false);
        $response->assertSee('aria-label="Edit Score"', false);
        $response->assertSee('data-id="' . $result->id . '"', false);
        $response->assertSee('data-score="' . $result->score . '"', false);
        $response->assertSee('data-name="' . $this->regularUser->name . '"', false);
        $response->assertSee('data-id-emp="' . $this->regularUser->id_emp . '"', false);
        $response->assertSee('data-url="' . route('assesment.result.update-score', [$assessment->slug, $result->id]) . '"', false);
    }

    /**
     * TEST 2: Edit score modal markup is included on the page.
     */
    public function test_assessment_result_page_renders_edit_score_modal(): void
    {
        $assessment = $this->createAssessment(75);
        $this->makeAssessmentResult($assessment, $this->regularUser, 70, 0);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('assesment.result', $assessment->slug));

        $response->assertStatus(200);
        $response->assertSee('id="modal-edit-score"', false);
        $response->assertSee('id="form-edit-score"', false);
        $response->assertSee('Edit Score');
        $response->assertSee('id="editScoreParticipantName"', false);
        $response->assertSee('id="editScoreParticipantId"', false);
        $response->assertSee('id="editScoreCurrentScoreBadge"', false);
        $response->assertSee('id="editScoreInput"', false);
        $response->assertSee('Save Score');
    }

    /**
     * TEST 3: Authorized user can successfully update participant score.
     */
    public function test_authorized_user_can_update_score(): void
    {
        $assessment = $this->createAssessment(75);
        $result = $this->makeAssessmentResult($assessment, $this->regularUser, 70, 0);

        $updateUrl = route('assesment.result.update-score', [$assessment->slug, $result->id]);

        $response = $this->actingAs($this->superAdmin)
            ->putJson($updateUrl, [
                'score' => 85,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status'  => 'success',
                'message' => 'Score updated successfully.',
                'data'    => [
                    'id'           => $result->id,
                    'score'        => 85,
                    'pass'         => 1,
                    'result_label' => 'Pass',
                ],
            ]);

        $result->refresh();
        $this->assertEquals(85, $result->score);
        $this->assertEquals(1, $result->pass);
    }

    /**
     * TEST 4: Result (Pass/Failed) status is properly recalculated based on assessment passing_grade.
     */
    public function test_result_recalculation_upon_score_change(): void
    {
        $passingGrade = 75;
        $assessment = $this->createAssessment($passingGrade);
        $result = $this->makeAssessmentResult($assessment, $this->regularUser, 50, 0);

        $updateUrl = route('assesment.result.update-score', [$assessment->slug, $result->id]);

        // 1. Upgrade score to pass: 50 -> 80 (>= 75) => Pass (pass = 1)
        $response = $this->actingAs($this->admin)
            ->putJson($updateUrl, ['score' => 80]);

        $response->assertStatus(200)
            ->assertJsonPath('data.pass', 1)
            ->assertJsonPath('data.result_label', 'Pass');

        $result->refresh();
        $this->assertEquals(80, $result->score);
        $this->assertEquals(1, $result->pass);

        // 2. Downgrade score to fail: 80 -> 60 (< 75) => Failed (pass = 0)
        $response2 = $this->actingAs($this->admin)
            ->putJson($updateUrl, ['score' => 60]);

        $response2->assertStatus(200)
            ->assertJsonPath('data.pass', 0)
            ->assertJsonPath('data.result_label', 'Failed');

        $result->refresh();
        $this->assertEquals(60, $result->score);
        $this->assertEquals(0, $result->pass);

        // 3. Exactly at passing grade: 60 -> 75 (== 75) => Pass (pass = 1)
        $response3 = $this->actingAs($this->admin)
            ->putJson($updateUrl, ['score' => 75]);

        $response3->assertStatus(200)
            ->assertJsonPath('data.pass', 1)
            ->assertJsonPath('data.result_label', 'Pass');

        $result->refresh();
        $this->assertEquals(75, $result->score);
        $this->assertEquals(1, $result->pass);
    }

    /**
     * TEST 5: Invalid scores are rejected (empty, negative, > 100, non-numeric).
     */
    public function test_invalid_score_inputs_are_rejected(): void
    {
        $assessment = $this->createAssessment(75);
        $result = $this->makeAssessmentResult($assessment, $this->regularUser, 70, 0);
        $updateUrl = route('assesment.result.update-score', [$assessment->slug, $result->id]);

        // Negative score
        $responseNeg = $this->actingAs($this->superAdmin)->putJson($updateUrl, ['score' => -5]);
        $responseNeg->assertStatus(422)->assertJsonValidationErrors('score');

        // Score above maximum (100)
        $responseOver = $this->actingAs($this->superAdmin)->putJson($updateUrl, ['score' => 105]);
        $responseOver->assertStatus(422)->assertJsonValidationErrors('score');

        // Non-numeric score
        $responseStr = $this->actingAs($this->superAdmin)->putJson($updateUrl, ['score' => 'invalid_score']);
        $responseStr->assertStatus(422)->assertJsonValidationErrors('score');

        // Empty score
        $responseEmpty = $this->actingAs($this->superAdmin)->putJson($updateUrl, ['score' => '']);
        $responseEmpty->assertStatus(422)->assertJsonValidationErrors('score');

        // Database value remains unchanged
        $result->refresh();
        $this->assertEquals(70, $result->score);
    }

    /**
     * TEST 6: Wrong assessment result cannot be modified through another assessment's endpoint.
     */
    public function test_result_from_another_assessment_cannot_be_modified(): void
    {
        $assessment1 = $this->createAssessment(75);
        $assessment2 = $this->createAssessment(75);

        $resultOfAssessment2 = $this->makeAssessmentResult($assessment2, $this->regularUser, 60, 0);

        // Attempt to update result 2 using assessment 1 slug
        $wrongUrl = route('assesment.result.update-score', [$assessment1->slug, $resultOfAssessment2->id]);

        $response = $this->actingAs($this->superAdmin)->putJson($wrongUrl, ['score' => 95]);

        $response->assertStatus(404);

        $resultOfAssessment2->refresh();
        $this->assertEquals(60, $resultOfAssessment2->score);
    }

    /**
     * TEST 7: Unauthorized regular user cannot modify assessment score.
     */
    public function test_unauthorized_user_cannot_modify_score(): void
    {
        $assessment = $this->createAssessment(75);
        $result = $this->makeAssessmentResult($assessment, $this->regularUser, 70, 0);
        $updateUrl = route('assesment.result.update-score', [$assessment->slug, $result->id]);

        $response = $this->actingAs($this->regularUser)->putJson($updateUrl, ['score' => 90]);

        $response->assertStatus(403);

        $result->refresh();
        $this->assertEquals(70, $result->score);
    }

    /**
     * TEST 8: Participant isolation: editing participant A's score does not change participant B's score.
     */
    public function test_participant_isolation_during_score_update(): void
    {
        $assessment = $this->createAssessment(75);
        $userA = $this->createTestUser(User::ROLE_USER, 'Participant A');
        $userB = $this->createTestUser(User::ROLE_USER, 'Participant B');

        $resultA = $this->makeAssessmentResult($assessment, $userA, 50, 0);
        $resultB = $this->makeAssessmentResult($assessment, $userB, 70, 0);

        $updateUrlA = route('assesment.result.update-score', [$assessment->slug, $resultA->id]);

        $response = $this->actingAs($this->superAdmin)->putJson($updateUrlA, ['score' => 90]);
        $response->assertStatus(200);

        $resultA->refresh();
        $resultB->refresh();

        $this->assertEquals(90, $resultA->score);
        $this->assertEquals(1, $resultA->pass);

        $this->assertEquals(70, $resultB->score);
        $this->assertEquals(0, $resultB->pass);
    }

    /**
     * TEST 9: Preserves user answers snapshot during score edit (answers are NOT broken or erased).
     */
    public function test_user_answers_preserved_when_score_is_updated(): void
    {
        $assessment = $this->createAssessment(75);
        $result = AssesmentResult::create([
            'user_id'      => $this->regularUser->id,
            'assesment_id' => $assessment->id,
            'batch'        => $assessment->batch,
            'final'        => 1,
            'score'        => 50,
            'pass'         => 0,
            'user_answer'  => ['1' => 'a', '2' => 'b'],
            'question_snapshot' => [
                ['question_id' => 1, 'order' => 1],
                ['question_id' => 2, 'order' => 2],
            ],
        ]);

        $updateUrl = route('assesment.result.update-score', [$assessment->slug, $result->id]);

        $response = $this->actingAs($this->superAdmin)->putJson($updateUrl, ['score' => 88]);
        $response->assertStatus(200);

        $result->refresh();
        $this->assertEquals(88, $result->score);
        $this->assertEquals(['1' => 'a', '2' => 'b'], $result->user_answer);
        $this->assertNotEmpty($result->question_snapshot);
    }

    /**
     * TEST 10: Score change synchronizes linked Certificate score if exists.
     */
    public function test_score_change_synchronizes_linked_certificate(): void
    {
        $assessment = $this->createAssessment(75);
        $result = $this->makeAssessmentResult($assessment, $this->regularUser, 60, 0);

        $setting = CertificateSetting::create([
            'title'               => 'Setting ' . uniqid(),
            'certificate_type'    => 'assessment',
            'assessment_id'       => $assessment->id,
            'certificate_code'    => 'CERT-' . strtoupper(Str::random(6)),
            'issuer_name'         => 'Test Issuer',
            'status'              => 'active',
        ]);

        $cert = Certificate::create([
            'user_id'                => $this->regularUser->id,
            'certificate_setting_id' => $setting->id,
            'certificate_type'       => 'assessment',
            'source_id'              => $assessment->id,
            'assessment_result_id'   => $result->id,
            'certificate_number'     => 'CN-' . uniqid(),
            'credential_id'          => 'CRED-' . uniqid(),
            'title'                  => 'Test Cert',
            'participant_name'       => $this->regularUser->name,
            'score'                  => $result->score,
            'issued_at'              => now(),
        ]);

        $updateUrl = route('assesment.result.update-score', [$assessment->slug, $result->id]);
        $response = $this->actingAs($this->superAdmin)->putJson($updateUrl, ['score' => 92]);
        $response->assertStatus(200);

        $cert->refresh();
        $this->assertEquals(92, $cert->score);
    }

    /**
     * TEST 11: Score edit creates a persistent score history record in assesment_score_histories.
     */
    public function test_score_edit_creates_persistent_history_record(): void
    {
        $assessment = $this->createAssessment(75);
        $result = $this->makeAssessmentResult($assessment, $this->regularUser, 74, 0);

        $updateUrl = route('assesment.result.update-score', [$assessment->slug, $result->id]);

        $response = $this->actingAs($this->admin)->putJson($updateUrl, ['score' => 85]);
        $response->assertStatus(200);

        $this->assertDatabaseHas('assesment_score_histories', [
            'assesment_result_id' => $result->id,
            'assesment_id'        => $assessment->id,
            'user_id'             => $this->regularUser->id,
            'changed_by_user_id'  => $this->admin->id,
            'previous_score'      => 74,
            'new_score'           => 85,
        ]);

        $history = AssesmentScoreHistory::where('assesment_result_id', $result->id)->first();
        $this->assertNotNull($history);
        $this->assertEquals('85', (string) $history->new_score);
        $this->assertEquals('74', (string) $history->previous_score);
        $this->assertEquals($this->admin->id, $history->changed_by_user_id);
        $this->assertEquals($this->admin->name, $history->changedBy->name);
    }

    /**
     * TEST 12: Multiple sequential score edits append new history records and never overwrite old ones.
     */
    public function test_multiple_score_edits_append_immutable_history(): void
    {
        $assessment = $this->createAssessment(75);
        $result = $this->makeAssessmentResult($assessment, $this->regularUser, 74, 0);
        $updateUrl = route('assesment.result.update-score', [$assessment->slug, $result->id]);

        // Edit 1: 74 -> 85 by Admin
        $this->actingAs($this->admin)->putJson($updateUrl, ['score' => 85])->assertStatus(200);

        // Edit 2: 85 -> 90 by Super Admin
        $this->actingAs($this->superAdmin)->putJson($updateUrl, ['score' => 90])->assertStatus(200);

        // Edit 3: 90 -> 60 by Admin
        $this->actingAs($this->admin)->putJson($updateUrl, ['score' => 60])->assertStatus(200);

        // Verify total histories = 3
        $histories = AssesmentScoreHistory::where('assesment_result_id', $result->id)
            ->orderBy('id', 'asc')
            ->get();

        $this->assertCount(3, $histories);

        // Record 1: 74 -> 85 (Admin)
        $this->assertEquals(74, $histories[0]->previous_score);
        $this->assertEquals(85, $histories[0]->new_score);
        $this->assertEquals($this->admin->id, $histories[0]->changed_by_user_id);

        // Record 2: 85 -> 90 (Super Admin)
        $this->assertEquals(85, $histories[1]->previous_score);
        $this->assertEquals(90, $histories[1]->new_score);
        $this->assertEquals($this->superAdmin->id, $histories[1]->changed_by_user_id);

        // Record 3: 90 -> 60 (Admin)
        $this->assertEquals(90, $histories[2]->previous_score);
        $this->assertEquals(60, $histories[2]->new_score);
        $this->assertEquals($this->admin->id, $histories[2]->changed_by_user_id);

        // Current score in assessment_results must be the latest score (60)
        $result->refresh();
        $this->assertEquals(60, $result->score);
        $this->assertEquals(0, $result->pass);
    }

    /**
     * TEST 13: Submitting identical score (no-op) does NOT create a redundant history record.
     */
    public function test_no_op_score_edit_does_not_create_history(): void
    {
        $assessment = $this->createAssessment(75);
        $result = $this->makeAssessmentResult($assessment, $this->regularUser, 80, 1);
        $updateUrl = route('assesment.result.update-score', [$assessment->slug, $result->id]);

        $response = $this->actingAs($this->admin)->putJson($updateUrl, ['score' => 80]);
        $response->assertStatus(200)
            ->assertJsonPath('data.is_changed', false);

        $this->assertEquals(0, AssesmentScoreHistory::where('assesment_result_id', $result->id)->count());
    }

    /**
     * TEST 14: Assessment Result page uses score number itself as history trigger and embeds score history template table.
     */
    public function test_assessment_result_page_displays_history_trigger_on_score_badge(): void
    {
        $assessment = $this->createAssessment(75);
        $result = $this->makeAssessmentResult($assessment, $this->regularUser, 85, 1);

        AssesmentScoreHistory::create([
            'assesment_result_id' => $result->id,
            'assesment_id'        => $assessment->id,
            'user_id'             => $this->regularUser->id,
            'changed_by_user_id'  => $this->admin->id,
            'previous_score'      => 74,
            'new_score'           => 85,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('assesment.result', $assessment->slug));

        $response->assertStatus(200);
        // Score number is displayed
        $response->assertSee('85');
        // Score badge is the interactive trigger with template
        $response->assertSee('score-value score-history-trigger has-history', false);
        $response->assertSee('score-history-popover-template', false);
        $response->assertSee('score-history-table', false);
        $response->assertSee($this->admin->name);
        // Standalone history icon must NOT be rendered
        $response->assertDontSee('fa-history');
    }

    /**
     * TEST 15: Score History popover renders as a table with columns No, Score, Changed By, Date and sequential numbering 1, 2, 3...
     */
    public function test_score_history_popover_renders_as_sequential_table(): void
    {
        $assessment = $this->createAssessment(75);
        $result = $this->makeAssessmentResult($assessment, $this->regularUser, 85, 1);

        // Create 3 history records with simulated timestamps
        $h1 = AssesmentScoreHistory::create([
            'assesment_result_id' => $result->id,
            'assesment_id'        => $assessment->id,
            'user_id'             => $this->regularUser->id,
            'changed_by_user_id'  => $this->admin->id,
            'previous_score'      => 72,
            'new_score'           => 90,
            'created_at'          => now()->subMinutes(10),
        ]);

        $h2 = AssesmentScoreHistory::create([
            'assesment_result_id' => $result->id,
            'assesment_id'        => $assessment->id,
            'user_id'             => $this->regularUser->id,
            'changed_by_user_id'  => $this->admin->id,
            'previous_score'      => 90,
            'new_score'           => 60,
            'created_at'          => now()->subMinutes(5),
        ]);

        $h3 = AssesmentScoreHistory::create([
            'assesment_result_id' => $result->id,
            'assesment_id'        => $assessment->id,
            'user_id'             => $this->regularUser->id,
            'changed_by_user_id'  => $this->superAdmin->id,
            'previous_score'      => 60,
            'new_score'           => 85,
            'created_at'          => now(),
        ]);

        // Render blade view partial directly
        $renderedHtml = view('main.assesment.partials.score-history-popover', [
            'histories' => $result->scoreHistories,
        ])->render();

        // 1. Table structure & headers
        $this->assertStringContainsString('score-history-table', $renderedHtml);
        $this->assertStringContainsString('No', $renderedHtml);
        $this->assertStringContainsString('Score', $renderedHtml);
        $this->assertStringContainsString('Changed By', $renderedHtml);
        $this->assertStringContainsString('Date', $renderedHtml);

        // 2. Sequential numbering 1, 2, 3 (newest first)
        $this->assertStringContainsString('>1<', $renderedHtml);
        $this->assertStringContainsString('>2<', $renderedHtml);
        $this->assertStringContainsString('>3<', $renderedHtml);

        // 3. Newest is #1 (60 → 85), followed by #2 (90 → 60), followed by #3 (72 → 90)
        $pos1 = strpos($renderedHtml, '60');
        $pos2 = strpos($renderedHtml, '90');
        $pos3 = strpos($renderedHtml, '72');
        $this->assertTrue($pos1 < $pos3, 'Newest change (60 -> 85) must appear before oldest (72 -> 90)');

        // 4. Old # prefix format (#1, #2, #3) is removed
        $this->assertStringNotContainsString('#1', $renderedHtml);
        $this->assertStringNotContainsString('#2', $renderedHtml);
        $this->assertStringNotContainsString('#3', $renderedHtml);
    }

    /**
     * TEST 16: Full end-to-end test verifying assessment result page delivers history table to DOM with proper isolation.
     */
    public function test_assessment_result_page_eager_loads_and_renders_full_history_table(): void
    {
        $assessment = $this->createAssessment(75);
        $userA = $this->createTestUser(User::ROLE_USER, 'Participant A');
        $userB = $this->createTestUser(User::ROLE_USER, 'Participant B');

        $resultA = $this->makeAssessmentResult($assessment, $userA, 70, 0);
        $resultB = $this->makeAssessmentResult($assessment, $userB, 85, 1);

        // Create history records for Result A
        AssesmentScoreHistory::create([
            'assesment_result_id' => $resultA->id,
            'assesment_id'        => $assessment->id,
            'user_id'             => $userA->id,
            'changed_by_user_id'  => $this->admin->id,
            'previous_score'      => 72,
            'new_score'           => 90,
            'created_at'          => now()->subMinutes(10),
        ]);
        AssesmentScoreHistory::create([
            'assesment_result_id' => $resultA->id,
            'assesment_id'        => $assessment->id,
            'user_id'             => $userA->id,
            'changed_by_user_id'  => $this->admin->id,
            'previous_score'      => 90,
            'new_score'           => 60,
            'created_at'          => now()->subMinutes(5),
        ]);
        AssesmentScoreHistory::create([
            'assesment_result_id' => $resultA->id,
            'assesment_id'        => $assessment->id,
            'user_id'             => $userA->id,
            'changed_by_user_id'  => $this->superAdmin->id,
            'previous_score'      => 60,
            'new_score'           => 70,
            'created_at'          => now(),
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('assesment.result', $assessment->slug));

        $response->assertStatus(200);

        // Result A has history
        $response->assertSee('data-target-template="#score-history-tpl-' . $resultA->id . '"', false);
        $response->assertSee('id="score-history-tpl-' . $resultA->id . '"', false);
        $response->assertSee('score-history-popover-template', false);
        $response->assertSee('score-history-table', false);
        $response->assertSee($this->admin->name);
        $response->assertSee($this->superAdmin->name);

        // Result A has sequential table row 1, 2, 3
        $response->assertSee('>1<', false);
        $response->assertSee('>2<', false);
        $response->assertSee('>3<', false);

        // Result B (no history) is clean
        $response->assertDontSee('data-target-template="#score-history-tpl-' . $resultB->id . '"', false);
    }
}
