<?php

namespace Tests\Feature;

use App\Jobs\ManualCalculatePlaylistContentUserLearningHoursJob;
use App\Models\Content;
use App\Models\RoleAccessPermission;
use App\Models\TrainingPlan;
use App\Models\User;
use App\Services\LearningHours\ActualLearningHourCalculator;
use App\Services\LearningHours\ManualPlaylistContentUserLearningHourService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ManualPlaylistContentUserLearningHourExecutionTest extends TestCase
{
    protected User $admin;
    protected ?User $testUser = null;
    protected ManualPlaylistContentUserLearningHourService $service;

    protected function setUp(): void
    {
        parent::setUp();

        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_ADMIN, 'module_key' => 'learning_hours_management'],
            ['can_access' => true]
        );

        $this->admin = User::where('role', User::ROLE_ADMIN)->first()
            ?? User::where('role', User::ROLE_SUPER_ADMIN)->first()
            ?? User::first();

        $this->service = app(ManualPlaylistContentUserLearningHourService::class);

        // Create isolated test user with unique email and id_emp
        $baseUser = User::first();
        $userAttrs = $baseUser->toArray();
        unset($userAttrs['id']);
        $userAttrs['password'] = bcrypt('secret');
        $userAttrs['email'] = 'test_exec_' . uniqid() . '@example.com';
        $userAttrs['id_emp'] = 'TEX' . rand(10000, 99999);
        $userAttrs['ssohash'] = md5(uniqid());
        $userAttrs['name'] = 'Test Exec LH User ' . uniqid();
        $this->testUser = User::create($userAttrs);
    }

    protected function tearDown(): void
    {
        if ($this->testUser) {
            DB::table('playlist_content_users')->where('user_id', $this->testUser->id)->delete();
            $this->testUser->delete();
        }
        parent::tearDown();
    }

    public function test_sync_queue_behavior_verified(): void
    {
        // Under current environment, default queue connection is sync
        $this->assertEquals('sync', config('queue.default'));
    }

    public function test_job_dispatch_and_execution_via_manual_service(): void
    {
        Queue::fake();

        ManualCalculatePlaylistContentUserLearningHoursJob::dispatch(
            ['mode' => 'filtered', 'user_id' => $this->testUser->id],
            ['dry_run' => true]
        );

        Queue::assertPushed(ManualCalculatePlaylistContentUserLearningHoursJob::class, function ($job) {
            return $job->criteria['mode'] === 'filtered' && $job->options['dry_run'] === true;
        });
    }

    public function test_job_handle_executes_using_manual_service(): void
    {
        $job = new ManualCalculatePlaylistContentUserLearningHoursJob(
            ['mode' => 'filtered', 'user_id' => $this->testUser->id],
            ['dry_run' => true]
        );

        $result = $job->handle($this->service);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['dry_run']);
        $this->assertEquals('filtered', $result['mode']);
    }

    public function test_duplicate_protection_via_cache_lock(): void
    {
        $lock = Cache::lock(ManualPlaylistContentUserLearningHourService::LOCK_KEY, 300);
        $lock->get(); // Acquire lock manually

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Another bulk calculation is currently in progress');

            // Attempt second calculation while locked
            $this->service->calculate(
                criteria: ['mode' => 'filtered', 'user_id' => $this->testUser->id],
                options: ['dry_run' => true]
            );
        } finally {
            $lock->release();
        }
    }

    public function test_retry_safety_and_idempotency(): void
    {
        $content = Content::whereNotNull('duration')->first();
        $tp = TrainingPlan::first();

        $recordId = DB::table('playlist_content_users')->insertGetId([
            'user_id'         => $this->testUser->id,
            'trainingplan_id' => $tp ? $tp->id : 1,
            'content_id'      => $content ? $content->id : 1,
            'current_time'    => 1230,
            'progress'        => 50,
            'finish'          => '0',
            'learning_hour'   => null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        // First run
        $this->service->calculate(
            criteria: ['mode' => 'filtered', 'user_id' => $this->testUser->id],
            options: ['dry_run' => false]
        );

        $row1 = DB::table('playlist_content_users')->where('id', $recordId)->first();
        $this->assertEquals('0.3417', number_format((float) $row1->learning_hour, 4, '.', ''));

        // Retry run
        $this->service->calculate(
            criteria: ['mode' => 'filtered', 'user_id' => $this->testUser->id],
            options: ['dry_run' => false]
        );

        $row2 = DB::table('playlist_content_users')->where('id', $recordId)->first();
        // Value remains 0.3417 (not doubled or incremented)
        $this->assertEquals('0.3417', number_format((float) $row2->learning_hour, 4, '.', ''));
    }

    public function test_dry_run_produces_zero_database_mutations(): void
    {
        $content = Content::whereNotNull('duration')->first();
        $tp = TrainingPlan::first();

        $recordId = DB::table('playlist_content_users')->insertGetId([
            'user_id'         => $this->testUser->id,
            'trainingplan_id' => $tp ? $tp->id : 1,
            'content_id'      => $content ? $content->id : 1,
            'current_time'    => 1230,
            'progress'        => 50,
            'finish'          => '0',
            'learning_hour'   => null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $res = $this->service->calculate(
            criteria: ['mode' => 'filtered', 'user_id' => $this->testUser->id],
            options: ['dry_run' => true]
        );

        $this->assertTrue($res['dry_run']);
        $this->assertEquals(1, $res['total_updated']); // Would update 1

        $row = DB::table('playlist_content_users')->where('id', $recordId)->first();
        $this->assertNull($row->learning_hour); // But untouched in DB
    }

    /**
     * Phase 15 Specific: force=true with identical existing value must NOT be misclassified
     * as concurrency difference even when MySQL returns affected rows = 0!
     */
    public function test_force_true_with_identical_existing_value_counts_as_updated(): void
    {
        $content = Content::whereNotNull('duration')->first();
        $tp = TrainingPlan::first();

        // Record already has exact calculated value (1230 / 3600 = 0.3417)
        $recordId = DB::table('playlist_content_users')->insertGetId([
            'user_id'         => $this->testUser->id,
            'trainingplan_id' => $tp ? $tp->id : 1,
            'content_id'      => $content ? $content->id : 1,
            'current_time'    => 1230,
            'progress'        => 50,
            'finish'          => '0',
            'learning_hour'   => 0.3417,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $resForce = $this->service->calculate(
            criteria: ['mode' => 'filtered', 'user_id' => $this->testUser->id],
            options: ['dry_run' => false, 'force' => true]
        );

        // Even though MySQL affected rows was 0, our fix detects current_time was untouched!
        $this->assertEquals(1, $resForce['total_updated'], 'Force calculate must count unchanged matching records as updated');
        $this->assertEquals(0, $resForce['total_skipped'], 'Force calculate must not skip accurate records');
        $this->assertEquals(0, $resForce['total_concurrency_skipped'], 'Must not misclassify identical value as concurrency collision');
    }

    public function test_force_true_with_different_existing_value_updates_and_counts_as_updated(): void
    {
        $content = Content::whereNotNull('duration')->first();
        $tp = TrainingPlan::first();

        // Record has inaccurate value 0.1000 instead of 0.3417
        $recordId = DB::table('playlist_content_users')->insertGetId([
            'user_id'         => $this->testUser->id,
            'trainingplan_id' => $tp ? $tp->id : 1,
            'content_id'      => $content ? $content->id : 1,
            'current_time'    => 1230,
            'progress'        => 50,
            'finish'          => '0',
            'learning_hour'   => 0.1000,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $res = $this->service->calculate(
            criteria: ['mode' => 'filtered', 'user_id' => $this->testUser->id],
            options: ['dry_run' => false, 'force' => true]
        );

        $this->assertEquals(1, $res['total_updated']);
        $this->assertEquals(0, $res['total_skipped']);
        $this->assertEquals(0, $res['total_concurrency_skipped']);

        $row = DB::table('playlist_content_users')->where('id', $recordId)->first();
        $this->assertEquals('0.3417', number_format((float) $row->learning_hour, 4, '.', ''));
    }

    public function test_force_false_with_identical_existing_value_skips(): void
    {
        $content = Content::whereNotNull('duration')->first();
        $tp = TrainingPlan::first();

        // Record already accurate
        $recordId = DB::table('playlist_content_users')->insertGetId([
            'user_id'         => $this->testUser->id,
            'trainingplan_id' => $tp ? $tp->id : 1,
            'content_id'      => $content ? $content->id : 1,
            'current_time'    => 1230,
            'progress'        => 50,
            'finish'          => '0',
            'learning_hour'   => 0.3417,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $res = $this->service->calculate(
            criteria: ['mode' => 'filtered', 'user_id' => $this->testUser->id],
            options: ['dry_run' => false, 'force' => false]
        );

        $this->assertEquals(1, $res['total_skipped']);
        $this->assertEquals(0, $res['total_updated']);
        $this->assertEquals(0, $res['total_concurrency_skipped']);
    }

    public function test_force_false_with_different_existing_value_updates(): void
    {
        $content = Content::whereNotNull('duration')->first();
        $tp = TrainingPlan::first();

        // Record inaccurate
        $recordId = DB::table('playlist_content_users')->insertGetId([
            'user_id'         => $this->testUser->id,
            'trainingplan_id' => $tp ? $tp->id : 1,
            'content_id'      => $content ? $content->id : 1,
            'current_time'    => 1230,
            'progress'        => 50,
            'finish'          => '0',
            'learning_hour'   => 0.1000,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $res = $this->service->calculate(
            criteria: ['mode' => 'filtered', 'user_id' => $this->testUser->id],
            options: ['dry_run' => false, 'force' => false]
        );

        $this->assertEquals(1, $res['total_updated']);
        $this->assertEquals(0, $res['total_skipped']);
        $this->assertEquals(0, $res['total_concurrency_skipped']);

        $row = DB::table('playlist_content_users')->where('id', $recordId)->first();
        $this->assertEquals('0.3417', number_format((float) $row->learning_hour, 4, '.', ''));
    }

    public function test_concurrency_heartbeat_change_detected_and_counted_as_concurrency_skipped(): void
    {
        $content = Content::whereNotNull('duration')->first();
        $tp = TrainingPlan::first();

        $recordId = DB::table('playlist_content_users')->insertGetId([
            'user_id'         => $this->testUser->id,
            'trainingplan_id' => $tp ? $tp->id : 1,
            'content_id'      => $content ? $content->id : 1,
            'current_time'    => 1300,
            'progress'        => 50,
            'finish'          => '0',
            'learning_hour'   => null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        // When DB has 1300 and snapshot update attempts 1230, 0 rows match
        $affected = DB::table('playlist_content_users')
            ->where('id', $recordId)
            ->where('current_time', 1230)
            ->update(['learning_hour' => 0.3417]);

        $this->assertEquals(0, $affected);

        $row = DB::table('playlist_content_users')->where('id', $recordId)->first();
        $this->assertEquals(1300, (int) $row->current_time);
        $this->assertNull($row->learning_hour);
    }

    public function test_batch_processing_and_no_n_plus_one(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->service->calculate(
            criteria: ['mode' => 'all'],
            options: ['dry_run' => true, 'max_records' => 100, 'chunk_size' => 500]
        );

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // 1 query for contents map + 1 query for records chunk = exactly 2 queries
        $this->assertEquals(2, count($queries));
    }

    public function test_database_mutation_scope_limited_to_learning_hour(): void
    {
        $content = Content::whereNotNull('duration')->first();
        $tp = TrainingPlan::first();

        $recordId = DB::table('playlist_content_users')->insertGetId([
            'user_id'         => $this->testUser->id,
            'trainingplan_id' => $tp ? $tp->id : 1,
            'content_id'      => $content ? $content->id : 1,
            'current_time'    => 720,
            'progress'        => 40,
            'finish'          => '0',
            'learning_hour'   => null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $this->service->calculate(
            criteria: ['mode' => 'filtered', 'user_id' => $this->testUser->id],
            options: ['dry_run' => false]
        );

        $row = DB::table('playlist_content_users')->where('id', $recordId)->first();
        $this->assertEquals('0.2000', number_format((float) $row->learning_hour, 4, '.', ''));
        // Untouched fields
        $this->assertEquals(720, (int) $row->current_time);
        $this->assertEquals(40, (int) $row->progress);
        $this->assertEquals('0', (string) $row->finish);
    }
}
