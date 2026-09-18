<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Content;
use App\Models\RoleAccessPermission;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TotalPageSynchronizationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected Classification $classification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->classification = Classification::create([
            'classification_name' => 'Testing Classification ' . \Illuminate\Support\Str::random(5),
            'slug'                => 'testing-classification-' . \Illuminate\Support\Str::random(5),
        ]);

        $this->adminUser = User::factory()->create([
            'role'       => User::ROLE_SUPER_ADMIN,
            'team'       => 'Quality Assurance',
            'department' => 'Technology',
            'division'   => 'Engineering',
            'ssohash'    => 'test-superadmin-totalpage-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-SUPERADMIN-TP',
        ]);

        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_SUPER_ADMIN, 'module_key' => 'content_repository'],
            ['can_access' => true]
        );
    }

    /**
     * Test 1 — Unauthenticated User Cannot Access Synchronization Endpoints
     */
    public function test_unauthenticated_user_cannot_access_total_page_endpoints(): void
    {
        $responseList = $this->getJson(route('content.document-pdf-list'));
        $this->assertTrue(in_array($responseList->status(), [401, 302], true));

        $responseSync = $this->postJson(route('content.sync-total-page'), [
            'id' => 1,
            'total_page' => 10,
        ]);
        $this->assertTrue(in_array($responseSync->status(), [401, 302], true));
    }

    /**
     * Test 2 — User Without Permission Cannot Access Bulk Sync Endpoints
     */
    public function test_user_without_permission_cannot_access_total_page_endpoints(): void
    {
        $user = User::factory()->create([
            'role'       => User::ROLE_ADMIN,
            'team'       => 'Quality Assurance',
            'department' => 'Technology',
            'division'   => 'Engineering',
            'ssohash'    => 'test-admin-tp-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-NO-PERM-TP',
        ]);

        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_ADMIN, 'module_key' => 'content_repository'],
            ['can_access' => false]
        );

        $responseList = $this->actingAs($user)->getJson(route('content.document-pdf-list'));
        $this->assertTrue(in_array($responseList->status(), [302, 403], true));

        $responseSync = $this->actingAs($user)->postJson(route('content.sync-total-page'), [
            'id' => 1,
            'total_page' => 10,
        ]);
        $this->assertTrue(in_array($responseSync->status(), [302, 403], true));
    }

    /**
     * Test 3 — Document PDF List Endpoint Returns Correct Structure Filtering Only Document Type
     */
    public function test_document_pdf_list_endpoint_returns_json_structure(): void
    {
        $doc = Content::create([
            'title'             => 'Test PDF Document Content',
            'type'              => 'Document',
            'active'            => 'Inactive',
            'classification_id' => $this->classification->id,
            'total_page'        => 15,
            'page_duration'     => 60,
        ]);

        $video = Content::create([
            'title'             => 'Test Video Content',
            'type'              => 'Video',
            'active'            => 'Inactive',
            'classification_id' => $this->classification->id,
            'duration'          => '00:10:00',
        ]);

        $response = $this->actingAs($this->adminUser)->getJson(route('content.document-pdf-list'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $data = $response->json('data');
        $this->assertIsArray($data);

        // Verify doc is present and video is NOT present in document list
        $docIds = collect($data)->pluck('id')->all();
        $this->assertContains($doc->id, $docIds);
        $this->assertNotContains($video->id, $docIds);

        // Verify fields
        $docItem = collect($data)->firstWhere('id', $doc->id);
        $this->assertNotNull($docItem);
        $this->assertSame('Test PDF Document Content', $docItem['title']);
        $this->assertSame(15, $docItem['total_page']);
        $this->assertSame(60, $docItem['page_duration']);
        $this->assertArrayHasKey('file_url', $docItem);
        $this->assertArrayHasKey('file_exists', $docItem);
    }

    /**
     * Test 4 — Validation Errors on Sync Endpoint
     */
    public function test_sync_total_page_validation_errors(): void
    {
        // Missing parameters
        $res1 = $this->actingAs($this->adminUser)->postJson(route('content.sync-total-page'), []);
        $res1->assertStatus(422);
        $res1->assertJsonValidationErrors(['id', 'total_page']);

        // Invalid total page (zero or negative)
        $res2 = $this->actingAs($this->adminUser)->postJson(route('content.sync-total-page'), [
            'id' => 9999999,
            'total_page' => 0,
        ]);
        $res2->assertStatus(422);
        $res2->assertJsonValidationErrors(['id', 'total_page']);
    }

    /**
     * Test 5 — Sync Total Page is Rejected When Content is Published
     */
    public function test_sync_total_page_rejected_when_content_is_published(): void
    {
        $content = Content::create([
            'title'             => 'Published Content Page Sync Test',
            'type'              => 'Document',
            'active'            => 'Active',
            'classification_id' => $this->classification->id,
            'total_page'        => 10,
        ]);

        $response = $this->actingAs($this->adminUser)->postJson(route('content.sync-total-page'), [
            'id'         => $content->id,
            'total_page' => 20,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Cannot update total page while content is published. Please unpublish first.',
        ]);
    }

    /**
     * Test 6 — Sync Total Page is Rejected When Content Type is Not Document
     */
    public function test_sync_total_page_rejected_when_not_document(): void
    {
        $content = Content::create([
            'title'             => 'Video Content Page Sync Test',
            'type'              => 'Video',
            'active'            => 'Inactive',
            'classification_id' => $this->classification->id,
            'duration'          => '00:05:00',
        ]);

        $response = $this->actingAs($this->adminUser)->postJson(route('content.sync-total-page'), [
            'id'         => $content->id,
            'total_page' => 12,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Content is not of type Document.',
        ]);
    }

    /**
     * Test 7 — Sync Total Page Successfully Updates Total Page
     */
    public function test_sync_total_page_success_when_updating_total_page(): void
    {
        $content = Content::create([
            'title'             => 'Unpublished Document Page Sync Test',
            'type'              => 'Document',
            'active'            => 'Inactive',
            'classification_id' => $this->classification->id,
            'total_page'        => 10,
            'page_duration'     => 60,
        ]);

        $response = $this->actingAs($this->adminUser)->postJson(route('content.sync-total-page'), [
            'id'         => $content->id,
            'total_page' => 45,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success'        => true,
            'updated'        => true,
            'id'             => $content->id,
            'old_total_page' => 10,
            'new_total_page' => 45,
            'message'        => 'Total page updated successfully.',
        ]);

        $content->refresh();
        $this->assertSame(45, (int) $content->total_page);
    }

    /**
     * Test 8 — Sync Total Page Returns Already Synchronized When Total Page Matches
     */
    public function test_sync_total_page_returns_already_synchronized_when_same_page_count(): void
    {
        $content = Content::create([
            'title'             => 'Unpublished Document Already Synced Test',
            'type'              => 'Document',
            'active'            => 'Inactive',
            'classification_id' => $this->classification->id,
            'total_page'        => 45,
        ]);

        $response = $this->actingAs($this->adminUser)->postJson(route('content.sync-total-page'), [
            'id'         => $content->id,
            'total_page' => 45,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success'    => true,
            'updated'    => false,
            'id'         => $content->id,
            'total_page' => 45,
            'message'    => 'Already synchronized.',
        ]);
    }

    /**
     * Test 9 — Content Index View Renders Modern Total Page Synchronization Tabler Modal
     */
    public function test_content_index_view_renders_modern_total_page_sync_modal(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('content.index'));

        $response->assertStatus(200);

        // Header trigger button with Tabler modal attributes
        $response->assertSee('id="btnSyncPdfTotalPage"', false);
        $response->assertSee('data-bs-toggle="modal"', false);
        $response->assertSee('data-bs-target="#modalTotalPageSynchronization"', false);

        // Modal shell and header
        $response->assertSee('id="modalTotalPageSynchronization"', false);
        $response->assertSee('Synchronize Total Pages', false);
        $response->assertSee('syncTotalPageCloseHeaderBtn', false);

        // Prompt state
        $response->assertSee('id="syncTotalPagePromptBody"', false);
        $response->assertSee('PDF documents will be checked.', false);
        $response->assertSee('Existing synchronized values will be preserved when already accurate.', false);

        // Loading state
        $response->assertSee('id="syncTotalPageLoadingBody"', false);
        $response->assertSee('id="syncTotalPageProgressBar"', false);
        $response->assertSee('id="syncTotalPageProgressCounter"', false);

        // Result summary state with 4 cards
        $response->assertSee('id="syncTotalPageResultBody"', false);
        $response->assertSee('id="resTotalDocuments"', false);
        $response->assertSee('id="resUpdatedPdf"', false);
        $response->assertSee('id="resAlreadySynced"', false);
        $response->assertSee('id="resMissingDefaulted"', false);

        // Collapsible missing/defaulted items
        $response->assertSee('id="syncTotalPageMissingContainer"', false);
        $response->assertSee('id="collapseMissingDetails"', false);
        $response->assertSee('id="syncTotalPageMissingList"', false);

        // Error container and footer buttons
        $response->assertSee('id="syncTotalPageErrorContainer"', false);
        $response->assertSee('id="btnCancelSyncTotalPage"', false);
        $response->assertSee('id="btnExecuteSyncTotalPage"', false);
        $response->assertSee('id="btnCloseSyncTotalPage"', false);
    }

    /**
     * Test 10 — JavaScript File Does Not Contain Native Browser Alert, Confirm, or Prompt
     */
    public function test_javascript_does_not_contain_native_alert(): void
    {
        $jsPath = public_path('js/sync-content-document-total-page.js');
        $this->assertFileExists($jsPath);

        $jsContent = file_get_contents($jsPath);

        $this->assertDoesNotMatchRegularExpression('/\balert\s*\(/i', $jsContent, 'JavaScript must not use native alert().');
        $this->assertDoesNotMatchRegularExpression('/\bconfirm\s*\(/i', $jsContent, 'JavaScript must not use native confirm().');
        $this->assertDoesNotMatchRegularExpression('/\bprompt\s*\(/i', $jsContent, 'JavaScript must not use native prompt().');
    }
}
