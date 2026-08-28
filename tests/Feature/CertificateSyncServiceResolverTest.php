<?php

namespace Tests\Feature;

use App\Models\Assesment;
use App\Models\CertificateSetting;
use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use App\Services\CertificateSyncService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class CertificateSyncServiceResolverTest extends TestCase
{
    use DatabaseTransactions;

    protected CertificateSyncService $service;
    protected Classification $classification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(CertificateSyncService::class);

        $this->classification = Classification::firstOrCreate(
            ['classification_name' => 'Certificate Sync Test Classification'],
            ['slug' => 'cert-sync-test-classification']
        );
    }

    /**
     * TEST 1 — Sync creates certificate settings for Content
     */
    public function test_1_sync_creates_certificate_settings_for_content(): void
    {
        $content = Content::create([
            'title'             => 'Sync Test Content ' . uniqid(),
            'slug'              => 'sync-test-c-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $this->assertDatabaseMissing('certificate_settings', [
            'certificate_type' => 'content',
            'content_id'       => $content->id,
        ]);

        $this->service->syncContents();

        $this->assertDatabaseHas('certificate_settings', [
            'certificate_type' => 'content',
            'content_id'       => $content->id,
            'is_active'        => true,
            'minimum_progress' => 100,
        ]);
    }

    /**
     * TEST 2 — Sync creates certificate settings for Playlist
     */
    public function test_2_sync_creates_certificate_settings_for_playlist(): void
    {
        $plan = TrainingPlan::create([
            'title'  => 'Sync Test Plan ' . uniqid(),
            'slug'   => 'sync-test-plan-' . rand(1000, 9999),
            'active' => 'Active',
        ]);

        $this->assertDatabaseMissing('certificate_settings', [
            'certificate_type' => 'playlist',
            'trainingplan_id'  => $plan->id,
        ]);

        $this->service->syncPlaylists();

        $this->assertDatabaseHas('certificate_settings', [
            'certificate_type' => 'playlist',
            'trainingplan_id'  => $plan->id,
            'is_active'        => true,
            'minimum_progress' => 100,
        ]);
    }

    /**
     * TEST 3 — Sync creates certificate settings for Global Assessment
     */
    public function test_3_sync_creates_certificate_settings_for_global_assessment(): void
    {
        $assessment = Assesment::create([
            'title'         => 'Sync Global Assessment ' . uniqid(),
            'slug'          => 'sync-glob-ass-' . rand(1000, 9999),
            'type'          => 'global',
            'status'        => 'OK',
            'passing_grade' => 80,
            'batch'         => 1,
        ]);

        $this->assertDatabaseMissing('certificate_settings', [
            'certificate_type' => 'global',
            'assessment_id'    => $assessment->id,
        ]);

        $this->service->syncGlobalAssessments();

        $this->assertDatabaseHas('certificate_settings', [
            'certificate_type'       => 'global',
            'assessment_id'          => $assessment->id,
            'is_assessment_required' => true,
            'minimum_score'          => 80,
        ]);
    }

    /**
     * TEST 4 — Sync is idempotent and does not create duplicate settings
     */
    public function test_4_sync_is_idempotent(): void
    {
        $content = Content::create([
            'title'             => 'Idempotent Content ' . uniqid(),
            'slug'              => 'idemp-c-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        // Run sync multiple times
        $this->service->sync();
        $count1 = CertificateSetting::where('certificate_type', 'content')->where('content_id', $content->id)->count();

        $this->service->sync();
        $count2 = CertificateSetting::where('certificate_type', 'content')->where('content_id', $content->id)->count();

        $this->assertSame(1, $count1);
        $this->assertSame(1, $count2);
    }

    /**
     * TEST 5 — Sync preserves customized settings
     */
    public function test_5_sync_preserves_customized_settings(): void
    {
        $content = Content::create([
            'title'             => 'Custom Content ' . uniqid(),
            'slug'              => 'cust-c-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        // Pre-create setting with custom attributes
        $setting = CertificateSetting::create([
            'certificate_type' => 'content',
            'content_id'       => $content->id,
            'minimum_progress' => 85,
            'minimum_score'    => 90,
            'issuer_name'      => 'Custom Issuer PTKP',
            'is_active'        => false,
        ]);

        // Run sync
        $this->service->sync();

        $refreshed = $setting->fresh();
        $this->assertSame(85, (int) $refreshed->minimum_progress);
        $this->assertSame(90, (int) $refreshed->minimum_score);
        $this->assertSame('Custom Issuer PTKP', $refreshed->issuer_name);
        $this->assertFalse((bool) $refreshed->is_active);
    }

    /**
     * TEST 6 — Sync performs zero mutations on progress tables
     */
    public function test_6_sync_performs_zero_mutations_on_progress_tables(): void
    {
        $initialCuCount = ContentUser::count();
        $initialPcuCount = PlaylistContentUser::count();

        $this->service->sync();

        $this->assertSame($initialCuCount, ContentUser::count());
        $this->assertSame($initialPcuCount, PlaylistContentUser::count());
    }
}
