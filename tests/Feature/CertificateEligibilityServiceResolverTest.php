<?php

namespace Tests\Feature;

use App\Models\Assesment;
use App\Models\AssesmentQuestion;
use App\Models\AssesmentResult;
use App\Models\CertificateSetting;
use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use App\Services\CertificateEligibilityService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class CertificateEligibilityServiceResolverTest extends TestCase
{
    use DatabaseTransactions;

    protected User $userA;
    protected User $userB;
    protected Classification $classification;
    protected CertificateEligibilityService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(CertificateEligibilityService::class);

        $this->userA = User::factory()->create([
            'name'       => 'Tester A',
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-hash-ces-a-' . Str::uuid(),
            'sap_code'   => 'SAP-CES-A-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->userB = User::factory()->create([
            'name'       => 'Tester B',
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-hash-ces-b-' . Str::uuid(),
            'sap_code'   => 'SAP-CES-B-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->classification = Classification::firstOrCreate(
            ['classification_name' => 'Certificate Eligibility Test Classification'],
            ['slug' => 'cert-eligibility-test-classification']
        );
    }

    /**
     * TEST 1 — Direct Content
     */
    public function test_1_direct_content_certificate_eligibility(): void
    {
        $content = Content::create([
            'title'             => 'Direct Content Cert 1',
            'slug'              => 'dir-cert-c-1-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $setting = CertificateSetting::create([
            'certificate_type' => 'content',
            'content_id'       => $content->id,
            'minimum_progress' => 100,
            'minimum_score'    => 80,
            'is_active'        => true,
            'issuer_name'      => 'OWL Test',
        ]);

        // Before progress -> Ineligible
        $resultBefore = $this->service->check($setting, $this->userA);
        $this->assertFalse($resultBefore->eligible);

        // After 100% in content_users -> Eligible
        ContentUser::create([
            'user_id'    => $this->userA->id,
            'content_id' => $content->id,
            'progress'   => 100,
            'finish'     => '1',
        ]);

        $resultAfter = $this->service->check($setting, $this->userA);
        $this->assertTrue($resultAfter->eligible);
    }

    /**
     * TEST 2 — Playlist Historical Category A
     */
    public function test_2_playlist_historical_category_a(): void
    {
        $plan = TrainingPlan::create(['title' => 'Cat A Cert Plan', 'slug' => 'cat-a-cert-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create([
            'title'             => 'Cat A Cert Content',
            'slug'              => 'cat-a-cert-c-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        $setting = CertificateSetting::create([
            'certificate_type' => 'playlist',
            'trainingplan_id'  => $plan->id,
            'minimum_progress' => 100,
            'minimum_score'    => 80,
            'is_active'        => true,
            'issuer_name'      => 'OWL Test',
        ]);

        // Historical 100% in content_users, single active playlist -> Eligible via Category A
        ContentUser::create([
            'user_id'    => $this->userA->id,
            'content_id' => $content->id,
            'progress'   => 100,
            'finish'     => '1',
        ]);

        $result = $this->service->check($setting, $this->userA);
        $this->assertTrue($result->eligible);
    }

    /**
     * TEST 3 — PCU Overrides Historical Content (CU = 100%, PCU = 50% -> Ineligible)
     */
    public function test_3_pcu_overrides_historical_content(): void
    {
        $plan = TrainingPlan::create(['title' => 'Override Cert Plan', 'slug' => 'over-cert-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create([
            'title'             => 'Override Cert Content',
            'slug'              => 'over-cert-c-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        $setting = CertificateSetting::create([
            'certificate_type' => 'playlist',
            'trainingplan_id'  => $plan->id,
            'minimum_progress' => 100,
            'minimum_score'    => 80,
            'is_active'        => true,
            'issuer_name'      => 'OWL Test',
        ]);

        // Historical 100%
        ContentUser::create(['user_id' => $this->userA->id, 'content_id' => $content->id, 'progress' => 100, 'finish' => '1']);

        // Authoritative 50% in PCU
        PlaylistContentUser::create([
            'user_id'         => $this->userA->id,
            'trainingplan_id' => $plan->id,
            'content_id'      => $content->id,
            'progress'        => 50,
            'finish'          => '0',
        ]);

        $result = $this->service->check($setting, $this->userA);
        $this->assertFalse($result->eligible);
    }

    /**
     * TEST 4 — Completed PCU Overrides Historical Incomplete (CU = 20%, PCU = 100% -> Eligible)
     */
    public function test_4_completed_pcu_overrides_historical_incomplete(): void
    {
        $plan = TrainingPlan::create(['title' => 'Win Cert Plan', 'slug' => 'win-cert-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create([
            'title'             => 'Win Cert Content',
            'slug'              => 'win-cert-c-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        $setting = CertificateSetting::create([
            'certificate_type' => 'playlist',
            'trainingplan_id'  => $plan->id,
            'minimum_progress' => 100,
            'minimum_score'    => 80,
            'is_active'        => true,
            'issuer_name'      => 'OWL Test',
        ]);

        // Historical 20%
        ContentUser::create(['user_id' => $this->userA->id, 'content_id' => $content->id, 'progress' => 20, 'finish' => '0']);

        // Authoritative 100% in PCU
        PlaylistContentUser::create([
            'user_id'         => $this->userA->id,
            'trainingplan_id' => $plan->id,
            'content_id'      => $content->id,
            'progress'        => 100,
            'finish'          => '1',
        ]);

        $result = $this->service->check($setting, $this->userA);
        $this->assertTrue($result->eligible);
    }

    /**
     * TEST 5 — Multi-Playlist Ambiguity
     */
    public function test_5_multi_playlist_ambiguity(): void
    {
        $planA = TrainingPlan::create(['title' => 'Amb Cert Plan A', 'slug' => 'amb-cert-a-' . rand(1000, 9999), 'active' => 'Active']);
        $planB = TrainingPlan::create(['title' => 'Amb Cert Plan B', 'slug' => 'amb-cert-b-' . rand(1000, 9999), 'active' => 'Active']);

        $content = Content::create([
            'title'             => 'Amb Cert Content',
            'slug'              => 'amb-cert-c-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);
        TrainingPlanDetail::create(['trainingplan_id' => $planA->id, 'content_id' => $content->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $planB->id, 'content_id' => $content->id, 'content_order' => 0]);

        $settingA = CertificateSetting::create(['certificate_type' => 'playlist', 'trainingplan_id' => $planA->id, 'minimum_progress' => 100, 'minimum_score' => 80, 'is_active' => true, 'issuer_name' => 'OWL Test']);
        $settingB = CertificateSetting::create(['certificate_type' => 'playlist', 'trainingplan_id' => $planB->id, 'minimum_progress' => 100, 'minimum_score' => 80, 'is_active' => true, 'issuer_name' => 'OWL Test']);

        // Historical 100% in content_users without PCU
        ContentUser::create(['user_id' => $this->userA->id, 'content_id' => $content->id, 'progress' => 100, 'finish' => '1']);

        // Both certificates must be ineligible due to ambiguous attribution (0%)
        $this->assertFalse($this->service->check($settingA, $this->userA)->eligible);
        $this->assertFalse($this->service->check($settingB, $this->userA)->eligible);
    }

    /**
     * TEST 6 — Playlist Isolation
     */
    public function test_6_playlist_isolation(): void
    {
        $planA = TrainingPlan::create(['title' => 'Iso Cert Plan A', 'slug' => 'iso-cert-a-' . rand(1000, 9999), 'active' => 'Active']);
        $planB = TrainingPlan::create(['title' => 'Iso Cert Plan B', 'slug' => 'iso-cert-b-' . rand(1000, 9999), 'active' => 'Active']);

        $content = Content::create([
            'title'             => 'Iso Cert Content',
            'slug'              => 'iso-cert-c-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);
        TrainingPlanDetail::create(['trainingplan_id' => $planA->id, 'content_id' => $content->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $planB->id, 'content_id' => $content->id, 'content_order' => 0]);

        $settingA = CertificateSetting::create(['certificate_type' => 'playlist', 'trainingplan_id' => $planA->id, 'minimum_progress' => 100, 'minimum_score' => 80, 'is_active' => true, 'issuer_name' => 'OWL Test']);
        $settingB = CertificateSetting::create(['certificate_type' => 'playlist', 'trainingplan_id' => $planB->id, 'minimum_progress' => 100, 'minimum_score' => 80, 'is_active' => true, 'issuer_name' => 'OWL Test']);

        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $planA->id, 'content_id' => $content->id, 'progress' => 100, 'finish' => '1']);
        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $planB->id, 'content_id' => $content->id, 'progress' => 50, 'finish' => '0']);

        $this->assertTrue($this->service->check($settingA, $this->userA)->eligible);
        $this->assertFalse($this->service->check($settingB, $this->userA)->eligible);
    }

    /**
     * TEST 7 — User Isolation
     */
    public function test_7_user_isolation(): void
    {
        $plan = TrainingPlan::create(['title' => 'User Iso Cert Plan', 'slug' => 'u-iso-cert-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create([
            'title'             => 'User Iso Cert Content',
            'slug'              => 'u-iso-cert-c-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        $setting = CertificateSetting::create(['certificate_type' => 'playlist', 'trainingplan_id' => $plan->id, 'minimum_progress' => 100, 'minimum_score' => 80, 'is_active' => true, 'issuer_name' => 'OWL Test']);

        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $plan->id, 'content_id' => $content->id, 'progress' => 100, 'finish' => '1']);

        $this->assertTrue($this->service->check($setting, $this->userA)->eligible);
        $this->assertFalse($this->service->check($setting, $this->userB)->eligible);
    }

    /**
     * TEST 8 — Partial Playlist (at least 1 required content incomplete -> ineligible)
     */
    public function test_8_partial_playlist(): void
    {
        $plan = TrainingPlan::create(['title' => 'Partial Cert Plan', 'slug' => 'part-cert-' . rand(1000, 9999), 'active' => 'Active']);
        $c1 = Content::create(['title' => 'Part C1', 'slug' => 'part-c1-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        $c2 = Content::create(['title' => 'Part C2', 'slug' => 'part-c2-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);

        $setting = CertificateSetting::create(['certificate_type' => 'playlist', 'trainingplan_id' => $plan->id, 'minimum_progress' => 100, 'minimum_score' => 80, 'is_active' => true, 'issuer_name' => 'OWL Test']);

        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'progress' => 100, 'finish' => '1']);
        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'progress' => 50, 'finish' => '0']);

        $this->assertFalse($this->service->check($setting, $this->userA)->eligible);
    }

    /**
     * TEST 9 — Fully Completed Playlist
     */
    public function test_9_fully_completed_playlist(): void
    {
        $plan = TrainingPlan::create(['title' => 'Full Cert Plan', 'slug' => 'full-cert-' . rand(1000, 9999), 'active' => 'Active']);
        $c1 = Content::create(['title' => 'Full C1', 'slug' => 'full-c1-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        $c2 = Content::create(['title' => 'Full C2', 'slug' => 'full-c2-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);

        $setting = CertificateSetting::create(['certificate_type' => 'playlist', 'trainingplan_id' => $plan->id, 'minimum_progress' => 100, 'minimum_score' => 80, 'is_active' => true, 'issuer_name' => 'OWL Test']);

        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'progress' => 100, 'finish' => '1']);
        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'progress' => 100, 'finish' => '1']);

        $this->assertTrue($this->service->check($setting, $this->userA)->eligible);
    }

    /**
     * TEST 10 — Zero Progress
     */
    public function test_10_zero_progress_remains_ineligible(): void
    {
        $plan = TrainingPlan::create(['title' => 'Zero Cert Plan', 'slug' => 'zero-cert-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create(['title' => 'Zero Cert C', 'slug' => 'zero-cert-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        $setting = CertificateSetting::create(['certificate_type' => 'playlist', 'trainingplan_id' => $plan->id, 'minimum_progress' => 100, 'minimum_score' => 80, 'is_active' => true, 'issuer_name' => 'OWL Test']);

        $this->assertFalse($this->service->check($setting, $this->userA)->eligible);
    }

    /**
     * TEST 11 — Assessment Requirement (Progress complete, but assessment required)
     */
    public function test_11_assessment_requirement_enforced(): void
    {
        $plan = TrainingPlan::create(['title' => 'Ass Req Plan', 'slug' => 'ass-req-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create(['title' => 'Ass Req C', 'slug' => 'ass-req-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        $assessment = Assesment::create([
            'title'           => 'Plan Assessment',
            'slug'            => 'plan-ass-' . rand(1000, 9999),
            'type'            => 'trainingplan',
            'trainingplan_id' => $plan->id,
            'status'          => 'OK',
            'passing_grade'   => 80,
            'batch'           => 1,
        ]);

        $setting = CertificateSetting::create(['certificate_type' => 'playlist', 'trainingplan_id' => $plan->id, 'minimum_progress' => 100, 'minimum_score' => 80, 'is_active' => true, 'issuer_name' => 'OWL Test']);

        // Progress 100%
        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $plan->id, 'content_id' => $content->id, 'progress' => 100, 'finish' => '1']);

        // Without assessment result -> Ineligible
        $this->assertFalse($this->service->check($setting, $this->userA)->eligible);

        // With passing score (90 >= 80) -> Eligible
        AssesmentResult::create([
            'user_id'      => $this->userA->id,
            'assesment_id' => $assessment->id,
            'batch'        => 1,
            'score'        => 90,
            'pass'         => 1,
            'final'        => 1,
            'user_answer'  => json_encode(['q1' => 'a']),
        ]);

        $this->assertTrue($this->service->check($setting, $this->userA)->eligible);
    }

    /**
     * TEST 12 — Inactive Setting
     */
    public function test_12_inactive_setting_is_ineligible(): void
    {
        $plan = TrainingPlan::create(['title' => 'Inact Cert Plan', 'slug' => 'inact-cert-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create(['title' => 'Inact Cert C', 'slug' => 'inact-cert-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        $setting = CertificateSetting::create(['certificate_type' => 'playlist', 'trainingplan_id' => $plan->id, 'minimum_progress' => 100, 'minimum_score' => 80, 'is_active' => false, 'issuer_name' => 'OWL Test']);

        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $plan->id, 'content_id' => $content->id, 'progress' => 100, 'finish' => '1']);

        $result = $this->service->check($setting, $this->userA);
        $this->assertFalse($result->eligible);
        $this->assertSame('Certificate setting is inactive.', $result->reason);
    }

    /**
     * TEST 13 — Zero Database Mutations
     */
    public function test_13_zero_database_mutations(): void
    {
        $plan = TrainingPlan::create(['title' => 'RO Cert Plan', 'slug' => 'ro-cert-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create(['title' => 'RO Cert C', 'slug' => 'ro-cert-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        $setting = CertificateSetting::create(['certificate_type' => 'playlist', 'trainingplan_id' => $plan->id, 'minimum_progress' => 100, 'minimum_score' => 80, 'is_active' => true, 'issuer_name' => 'OWL Test']);

        $initialPcuCount = PlaylistContentUser::count();
        $initialCuCount = ContentUser::count();

        $this->service->check($setting, $this->userA);
        $this->service->checkMany(collect([$setting]), $this->userA);

        $this->assertEquals($initialPcuCount, PlaylistContentUser::count());
        $this->assertEquals($initialCuCount, ContentUser::count());
    }
}
