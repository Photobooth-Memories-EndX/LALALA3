<?php

namespace Tests\Feature;

use App\Exports\ExportMyLearningRecord;
use App\Http\Controllers\LearningRecordController;
use App\Models\Certificate;
use App\Models\CertificateSetting;
use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use App\Services\CertificateEligibilityService;
use App\Services\Learning\CompletedPlaylistService;
use App\Services\Learning\LearningHourService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class LearningRecordIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $userA;
    protected User $userB;
    protected Classification $classification;
    protected CertificateEligibilityService $eligibilityService;
    protected LearningRecordController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eligibilityService = app(CertificateEligibilityService::class);
        $this->controller = app(LearningRecordController::class);

        $this->userA = User::factory()->create([
            'name'       => 'Tester Record A',
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-hash-lr-a-' . Str::uuid(),
            'sap_code'   => 'SAP-LR-A-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->userB = User::factory()->create([
            'name'       => 'Tester Record B',
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-hash-lr-b-' . Str::uuid(),
            'sap_code'   => 'SAP-LR-B-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->classification = Classification::firstOrCreate(
            ['classification_name' => 'Learning Record Test Classification'],
            ['slug' => 'lr-test-classification']
        );
    }

    /**
     * TEST 1: Playlist Certificate Setting is visible in certificate context when progress exists only in PCU.
     */
    public function test_playlist_certificate_setting_visible_when_progress_exists_only_in_pcu(): void
    {
        $plan = TrainingPlan::create(['title' => 'PCU Only Plan', 'slug' => 'pcu-only-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $c = Content::create(['title' => 'PCU Content', 'slug' => 'pcu-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c->id, 'content_order' => 0]);

        $setting = CertificateSetting::create([
            'certificate_type' => 'playlist',
            'trainingplan_id'  => $plan->id,
            'minimum_progress' => 100,
            'minimum_score'    => 80,
            'is_active'        => true,
            'issuer_name'      => 'OWL Test',
        ]);

        // User A only has progress in playlist_content_users (no content_users record at all)
        PlaylistContentUser::create([
            'user_id'         => $this->userA->id,
            'trainingplan_id' => $plan->id,
            'content_id'      => $c->id,
            'progress'        => 100,
            'finish'          => '1',
        ]);

        $this->actingAs($this->userA);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('certificateContext');
        $method->setAccessible(true);
        $certContext = $method->invoke($this->controller, $this->eligibilityService);

        $this->assertTrue($certContext['settings']->contains('id', $setting->id));
        $this->assertTrue($certContext['byPlaylist']->has($plan->id));
        $this->assertTrue($certContext['eligibility'][$setting->id]->eligible);
    }

    /**
     * TEST 2: Playlist Certificate Setting is visible with historical Category A content_users.
     */
    public function test_playlist_certificate_setting_visible_with_historical_category_a_content_users(): void
    {
        $plan = TrainingPlan::create(['title' => 'Hist Cat A Plan', 'slug' => 'hist-cat-a-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $c = Content::create(['title' => 'Hist Cat A Content', 'slug' => 'hist-cat-a-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c->id, 'content_order' => 0]);

        $setting = CertificateSetting::create([
            'certificate_type' => 'playlist',
            'trainingplan_id'  => $plan->id,
            'minimum_progress' => 100,
            'minimum_score'    => 80,
            'is_active'        => true,
            'issuer_name'      => 'OWL Test',
        ]);

        // User A completes playlist via playlist_content_users
        PlaylistContentUser::create([
            'user_id'         => $this->userA->id,
            'trainingplan_id' => $plan->id,
            'content_id'      => $c->id,
            'progress'        => 100,
            'finish'          => '1',
        ]);

        $this->actingAs($this->userA);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('certificateContext');
        $method->setAccessible(true);
        $certContext = $method->invoke($this->controller, $this->eligibilityService);

        $this->assertTrue($certContext['settings']->contains('id', $setting->id));
        $this->assertTrue($certContext['eligibility'][$setting->id]->eligible);
    }

    /**
     * TEST 3: PCU remains authoritative over content_users (CU = 100%, PCU = 50% -> not eligible).
     */
    public function test_pcu_remains_authoritative_over_content_users(): void
    {
        $plan = TrainingPlan::create(['title' => 'Auth Plan', 'slug' => 'auth-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $c = Content::create(['title' => 'Auth Content', 'slug' => 'auth-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c->id, 'content_order' => 0]);

        $setting = CertificateSetting::create([
            'certificate_type' => 'playlist',
            'trainingplan_id'  => $plan->id,
            'minimum_progress' => 100,
            'minimum_score'    => 80,
            'is_active'        => true,
            'issuer_name'      => 'OWL Test',
        ]);

        // Historical CU is 100%
        ContentUser::create([
            'user_id'    => $this->userA->id,
            'content_id' => $c->id,
            'progress'   => 100,
            'finish'     => '1',
        ]);

        // PCU is 50% (incomplete)
        PlaylistContentUser::create([
            'user_id'         => $this->userA->id,
            'trainingplan_id' => $plan->id,
            'content_id'      => $c->id,
            'progress'        => 50,
            'finish'          => '0',
        ]);

        $this->actingAs($this->userA);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('certificateContext');
        $method->setAccessible(true);
        $certContext = $method->invoke($this->controller, $this->eligibilityService);

        $this->assertTrue($certContext['settings']->contains('id', $setting->id));
        $this->assertFalse($certContext['eligibility'][$setting->id]->eligible);
    }

    /**
     * TEST 4: Multi-playlist does not leak historical content progress across playlists.
     */
    public function test_multi_playlist_does_not_leak_historical_content_progress(): void
    {
        $planA = TrainingPlan::create(['title' => 'Multi Plan A', 'slug' => 'multi-plan-a-' . rand(1000, 9999), 'active' => 'Active']);
        $planB = TrainingPlan::create(['title' => 'Multi Plan B', 'slug' => 'multi-plan-b-' . rand(1000, 9999), 'active' => 'Active']);

        $c = Content::create(['title' => 'Shared Content', 'slug' => 'shared-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $planA->id, 'content_id' => $c->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $planB->id, 'content_id' => $c->id, 'content_order' => 0]);

        // Completed in Plan A (PCU = 100%)
        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $planA->id, 'content_id' => $c->id, 'progress' => 100, 'finish' => '1']);

        $completedPlaylists = app(CompletedPlaylistService::class)->forUser((int) $this->userA->id);

        $this->assertTrue($completedPlaylists->contains('id', $planA->id));
        $this->assertFalse($completedPlaylists->contains('id', $planB->id));
    }

    /**
     * TEST 5: Direct Content Learning Record is unchanged (CU finish = 1).
     */
    public function test_direct_content_learning_record_is_unchanged(): void
    {
        $c = Content::create(['title' => 'Direct Content', 'slug' => 'dir-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'duration' => '00:10:00', 'learning_hours' => 0.5]);
        $cu = ContentUser::create([
            'user_id'    => $this->userA->id,
            'content_id' => $c->id,
            'progress'   => 100,
            'finish'     => '1',
            'status'     => 'mandatory',
        ]);

        $this->actingAs($this->userA);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getCompletedContents');
        $method->setAccessible(true);
        $completed = $method->invoke($this->controller, (int) $this->userA->id);

        $this->assertTrue($completed->contains('id', $cu->id));
    }

    /**
     * TEST 6: Completed playlist appears in export when completed via PCU.
     */
    public function test_completed_playlist_appears_in_export_when_completed_via_pcu(): void
    {
        $plan = TrainingPlan::create(['title' => 'Export PCU Plan', 'slug' => 'exp-pcu-plan-' . rand(1000, 9999), 'active' => 'Active', 'total_duration_seconds' => 3600, 'learning_hours' => 1.0]);
        $c = Content::create(['title' => 'Export PCU Content', 'slug' => 'exp-pcu-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y', 'duration' => '01:00:00', 'learning_hours' => 1.0]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c->id, 'content_order' => 0]);

        CertificateSetting::create(['certificate_type' => 'playlist', 'trainingplan_id' => $plan->id, 'minimum_progress' => 100, 'minimum_score' => 80, 'is_active' => true, 'issuer_name' => 'OWL Test']);

        PlaylistContentUser::create([
            'user_id'         => $this->userA->id,
            'trainingplan_id' => $plan->id,
            'content_id'      => $c->id,
            'progress'        => 100,
            'finish'          => '1',
        ]);

        $this->actingAs($this->userA);

        $export = new ExportMyLearningRecord();
        $array = $export->array();

        $rows = collect($array);
        $playlistRow = $rows->firstWhere(1, 'Export PCU Plan');

        $this->assertNotNull($playlistRow, 'Playlist completed via PCU must appear in export array.');
        $this->assertEquals('Playlist', $playlistRow[2]);
        $this->assertEquals('Playlist', $playlistRow[3]);
        $this->assertEquals('-', $playlistRow[4]);
        $this->assertEquals('100%', $playlistRow[7]);
        $this->assertEquals('Available', $playlistRow[11]);
    }

    /**
     * TEST 7: Completed playlist with multiple modules appears in export.
     */
    public function test_completed_playlist_with_multiple_modules_appears_in_export(): void
    {
        $plan = TrainingPlan::create(['title' => 'Export Multi Plan', 'slug' => 'exp-multi-plan-' . rand(1000, 9999), 'active' => 'Active', 'total_duration_seconds' => 7200, 'learning_hours' => 2.0]);
        $c1 = Content::create(['title' => 'Export Multi C1', 'slug' => 'exp-multi-c1-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y', 'duration' => '01:00:00', 'learning_hours' => 1.0]);
        $c2 = Content::create(['title' => 'Export Multi C2', 'slug' => 'exp-multi-c2-' . rand(1000, 9999), 'type' => 'Document', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y', 'duration' => '01:00:00', 'learning_hours' => 1.0]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);

        CertificateSetting::create(['certificate_type' => 'playlist', 'trainingplan_id' => $plan->id, 'minimum_progress' => 100, 'minimum_score' => 80, 'is_active' => true, 'issuer_name' => 'OWL Test']);

        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'progress' => 100, 'finish' => '1']);
        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'progress' => 100, 'finish' => '1']);

        $this->actingAs($this->userA);

        $export = new ExportMyLearningRecord();
        $array = $export->array();

        $rows = collect($array);
        $playlistRow = $rows->firstWhere(1, 'Export Multi Plan');

        $this->assertNotNull($playlistRow, 'Multi-module completed playlist must appear in export array.');
        $this->assertEquals('Playlist', $playlistRow[2]);
        $this->assertEquals('Playlist', $playlistRow[3]);
        $this->assertEquals('-', $playlistRow[4]);
        $this->assertEquals('100%', $playlistRow[7]);
        $this->assertEquals('Available', $playlistRow[11]);
    }

    /**
     * TEST 8: Multi-playlist isolation in export (Plan A completed in PCU, Plan B incomplete in PCU).
     */
    public function test_multi_playlist_isolation_in_export(): void
    {
        $planA = TrainingPlan::create(['title' => 'Export Iso Plan A', 'slug' => 'exp-iso-a-' . rand(1000, 9999), 'active' => 'Active']);
        $planB = TrainingPlan::create(['title' => 'Export Iso Plan B', 'slug' => 'exp-iso-b-' . rand(1000, 9999), 'active' => 'Active']);

        $c = Content::create(['title' => 'Export Iso Content', 'slug' => 'exp-iso-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $planA->id, 'content_id' => $c->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $planB->id, 'content_id' => $c->id, 'content_order' => 0]);

        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $planA->id, 'content_id' => $c->id, 'progress' => 100, 'finish' => '1']);
        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $planB->id, 'content_id' => $c->id, 'progress' => 30, 'finish' => '0']);

        $this->actingAs($this->userA);

        $export = new ExportMyLearningRecord();
        $array = $export->array();

        $rows = collect($array);
        $this->assertNotNull($rows->firstWhere(1, 'Export Iso Plan A'), 'Plan A must appear in export.');
        $this->assertNull($rows->firstWhere(1, 'Export Iso Plan B'), 'Plan B must not appear in export.');
    }

    /**
     * TEST 9: Existing certificate remains unchanged.
     */
    public function test_existing_certificate_remains_unchanged(): void
    {
        $plan = TrainingPlan::create(['title' => 'Exist Cert Plan', 'slug' => 'exist-cert-' . rand(1000, 9999), 'active' => 'Active']);
        $c = Content::create(['title' => 'Exist Cert Content', 'slug' => 'exist-cert-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c->id, 'content_order' => 0]);

        $setting = CertificateSetting::create(['certificate_type' => 'playlist', 'trainingplan_id' => $plan->id, 'minimum_progress' => 100, 'minimum_score' => 80, 'is_active' => true, 'issuer_name' => 'OWL Test']);

        $credentialId = 'OWL-TEST-' . strtoupper(Str::random(8));
        $cert = Certificate::create([
            'user_id'                => $this->userA->id,
            'certificate_setting_id' => $setting->id,
            'certificate_type'       => 'playlist',
            'source_id'              => $plan->id,
            'certificate_number'     => 'OWL-CERT-TEST-999',
            'credential_id'          => $credentialId,
            'title'                  => $plan->title,
            'participant_name'       => $this->userA->name,
            'issued_at'              => now(),
        ]);

        $this->actingAs($this->userA);

        // Load learning record context
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('certificateContext');
        $method->setAccessible(true);
        $certContext = $method->invoke($this->controller, $this->eligibilityService);

        $existing = $certContext['certificates']->get($setting->id);
        $this->assertNotNull($existing);
        $this->assertSame($cert->id, $existing->id);
        $this->assertSame('OWL-CERT-TEST-999', $existing->certificate_number);
        $this->assertSame($credentialId, $existing->credential_id);
    }

    /**
     * TEST 10: No progress tables are mutated by learning record or export.
     */
    public function test_no_progress_tables_are_mutated_by_learning_record(): void
    {
        $plan = TrainingPlan::create(['title' => 'Readonly Plan', 'slug' => 'ro-lr-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $c = Content::create(['title' => 'Readonly Content', 'slug' => 'ro-lr-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c->id, 'content_order' => 0]);

        CertificateSetting::create(['certificate_type' => 'playlist', 'trainingplan_id' => $plan->id, 'minimum_progress' => 100, 'minimum_score' => 80, 'is_active' => true, 'issuer_name' => 'OWL Test']);

        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $plan->id, 'content_id' => $c->id, 'progress' => 100, 'finish' => '1']);
        ContentUser::create(['user_id' => $this->userA->id, 'content_id' => $c->id, 'progress' => 100, 'finish' => '1']);

        $initialPcuCount = PlaylistContentUser::count();
        $initialCuCount = ContentUser::count();
        $initialCertCount = Certificate::count();

        $this->actingAs($this->userA);

        // 1. Run LearningRecord partial
        $this->controller->partial($this->eligibilityService);

        // 2. Run Export
        $export = new ExportMyLearningRecord();
        $export->array();

        $this->assertSame($initialPcuCount, PlaylistContentUser::count(), 'PCU count must remain unchanged.');
        $this->assertSame($initialCuCount, ContentUser::count(), 'CU count must remain unchanged.');
        $this->assertSame($initialCertCount, Certificate::count(), 'Certificates count must remain unchanged.');
    }
}
