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
     * TEST 1 — Direct Content (playlistonly = 'N')
     */
    public function test_direct_content_renders_standard_card_without_playlist_badge(): void
    {
        $directContent = Content::create([
            'title'             => 'Standard Direct Video',
            'slug'              => 'standard-direct-video-' . rand(1000, 9999),
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

        $this->assertStringContainsString($directContent->title, $html);
        $this->assertStringContainsString(route('content.user.view', $directContent->slug), $html);
        $this->assertStringNotContainsString('badge bg-green text-white', $html);
        $this->assertStringNotContainsString('ti-playlist me-1', $html);
    }

    /**
     * TEST 2 — Playlist Only + No Available Playlist (playlistonly = 'Y', 0 active playlists)
     * Expected: NO Playlist badge, Direct Content fallback URL, CTA enabled.
     */
    public function test_orphan_playlist_content_has_no_badge_and_falls_back_to_direct_content(): void
    {
        $orphanContent = Content::create([
            'title'             => 'Orphan Playlist Doc',
            'slug'              => 'orphan-playlist-doc-' . rand(1000, 9999),
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

        // Badge must NOT appear
        $this->assertStringNotContainsString('badge bg-green text-white', $html);
        $this->assertStringNotContainsString('ti-playlist me-1', $html);
        $this->assertStringNotContainsString('Playlist Only', $html);

        // Direct Content fallback must work with enabled CTA
        $this->assertStringContainsString('Start Learning', $html);
        $this->assertStringContainsString($expectedDirectUrl, $html);
        $this->assertStringNotContainsString('Playlist Belum Tersedia', $html);
    }

    /**
     * TEST 3 — Playlist Only + One Playlist (playlistonly = 'Y', 1 active playlist)
     * Expected: Green Playlist badge exists, text is 'Playlist' (not 'Playlist Only'), links directly to playlist.
     */
    public function test_single_playlist_content_shows_green_playlist_badge_and_links_to_playlist(): void
    {
        $playlist = TrainingPlan::create([
            'title'                  => 'Single Target Playlist',
            'slug'                   => 'single-target-playlist-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 3600,
            'learning_hours'         => 1.0,
            'has_deadline'           => 0,
        ]);

        $playlistContent = Content::create([
            'title'             => 'Exclusive Playlist Video 1',
            'slug'              => 'exclusive-playlist-video-1-' . rand(1000, 9999),
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

        $expectedUrl = route('playlist.user.view', ['slug' => $playlist->slug, 'vidslug' => $playlistContent->slug]);

        // Green Playlist badge checks
        $this->assertStringContainsString('badge bg-green text-white', $html);
        $this->assertStringContainsString('ti-playlist me-1', $html);
        $this->assertStringContainsString('Playlist', $html);
        $this->assertStringNotContainsString('Playlist Only', $html);

        // URL check
        $this->assertStringContainsString($expectedUrl, $html);
        $this->assertStringNotContainsString(route('content.user.view', $playlistContent->slug), $html);
    }

    /**
     * TEST 4 — Playlist Only + Multiple Playlists (playlistonly = 'Y', >= 2 active playlists)
     * Expected: Green Playlist badge exists, modal selector triggers.
     */
    public function test_multi_playlist_content_shows_green_badge_and_modal_selector(): void
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

        $modalId = 'modal-select-playlist-' . $multiContent->id;
        $urlA = route('playlist.user.view', ['slug' => $playlistA->slug, 'vidslug' => $multiContent->slug]);
        $urlB = route('playlist.user.view', ['slug' => $playlistB->slug, 'vidslug' => $multiContent->slug]);

        $this->assertStringContainsString('badge bg-green text-white', $html);
        $this->assertStringContainsString('ti-playlist me-1', $html);
        $this->assertStringContainsString('data-bs-target="#' . $modalId . '"', $html);
        $this->assertStringContainsString('Pilih Playlist (2)', $html);
        $this->assertStringContainsString($urlA, $html);
        $this->assertStringContainsString($urlB, $html);
        $this->assertStringNotContainsString(route('content.user.view', $multiContent->slug), $html);
    }

    /**
     * TEST 5 — Badge Consistency across Video and Document
     */
    public function test_badge_consistency_between_video_and_document(): void
    {
        $playlist = TrainingPlan::create([
            'title'                  => 'Shared Playlist Consistency',
            'slug'                   => 'shared-playlist-consistency-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 3600,
            'learning_hours'         => 1.0,
            'has_deadline'           => 0,
        ]);

        $videoContent = Content::create([
            'title'             => 'Consistency Video',
            'slug'              => 'consistency-video-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:10:00',
            'active'            => 'Active',
            'playlistonly'      => 'Y',
            'has_deadline'      => 0,
        ]);

        $docContent = Content::create([
            'title'             => 'Consistency Document',
            'slug'              => 'consistency-doc-' . rand(1000, 9999),
            'type'              => 'Document',
            'classification_id' => $this->classification->id,
            'duration'          => '00:10:00',
            'page_duration'     => 0,
            'total_page'        => 5,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
            'has_deadline'      => 0,
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $playlist->id, 'content_id' => $videoContent->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $playlist->id, 'content_id' => $docContent->id, 'content_order' => 1]);

        $videoRes = $this->actingAs($this->user)->get(route('content.user.index', ['search' => $videoContent->title]));
        $videoRes->assertStatus(200);
        $videoHtml = $videoRes->getContent();

        $this->assertStringContainsString('badge bg-green text-white', $videoHtml);
        $this->assertStringContainsString('ti-playlist me-1', $videoHtml);
        $this->assertStringNotContainsString('Playlist Only', $videoHtml);

        $docRes = $this->actingAs($this->user)->get(route('content.user.index', ['search' => $docContent->title]));
        $docRes->assertStatus(200);
        $docHtml = $docRes->getContent();

        $this->assertStringContainsString('badge bg-green text-white', $docHtml);
        $this->assertStringContainsString('ti-playlist me-1', $docHtml);
        $this->assertStringNotContainsString('Playlist Only', $docHtml);
    }

    /**
     * TEST 6 — Progress Context for Direct Fallback
     */
    public function test_progress_context_for_direct_fallback(): void
    {
        $orphanContent = Content::create([
            'title'             => 'Fallback Direct Learning Content 2',
            'slug'              => 'fallback-direct-learning-content-2-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:10:00',
            'active'            => 'Active',
            'playlistonly'      => 'Y',
            'has_deadline'      => 0,
        ]);

        // User views the fallback direct content
        $viewResponse = $this->actingAs($this->user)->get(route('content.user.view', $orphanContent->slug));
        $viewResponse->assertStatus(200);

        // User updates progress via direct content endpoint
        $progressResponse = $this->actingAs($this->user)->post(route('content.user.progress.update'), [
            'content_id'       => $orphanContent->id,
            'type'             => 'video',
            'current_time'     => 120,
            'duration_seconds' => 600,
            'status'           => 'in_progress',
        ]);
        $progressResponse->assertStatus(200);

        // Assert record exists in content_users
        $this->assertDatabaseHas('content_users', [
            'user_id'    => $this->user->id,
            'content_id' => $orphanContent->id,
        ]);

        // Assert NO record was created in playlist_content_users
        $this->assertDatabaseMissing('playlist_content_users', [
            'user_id'    => $this->user->id,
            'content_id' => $orphanContent->id,
        ]);
    }
}
