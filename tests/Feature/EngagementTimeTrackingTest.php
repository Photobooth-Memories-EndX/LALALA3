<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\RoleAccessPermission;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use App\Services\EngagementTime\EngagementTimeService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class EngagementTimeTrackingTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user1;
    protected User $user2;
    protected Content $targetContent;
    protected Content $nonTargetContent;
    protected TrainingPlan $targetPlaylist;
    protected Content $targetPlaylistContent;
    protected TrainingPlan $nonTargetPlaylist;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->user1 = User::factory()->create([
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-hash-' . Str::uuid(),
            'sap_code'   => 'SAP-TEST-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->user2 = User::factory()->create([
            'team'       => 'Marketing',
            'department' => 'Commercial',
            'division'   => 'Sales',
            'ssohash'    => 'test-hash-' . Str::uuid(),
            'sap_code'   => 'SAP-TEST-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_USER, 'module_key' => 'playlist_debug_tools'],
            ['can_access' => true]
        );

        // Find or create the target content matching the exact canonical slug
        $this->targetContent = Content::firstOrCreate(
            ['slug' => EngagementTimeService::TARGET_CONTENT_SLUG],
            [
                'title'          => 'Penanggulangan HIV/AIDS di Tempat Kerja (Part 2)',
                'type'           => 'Video',
                'active'         => 'Active',
                'duration'       => '00:10:00',
                'learning_hours' => 0.5,
            ]
        );
        $this->targetContent->update(['active' => 'Active']);

        // Find or create the target playlist and its child content matching exact slugs
        $this->targetPlaylist = TrainingPlan::firstOrCreate(
            ['slug' => EngagementTimeService::TARGET_PLAYLIST_SLUG],
            [
                'title'          => 'PTKP Global Assessment 2026 for Job Clan M/S & T',
                'active'         => 'Active',
                'learning_hours' => 1.0,
            ]
        );

        $this->targetPlaylistContent = Content::firstOrCreate(
            ['slug' => EngagementTimeService::TARGET_PLAYLIST_CONTENT_SLUG],
            [
                'title'          => '[HR] A.1 - Budaya Perusahaan & Pengembangan Organisasi [2025]',
                'type'           => 'Document',
                'active'         => 'Active',
                'duration'       => '00:15:00',
                'learning_hours' => 0.5,
            ]
        );

        TrainingPlanDetail::firstOrCreate([
            'trainingplan_id' => $this->targetPlaylist->id,
            'content_id'      => $this->targetPlaylistContent->id,
        ], [
            'content_order'   => 1,
        ]);

        // Non-target items for regression tests
        $this->nonTargetContent = Content::create([
            'title'          => 'Random Non Target Content',
            'slug'           => 'random-non-target-content-' . Str::random(8),
            'type'           => 'Video',
            'active'         => 'Active',
            'duration'       => '00:10:00',
            'learning_hours' => 0.5,
        ]);

        $this->nonTargetPlaylist = TrainingPlan::create([
            'title'          => 'Random Non Target Playlist',
            'slug'           => 'random-non-target-playlist-' . Str::random(8),
            'active'         => 'Active',
            'learning_hours' => 1.0,
        ]);
    }

    /** @test */
    public function invalid_delta_values_are_strictly_rejected(): void
    {
        $this->actingAs($this->user1);

        // Delta 0 rejected
        $this->postJson(route('engagement-time.heartbeat'), [
            'target_type'   => 'content',
            'content_id'    => $this->targetContent->id,
            'delta_seconds' => 0,
            'heartbeat_id'  => (string) Str::uuid(),
        ])->assertStatus(422);

        // Negative delta rejected
        $this->postJson(route('engagement-time.heartbeat'), [
            'target_type'   => 'content',
            'content_id'    => $this->targetContent->id,
            'delta_seconds' => -10,
            'heartbeat_id'  => (string) Str::uuid(),
        ])->assertStatus(422);

        // Delta exceeding MAX_DELTA_SECONDS (120) rejected
        $this->postJson(route('engagement-time.heartbeat'), [
            'target_type'   => 'content',
            'content_id'    => $this->targetContent->id,
            'delta_seconds' => 121,
            'heartbeat_id'  => (string) Str::uuid(),
        ])->assertStatus(422);

        // Non-integer string delta rejected
        $this->postJson(route('engagement-time.heartbeat'), [
            'target_type'   => 'content',
            'content_id'    => $this->targetContent->id,
            'delta_seconds' => 'invalid_delta',
            'heartbeat_id'  => (string) Str::uuid(),
        ])->assertStatus(422);
    }

    /** @test */
    public function unauthenticated_requests_are_rejected(): void
    {
        $response = $this->postJson(route('engagement-time.heartbeat'), [
            'target_type'   => 'content',
            'content_id'    => $this->targetContent->id,
            'delta_seconds' => 15,
            'heartbeat_id'  => (string) Str::uuid(),
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function non_target_content_is_strictly_forbidden(): void
    {
        $this->actingAs($this->user1);

        $response = $this->postJson(route('engagement-time.heartbeat'), [
            'target_type'   => 'content',
            'content_id'    => $this->nonTargetContent->id,
            'delta_seconds' => 15,
            'heartbeat_id'  => (string) Str::uuid(),
        ]);

        $response->assertStatus(403);
        $response->assertJson(['success' => false]);
    }

    /** @test */
    public function non_target_playlist_is_strictly_forbidden(): void
    {
        $this->actingAs($this->user1);

        $response = $this->postJson(route('engagement-time.heartbeat'), [
            'target_type'     => 'playlist',
            'trainingplan_id' => $this->nonTargetPlaylist->id,
            'content_id'      => $this->targetPlaylistContent->id,
            'delta_seconds'   => 15,
            'heartbeat_id'    => (string) Str::uuid(),
        ]);

        $response->assertStatus(403);
        $response->assertJson(['success' => false]);
    }

    /** @test */
    public function target_content_accumulates_afk_duration_seconds_accurately(): void
    {
        $this->actingAs($this->user1);

        $initialAfk = (int) (ContentUser::where('user_id', $this->user1->id)
            ->where('content_id', $this->targetContent->id)
            ->value('afk_duration_seconds') ?? 0);

        $response = $this->postJson(route('engagement-time.heartbeat'), [
            'target_type'   => 'content',
            'content_id'    => $this->targetContent->id,
            'delta_seconds' => 15,
            'heartbeat_id'  => (string) Str::uuid(),
        ]);

        $response->assertOk();
        $response->assertJson([
            'success'                  => true,
            'total_engagement_seconds' => $initialAfk + 15,
            'afk_duration_seconds'     => $initialAfk + 15,
        ]);

        $savedAfk = (int) ContentUser::where('user_id', $this->user1->id)
            ->where('content_id', $this->targetContent->id)
            ->value('afk_duration_seconds');

        $this->assertEquals($initialAfk + 15, $savedAfk);
    }

    /** @test */
    public function target_playlist_accumulates_afk_duration_seconds_accurately(): void
    {
        $this->actingAs($this->user1);

        $initialAfk = (int) (PlaylistContentUser::where('user_id', $this->user1->id)
            ->where('trainingplan_id', $this->targetPlaylist->id)
            ->where('content_id', $this->targetPlaylistContent->id)
            ->value('afk_duration_seconds') ?? 0);

        $response = $this->postJson(route('engagement-time.heartbeat'), [
            'target_type'     => 'playlist',
            'trainingplan_id' => $this->targetPlaylist->id,
            'content_id'      => $this->targetPlaylistContent->id,
            'delta_seconds'   => 20,
            'heartbeat_id'    => (string) Str::uuid(),
        ]);

        $response->assertOk();
        $response->assertJson([
            'success'                  => true,
            'total_engagement_seconds' => $initialAfk + 20,
            'afk_duration_seconds'     => $initialAfk + 20,
        ]);

        $savedAfk = (int) PlaylistContentUser::where('user_id', $this->user1->id)
            ->where('trainingplan_id', $this->targetPlaylist->id)
            ->where('content_id', $this->targetPlaylistContent->id)
            ->value('afk_duration_seconds');

        $this->assertEquals($initialAfk + 20, $savedAfk);
    }

    /** @test */
    public function delta_idempotency_prevents_double_counting_on_duplicate_heartbeat(): void
    {
        $this->actingAs($this->user1);

        $heartbeatId = (string) Str::uuid();

        // First attempt
        $res1 = $this->postJson(route('engagement-time.heartbeat'), [
            'target_type'   => 'content',
            'content_id'    => $this->targetContent->id,
            'delta_seconds' => 15,
            'heartbeat_id'  => $heartbeatId,
        ]);
        $res1->assertOk();
        $total1 = $res1->json('total_engagement_seconds');

        // Duplicate delivery with identical heartbeat_id
        $res2 = $this->postJson(route('engagement-time.heartbeat'), [
            'target_type'   => 'content',
            'content_id'    => $this->targetContent->id,
            'delta_seconds' => 15,
            'heartbeat_id'  => $heartbeatId,
        ]);
        $res2->assertOk();
        $res2->assertJson(['idempotent' => true]);
        $total2 = $res2->json('total_engagement_seconds');

        $this->assertEquals($total1, $total2, 'Duplicate heartbeat must not increase total engagement time.');

        $dbTotal = (int) ContentUser::where('user_id', $this->user1->id)
            ->where('content_id', $this->targetContent->id)
            ->value('afk_duration_seconds');

        $this->assertEquals($total1, $dbTotal);
    }

    /** @test */
    public function multiple_distinct_heartbeats_accumulate_safely(): void
    {
        $this->actingAs($this->user1);

        $initialAfk = (int) (ContentUser::where('user_id', $this->user1->id)
            ->where('content_id', $this->targetContent->id)
            ->value('afk_duration_seconds') ?? 0);

        // Heartbeat 1 (+10s)
        $this->postJson(route('engagement-time.heartbeat'), [
            'target_type'   => 'content',
            'content_id'    => $this->targetContent->id,
            'delta_seconds' => 10,
            'heartbeat_id'  => (string) Str::uuid(),
        ])->assertOk();

        // Heartbeat 2 (+15s)
        $this->postJson(route('engagement-time.heartbeat'), [
            'target_type'   => 'content',
            'content_id'    => $this->targetContent->id,
            'delta_seconds' => 15,
            'heartbeat_id'  => (string) Str::uuid(),
        ])->assertOk();

        // Heartbeat 3 (+5s)
        $this->postJson(route('engagement-time.heartbeat'), [
            'target_type'   => 'content',
            'content_id'    => $this->targetContent->id,
            'delta_seconds' => 5,
            'heartbeat_id'  => (string) Str::uuid(),
        ])->assertOk();

        $dbTotal = (int) ContentUser::where('user_id', $this->user1->id)
            ->where('content_id', $this->targetContent->id)
            ->value('afk_duration_seconds');

        $this->assertEquals($initialAfk + 30, $dbTotal);
    }

    /** @test */
    public function two_different_users_do_not_interfere_with_each_other(): void
    {
        // User 1 accumulates 15s
        $this->actingAs($this->user1);
        $this->postJson(route('engagement-time.heartbeat'), [
            'target_type'   => 'content',
            'content_id'    => $this->targetContent->id,
            'delta_seconds' => 15,
            'heartbeat_id'  => (string) Str::uuid(),
        ])->assertOk();

        // User 2 accumulates 25s
        $this->actingAs($this->user2);
        $this->postJson(route('engagement-time.heartbeat'), [
            'target_type'   => 'content',
            'content_id'    => $this->targetContent->id,
            'delta_seconds' => 25,
            'heartbeat_id'  => (string) Str::uuid(),
        ])->assertOk();

        $user1Afk = (int) ContentUser::where('user_id', $this->user1->id)
            ->where('content_id', $this->targetContent->id)
            ->value('afk_duration_seconds');

        $user2Afk = (int) ContentUser::where('user_id', $this->user2->id)
            ->where('content_id', $this->targetContent->id)
            ->value('afk_duration_seconds');

        $this->assertEquals(15, $user1Afk);
        $this->assertEquals(25, $user2Afk);
    }

    /** @test */
    public function engagement_time_does_not_mutate_learning_hours_progress_or_watch_time(): void
    {
        $this->actingAs($this->user1);

        $cu = ContentUser::firstOrCreate(
            ['user_id' => $this->user1->id, 'content_id' => $this->targetContent->id],
            [
                'progress'             => 45,
                'current_time'         => 120,
                'actual_watch_seconds' => 100,
                'learning_hours'       => 0.2500,
                'finish'               => '0',
                'learning_status'      => 'in_progress',
            ]
        );

        $snapshotBefore = ContentUser::where('id', $cu->id)->first()->only([
            'progress',
            'current_time',
            'actual_watch_seconds',
            'learning_hours',
            'finish',
            'learning_status',
            'completed_at',
        ]);

        $this->postJson(route('engagement-time.heartbeat'), [
            'target_type'   => 'content',
            'content_id'    => $this->targetContent->id,
            'delta_seconds' => 15,
            'heartbeat_id'  => (string) Str::uuid(),
        ])->assertOk();

        $snapshotAfter = ContentUser::where('id', $cu->id)->first()->only([
            'progress',
            'current_time',
            'actual_watch_seconds',
            'learning_hours',
            'finish',
            'learning_status',
            'completed_at',
        ]);

        $this->assertEquals(
            $snapshotBefore,
            $snapshotAfter,
            'Progress, watch time, learning hours, and status must remain completely unaltered.'
        );
    }

    /** @test */
    public function target_content_view_renders_engagement_time_component(): void
    {
        $this->actingAs($this->user1);

        $response = $this->get(route('content.user.view', $this->targetContent->slug));
        $response->assertOk();
        $response->assertSee('id="totalEngagementCard"', false);
        $response->assertSee('Total Engagement Time', false);
        $response->assertSee('engagement-time-tracker.js', false);
    }

    /** @test */
    public function non_target_content_view_does_not_render_engagement_time_component(): void
    {
        $this->actingAs($this->user1);

        $response = $this->get(route('content.user.view', $this->nonTargetContent->slug));
        $response->assertOk();
        $response->assertDontSee('id="totalEngagementCard"', false);
        $response->assertDontSee('Total Engagement Time', false);
        $response->assertDontSee('engagement-time-tracker.js', false);
    }

    /** @test */
    public function target_playlist_view_renders_engagement_time_component(): void
    {
        $this->actingAs($this->user1);

        $response = $this->get(route('playlist.user.view', [
            'slug'    => $this->targetPlaylist->slug,
            'vidslug' => $this->targetPlaylistContent->slug,
        ]));

        $response->assertOk();
        $response->assertSee('id="totalEngagementCard"', false);
        $response->assertSee('Total Engagement Time', false);
        $response->assertSee('engagement-time-tracker.js', false);
    }

    /** @test */
    public function non_target_playlist_view_does_not_render_engagement_time_component(): void
    {
        $this->actingAs($this->user1);

        // Link non-target content to non-target playlist
        TrainingPlanDetail::create([
            'trainingplan_id' => $this->nonTargetPlaylist->id,
            'content_id'      => $this->nonTargetContent->id,
            'content_order'   => 1,
        ]);

        $response = $this->get(route('playlist.user.view', [
            'slug'    => $this->nonTargetPlaylist->slug,
            'vidslug' => $this->nonTargetContent->slug,
        ]));

        $response->assertOk();
        $response->assertDontSee('id="totalEngagementCard"', false);
        $response->assertDontSee('Total Engagement Time', false);
        $response->assertDontSee('engagement-time-tracker.js', false);
    }

    /** @test */
    public function target_content_view_hides_engagement_time_component_when_permission_revoked(): void
    {
        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_USER, 'module_key' => 'playlist_debug_tools'],
            ['can_access' => false]
        );

        $this->actingAs($this->user1);

        $response = $this->get(route('content.user.view', $this->targetContent->slug));
        $response->assertOk();
        $response->assertDontSee('id="totalEngagementCard"', false);
        $response->assertDontSee('Total Engagement Time', false);
        $response->assertDontSee('engagement-time-tracker.js', false);
    }

    /** @test */
    public function target_playlist_view_hides_engagement_time_component_when_permission_revoked(): void
    {
        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_USER, 'module_key' => 'playlist_debug_tools'],
            ['can_access' => false]
        );

        $this->actingAs($this->user1);

        $response = $this->get(route('playlist.user.view', [
            'slug'    => $this->targetPlaylist->slug,
            'vidslug' => $this->targetPlaylistContent->slug,
        ]));

        $response->assertOk();
        $response->assertDontSee('id="totalEngagementCard"', false);
        $response->assertDontSee('Total Engagement Time', false);
        $response->assertDontSee('engagement-time-tracker.js', false);
    }
}
