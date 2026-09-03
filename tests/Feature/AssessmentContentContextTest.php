<?php

namespace Tests\Feature;

use App\Models\Assesment;
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
use Tests\TestCase;

class AssessmentContentContextTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected User $admin;
    protected Classification $classification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'team'       => 'QA Team',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-hash-acc-user-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-ACC-U-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->admin = User::factory()->create([
            'team'       => 'Admin Team',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-hash-acc-admin-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-ACC-ADM-' . rand(1000, 9999),
            'role'       => 2,
            'active'     => 1,
        ]);

        $this->classification = Classification::firstOrCreate(
            ['classification_name' => 'Assessment Context Test Classification'],
            ['slug' => 'assessment-context-test-classification']
        );
    }

    /**
     * TEST 1 & 2: Existing legacy content assessment and content_for = 'content' appear in View Content.
     */
    public function test_1_and_2_content_for_content_appears_in_view_content(): void
    {
        $content = Content::create([
            'title'             => 'Direct Course 1',
            'slug'              => 'dir-course-1-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $assessment = Assesment::create([
            'title'         => 'Direct Course Assessment',
            'slug'          => 'dir-ass-1-' . rand(1000, 9999),
            'type'          => 'content',
            'content_for'   => 'content',
            'content_id'    => $content->id,
            'status'        => 'OK',
            'passing_grade' => 80,
            'batch'         => 1,
        ]);

        $response = $this->actingAs($this->user)->get(route('content.user.view', $content->slug));

        $response->assertStatus(200);
        $response->assertViewHas('assesment');
        $this->assertEquals($assessment->id, $response->viewData('assesment')->id);
    }

    /**
     * TEST 3: content_for = 'playlist' does NOT appear in View Content.
     */
    public function test_3_content_for_playlist_does_not_appear_in_view_content(): void
    {
        $playlist = TrainingPlan::create([
            'title'  => 'Test Playlist 3',
            'slug'   => 'tp-test-3-' . rand(1000, 9999),
            'active' => 'Active',
        ]);

        $content = Content::create([
            'title'             => 'Playlist Course 3',
            'slug'              => 'pl-course-3-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        Assesment::create([
            'title'           => 'Playlist Specific Assessment',
            'slug'            => 'pl-spec-ass-3-' . rand(1000, 9999),
            'type'            => 'content',
            'content_for'     => 'playlist',
            'content_id'      => $content->id,
            'trainingplan_id' => $playlist->id,
            'status'          => 'OK',
            'passing_grade'   => 80,
            'batch'           => 1,
        ]);

        $response = $this->actingAs($this->user)->get(route('content.user.view', $content->slug));

        $response->assertStatus(200);
        $this->assertNull($response->viewData('assesment'));
    }

    /**
     * TEST 4: content_for = 'playlist' appears in Playlist Sidebar for matching playlist.
     */
    public function test_4_content_for_playlist_appears_in_playlist_sidebar(): void
    {
        $playlist = TrainingPlan::create([
            'title'  => 'Test Playlist 4',
            'slug'   => 'tp-test-4-' . rand(1000, 9999),
            'active' => 'Active',
        ]);

        $content = Content::create([
            'title'             => 'Playlist Lesson 4',
            'slug'              => 'pl-lesson-4-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => $playlist->id,
            'content_id'      => $content->id,
            'content_order'   => 1,
        ]);

        $contentAssessment = Assesment::create([
            'title'           => 'Lesson 4 Assessment',
            'slug'            => 'l4-ass-' . rand(1000, 9999),
            'type'            => 'content',
            'content_for'     => 'playlist',
            'content_id'      => $content->id,
            'trainingplan_id' => $playlist->id,
            'status'          => 'OK',
            'passing_grade'   => 80,
            'batch'           => 1,
        ]);

        $response = $this->actingAs($this->user)->get(route('playlist.user.view', [$playlist->slug, $content->slug]));

        $response->assertStatus(200);
        $response->assertViewHas('assessmentsByContent');
        $assessmentsByContent = $response->viewData('assessmentsByContent');

        $this->assertTrue($assessmentsByContent->has($content->id));
        $this->assertEquals($contentAssessment->id, $assessmentsByContent->get($content->id)->id);
        $response->assertSee('Assessment Content');
    }

    /**
     * TEST 5 & 6: Assessment Playlist (type = 'trainingplan') remains intact and is NOT confused with Assessment Content.
     */
    public function test_5_and_6_assessment_playlist_remains_intact_and_distinct(): void
    {
        $playlist = TrainingPlan::create([
            'title'  => 'Test Playlist 5',
            'slug'   => 'tp-test-5-' . rand(1000, 9999),
            'active' => 'Active',
        ]);

        $content = Content::create([
            'title'             => 'Playlist Lesson 5',
            'slug'              => 'pl-lesson-5-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => $playlist->id,
            'content_id'      => $content->id,
            'content_order'   => 1,
        ]);

        // Assessment Playlist (Final Exam for the whole playlist)
        $playlistFinalAssessment = Assesment::create([
            'title'           => 'Final Playlist Exam 5',
            'slug'            => 'final-pl-exam-5-' . rand(1000, 9999),
            'type'            => 'trainingplan',
            'content_for'     => 'content',
            'trainingplan_id' => $playlist->id,
            'content_id'      => null,
            'status'          => 'OK',
            'passing_grade'   => 85,
            'batch'           => 1,
        ]);

        // Assessment Content for lesson 5
        $lessonAssessment = Assesment::create([
            'title'           => 'Lesson 5 Quiz',
            'slug'            => 'lesson-5-quiz-' . rand(1000, 9999),
            'type'            => 'content',
            'content_for'     => 'playlist',
            'trainingplan_id' => $playlist->id,
            'content_id'      => $content->id,
            'status'          => 'OK',
            'passing_grade'   => 75,
            'batch'           => 1,
        ]);

        $response = $this->actingAs($this->user)->get(route('playlist.user.view', [$playlist->slug, $content->slug]));

        $response->assertStatus(200);

        // $assesment variable in view is specifically the Playlist Final Exam
        $this->assertEquals($playlistFinalAssessment->id, $response->viewData('assesment')->id);
        $this->assertEquals('trainingplan', $response->viewData('assesment')->type);

        // $assessmentsByContent in view contains the lesson-level assessment
        $assessmentsByContent = $response->viewData('assessmentsByContent');
        $this->assertTrue($assessmentsByContent->has($content->id));
        $this->assertEquals($lessonAssessment->id, $assessmentsByContent->get($content->id)->id);
        $this->assertEquals('content', $assessmentsByContent->get($content->id)->type);

        // Both buttons exist in the page: Final exam in header/nav, Lesson assessment in sidebar
        $response->assertSee('Assessment Playlist');
        $response->assertSee('Assessment Content');
    }

    /**
     * TEST 7 & 8: Multi-playlist isolation: Content A in Playlist A and Playlist B.
     * Assessment created for Playlist A must NOT appear in Playlist B.
     */
    public function test_7_and_8_multi_playlist_isolation(): void
    {
        $playlistA = TrainingPlan::create([
            'title'  => 'Playlist A',
            'slug'   => 'pl-a-' . rand(1000, 9999),
            'active' => 'Active',
        ]);

        $playlistB = TrainingPlan::create([
            'title'  => 'Playlist B',
            'slug'   => 'pl-b-' . rand(1000, 9999),
            'active' => 'Active',
        ]);

        $sharedContent = Content::create([
            'title'             => 'Shared Lesson Between Playlists',
            'slug'              => 'shared-lesson-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $playlistA->id, 'content_id' => $sharedContent->id, 'content_order' => 1]);
        TrainingPlanDetail::create(['trainingplan_id' => $playlistB->id, 'content_id' => $sharedContent->id, 'content_order' => 1]);

        // Assessment assigned specifically to Playlist A
        $assessmentA = Assesment::create([
            'title'           => 'Assessment For Playlist A Only',
            'slug'            => 'ass-pl-a-' . rand(1000, 9999),
            'type'            => 'content',
            'content_for'     => 'playlist',
            'content_id'      => $sharedContent->id,
            'trainingplan_id' => $playlistA->id,
            'status'          => 'OK',
            'passing_grade'   => 80,
            'batch'           => 1,
        ]);

        // 1. In Playlist A: Assessment MUST appear
        $resA = $this->actingAs($this->user)->get(route('playlist.user.view', [$playlistA->slug, $sharedContent->slug]));
        $resA->assertStatus(200);
        $assessmentsA = $resA->viewData('assessmentsByContent');
        $this->assertTrue($assessmentsA->has($sharedContent->id));
        $this->assertEquals($assessmentA->id, $assessmentsA->get($sharedContent->id)->id);
        $resA->assertSee('data-assessment-action');

        // 2. In Playlist B: Assessment MUST NOT appear
        $resB = $this->actingAs($this->user)->get(route('playlist.user.view', [$playlistB->slug, $sharedContent->slug]));
        $resB->assertStatus(200);
        $assessmentsB = $resB->viewData('assessmentsByContent');
        $this->assertFalse($assessmentsB->has($sharedContent->id));
        $resB->assertDontSee('data-assessment-action');

        // 3. In Direct View: Assessment MUST NOT appear
        $resDirect = $this->actingAs($this->user)->get(route('content.user.view', $sharedContent->slug));
        $resDirect->assertStatus(200);
        $this->assertNull($resDirect->viewData('assesment'));
    }

    /**
     * TEST 9 & 10: Progress context verification:
     * - Assessment Content Playlist checks playlist_content_users
     * - Direct Content Assessment checks content_users
     */
    public function test_9_and_10_progress_contexts_and_access_service(): void
    {
        $accessService = app(AssessmentAccessService::class);

        $playlist = TrainingPlan::create([
            'title'  => 'Progress Plan 9',
            'slug'   => 'prog-plan-9-' . rand(1000, 9999),
            'active' => 'Active',
        ]);

        $content = Content::create([
            'title'             => 'Progress Content 9',
            'slug'              => 'prog-c-9-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $playlist->id, 'content_id' => $content->id, 'content_order' => 1]);

        $playlistAssessment = Assesment::create([
            'title'           => 'Playlist Content Assessment',
            'slug'            => 'pca-9-' . rand(1000, 9999),
            'type'            => 'content',
            'content_for'     => 'playlist',
            'content_id'      => $content->id,
            'trainingplan_id' => $playlist->id,
            'status'          => 'OK',
            'passing_grade'   => 70,
            'batch'           => 1,
        ]);

        // Initially 0% -> access denied with REASON_PROGRESS
        $this->assertEquals(
            AssessmentAccessService::REASON_PROGRESS,
            $accessService->denialReason($playlistAssessment, $this->user)
        );

        // User finishes in playlist_content_users (100%), but content_users has 0%
        PlaylistContentUser::create([
            'user_id'         => $this->user->id,
            'trainingplan_id' => $playlist->id,
            'content_id'      => $content->id,
            'progress'        => 100,
            'finish'          => '1',
        ]);

        // Assessment correctly checks playlist_content_users because trainingplan_id is set
        $this->assertNull(
            $accessService->denialReason($playlistAssessment, $this->user)
        );

        // Direct Content Assessment:
        $directAssessment = Assesment::create([
            'title'         => 'Direct Content Assessment',
            'slug'          => 'dca-9-' . rand(1000, 9999),
            'type'          => 'content',
            'content_for'   => 'content',
            'content_id'    => $content->id,
            'status'        => 'OK',
            'passing_grade' => 70,
            'batch'         => 1,
        ]);

        // Direct assessment checks content_users -> denied because content_users is 0%
        $this->assertEquals(
            AssessmentAccessService::REASON_PROGRESS,
            $accessService->denialReason($directAssessment, $this->user)
        );

        // Grant 100% on content_users:
        ContentUser::create([
            'user_id'    => $this->user->id,
            'content_id' => $content->id,
            'progress'   => 100,
            'finish'     => '1',
        ]);

        $this->assertNull(
            $accessService->denialReason($directAssessment, $this->user)
        );
    }

    /**
     * TEST 11: Admin form store and update flow for content_for (validation, create, edit toggle).
     */
    public function test_11_admin_assessment_crud_content_for(): void
    {
        $playlist = TrainingPlan::create(['title' => 'Admin Test TP', 'slug' => 'adm-tp-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create([
            'title'             => 'Admin Test Content',
            'slug'              => 'adm-cnt-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
        ]);

        // 1. Validation fails if content_for = 'playlist' but no trainingplan_id
        $failRes = $this->actingAs($this->admin)->post(route('assesment.store'), [
            'type'               => 'content',
            'content_for'        => 'playlist',
            'content_id'         => $content->id,
            'title'              => 'Invalid Assessment Without TP',
            'passing_grade'      => 75,
            'total_remedials'    => 1,
            'has_access_period'  => '0',
        ]);
        $failRes->assertSessionHasErrors(['trainingplan_id']);

        // 2. Success create with content_for = 'playlist'
        $slug = 'valid-pl-ass-' . rand(1000, 9999);
        $successRes = $this->actingAs($this->admin)->post(route('assesment.store'), [
            'type'               => 'content',
            'content_for'        => 'playlist',
            'content_id'         => $content->id,
            'trainingplan_id'    => $playlist->id,
            'title'              => 'Valid Playlist Assessment',
            'unique_code'        => 'VPL' . rand(100, 999),
            'passing_grade'      => 80,
            'total_remedials'    => 2,
            'has_access_period'  => '0',
        ]);

        $created = Assesment::where('title', 'Valid Playlist Assessment')->first();
        $this->assertNotNull($created);
        $this->assertEquals('content', $created->type);
        $this->assertEquals('playlist', $created->content_for);
        $this->assertEquals($content->id, $created->content_id);
        $this->assertEquals($playlist->id, $created->trainingplan_id);

        // 3. Edit toggle: switch from Playlist -> Content
        $updateRes = $this->actingAs($this->admin)->put(route('assesment.update', $created->slug), [
            'type'               => 'content',
            'content_for'        => 'content',
            'content_id'         => $content->id,
            'trainingplan_id'    => $playlist->id, // sent by form but should be nullified
            'title'              => 'Updated To Direct Content',
            'unique_code'        => $created->unique_code,
            'passing_grade'      => 80,
            'total_remedials'    => 2,
            'has_access_period'  => '0',
        ]);

        $updated = $created->fresh();
        $this->assertEquals('content', $updated->content_for);
        $this->assertNull($updated->trainingplan_id);
    }

    /**
     * TEST 12: Idempotence & Read-only rendering safety: No mutations during view rendering.
     */
    public function test_12_no_database_mutations_during_view_rendering(): void
    {
        $content = Content::create([
            'title'             => 'Read Only Render Content',
            'slug'              => 'ro-render-c-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
        ]);

        $assessment = Assesment::create([
            'title'         => 'Read Only Render Assessment',
            'slug'          => 'ro-render-ass-' . rand(1000, 9999),
            'type'          => 'content',
            'content_for'   => 'content',
            'content_id'    => $content->id,
            'status'        => 'OK',
            'passing_grade' => 80,
            'batch'         => 1,
        ]);

        $countBefore = AssesmentResult::count();

        // Render direct content view
        $this->actingAs($this->user)->get(route('content.user.view', $content->slug));

        $this->assertEquals($countBefore, AssesmentResult::count());
    }

    /**
     * TEST 13: Assessment Management / Assessment List renders "All Content" for direct content,
     * while preserving "Content (Playlist)", "Playlist", and "Global" labels.
     * Database attributes (type, content_for) remain untouched.
     */
    public function test_13_assessment_management_list_renders_all_content_and_existing_labels(): void
    {
        $playlist = TrainingPlan::create([
            'title'  => 'Label Test Playlist ' . rand(1000, 9999),
            'slug'   => 'label-tp-' . rand(1000, 9999),
            'active' => 'Active',
        ]);

        $content = Content::create([
            'title'             => 'Label Test Content ' . rand(1000, 9999),
            'slug'              => 'label-cnt-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
        ]);

        // 1. type=content + content_for=content -> "All Content"
        $directContentAssessment = Assesment::create([
            'title'         => 'Direct Content Assessment Label Test',
            'slug'          => 'dir-cnt-lbl-' . rand(1000, 9999),
            'type'          => 'content',
            'content_for'   => 'content',
            'content_id'    => $content->id,
            'status'        => 'OK',
            'passing_grade' => 80,
            'batch'         => 1,
        ]);

        // 2. type=content + content_for=playlist -> "Content (Playlist)"
        $playlistContentAssessment = Assesment::create([
            'title'           => 'Playlist Content Assessment Label Test',
            'slug'            => 'pl-cnt-lbl-' . rand(1000, 9999),
            'type'            => 'content',
            'content_for'     => 'playlist',
            'content_id'      => $content->id,
            'trainingplan_id' => $playlist->id,
            'status'          => 'OK',
            'passing_grade'   => 80,
            'batch'           => 1,
        ]);

        // 3. type=global -> "Global"
        $globalAssessment = Assesment::create([
            'title'         => 'Global Assessment Label Test',
            'slug'          => 'global-lbl-' . rand(1000, 9999),
            'type'          => 'global',
            'status'        => 'OK',
            'passing_grade' => 80,
            'batch'         => 1,
        ]);

        // 4. type=trainingplan -> "Playlist"
        $trainingplanAssessment = Assesment::create([
            'title'           => 'Trainingplan Assessment Label Test',
            'slug'            => 'tp-lbl-' . rand(1000, 9999),
            'type'            => 'trainingplan',
            'trainingplan_id' => $playlist->id,
            'status'          => 'OK',
            'passing_grade'   => 80,
            'batch'           => 1,
        ]);

        // Verify individual row rendering
        $directRowHtml = view('main.assesment.partials.table-row', [
            'list'      => $directContentAssessment,
            'iteration' => 1,
        ])->render();
        $this->assertStringContainsString('Content (All Content)', $directRowHtml);
        $this->assertStringNotContainsString('Content (Playlist)', $directRowHtml);

        // Ensure badge text specifically
        $this->assertMatchesRegularExpression('/<span class="owl-badge owl-badge-blue">\s*Content \(All Content\)\s*<\/span>/', $directRowHtml);

        $playlistRowHtml = view('main.assesment.partials.table-row', [
            'list'      => $playlistContentAssessment,
            'iteration' => 2,
        ])->render();
        $this->assertStringContainsString('Content (Playlist)', $playlistRowHtml);
        $this->assertMatchesRegularExpression('/<span class="owl-badge owl-badge-blue">\s*Content \(Playlist\)\s*<\/span>/', $playlistRowHtml);

        $globalRowHtml = view('main.assesment.partials.table-row', [
            'list'      => $globalAssessment,
            'iteration' => 3,
        ])->render();
        $this->assertStringContainsString('Global', $globalRowHtml);
        $this->assertMatchesRegularExpression('/<span class="owl-badge owl-badge-green">\s*Global\s*<\/span>/', $globalRowHtml);

        $trainingplanRowHtml = view('main.assesment.partials.table-row', [
            'list'      => $trainingplanAssessment,
            'iteration' => 4,
        ])->render();
        $this->assertStringContainsString('Playlist', $trainingplanRowHtml);
        $this->assertMatchesRegularExpression('/<span class="owl-badge owl-badge-purple">\s*Playlist\s*<\/span>/', $trainingplanRowHtml);

        // Verify HTTP GET /manage/assessment (route 'assesment.index')
        $response = $this->actingAs($this->admin)->get(route('assesment.index'));
        $response->assertStatus(200);
        $response->assertSee('Content (All Content)');
        $response->assertSee('Content (Playlist)');
        $response->assertSee('Global');
        $response->assertSee('Playlist');

        // Verify database fields remain unchanged
        $this->assertSame('content', $directContentAssessment->fresh()->type);
        $this->assertSame('content', $directContentAssessment->fresh()->content_for);
        $this->assertSame('content', $playlistContentAssessment->fresh()->type);
        $this->assertSame('playlist', $playlistContentAssessment->fresh()->content_for);
        $this->assertSame('global', $globalAssessment->fresh()->type);
        $this->assertSame('trainingplan', $trainingplanAssessment->fresh()->type);
    }
}
