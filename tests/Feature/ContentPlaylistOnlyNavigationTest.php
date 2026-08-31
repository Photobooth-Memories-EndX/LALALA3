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
     * CASE 1: playlistonly = 'N'
     * → muncul di All Content.
     */
    public function test_case_1_playlist_only_n_appears_in_all_content(): void
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
        $this->assertStringContainsString(route('content.user.view', $directContent->slug), $html);
    }

    /**
     * CASE 2: playlistonly = 'Y'
     * → tidak muncul di All Content.
     */
    public function test_case_2_playlist_only_y_does_not_appear_in_all_content(): void
    {
        $playlistContent = Content::create([
            'title'             => 'Exclusive Playlist Hidden Video',
            'slug'              => 'exclusive-playlist-hidden-video-' . rand(1000, 9999),
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

        // Contents collection must not contain the playlist-only content
        $contents = $response->viewData('contents');
        $this->assertFalse($contents->pluck('id')->contains($playlistContent->id));

        // Direct view URL of the playlist-only content must not appear in the cards
        $this->assertStringNotContainsString(route('content.user.view', $playlistContent->slug), $html);
        $this->assertStringContainsString('Content not found', $html);
    }

    /**
     * CASE 3: Content playlistonly = 'Y'
     * → tetap dapat diakses dari halaman Playlist.
     */
    public function test_case_3_playlist_only_y_accessible_from_playlist_page(): void
    {
        $playlist = TrainingPlan::create([
            'title'                  => 'QA Test Playlist Package',
            'slug'                   => 'qa-test-playlist-package-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 3600,
            'learning_hours'         => 1.0,
            'has_deadline'           => 0,
        ]);

        $playlistContent = Content::create([
            'title'             => 'Playlist Inner Video Content',
            'slug'              => 'playlist-inner-video-content-' . rand(1000, 9999),
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

        $response = $this->actingAs($this->user)->get(route('playlist.user.view', [
            'slug'    => $playlist->slug,
            'vidslug' => $playlistContent->slug,
        ]));

        $response->assertStatus(200);
        $html = $response->getContent();

        $this->assertStringContainsString($playlistContent->title, $html);
        $this->assertStringContainsString($playlist->title, $html);
    }

    /**
     * CASE 4: Badge Playlist
     * → tidak tampil di All Content.
     */
    public function test_case_4_playlist_badge_not_displayed_in_all_content(): void
    {
        $directDoc = Content::create([
            'title'             => 'Direct Document Without Badge',
            'slug'              => 'direct-doc-without-badge-' . rand(1000, 9999),
            'type'              => 'Document',
            'classification_id' => $this->classification->id,
            'duration'          => '00:10:00',
            'page_duration'     => 0,
            'total_page'        => 5,
            'active'            => 'Active',
            'playlistonly'      => 'N',
            'has_deadline'      => 0,
        ]);

        $response = $this->actingAs($this->user)->get(route('content.user.index', ['search' => $directDoc->title]));

        $response->assertStatus(200);
        $html = $response->getContent();

        $this->assertStringNotContainsString('ti-playlist me-1', $html);
        $this->assertStringNotContainsString('badge bg-green text-white', $html);
    }

    /**
     * CASE 5: Direct Content
     * → tetap mengarah ke /content/{slug}.
     */
    public function test_case_5_direct_content_card_and_cta_route_to_content_slug(): void
    {
        $directContent = Content::create([
            'title'             => 'Direct Target Route Video',
            'slug'              => 'direct-target-route-video-' . rand(1000, 9999),
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
        $this->assertStringContainsString('Start Learning', $html);
        $this->assertStringNotContainsString('modal-select-playlist', $html);
    }

    /**
     * REGRESSION / PROGRESS ISOLATION:
     * Direct learning updates content_users and does NOT write to playlist_content_users
     */
    public function test_progress_context_for_direct_learning(): void
    {
        $content = Content::create([
            'title'             => 'Direct Progress Test Video 3',
            'slug'              => 'direct-progress-test-video-3-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:10:00',
            'active'            => 'Active',
            'playlistonly'      => 'N',
            'has_deadline'      => 0,
        ]);

        // User views direct content
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
