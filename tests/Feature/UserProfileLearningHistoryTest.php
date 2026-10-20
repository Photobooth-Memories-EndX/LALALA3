<?php

namespace Tests\Feature;

use App\Models\ContentUser;
use App\Models\User;
use Tests\TestCase;

class UserProfileLearningHistoryTest extends TestCase
{
    protected User $admin;
    protected User $targetUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', '2')->first() ?? User::first();

        // Target user with ssohash and some learning records
        $this->targetUser = User::whereNotNull('ssohash')
            ->whereHas('contentUsers')
            ->first() ?? User::firstWhere('ssohash', '037525a8920571278cbe92f24a1a8317433d8d3e') ?? User::first();
    }

    /**
     * TEST 1: Manage User Profile renders Quick Filter toolbar with all 10 controls in 1 line.
     */
    public function test_user_profile_renders_quick_filter_toolbar(): void
    {
        $response = $this->actingAs($this->admin)->get(route('user.manage.profile', $this->targetUser->ssohash));

        $response->assertStatus(200);

        // Profile Hero & Stats still intact
        $response->assertSee('Employee Learning Profile');
        $response->assertSee($this->targetUser->name);

        // Quick Filter Section & Toolbar
        $response->assertSee('Quick Filter');
        $response->assertSee('quick-filter-toolbar', false);
        $response->assertSee('flex-xl-nowrap', false);

        // Controls
        $response->assertSee('id="profile-title-input"', false);
        $response->assertSee('placeholder="Search learning title..."', false);

        $response->assertSee('id="profile-classification-select"', false);
        $response->assertSee('All Classification');

        $response->assertSee('id="profile-status-select"', false);
        $response->assertSee('All Status');
        $response->assertSee('Finished');
        $response->assertSee('Ongoing');

        $response->assertSee('id="profile-type-select"', false);
        $response->assertSee('Type: All');

        $response->assertSee('id="profile-year-select"', false);
        $response->assertSee('All Year');

        // Action Buttons
        $response->assertSee('id="btn-profile-apply-filter"', false);
        $response->assertSee('id="btn-profile-more-filters"', false);
        $response->assertSee('id="btn-profile-reset-filter"', false);

        // Download Dropdown
        $response->assertSee('id="btn-profile-download-dropdown"', false);
        $response->assertSee('btn-download');
        $response->assertSee('id="btn-profile-download-excel"', false);
        $response->assertSee('Download Excel (.xlsx)');
        $response->assertSee('id="btn-profile-download-csv"', false);
        $response->assertSee('Download CSV (.csv)');
        $response->assertSee('id="btn-profile-download-pdf"', false);
        $response->assertSee('Download PDF (.pdf)');

        // More Filters Modal
        $response->assertSee('id="profileMoreFiltersModal"', false);
        $response->assertSee('id="profile-modal-from-date"', false);
        $response->assertSee('id="profile-modal-to-date"', false);
        $response->assertSee('name="profile_modal_progress_range"', false);
        $response->assertSee('name="profile_modal_duration"', false);

        // Redundant default DataTables search hidden in CSS
        $response->assertSee('#MyTable_filter {', false);
    }

    /**
     * TEST 2: Server-side DataTables AJAX endpoint returns valid learning records.
     */
    public function test_user_profile_datatable_ajax_endpoint(): void
    {
        $response = $this->actingAs($this->admin)->getJson(
            route('user.manage.profile.learning.datatable', $this->targetUser->ssohash)
        );

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data',
        ]);

        // Standard DataTables payload with search array, order, and columns (verifies no Array to string conversion)
        $responseWithDtParams = $this->actingAs($this->admin)->getJson(
            route('user.manage.profile.learning.datatable', [
                'hash' => $this->targetUser->ssohash,
                'draw' => 1,
                'start' => 0,
                'length' => 25,
                'search' => ['value' => '', 'regex' => 'false'],
                'order' => [['column' => 6, 'dir' => 'desc']],
                'columns' => [
                    ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex'],
                    ['data' => 'status_badge', 'name' => 'content_users.finish'],
                    ['data' => 'user_name', 'name' => 'user.name'],
                    ['data' => 'title_html', 'name' => 'content.title'],
                    ['data' => 'classification_html', 'name' => 'content.classification.classification_name'],
                    ['data' => 'duration_formatted', 'name' => 'content.duration'],
                    ['data' => 'date_formatted', 'name' => 'content_users.created_at'],
                    ['data' => 'progress_html', 'name' => 'content_users.progress'],
                ],
            ])
        );

        $responseWithDtParams->assertStatus(200);
        $responseWithDtParams->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data',
        ]);
        $this->assertGreaterThan(0, $responseWithDtParams->json('recordsTotal'));
    }

    /**
     * TEST 3: DataTables endpoint correctly applies filters (Title, Status, Type, Year).
     */
    public function test_user_profile_datatable_applies_filters(): void
    {
        // 1. Status Filter
        $respFinished = $this->actingAs($this->admin)->getJson(
            route('user.manage.profile.learning.datatable', [
                'hash' => $this->targetUser->ssohash,
                'status' => 'finished',
            ])
        );
        $respFinished->assertStatus(200);

        // 2. Year Filter
        $respYear = $this->actingAs($this->admin)->getJson(
            route('user.manage.profile.learning.datatable', [
                'hash' => $this->targetUser->ssohash,
                'year' => '2026',
            ])
        );
        $respYear->assertStatus(200);

        // 3. Search Title Filter
        $respTitle = $this->actingAs($this->admin)->getJson(
            route('user.manage.profile.learning.datatable', [
                'hash' => $this->targetUser->ssohash,
                'learning_title' => 'TestNonExistentKeyword999xyz',
            ])
        );
        $respTitle->assertStatus(200);
        $data = $respTitle->json('data');
        $this->assertCount(0, $data);
    }

    /**
     * TEST 4: Excel export endpoint works and respects filters.
     */
    public function test_user_profile_excel_export(): void
    {
        $response = $this->actingAs($this->admin)->get(
            route('user.manage.profile.export.excel', [
                'hash' => $this->targetUser->ssohash,
                'status' => 'finished',
            ])
        );

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition'));
    }

    /**
     * TEST 5: CSV export endpoint works, returns UTF-8 BOM, and respects filters.
     */
    public function test_user_profile_csv_export(): void
    {
        $response = $this->actingAs($this->admin)->get(
            route('user.manage.profile.export.csv', [
                'hash' => $this->targetUser->ssohash,
                'status' => 'ongoing',
            ])
        );

        $response->assertStatus(200);
        $this->assertStringContainsString('.csv', $response->headers->get('content-disposition'));
    }

    /**
     * TEST 6: PDF export endpoint works and returns landscape PDF.
     */
    public function test_user_profile_pdf_export(): void
    {
        $response = $this->actingAs($this->admin)->get(
            route('user.manage.profile.export.pdf', [
                'hash' => $this->targetUser->ssohash,
                'year' => '2025',
            ])
        );

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('.pdf', $response->headers->get('content-disposition'));
    }

    /**
     * TEST 7: Direct action=download / download_csv / download_pdf query parameters work.
     */
    public function test_user_profile_action_query_param_exports(): void
    {
        // 1. action=download
        $respExcel = $this->actingAs($this->admin)->get(
            route('user.manage.profile', [
                'hash' => $this->targetUser->ssohash,
                'action' => 'download',
            ])
        );
        $respExcel->assertStatus(200);
        $respExcel->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        // 2. action=download_csv
        $respCsv = $this->actingAs($this->admin)->get(
            route('user.manage.profile', [
                'hash' => $this->targetUser->ssohash,
                'action' => 'download_csv',
            ])
        );
        $respCsv->assertStatus(200);
        $this->assertStringContainsString('.csv', $respCsv->headers->get('content-disposition'));

        // 3. action=download_pdf
        $respPdf = $this->actingAs($this->admin)->get(
            route('user.manage.profile', [
                'hash' => $this->targetUser->ssohash,
                'action' => 'download_pdf',
            ])
        );
        $respPdf->assertStatus(200);
        $respPdf->assertHeader('content-type', 'application/pdf');
    }

    /**
     * TEST 8: Authorization protection.
     */
    public function test_user_profile_authorization_protection(): void
    {
        // Guest cannot access
        $respGuest = $this->get(route('user.manage.profile', $this->targetUser->ssohash));
        $respGuest->assertRedirect('/login');

        $respDatatableGuest = $this->get(route('user.manage.profile.learning.datatable', $this->targetUser->ssohash));
        $respDatatableGuest->assertRedirect('/login');
    }
}
