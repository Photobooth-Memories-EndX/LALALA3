<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LearningRecordCompletionTimestampFreezeTest extends TestCase
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
            'ssohash'    => 'test-hash-freeze-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-FRZ-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->classification = Classification::firstOrCreate(
            ['classification_name' => 'Freeze Test Classification'],
            ['slug' => 'freeze-test-classification']
        );
    }

    /**
     * Scenario A: Reopening 100% completed Content preserves original completion timestamp (T1)
     * and does NOT jump to the top of Learning Record.
     */
    public function test_content_reopen_preserves_completion_timestamp_and_learning_record_order(): void
    {
        $t1 = Carbon::parse('2026-05-10 10:30:00');
        $tNewer = Carbon::parse('2026-05-10 12:00:00');
        $t2 = Carbon::parse('2026-05-10 14:00:00');

        // Content A (completed earlier at T1)
        $contentA = Content::create([
            'title'             => 'Content A Older Completion',
            'slug'              => 'content-a-' . rand(1000, 9999),
            'type'              => 'video',
            'duration'          => '00:10:00',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $cuA = new ContentUser([
            'user_id'              => $this->user->id,
            'content_id'           => $contentA->id,
            'progress'             => 100,
            'finish'               => '1',
            'current_time'         => 600,
            'duration_seconds'     => 600,
            'actual_watch_seconds' => 600,
            'completed_at'         => $t1,
        ]);
        $cuA->save();
        $cuA->updated_at = $t1;
        $cuA->saveQuietly();

        // Content B (completed later at TNewer - should remain on top)
        $contentB = Content::create([
            'title'             => 'Content B Newer Completion',
            'slug'              => 'content-b-' . rand(1000, 9999),
            'type'              => 'video',
            'duration'          => '00:10:00',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $cuB = new ContentUser([
            'user_id'              => $this->user->id,
            'content_id'           => $contentB->id,
            'progress'             => 100,
            'finish'               => '1',
            'current_time'         => 600,
            'duration_seconds'     => 600,
            'actual_watch_seconds' => 600,
            'completed_at'         => $tNewer,
        ]);
        $cuB->save();
        $cuB->updated_at = $tNewer;
        $cuB->saveQuietly();

        // User reopens Content A at T2
        Carbon::setTestNow($t2);

        $this->actingAs($this->user);

        // Tracking heartbeat runs legitimately on reopen
        $response = $this->postJson(route('content.user.progress.update'), [
            'content_id'           => $contentA->id,
            'type'                 => 'video',
            'current_time'         => 300,
            'duration_seconds'     => 600,
            'delta_seconds'        => 10,
            'heartbeat_id'         => 'hb-reopen-' . rand(1000, 9999),
            'status'               => 'in_progress',
        ]);

        $response->assertStatus(200);

        // 1. Verify DB record: completed_at MUST still be T1, but actual_watch_seconds legitimately increased
        $cuAFresh = ContentUser::find($cuA->id);
        $this->assertEquals($t1->format('Y-m-d H:i:s'), $cuAFresh->completed_at->format('Y-m-d H:i:s'));
        $this->assertEquals(610, (int) $cuAFresh->actual_watch_seconds);
        $this->assertEquals(100, (int) $cuAFresh->progress);
        $this->assertEquals('1', (string) $cuAFresh->finish);

        // 2. Fetch Learning Record
        $recordResponse = $this->get(route('user.learning.dashboard.partial', ['tab' => 'record']));
        $recordResponse->assertStatus(200);

        $html = $recordResponse->getContent();

        // Finish date of Content A in Learning Record MUST still be T1
        $this->assertStringContainsString($t1->format('d/m/y H:i:s'), $html);
        $this->assertStringNotContainsString($t2->format('d/m/y H:i:s'), $html);

        // Content B (12:00) MUST appear before Content A (10:30) in HTML (Content A did NOT jump to top)
        $posB = strpos($html, 'Content B Newer Completion');
        $posA = strpos($html, 'Content A Older Completion');
        $this->assertNotFalse($posB, 'Content B should be present');
        $this->assertNotFalse($posA, 'Content A should be present');
        $this->assertLessThan($posA, $posB, 'Content B (newer) must appear before Content A (older) even after Content A is reopened');

        Carbon::setTestNow();
    }

    /**
     * Scenario B: Reopening a completed Playlist preserves its original completion timestamp
     * and does NOT jump to the top of Learning Record.
     */
    public function test_playlist_reopen_preserves_completion_timestamp_and_learning_record_order(): void
    {
        $t1 = Carbon::parse('2026-05-10 10:30:00');
        $tNewer = Carbon::parse('2026-05-10 12:00:00');
        $t2 = Carbon::parse('2026-05-10 14:00:00');

        // Playlist A with 2 contents (completed at T1)
        $planA = TrainingPlan::create([
            'title'                  => 'Playlist A Older Completion',
            'slug'                   => 'playlist-a-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 1200,
        ]);

        $c1 = Content::create([
            'title'             => 'Playlist A Content 1',
            'slug'              => 'pa-c1-' . rand(1000, 9999),
            'type'              => 'video',
            'duration'          => '00:10:00',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);
        $c2 = Content::create([
            'title'             => 'Playlist A Content 2',
            'slug'              => 'pa-c2-' . rand(1000, 9999),
            'type'              => 'video',
            'duration'          => '00:10:00',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $planA->id, 'content_id' => $c1->id, 'content_order' => 1]);
        TrainingPlanDetail::create(['trainingplan_id' => $planA->id, 'content_id' => $c2->id, 'content_order' => 2]);

        $pcu1 = new PlaylistContentUser([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $planA->id,
            'content_id'           => $c1->id,
            'progress'             => 100,
            'finish'               => '1',
            'current_time'         => 600,
            'duration_seconds'     => 600,
            'actual_watch_seconds' => 600,
            'completed_at'         => Carbon::parse('2026-05-10 10:00:00'),
        ]);
        $pcu1->save();

        $pcu2 = new PlaylistContentUser([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $planA->id,
            'content_id'           => $c2->id,
            'progress'             => 100,
            'finish'               => '1',
            'current_time'         => 600,
            'duration_seconds'     => 600,
            'actual_watch_seconds' => 600,
            'completed_at'         => $t1,
        ]);
        $pcu2->save();

        // Standalone Content C completed at TNewer
        $contentC = Content::create([
            'title'             => 'Content C Newer Standalone',
            'slug'              => 'content-c-' . rand(1000, 9999),
            'type'              => 'video',
            'duration'          => '00:10:00',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $cuC = new ContentUser([
            'user_id'              => $this->user->id,
            'content_id'           => $contentC->id,
            'progress'             => 100,
            'finish'               => '1',
            'current_time'         => 600,
            'duration_seconds'     => 600,
            'actual_watch_seconds' => 600,
            'completed_at'         => $tNewer,
        ]);
        $cuC->save();
        $cuC->updated_at = $tNewer;
        $cuC->saveQuietly();

        // User reopens Playlist A item 1 at T2
        Carbon::setTestNow($t2);

        $this->actingAs($this->user);

        $response = $this->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'      => $planA->id,
            'content_id'           => $c1->id,
            'type'                 => 'video',
            'current_time'         => 300,
            'duration_seconds'     => 600,
            'delta_seconds'        => 10,
            'heartbeat_id'         => 'hb-pl-reopen-' . rand(1000, 9999),
            'status'               => 'in_progress',
        ]);

        $response->assertStatus(200);

        // 1. Verify DB record: completed_at of c1 remains 10:00:00
        $pcu1Fresh = PlaylistContentUser::find($pcu1->id);
        $this->assertEquals('2026-05-10 10:00:00', $pcu1Fresh->completed_at->format('Y-m-d H:i:s'));
        $this->assertEquals(610, (int) $pcu1Fresh->actual_watch_seconds);

        // 2. CompletedPlaylistService must still return last_finished_at = T1 (10:30:00)
        $completedPlaylists = app(\App\Services\Learning\CompletedPlaylistService::class)->forUser($this->user->id);
        $found = $completedPlaylists->firstWhere('id', $planA->id);
        $this->assertNotNull($found);
        $this->assertEquals($t1->format('Y-m-d H:i:s'), Carbon::parse($found->last_finished_at)->format('Y-m-d H:i:s'));

        // 3. Learning Record UI ordering
        $recordResponse = $this->get(route('user.learning.dashboard.partial', ['tab' => 'record']));
        $recordResponse->assertStatus(200);

        $html = $recordResponse->getContent();

        // Content C (12:00) MUST appear before Playlist A (10:30)
        $posC = strpos($html, 'Content C Newer Standalone');
        $posA = strpos($html, 'Playlist A Older Completion');
        $this->assertNotFalse($posC, 'Content C should be present');
        $this->assertNotFalse($posA, 'Playlist A should be present');
        $this->assertLessThan($posA, $posC, 'Content C (newer) must appear before Playlist A (older) even after Playlist A is reopened');

        Carbon::setTestNow();
    }

    /**
     * Scenario C: Incomplete learning (< 100%) continues updating normally without setting completed_at.
     */
    public function test_incomplete_learning_continues_tracking_without_setting_completed_at(): void
    {
        $content = Content::create([
            'title'             => 'Incomplete Content',
            'slug'              => 'incomplete-c-' . rand(1000, 9999),
            'type'              => 'video',
            'duration'          => '00:10:00',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $cu = new ContentUser([
            'user_id'              => $this->user->id,
            'content_id'           => $content->id,
            'progress'             => 40,
            'finish'               => '0',
            'current_time'         => 240,
            'duration_seconds'     => 600,
            'actual_watch_seconds' => 240,
        ]);
        $cu->save();

        $this->assertNull($cu->completed_at);

        $this->actingAs($this->user);

        // Progress continues to 60%
        $response = $this->postJson(route('content.user.progress.update'), [
            'content_id'           => $content->id,
            'type'                 => 'video',
            'current_time'         => 360,
            'duration_seconds'     => 600,
            'delta_seconds'        => 20,
            'heartbeat_id'         => 'hb-incomp-' . rand(1000, 9999),
            'status'               => 'in_progress',
        ]);

        $response->assertStatus(200);

        $cuFresh = ContentUser::find($cu->id);
        $this->assertEquals(60, (int) $cuFresh->progress);
        $this->assertEquals('0', (string) $cuFresh->finish);
        $this->assertNull($cuFresh->completed_at);
        $this->assertEquals(260, (int) $cuFresh->actual_watch_seconds);
    }

    /**
     * Scenario D: Legacy completed record with null completed_at preserves original updated_at.
     */
    public function test_legacy_completed_record_preserves_original_timestamp_when_reopened(): void
    {
        $legacyTime = Carbon::parse('2025-11-20 08:00:00');
        $reopenTime = Carbon::parse('2026-09-17 14:00:00');

        $content = Content::create([
            'title'             => 'Legacy Content',
            'slug'              => 'legacy-c-' . rand(1000, 9999),
            'type'              => 'video',
            'duration'          => '00:10:00',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $cu = new ContentUser([
            'user_id'              => $this->user->id,
            'content_id'           => $content->id,
            'progress'             => 100,
            'finish'               => '1',
            'current_time'         => 600,
            'duration_seconds'     => 600,
            'actual_watch_seconds' => 600,
        ]);
        $cu->save();

        // Simulate legacy row where completed_at is NULL in database
        \Illuminate\Support\Facades\DB::table('content_users')
            ->where('id', $cu->id)
            ->update([
                'completed_at' => null,
                'updated_at'   => $legacyTime,
                'created_at'   => $legacyTime,
            ]);

        // Reopen at reopenTime
        Carbon::setTestNow($reopenTime);

        $loaded = ContentUser::find($cu->id);
        $this->assertNull($loaded->completed_at);

        // Perform save (simulating reopen / review tracking update)
        $loaded->actual_watch_seconds += 10;
        $loaded->save();

        // completed_at MUST be preserved as the original legacy timestamp, NOT $reopenTime
        $this->assertEquals($legacyTime->format('Y-m-d H:i:s'), $loaded->completed_at->format('Y-m-d H:i:s'));

        Carbon::setTestNow();
    }
}
