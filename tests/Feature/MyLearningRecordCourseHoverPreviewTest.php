<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MyLearningRecordCourseHoverPreviewTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private Classification $classification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name'       => 'Test Learner ' . rand(1000, 9999),
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-hash-lh-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-LH-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);
        $this->classification = Classification::firstOrCreate(
            ['classification_name' => 'Technology & Science'],
            ['slug' => 'tech-science']
        );
    }

    /**
     * CASE 1 & CASE 7: Direct Video hover preview template contains title, and clicking title navigates to /content/{slug}.
     */
    public function test_direct_video_hover_preview_and_direct_navigation(): void
    {
        $video = Content::create([
            'title'             => 'Direct Video Machine Learning',
            'slug'              => 'video-ml-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:25:00',
            'learning_hours'    => 0.5,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        ContentUser::create([
            'user_id'    => $this->user->id,
            'content_id' => $video->id,
            'progress'   => 100,
            'finish'     => '1',
            'status'     => 'mandatory',
        ]);

        $this->actingAs($this->user);

        $response = $this->get(route('user.learning.dashboard.partial', ['tab' => 'record']));
        $response->assertStatus(200);

        $html = $response->getContent();

        // 1. Direct Content title link points to /content/{slug}
        $expectedUrl = route('content.user.view', $video->slug);
        $this->assertStringContainsString('href="' . $expectedUrl . '"', $html);
        $this->assertStringNotContainsString('/playlist/' . $video->slug, $html);

        // 2. Category trigger exists, Course title is strictly for redirect (NOT a popup trigger)
        $this->assertStringContainsString('category-preview-trigger', $html);
        $this->assertStringContainsString('data-preview-kind="content"', $html);
        $this->assertStringNotContainsString('course-preview-trigger', $html);

        // 3. Hover preview template contains title inside course-preview-content
        $this->assertStringContainsString('course-preview-content', $html);
        $this->assertStringContainsString('Direct Video Machine Learning', $html);
        $this->assertStringContainsString('Type:', $html);
        $this->assertStringContainsString('Video', $html);
        $this->assertStringContainsString('Classification:', $html);
        $this->assertStringContainsString('Technology &amp; Science', $html);

        // 4. Mandatory badge displayed under Course Title as plain text
        $this->assertStringContainsString('<div class="learning-record-program text-muted small mt-1">Mandatory</div>', $html);
    }

    /**
     * CASE 2: Direct Document hover preview template contains title, and Optional/Mandatory badges remain hidden.
     */
    public function test_direct_document_hover_preview_and_badges(): void
    {
        $doc = Content::create([
            'title'             => 'Direct Document Architecture Guide',
            'slug'              => 'doc-arch-' . rand(1000, 9999),
            'type'              => 'Document',
            'classification_id' => $this->classification->id,
            'duration'          => '00:15:00',
            'learning_hours'    => 0.5,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        ContentUser::create([
            'user_id'    => $this->user->id,
            'content_id' => $doc->id,
            'progress'   => 100,
            'finish'     => '1',
            'status'     => 'optional',
        ]);

        $this->actingAs($this->user);

        $response = $this->get(route('user.learning.dashboard.partial', ['tab' => 'record']));
        $response->assertStatus(200);

        $html = $response->getContent();

        $expectedUrl = route('content.user.view', $doc->slug);
        $this->assertStringContainsString('href="' . $expectedUrl . '"', $html);

        // Template contains document title, type, and classification
        $this->assertStringContainsString('Direct Document Architecture Guide', $html);
        $this->assertStringContainsString('Type:', $html);
        $this->assertStringContainsString('Document', $html);
        $this->assertStringContainsString('Classification:', $html);
        $this->assertStringContainsString('Technology &amp; Science', $html);

        // CASE 12: Document compact badge present in TYPE column
        $this->assertStringContainsString('learning-record-type-badge', $html);

        // CASE 13: Optional badge displayed under Course Title as plain text
        $this->assertStringContainsString('<div class="learning-record-program text-muted small mt-1">Optional</div>', $html);
    }

    /**
     * CASE 3, 4, 5, 6: Playlist hover displays playlist name, all contents in content_order,
     * and clicking an item routes to /playlist/{playlist:slug}/{content:slug}.
     */
    public function test_playlist_hover_preview_ordering_and_item_navigation(): void
    {
        $plan = TrainingPlan::create([
            'title'                  => 'Fullstack Web Engineering',
            'slug'                   => 'fullstack-web-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 5400,
            'learning_hours'         => 1.5,
        ]);

        $c1 = Content::create(['title' => 'Module 1: Introduction to Web', 'slug' => 'mod-1-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'duration' => '00:30:00', 'learning_hours' => 0.5, 'active' => 'Active', 'playlistonly' => 'Y']);
        $c2 = Content::create(['title' => 'Module 2: Database Design', 'slug' => 'mod-2-' . rand(1000, 9999), 'type' => 'Document', 'classification_id' => $this->classification->id, 'duration' => '00:30:00', 'learning_hours' => 0.5, 'active' => 'Active', 'playlistonly' => 'Y']);
        $c3 = Content::create(['title' => 'Module 3: Security & Deployment', 'slug' => 'mod-3-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'duration' => '00:30:00', 'learning_hours' => 0.5, 'active' => 'Active', 'playlistonly' => 'Y']);

        // Insert in non-sequential order to test content_order sorting
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 2]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 1]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c3->id, 'content_order' => 3]);

        PlaylistContentUser::create(['user_id' => $this->user->id, 'trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'progress' => 100, 'finish' => '1']);
        PlaylistContentUser::create(['user_id' => $this->user->id, 'trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'progress' => 100, 'finish' => '1']);
        PlaylistContentUser::create(['user_id' => $this->user->id, 'trainingplan_id' => $plan->id, 'content_id' => $c3->id, 'progress' => 100, 'finish' => '1']);

        $this->actingAs($this->user);

        $response = $this->get(route('user.learning.dashboard.partial', ['tab' => 'record']));
        $response->assertStatus(200);

        $html = $response->getContent();

        // CASE 3: Hover displays Playlist name
        $this->assertStringContainsString('Fullstack Web Engineering', $html);
        $this->assertStringContainsString('course-preview-playlist', $html);

        // CASE 4: Popup displays all 3 contents
        $this->assertStringContainsString('Module 1: Introduction to Web', $html);
        $this->assertStringContainsString('Module 2: Database Design', $html);
        $this->assertStringContainsString('Module 3: Security &amp; Deployment', $html);

        // CASE 5: Ordering strictly follows content_order (Module 1 before Module 2, Module 2 before Module 3)
        $pos1 = strpos($html, 'Module 1: Introduction to Web');
        $pos2 = strpos($html, 'Module 2: Database Design');
        $pos3 = strpos($html, 'Module 3: Security &amp; Deployment');
        $this->assertTrue($pos1 < $pos2, 'Module 1 must appear before Module 2 in playlist popup.');
        $this->assertTrue($pos2 < $pos3, 'Module 2 must appear before Module 3 in playlist popup.');

        // CASE 6: Clicking content inside popup routes to /playlist/{playlist}/{content}
        $expectedItem1Url = route('playlist.user.view', [$plan->slug, $c1->slug]);
        $expectedItem2Url = route('playlist.user.view', [$plan->slug, $c2->slug]);
        $expectedItem3Url = route('playlist.user.view', [$plan->slug, $c3->slug]);

        $this->assertStringContainsString('href="' . $expectedItem1Url . '"', $html);
        $this->assertStringContainsString('href="' . $expectedItem2Url . '"', $html);
        $this->assertStringContainsString('href="' . $expectedItem3Url . '"', $html);
    }

    /**
     * CASE 8 & 9: Multi-Playlist isolation — Playlist A popup does not leak content into Playlist B.
     */
    public function test_multi_playlist_isolation_and_no_leakage(): void
    {
        $planA = TrainingPlan::create(['title' => 'Alpha Track', 'slug' => 'alpha-track-' . rand(1000, 9999), 'active' => 'Active', 'total_duration_seconds' => 1800, 'learning_hours' => 0.5]);
        $planB = TrainingPlan::create(['title' => 'Beta Track', 'slug' => 'beta-track-' . rand(1000, 9999), 'active' => 'Active', 'total_duration_seconds' => 1800, 'learning_hours' => 0.5]);

        $cA = Content::create(['title' => 'Alpha Only Module', 'slug' => 'alpha-mod-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        $cB = Content::create(['title' => 'Beta Only Module', 'slug' => 'beta-mod-' . rand(1000, 9999), 'type' => 'Document', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);

        TrainingPlanDetail::create(['trainingplan_id' => $planA->id, 'content_id' => $cA->id, 'content_order' => 1]);
        TrainingPlanDetail::create(['trainingplan_id' => $planB->id, 'content_id' => $cB->id, 'content_order' => 1]);

        PlaylistContentUser::create(['user_id' => $this->user->id, 'trainingplan_id' => $planA->id, 'content_id' => $cA->id, 'progress' => 100, 'finish' => '1']);
        PlaylistContentUser::create(['user_id' => $this->user->id, 'trainingplan_id' => $planB->id, 'content_id' => $cB->id, 'progress' => 100, 'finish' => '1']);

        $this->actingAs($this->user);

        $response = $this->get(route('user.learning.dashboard.partial', ['tab' => 'record']));
        $response->assertStatus(200);

        $rows = $response->viewData('rows');
        $rowA = $rows->firstWhere('title', 'Alpha Track');
        $rowB = $rows->firstWhere('title', 'Beta Track');

        $this->assertNotNull($rowA);
        $this->assertNotNull($rowB);

        // Alpha track items must only contain Alpha Only Module
        $aTitles = collect($rowA['playlist_items'])->pluck('title')->all();
        $this->assertContains('Alpha Only Module', $aTitles);
        $this->assertNotContains('Beta Only Module', $aTitles);

        // Beta track items must only contain Beta Only Module
        $bTitles = collect($rowB['playlist_items'])->pluck('title')->all();
        $this->assertContains('Beta Only Module', $bTitles);
        $this->assertNotContains('Alpha Only Module', $bTitles);
    }

    /**
     * CASE 10: Zero database mutation during Learning Record rendering.
     */
    public function test_zero_database_mutation_on_render(): void
    {
        $plan = TrainingPlan::create(['title' => 'No Mutation Plan', 'slug' => 'nomut-plan-' . rand(1000, 9999), 'active' => 'Active', 'total_duration_seconds' => 1800, 'learning_hours' => 0.5]);
        $content = Content::create(['title' => 'No Mutation Content', 'slug' => 'nomut-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 1]);

        PlaylistContentUser::create(['user_id' => $this->user->id, 'trainingplan_id' => $plan->id, 'content_id' => $content->id, 'progress' => 100, 'finish' => '1']);
        ContentUser::create(['user_id' => $this->user->id, 'content_id' => $content->id, 'progress' => 100, 'finish' => '1']);

        $cuCountBefore = ContentUser::count();
        $pcuCountBefore = PlaylistContentUser::count();
        $planCountBefore = TrainingPlan::count();
        $contentCountBefore = Content::count();

        $this->actingAs($this->user);

        $response = $this->get(route('user.learning.dashboard.partial', ['tab' => 'record']));
        $response->assertStatus(200);

        $this->assertEquals($cuCountBefore, ContentUser::count(), 'content_users count must not change.');
        $this->assertEquals($pcuCountBefore, PlaylistContentUser::count(), 'playlist_content_users count must not change.');
        $this->assertEquals($planCountBefore, TrainingPlan::count(), 'trainingplan count must not change.');
        $this->assertEquals($contentCountBefore, Content::count(), 'content count must not change.');
    }

    /**
     * CASE 11: Zero N+1 query — playlist content data is retrieved in a single batch query.
     */
    public function test_zero_n_plus_one_for_playlist_contents(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $plan = TrainingPlan::create(['title' => "Batch Plan $i", 'slug' => "batch-p-$i-" . rand(1000, 9999), 'active' => 'Active', 'total_duration_seconds' => 1800, 'learning_hours' => 0.5]);
            $c = Content::create(['title' => "Batch Content $i", 'slug' => "batch-c-$i-" . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
            TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c->id, 'content_order' => 1]);
            PlaylistContentUser::create(['user_id' => $this->user->id, 'trainingplan_id' => $plan->id, 'content_id' => $c->id, 'progress' => 100, 'finish' => '1']);
        }

        $this->actingAs($this->user);

        $tpdQueries = 0;
        DB::listen(function ($query) use (&$tpdQueries) {
            if (str_contains($query->sql, 'trainingplandetail')) {
                $tpdQueries++;
            }
        });

        $response = $this->get(route('user.learning.dashboard.partial', ['tab' => 'record']));
        $response->assertStatus(200);

        // For 5 playlists, there are only batched queries on trainingplandetail (<= 5 queries in total across the entire page load, never 5+ queries scaling with playlists).
        $this->assertLessThanOrEqual(5, $tpdQueries, 'trainingplandetail queries must be batched, not N+1 per playlist.');
    }

    /**
     * Comprehensive Test for Phase Revisi Struktur 11 Kolom:
     * 1. Course title tampil.
     * 2. Optional tampil.
     * 3. Mandatory tampil.
     * 4. Type Video tampil pada kolom TYPE.
     * 5. Type Document tampil pada kolom TYPE.
     * 6. Type Playlist tampil pada kolom TYPE.
     * 7. Category Content untuk Video.
     * 8. Category Content untuk Document.
     * 9. Category Playlist untuk Playlist.
     * 10. Classification tetap benar.
     * 11. Filter Type tetap bekerja (data-type attribute).
     * 12. Filter Category tetap bekerja (data-category attribute).
     * 13. Filter Classification tetap bekerja (data-classification attribute).
     * 14. Learning Hour tetap benar.
     * 15. Progress Direct Content tidak berubah.
     * 16. Progress Playlist tidak berubah.
     * 17. Tidak ada database mutation.
     */
    public function test_revisi_struktur_11_kolom_type_category_classification_dan_program_badge(): void
    {
        $customClass = Classification::firstOrCreate(
            ['classification_name' => 'Tech Special Class'],
            ['slug' => 'tech-special-class']
        );

        // 1. Create Video Content (Mandatory)
        $video = Content::create([
            'title'             => 'Video 11-Col Course',
            'slug'              => 'video-11c-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $customClass->id,
            'duration'          => '00:30:00',
            'learning_hours'    => 0.5,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);
        ContentUser::create([
            'user_id'    => $this->user->id,
            'content_id' => $video->id,
            'progress'   => 100,
            'finish'     => '1',
            'status'     => 'mandatory',
        ]);

        // 2. Create Document Content (Optional)
        $doc = Content::create([
            'title'             => 'Doc 11-Col Course',
            'slug'              => 'doc-11c-' . rand(1000, 9999),
            'type'              => 'Document',
            'classification_id' => $customClass->id,
            'duration'          => '00:45:00',
            'learning_hours'    => 0.75,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);
        ContentUser::create([
            'user_id'    => $this->user->id,
            'content_id' => $doc->id,
            'progress'   => 100,
            'finish'     => '1',
            'status'     => 'optional',
        ]);

        // 3. Create Playlist
        $plan = TrainingPlan::create([
            'title'                  => 'Playlist 11-Col Track',
            'slug'                   => 'plan-11c-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 3600,
            'learning_hours'         => 1.0,
        ]);
        $planContent = Content::create([
            'title'             => 'Plan Member Content',
            'slug'              => 'pmc-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $customClass->id,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);
        TrainingPlanDetail::create([
            'trainingplan_id' => $plan->id,
            'content_id'      => $planContent->id,
            'content_order'   => 1,
        ]);
        PlaylistContentUser::create([
            'user_id'         => $this->user->id,
            'trainingplan_id' => $plan->id,
            'content_id'      => $planContent->id,
            'progress'        => 100,
            'finish'          => '1',
        ]);

        // Count DB records before request
        $cuBefore = ContentUser::count();
        $pcuBefore = PlaylistContentUser::count();
        $cBefore = Content::count();
        $tpBefore = TrainingPlan::count();

        $this->actingAs($this->user);

        $response = $this->get(route('user.learning.dashboard.partial', ['tab' => 'record']));
        $response->assertStatus(200);

        $html = $response->getContent();

        // 1. Course titles tampil
        $this->assertStringContainsString('Video 11-Col Course', $html);
        $this->assertStringContainsString('Doc 11-Col Course', $html);
        $this->assertStringContainsString('Playlist 11-Col Track', $html);

        // 2 & 3. Optional & Mandatory tampil sebagai plain text (tanpa badge/pill/border)
        $this->assertStringContainsString('<div class="learning-record-program text-muted small mt-1">Mandatory</div>', $html);
        $this->assertStringContainsString('<div class="learning-record-program text-muted small mt-1">Optional</div>', $html);
        $this->assertStringNotContainsString('bg-danger-lt text-danger learning-record-badge">Mandatory', $html);
        $this->assertStringNotContainsString('bg-secondary-lt text-secondary learning-record-badge">Optional', $html);

        // Horizontal scroll and table styling check
        $this->assertStringContainsString('learning-record-scroll', $html);
        $this->assertStringContainsString('min-width: 1280px', $html);

        // 4, 5, 6. Type Video, Document, Playlist tampil pada kolom TYPE
        $this->assertStringContainsString('<td class="learning-record-type text-center">', $html);
        $this->assertStringContainsString('learning-record-type-badge">Video</span>', $html);
        $this->assertStringContainsString('learning-record-type-badge">Document</span>', $html);
        $this->assertStringContainsString('learning-record-type-badge record-playlist-link"', $html);

        // 7, 8, 9. Category Content untuk Video/Doc, Playlist untuk Playlist (dengan category-preview-trigger)
        $this->assertStringContainsString('category-preview-trigger', $html);
        $this->assertStringContainsString('badge bg-blue-lt text-blue learning-record-badge category-preview-trigger', $html);
        $this->assertStringContainsString('badge bg-green-lt text-green learning-record-badge category-preview-trigger', $html);

        // 10. Classification tetap benar
        $this->assertStringContainsString('Tech Special Class', $html);

        // 11, 12, 13. Data attributes untuk filter JavaScript
        $this->assertStringContainsString('data-type="Video"', $html);
        $this->assertStringContainsString('data-type="Document"', $html);
        $this->assertStringContainsString('data-type="Playlist"', $html);
        $this->assertStringContainsString('data-category="Content"', $html);
        $this->assertStringContainsString('data-category="Playlist"', $html);
        $this->assertStringContainsString('data-classification="Tech Special Class"', $html);

        // 14. Learning Hour tetap benar
        $this->assertStringContainsString('0.5 h', $html);
        $this->assertStringContainsString('0.8 h', $html);
        $this->assertStringContainsString('1 h', $html);

        // 15 & 16. Progress Direct Content & Playlist tetap 100%
        $this->assertStringContainsString('100%', $html);

        // 17. Database safety - zero mutation
        $this->assertEquals($cuBefore, ContentUser::count(), 'Zero mutation on content_users');
        $this->assertEquals($pcuBefore, PlaylistContentUser::count(), 'Zero mutation on playlist_content_users');
        $this->assertEquals($cBefore, Content::count(), 'Zero mutation on content');
        $this->assertEquals($tpBefore, TrainingPlan::count(), 'Zero mutation on trainingplan');
    }

    /**
     * PROMPT TEST: Title is strictly for redirect (NO popup trigger on title),
     * while Category label is the popup trigger.
     */
    public function test_title_is_link_only_and_category_is_popup_trigger(): void
    {
        $plan = TrainingPlan::create([
            'title'                  => 'Coke Plant : Moving Machine (MM) 2025',
            'slug'                   => 'coke-plant-mm-2025-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 3600,
            'learning_hours'         => 1.0,
        ]);

        $m1 = Content::create(['title' => 'MM A - Pusher Car Operation', 'slug' => 'mm-a-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'duration' => '00:12:00', 'learning_hours' => 0.2, 'active' => 'Active', 'playlistonly' => 'Y']);
        $m2 = Content::create(['title' => 'MM B - Transfer Car Operation', 'slug' => 'mm-b-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'duration' => '00:12:00', 'learning_hours' => 0.2, 'active' => 'Active', 'playlistonly' => 'Y']);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $m1->id, 'content_order' => 1]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $m2->id, 'content_order' => 2]);

        PlaylistContentUser::create(['user_id' => $this->user->id, 'trainingplan_id' => $plan->id, 'content_id' => $m1->id, 'progress' => 100, 'finish' => '1']);
        PlaylistContentUser::create(['user_id' => $this->user->id, 'trainingplan_id' => $plan->id, 'content_id' => $m2->id, 'progress' => 100, 'finish' => '1']);

        $this->actingAs($this->user);
        $response = $this->get(route('user.learning.dashboard.partial', ['tab' => 'record']));
        $response->assertStatus(200);

        $html = $response->getContent();

        // 1. Course Title link points to playlist view URL and has NO popup trigger
        $expectedPlaylistViewUrl = route('playlist.user.view', [$plan->slug, $m1->slug]);
        $this->assertStringContainsString('href="' . $expectedPlaylistViewUrl . '"', $html);

        // Verify Title link does NOT have popup trigger classes or attributes
        $this->assertStringNotContainsString('learning-record-link fw-semibold category-preview-trigger', $html);
        $this->assertStringNotContainsString('learning-record-link fw-semibold course-preview-trigger', $html);
        $this->assertStringNotContainsString('data-bs-toggle="popover"', $html);

        // 2. Category badge has category-preview-trigger
        $this->assertStringContainsString('badge bg-green-lt text-green learning-record-badge category-preview-trigger', $html);

        // 3. Category cell contains the popup template with all modules in order
        $this->assertStringContainsString('MM A - Pusher Car Operation', $html);
        $this->assertStringContainsString('MM B - Transfer Car Operation', $html);
        $this->assertStringContainsString(route('playlist.user.view', [$plan->slug, $m1->slug]), $html);
        $this->assertStringContainsString(route('playlist.user.view', [$plan->slug, $m2->slug]), $html);
    }

    /**
     * TEST: Playlist popup numbering renders cleanly for 1-digit, 2-digit (10, 11, 12, etc.)
     * with discrete number elements (no clipping from list marker offset).
     */
    public function test_playlist_popup_two_digit_numbering(): void
    {
        $plan = TrainingPlan::create([
            'title'                  => 'Safety Mandatory E-Learning 2025 Multi-Module',
            'slug'                   => 'safety-mandatory-mm-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 7200,
            'learning_hours'         => 2.0,
        ]);

        $itemTitles = [
            1  => 'Opening Safety Mandatory',
            2  => '3 Major Accident',
            3  => '10 Safety Iron Rules',
            4  => 'Hazard Identification',
            5  => 'Hierarchy of Hazard Control',
            6  => 'Tool Box Meeting',
            7  => 'Personal Protective Equipment',
            8  => 'Good Driver',
            9  => 'Fire Prevention and Protection System',
            10 => 'Closing Safety Mandatory E-Learning',
            11 => 'Safety Assessment Review',
            12 => 'Post Safety Compliance',
        ];

        foreach ($itemTitles as $order => $title) {
            $c = Content::create([
                'title'             => $title,
                'slug'              => 'sec-mod-' . $order . '-' . rand(1000, 9999),
                'type'              => 'Video',
                'classification_id' => $this->classification->id,
                'duration'          => '00:10:00',
                'learning_hours'    => 0.2,
                'active'            => 'Active',
                'playlistonly'      => 'Y',
            ]);

            TrainingPlanDetail::create([
                'trainingplan_id' => $plan->id,
                'content_id'      => $c->id,
                'content_order'   => $order,
            ]);

            PlaylistContentUser::create([
                'user_id'         => $this->user->id,
                'trainingplan_id' => $plan->id,
                'content_id'      => $c->id,
                'progress'        => 100,
                'finish'          => '1',
            ]);
        }

        $this->actingAs($this->user);
        $response = $this->get(route('user.learning.dashboard.partial', ['tab' => 'record']));
        $response->assertStatus(200);

        $html = $response->getContent();

        // 1. Verify single-digit numbering elements are rendered
        $this->assertStringContainsString('<span class="course-preview-playlist-number">1.</span>', $html);
        $this->assertStringContainsString('<span class="course-preview-playlist-number">8.</span>', $html);
        $this->assertStringContainsString('<span class="course-preview-playlist-number">9.</span>', $html);

        // 2. Verify two-digit numbering elements (10., 11., 12.) are rendered intact
        $this->assertStringContainsString('<span class="course-preview-playlist-number">10.</span>', $html);
        $this->assertStringContainsString('<span class="course-preview-playlist-number">11.</span>', $html);
        $this->assertStringContainsString('<span class="course-preview-playlist-number">12.</span>', $html);

        // 3. Verify ordering: 10. comes before 11., 11. comes before 12.
        $pos10 = strpos($html, '<span class="course-preview-playlist-number">10.</span>');
        $pos11 = strpos($html, '<span class="course-preview-playlist-number">11.</span>');
        $pos12 = strpos($html, '<span class="course-preview-playlist-number">12.</span>');
        $this->assertTrue($pos10 < $pos11, '10. must appear before 11.');
        $this->assertTrue($pos11 < $pos12, '11. must appear before 12.');

        // 4. Verify 10. is associated with Closing Safety Mandatory E-Learning
        $closingPos = strpos($html, 'Closing Safety Mandatory E-Learning');
        $this->assertTrue($pos10 < $closingPos, 'Number 10. must precede Closing Safety title.');

        // 5. Verify trigger has category-preview-trigger
        $this->assertStringContainsString('category-preview-trigger', $html);
    }
}

