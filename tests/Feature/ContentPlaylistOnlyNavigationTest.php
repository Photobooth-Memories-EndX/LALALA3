<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Content;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ContentPlaylistOnlyNavigationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Classification $classification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-hash-nav-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-NAV-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->classification = Classification::firstOrCreate(
            ['classification_name' => 'General Training Test'],
            ['slug' => 'general-training-test']
        );
    }

    /**
     * CASE 1: playlistonly = N
     * → card mengarah ke /content/{slug}
     * → tidak ada badge Playlist
     */
    public function test_case_1_direct_content_renders_direct_url_without_playlist_badge(): void
    {
        $directContent = Content::create([
            'title'             => 'Direct Standard Content Video',
            'slug'              => 'direct-standard-video-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:15:00',
            'active'            => 'Active',
            'playlistonly'      => 'N',
            'has_deadline'      => 0,
        ]);

        $response = $this->actingAs($this->user)->get(route('content.user.index', ['search' => $directContent->title]));

        $response->assertStatus(200);
        $html = $response->getContent();

        $expectedDirectUrl = route('content.user.view', $directContent->slug);

        $this->assertStringContainsString($directContent->title, $html);
        $this->assertStringContainsString($expectedDirectUrl, $html);
        $this->assertStringNotContainsString('badge bg-green text-white', $html);
        $this->assertStringNotContainsString('ti-playlist me-1', $html);
        $this->assertStringContainsString('Start Learning', $html);
    }

    /**
     * CASE 2: playlistonly = Y
     * → card mengarah ke /content/{slug}
     * → badge Playlist tetap muncul
     */
    public function test_case_2_playlist_only_content_renders_direct_url_and_shows_playlist_badge(): void
    {
        $playlistContent = Content::create([
            'title'             => 'Playlist Only General Video',
            'slug'              => 'playlist-only-general-video-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:20:00',
            'active'            => 'Active',
            'playlistonly'      => 'Y',
            'has_deadline'      => 0,
        ]);

        $response = $this->actingAs($this->user)->get(route('content.user.index', ['search' => $playlistContent->title]));

        $response->assertStatus(200);
        $html = $response->getContent();

        $expectedDirectUrl = route('content.user.view', $playlistContent->slug);

        // Badge Playlist must appear
        $this->assertStringContainsString('badge bg-green text-white', $html);
        $this->assertStringContainsString('ti-playlist me-1', $html);
        $this->assertStringContainsString('Playlist', $html);

        // Target URL must be direct content route
        $this->assertStringContainsString($expectedDirectUrl, $html);
        $this->assertStringContainsString('Start Learning', $html);
    }

    /**
     * CASE 3: playlistonly = Y + memiliki 1 playlist
     * → tetap /content/{slug}
     * → tidak redirect ke playlist
     */
    public function test_case_3_playlist_only_with_single_playlist_renders_direct_url(): void
    {
        $playlist = TrainingPlan::create([
            'title'                  => 'Single Active Target Playlist',
            'slug'                   => 'single-active-target-playlist-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 3600,
            'learning_hours'         => 1.0,
            'has_deadline'           => 0,
        ]);

        $playlistContent = Content::create([
            'title'             => 'Single Playlist Linked Content',
            'slug'              => 'single-playlist-linked-content-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:20:00',
            'active'            => 'Active',
            'playlistonly'      => 'Y',
            'has_deadline'      => 0,
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => $playlist->id,
            'content_id'      => $playlistContent->id,
            'content_order'   => 0,
        ]);

        $response = $this->actingAs($this->user)->get(route('content.user.index', ['search' => $playlistContent->title]));

        $response->assertStatus(200);
        $html = $response->getContent();

        $expectedDirectUrl = route('content.user.view', $playlistContent->slug);
        $playlistRouteUrl = route('playlist.user.view', ['slug' => $playlist->slug, 'vidslug' => $playlistContent->slug]);

        // Badge Playlist must appear
        $this->assertStringContainsString('badge bg-green text-white', $html);
        $this->assertStringContainsString('ti-playlist me-1', $html);
        $this->assertStringContainsString('Playlist', $html);

        // Card navigation MUST point to direct content, NOT playlist view
        $this->assertStringContainsString($expectedDirectUrl, $html);
        $this->assertStringNotContainsString($playlistRouteUrl, $html);
        $this->assertStringContainsString('Start Learning', $html);
    }

    /**
     * CASE 4: playlistonly = Y + memiliki banyak playlist
     * → tetap /content/{slug}
     * → tidak membuka modal selector
     */
    public function test_case_4_playlist_only_with_multi_playlist_renders_direct_url_without_modal(): void
    {
        $playlistA = TrainingPlan::create([
            'title'                  => 'Multi Target Playlist A',
            'slug'                   => 'multi-target-playlist-a-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 3600,
            'learning_hours'         => 1.0,
            'has_deadline'           => 0,
        ]);

        $playlistB = TrainingPlan::create([
            'title'                  => 'Multi Target Playlist B',
            'slug'                   => 'multi-target-playlist-b-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 3600,
            'learning_hours'         => 1.0,
            'has_deadline'           => 0,
        ]);

        $multiContent = Content::create([
            'title'             => 'Shared Multi Playlist Doc',
            'slug'              => 'shared-multi-playlist-doc-' . rand(1000, 9999),
            'type'              => 'Document',
            'classification_id' => $this->classification->id,
            'duration'          => '00:10:00',
            'page_duration'     => 0,
            'total_page'        => 5,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
            'has_deadline'      => 0,
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => $playlistA->id,
            'content_id'      => $multiContent->id,
            'content_order'   => 0,
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => $playlistB->id,
            'content_id'      => $multiContent->id,
            'content_order'   => 0,
        ]);

        $response = $this->actingAs($this->user)->get(route('content.user.index', ['search' => $multiContent->title]));

        $response->assertStatus(200);
        $html = $response->getContent();

        $expectedDirectUrl = route('content.user.view', $multiContent->slug);

        // Badge Playlist must appear
        $this->assertStringContainsString('badge bg-green text-white', $html);
        $this->assertStringContainsString('ti-playlist me-1', $html);
        $this->assertStringContainsString('Playlist', $html);

        // Target URL must be direct content, no modal trigger
        $this->assertStringContainsString($expectedDirectUrl, $html);
        $this->assertStringNotContainsString('modal-select-playlist', $html);
        $this->assertStringNotContainsString('Pilih Playlist', $html);
        $this->assertStringContainsString('Start Learning', $html);
    }

    /**
     * CASE 5: playlistonly = Y + tidak memiliki playlist aktif
     * → tetap /content/{slug}
     * → tidak menampilkan "Playlist Belum Tersedia"
     * → badge Playlist tetap muncul
     */
    public function test_case_5_playlist_only_without_active_playlist_renders_direct_url_and_badge(): void
    {
        $orphanContent = Content::create([
            'title'             => 'Orphan Playlist Document',
            'slug'              => 'orphan-playlist-document-' . rand(1000, 9999),
            'type'              => 'Document',
            'classification_id' => $this->classification->id,
            'duration'          => '00:10:00',
            'page_duration'     => 0,
            'total_page'        => 5,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
            'has_deadline'      => 0,
        ]);

        $response = $this->actingAs($this->user)->get(route('content.user.index', ['search' => $orphanContent->title]));

        $response->assertStatus(200);
        $html = $response->getContent();

        $expectedDirectUrl = route('content.user.view', $orphanContent->slug);

        // Badge Playlist appears based on playlistonly = Y
        $this->assertStringContainsString('badge bg-green text-white', $html);
        $this->assertStringContainsString('ti-playlist me-1', $html);
        $this->assertStringContainsString('Playlist', $html);

        // Direct Content target URL and CTA enabled
        $this->assertStringContainsString($expectedDirectUrl, $html);
        $this->assertStringContainsString('Start Learning', $html);
        $this->assertStringNotContainsString('Playlist Belum Tersedia', $html);
    }

    /**
     * CASE 6: Badge Consistency across Video and Document
     */
    public function test_badge_consistency_between_video_and_document(): void
    {
        $videoContent = Content::create([
            'title'             => 'Consistency Check Video',
            'slug'              => 'consistency-check-video-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:10:00',
            'active'            => 'Active',
            'playlistonly'      => 'Y',
            'has_deadline'      => 0,
        ]);

        $docContent = Content::create([
            'title'             => 'Consistency Check Document',
            'slug'              => 'consistency-check-doc-' . rand(1000, 9999),
            'type'              => 'Document',
            'classification_id' => $this->classification->id,
            'duration'          => '00:10:00',
            'page_duration'     => 0,
            'total_page'        => 5,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
            'has_deadline'      => 0,
        ]);

        $videoRes = $this->actingAs($this->user)->get(route('content.user.index', ['search' => $videoContent->title]));
        $videoRes->assertStatus(200);
        $videoHtml = $videoRes->getContent();

        $this->assertStringContainsString('badge bg-green text-white', $videoHtml);
        $this->assertStringContainsString('ti-playlist me-1', $videoHtml);
        $this->assertStringContainsString(route('content.user.view', $videoContent->slug), $videoHtml);

        $docRes = $this->actingAs($this->user)->get(route('content.user.index', ['search' => $docContent->title]));
        $docRes->assertStatus(200);
        $docHtml = $docRes->getContent();

        $this->assertStringContainsString('badge bg-green text-white', $docHtml);
        $this->assertStringContainsString('ti-playlist me-1', $docHtml);
        $this->assertStringContainsString(route('content.user.view', $docContent->slug), $docHtml);
    }

    /**
     * CASE 7: Progress Context for Direct Content
     */
    public function test_progress_context_for_direct_learning(): void
    {
        $content = Content::create([
            'title'             => 'Direct Progress Test Video',
            'slug'              => 'direct-progress-test-video-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:10:00',
            'active'            => 'Active',
            'playlistonly'      => 'Y',
            'has_deadline'      => 0,
        ]);

        // User views the direct content
        $viewResponse = $this->actingAs($this->user)->get(route('content.user.view', $content->slug));
        $viewResponse->assertStatus(200);

        // User updates progress via direct content endpoint
        $progressResponse = $this->actingAs($this->user)->post(route('content.user.progress.update'), [
            'content_id'       => $content->id,
            'type'             => 'video',
            'current_time'     => 120,
            'duration_seconds' => 600,
            'status'           => 'in_progress',
        ]);
        $progressResponse->assertStatus(200);

        // Assert record exists in content_users
        $this->assertDatabaseHas('content_users', [
            'user_id'    => $this->user->id,
            'content_id' => $content->id,
        ]);

        // Assert NO record was created in playlist_content_users
        $this->assertDatabaseMissing('playlist_content_users', [
            'user_id'    => $this->user->id,
            'content_id' => $content->id,
        ]);
    }
}
