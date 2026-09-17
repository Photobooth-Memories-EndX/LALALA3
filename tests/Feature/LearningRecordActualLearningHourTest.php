<?php

namespace Tests\Feature;

use App\Http\Controllers\LearningRecordController;
use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use App\Services\CertificateEligibilityService;
use App\Services\Learning\LearningHourService;
use App\Services\Learning\LearningRecordLearningHourService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class LearningRecordActualLearningHourTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected User $otherUser;
    protected Classification $classification;
    protected LearningRecordController $controller;
    protected LearningRecordLearningHourService $actualHourService;
    protected LearningHourService $officialHourService;
    protected CertificateEligibilityService $eligibilityService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = app(LearningRecordController::class);
        $this->actualHourService = app(LearningRecordLearningHourService::class);
        $this->officialHourService = app(LearningHourService::class);
        $this->eligibilityService = app(CertificateEligibilityService::class);

        $this->user = User::factory()->create([
            'name'       => 'Test Learner ' . rand(1000, 9999),
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-hash-lh-' . Str::uuid(),
            'sap_code'   => 'SAP-LH-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->otherUser = User::factory()->create([
            'name'       => 'Other Learner ' . rand(1000, 9999),
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-hash-other-' . Str::uuid(),
            'sap_code'   => 'SAP-OTH-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->classification = Classification::firstOrCreate(
            ['classification_name' => 'Safety & Environment'],
            ['slug' => 'safety-environment']
        );
    }

    /**
     * CONTENT TEST 1: current_time > 0 takes current_time directly.
     */
    public function test_content_actual_hours_uses_current_time_when_greater_than_zero(): void
    {
        $content = Content::create([
            'title'             => 'Current Time Content',
            'slug'              => 'ct-content-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:06:00', // 360s
            'learning_hours'    => 1.5, // Catalog value: MUST NOT BE USED
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $cu = ContentUser::create([
            'user_id'          => $this->user->id,
            'content_id'       => $content->id,
            'current_time'     => 72, // 72s -> 72/3600 = 0.02 hours
            'duration_seconds' => 360,
            'progress'         => 20,
            'finish'           => '1',
            'status'           => 'optional',
        ]);

        $hours = $this->actualHourService->calculateContent($cu);
        $this->assertEquals(0.02, $hours);
        $this->assertEquals('0.02 hour', $this->actualHourService->formatHours($hours));

        $this->actingAs($this->user);
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('buildContentRows');
        $method->setAccessible(true);
        $certMethod = $reflection->getMethod('certificateContext');
        $certMethod->setAccessible(true);
        $cert = $certMethod->invoke($this->controller, $this->eligibilityService);

        $rows = $method->invoke($this->controller, collect([$cu]), $cert);
        $row = $rows->firstWhere('slug', $content->slug);

        $this->assertNotNull($row);
        $this->assertEquals(0.02, $row['learning_hour_bucket']);
        $this->assertEquals(0.02, $row['learning_hour_value']);
        $this->assertEquals('0.02 hour', $row['learning_hour']);
    }

    /**
     * CONTENT TEST 2: current_time = 0 + progress > 0 calculates (progress/100) * duration.
     */
    public function test_content_actual_hours_uses_progress_when_current_time_is_zero(): void
    {
        $content = Content::create([
            'title'             => 'Progress Content',
            'slug'              => 'prog-content-' . rand(1000, 9999),
            'type'              => 'Document',
            'classification_id' => $this->classification->id,
            'duration'          => '00:04:38', // 278s
            'learning_hours'    => 1.0, // Catalog value: MUST NOT BE USED
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $cu = ContentUser::create([
            'user_id'          => $this->user->id,
            'content_id'       => $content->id,
            'current_time'     => 0,
            'duration_seconds' => 278,
            'progress'         => 10, // 10% of 278 = 27.8s -> 28/3600 = 0.0078 -> 0.01 hours
            'finish'           => '0',
            'status'           => 'optional',
        ]);

        $hours = $this->actualHourService->calculateContent($cu);
        $this->assertEquals(0.0078, $hours);
        $this->assertEquals('0.01 hour', $this->actualHourService->formatHours($hours));
    }

    /**
     * CONTENT TEST 3: finished + current_time = 0 uses full duration_seconds.
     */
    public function test_content_actual_hours_uses_full_duration_when_finished_and_current_time_is_zero(): void
    {
        $content = Content::create([
            'title'             => 'Finished Content',
            'slug'              => 'fin-content-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:04:30', // 270s
            'learning_hours'    => 2.0, // Catalog: MUST NOT BE USED
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $cu = ContentUser::create([
            'user_id'          => $this->user->id,
            'content_id'       => $content->id,
            'current_time'     => 0,
            'duration_seconds' => 270,
            'progress'         => 100,
            'finish'           => '1',
            'status'           => 'mandatory',
        ]);

        $hours = $this->actualHourService->calculateContent($cu);
        $this->assertEquals(0.075, $hours); // 270 / 3600 = 0.075
        $this->assertEquals('0.08 hour', $this->actualHourService->formatHours($hours));
    }

    /**
     * CONTENT TEST 4: Duration fallback to content.duration if duration_seconds is missing.
     */
    public function test_content_actual_hours_falls_back_to_content_duration(): void
    {
        $content = Content::create([
            'title'             => 'Duration Fallback Content',
            'slug'              => 'dur-fallback-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:05:00', // 300s
            'learning_hours'    => 1.0,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $cu = ContentUser::create([
            'user_id'          => $this->user->id,
            'content_id'       => $content->id,
            'current_time'     => 0,
            'duration_seconds' => 0, // Empty in CU
            'progress'         => 100,
            'finish'           => '1',
        ]);
        $cu->setRelation('content', $content);

        $hours = $this->actualHourService->calculateContent($cu);
        $this->assertEquals(0.0833, $hours); // 300 / 3600 = 0.0833
        $this->assertEquals('0.08 hour', $this->actualHourService->formatHours($hours));
    }

    /**
     * CONTENT TEST 5: Never use content.learning_hours as actual learning time.
     */
    public function test_content_never_uses_catalog_learning_hours(): void
    {
        $content = Content::create([
            'title'             => 'Catalog LH Content',
            'slug'              => 'catalog-lh-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:05:00', // 300s -> 0.08 hours
            'learning_hours'    => 5.0, // Large catalog value
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $cu = ContentUser::create([
            'user_id'          => $this->user->id,
            'content_id'       => $content->id,
            'current_time'     => 0,
            'duration_seconds' => 300,
            'progress'         => 100,
            'finish'           => '1',
        ]);

        $hours = $this->actualHourService->calculateContent($cu);
        $this->assertNotEquals(5.0, $hours);
        $this->assertEquals(0.0833, $hours);
    }

    /**
     * PLAYLIST TEST 1: Multiple modules with mixed states sum consumed seconds.
     */
    public function test_playlist_actual_hours_aggregates_multiple_modules(): void
    {
        $plan = TrainingPlan::create([
            'title'                  => 'Multi Module Plan',
            'slug'                   => 'multi-mod-plan-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 2000,
            'learning_hours'         => 6.0, // Catalog: MUST NOT BE USED
        ]);

        $c1 = Content::create([
            'title' => 'C1', 'slug' => 'c1-' . rand(1000, 9999), 'type' => 'Video',
            'classification_id' => $this->classification->id, 'active' => 'Active',
            'duration' => '00:16:40', 'playlistonly' => 'Y', // 1000s
        ]);
        $c2 = Content::create([
            'title' => 'C2', 'slug' => 'c2-' . rand(1000, 9999), 'type' => 'Video',
            'classification_id' => $this->classification->id, 'active' => 'Active',
            'duration' => '00:10:00', 'playlistonly' => 'Y', // 600s
        ]);
        $c3 = Content::create([
            'title' => 'C3', 'slug' => 'c3-' . rand(1000, 9999), 'type' => 'Video',
            'classification_id' => $this->classification->id, 'active' => 'Active',
            'duration' => '00:06:40', 'playlistonly' => 'Y', // 400s
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c3->id, 'content_order' => 2]);

        // Module 1: finished = 1, current_time = 0 -> 1000s
        PlaylistContentUser::create([
            'user_id' => $this->user->id, 'trainingplan_id' => $plan->id, 'content_id' => $c1->id,
            'duration_seconds' => 1000, 'current_time' => 0, 'progress' => 100, 'finish' => '1',
        ]);
        // Module 2: current_time = 450s > 0 -> 450s
        PlaylistContentUser::create([
            'user_id' => $this->user->id, 'trainingplan_id' => $plan->id, 'content_id' => $c2->id,
            'duration_seconds' => 600, 'current_time' => 450, 'progress' => 75, 'finish' => '0',
        ]);
        // Module 3: progress = 50%, current_time = 0 -> 200s
        PlaylistContentUser::create([
            'user_id' => $this->user->id, 'trainingplan_id' => $plan->id, 'content_id' => $c3->id,
            'duration_seconds' => 400, 'current_time' => 0, 'progress' => 50, 'finish' => '0',
        ]);

        // Total seconds = 1000 + 450 + 200 = 1650s -> 1650 / 3600 = 0.4583 hours
        $hours = $this->actualHourService->calculatePlaylistSingle((int) $this->user->id, $plan);
        $this->assertEquals(0.4583, $hours);
        $this->assertEquals('0.46 hour', $this->actualHourService->formatHours($hours));
    }

    /**
     * PLAYLIST TEST 2: Handles zero learning_hour with positive progress.
     */
    public function test_playlist_actual_hours_handles_zero_learning_hour_with_progress(): void
    {
        $plan = TrainingPlan::create([
            'title'                  => 'Zero LH Plan',
            'slug'                   => 'zero-lh-plan-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 1200,
        ]);

        $c = Content::create([
            'title' => 'C Zero LH', 'slug' => 'czlh-' . rand(1000, 9999), 'type' => 'Video',
            'classification_id' => $this->classification->id, 'active' => 'Active',
            'duration' => '00:20:00', 'playlistonly' => 'Y', // 1200s
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c->id, 'content_order' => 0]);

        // pcu.learning_hour = 0, but progress = 25% of 1200s = 300s -> 0.0833 hours
        PlaylistContentUser::create([
            'user_id' => $this->user->id, 'trainingplan_id' => $plan->id, 'content_id' => $c->id,
            'duration_seconds' => 1200, 'current_time' => 0, 'progress' => 25, 'finish' => '0',
            'learning_hour' => 0,
        ]);

        $hours = $this->actualHourService->calculatePlaylistSingle((int) $this->user->id, $plan);
        $this->assertEquals(0.0833, $hours);
        $this->assertEquals('0.08 hour', $this->actualHourService->formatHours($hours));
    }

    /**
     * PLAYLIST TEST 3: TrainingPlan catalog learning_hours is NOT used.
     */
    public function test_playlist_never_uses_catalog_learning_hours(): void
    {
        $plan = TrainingPlan::create([
            'title'                  => 'Catalog Plan',
            'slug'                   => 'cat-plan-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 7200,
            'learning_hours'         => 6.0, // Official catalog: 6.0
        ]);

        $c = Content::create([
            'title' => 'C Catalog', 'slug' => 'ccat-' . rand(1000, 9999), 'type' => 'Video',
            'classification_id' => $this->classification->id, 'active' => 'Active',
            'duration' => '00:10:00', 'playlistonly' => 'Y', // 600s
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c->id, 'content_order' => 0]);

        PlaylistContentUser::create([
            'user_id' => $this->user->id, 'trainingplan_id' => $plan->id, 'content_id' => $c->id,
            'duration_seconds' => 600, 'current_time' => 600, 'progress' => 100, 'finish' => '1',
        ]);

        $hours = $this->actualHourService->calculatePlaylistSingle((int) $this->user->id, $plan);
        $this->assertNotEquals(6.0, $hours);
        $this->assertEquals(0.1667, $hours); // 600 / 3600 = 0.1667
    }

    /**
     * PLAYLIST TEST 4: User and TrainingPlan isolation (no cross-contamination).
     */
    public function test_playlist_user_and_plan_isolation(): void
    {
        $planA = TrainingPlan::create(['title' => 'Plan A', 'slug' => 'iso-plan-a-' . rand(1000, 9999), 'active' => 'Active', 'total_duration_seconds' => 1000]);
        $planB = TrainingPlan::create(['title' => 'Plan B', 'slug' => 'iso-plan-b-' . rand(1000, 9999), 'active' => 'Active', 'total_duration_seconds' => 1000]);

        $cA = Content::create(['title' => 'CA', 'slug' => 'ca-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'duration' => '00:10:00']);
        $cB = Content::create(['title' => 'CB', 'slug' => 'cb-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'duration' => '00:10:00']);

        TrainingPlanDetail::create(['trainingplan_id' => $planA->id, 'content_id' => $cA->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $planB->id, 'content_id' => $cB->id, 'content_order' => 0]);

        // User A on Plan A: 300s
        PlaylistContentUser::create([
            'user_id' => $this->user->id, 'trainingplan_id' => $planA->id, 'content_id' => $cA->id,
            'duration_seconds' => 600, 'current_time' => 300, 'progress' => 50, 'finish' => '0',
        ]);

        // Other user on Plan A: 600s
        PlaylistContentUser::create([
            'user_id' => $this->otherUser->id, 'trainingplan_id' => $planA->id, 'content_id' => $cA->id,
            'duration_seconds' => 600, 'current_time' => 600, 'progress' => 100, 'finish' => '1',
        ]);

        // User A on Plan B: 600s
        PlaylistContentUser::create([
            'user_id' => $this->user->id, 'trainingplan_id' => $planB->id, 'content_id' => $cB->id,
            'duration_seconds' => 600, 'current_time' => 600, 'progress' => 100, 'finish' => '1',
        ]);

        // User A on Plan A should have exactly 300 / 3600 = 0.0833
        $hours = $this->actualHourService->calculatePlaylistSingle((int) $this->user->id, $planA);
        $this->assertEquals(0.0833, $hours);
    }

    /**
     * PLAYLIST TEST 5: No double count when module exists in BOTH playlist_content_users and content_users.
     */
    public function test_playlist_does_not_double_count_when_module_exists_in_both_pcu_and_cu(): void
    {
        $plan = TrainingPlan::create([
            'title'                  => 'Double Count Test Plan',
            'slug'                   => 'dc-plan-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 3600,
        ]);

        $c1 = Content::create([
            'title' => 'Module 1', 'slug' => 'mod1-' . rand(1000, 9999), 'type' => 'Video',
            'classification_id' => $this->classification->id, 'active' => 'Active',
            'duration' => '00:30:00', 'playlistonly' => 'Y', // 1800s
        ]);
        $c2 = Content::create([
            'title' => 'Module 2', 'slug' => 'mod2-' . rand(1000, 9999), 'type' => 'Video',
            'classification_id' => $this->classification->id, 'active' => 'Active',
            'duration' => '00:30:00', 'playlistonly' => 'Y', // 1800s
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);

        // Module 1 exists in PCU (1800s) AND in CU (1800s)
        PlaylistContentUser::create([
            'user_id' => $this->user->id, 'trainingplan_id' => $plan->id, 'content_id' => $c1->id,
            'duration_seconds' => 1800, 'current_time' => 1800, 'progress' => 100, 'finish' => '1',
        ]);
        ContentUser::create([
            'user_id' => $this->user->id, 'content_id' => $c1->id,
            'duration_seconds' => 1800, 'current_time' => 1800, 'progress' => 100, 'finish' => '1',
        ]);

        // Module 2 only exists in CU (historical Category A, 1800s)
        ContentUser::create([
            'user_id' => $this->user->id, 'content_id' => $c2->id,
            'duration_seconds' => 1800, 'current_time' => 1800, 'progress' => 100, 'finish' => '1',
        ]);

        // Total should be Module 1 (1800s from PCU) + Module 2 (1800s fallback from CU) = 3600s = 1.0 hour!
        // If double counting occurred for Module 1, it would be 1800 + 1800 + 1800 = 5400s (1.5 hours).
        $hours = $this->actualHourService->calculatePlaylistSingle((int) $this->user->id, $plan);
        $this->assertEquals(1.0, $hours);
        $this->assertEquals('1.00 hour', $this->actualHourService->formatHours($hours));
    }

    /**
     * EXPORT TEST: Export matches UI actual learning hour values.
     */
    public function test_export_learning_record_matches_ui_actual_learning_hours(): void
    {
        $content = Content::create([
            'title'             => 'Export Match Content',
            'slug'              => 'exp-match-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:04:30', // 270s -> 0.075h -> 0.08 h
            'learning_hours'    => 2.0, // Catalog: MUST NOT BE USED
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        ContentUser::create([
            'user_id'          => $this->user->id,
            'content_id'       => $content->id,
            'current_time'     => 0,
            'duration_seconds' => 270,
            'progress'         => 100,
            'finish'           => '1',
        ]);

        $this->actingAs($this->user);

        $export = new \App\Exports\ExportMyLearningRecord();
        $array = $export->array();
        $rows = collect($array);
        $row = $rows->firstWhere(1, 'Export Match Content');

        $this->assertNotNull($row);
        $this->assertEquals('00:04:30', $row[5]); // Column 5 = Duration (hh:mm:ss)
        $this->assertEquals(0.08, $row[6]); // Column 6 = Learning Hour (numeric)
    }

    /**
     * OFFICIAL TEST: Official LearningHourService bucket formula ceil(seconds/1800)*0.5 is untouched.
     */
    public function test_official_learning_hour_service_formula_is_preserved(): void
    {
        // 0s -> 0.0
        $this->assertEquals(0.0, $this->officialHourService->calculateFromSeconds(0));
        // 1s -> 0.5
        $this->assertEquals(0.5, $this->officialHourService->calculateFromSeconds(1));
        // 1800s (30m) -> 0.5
        $this->assertEquals(0.5, $this->officialHourService->calculateFromSeconds(1800));
        // 1801s (30m 1s) -> 1.0
        $this->assertEquals(1.0, $this->officialHourService->calculateFromSeconds(1801));
        // 3600s (60m) -> 1.0
        $this->assertEquals(1.0, $this->officialHourService->calculateFromSeconds(3600));
        // 3601s (60m 1s) -> 1.5
        $this->assertEquals(1.5, $this->officialHourService->calculateFromSeconds(3601));
    }
}