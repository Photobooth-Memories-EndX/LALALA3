<?php

namespace Tests\Feature;

use App\Http\Controllers\Manage\LearningHours\PlaylistProgressDryRunController;
use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\RoleAccessPermission;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use App\Services\LearningHours\PlaylistProgressDryRunService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ReflectionClass;
use Tests\TestCase;

class PlaylistProgressDryRunTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $regularUser;
    protected Classification $classification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', User::ROLE_ADMIN)->first() ?? User::factory()->create([
            'role'       => User::ROLE_ADMIN,
            'ssohash'    => 'test-sso-' . Str::uuid(),
            'sap_code'   => 'SAP-ADM-' . rand(10000, 99999),
            'active'     => 1,
        ]);

        $this->regularUser = User::where('role', User::ROLE_USER)->first() ?? User::factory()->create([
            'role'       => User::ROLE_USER,
            'ssohash'    => 'test-sso-' . Str::uuid(),
            'sap_code'   => 'SAP-USR-' . rand(10000, 99999),
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
            ['classification_name' => 'Dry Run Test Classification'],
            ['slug' => 'dry-run-test-classification']
        );
    }

    /**
     * 1. Authorized user can open dry-run page.
     */
    public function test_1_authorized_user_can_access_dry_run_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.learning-hours.dry-run.index'));

        $response->assertStatus(200);
        $response->assertSee('Historical Playlist Progress Migration — Dry Run');
        $response->assertSee('STRICTLY READ-ONLY PREVIEW');
        $response->assertSee('Total Unique Candidates');
        $response->assertSee('New PCU to Create');
        $response->assertSee('Source Data Validation');
    }

    /**
     * 2. User without permission receives 403 Forbidden.
     */
    public function test_2_unauthorized_user_gets_403(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('manage.learning-hours.dry-run.index'));

        $response->assertStatus(403);
    }

    /**
     * 3. Guest redirects to login.
     */
    public function test_3_guest_redirects_to_login(): void
    {
        $response = $this->get(route('manage.learning-hours.dry-run.index'));

        $response->assertRedirect('/login');
    }

    /**
     * 4. Page performs ZERO mutations on the database.
     */
    public function test_4_dry_run_page_performs_zero_database_mutations(): void
    {
        $pcuBefore = PlaylistContentUser::count();
        $cuBefore = ContentUser::count();
        $tpBefore = TrainingPlan::count();

        $response = $this->actingAs($this->admin)->get(route('manage.learning-hours.dry-run.index'));
        $response->assertStatus(200);

        $pcuAfter = PlaylistContentUser::count();
        $cuAfter = ContentUser::count();
        $tpAfter = TrainingPlan::count();

        $this->assertEquals($pcuBefore, $pcuAfter, 'PlaylistContentUser count must remain identical');
        $this->assertEquals($cuBefore, $cuAfter, 'ContentUser count must remain identical');
        $this->assertEquals($tpBefore, $tpAfter, 'TrainingPlan count must remain identical');
    }

    /**
     * 5. Summary metrics are mathematically consistent:
     *    total_candidates - skipped_existing == to_insert
     *    finished + in_progress + zero == total_candidates
     */
    public function test_5_summary_metrics_are_consistent(): void
    {
        $service = app(PlaylistProgressDryRunService::class);
        $metrics = $service->getSummaryMetrics();

        $this->assertArrayHasKey('total_candidates', $metrics);
        $this->assertArrayHasKey('to_insert_count', $metrics);
        $this->assertArrayHasKey('skipped_existing_count', $metrics);

        $expectedToInsert = max(0, $metrics['total_candidates'] - $metrics['skipped_existing_count']);
        $this->assertEquals($expectedToInsert, $metrics['to_insert_count']);

        $sumBreakdown = $metrics['finished_count'] + $metrics['in_progress_count'] + $metrics['zero_count'];
        $this->assertEquals($metrics['total_candidates'], $sumBreakdown, 'Breakdown sum must equal total_candidates');
    }

    /**
     * 6. Pagination works server-side.
     */
    public function test_6_pagination_works_server_side(): void
    {
        $service = app(PlaylistProgressDryRunService::class);

        $page1 = $service->getCandidatesPaginated(PlaylistProgressDryRunService::DEFAULT_CUTOFF, null, 10, 1);
        $this->assertLessThanOrEqual(10, $page1->count());
        $this->assertEquals(1, $page1->currentPage());

        if ($page1->total() > 10) {
            $page2 = $service->getCandidatesPaginated(PlaylistProgressDryRunService::DEFAULT_CUTOFF, null, 10, 2);
            $this->assertEquals(2, $page2->currentPage());
        }
    }

    /**
     * 7. Filter by playlist works correctly.
     */
    public function test_7_filter_by_playlist_works(): void
    {
        $plan = TrainingPlan::create([
            'title'      => 'Test Specific Playlist Filter',
            'slug'       => 'test-spec-filter-' . rand(1000, 9999),
            'active'     => 'Active',
            'created_at' => Carbon::parse('2026-08-01 10:00:00'),
            'updated_at' => Carbon::parse('2026-08-01 10:00:00'),
        ]);

        $content = Content::create([
            'title'             => 'Test Filter Content',
            'slug'              => 'test-filter-content-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => $plan->id,
            'content_id'      => $content->id,
            'content_order'   => 0,
        ]);

        ContentUser::create([
            'user_id'          => $this->admin->id,
            'content_id'       => $content->id,
            'progress'         => 100,
            'finish'           => '1',
            'learning_status'  => 'completed',
        ]);

        $response = $this->actingAs($this->admin)->get(route('manage.learning-hours.dry-run.index', [
            'playlist_id' => $plan->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee($plan->title);
    }

    /**
     * 8. Existing PCU is displayed with already_exists = 1 (Existing Skip).
     */
    public function test_8_existing_pcu_displayed_as_existing_skip(): void
    {
        $plan = TrainingPlan::create([
            'title'      => 'Test Existing PCU Display Plan',
            'slug'       => 'test-existing-pcu-' . rand(1000, 9999),
            'active'     => 'Active',
            'created_at' => Carbon::parse('2026-08-01 10:00:00'),
        ]);

        $content = Content::create([
            'title'             => 'Test Existing PCU Content',
            'slug'              => 'test-existing-content-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        ContentUser::create([
            'user_id'          => $this->admin->id,
            'content_id'       => $content->id,
            'progress'         => 100,
            'finish'           => '1',
            'learning_status'  => 'completed',
        ]);

        // Pre-create PCU
        PlaylistContentUser::create([
            'user_id'          => $this->admin->id,
            'trainingplan_id'  => $plan->id,
            'content_id'       => $content->id,
            'progress'         => 60,
            'finish'           => '0',
            'learning_status'  => 'in_progress',
        ]);

        $service = app(PlaylistProgressDryRunService::class);
        $candidates = $service->getCandidatesPaginated(PlaylistProgressDryRunService::DEFAULT_CUTOFF, $plan->id, 25, 1);

        $matchingCandidate = collect($candidates->items())->first(fn ($c) => $c->user_id == $this->admin->id && $c->content_id == $content->id);
        $this->assertNotNull($matchingCandidate);
        $this->assertEquals(1, $matchingCandidate->already_exists, 'Candidate must be flagged already_exists=1');
    }

    /**
     * 9. Strictly NO Artisan::call() used in Service or Controller.
     */
    public function test_9_no_artisan_call_in_dry_run_classes(): void
    {
        $controllerRefl = new ReflectionClass(PlaylistProgressDryRunController::class);
        $serviceRefl = new ReflectionClass(PlaylistProgressDryRunService::class);

        $controllerFile = file_get_contents($controllerRefl->getFileName());
        $serviceFile = file_get_contents($serviceRefl->getFileName());

        $this->assertStringNotContainsString('Artisan::call', $controllerFile, 'Controller must not call Artisan::call');
        $this->assertStringNotContainsString('Artisan::call', $serviceFile, 'Service must not call Artisan::call');
        $this->assertStringNotContainsString('insertOrIgnore', $serviceFile, 'Read-only service must not mutate data');
        $this->assertStringNotContainsString('->insert(', $serviceFile, 'Read-only service must not insert');
        $this->assertStringNotContainsString('->update(', $serviceFile, 'Read-only service must not update');
        $this->assertStringNotContainsString('->delete(', $serviceFile, 'Read-only service must not delete');
    }

    /**
     * 10. Multi-playlist separation:
     *     Same content in two playlists produces two distinct candidate contexts.
     */
    public function test_10_multi_playlist_separation_in_candidates(): void
    {
        $planA = TrainingPlan::create([
            'title'      => 'Multi Plan Alpha',
            'slug'       => 'multi-alpha-' . rand(1000, 9999),
            'active'     => 'Active',
            'created_at' => Carbon::parse('2026-08-01 10:00:00'),
        ]);

        $planB = TrainingPlan::create([
            'title'      => 'Multi Plan Beta',
            'slug'       => 'multi-beta-' . rand(1000, 9999),
            'active'     => 'Active',
            'created_at' => Carbon::parse('2026-08-01 10:00:00'),
        ]);

        $content = Content::create([
            'title'             => 'Multi Shared Content',
            'slug'              => 'multi-shared-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $planA->id, 'content_id' => $content->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $planB->id, 'content_id' => $content->id, 'content_order' => 0]);

        ContentUser::create([
            'user_id'          => $this->admin->id,
            'content_id'       => $content->id,
            'progress'         => 100,
            'finish'           => '1',
            'learning_status'  => 'completed',
        ]);

        $service = app(PlaylistProgressDryRunService::class);
        $candidatesA = $service->getCandidatesPaginated(PlaylistProgressDryRunService::DEFAULT_CUTOFF, $planA->id, 25, 1);
        $candidatesB = $service->getCandidatesPaginated(PlaylistProgressDryRunService::DEFAULT_CUTOFF, $planB->id, 25, 1);

        $this->assertTrue(collect($candidatesA->items())->contains('trainingplan_id', $planA->id));
        $this->assertTrue(collect($candidatesB->items())->contains('trainingplan_id', $planB->id));
    }

    /**
     * 11. Landing page displays the Dry Run Migration card linking to dry-run page.
     */
    public function test_11_landing_page_displays_dry_run_card(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.learning-hours.index'));

        $response->assertStatus(200);
        $response->assertSee('Dry Run Migration');
        $response->assertSee('Preview historical learning progress migration from Content User to Playlist Content User.');
        $response->assertSee(route('manage.learning-hours.dry-run.index'));
    }
}
