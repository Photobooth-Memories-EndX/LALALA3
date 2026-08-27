<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentScrollModeTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Classification $classification;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckManagePermission::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
        ]);

        $this->user = User::factory()->create([
            'team'       => 'Quality Assurance',
            'department' => 'Technology',
            'division'   => 'Engineering',
            'ssohash'    => 'test-hash-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-TEST',
            'role'       => 1,
        ]);
        $this->actingAs($this->user);

        $this->classification = Classification::create([
            'name' => 'General',
            'description' => 'General classification',
        ]);
    }

    /**
     * Test 1 — ADMIN STORE: Document with require_page_duration = true and page_duration = 30
     * Expected: Valid, saved page_duration = 30, duration = 59 * 30 = 1770s (00:29:30).
     */
    public function test_admin_store_document_with_pagination_mode(): void
    {
        $response = $this->post(route('content.store'), [
            'title'                 => 'Timed Document Test',
            'type'                  => 'Document',
            'classification'        => $this->classification->id,
            'require_page_duration' => '1',
            'page_duration'         => '30',
            'document_total_pages'  => '59',
            'learning_hours'        => '0.5',
            'action'                => 'save',
        ]);

        $response->assertSessionHasNoErrors();

        $content = Content::where('title', 'Timed Document Test')->first();
        $this->assertNotNull($content);
        $this->assertSame(30, (int) $content->page_duration);
        $this->assertSame(59, (int) $content->total_page);
        $this->assertSame('00:29:30', $content->duration);
    }

    /**
     * Test 2 — ADMIN STORE: Document with require_page_duration = true and page_duration = 0 (or negative)
     * Expected: Validation error rejecting 0 when duration is enabled.
     */
    public function test_admin_store_document_rejects_zero_when_duration_required(): void
    {
        $response = $this->post(route('content.store'), [
            'title'                 => 'Invalid Timed Document',
            'type'                  => 'Document',
            'classification'        => $this->classification->id,
            'require_page_duration' => '1',
            'page_duration'         => '0',
            'document_total_pages'  => '59',
            'learning_hours'        => '0.5',
            'action'                => 'save',
        ]);

        $response->assertSessionHasErrors('page_duration');
    }

    /**
     * Test 3 — ADMIN STORE: Document with require_page_duration = false (Scroll Mode)
     * Expected: Normalized to page_duration = 0, duration = "00:00:00".
     */
    public function test_admin_store_document_with_scroll_mode_normalizes_to_zero(): void
    {
        $response = $this->post(route('content.store'), [
            'title'                 => 'Scroll Mode Document Test',
            'type'                  => 'Document',
            'classification'        => $this->classification->id,
            'require_page_duration' => '0',
            'page_duration'         => '30', // submitted but should be normalized to 0
            'document_total_pages'  => '59',
            'learning_hours'        => '0.5',
            'action'                => 'save',
        ]);

        $response->assertSessionHasNoErrors();

        $content = Content::where('title', 'Scroll Mode Document Test')->first();
        $this->assertNotNull($content);
        $this->assertSame(0, (int) $content->page_duration);
        $this->assertSame(59, (int) $content->total_page);
        $this->assertSame('00:00:00', $content->duration);
    }

    /**
     * Test 4 — ADMIN UPDATE: Convert existing Pagination Document (page_duration = 30) to Scroll Mode (page_duration = 0)
     */
    public function test_admin_update_document_from_pagination_to_scroll_mode(): void
    {
        $content = Content::create([
            'title'             => 'Existing Timed Doc',
            'type'              => 'Document',
            'classification_id' => $this->classification->id,
            'total_page'        => 59,
            'page_duration'     => 30,
            'duration'          => '00:29:30',
            'learning_hours'    => 0.5,
            'active'            => 'Active',
            'slug'              => 'existing-timed-doc',
        ]);

        $response = $this->put(route('content.update', $content->slug), [
            'contentid'             => $content->id,
            'title'                 => 'Existing Timed Doc Updated to Scroll',
            'type'                  => 'Document',
            'classification'        => $this->classification->id,
            'require_page_duration' => '0',
            'page_duration'         => '0',
            'total_page'            => '59',
            'action'                => 'update',
        ]);

        $response->assertSessionHasNoErrors();

        $content->refresh();
        $this->assertSame(0, (int) $content->page_duration);
        $this->assertSame(59, (int) $content->total_page);
        $this->assertSame('00:00:00', $content->duration);
    }

    /**
     * Test 5 — ADMIN UPDATE: Convert Scroll Mode Document (page_duration = 0) to Pagination Mode (page_duration = 20)
     */
    public function test_admin_update_document_from_scroll_mode_to_pagination(): void
    {
        $content = Content::create([
            'title'             => 'Existing Scroll Doc',
            'type'              => 'Document',
            'classification_id' => $this->classification->id,
            'total_page'        => 50,
            'page_duration'     => 0,
            'duration'          => '00:00:00',
            'learning_hours'    => 0.0,
            'active'            => 'Active',
            'slug'              => 'existing-scroll-doc',
        ]);

        $response = $this->put(route('content.update', $content->slug), [
            'contentid'             => $content->id,
            'title'                 => 'Existing Scroll Doc Updated to Pagination',
            'type'                  => 'Document',
            'classification'        => $this->classification->id,
            'require_page_duration' => '1',
            'page_duration'         => '20',
            'total_page'            => '50',
            'action'                => 'update',
        ]);

        $response->assertSessionHasNoErrors();

        $content->refresh();
        $this->assertSame(20, (int) $content->page_duration);
        $this->assertSame(50, (int) $content->total_page);
        $this->assertSame('00:16:40', $content->duration);
    }

    /**
     * Test 6 — PROGRESS UPDATE: Scroll Mode (page_duration = 0) computes progress without session countdown
     */
    public function test_learner_progress_update_scroll_mode_computes_directly(): void
    {
        $content = Content::create([
            'title'             => 'Scroll Mode Progress Doc',
            'type'              => 'Document',
            'classification_id' => $this->classification->id,
            'total_page'        => 50,
            'page_duration'     => 0,
            'duration'          => '00:00:00',
            'learning_hours'    => 0.0,
            'active'            => 'Active',
            'slug'              => 'scroll-mode-progress-doc',
        ]);

        // Learner scrolls to page 25
        $response = $this->postJson(route('content.user.progress.update'), [
            'content_id'  => $content->id,
            'type'        => 'document',
            'last_page'   => 25,
            'total_pages' => 50,
            'status'      => 'in_progress',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success'  => true,
            'progress' => 50,
        ]);

        $record = ContentUser::where('content_id', $content->id)->where('user_id', $this->user->id)->first();
        $this->assertNotNull($record);
        $this->assertSame(25, (int) $record->last_page);
        $this->assertSame(50, (int) $record->progress);
    }

    /**
     * Test 7 — PROGRESS MONOTONICITY: Scrolling back from page 25 to page 10 in Scroll Mode preserves last_page = 25
     */
    public function test_learner_progress_scroll_mode_is_monotonic(): void
    {
        $content = Content::create([
            'title'             => 'Monotonic Scroll Doc',
            'type'              => 'Document',
            'classification_id' => $this->classification->id,
            'total_page'        => 50,
            'page_duration'     => 0,
            'duration'          => '00:00:00',
            'learning_hours'    => 0.0,
            'active'            => 'Active',
            'slug'              => 'monotonic-scroll-doc',
        ]);

        ContentUser::create([
            'user_id'    => $this->user->id,
            'content_id' => $content->id,
            'last_page'  => 25,
            'total_pages'=> 50,
            'progress'   => 50,
        ]);

        // Learner scrolls back to page 10
        $response = $this->postJson(route('content.user.progress.update'), [
            'content_id'  => $content->id,
            'type'        => 'document',
            'last_page'   => 10,
            'total_pages' => 50,
            'status'      => 'in_progress',
        ]);

        $response->assertOk();

        $record = ContentUser::where('content_id', $content->id)->where('user_id', $this->user->id)->first();
        $this->assertSame(25, (int) $record->last_page);
        $this->assertSame(50, (int) $record->progress);
    }

    /**
     * Test 8 — PROGRESS COMPLETION: Reaching the last page in Scroll Mode completes the document
     */
    public function test_learner_scroll_mode_reaches_last_page_completes_content(): void
    {
        $content = Content::create([
            'title'             => 'Complete Scroll Doc',
            'type'              => 'Document',
            'classification_id' => $this->classification->id,
            'total_page'        => 50,
            'page_duration'     => 0,
            'duration'          => '00:00:00',
            'learning_hours'    => 0.0,
            'active'            => 'Active',
            'slug'              => 'complete-scroll-doc',
        ]);

        $response = $this->postJson(route('content.user.progress.update'), [
            'content_id'  => $content->id,
            'type'        => 'document',
            'last_page'   => 50,
            'total_pages' => 50,
            'status'      => 'in_progress',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success'  => true,
            'progress' => 100,
        ]);

        $record = ContentUser::where('content_id', $content->id)->where('user_id', $this->user->id)->first();
        $this->assertSame(50, (int) $record->last_page);
        $this->assertSame(100, (int) $record->progress);
        $this->assertSame('1', (string) $record->finish);
        $this->assertSame('completed', $record->learning_status);
    }

    /**
     * Test 9 — NON-DOCUMENT: Video content is unaffected by document progress logic
     */
    public function test_video_content_progress_is_unaffected(): void
    {
        $content = Content::create([
            'title'             => 'Sample Video Content',
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:10:00',
            'learning_hours'    => 0.5,
            'active'            => 'Active',
            'slug'              => 'sample-video-content',
        ]);

        $response = $this->postJson(route('content.user.progress.update'), [
            'content_id'       => $content->id,
            'type'             => 'video',
            'current_time'     => 300,
            'duration_seconds' => 600,
            'status'           => 'in_progress',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success'  => true,
            'progress' => 50,
        ]);

        $record = ContentUser::where('content_id', $content->id)->where('user_id', $this->user->id)->first();
        $this->assertSame(50, (int) $record->progress);
        $this->assertSame(300, (int) $record->current_time);
        $this->assertSame(600, (int) $record->duration_seconds);
    }

    /**
     * Test 10 — TIMED PAGINATION MODE: Progress update verifies timer session for page_duration > 0
     */
    public function test_timed_pagination_mode_enforces_page_duration_timer(): void
    {
        $content = Content::create([
            'title'             => 'Timed Document Timer Test',
            'type'              => 'Document',
            'classification_id' => $this->classification->id,
            'total_page'        => 10,
            'page_duration'     => 30,
            'duration'          => '00:05:00',
            'learning_hours'    => 0.5,
            'active'            => 'Active',
            'slug'              => 'timed-doc-timer-test',
        ]);

        // 1. Activate page 1
        $this->postJson(route('content.user.progress.update'), [
            'content_id'  => $content->id,
            'type'        => 'document',
            'active_page' => 1,
            'total_pages' => 10,
            'status'      => 'in_progress',
        ]);

        // 2. Attempt to complete page 1 with only 5 seconds elapsed (insufficient time)
        $responseFail = $this->postJson(route('content.user.progress.update'), [
            'content_id'           => $content->id,
            'type'                 => 'document',
            'active_page'          => 1,
            'completed_page'       => 1,
            'page_elapsed_seconds' => 5,
            'total_pages'          => 10,
            'status'               => 'in_progress',
        ]);

        $responseFail->assertOk();
        $responseFail->assertJson([
            'success'        => true,
            'page_completed' => false,
        ]);

        // 3. Fake session timer to have started 30 seconds ago
        // 3. Complete page 1 with session timer
        $responseSuccess = $this->withSession([
            'learning.document_page_timer.' . $content->id => [
                'page'       => 1,
                'started_at' => microtime(true) - 30.5,
            ]
        ])->postJson(route('content.user.progress.update'), [
            'content_id'           => $content->id,
            'type'                 => 'document',
            'active_page'          => 1,
            'completed_page'       => 1,
            'page_elapsed_seconds' => 30,
            'total_pages'          => 10,
            'status'               => 'in_progress',
        ]);

        $responseSuccess->assertOk();
        $responseSuccess->assertJson([
            'success'        => true,
            'page_completed' => true,
            'last_page'      => 1,
            'progress'       => 10,
        ]);
    }

    /**
     * Test 11 — LEARNING HOURS SYNC: Syncing Document with page_duration = 0 safely sets 00:00:00 duration
     */
    public function test_learning_hours_service_sync_handles_scroll_mode_safely(): void
    {
        $content = Content::create([
            'title'             => 'Sync Scroll Doc',
            'type'              => 'Document',
            'classification_id' => $this->classification->id,
            'total_page'        => 50,
            'page_duration'     => 0,
            'duration'          => '00:00:00',
            'learning_hours'    => 0.0,
            'active'            => 'Active',
            'slug'              => 'sync-scroll-doc',
        ]);

        $response = $this->postJson(route('content.synchronize-learning-hours', $content->slug), [
            'type'          => 'Document',
            'total_page'    => 50,
            'page_duration' => 0,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'duration' => '00:00:00',
        ]);

        $content->refresh();
        $this->assertSame('00:00:00', $content->duration);
        $this->assertEquals(0.0, (float) $content->learning_hours);
    }
}
