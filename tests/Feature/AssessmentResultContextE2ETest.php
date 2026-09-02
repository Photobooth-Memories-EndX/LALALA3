<?php

namespace Tests\Feature;

use App\Models\Assesment;
use App\Models\AssesmentQuestion;
use App\Models\AssesmentResult;
use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use App\Services\AssessmentAccessService;
use App\Services\CertificateEligibilityService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class AssessmentResultContextE2ETest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected User $admin;
    protected Classification $classification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'team'       => 'QA Test Team',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-e2e-u-' . Str::uuid(),
            'sap_code'   => 'SAP-E2E-U-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->admin = User::factory()->create([
            'team'       => 'Admin Team',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-e2e-adm-' . Str::uuid(),
            'sap_code'   => 'SAP-E2E-ADM-' . rand(1000, 9999),
            'role'       => 2,
            'active'     => 1,
        ]);

        $this->classification = Classification::firstOrCreate(
            ['classification_name' => 'E2E Assessment Result Test Classification'],
            ['slug' => 'e2e-assessment-result-test-classification']
        );
    }

    private function createContent(string $prefix = 'e2e'): Content
    {
        return Content::create([
            'title'             => 'Content ' . $prefix . ' ' . rand(1000, 9999),
            'slug'              => 'content-' . $prefix . '-' . rand(10000, 99999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);
    }

    private function createPlaylist(string $name = 'Playlist'): TrainingPlan
    {
        return TrainingPlan::create([
            'title'  => $name . ' ' . rand(1000, 9999),
            'slug'   => 'tp-' . strtolower(str_replace(' ', '-', $name)) . '-' . rand(10000, 99999),
            'active' => 'Active',
        ]);
    }

    private function createAssessment(array $attributes): Assesment
    {
        $defaults = [
            'title'           => 'Assessment ' . rand(1000, 9999),
            'slug'            => 'ass-' . rand(10000, 99999),
            'status'          => 'OK',
            'passing_grade'   => 80,
            'batch'           => 1,
            'total_remedials' => 2,
        ];

        $assessment = Assesment::create(array_merge($defaults, $attributes));

        // Create a question so submission can calculate score
        AssesmentQuestion::create([
            'assesment_id' => $assessment->id,
            'question'     => 'What is 1+1?',
            'option'       => ['A' => '1', 'B' => '2', 'C' => '3'],
            'answer'       => 'B',
            'status'       => '1',
            'is_used'      => true,
        ]);

        return $assessment;
    }

    /**
     * TEST 1: Direct Content Result Isolation
     */
    public function test_scenario_1_direct_content_result_isolation(): void
    {
        $content = $this->createContent('direct');
        $assessmentDirect = $this->createAssessment([
            'type'            => 'content',
            'content_for'     => 'content',
            'content_id'      => $content->id,
            'trainingplan_id' => null,
        ]);

        // Grant progress on Direct Content
        ContentUser::create([
            'user_id'         => $this->user->id,
            'content_id'      => $content->id,
            'progress'        => 100,
            'finish'          => '1',
            'learning_status' => 'finished',
        ]);

        // 1. Open view page (creates draft)
        $viewRes = $this->actingAs($this->user)->get(route('assesment.user.view', $assessmentDirect->slug));
        $viewRes->assertStatus(200);

        // Verify draft was created with assesment_id = assessmentDirect->id
        $draft = AssesmentResult::where('user_id', $this->user->id)
            ->where('assesment_id', $assessmentDirect->id)
            ->where('final', 0)
            ->first();
        $this->assertNotNull($draft);
        $this->assertSame((int) $assessmentDirect->id, (int) $draft->assesment_id);

        // 2. Submit assessment
        $question = $assessmentDirect->questions->first();
        $storeRes = $this->actingAs($this->user)->post(route('assesment.user.store', $assessmentDirect->slug), [
            'submission_token'     => Str::uuid()->toString(),
            (string) $question->id => 'B',
        ]);
        $storeRes->assertRedirect();

        // 3. Verify submitted result
        $finalResult = AssesmentResult::where('user_id', $this->user->id)
            ->where('assesment_id', $assessmentDirect->id)
            ->where('final', 1)
            ->first();
        $this->assertNotNull($finalResult);
        $this->assertSame((int) $assessmentDirect->id, (int) $finalResult->assesment_id);
        $this->assertEquals(100, (float) $finalResult->score);
        $this->assertSame(1, (int) $finalResult->pass);

        // 4. View Direct Content page: shows assessment and result score
        $contentRes = $this->actingAs($this->user)->get(route('content.user.view', $content->slug));
        $contentRes->assertStatus(200);
        $this->assertSame((int) $assessmentDirect->id, (int) $contentRes->viewData('assesment')->id);
        $this->assertEquals(100, (float) $contentRes->viewData('assessmentLatestScores'));
        $this->assertSame(1, (int) $contentRes->viewData('assessmentCount'));
    }

    /**
     * TEST 2 & 3: Playlist A and Playlist B Result Isolation with Same Content
     */
    public function test_scenario_2_and_3_playlist_a_and_b_result_isolation(): void
    {
        $content = $this->createContent('shared');
        $playlistA = $this->createPlaylist('Playlist A');
        $playlistB = $this->createPlaylist('Playlist B');

        TrainingPlanDetail::create(['trainingplan_id' => $playlistA->id, 'content_id' => $content->id, 'content_order' => '1']);
        TrainingPlanDetail::create(['trainingplan_id' => $playlistB->id, 'content_id' => $content->id, 'content_order' => '1']);

        $assPlaylistA = $this->createAssessment([
            'type'            => 'content',
            'content_for'     => 'playlist',
            'content_id'      => $content->id,
            'trainingplan_id' => $playlistA->id,
        ]);

        $assPlaylistB = $this->createAssessment([
            'type'            => 'content',
            'content_for'     => 'playlist',
            'content_id'      => $content->id,
            'trainingplan_id' => $playlistB->id,
        ]);

        // Set playlist progress for user
        PlaylistContentUser::create([
            'user_id'         => $this->user->id,
            'trainingplan_id' => $playlistA->id,
            'content_id'      => $content->id,
            'progress'        => 100,
            'finish'          => '1',
            'learning_status' => 'finished',
        ]);

        PlaylistContentUser::create([
            'user_id'         => $this->user->id,
            'trainingplan_id' => $playlistB->id,
            'content_id'      => $content->id,
            'progress'        => 100,
            'finish'          => '1',
            'learning_status' => 'finished',
        ]);

        // Submit Playlist A assessment (score 100)
        $qA = $assPlaylistA->questions->first();
        $this->actingAs($this->user)->post(route('assesment.user.store', $assPlaylistA->slug), [
            'submission_token' => Str::uuid()->toString(),
            (string) $qA->id   => 'B',
        ]);

        // Submit Playlist B assessment with wrong answer (score 0)
        $qB = $assPlaylistB->questions->first();
        $this->actingAs($this->user)->post(route('assesment.user.store', $assPlaylistB->slug), [
            'submission_token' => Str::uuid()->toString(),
            (string) $qB->id   => 'A',
        ]);

        // Verify results in database
        $resultA = AssesmentResult::where('user_id', $this->user->id)
            ->where('assesment_id', $assPlaylistA->id)
            ->first();
        $resultB = AssesmentResult::where('user_id', $this->user->id)
            ->where('assesment_id', $assPlaylistB->id)
            ->first();

        $this->assertNotNull($resultA);
        $this->assertNotNull($resultB);
        $this->assertNotEquals($resultA->id, $resultB->id);
        $this->assertSame((int) $assPlaylistA->id, (int) $resultA->assesment_id);
        $this->assertSame((int) $assPlaylistB->id, (int) $resultB->assesment_id);
        $this->assertEquals(100, (float) $resultA->score);
        $this->assertEquals(0, (float) $resultB->score);

        // In Playlist A view: latest score is 100
        $viewPlA = $this->actingAs($this->user)->get(route('playlist.user.view', [$playlistA->slug, $content->slug]));
        $viewPlA->assertStatus(200);
        $this->assertSame((int) $assPlaylistA->id, (int) $viewPlA->viewData('assessmentContent')->id);
        $this->assertEquals(100, (float) $viewPlA->viewData('assessmentContentLatestScores'));

        // In Playlist B view: latest score is 0
        $viewPlB = $this->actingAs($this->user)->get(route('playlist.user.view', [$playlistB->slug, $content->slug]));
        $viewPlB->assertStatus(200);
        $this->assertSame((int) $assPlaylistB->id, (int) $viewPlB->viewData('assessmentContent')->id);
        $this->assertEquals(0, (float) $viewPlB->viewData('assessmentContentLatestScores'));
    }

    /**
     * TEST 4: Same User has Direct, Playlist A, and Playlist B results simultaneously without collision
     */
    public function test_same_user_can_have_three_distinct_results_without_collision(): void
    {
        $content = $this->createContent('triple');
        $playlistA = $this->createPlaylist('Triple A');
        $playlistB = $this->createPlaylist('Triple B');

        $assDirect = $this->createAssessment([
            'type'            => 'content',
            'content_for'     => 'content',
            'content_id'      => $content->id,
            'trainingplan_id' => null,
        ]);

        $assPlA = $this->createAssessment([
            'type'            => 'content',
            'content_for'     => 'playlist',
            'content_id'      => $content->id,
            'trainingplan_id' => $playlistA->id,
        ]);

        $assPlB = $this->createAssessment([
            'type'            => 'content',
            'content_for'     => 'playlist',
            'content_id'      => $content->id,
            'trainingplan_id' => $playlistB->id,
        ]);

        // Create explicit results for same user
        AssesmentResult::create([
            'user_id'      => $this->user->id,
            'assesment_id' => $assDirect->id,
            'batch'        => '1',
            'score'        => 65,
            'pass'         => 0,
            'final'        => 1,
            'user_answer'  => [],
        ]);

        AssesmentResult::create([
            'user_id'      => $this->user->id,
            'assesment_id' => $assPlA->id,
            'batch'        => '1',
            'score'        => 85,
            'pass'         => 1,
            'final'        => 1,
            'user_answer'  => [],
        ]);

        AssesmentResult::create([
            'user_id'      => $this->user->id,
            'assesment_id' => $assPlB->id,
            'batch'        => '1',
            'score'        => 95,
            'pass'         => 1,
            'final'        => 1,
            'user_answer'  => [],
        ]);

        // Total results for this user across these 3 assessments is exactly 3
        $userResults = AssesmentResult::where('user_id', $this->user->id)
            ->whereIn('assesment_id', [$assDirect->id, $assPlA->id, $assPlB->id])
            ->get();
        $this->assertCount(3, $userResults);

        // Verify isolation by assesment_id
        $this->assertEquals(65, (float) AssesmentResult::where('user_id', $this->user->id)->where('assesment_id', $assDirect->id)->first()->score);
        $this->assertEquals(85, (float) AssesmentResult::where('user_id', $this->user->id)->where('assesment_id', $assPlA->id)->first()->score);
        $this->assertEquals(95, (float) AssesmentResult::where('user_id', $this->user->id)->where('assesment_id', $assPlB->id)->first()->score);
    }

    /**
     * TEST 5: Draft Isolation between Direct, Playlist A, and Playlist B
     */
    public function test_draft_isolation_between_direct_playlist_a_and_playlist_b(): void
    {
        $content = $this->createContent('draft');
        $playlistA = $this->createPlaylist('Draft Pl A');
        $playlistB = $this->createPlaylist('Draft Pl B');

        $assDirect = $this->createAssessment(['type' => 'content', 'content_for' => 'content', 'content_id' => $content->id]);
        $assPlA    = $this->createAssessment(['type' => 'content', 'content_for' => 'playlist', 'content_id' => $content->id, 'trainingplan_id' => $playlistA->id]);
        $assPlB    = $this->createAssessment(['type' => 'content', 'content_for' => 'playlist', 'content_id' => $content->id, 'trainingplan_id' => $playlistB->id]);

        // Progress
        ContentUser::create(['user_id' => $this->user->id, 'content_id' => $content->id, 'progress' => 100, 'finish' => '1', 'learning_status' => 'finished']);
        PlaylistContentUser::create(['user_id' => $this->user->id, 'trainingplan_id' => $playlistA->id, 'content_id' => $content->id, 'progress' => 100, 'finish' => '1', 'learning_status' => 'finished']);
        PlaylistContentUser::create(['user_id' => $this->user->id, 'trainingplan_id' => $playlistB->id, 'content_id' => $content->id, 'progress' => 100, 'finish' => '1', 'learning_status' => 'finished']);

        // Open all 3 views
        $this->actingAs($this->user)->get(route('assesment.user.view', $assDirect->slug));
        $this->actingAs($this->user)->get(route('assesment.user.view', $assPlA->slug));
        $this->actingAs($this->user)->get(route('assesment.user.view', $assPlB->slug));

        $draftDirect = AssesmentResult::where('user_id', $this->user->id)->where('assesment_id', $assDirect->id)->where('final', 0)->first();
        $draftPlA    = AssesmentResult::where('user_id', $this->user->id)->where('assesment_id', $assPlA->id)->where('final', 0)->first();
        $draftPlB    = AssesmentResult::where('user_id', $this->user->id)->where('assesment_id', $assPlB->id)->where('final', 0)->first();

        $this->assertNotNull($draftDirect);
        $this->assertNotNull($draftPlA);
        $this->assertNotNull($draftPlB);

        // Three completely distinct drafts
        $this->assertNotEquals($draftDirect->id, $draftPlA->id);
        $this->assertNotEquals($draftPlA->id, $draftPlB->id);
        $this->assertNotEquals($draftDirect->id, $draftPlB->id);
    }

    /**
     * TEST 6: Submit Isolation uses correct draft and assessment
     */
    public function test_submit_isolation_uses_correct_draft_and_assessment(): void
    {
        $content = $this->createContent('subm');
        $playlistA = $this->createPlaylist('Subm Pl A');

        $assDirect = $this->createAssessment(['type' => 'content', 'content_for' => 'content', 'content_id' => $content->id]);
        $assPlA    = $this->createAssessment(['type' => 'content', 'content_for' => 'playlist', 'content_id' => $content->id, 'trainingplan_id' => $playlistA->id]);

        ContentUser::create(['user_id' => $this->user->id, 'content_id' => $content->id, 'progress' => 100, 'finish' => '1', 'learning_status' => 'finished']);
        PlaylistContentUser::create(['user_id' => $this->user->id, 'trainingplan_id' => $playlistA->id, 'content_id' => $content->id, 'progress' => 100, 'finish' => '1', 'learning_status' => 'finished']);

        // Create drafts for both
        $this->actingAs($this->user)->get(route('assesment.user.view', $assDirect->slug));
        $this->actingAs($this->user)->get(route('assesment.user.view', $assPlA->slug));

        // Submit only Playlist A
        $qA = $assPlA->questions->first();
        $this->actingAs($this->user)->post(route('assesment.user.store', $assPlA->slug), [
            'submission_token' => Str::uuid()->toString(),
            (string) $qA->id   => 'B',
        ]);

        // Playlist A draft is now final = 1
        $this->assertSame(1, (int) AssesmentResult::where('user_id', $this->user->id)->where('assesment_id', $assPlA->id)->first()->final);

        // Direct draft remains final = 0
        $this->assertSame(0, (int) AssesmentResult::where('user_id', $this->user->id)->where('assesment_id', $assDirect->id)->first()->final);
    }

    /**
     * TEST 7: Remedial Attempt History is isolated per assessment
     */
    public function test_remedial_attempt_history_is_isolated_per_assessment(): void
    {
        $content = $this->createContent('remedial');
        $playlistA = $this->createPlaylist('Remedial Pl A');

        $assDirect = $this->createAssessment(['type' => 'content', 'content_for' => 'content', 'content_id' => $content->id, 'total_remedials' => 3]);
        $assPlA    = $this->createAssessment(['type' => 'content', 'content_for' => 'playlist', 'content_id' => $content->id, 'trainingplan_id' => $playlistA->id, 'total_remedials' => 3]);

        // Add 2 failed attempts to Playlist A
        AssesmentResult::create([
            'user_id'      => $this->user->id,
            'assesment_id' => $assPlA->id,
            'batch'        => '1',
            'score'        => 40,
            'pass'         => 0,
            'final'        => 1,
            'user_answer'  => [],
        ]);

        AssesmentResult::create([
            'user_id'      => $this->user->id,
            'assesment_id' => $assPlA->id,
            'batch'        => '1',
            'score'        => 60,
            'pass'         => 0,
            'final'        => 1,
            'user_answer'  => [],
        ]);

        // Playlist A attempt count is 2
        $attemptsPlA = AssesmentResult::where('user_id', $this->user->id)->where('assesment_id', $assPlA->id)->count();
        $this->assertSame(2, $attemptsPlA);

        // Direct attempt count is strictly 0
        $attemptsDirect = AssesmentResult::where('user_id', $this->user->id)->where('assesment_id', $assDirect->id)->count();
        $this->assertSame(0, $attemptsDirect);
    }

    /**
     * TEST 8: Progress Source Isolation (Direct Content vs Playlist Content)
     */
    public function test_progress_source_isolation_direct_vs_playlist(): void
    {
        $content = $this->createContent('prog');
        $playlistA = $this->createPlaylist('Prog Pl A');

        $assDirect = $this->createAssessment(['type' => 'content', 'content_for' => 'content', 'content_id' => $content->id]);
        $assPlA    = $this->createAssessment(['type' => 'content', 'content_for' => 'playlist', 'content_id' => $content->id, 'trainingplan_id' => $playlistA->id]);

        // User has 100% on direct content_users, but 0% on playlist_content_users
        ContentUser::create(['user_id' => $this->user->id, 'content_id' => $content->id, 'progress' => 100, 'finish' => '1', 'learning_status' => 'finished']);
        PlaylistContentUser::create(['user_id' => $this->user->id, 'trainingplan_id' => $playlistA->id, 'content_id' => $content->id, 'progress' => 0, 'finish' => '0', 'learning_status' => 'not_started']);

        $accessService = app(AssessmentAccessService::class);

        // Direct: progress requirement is met
        $reasonDirect = $accessService->denialReason($assDirect, $this->user);
        $this->assertNull($reasonDirect);

        // Playlist A: progress requirement is UNMET because playlist_content_user is 0%
        $reasonPlA = $accessService->denialReason($assPlA, $this->user);
        $this->assertSame(AssessmentAccessService::REASON_PROGRESS, $reasonPlA);

        // Now finish playlist content
        PlaylistContentUser::where('user_id', $this->user->id)
            ->where('trainingplan_id', $playlistA->id)
            ->where('content_id', $content->id)
            ->update(['progress' => 100, 'finish' => '1', 'learning_status' => 'finished']);

        // Now Playlist A: progress requirement is met
        $reasonPlAAfter = $accessService->denialReason($assPlA, $this->user);
        $this->assertNull($reasonPlAAfter);
    }

    /**
     * TEST 9: Result Page Isolation
     */
    public function test_result_page_shows_correct_result_per_assessment(): void
    {
        $content = $this->createContent('respag');
        $playlistA = $this->createPlaylist('Res Pag Pl A');

        $assDirect = $this->createAssessment(['title' => 'Ass Direct Res', 'type' => 'content', 'content_for' => 'content', 'content_id' => $content->id]);
        $assPlA    = $this->createAssessment(['title' => 'Ass Pl A Res', 'type' => 'content', 'content_for' => 'playlist', 'content_id' => $content->id, 'trainingplan_id' => $playlistA->id]);

        $resDirect = AssesmentResult::create([
            'user_id'      => $this->user->id,
            'assesment_id' => $assDirect->id,
            'batch'        => '1',
            'score'        => 77,
            'pass'         => 0,
            'final'        => 1,
            'user_answer'  => [],
        ]);

        $resPlA = AssesmentResult::create([
            'user_id'      => $this->user->id,
            'assesment_id' => $assPlA->id,
            'batch'        => '1',
            'score'        => 88,
            'pass'         => 1,
            'final'        => 1,
            'user_answer'  => [],
        ]);

        // 1. View Direct Result page
        $resPageDirect = $this->actingAs($this->user)->get(route('assesment.user.result', $assDirect->slug));
        $resPageDirect->assertStatus(200);
        $this->assertSame((int) $resDirect->id, (int) $resPageDirect->viewData('assesment_result')->id);
        $this->assertEquals(77, (float) $resPageDirect->viewData('assesment_result')->score);

        // 2. View Playlist A Result page
        $resPagePlA = $this->actingAs($this->user)->get(route('assesment.user.result', $assPlA->slug));
        $resPagePlA->assertStatus(200);
        $this->assertSame((int) $resPlA->id, (int) $resPagePlA->viewData('assesment_result')->id);
        $this->assertEquals(88, (float) $resPagePlA->viewData('assesment_result')->score);
    }

    /**
     * TEST 10: Result Page Session Scoping Edge Case Audit
     * When session('assesmentResultId') holds result from another assessment.
     */
    public function test_result_page_session_id_from_another_assessment_edge_case(): void
    {
        $content = $this->createContent('crosssess');
        $assA = $this->createAssessment(['title' => 'Ass Cross A', 'type' => 'content', 'content_for' => 'content', 'content_id' => $content->id]);
        $assB = $this->createAssessment(['title' => 'Ass Cross B', 'type' => 'content', 'content_for' => 'content', 'content_id' => $content->id]);

        $resA = AssesmentResult::create([
            'user_id'      => $this->user->id,
            'assesment_id' => $assA->id,
            'batch'        => '1',
            'score'        => 55,
            'pass'         => 0,
            'final'        => 1,
            'user_answer'  => [],
        ]);

        $resB = AssesmentResult::create([
            'user_id'      => $this->user->id,
            'assesment_id' => $assB->id,
            'batch'        => '1',
            'score'        => 99,
            'pass'         => 1,
            'final'        => 1,
            'user_answer'  => [],
        ]);

        // 1. User visits Assessment A result page with session holding resA id: resA is displayed
        $resPageA = $this->actingAs($this->user)
            ->withSession(['assesmentResultId' => $resA->id])
            ->get(route('assesment.user.result', $assA->slug));

        $resPageA->assertStatus(200);
        $loadedResultA = $resPageA->viewData('assesment_result');
        $this->assertNotNull($loadedResultA);
        $this->assertSame((int) $resA->id, (int) $loadedResultA->id);
        $this->assertSame((int) $assA->id, (int) $loadedResultA->assesment_id);

        // 2. User visits Assessment B result page with session still holding resA id:
        // resA MUST NOT be displayed! It must use resB via fallback query
        $resPageB = $this->actingAs($this->user)
            ->withSession(['assesmentResultId' => $resA->id])
            ->get(route('assesment.user.result', $assB->slug));

        $resPageB->assertStatus(200);
        $loadedResultB = $resPageB->viewData('assesment_result');
        $this->assertNotNull($loadedResultB);
        $this->assertNotEquals((int) $resA->id, (int) $loadedResultB->id, 'Result A must NOT be loaded for Assessment B');
        $this->assertSame((int) $resB->id, (int) $loadedResultB->id, 'Result B must be loaded for Assessment B');
        $this->assertSame((int) $assB->id, (int) $loadedResultB->assesment_id);

        // 3. User visits Assessment C (no results yet) with session still holding resA id:
        // resA MUST NOT be displayed! loadedResult must be null
        $assC = $this->createAssessment(['title' => 'Ass Cross C', 'type' => 'content', 'content_for' => 'content', 'content_id' => $content->id]);
        $resPageC = $this->actingAs($this->user)
            ->withSession(['assesmentResultId' => $resA->id])
            ->get(route('assesment.user.result', $assC->slug));

        $resPageC->assertStatus(200);
        $loadedResultC = $resPageC->viewData('assesment_result');
        $this->assertNull($loadedResultC, 'Assessment C has no results, so result A must NOT leak into Assessment C');
    }

    /**
     * TEST 11: Global Assessment Isolation
     */
    public function test_global_assessment_isolation(): void
    {
        $global = $this->createAssessment([
            'type'            => 'global',
            'content_for'     => 'content',
            'content_id'      => null,
            'trainingplan_id' => null,
            'total_remedials' => 0,
        ]);

        AssesmentResult::create([
            'user_id'      => $this->user->id,
            'assesment_id' => $global->id,
            'batch'        => '1',
            'score'        => 70,
            'pass'         => 0,
            'final'        => 1,
            'user_answer'  => [],
        ]);

        // Global allows only 1 attempt. After final=1, AssessmentAccessService denies with REASON_MAX_ATTEMPTS
        $viewRes = $this->actingAs($this->user)->get(route('assesment.user.view', $global->slug));
        $viewRes->assertStatus(403);
        $viewRes->assertViewIs('errors.assessment-unavailable');
        $viewRes->assertViewHas('reason', AssessmentAccessService::REASON_MAX_ATTEMPTS);
    }

    /**
     * TEST 12: Assessment Playlist (TrainingPlan) Isolation
     */
    public function test_trainingplan_assessment_isolation(): void
    {
        $playlist = $this->createPlaylist('TP Ass Plan');
        $content = $this->createContent('tpcontent');
        TrainingPlanDetail::create(['trainingplan_id' => $playlist->id, 'content_id' => $content->id, 'content_order' => '1']);

        $assTp = $this->createAssessment([
            'type'            => 'trainingplan',
            'content_for'     => 'content',
            'content_id'      => null,
            'trainingplan_id' => $playlist->id,
        ]);

        // User hasn't finished content in playlist
        PlaylistContentUser::create([
            'user_id'         => $this->user->id,
            'trainingplan_id' => $playlist->id,
            'content_id'      => $content->id,
            'progress'        => 50,
            'finish'          => '0',
            'learning_status' => 'in_progress',
        ]);

        $accessService = app(AssessmentAccessService::class);
        $reason = $accessService->denialReason($assTp, $this->user);

        // Denied because not all contents in playlist are finished
        $this->assertSame(AssessmentAccessService::REASON_PROGRESS, $reason);

        // Finish content
        PlaylistContentUser::where('user_id', $this->user->id)
            ->where('trainingplan_id', $playlist->id)
            ->where('content_id', $content->id)
            ->update(['progress' => 100, 'finish' => '1', 'learning_status' => 'finished']);

        $reasonAfter = $accessService->denialReason($assTp, $this->user);
        $this->assertNull($reasonAfter);
    }

    /**
     * TEST 13: Legacy Assessment Content Compatibility
     */
    public function test_legacy_assessment_content_compatibility(): void
    {
        $content = $this->createContent('legacy');
        // Legacy content assessment has type = 'content', content_for = 'content', trainingplan_id = null
        $legacy = $this->createAssessment([
            'title'           => 'Legacy Content Assessment',
            'type'            => 'content',
            'content_for'     => 'content',
            'content_id'      => $content->id,
            'trainingplan_id' => null,
            'status'          => 'OK',
            'passing_grade'   => 75,
            'batch'           => 1,
        ]);

        ContentUser::create([
            'user_id'         => $this->user->id,
            'content_id'      => $content->id,
            'progress'        => 100,
            'finish'          => '1',
            'learning_status' => 'finished',
        ]);

        // 1. Appears in Direct Content View
        $viewContent = $this->actingAs($this->user)->get(route('content.user.view', $content->slug));
        $viewContent->assertStatus(200);
        $this->assertSame((int) $legacy->id, (int) $viewContent->viewData('assesment')->id);

        // 2. Can be started (viewed)
        $viewAss = $this->actingAs($this->user)->get(route('assesment.user.view', $legacy->slug));
        $viewAss->assertStatus(200);

        // 3. Can be submitted
        $q = $legacy->questions->first();
        $subRes = $this->actingAs($this->user)->post(route('assesment.user.store', $legacy->slug), [
            'submission_token'   => Str::uuid()->toString(),
            (string) $q->id      => 'B',
        ]);
        $subRes->assertRedirect();

        // 4. Result exists and is final
        $result = AssesmentResult::where('user_id', $this->user->id)
            ->where('assesment_id', $legacy->id)
            ->where('final', 1)
            ->first();
        $this->assertNotNull($result);
        $this->assertEquals(100, (float) $result->score);
        $this->assertSame(1, (int) $result->pass);

        // 5. Result page renders correctly
        $resPage = $this->actingAs($this->user)->get(route('assesment.user.result', $legacy->slug));
        $resPage->assertStatus(200);
        $this->assertSame((int) $result->id, (int) $resPage->viewData('assesment_result')->id);
    }
}
