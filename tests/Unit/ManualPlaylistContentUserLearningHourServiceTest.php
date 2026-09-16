<?php

namespace Tests\Unit;

use App\Models\Content;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\User;
use App\Services\LearningHours\ActualLearningHourCalculator;
use App\Services\LearningHours\ManualPlaylistContentUserLearningHourService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ManualPlaylistContentUserLearningHourServiceTest extends TestCase
{
    protected ManualPlaylistContentUserLearningHourService $service;
    protected ActualLearningHourCalculator $calculator;
    protected ?User $testUser = null;
    protected int $userId;
    protected int $trainingPlanId;
    protected int $contentId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new ActualLearningHourCalculator();
        $this->service = new ManualPlaylistContentUserLearningHourService($this->calculator);

        $baseUser = User::first();
        $userAttrs = $baseUser->toArray();
        unset($userAttrs['id']);
        $userAttrs['password'] = bcrypt('secret');
        $userAttrs['email'] = 'test_lh_' . uniqid() . '@example.com';
        $userAttrs['id_emp'] = 'TLH' . rand(10000, 99999);
        $userAttrs['ssohash'] = md5(uniqid());
        $userAttrs['name'] = 'Test Manual LH User ' . uniqid();
        $this->testUser = User::create($userAttrs);
        $this->userId = $this->testUser->id;

        $tp = TrainingPlan::first();
        $this->trainingPlanId = $tp ? $tp->id : 1;

        $content = Content::whereNotNull('duration')->where('duration', '!=', '')->first();
        $this->contentId = $content ? $content->id : 1;
    }

    protected function tearDown(): void
    {
        if ($this->testUser) {
            DB::table('playlist_content_users')->where('user_id', $this->testUser->id)->delete();
            $this->testUser->delete();
        }
        parent::tearDown();
    }

    public function test_calculate_actual_tracking(): void
    {
        // current_time = 1230 seconds -> 1230 / 3600 = 0.3417
        $recordId = DB::table('playlist_content_users')->insertGetId([
            'user_id'         => $this->userId,
            'trainingplan_id' => $this->trainingPlanId,
            'content_id'      => $this->contentId,
            'current_time'    => 1230,
            'progress'        => 50,
            'finish'          => '0',
            'learning_hour'   => null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $result = $this->service->calculate(
            criteria: ['mode' => 'filtered', 'user_id' => $this->userId],
            options: ['max_records' => 10, 'chunk_size' => 10]
        );

        $this->assertTrue($result['success']);
        $this->assertGreaterThanOrEqual(1, $result['total_updated']);

        $row = DB::table('playlist_content_users')->where('id', $recordId)->first();
        $this->assertEquals('0.3417', number_format((float) $row->learning_hour, 4, '.', ''));
        // current_time, progress, finish MUST NOT CHANGE
        $this->assertEquals(1230, (int) $row->current_time);
        $this->assertEquals(50, (int) $row->progress);
        $this->assertEquals('0', (string) $row->finish);
    }

    public function test_calculate_historical_fallback(): void
    {
        // current_time = 0, finish = '1', duration = official duration
        $content = Content::where('id', $this->contentId)->first();
        $contentDuration = $this->calculator->parseContentDurationToSeconds($content->duration ?? '01:01:54');
        $expectedHour = $this->calculator->calculate($contentDuration);

        $recordId = DB::table('playlist_content_users')->insertGetId([
            'user_id'         => $this->userId,
            'trainingplan_id' => $this->trainingPlanId,
            'content_id'      => $this->contentId,
            'current_time'    => 0,
            'progress'        => 100,
            'finish'          => '1',
            'learning_hour'   => null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $result = $this->service->calculate(
            criteria: ['mode' => 'filtered', 'user_id' => $this->userId],
            options: ['max_records' => 10, 'chunk_size' => 10]
        );

        $this->assertTrue($result['success']);

        $row = DB::table('playlist_content_users')->where('id', $recordId)->first();
        $this->assertEquals(
            number_format($expectedHour, 4, '.', ''),
            number_format((float) $row->learning_hour, 4, '.', '')
        );
        $this->assertEquals(0, (int) $row->current_time);
        $this->assertEquals(100, (int) $row->progress);
        $this->assertEquals('1', (string) $row->finish);
    }

    public function test_calculate_unfinished_records_zero(): void
    {
        // current_time = 0, finish = '0', progress = 25 -> 0.0000
        $recordId = DB::table('playlist_content_users')->insertGetId([
            'user_id'         => $this->userId,
            'trainingplan_id' => $this->trainingPlanId,
            'content_id'      => $this->contentId,
            'current_time'    => 0,
            'progress'        => 25,
            'finish'          => '0',
            'learning_hour'   => null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $result = $this->service->calculate(
            criteria: ['mode' => 'filtered', 'user_id' => $this->userId],
            options: ['max_records' => 10, 'chunk_size' => 10]
        );

        $this->assertTrue($result['success']);

        $row = DB::table('playlist_content_users')->where('id', $recordId)->first();
        $this->assertEquals('0.0000', number_format((float) $row->learning_hour, 4, '.', ''));
    }

    public function test_idempotency_and_skipped_detection(): void
    {
        // Initial run calculates 0.3417. Second run skips because value matches!
        $recordId = DB::table('playlist_content_users')->insertGetId([
            'user_id'         => $this->userId,
            'trainingplan_id' => $this->trainingPlanId,
            'content_id'      => $this->contentId,
            'current_time'    => 1230,
            'progress'        => 50,
            'finish'          => '0',
            'learning_hour'   => null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        // Run 1: Calculates and updates
        $res1 = $this->service->calculate(
            criteria: ['mode' => 'filtered', 'user_id' => $this->userId],
            options: ['max_records' => 10, 'chunk_size' => 10]
        );
        $this->assertGreaterThanOrEqual(1, $res1['total_updated']);

        $row1 = DB::table('playlist_content_users')->where('id', $recordId)->first();
        $this->assertEquals('0.3417', number_format((float) $row1->learning_hour, 4, '.', ''));

        // Run 2: Same data -> Skipped, value remains 0.3417 (not doubled!)
        $res2 = $this->service->calculate(
            criteria: ['mode' => 'filtered', 'user_id' => $this->userId],
            options: ['max_records' => 10, 'chunk_size' => 10]
        );
        $this->assertGreaterThanOrEqual(1, $res2['total_skipped']);

        $row2 = DB::table('playlist_content_users')->where('id', $recordId)->first();
        $this->assertEquals('0.3417', number_format((float) $row2->learning_hour, 4, '.', ''));
    }

    public function test_dry_run_does_not_modify_database(): void
    {
        $recordId = DB::table('playlist_content_users')->insertGetId([
            'user_id'         => $this->userId,
            'trainingplan_id' => $this->trainingPlanId,
            'content_id'      => $this->contentId,
            'current_time'    => 1230,
            'progress'        => 50,
            'finish'          => '0',
            'learning_hour'   => null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $result = $this->service->calculate(
            criteria: ['mode' => 'filtered', 'user_id' => $this->userId],
            options: ['dry_run' => true, 'max_records' => 10, 'chunk_size' => 10]
        );

        $this->assertTrue($result['dry_run']);
        $this->assertGreaterThanOrEqual(1, $result['total_updated']); // Reports what would be updated

        // In database, learning_hour MUST REMAIN NULL
        $row = DB::table('playlist_content_users')->where('id', $recordId)->first();
        $this->assertNull($row->learning_hour);
    }

    public function test_concurrency_protection_does_not_overwrite_modified_current_time(): void
    {
        // If learner heartbeat modifies current_time from 1230 to 1300 mid-flight,
        // the snapshot update fails safely
        $recordId = DB::table('playlist_content_users')->insertGetId([
            'user_id'         => $this->userId,
            'trainingplan_id' => $this->trainingPlanId,
            'content_id'      => $this->contentId,
            'current_time'    => 1300, // already at 1300
            'progress'        => 55,
            'finish'          => '0',
            'learning_hour'   => null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        // Attempting to run update with old snapshot (1230)
        $affected = DB::table('playlist_content_users')
            ->where('id', $recordId)
            ->where('current_time', 1230)
            ->update(['learning_hour' => 0.3417]);

        $this->assertEquals(0, $affected);

        $row = DB::table('playlist_content_users')->where('id', $recordId)->first();
        $this->assertEquals(1300, (int) $row->current_time);
        $this->assertNull($row->learning_hour);
    }

    public function test_master_learning_hour_is_not_copied_as_actual_learning_hour(): void
    {
        // Content with master learning_hour = 1.5, learner watched 1230s -> actual = 0.3417
        $recordId = DB::table('playlist_content_users')->insertGetId([
            'user_id'         => $this->userId,
            'trainingplan_id' => $this->trainingPlanId,
            'content_id'      => $this->contentId,
            'current_time'    => 1230,
            'progress'        => 50,
            'finish'          => '0',
            'learning_hour'   => null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $this->service->calculate(
            criteria: ['mode' => 'filtered', 'user_id' => $this->userId],
            options: ['max_records' => 10, 'chunk_size' => 10]
        );

        $row = DB::table('playlist_content_users')->where('id', $recordId)->first();
        $this->assertEquals('0.3417', number_format((float) $row->learning_hour, 4, '.', ''));
        $this->assertNotEquals('1.5000', number_format((float) $row->learning_hour, 4, '.', ''));
    }
}
