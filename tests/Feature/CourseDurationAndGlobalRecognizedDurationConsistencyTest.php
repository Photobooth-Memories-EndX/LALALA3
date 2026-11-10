<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use App\Services\Learning\LearningRecordLearningHourService;
use App\Services\Learning\LearningRecordRecognizedDurationService;
use App\Support\DurationFormatter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class CourseDurationAndGlobalRecognizedDurationConsistencyTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Classification $classification;
    protected LearningRecordLearningHourService $hourService;
    protected LearningRecordRecognizedDurationService $recognizedService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name'       => 'Consistency Tester ' . rand(1000, 9999),
            'id_emp'     => 'EMP-CON-' . rand(1000, 9999),
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'hash-con-' . Str::uuid(),
            'sap_code'   => 'SAP-CON-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->classification = Classification::firstOrCreate(
            ['classification_name' => 'Consistency Classification'],
            ['slug' => 'consistency-classification-' . rand(1000, 9999)]
        );

        $this->hourService = app(LearningRecordLearningHourService::class);
        $this->recognizedService = app(LearningRecordRecognizedDurationService::class);
    }

    /**
     * Test 1: Course Duration is formatted as HH:MM:SS consistently across all services and views.
     * Content: 00:03:20 (200 seconds).
     * Must not format as '3 min' or '15 min'.
     */
    public function test_course_duration_is_formatted_as_hh_mm_ss(): void
    {
        $video = Content::create([
            'title'             => 'Penanggulangan HIV AIDS Part 2',
            'slug'              => 'penanggulangan-hiv-aids-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:03:20',
            'learning_hours'    => 0.5,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $this->assertSame('00:03:20', DurationFormatter::format($video->duration));
        $this->assertSame('00:03:20', DurationFormatter::normalize($video->duration));
        $this->assertSame(200, DurationFormatter::toSeconds($video->duration));

        // When accessed via direct view
        $response = $this->actingAs($this->user)->get('/content/' . $video->slug);
        $response->assertOk();
        $response->assertSee('00:03:20');
        $response->assertSee('Total Recognized Duration');
        $response->assertSee('id="contentTotalRecognizedDuration"', false);
    }

    /**
     * Test 2: Global Recognized Duration accumulates across Direct and Playlist up to 2x cap,
     * and is identical across centralized services and pages.
     */
    public function test_global_recognized_duration_consistency_across_direct_and_playlist(): void
    {
        $uniqueSuffix = rand(10000, 99999);
        $content = Content::create([
            'title'             => 'Consistent Content ' . $uniqueSuffix,
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:03:20',
            'learning_hours'    => 0.5,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $plan = TrainingPlan::create([
            'title'                  => 'Consistent Playlist ' . $uniqueSuffix,
            'active'                 => 'Active',
            'playlistonly'           => 'Y',
            'total_duration_seconds' => 200,
            'learning_hours'         => 0.5,
            'has_deadline'           => 0,
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => (string) $plan->id,
            'content_id'      => (string) $content->id,
            'content_order'   => '1',
        ]);

        $content->refresh();
        $plan->refresh();

        // Phase 1: User watches 200s directly (1x First Watch completed, Recognized Rewatch is 0)
        ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 200,
            'current_time'         => 200,
            'duration_seconds'     => 200,
            'progress'             => 100,
            'finish'               => '1',
            'learning_status'      => 'completed',
        ]);

        $recognizedSec = $this->recognizedService->resolveGlobalContentRecognizedSeconds($this->user->id, $content);
        $recognizedDur = $this->recognizedService->resolveGlobalContentRecognizedDuration($this->user->id, $content);

        // First watch does not generate recognized rewatch: 00:00:00
        $this->assertSame(0, $recognizedSec);
        $this->assertSame('00:00:00', $recognizedDur);

        // Check Direct View
        $directResponse = $this->actingAs($this->user)->get('/content/' . $content->slug);
        $directResponse->assertOk();
        $directResponse->assertSee('<span id="contentTotalRecognizedDuration">00:00:00</span>', false);

        // Check Playlist View
        $playlistResponse = $this->actingAs($this->user)->get('/playlist/' . $plan->slug . '/' . $content->slug);
        $playlistResponse->assertOk();
        $this->assertSame('00:00:00', $playlistResponse->viewData('totalRecognizedDuration'));

        // Phase 2: User watches 150s via Playlist (Total actual = 350s, Rewatch = 150s)
        PlaylistContentUser::updateOrCreate(
            [
                'user_id'         => $this->user->id,
                'trainingplan_id' => $plan->id,
                'content_id'      => $content->id,
            ],
            [
                'actual_watch_seconds' => 150,
                'current_time'         => 150,
                'duration_seconds'     => 200,
                'progress'             => 75,
                'finish'               => '0',
                'learning_status'      => 'in_progress',
            ]
        );

        $recognizedSec2 = $this->recognizedService->resolveGlobalContentRecognizedSeconds($this->user->id, $content);
        $recognizedDur2 = $this->recognizedService->resolveGlobalContentRecognizedDuration($this->user->id, $content);

        // 350s total - 200s first watch quota = 150s (00:02:30)
        $this->assertSame(150, $recognizedSec2);
        $this->assertSame('00:02:30', $recognizedDur2);

        $directResponse2 = $this->actingAs($this->user)->get('/content/' . $content->slug);
        $directResponse2->assertOk();
        $directResponse2->assertSee('<span id="contentTotalRecognizedDuration">00:02:30</span>', false);

        // Phase 3: User watches another 100s (Total actual = 450s > 400s max cap, Rewatch capped at 200s)
        $cu = ContentUser::where('user_id', $this->user->id)->where('content_id', $content->id)->first();
        $cu->update(['actual_watch_seconds' => 300]); // 300 + 150 = 450s

        $recognizedSec3 = $this->recognizedService->resolveGlobalContentRecognizedSeconds($this->user->id, $content);
        $recognizedDur3 = $this->recognizedService->resolveGlobalContentRecognizedDuration($this->user->id, $content);

        // Hard capped at 1x duration rewatch = 200s (00:03:20)
        $this->assertSame(200, $recognizedSec3);
        $this->assertSame('00:03:20', $recognizedDur3);

        $directResponse3 = $this->actingAs($this->user)->get('/content/' . $content->slug);
        $directResponse3->assertOk();
        $directResponse3->assertSee('<span id="contentTotalRecognizedDuration">00:03:20</span>', false);
    }

    /**
     * Test 3: Heartbeat on Direct Content returns dynamic total_recognized_duration and total_recognized_seconds.
     */
    public function test_heartbeat_returns_dynamic_global_recognized_duration_on_content(): void
    {
        $content = Content::create([
            'title'             => 'Heartbeat Test Content',
            'slug'              => 'heartbeat-content-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:03:20', // 200s
            'learning_hours'    => 0.5,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $cu = ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 50,
            'current_time'         => 50,
            'duration_seconds'     => 200,
            'progress'             => 25,
            'finish'               => '0',
            'learning_status'      => 'in_progress',
        ]);

        $response = $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'       => $content->id,
            'type'             => 'video',
            'delta_seconds'    => 10,
            'current_time'     => 60,
            'duration_seconds' => 200,
            'heartbeat_id'     => 'hb-' . Str::uuid(),
            'status'           => 'in_progress',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success'                   => true,
            'total_recognized_seconds'  => 0,
            'total_recognized_duration' => '00:00:00',
        ]);
    }

    /**
     * Test 4: Heartbeat on Playlist Content returns dynamic total_recognized_duration and total_recognized_seconds.
     */
    public function test_heartbeat_returns_dynamic_global_recognized_duration_on_playlist(): void
    {
        $content = Content::create([
            'title'             => 'Playlist Heartbeat Content',
            'slug'              => 'playlist-heartbeat-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:03:20', // 200s
            'learning_hours'    => 0.5,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);

        $plan = TrainingPlan::create([
            'title'        => 'Heartbeat Playlist',
            'slug'         => 'heartbeat-playlist-' . rand(1000, 9999),
            'active'       => 'Active',
            'playlistonly' => 'Y',
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => $plan->id,
            'content_id'      => $content->id,
            'content_order'   => 1,
        ]);

        PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $plan->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 30,
            'current_time'         => 30,
            'duration_seconds'     => 200,
            'progress'             => 15,
            'finish'               => '0',
            'learning_status'      => 'in_progress',
        ]);

        $response = $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $plan->id,
            'content_id'       => $content->id,
            'type'             => 'video',
            'delta_seconds'    => 10,
            'current_time'     => 40,
            'duration_seconds' => 200,
            'heartbeat_id'     => 'hb-pcu-' . Str::uuid(),
            'status'           => 'in_progress',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success'                   => true,
            'total_recognized_seconds'  => 0,
            'total_recognized_duration' => '00:00:00',
        ]);
    }

    /**
     * Test 5: Progress and finish remain strictly independent between content_users and playlist_content_users.
     */
    public function test_progress_and_finish_remain_strictly_independent(): void
    {
        $content = Content::create([
            'title'             => 'Dual Track Content',
            'slug'              => 'dual-track-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:04:00', // 240s
            'learning_hours'    => 0.5,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $plan = TrainingPlan::create([
            'title'        => 'Dual Track Playlist',
            'slug'         => 'dual-track-plan-' . rand(1000, 9999),
            'active'       => 'Active',
            'playlistonly' => 'Y',
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => $plan->id,
            'content_id'      => $content->id,
            'content_order'   => 1,
        ]);

        // Direct: completed 100%
        $cu = ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 240,
            'progress'             => 100,
            'finish'               => '1',
            'learning_status'      => 'completed',
        ]);

        // Playlist: in progress 40%
        $pcu = PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $plan->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 96,
            'progress'             => 40,
            'finish'               => '0',
            'learning_status'      => 'in_progress',
        ]);

        $this->assertSame(100, (int) $cu->fresh()->progress);
        $this->assertSame('1', (string) $cu->fresh()->finish);

        $this->assertSame(40, (int) $pcu->fresh()->progress);
        $this->assertSame('0', (string) $pcu->fresh()->finish);

        // Combined recognized duration: min(max(240 + 96 - 240, 0), 240) = 96s (00:01:36)
        $recognizedSec = $this->recognizedService->resolveGlobalContentRecognizedSeconds($this->user->id, $content);
        $recognizedDur = $this->recognizedService->resolveGlobalContentRecognizedDuration($this->user->id, $content);

        $this->assertSame(96, $recognizedSec);
        $this->assertSame('00:01:36', $recognizedDur);
    }

    /**
     * Test 6: Learning Record Online and All modes render Course Duration in canonical HH:MM:SS.
     */
    public function test_learning_record_renders_canonical_duration_and_recognized_duration(): void
    {
        $content = Content::create([
            'title'             => 'Penanggulangan HIV Part 2 LR',
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:03:20', // 200s
            'learning_hours'    => 0.5,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 200,
            'current_time'         => 200,
            'duration_seconds'     => 200,
            'progress'             => 100,
            'finish'               => '1',
            'learning_status'      => 'completed',
        ]);

        // Online mode
        $onlineResponse = $this->actingAs($this->user)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'record', 'record_mode' => 'online']));
        $onlineResponse->assertOk();
        $onlineResponse->assertSee('00:03:20');

        // All mode
        $allResponse = $this->actingAs($this->user)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'record', 'record_mode' => 'all']));
        $allResponse->assertOk();
        $allResponse->assertSee('00:03:20');
    }
}
