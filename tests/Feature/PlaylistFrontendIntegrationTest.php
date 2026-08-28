<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PlaylistFrontendIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected TrainingPlan $playlistA;
    protected TrainingPlan $playlistB;
    protected Content $contentVideo;
    protected Content $contentDoc;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-hash-frontend-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-F-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->playlistA = TrainingPlan::create([
            'title'                  => 'Playlist A Frontend Test',
            'slug'                   => 'playlist-a-frontend-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 3600,
            'learning_hours'         => 1.0,
            'has_deadline'           => 0,
        ]);

        $this->playlistB = TrainingPlan::create([
            'title'                  => 'Playlist B Frontend Test',
            'slug'                   => 'playlist-b-frontend-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 3600,
            'learning_hours'         => 1.0,
            'has_deadline'           => 0,
        ]);

        $this->contentVideo = Content::create([
            'title'        => 'Content Video Frontend Test',
            'slug'         => 'content-video-fe-' . rand(1000, 9999),
            'type'         => 'Video',
            'duration'     => '00:10:00',
            'active'       => 'Active',
            'playlistonly' => 'N',
            'has_deadline' => 0,
        ]);

        $this->contentDoc = Content::create([
            'title'         => 'Content Doc Frontend Test',
            'slug'          => 'content-doc-fe-' . rand(1000, 9999),
            'type'          => 'Document',
            'duration'      => '00:05:00',
            'page_duration' => 0,
            'total_page'    => 10,
            'active'        => 'Active',
            'playlistonly'  => 'N',
            'has_deadline'  => 0,
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => $this->playlistA->id,
            'content_id'      => $this->contentVideo->id,
            'content_order'   => 0,
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => $this->playlistB->id,
            'content_id'      => $this->contentVideo->id,
            'content_order'   => 0,
        ]);
    }

    /**
     * Test 1 — Direct Content Blade rendered script
     * Must target /content/progress/update and NOT contain trainingPlanId
     */
    public function test_direct_content_view_renders_direct_progress_endpoint_without_trainingplan_id(): void
    {
        $response = $this->actingAs($this->user)->get(route('content.user.view', [
            'slug' => $this->contentVideo->slug,
        ]));

        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString(route('content.user.progress.update'), $content, 'Direct content must target content.user.progress.update endpoint.');
        $this->assertStringNotContainsString('trainingPlanId', $content, 'Direct content view must not supply trainingPlanId.');
    }

    /**
     * Test 2 — Playlist Blade rendered script
     * Must target /playlist/progress/update and include trainingPlanId
     */
    public function test_playlist_view_renders_playlist_progress_endpoint_with_trainingplan_id(): void
    {
        $response = $this->actingAs($this->user)->get(route('playlist.user.view', [
            'slug'    => $this->playlistA->slug,
            'vidslug' => $this->contentVideo->slug,
        ]));

        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString(route('playlist.user.progress.update'), $content);
        $this->assertStringContainsString('trainingPlanId: ' . $this->playlistA->id, $content);
        $this->assertStringContainsString('contentId: ' . $this->contentVideo->id, $content);
    }

    /**
     * Test 3 — Multi-Playlist rendered script isolation
     */
    public function test_multi_playlist_views_render_respective_trainingplan_ids(): void
    {
        // View Playlist A
        $responseA = $this->actingAs($this->user)->get(route('playlist.user.view', [
            'slug'    => $this->playlistA->slug,
            'vidslug' => $this->contentVideo->slug,
        ]));
        $responseA->assertStatus(200);
        $this->assertStringContainsString('trainingPlanId: ' . $this->playlistA->id, $responseA->getContent());

        // View Playlist B
        $responseB = $this->actingAs($this->user)->get(route('playlist.user.view', [
            'slug'    => $this->playlistB->slug,
            'vidslug' => $this->contentVideo->slug,
        ]));
        $responseB->assertStatus(200);
        $this->assertStringContainsString('trainingPlanId: ' . $this->playlistB->id, $responseB->getContent());
    }

    /**
     * Test 4 — Static inspection of learning-progress.js
     */
    public function test_learning_progress_js_file_contains_optional_trainingplan_id(): void
    {
        $jsContent = file_get_contents(public_path('js/learning-progress.js'));

        $this->assertStringContainsString('this.trainingPlanId = options.trainingPlanId || options.trainingplanId || null;', $jsContent);
        $this->assertStringContainsString('if (this.trainingPlanId) {', $jsContent);
        $this->assertStringContainsString('data.trainingplan_id = this.trainingPlanId;', $jsContent);
    }
}
