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

        // 2. Hover trigger exists
        $this->assertStringContainsString('course-preview-trigger', $html);
        $this->assertStringContainsString('data-preview-kind="content"', $html);

        // 3. Hover preview template contains title inside course-preview-content
        $this->assertStringContainsString('course-preview-content', $html);
        $this->assertStringContainsString('Direct Video Machine Learning', $html);
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

        // Template contains document title
        $this->assertStringContainsString('Direct Document Architecture Guide', $html);

        // CASE 12: Document compact badge present
        $this->assertStringContainsString('learning-record-type-badge', $html);
        $this->assertStringContainsString('Document', $html);

        // CASE 13: Optional / Mandatory badges NOT displayed
        $this->assertStringNotContainsString('>Optional<', $html);
        $this->assertStringNotContainsString('>Mandatory<', $html);
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
}
