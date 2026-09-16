<?php

namespace Tests\Feature;

use App\Models\RoleAccessPermission;
use App\Models\User;
use Tests\TestCase;

class PlaylistLearningHourTerminologyAndInfoTest extends TestCase
{
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_ADMIN, 'module_key' => 'learning_hours_management'],
            ['can_access' => true]
        );

        $this->admin = User::where('role', User::ROLE_ADMIN)->first()
            ?? User::where('role', User::ROLE_SUPER_ADMIN)->first()
            ?? User::first();
    }

    public function test_table_headers_render_all_required_columns_with_distinct_terminology(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/learning-hours/playlist-content-user');
        $response->assertStatus(200);

        $html = $response->getContent();

        // 1. Column headers
        $this->assertStringContainsString('Progress', $html);
        $this->assertStringContainsString('Current Time', $html);
        $this->assertStringContainsString('Duration', $html);
        $this->assertStringContainsString('Actual Learning Hour', $html);
        $this->assertStringContainsString('Master Learning Hour', $html);
        $this->assertStringContainsString('Calculation Source', $html);
        $this->assertStringContainsString('Status', $html);
        $this->assertStringContainsString('Completed At', $html);
    }

    public function test_table_legend_and_reference_guide_is_displayed_above_table(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/learning-hours/playlist-content-user');
        $response->assertStatus(200);

        $html = $response->getContent();

        // Legend Title & Key Clarification
        $this->assertStringContainsString('Learning Hour Information & Reference Guide', $html);
        $this->assertStringContainsString('Master Learning Hour is a reference value. Actual Learning Hour represents the calculated learning time for the learner.', $html);

        // Core Explanations
        $this->assertStringContainsString('Actual tracked time recorded for this learner (stored in seconds).', $html);
        $this->assertStringContainsString('Calculated learning hours based on tracked learning time. Historical completed records may use content duration as fallback.', $html);
        $this->assertStringContainsString('Reference learning-hour value of the content. This is <strong>NOT</strong> the learner\'s actual learning hour.', $html);
        $this->assertStringContainsString('Official duration of the content.', $html);
        $this->assertStringContainsString('Completion percentage of the content (100% does not equal Master Learning Hour).', $html);
        $this->assertStringContainsString('Indicates whether Actual Learning Hour was determined via Actual Tracking, Historical Fallback, or Zero / Unfinished.', $html);
    }

    public function test_table_header_tooltips_provide_clear_field_definitions(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/learning-hours/playlist-content-user');
        $response->assertStatus(200);

        $html = $response->getContent();

        // Tooltips on table header cells
        $this->assertStringContainsString('title="Completion percentage of the content."', $html);
        $this->assertStringContainsString('title="Actual tracked learning time stored in seconds."', $html);
        $this->assertStringContainsString('title="Official duration of the content."', $html);
        $this->assertStringContainsString('title="Learning hours calculated from actual tracked time. Historical completed records may use content duration as fallback."', $html);
        $this->assertStringContainsString('title="Reference/master learning-hour value defined for the content. It is not the learner\'s actual learning time."', $html);
        $this->assertStringContainsString('title="Shows whether the learning hour comes from actual tracking, historical fallback, or zero/unfinished state."', $html);
        $this->assertStringContainsString('title="Current completion status of the playlist content record."', $html);
    }

    public function test_datatable_json_maintains_distinct_actual_and_master_learning_hour_fields(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/manage/learning-hours/playlist-content-user/datatable?draw=1&start=0&length=5');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertNotEmpty($data, 'Datatable should return records');

        foreach ($data as $row) {
            $this->assertArrayHasKey('actual_learning_hour', $row);
            $this->assertArrayHasKey('master_learning_hour', $row);
            $this->assertArrayHasKey('current_time_display', $row);
            $this->assertArrayHasKey('calculation_source', $row);
            $this->assertArrayHasKey('status_badge', $row);
            $this->assertArrayHasKey('duration', $row);
            $this->assertArrayHasKey('progress_bar', $row);

            $this->assertNotNull($row['actual_learning_hour']);
            $this->assertNotNull($row['calculation_source']);

            // Ensure actual learning hour contains 'h' suffix (e.g. 0.0750 h or -)
            $this->assertMatchesRegularExpression('/(h|Not Calculated|-)/', $row['actual_learning_hour']);
        }
    }

    public function test_light_mode_and_dark_mode_theme_contrast_styling_is_rendered(): void
    {
        // 1. Light Mode
        $resLight = $this->actingAs($this->admin)->get('/manage/learning-hours/playlist-content-user?theme=light');
        $resLight->assertStatus(200);
        $htmlLight = $resLight->getContent();

        $this->assertStringContainsString('data-bs-theme="light"', $htmlLight);
        $this->assertStringContainsString('info-guide-card', $htmlLight);
        $this->assertStringContainsString('info-guide-title', $htmlLight);
        $this->assertStringContainsString('info-guide-item', $htmlLight);
        $this->assertStringContainsString('info-guide-item-title', $htmlLight);
        $this->assertStringContainsString('info-guide-item-desc', $htmlLight);
        $this->assertStringContainsString('.info-guide-item-desc', $htmlLight);
        $this->assertStringContainsString('color: #475569', $htmlLight);

        // 2. Dark Mode
        $resDark = $this->actingAs($this->admin)->get('/manage/learning-hours/playlist-content-user?theme=dark');
        $resDark->assertStatus(200);
        $htmlDark = $resDark->getContent();

        $this->assertStringContainsString('data-bs-theme="dark"', $htmlDark);
        $this->assertStringContainsString('dark-mode', $htmlDark);
        $this->assertStringContainsString('[data-bs-theme="dark"] .manage-learning-hours-page .info-guide-card', $htmlDark);
        $this->assertStringContainsString('[data-bs-theme="dark"] .manage-learning-hours-page .info-guide-item-desc', $htmlDark);
    }
}
