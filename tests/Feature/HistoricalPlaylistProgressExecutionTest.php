<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\LearningHourCalculationBatch;
use App\Models\PlaylistContentUser;
use App\Models\RoleAccessPermission;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use App\Services\LearningHours\HistoricalPlaylistProgressExecutionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class HistoricalPlaylistProgressExecutionTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $regularUser;
    protected Classification $classification;
    protected string $cutoff = '2021-12-31 23:59:59';
    protected HistoricalPlaylistProgressExecutionService $executionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->executionService = app(HistoricalPlaylistProgressExecutionService::class);
        $this->executionService->releaseLock();
        Cache::forget(HistoricalPlaylistProgressExecutionService::ACTIVE_EXECUTION_CACHE_KEY);

        $this->admin = User::factory()->create([
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-sso-adm-' . Str::uuid(),
            'sap_code'   => 'SAP-ADM-' . rand(10000, 99999),
            'role'       => User::ROLE_ADMIN,
            'active'     => 1,
        ]);

        $this->regularUser = User::factory()->create([
            'team'       => 'Operations',
            'department' => 'Plant',
            'division'   => 'Steel',
            'ssohash'    => 'test-sso-usr-' . Str::uuid(),
            'sap_code'   => 'SAP-USR-' . rand(10000, 99999),
            'role'       => User::ROLE_USER,
            'active'     => 1,
        ]);

        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_ADMIN, 'module_key' => 'learning_hours_management'],
            ['can_access' => true]
        );

        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_USER, 'module_key' => 'learning_hours_management'],
            ['can_access' => false]
        );

        $this->classification = Classification::firstOrCreate(
            ['classification_name' => 'Execution Test Classification'],
            ['slug' => 'execution-test-classification']
        );
    }

    protected function tearDown(): void
    {
        $this->executionService->releaseLock();
        Cache::forget(HistoricalPlaylistProgressExecutionService::ACTIVE_EXECUTION_CACHE_KEY);
        parent::tearDown();
    }

    protected function createOldPlaylist(string $title, string $createdAt = '2021-06-01 10:00:00'): TrainingPlan
    {
        return TrainingPlan::create([
            'title'      => $title,
            'slug'       => Str::slug($title) . '-' . rand(1000, 9999),
            'active'     => 'Active',
            'created_at' => Carbon::parse($createdAt),
            'updated_at' => Carbon::parse($createdAt),
        ]);
    }

    protected function createNewPlaylist(string $title, string $createdAt = '2022-06-01 10:00:00'): TrainingPlan
    {
        return TrainingPlan::create([
            'title'      => $title,
            'slug'       => Str::slug($title) . '-' . rand(1000, 9999),
            'active'     => 'Active',
            'created_at' => Carbon::parse($createdAt),
            'updated_at' => Carbon::parse($createdAt),
        ]);
    }

    protected function createContent(string $title): Content
    {
        return Content::create([
            'title'             => $title,
            'slug'              => Str::slug($title) . '-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);
    }

    protected function createLearner(string $name): User
    {
        return User::factory()->create([
            'name'       => $name,
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-sso-learner-' . Str::uuid(),
            'sap_code'   => 'SAP-LN-' . rand(10000, 99999),
            'role'       => User::ROLE_USER,
            'active'     => 1,
        ]);
    }

    /**
     * 1. Execute endpoint requires authentication.
     */
    public function test_execute_requires_authentication(): void
    {
        $response = $this->postJson(route('manage.learning-hours.dry-run.execute'), [
            'cutoff' => $this->cutoff,
        ]);

        $response->assertStatus(401);
    }

    /**
     * 2. Execute endpoint requires learning_hours_management permission.
     */
    public function test_execute_requires_learning_hours_management_permission(): void
    {
        $response = $this->actingAs($this->regularUser)->postJson(route('manage.learning-hours.dry-run.execute'), [
            'cutoff' => $this->cutoff,
        ]);

        $response->assertStatus(403);
    }

    /**
     * 3. GET execute endpoint cannot mutate (MethodNotAllowed / Read-only status endpoint).
     */
    public function test_get_execute_endpoint_cannot_mutate(): void
    {
        // GET on /execute is not allowed
        $response = $this->actingAs($this->admin)->get('/manage/learning-hours/dry-run/execute');
        $this->assertContains($response->getStatusCode(), [404, 405]);

        // GET on status endpoint is strictly read-only and performs 0 mutations
        $pcuBefore = PlaylistContentUser::count();
        $statusResponse = $this->actingAs($this->admin)->get(route('manage.learning-hours.dry-run.status', ['executionId' => 'non-existent-999']));
        $this->assertEquals(404, $statusResponse->getStatusCode());
        $this->assertEquals($pcuBefore, PlaylistContentUser::count(), 'Status polling endpoint must never mutate database');
    }

    /**
     * 4. Execute requires valid scope.
     */
    public function test_execute_requires_valid_scope(): void
    {
        $plan = $this->createOldPlaylist('Valid Scope Plan');
        $content = $this->createContent('Valid Scope Content');
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        $learner = $this->createLearner('Scope Learner');
        ContentUser::create([
            'user_id'    => $learner->id,
            'content_id' => $content->id,
            'progress'   => 50,
            'finish'     => '0',
        ]);

        $response = $this->actingAs($this->admin)->postJson(route('manage.learning-hours.dry-run.execute'), [
            'cutoff'      => $this->cutoff,
            'playlist_id' => $plan->id,
            'chunk_size'  => 500,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'execution' => [
                'playlist_id' => $plan->id,
                'status'      => 'processing',
            ],
        ]);
    }

    /**
     * 5. Execute requires CSRF protection for web requests.
     */
    public function test_execute_requires_csrf(): void
    {
        // 1. Verify route has 'web' middleware group which registers VerifyCsrfToken
        $route = app('router')->getRoutes()->getByName('manage.learning-hours.dry-run.execute');
        $this->assertNotNull($route);
        $this->assertContains('web', $route->gatherMiddleware());

        // 2. Verify route is NOT in CSRF exclusion list ($except)
        $csrfMiddleware = app(\App\Http\Middleware\VerifyCsrfToken::class);
        $reflection = new \ReflectionClass($csrfMiddleware);
        $exceptProp = $reflection->getProperty('except');
        $exceptProp->setAccessible(true);
        $except = $exceptProp->getValue($csrfMiddleware);
        $this->assertNotContains('manage/learning-hours/dry-run/execute', $except);

        // 3. Test that VerifyCsrfToken throws TokenMismatchException when CSRF token is missing
        $request = \Illuminate\Http\Request::create(route('manage.learning-hours.dry-run.execute'), 'POST', [
            'cutoff' => $this->cutoff,
        ]);
        $request->setLaravelSession(app('session')->driver());

        $enforcedCsrfMiddleware = new class(app(), app('encrypter')) extends \App\Http\Middleware\VerifyCsrfToken {
            protected function runningUnitTests() {
                return false;
            }
        };

        $this->expectException(\Illuminate\Session\TokenMismatchException::class);
        $enforcedCsrfMiddleware->handle($request, function () {
            return response('OK');
        });
    }

    /**
     * 6. Existing PCU is never overwritten (PCU authoritative / insertOrIgnore).
     */
    public function test_existing_pcu_is_never_overwritten(): void
    {
        $plan = $this->createOldPlaylist('Overwrite Guard Plan');
        $content = $this->createContent('Overwrite Guard Content');
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        $learner = $this->createLearner('Overwrite Learner');

        // Pre-existing PCU has 30%
        PlaylistContentUser::create([
            'user_id'         => $learner->id,
            'trainingplan_id' => $plan->id,
            'content_id'      => $content->id,
            'progress'        => 30,
            'finish'          => '0',
            'learning_status' => 'in_progress',
        ]);

        // Source in content_users has 100%
        ContentUser::create([
            'user_id'         => $learner->id,
            'content_id'      => $content->id,
            'progress'        => 100,
            'finish'          => '1',
            'learning_status' => 'completed',
        ]);

        $exec = $this->executionService->startExecution($this->cutoff, $plan->id, 1000);
        $step = $this->executionService->executeStep($exec['execution_id']);

        $this->assertTrue($step['success']);
        $this->assertEquals(0, $step['inserted_records'], 'Overlapping PCU record must be skipped');
        $this->assertEquals(1, $step['skipped_records']);

        $pcu = PlaylistContentUser::where('user_id', $learner->id)
            ->where('trainingplan_id', $plan->id)
            ->where('content_id', $content->id)
            ->first();

        $this->assertNotNull($pcu);
        $this->assertEquals(30, $pcu->progress, 'Existing PCU progress must remain 30% and never be overwritten');
        $this->assertEquals('0', $pcu->finish);
    }

    /**
     * 7. Best progress source is selected (Phase 3 priority: finish, progress, current_time, id).
     */
    public function test_best_progress_source_is_selected(): void
    {
        $plan = $this->createOldPlaylist('Priority Source Plan');
        $content = $this->createContent('Priority Source Content');
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        $learner = $this->createLearner('Priority Learner');

        // Older ID with lower progress (ID 10, 0%)
        ContentUser::create([
            'user_id'      => $learner->id,
            'content_id'   => $content->id,
            'progress'     => 0,
            'finish'       => '0',
            'current_time' => 0,
        ]);

        // Newer ID with complete progress (ID 20, 100%)
        ContentUser::create([
            'user_id'      => $learner->id,
            'content_id'   => $content->id,
            'progress'     => 100,
            'finish'       => '1',
            'current_time' => 500,
        ]);

        $exec = $this->executionService->startExecution($this->cutoff, $plan->id, 1000);
        $step = $this->executionService->executeStep($exec['execution_id']);

        $this->assertTrue($step['success']);
        $this->assertEquals(1, $step['inserted_records']);

        $pcu = PlaylistContentUser::where('user_id', $learner->id)
            ->where('trainingplan_id', $plan->id)
            ->where('content_id', $content->id)
            ->first();

        $this->assertNotNull($pcu);
        $this->assertEquals(100, $pcu->progress, 'Best source progress (100%) must be selected over 0%');
        $this->assertEquals('1', $pcu->finish);
        $this->assertEquals(500, $pcu->current_time);
    }

    /**
     * 8. Duplicate execution is idempotent (Re-running produces 0 duplicates).
     */
    public function test_duplicate_execution_is_idempotent(): void
    {
        $plan = $this->createOldPlaylist('Idempotent Plan');
        $content = $this->createContent('Idempotent Content');
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        $learner = $this->createLearner('Idempotent Learner');
        ContentUser::create([
            'user_id'    => $learner->id,
            'content_id' => $content->id,
            'progress'   => 80,
            'finish'     => '0',
        ]);

        // Execution 1
        $exec1 = $this->executionService->startExecution($this->cutoff, $plan->id, 1000);
        $this->executionService->executeStep($exec1['execution_id']);
        $this->executionService->releaseLock();

        $countAfterFirst = PlaylistContentUser::where('user_id', $learner->id)
            ->where('trainingplan_id', $plan->id)
            ->where('content_id', $content->id)
            ->count();
        $this->assertEquals(1, $countAfterFirst);

        // Execution 2 on identical scope
        $exec2 = $this->executionService->startExecution($this->cutoff, $plan->id, 1000);
        $step2 = $this->executionService->executeStep($exec2['execution_id']);
        $this->executionService->releaseLock();

        $this->assertEquals(0, $step2['inserted_records']);
        $this->assertEquals(1, $step2['skipped_records']);

        $countAfterSecond = PlaylistContentUser::where('user_id', $learner->id)
            ->where('trainingplan_id', $plan->id)
            ->where('content_id', $content->id)
            ->count();
        $this->assertEquals(1, $countAfterSecond, 'Second execution must produce 0 duplicate records');
    }

    /**
     * 9. Concurrent execution is blocked (Lock protection).
     */
    public function test_concurrent_execution_is_blocked(): void
    {
        $plan = $this->createOldPlaylist('Concurrency Plan');
        $content = $this->createContent('Concurrency Content');
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        $learner = $this->createLearner('Concurrency Learner');
        ContentUser::create([
            'user_id'    => $learner->id,
            'content_id' => $content->id,
            'progress'   => 40,
            'finish'     => '0',
        ]);

        // Start execution 1
        $exec1 = $this->executionService->startExecution($this->cutoff, $plan->id, 1000);
        $this->assertNotNull($exec1['execution_id']);

        // Attempt execution 2 while execution 1 is processing
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Migration is currently in progress.');

        $this->executionService->startExecution($this->cutoff, $plan->id, 1000);
    }

    /**
     * 10. Single chunk execution processes and completes.
     */
    public function test_single_chunk_execution(): void
    {
        $plan = $this->createOldPlaylist('Single Chunk Plan');
        $content1 = $this->createContent('Content S1');
        $content2 = $this->createContent('Content S2');

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content1->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content2->id, 'content_order' => 1]);

        $learner = $this->createLearner('Single Chunk Learner');
        ContentUser::create(['user_id' => $learner->id, 'content_id' => $content1->id, 'progress' => 50, 'finish' => '0']);
        ContentUser::create(['user_id' => $learner->id, 'content_id' => $content2->id, 'progress' => 100, 'finish' => '1']);

        $exec = $this->executionService->startExecution($this->cutoff, $plan->id, chunkSize: 10);
        $this->assertFalse($exec['is_completed']);

        $step = $this->executionService->executeStep($exec['execution_id']);

        $this->assertTrue($step['success']);
        $this->assertTrue($step['is_completed']);
        $this->assertEquals(2, $step['inserted_records']);
        $this->assertEquals(100.0, $step['percentage']);
    }

    /**
     * 11. Multiple chunks complete migration step-by-step.
     */
    public function test_multiple_chunks_complete_migration(): void
    {
        $plan = $this->createOldPlaylist('Multi Chunk Plan');
        $content = $this->createContent('Multi Chunk Content');
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        // Create 3 learners with chunkSize = 1
        $l1 = $this->createLearner('L1');
        $l2 = $this->createLearner('L2');
        $l3 = $this->createLearner('L3');

        ContentUser::create(['user_id' => $l1->id, 'content_id' => $content->id, 'progress' => 10]);
        ContentUser::create(['user_id' => $l2->id, 'content_id' => $content->id, 'progress' => 20]);
        ContentUser::create(['user_id' => $l3->id, 'content_id' => $content->id, 'progress' => 30]);

        $exec = $this->executionService->startExecution($this->cutoff, $plan->id, chunkSize: 1);
        $this->assertEquals(3, $exec['total_candidates']);

        // Step 1 (processes 1 record)
        $step1 = $this->executionService->executeStep($exec['execution_id']);
        $this->assertFalse($step1['is_completed']);
        $this->assertEquals(1, $step1['processed_records']);
        $this->assertEquals(1, $step1['inserted_records']);

        // Step 2 (processes 2nd record)
        $step2 = $this->executionService->executeStep($exec['execution_id']);
        $this->assertFalse($step2['is_completed']);
        $this->assertEquals(2, $step2['processed_records']);
        $this->assertEquals(2, $step2['inserted_records']);

        // Step 3 (processes 3rd record & completes)
        $step3 = $this->executionService->executeStep($exec['execution_id']);
        $this->assertTrue($step3['is_completed']);
        $this->assertEquals(3, $step3['processed_records']);
        $this->assertEquals(3, $step3['inserted_records']);
        $this->assertEquals(100.0, $step3['percentage']);
    }

    /**
     * 12. Partial failure can retry safely.
     */
    public function test_partial_failure_can_retry(): void
    {
        $plan = $this->createOldPlaylist('Retry Plan');
        $content = $this->createContent('Retry Content');
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        $learner = $this->createLearner('Retry Learner');
        ContentUser::create(['user_id' => $learner->id, 'content_id' => $content->id, 'progress' => 50]);

        $exec = $this->executionService->startExecution($this->cutoff, $plan->id, 10);

        // Manually simulate batch failure state
        $batch = LearningHourCalculationBatch::find($exec['batch_id']);
        $batch->update(['status' => 'failed']);

        // Calling executeStep on failed batch transitions back and completes
        $step = $this->executionService->executeStep($exec['execution_id']);
        $this->assertTrue($step['success']);
        $this->assertTrue($step['is_completed']);
        $this->assertEquals(1, $step['inserted_records']);
    }

    /**
     * 13. Scoped playlist only migrates selected playlist.
     */
    public function test_scoped_playlist_only_migrates_selected_playlist(): void
    {
        $planA = $this->createOldPlaylist('Scoped Plan A');
        $planB = $this->createOldPlaylist('Scoped Plan B');

        $contentA = $this->createContent('Scoped Content A');
        $contentB = $this->createContent('Scoped Content B');

        TrainingPlanDetail::create(['trainingplan_id' => $planA->id, 'content_id' => $contentA->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $planB->id, 'content_id' => $contentB->id, 'content_order' => 0]);

        $learner = $this->createLearner('Scoped Learner');
        ContentUser::create(['user_id' => $learner->id, 'content_id' => $contentA->id, 'progress' => 70]);
        ContentUser::create(['user_id' => $learner->id, 'content_id' => $contentB->id, 'progress' => 80]);

        // Migrate ONLY Plan A
        $exec = $this->executionService->startExecution($this->cutoff, $planA->id, 100);
        $this->executionService->executeStep($exec['execution_id']);
        $this->executionService->releaseLock();

        // Verify PCU for Plan A exists
        $pcuA = PlaylistContentUser::where('user_id', $learner->id)->where('trainingplan_id', $planA->id)->first();
        $this->assertNotNull($pcuA);
        $this->assertEquals(70, $pcuA->progress);

        // Verify PCU for Plan B was NOT created
        $pcuB = PlaylistContentUser::where('user_id', $learner->id)->where('trainingplan_id', $planB->id)->first();
        $this->assertNull($pcuB, 'Playlist B must not be migrated when scoped to Playlist A');
    }

    /**
     * 14. All scope migrates all historical playlists.
     */
    public function test_all_scope_migrates_all_historical_playlists(): void
    {
        $planA = $this->createOldPlaylist('All Scope A');
        $planB = $this->createOldPlaylist('All Scope B');

        $contentA = $this->createContent('All Scope Content A');
        $contentB = $this->createContent('All Scope Content B');

        TrainingPlanDetail::create(['trainingplan_id' => $planA->id, 'content_id' => $contentA->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $planB->id, 'content_id' => $contentB->id, 'content_order' => 0]);

        $learner = $this->createLearner('All Scope Learner');
        ContentUser::create(['user_id' => $learner->id, 'content_id' => $contentA->id, 'progress' => 60]);
        ContentUser::create(['user_id' => $learner->id, 'content_id' => $contentB->id, 'progress' => 90]);

        // Migrate with playlist_id = null (all old playlists)
        $exec = $this->executionService->startExecution($this->cutoff, null, 1000);

        while (!$exec['is_completed']) {
            $exec = $this->executionService->executeStep($exec['execution_id']);
        }
        $this->executionService->releaseLock();

        $pcuA = PlaylistContentUser::where('user_id', $learner->id)->where('trainingplan_id', $planA->id)->first();
        $pcuB = PlaylistContentUser::where('user_id', $learner->id)->where('trainingplan_id', $planB->id)->first();

        $this->assertNotNull($pcuA);
        $this->assertNotNull($pcuB);
        $this->assertEquals(60, $pcuA->progress);
        $this->assertEquals(90, $pcuB->progress);
    }

    /**
     * 15. Execution state tracks progress in database.
     */
    public function test_execution_state_tracks_progress(): void
    {
        $plan = $this->createOldPlaylist('State Track Plan');
        $content = $this->createContent('State Track Content');
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        $learner = $this->createLearner('State Learner');
        ContentUser::create(['user_id' => $learner->id, 'content_id' => $content->id, 'progress' => 100, 'finish' => '1']);

        $exec = $this->executionService->startExecution($this->cutoff, $plan->id, 1000);
        $batch = LearningHourCalculationBatch::find($exec['batch_id']);

        $this->assertEquals('processing', $batch->status);
        $this->assertEquals(1, $batch->total_records);
        $this->assertEquals(0, $batch->processed_records);

        $this->executionService->executeStep($exec['execution_id']);

        $freshBatch = $batch->fresh();
        $this->assertEquals('completed', $freshBatch->status);
        $this->assertEquals(1, $freshBatch->processed_records);
        $this->assertNotNull($freshBatch->finished_at);
    }

    /**
     * 16. Completed execution allows starting next execution session without duplicate mutation.
     */
    public function test_completed_execution_cannot_start_duplicate_migration(): void
    {
        $plan = $this->createOldPlaylist('Subsequent Plan');
        $content = $this->createContent('Subsequent Content');
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        $learner = $this->createLearner('Subsequent Learner');
        ContentUser::create(['user_id' => $learner->id, 'content_id' => $content->id, 'progress' => 100, 'finish' => '1']);

        // First run completes
        $exec1 = $this->executionService->startExecution($this->cutoff, $plan->id, 100);
        $this->executionService->executeStep($exec1['execution_id']);
        $this->executionService->releaseLock();

        // Second run starts cleanly (not blocked) but detects 0 candidates to insert
        $exec2 = $this->executionService->startExecution($this->cutoff, $plan->id, 100);
        $step2 = $this->executionService->executeStep($exec2['execution_id']);
        $this->executionService->releaseLock();

        $this->assertEquals(0, $step2['inserted_records']);
        $this->assertEquals(1, $step2['skipped_records']);
        $this->assertEquals(1, PlaylistContentUser::where('user_id', $learner->id)->count());
    }

    /**
     * 17. CLI and Service share identical execution logic.
     */
    public function test_cli_and_service_use_same_execution_logic(): void
    {
        $plan = $this->createOldPlaylist('CLI Parity Plan');
        $content = $this->createContent('CLI Parity Content');
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        $learner = $this->createLearner('CLI Learner');
        ContentUser::create([
            'user_id'          => $learner->id,
            'content_id'       => $content->id,
            'progress'         => 75,
            'finish'           => '0',
            'learning_status'  => 'in_progress',
            'current_time'     => 150,
            'duration_seconds' => 200,
        ]);

        // Run artisan command
        $this->artisan('learning:migrate-playlist-progress', [
            '--playlist' => $plan->id,
            '--cutoff'   => $this->cutoff,
            '--force'    => true,
        ])->assertSuccessful();

        $pcu = PlaylistContentUser::where('user_id', $learner->id)
            ->where('trainingplan_id', $plan->id)
            ->where('content_id', $content->id)
            ->first();

        $this->assertNotNull($pcu);
        $this->assertEquals(75, $pcu->progress);
        $this->assertEquals('0', $pcu->finish);
        $this->assertEquals('in_progress', $pcu->learning_status);
        $this->assertEquals(150, $pcu->current_time);
        $this->assertEquals(200, $pcu->duration_seconds);
    }

    /**
     * 18. Multi-playlist isolation for shared contents.
     */
    public function test_multi_playlist_isolation(): void
    {
        $planA = $this->createOldPlaylist('Shared Plan A');
        $planB = $this->createOldPlaylist('Shared Plan B');
        $sharedContent = $this->createContent('Shared Content Across Playlists');

        TrainingPlanDetail::create(['trainingplan_id' => $planA->id, 'content_id' => $sharedContent->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $planB->id, 'content_id' => $sharedContent->id, 'content_order' => 0]);

        $learner = $this->createLearner('Shared Content Learner');
        ContentUser::create([
            'user_id'    => $learner->id,
            'content_id' => $sharedContent->id,
            'progress'   => 100,
            'finish'     => '1',
        ]);

        // Migrate all
        $exec = $this->executionService->startExecution($this->cutoff, null, 1000);
        while (!$exec['is_completed']) {
            $exec = $this->executionService->executeStep($exec['execution_id']);
        }
        $this->executionService->releaseLock();

        // PCU for Plan A and Plan B must both exist independently
        $pcuA = PlaylistContentUser::where('user_id', $learner->id)
            ->where('trainingplan_id', $planA->id)
            ->where('content_id', $sharedContent->id)
            ->first();

        $pcuB = PlaylistContentUser::where('user_id', $learner->id)
            ->where('trainingplan_id', $planB->id)
            ->where('content_id', $sharedContent->id)
            ->first();

        $this->assertNotNull($pcuA);
        $this->assertNotNull($pcuB);
        $this->assertNotEquals($pcuA->id, $pcuB->id);
        $this->assertEquals(100, $pcuA->progress);
        $this->assertEquals(100, $pcuB->progress);
    }
}
