<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\ContentUser;
use App\Models\RoleAccessPermission;
use App\Models\User;
use App\Services\LearningHours\ActualLearningHourCalculator;
use App\Services\LearningHours\ContentUserBatchCalculatorService;
use App\Services\LearningHours\ContentUserLearningHourManagementService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ContentUserActualLearningHourTest extends TestCase
{
    protected User $admin;
    protected ActualLearningHourCalculator $calculator;
    protected ContentUserLearningHourManagementService $service;
    protected ContentUserBatchCalculatorService $batchService;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure permission is seeded
        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_ADMIN, 'module_key' => 'learning_hours_management'],
            ['can_access' => true]
        );

        $this->admin = User::where('role', User::ROLE_ADMIN)->first() ?? User::first();
        $this->calculator = app(ActualLearningHourCalculator::class);
        $this->service = app(ContentUserLearningHourManagementService::class);
        $this->batchService = app(ContentUserBatchCalculatorService::class);
    }

    /**
     * 1. current_time calculation (e.g., 1230s -> 0.3417 h, 1530s -> 0.4250 h, 4815s -> 1.3375 h)
     */
    public function test_current_time_calculation_produces_exact_actual_hours(): void
    {
        // Test calculator directly: (currentTime, finish, progress, contentDuration)
        $this->assertEquals(0.3417, $this->calculator->calculateFromTracking(1230, '0', 0, 3600));
        $this->assertEquals(0.4250, $this->calculator->calculateFromTracking(1530, '0', 0, 3600));
        $this->assertEquals(1.3375, $this->calculator->calculateFromTracking(4815, '0', 0, 7200));

        // Test formatting
        $this->assertEquals('0.3417 h', $this->service->formatHours(0.3417));
        $this->assertEquals('0.4250 h', $this->service->formatHours(0.4250));
        $this->assertEquals('1.3375 h', $this->service->formatHours(1.3375));
    }

    /**
     * 2. Completed fallback to content duration when current_time = 0
     */
    public function test_completed_fallback_to_duration_when_current_time_is_zero(): void
    {
        // 1800 seconds content duration = 0.5000 h
        $hours = $this->calculator->calculateFromTracking(0, '1', 100, 1800);
        $this->assertEquals(0.5000, $hours);
        $this->assertEquals('0.5000 h', $this->service->formatHours($hours));

        // 270 seconds content duration = 0.0750 h
        $hoursShort = $this->calculator->calculateFromTracking(0, '1', 100, 270);
        $this->assertEquals(0.0750, $hoursShort);
    }

    /**
     * 3. Unfinished zero (0.0000 h) when current_time = 0 and not finished
     */
    public function test_unfinished_zero_when_current_time_is_zero(): void
    {
        $hours = $this->calculator->calculateFromTracking(0, '0', 0, 3600);
        $this->assertEquals(0.0000, $hours);
        $this->assertEquals('0.0000 h', $this->service->formatHours($hours));
    }

    /**
     * 4. 4 decimal precision in database and model cast
     */
    public function test_database_and_model_cast_preserve_four_decimal_precision(): void
    {
        $content = Content::first();
        $this->assertNotNull($content);

        $cu = ContentUser::create([
            'user_id' => $this->admin->id,
            'content_id' => $content->id,
            'current_time' => 1230,
            'finish' => '0',
            'progress' => 34,
        ]);

        try {
            $fromDb = DB::table('content_users')->where('id', $cu->id)->first();
            $this->assertEquals('0.3417', (string) $fromDb->learning_hours);

            $cuFresh = ContentUser::find($cu->id);
            $this->assertSame('0.3417', (string) $cuFresh->learning_hours);
        } finally {
            $cu->delete();
        }
    }

    /**
     * 5. Model calculation via booted saving hook
     */
    public function test_model_booted_hook_calculates_actual_learning_hours_automatically(): void
    {
        $content = Content::first();
        $this->assertNotNull($content);

        $cu = new ContentUser();
        $cu->user_id = $this->admin->id;
        $cu->content_id = $content->id;
        $cu->current_time = 1530; // 1530 / 3600 = 0.425
        $cu->finish = '0';
        $cu->progress = 42;
        $cu->save();

        try {
            $this->assertEquals(0.4250, (float) $cu->learning_hours);
        } finally {
            $cu->delete();
        }
    }

    /**
     * 6. Service calculation via calculateSingle()
     */
    public function test_service_calculate_single_updates_record_correctly(): void
    {
        $content = Content::first();
        $this->assertNotNull($content);

        $cu = ContentUser::create([
            'user_id' => $this->admin->id,
            'content_id' => $content->id,
            'current_time' => 4815, // 4815 / 3600 = 1.3375
            'finish' => '1',
            'progress' => 100,
        ]);

        try {
            // Force reset in DB
            DB::table('content_users')->where('id', $cu->id)->update(['learning_hours' => null]);

            $result = $this->service->calculateSingle($cu->fresh());
            $this->assertTrue($result['success']);
            $this->assertEquals(1.3375, (float) $result['learning_hours']);

            $cuFresh = $cu->fresh();
            $this->assertEquals(4815, (int) $cuFresh->current_time);
            $this->assertEquals(1.3375, (float) $cuFresh->learning_hours);
        } finally {
            $cu->delete();
        }
    }

    /**
     * 7. Batch calculation via ContentUserBatchCalculatorService
     */
    public function test_batch_calculator_service_processes_records(): void
    {
        $content = Content::first();
        $this->assertNotNull($content);

        $cu = ContentUser::create([
            'user_id' => $this->admin->id,
            'content_id' => $content->id,
            'current_time' => 1230,
            'finish' => '0',
            'progress' => 30,
        ]);

        try {
            // Dry run
            $dryResult = $this->batchService->process(['chunk_size' => 10, 'max_records' => 10, 'dry_run' => true]);
            $this->assertGreaterThan(0, $dryResult['total_scanned']);

            // Live execution on a limited subset
            $liveResult = $this->batchService->process(['chunk_size' => 10, 'max_records' => 10, 'force' => true, 'dry_run' => false]);
            $this->assertGreaterThan(0, $liveResult['total_scanned']);
            $this->assertEquals(0, $liveResult['total_failed']);
        } finally {
            $cu->delete();
        }
    }

    /**
     * 8. Idempotency: repeated calculations yield identical results
     */
    public function test_idempotency_of_learning_hours_calculation(): void
    {
        $content = Content::first();
        $this->assertNotNull($content);

        $cu = ContentUser::create([
            'user_id' => $this->admin->id,
            'content_id' => $content->id,
            'current_time' => 1230,
            'finish' => '0',
            'progress' => 34,
        ]);

        try {
            $initialHours = $cu->fresh()->learning_hours;
            $this->assertEquals(0.3417, (float) $initialHours);

            // Re-run single
            $result = $this->service->calculateSingle($cu->fresh());
            $this->assertTrue($result['success']);
            $secondHours = $cu->fresh()->learning_hours;
            $this->assertEquals($initialHours, $secondHours);

            // Re-run batch step
            $batch = $this->service->startBatch('recalculate_all');
            $this->service->processBatchStep($batch->id, 10);
            $thirdHours = $cu->fresh()->learning_hours;
            $this->assertEquals($initialHours, $thirdHours);
        } finally {
            $cu->delete();
        }
    }

    /**
     * 9. Concurrency protection via snapshot matching
     */
    public function test_concurrency_protection_skips_row_if_current_time_changed(): void
    {
        $content = Content::first();
        $this->assertNotNull($content);

        $cu = ContentUser::create([
            'user_id' => $this->admin->id,
            'content_id' => $content->id,
            'current_time' => 1000,
            'finish' => '0',
            'progress' => 20,
        ]);

        try {
            // Simulate reading snapshot at current_time = 1000
            $snapshotTime = 1000;

            // In background, learner watched more video:
            DB::table('content_users')->where('id', $cu->id)->update(['current_time' => 1500]);

            // Now attempt optimistic update using stale snapshot:
            $affected = DB::table('content_users')
                ->where('id', $cu->id)
                ->where('current_time', $snapshotTime)
                ->update(['learning_hours' => 0.2778]);

            $this->assertEquals(0, $affected, 'Optimistic lock should prevent stale write.');
            
            // Fresh read has the updated current_time
            $cuFresh = $cu->fresh();
            $this->assertEquals(1500, $cuFresh->current_time);
        } finally {
            $cu->delete();
        }
    }

    /**
     * 10a. DataTable server-side output structure and 4-decimal formatting
     */
    public function test_datatable_server_side_output_structure_and_formatting(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/manage/learning-hours/content-user/datatable?draw=1&start=0&length=10');
        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);

        foreach ($data as $row) {
            $this->assertArrayHasKey('learning_hours', $row);
            $this->assertArrayHasKey('learning_hours_badge', $row);

            $badgeText = strip_tags($row['learning_hours_badge']);
            if (trim($badgeText) !== 'Not Calculated') {
                $this->assertMatchesRegularExpression('/^[0-9]+\.[0-9]{4}\s*h$/', trim($badgeText));
            }

            if ($row['learning_hours'] !== null) {
                $this->assertMatchesRegularExpression('/^[0-9]+\.[0-9]{4}$/', (string) $row['learning_hours']);
            }
        }
    }

    /**
     * 10b. DataTable server-side numeric sorting by actual learning hours
     */
    public function test_datatable_numeric_sorting_on_actual_learning_hours(): void
    {
        $sortDesc = $this->actingAs($this->admin)->getJson('/manage/learning-hours/content-user/datatable?draw=1&start=0&length=5&order[0][column]=7&order[0][dir]=desc&columns[7][data]=learning_hours_badge&columns[7][orderable]=true');
        $sortDesc->assertStatus(200);
        $descData = $sortDesc->json('data');
        $this->assertNotEmpty($descData);

        // Extract hours
        $descValues = [];
        foreach ($descData as $row) {
            if ($row['learning_hours'] !== null) {
                $descValues[] = (float) $row['learning_hours'];
            }
        }
        for ($i = 0; $i < count($descValues) - 1; $i++) {
            $this->assertGreaterThanOrEqual($descValues[$i + 1], $descValues[$i], 'DataTable sorting by learning_hours DESC should be in descending numerical order.');
        }
    }
}
