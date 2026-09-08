<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\RoleAccessPermission;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use App\Services\LearningHours\ContentLearningHourService;
use App\Services\LearningHours\LearningHourConverterService;
use App\Services\LearningHours\PlaylistLearningHourService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LearningHoursSynchronizationTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test 1 — Converter Service Actual Hours Rules
     * Verifies that LearningHourConverterService calculates actual hours:
     * round(total_seconds / 3600, 4)
     */
    public function test_converter_service_actual_rules(): void
    {
        $converter = new LearningHourConverterService();

        $this->assertSame(0.0, $converter->convert('00:00:00'));
        $this->assertSame(0.3333, $converter->convert('00:20:00')); // 1200 / 3600
        $this->assertSame(0.5000, $converter->convert('00:30:00')); // 1800 / 3600
        $this->assertSame(0.6667, $converter->convert('00:40:00')); // 2400 / 3600
        $this->assertSame(0.8333, $converter->convert('00:50:00')); // 3000 / 3600
        $this->assertSame(1.0000, $converter->convert('01:00:00')); // 3600 / 3600
        $this->assertSame(1.1072, $converter->convert('01:06:26')); // 3986 / 3600
        $this->assertSame(1.5000, $converter->convert('01:30:00')); // 5400 / 3600
        $this->assertSame(2.0000, $converter->convert('02:00:00')); // 7200 / 3600
    }

    /**
     * Test 2 — Single Content Sync with Actual Hours
     */
    public function test_single_item_sync_methods(): void
    {
        $converter = new LearningHourConverterService();
        $contentService = new ContentLearningHourService($converter);

        $content = new Content([
            'duration' => '01:06:26', // 3986 seconds -> 1.1072 hours
            'learning_hours' => 0.0,
        ]);

        $result = $contentService->syncSingleContent($content);

        $this->assertTrue($result->success);
        $this->assertSame(1.1072, $result->learningHours);
        $this->assertSame('Learning Hours synchronized successfully.', $result->message);

        $array = $result->toArray();
        $this->assertTrue($array['success']);
        $this->assertSame(1.1072, $array['learning_hours']);
    }

    /**
     * Test 3 — Document Calculation: total_page * page_duration
     */
    public function test_document_calculation_actual_hours(): void
    {
        $converter = new LearningHourConverterService();
        $contentService = new ContentLearningHourService($converter);

        // Case A: 40 pages * 30 seconds = 1200 seconds (00:20:00) -> 0.3333 hours
        $doc1 = new Content([
            'type' => 'Document',
            'total_page' => 40,
            'page_duration' => 30,
            'duration' => '00:00:00',
            'learning_hours' => 0.0,
        ]);
        $result1 = $contentService->syncSingleContent($doc1);
        $this->assertSame('00:20:00', $result1->meta['duration']);
        $this->assertSame(0.3333, $result1->learningHours);

        // Case B: 100 pages * 30 seconds = 3000 seconds (00:50:00) -> 0.8333 hours
        $doc2 = new Content([
            'type' => 'Document',
            'total_page' => 100,
            'page_duration' => 30,
            'duration' => '00:00:00',
            'learning_hours' => 0.0,
        ]);
        $result2 = $contentService->syncSingleContent($doc2);
        $this->assertSame('00:50:00', $result2->meta['duration']);
        $this->assertSame(0.8333, $result2->learningHours);
    }

    /**
     * Test 4 — Playlist Master Calculation from Contents
     * Content A (00:20:00 = 1200s) + Content B (00:30:00 = 1800s) = 3000s (00:50:00) -> 0.8333 hours
     */
    public function test_playlist_master_actual_hours_calculation(): void
    {
        $c1 = Content::create(['title' => 'Content A', 'duration' => '00:20:00', 'active' => 'Active']);
        $c2 = Content::create(['title' => 'Content B', 'duration' => '00:30:00', 'active' => 'Active']);

        $plan = TrainingPlan::create([
            'title' => 'Playlist Test 50m',
            'active' => 'Active',
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);

        $playlistService = app(PlaylistLearningHourService::class);
        $result = $playlistService->syncSinglePlaylist($plan);

        $this->assertTrue($result->success);
        $this->assertSame(3000, $result->meta['total_duration_seconds']);
        $this->assertSame('00:50:00', $result->meta['duration']);
        $this->assertSame(0.8333, $result->learningHours);

        $plan->refresh();
        $this->assertSame(3000, $plan->total_duration_seconds);
        $this->assertEquals(0.8333, $plan->learning_hours);
    }

    /**
     * Test 5 — Authorization: Unauthenticated User Cannot Access Endpoints
     */
    public function test_unauthenticated_user_cannot_access_synchronize_endpoint(): void
    {
        $response = $this->postJson('/manage/content/test-slug/synchronize-learning-hours');
        $response->assertStatus(401);

        $responseBulk = $this->postJson('/manage/learning-hours/sync');
        $responseBulk->assertStatus(401);
    }

    /**
     * Test 6 — Authorization: User Without content_repository Permission Cannot Access Bulk Sync
     */
    public function test_user_without_permission_cannot_access_bulk_sync(): void
    {
        $user = User::factory()->create([
            'role'       => User::ROLE_ADMIN,
            'team'       => 'Quality Assurance',
            'department' => 'Technology',
            'division'   => 'Engineering',
            'ssohash'    => 'test-admin-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-NO-PERM',
        ]);

        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_ADMIN, 'module_key' => 'content_repository'],
            ['can_access' => false]
        );

        $response = $this->actingAs($user)->postJson('/manage/learning-hours/sync');
        // mpermission middleware redirects unauthorized users or aborts with 403
        $this->assertTrue(in_array($response->status(), [302, 403], true));
    }

    /**
     * Test 7 — Bulk Sync Endpoint Returns JSON with Actual Calculation and Summary
     */
    public function test_bulk_sync_endpoint_executes_and_returns_summary(): void
    {
        $user = User::factory()->create([
            'role'       => User::ROLE_SUPER_ADMIN,
            'team'       => 'Quality Assurance',
            'department' => 'Technology',
            'division'   => 'Engineering',
            'ssohash'    => 'test-superadmin-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-SUPERADMIN',
        ]);

        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_SUPER_ADMIN, 'module_key' => 'content_repository'],
            ['can_access' => true]
        );

        $response = $this->actingAs($user)->postJson('/manage/learning-hours/sync');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'content_updated',
                'content_skipped',
                'content_total',
                'playlist_updated',
                'playlist_skipped',
                'playlist_total',
            ],
        ]);
    }

    /**
     * Test 8 — Concurrency: Simultaneous Sync is Rejected with HTTP 429
     */
    public function test_bulk_sync_concurrency_lock_rejects_simultaneous_call(): void
    {
        $user = User::factory()->create([
            'role'       => User::ROLE_SUPER_ADMIN,
            'team'       => 'Quality Assurance',
            'department' => 'Technology',
            'division'   => 'Engineering',
            'ssohash'    => 'test-superadmin-lock-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-LOCK',
        ]);

        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_SUPER_ADMIN, 'module_key' => 'content_repository'],
            ['can_access' => true]
        );

        // Acquire lock manually to simulate another admin running sync
        $lock = Cache::lock('learning_hours_sync_lock', 60);
        $this->assertTrue($lock->get());

        try {
            $response = $this->actingAs($user)->postJson('/manage/learning-hours/sync');
            $response->assertStatus(429);
            $response->assertJson([
                'success' => false,
                'message' => 'Another synchronization is currently in progress. Please wait until it completes.',
            ]);
        } finally {
            $lock->release();
        }
    }
}
