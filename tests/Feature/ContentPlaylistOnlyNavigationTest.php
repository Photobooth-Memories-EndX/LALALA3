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
     * Test 1: Direct Content (playlistonly = 'N') does NOT show badge and links to /content/{slug}
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
        $this->assertStringNotContainsString('Playlist Only', $html);
    }

    /**
     * Test 2: Single Playlist Content (playlistonly = 'Y') shows badge and links to /playlist/{tpSlug}/{cSlug}
     */
    public function test_single_playlist_content_shows_badge_and_links_directly_to_playlist(): void
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

        $this->assertStringContainsString('Playlist Only', $html);
        $this->assertStringContainsString($expectedUrl, $html);
        $this->assertStringNotContainsString(route('content.user.view', $playlistContent->slug), $html);
    }

    /**
     * Test 3: Multi-Playlist Content (playlistonly = 'Y') shows badge and modal selector
     */
    public function test_multi_playlist_content_shows_badge_and_modal_selector(): void
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

        $this->assertStringContainsString('Playlist Only', $html);
        $this->assertStringContainsString('data-bs-target="#' . $modalId . '"', $html);
        $this->assertStringContainsString('Pilih Playlist (2)', $html);
        $this->assertStringContainsString($urlA, $html);
        $this->assertStringContainsString($urlB, $html);
        $this->assertStringNotContainsString(route('content.user.view', $multiContent->slug), $html);
    }

    /**
     * Test 4: Orphan Playlist Content (playlistonly = 'Y' but 0 active playlists) shows badge & disabled CTA
     */
    public function test_orphan_playlist_content_shows_badge_and_disabled_button(): void
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

        $this->assertStringContainsString('Playlist Only', $html);
        $this->assertStringContainsString('Playlist Belum Tersedia', $html);
        $this->assertStringNotContainsString(route('content.user.view', $orphanContent->slug), $html);
    }
}
