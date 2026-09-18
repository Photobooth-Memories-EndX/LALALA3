<?php

namespace Tests\Feature;

use App\Http\Controllers\TrainingPlanController;
use App\Models\Classification;
use App\Models\Content;
use App\Models\PlaylistContentUser;
use App\Models\RoleAccessPermission;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PlaylistLearningHoursSynchronizationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected User $unauthorizedUser;
    protected Classification $classification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->classification = Classification::firstOrCreate(
            ['slug' => 'test-classification-pl-lh'],
            ['classification_name' => 'Testing Classification PL LH']
        );

        $this->adminUser = User::factory()->create([
            'role'       => User::ROLE_SUPER_ADMIN,
            'team'       => 'Quality Assurance',
            'department' => 'Technology',
            'division'   => 'Engineering',
            'ssohash'    => 'test-admin-lh-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-ADMIN-LH',
        ]);

        $this->unauthorizedUser = User::factory()->create([
            'role'       => User::ROLE_USER,
            'team'       => 'Operations',
            'department' => 'Manufacturing',
            'division'   => 'Production',
            'ssohash'    => 'test-user-lh-noperm-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-USER-NOPERM',
        ]);

        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_SUPER_ADMIN, 'module_key' => 'playlist_learning_path'],
            ['can_access' => true]
        );

        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_USER, 'module_key' => 'playlist_learning_path'],
            ['can_access' => false]
        );

        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_SUPER_ADMIN, 'module_key' => 'content_repository'],
            ['can_access' => true]
        );
    }

    /**
     * Test 1 — Authorized user can synchronize Playlist Learning Hours.
     */
    public function test_authorized_user_can_synchronize_playlist_learning_hours(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('manage.playlist.synchronize-learning-hours'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'total',
                'updated',
                'skipped',
            ],
        ]);
        $this->assertTrue($response->json('success'));
    }

    /**
     * Test 2 — Unauthorized and unauthenticated users are rejected.
     */
    public function test_unauthorized_user_is_rejected(): void
    {
        // Unauthenticated request
        $unauthResponse = $this->postJson(route('manage.playlist.synchronize-learning-hours'));
        $unauthResponse->assertStatus(401);

        // Authenticated user lacking playlist_learning_path permission
        $forbiddenResponse = $this->actingAs($this->unauthorizedUser)
            ->postJson(route('manage.playlist.synchronize-learning-hours'));
        $this->assertTrue(in_array($forbiddenResponse->status(), [302, 403], true));
    }

    /**
     * Test 3 — Correct Playlist Learning Hours are calculated.
     * Content A (00:20:00 = 1200s) + Content B (00:40:00 = 2400s) = 3600s -> 1.0000 Learning Hours.
     */
    public function test_correct_playlist_learning_hours_are_calculated(): void
    {
        $c1 = Content::create([
            'title'             => 'Test Content 20m',
            'duration'          => '00:20:00',
            'active'            => 'Active',
            'classification_id' => $this->classification->id,
        ]);
        $c2 = Content::create([
            'title'             => 'Test Content 40m',
            'duration'          => '00:40:00',
            'active'            => 'Active',
            'classification_id' => $this->classification->id,
        ]);

        $plan = TrainingPlan::create([
            'title'                  => 'Playlist 1 Hour Total',
            'active'                 => 'Active',
            'learning_hours'         => 0.0,
            'total_duration_seconds' => 3600,
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('manage.playlist.synchronize-learning-hours'));

        $response->assertStatus(200);

        $plan->refresh();
        $this->assertEquals(1.0000, $plan->learning_hours);
    }

    /**
     * Test 4 — Multiple playlists work and are synchronized in batch.
     */
    public function test_multiple_playlists_work(): void
    {
        $c1 = Content::create(['title' => 'C1 30m', 'duration' => '00:30:00', 'active' => 'Active', 'classification_id' => $this->classification->id]);
        $c2 = Content::create(['title' => 'C2 90m', 'duration' => '01:30:00', 'active' => 'Active', 'classification_id' => $this->classification->id]);

        $plan1 = TrainingPlan::create(['title' => 'Plan 1', 'active' => 'Active', 'learning_hours' => 0.0]);
        $plan2 = TrainingPlan::create(['title' => 'Plan 2', 'active' => 'Active', 'learning_hours' => 0.0]);

        TrainingPlanDetail::create(['trainingplan_id' => $plan1->id, 'content_id' => $c1->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan2->id, 'content_id' => $c2->id, 'content_order' => 0]);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('manage.playlist.synchronize-learning-hours'));

        $response->assertStatus(200);

        $plan1->refresh();
        $plan2->refresh();

        // 1800s / 3600 = 0.5000
        $this->assertEquals(0.5000, $plan1->learning_hours);
        // 5400s / 3600 = 1.5000
        $this->assertEquals(1.5000, $plan2->learning_hours);
    }

    /**
     * Test 5 — Empty playlist is handled safely without error and evaluates to 0.0 learning hours.
     */
    public function test_empty_playlist_is_handled_safely(): void
    {
        $emptyPlan = TrainingPlan::create([
            'title'                  => 'Empty Playlist Zero Content',
            'active'                 => 'Active',
            'learning_hours'         => 1.25, // Outdated, should become 0.0
            'total_duration_seconds' => 0,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('manage.playlist.synchronize-learning-hours'));

        $response->assertStatus(200);

        $emptyPlan->refresh();
        $this->assertEquals(0.0, $emptyPlan->learning_hours);
    }

    /**
     * Test 6 — Existing Playlist Duration (total_duration_seconds) is NOT modified.
     * Strict isolation from "Sync Playlist Durations".
     */
    public function test_existing_playlist_duration_is_not_modified(): void
    {
        $content = Content::create([
            'title'             => 'Test Content 30m',
            'duration'          => '00:30:00', // 1800s -> 0.5000 LH
            'active'            => 'Active',
            'classification_id' => $this->classification->id,
        ]);

        $plan = TrainingPlan::create([
            'title'                  => 'Playlist Preserved Duration',
            'active'                 => 'Active',
            'learning_hours'         => 0.0,
            'total_duration_seconds' => 0,
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        // Explicitly set total_duration_seconds to a custom value (e.g. 7200 seconds)
        // to verify that Playlist Learning Hours synchronization strictly leaves it untouched.
        $plan->forceFill(['total_duration_seconds' => 7200])->saveQuietly();

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('manage.playlist.synchronize-learning-hours'));

        $response->assertStatus(200);

        $plan->refresh();
        // Learning hours should be updated to 0.5000
        $this->assertEquals(0.5000, $plan->learning_hours);
        // total_duration_seconds MUST remain 7200 and NOT be changed to 1800
        $this->assertSame(7200, $plan->total_duration_seconds);
    }

    /**
     * Test 7 — User progress (playlist_content_users) is NOT modified.
     */
    public function test_user_progress_is_not_modified(): void
    {
        $content = Content::create([
            'title'             => 'Progress Test Content',
            'duration'          => '00:30:00',
            'active'            => 'Active',
            'classification_id' => $this->classification->id,
        ]);

        $plan = TrainingPlan::create([
            'title'          => 'Progress Test Plan',
            'active'         => 'Active',
            'learning_hours' => 0.0,
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        $userProgress = PlaylistContentUser::create([
            'user_id'          => $this->adminUser->id,
            'trainingplan_id'  => $plan->id,
            'content_id'       => $content->id,
            'progress'         => 80,
            'current_time'     => 1440,
            'duration_seconds' => 1800,
            'learning_status'  => 'In Progress',
            'finish'           => '0',
        ]);

        $originalProgress = $userProgress->progress;
        $originalCurrentTime = $userProgress->current_time;
        $originalDurationSeconds = $userProgress->duration_seconds;
        $originalLearningHour = $userProgress->learning_hour;
        $originalStatus = $userProgress->learning_status;

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('manage.playlist.synchronize-learning-hours'));

        $response->assertStatus(200);

        $userProgress->refresh();
        $this->assertSame($originalProgress, $userProgress->progress);
        $this->assertSame($originalCurrentTime, $userProgress->current_time);
        $this->assertSame($originalDurationSeconds, $userProgress->duration_seconds);
        $this->assertEquals($originalLearningHour, $userProgress->learning_hour);
        $this->assertSame($originalStatus, $userProgress->learning_status);
    }

    /**
     * Test 8 — Content Learning Hours are NOT modified by playlist sync.
     */
    public function test_content_learning_hours_are_not_modified(): void
    {
        $content = Content::create([
            'title'             => 'Content Intact Test',
            'duration'          => '00:30:00',
            'learning_hours'    => 2.5000, // Custom/existing content learning hours
            'active'            => 'Active',
            'classification_id' => $this->classification->id,
        ]);

        $plan = TrainingPlan::create([
            'title'          => 'Plan Using Content',
            'active'         => 'Active',
            'learning_hours' => 0.0,
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('manage.playlist.synchronize-learning-hours'));

        $response->assertStatus(200);

        $content->refresh();
        // Content learning_hours must remain strictly 2.5000
        $this->assertEquals(2.5000, $content->learning_hours);
    }

    /**
     * Test 9 — Existing /manage/content synchronization endpoint still works as expected.
     */
    public function test_existing_content_synchronization_still_works(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('learning-hours.sync'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'content_updated',
                'content_skipped',
                'content_total',
                'playlist_updated',
                'playlist_skipped',
                'playlist_total',
            ],
        ]);
    }

    /**
     * Test 10 — Running synchronization twice is safe and idempotent (reports skipped on second run).
     */
    public function test_running_synchronization_twice_is_idempotent(): void
    {
        $content = Content::create([
            'title'             => 'Idempotent Test Content',
            'duration'          => '01:00:00',
            'active'            => 'Active',
            'classification_id' => $this->classification->id,
        ]);

        $plan = TrainingPlan::create([
            'title'          => 'Idempotent Test Plan',
            'active'         => 'Active',
            'learning_hours' => 0.0,
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        // First run: plan is updated
        $response1 = $this->actingAs($this->adminUser)
            ->postJson(route('manage.playlist.synchronize-learning-hours'));

        $response1->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, $response1->json('data.updated'));

        // Second run: plan is skipped / unchanged
        $response2 = $this->actingAs($this->adminUser)
            ->postJson(route('manage.playlist.synchronize-learning-hours'));

        $response2->assertStatus(200);
        $plan->refresh();
        $this->assertEquals(1.0000, $plan->learning_hours);
        $this->assertGreaterThanOrEqual(1, $response2->json('data.skipped'));
    }

    /**
     * Test 11 — Concurrency lock rejects simultaneous requests with HTTP 429.
     */
    public function test_concurrency_lock_rejects_simultaneous_call(): void
    {
        $lock = Cache::lock(TrainingPlanController::LEARNING_HOURS_SYNC_LOCK_KEY, 60);
        $this->assertTrue($lock->get());

        try {
            $response = $this->actingAs($this->adminUser)
                ->postJson(route('manage.playlist.synchronize-learning-hours'));

            $response->assertStatus(429);
            $response->assertJson([
                'success' => false,
                'message' => 'Another playlist learning hours synchronization is currently in progress. Please wait until it completes.',
            ]);
        } finally {
            $lock->release();
        }
    }

    /**
     * Test 12 — Concurrency lock is released after execution finishes.
     */
    public function test_lock_is_released_after_synchronization_finishes(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('manage.playlist.synchronize-learning-hours'));

        $response->assertStatus(200);

        $lock = Cache::lock(TrainingPlanController::LEARNING_HOURS_SYNC_LOCK_KEY, 10);
        $this->assertTrue($lock->get(), 'Lock was not released after successful synchronization.');
        $lock->release();
    }
}
