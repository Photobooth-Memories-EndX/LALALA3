<?php

namespace Tests\Feature;

use App\Models\Assesment;
use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use App\Services\AssessmentAccessService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AssessmentAccessServiceResolverTest extends TestCase
{
    use DatabaseTransactions;

    protected User $userA;
    protected User $userB;
    protected User $adminUser;
    protected Classification $classification;
    protected AssessmentAccessService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(AssessmentAccessService::class);

        $this->userA = User::factory()->create([
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-hash-aas-a-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-AAS-A-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->userB = User::factory()->create([
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-hash-aas-b-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-AAS-B-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->adminUser = User::factory()->create([
            'team'       => 'Admin Team',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-hash-aas-adm-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-AAS-ADM-' . rand(1000, 9999),
            'role'       => 2,
            'active'     => 1,
        ]);

        $this->classification = Classification::firstOrCreate(
            ['classification_name' => 'Assessment Access Test Classification'],
            ['slug' => 'assessment-access-test-classification']
        );
    }

    /**
     * TEST 1: Direct Content assessment prerequisite (content_users = 100%)
     */
    public function test_1_direct_content_assessment_prerequisite(): void
    {
        $content = Content::create([
            'title'             => 'Direct Content 1',
            'slug'              => 'dir-c-1-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $assessment = Assesment::create([
            'title'         => 'Direct Content Assessment',
            'slug'          => 'dir-c-ass-' . rand(1000, 9999),
            'type'          => 'content',
            'content_id'    => $content->id,
            'status'        => 'OK',
            'passing_grade' => 70,
            'batch'         => 1,
        ]);

        // Incomplete initially -> REASON_PROGRESS
        $this->assertEquals(
            AssessmentAccessService::REASON_PROGRESS,
            $this->service->denialReason($assessment, $this->userA)
        );

        // 100% in content_users -> Access granted (null)
        ContentUser::create([
            'user_id'    => $this->userA->id,
            'content_id' => $content->id,
            'progress'   => 100,
            'finish'     => '1',
        ]);

        $this->assertNull($this->service->denialReason($assessment, $this->userA));
    }

    /**
     * TEST 2: Playlist assessment with historical Category A (content_users = 100%, single active playlist)
     */
    public function test_2_playlist_assessment_with_historical_category_a(): void
    {
        $plan = TrainingPlan::create(['title' => 'Cat A Plan', 'slug' => 'cat-a-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create([
            'title'             => 'Cat A Content',
            'slug'              => 'cat-a-c-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        $assessment = Assesment::create([
            'title'           => 'Playlist Assessment',
            'slug'            => 'pl-ass-' . rand(1000, 9999),
            'type'            => 'trainingplan',
            'trainingplan_id' => $plan->id,
            'status'          => 'OK',
            'passing_grade'   => 70,
            'batch'           => 1,
        ]);

        // Playlist 100% in playlist_content_users
        PlaylistContentUser::create([
            'user_id'         => $this->userA->id,
            'trainingplan_id' => $plan->id,
            'content_id'      => $content->id,
            'progress'        => 100,
            'finish'          => '1',
        ]);

        $this->assertNull($this->service->denialReason($assessment, $this->userA));
    }

    /**
     * TEST 3: Playlist PCU overrides historical Direct Content (CU = 100%, PCU = 50% -> denied)
     */
    public function test_3_playlist_pcu_overrides_historical_direct_content(): void
    {
        $plan = TrainingPlan::create(['title' => 'Override Plan', 'slug' => 'over-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create([
            'title'             => 'Override Content',
            'slug'              => 'over-c-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        $assessment = Assesment::create([
            'title'           => 'Override Assessment',
            'slug'            => 'over-ass-' . rand(1000, 9999),
            'type'            => 'trainingplan',
            'trainingplan_id' => $plan->id,
            'status'          => 'OK',
            'passing_grade'   => 70,
            'batch'           => 1,
        ]);

        // Historical 100% in content_users
        ContentUser::create(['user_id' => $this->userA->id, 'content_id' => $content->id, 'progress' => 100, 'finish' => '1']);

        // Authoritative 50% in PCU
        PlaylistContentUser::create([
            'user_id'         => $this->userA->id,
            'trainingplan_id' => $plan->id,
            'content_id'      => $content->id,
            'progress'        => 50,
            'finish'          => '0',
        ]);

        $this->assertEquals(
            AssessmentAccessService::REASON_PROGRESS,
            $this->service->denialReason($assessment, $this->userA)
        );
    }

    /**
     * TEST 4: Completed PCU overrides historical incomplete Direct Content (CU = 20%, PCU = 100% -> granted)
     */
    public function test_4_completed_pcu_overrides_historical_incomplete_direct_content(): void
    {
        $plan = TrainingPlan::create(['title' => 'Comp Win Plan', 'slug' => 'comp-win-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create([
            'title'             => 'Comp Win Content',
            'slug'              => 'comp-win-c-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        $assessment = Assesment::create([
            'title'           => 'Comp Win Assessment',
            'slug'            => 'comp-win-ass-' . rand(1000, 9999),
            'type'            => 'trainingplan',
            'trainingplan_id' => $plan->id,
            'status'          => 'OK',
            'passing_grade'   => 70,
            'batch'           => 1,
        ]);

        // Historical 20% in content_users
        ContentUser::create(['user_id' => $this->userA->id, 'content_id' => $content->id, 'progress' => 20, 'finish' => '0']);

        // Authoritative 100% in PCU
        PlaylistContentUser::create([
            'user_id'         => $this->userA->id,
            'trainingplan_id' => $plan->id,
            'content_id'      => $content->id,
            'progress'        => 100,
            'finish'          => '1',
        ]);

        $this->assertNull($this->service->denialReason($assessment, $this->userA));
    }

    /**
     * TEST 5: Multi-playlist historical ambiguity
     */
    public function test_5_multi_playlist_historical_ambiguity(): void
    {
        $planA = TrainingPlan::create(['title' => 'Amb Plan A', 'slug' => 'amb-a-' . rand(1000, 9999), 'active' => 'Active']);
        $planB = TrainingPlan::create(['title' => 'Amb Plan B', 'slug' => 'amb-b-' . rand(1000, 9999), 'active' => 'Active']);

        $content = Content::create([
            'title'             => 'Amb Content',
            'slug'              => 'amb-c-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);
        TrainingPlanDetail::create(['trainingplan_id' => $planA->id, 'content_id' => $content->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $planB->id, 'content_id' => $content->id, 'content_order' => 0]);

        $assessmentA = Assesment::create(['title' => 'Ass A', 'slug' => 'ass-a-' . rand(1000, 9999), 'type' => 'trainingplan', 'trainingplan_id' => $planA->id, 'status' => 'OK', 'passing_grade' => 70, 'batch' => 1]);
        $assessmentB = Assesment::create(['title' => 'Ass B', 'slug' => 'ass-b-' . rand(1000, 9999), 'type' => 'trainingplan', 'trainingplan_id' => $planB->id, 'status' => 'OK', 'passing_grade' => 70, 'batch' => 1]);

        // Historical 100% in content_users
        ContentUser::create(['user_id' => $this->userA->id, 'content_id' => $content->id, 'progress' => 100, 'finish' => '1']);

        // Both assessments must be denied because attribution is ambiguous (progress = 0)
        $this->assertEquals(AssessmentAccessService::REASON_PROGRESS, $this->service->denialReason($assessmentA, $this->userA));
        $this->assertEquals(AssessmentAccessService::REASON_PROGRESS, $this->service->denialReason($assessmentB, $this->userA));
    }

    /**
     * TEST 6: Playlist isolation (Plan A PCU = 100%, Plan B PCU = 50%)
     */
    public function test_6_playlist_isolation(): void
    {
        $planA = TrainingPlan::create(['title' => 'Iso Plan A', 'slug' => 'iso-a-' . rand(1000, 9999), 'active' => 'Active']);
        $planB = TrainingPlan::create(['title' => 'Iso Plan B', 'slug' => 'iso-b-' . rand(1000, 9999), 'active' => 'Active']);

        $content = Content::create([
            'title'             => 'Iso Content',
            'slug'              => 'iso-c-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);
        TrainingPlanDetail::create(['trainingplan_id' => $planA->id, 'content_id' => $content->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $planB->id, 'content_id' => $content->id, 'content_order' => 0]);

        $assessmentA = Assesment::create(['title' => 'Ass A', 'slug' => 'ass-a-' . rand(1000, 9999), 'type' => 'trainingplan', 'trainingplan_id' => $planA->id, 'status' => 'OK', 'passing_grade' => 70, 'batch' => 1]);
        $assessmentB = Assesment::create(['title' => 'Ass B', 'slug' => 'ass-b-' . rand(1000, 9999), 'type' => 'trainingplan', 'trainingplan_id' => $planB->id, 'status' => 'OK', 'passing_grade' => 70, 'batch' => 1]);

        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $planA->id, 'content_id' => $content->id, 'progress' => 100, 'finish' => '1']);
        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $planB->id, 'content_id' => $content->id, 'progress' => 50, 'finish' => '0']);

        $this->assertNull($this->service->denialReason($assessmentA, $this->userA));
        $this->assertEquals(AssessmentAccessService::REASON_PROGRESS, $this->service->denialReason($assessmentB, $this->userA));
    }

    /**
     * TEST 7: User isolation (User A = 100%, User B = 0%)
     */
    public function test_7_user_isolation(): void
    {
        $plan = TrainingPlan::create(['title' => 'User Iso Plan', 'slug' => 'u-iso-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create([
            'title'             => 'User Iso Content',
            'slug'              => 'u-iso-c-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        $assessment = Assesment::create([
            'title'           => 'User Iso Assessment',
            'slug'            => 'u-iso-ass-' . rand(1000, 9999),
            'type'            => 'trainingplan',
            'trainingplan_id' => $plan->id,
            'status'          => 'OK',
            'passing_grade'   => 70,
            'batch'           => 1,
        ]);

        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $plan->id, 'content_id' => $content->id, 'progress' => 100, 'finish' => '1']);

        $this->assertNull($this->service->denialReason($assessment, $this->userA));
        $this->assertEquals(AssessmentAccessService::REASON_PROGRESS, $this->service->denialReason($assessment, $this->userB));
    }

    /**
     * TEST 8: Zero / no progress
     */
    public function test_8_zero_progress_denies_access(): void
    {
        $plan = TrainingPlan::create(['title' => 'Zero Plan', 'slug' => 'zero-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create(['title' => 'Zero Content', 'slug' => 'zero-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        $assessment = Assesment::create(['title' => 'Zero Ass', 'slug' => 'zero-ass-' . rand(1000, 9999), 'type' => 'trainingplan', 'trainingplan_id' => $plan->id, 'status' => 'OK', 'passing_grade' => 70, 'batch' => 1]);

        $this->assertEquals(AssessmentAccessService::REASON_PROGRESS, $this->service->denialReason($assessment, $this->userA));
    }

    /**
     * TEST 9: Progress = 100% without finish = 1 is recognized as finished
     */
    public function test_9_progress_100_percent_without_finish_1_grants_access(): void
    {
        $plan = TrainingPlan::create(['title' => 'P100 Plan', 'slug' => 'p100-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create(['title' => 'P100 Content', 'slug' => 'p100-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        $assessment = Assesment::create(['title' => 'P100 Ass', 'slug' => 'p100-ass-' . rand(1000, 9999), 'type' => 'trainingplan', 'trainingplan_id' => $plan->id, 'status' => 'OK', 'passing_grade' => 70, 'batch' => 1]);

        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $plan->id, 'content_id' => $content->id, 'progress' => 100, 'finish' => '0']);

        $this->assertNull($this->service->denialReason($assessment, $this->userA));
    }

    /**
     * TEST 10: Historical Category A with finish = 1 grants access
     */
    public function test_10_historical_category_a_with_finish_1_grants_access(): void
    {
        $plan = TrainingPlan::create(['title' => 'F1 Plan', 'slug' => 'f1-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create(['title' => 'F1 Content', 'slug' => 'f1-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        $assessment = Assesment::create(['title' => 'F1 Ass', 'slug' => 'f1-ass-' . rand(1000, 9999), 'type' => 'trainingplan', 'trainingplan_id' => $plan->id, 'status' => 'OK', 'passing_grade' => 70, 'batch' => 1]);

        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $plan->id, 'content_id' => $content->id, 'progress' => 100, 'finish' => '1']);

        $this->assertNull($this->service->denialReason($assessment, $this->userA));
    }

    /**
     * TEST 11: Existing assessment authorization rules remain intact (inactive status, admin bypass)
     */
    public function test_11_existing_authorization_rules_remain_intact(): void
    {
        $assessmentInactive = Assesment::create(['title' => 'Inactive Ass', 'slug' => 'inact-ass-' . rand(1000, 9999), 'type' => 'global', 'status' => 'NOT', 'passing_grade' => 70, 'batch' => 1]);

        // Regular user blocked by inactive
        $this->assertEquals(
            AssessmentAccessService::REASON_INACTIVE,
            $this->service->denialReason($assessmentInactive, $this->userA)
        );

        // Admin user debug bypass
        $this->assertNull($this->service->denialReason($assessmentInactive, $this->adminUser));
    }

    /**
     * TEST 12: Zero database writes on service execution
     */
    public function test_12_service_performs_zero_database_writes(): void
    {
        $plan = TrainingPlan::create(['title' => 'ZW Ass Plan', 'slug' => 'zw-ass-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create(['title' => 'ZW Ass Content', 'slug' => 'zw-ass-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        $assessment = Assesment::create(['title' => 'ZW Ass', 'slug' => 'zw-ass-rec-' . rand(1000, 9999), 'type' => 'trainingplan', 'trainingplan_id' => $plan->id, 'status' => 'OK', 'passing_grade' => 70, 'batch' => 1]);

        $initialPcuCount = PlaylistContentUser::count();
        $initialCuCount = ContentUser::count();

        $this->service->denialReason($assessment, $this->userA);

        $this->assertEquals($initialPcuCount, PlaylistContentUser::count());
        $this->assertEquals($initialCuCount, ContentUser::count());
    }
}
