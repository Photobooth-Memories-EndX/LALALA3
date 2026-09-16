<?php

namespace Tests\Feature;

use App\Models\Assesment;
use App\Models\AssesmentResult;
use App\Models\Certificate;
use App\Models\CertificateSetting;
use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use App\Services\CertificateGenerateService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CertificateGenerateServiceResolverTest extends TestCase
{
    use DatabaseTransactions;

    protected User $userA;
    protected User $userB;
    protected Classification $classification;
    protected CertificateGenerateService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(CertificateGenerateService::class);

        $this->userA = User::factory()->create([
            'name'       => 'User Gen A',
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-hash-cgs-a-' . Str::uuid(),
            'sap_code'   => 'SAP-CGS-A-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->userB = User::factory()->create([
            'name'       => 'User Gen B',
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-hash-cgs-b-' . Str::uuid(),
            'sap_code'   => 'SAP-CGS-B-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->classification = Classification::firstOrCreate(
            ['classification_name' => 'Certificate Generate Test Classification'],
            ['slug' => 'cert-gen-test-classification']
        );
    }

    /**
     * TEST 1 — Direct Content
     */
    public function test_1_direct_content_certificate_generation(): void
    {
        $content = Content::create([
            'title'             => 'Direct Gen Content',
            'slug'              => 'dir-gen-c-' . rand(1000, 9999),
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

        $cu = ContentUser::create([
            'user_id'    => $this->userA->id,
            'content_id' => $content->id,
            'progress'   => 100,
            'finish'     => '1',
        ]);

        $certificate = $this->service->generate($setting, $this->userA);

        $this->assertInstanceOf(Certificate::class, $certificate);
        $this->assertEquals($this->userA->id, $certificate->user_id);
        $this->assertEquals($setting->id, $certificate->certificate_setting_id);
        $this->assertEquals('content', $certificate->certificate_type);
        $this->assertEquals($cu->id, $certificate->content_user_id);
        $this->assertNotEmpty($certificate->certificate_number);
        $this->assertNotEmpty($certificate->credential_id);
    }

    /**
     * TEST 2 — Playlist Eligible
     */
    public function test_2_playlist_eligible_certificate_generation(): void
    {
        $plan = TrainingPlan::create(['title' => 'Pl Gen Plan', 'slug' => 'pl-gen-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create(['title' => 'Pl Gen Content', 'slug' => 'pl-gen-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        $setting = CertificateSetting::create([
            'certificate_type' => 'playlist',
            'trainingplan_id'  => $plan->id,
            'minimum_progress' => 100,
            'minimum_score'    => 80,
            'is_active'        => true,
            'issuer_name'      => 'OWL Test',
        ]);

        PlaylistContentUser::create([
            'user_id'         => $this->userA->id,
            'trainingplan_id' => $plan->id,
            'content_id'      => $content->id,
            'progress'        => 100,
            'finish'          => '1',
        ]);

        $certificate = $this->service->generate($setting, $this->userA);

        $this->assertInstanceOf(Certificate::class, $certificate);
        $this->assertEquals($this->userA->id, $certificate->user_id);
        $this->assertEquals($setting->id, $certificate->certificate_setting_id);
        $this->assertEquals('playlist', $certificate->certificate_type);
        $this->assertEquals($plan->id, $certificate->source_id);
    }

    /**
     * TEST 3 — Playlist Ineligible
     */
    public function test_3_playlist_ineligible_certificate_generation_blocked(): void
    {
        $plan = TrainingPlan::create(['title' => 'Pl Inact Plan', 'slug' => 'pl-inact-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create(['title' => 'Pl Inact Content', 'slug' => 'pl-inact-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        $setting = CertificateSetting::create([
            'certificate_type' => 'playlist',
            'trainingplan_id'  => $plan->id,
            'minimum_progress' => 100,
            'minimum_score'    => 80,
            'is_active'        => true,
            'issuer_name'      => 'OWL Test',
        ]);

        // Progress 50%
        PlaylistContentUser::create([
            'user_id'         => $this->userA->id,
            'trainingplan_id' => $plan->id,
            'content_id'      => $content->id,
            'progress'        => 50,
            'finish'          => '0',
        ]);

        $this->expectException(HttpException::class);
        $this->service->generate($setting, $this->userA);
    }

    /**
     * TEST 4 — PCU Overrides Historical Content (CU = 100%, PCU = 50% -> Blocked)
     */
    public function test_4_pcu_overrides_historical_content_blocks_generation(): void
    {
        $plan = TrainingPlan::create(['title' => 'PCU Block Plan', 'slug' => 'pcu-blk-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create(['title' => 'PCU Block Content', 'slug' => 'pcu-blk-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        $setting = CertificateSetting::create(['certificate_type' => 'playlist', 'trainingplan_id' => $plan->id, 'minimum_progress' => 100, 'minimum_score' => 80, 'is_active' => true, 'issuer_name' => 'OWL Test']);

        ContentUser::create(['user_id' => $this->userA->id, 'content_id' => $content->id, 'progress' => 100, 'finish' => '1']);
        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $plan->id, 'content_id' => $content->id, 'progress' => 50, 'finish' => '0']);

        $this->expectException(HttpException::class);
        $this->service->generate($setting, $this->userA);
    }

    /**
     * TEST 5 — Completed PCU
     */
    public function test_5_completed_pcu_permits_generation(): void
    {
        $plan = TrainingPlan::create(['title' => 'PCU Win Plan', 'slug' => 'pcu-win-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create(['title' => 'PCU Win Content', 'slug' => 'pcu-win-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        $setting = CertificateSetting::create(['certificate_type' => 'playlist', 'trainingplan_id' => $plan->id, 'minimum_progress' => 100, 'minimum_score' => 80, 'is_active' => true, 'issuer_name' => 'OWL Test']);

        ContentUser::create(['user_id' => $this->userA->id, 'content_id' => $content->id, 'progress' => 20, 'finish' => '0']);
        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $plan->id, 'content_id' => $content->id, 'progress' => 100, 'finish' => '1']);

        $certificate = $this->service->generate($setting, $this->userA);
        $this->assertInstanceOf(Certificate::class, $certificate);
    }

    /**
     * TEST 6 — Historical Category A
     */
    public function test_6_historical_category_a_permits_generation(): void
    {
        $plan = TrainingPlan::create(['title' => 'Cat A Gen Plan', 'slug' => 'cat-a-gen-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create(['title' => 'Cat A Gen Content', 'slug' => 'cat-a-gen-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        $setting = CertificateSetting::create(['certificate_type' => 'playlist', 'trainingplan_id' => $plan->id, 'minimum_progress' => 100, 'minimum_score' => 80, 'is_active' => true, 'issuer_name' => 'OWL Test']);

        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $plan->id, 'content_id' => $content->id, 'progress' => 100, 'finish' => '1']);

        $certificate = $this->service->generate($setting, $this->userA);
        $this->assertInstanceOf(Certificate::class, $certificate);
    }

    /**
     * TEST 7 — Multi-Playlist Ambiguity
     */
    public function test_7_multi_playlist_ambiguity_blocks_generation(): void
    {
        $planA = TrainingPlan::create(['title' => 'Amb Gen Plan A', 'slug' => 'amb-gen-a-' . rand(1000, 9999), 'active' => 'Active']);
        $planB = TrainingPlan::create(['title' => 'Amb Gen Plan B', 'slug' => 'amb-gen-b-' . rand(1000, 9999), 'active' => 'Active']);

        $content = Content::create(['title' => 'Amb Gen Content', 'slug' => 'amb-gen-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $planA->id, 'content_id' => $content->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $planB->id, 'content_id' => $content->id, 'content_order' => 0]);

        $settingA = CertificateSetting::create(['certificate_type' => 'playlist', 'trainingplan_id' => $planA->id, 'minimum_progress' => 100, 'minimum_score' => 80, 'is_active' => true, 'issuer_name' => 'OWL Test']);

        // Historical 100% in content_users, but 2 active playlists -> Ambiguous (0%)
        ContentUser::create(['user_id' => $this->userA->id, 'content_id' => $content->id, 'progress' => 100, 'finish' => '1']);

        $this->expectException(HttpException::class);
        $this->service->generate($settingA, $this->userA);
    }

    /**
     * TEST 8 — Playlist Isolation
     */
    public function test_8_playlist_isolation(): void
    {
        $planA = TrainingPlan::create(['title' => 'Iso Gen Plan A', 'slug' => 'iso-gen-a-' . rand(1000, 9999), 'active' => 'Active']);
        $planB = TrainingPlan::create(['title' => 'Iso Gen Plan B', 'slug' => 'iso-gen-b-' . rand(1000, 9999), 'active' => 'Active']);

        $content = Content::create(['title' => 'Iso Gen Content', 'slug' => 'iso-gen-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $planA->id, 'content_id' => $content->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $planB->id, 'content_id' => $content->id, 'content_order' => 0]);

        $settingA = CertificateSetting::create(['certificate_type' => 'playlist', 'trainingplan_id' => $planA->id, 'minimum_progress' => 100, 'minimum_score' => 80, 'is_active' => true, 'issuer_name' => 'OWL Test']);
        $settingB = CertificateSetting::create(['certificate_type' => 'playlist', 'trainingplan_id' => $planB->id, 'minimum_progress' => 100, 'minimum_score' => 80, 'is_active' => true, 'issuer_name' => 'OWL Test']);

        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $planA->id, 'content_id' => $content->id, 'progress' => 100, 'finish' => '1']);
        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $planB->id, 'content_id' => $content->id, 'progress' => 50, 'finish' => '0']);

        $certA = $this->service->generate($settingA, $this->userA);
        $this->assertInstanceOf(Certificate::class, $certA);

        $this->expectException(HttpException::class);
        $this->service->generate($settingB, $this->userA);
    }

    /**
     * TEST 9 — Assessment Requirement
     */
    public function test_9_assessment_requirement_enforced_blocks_generation(): void
    {
        $plan = TrainingPlan::create(['title' => 'Ass Gen Plan', 'slug' => 'ass-gen-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create(['title' => 'Ass Gen C', 'slug' => 'ass-gen-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        Assesment::create([
            'title'           => 'Plan Assessment',
            'slug'            => 'plan-gen-ass-' . rand(1000, 9999),
            'type'            => 'trainingplan',
            'trainingplan_id' => $plan->id,
            'status'          => 'OK',
            'passing_grade'   => 80,
            'batch'           => 1,
        ]);

        $setting = CertificateSetting::create(['certificate_type' => 'playlist', 'trainingplan_id' => $plan->id, 'minimum_progress' => 100, 'minimum_score' => 80, 'is_active' => true, 'issuer_name' => 'OWL Test']);

        // Progress 100%, but no assessment score
        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $plan->id, 'content_id' => $content->id, 'progress' => 100, 'finish' => '1']);

        $this->expectException(HttpException::class);
        $this->service->generate($setting, $this->userA);
    }

    /**
     * TEST 10 — User Isolation
     */
    public function test_10_user_isolation(): void
    {
        $plan = TrainingPlan::create(['title' => 'User Iso Gen Plan', 'slug' => 'u-iso-gen-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create(['title' => 'User Iso Gen Content', 'slug' => 'u-iso-gen-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        $setting = CertificateSetting::create(['certificate_type' => 'playlist', 'trainingplan_id' => $plan->id, 'minimum_progress' => 100, 'minimum_score' => 80, 'is_active' => true, 'issuer_name' => 'OWL Test']);

        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $plan->id, 'content_id' => $content->id, 'progress' => 100, 'finish' => '1']);

        $certA = $this->service->generate($setting, $this->userA);
        $this->assertInstanceOf(Certificate::class, $certA);

        $this->expectException(HttpException::class);
        $this->service->generate($setting, $this->userB);
    }

    /**
     * TEST 11 — No Progress Mutation
     */
    public function test_11_zero_progress_mutations_during_generation(): void
    {
        $plan = TrainingPlan::create(['title' => 'No Mut Plan', 'slug' => 'no-mut-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create(['title' => 'No Mut Content', 'slug' => 'no-mut-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        $setting = CertificateSetting::create(['certificate_type' => 'playlist', 'trainingplan_id' => $plan->id, 'minimum_progress' => 100, 'minimum_score' => 80, 'is_active' => true, 'issuer_name' => 'OWL Test']);

        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $plan->id, 'content_id' => $content->id, 'progress' => 100, 'finish' => '1']);

        $initialPcuCount = PlaylistContentUser::count();
        $initialCuCount = ContentUser::count();

        $this->service->generate($setting, $this->userA);

        $this->assertEquals($initialPcuCount, PlaylistContentUser::count());
        $this->assertEquals($initialCuCount, ContentUser::count());
    }
}
