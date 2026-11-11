<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OfflineTableActionAlignmentTest extends TestCase
{
    use DatabaseTransactions;

    protected function authenticateAdmin()
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);
        return $user;
    }

    /**
     * Test participant blade table structure, column counts, and action column configuration.
     */
    public function test_offline_participant_table_structure_and_action_alignment()
    {
        $bladePath = resource_path('views/main/record/offline/participant.blade.php');
        $this->assertFileExists($bladePath);
        $content = file_get_contents($bladePath);

        // 1. Column count matching
        preg_match('/<thead>(.*?)<\/thead>/s', $content, $mHeader);
        $this->assertNotEmpty($mHeader);
        preg_match_all('/<th\b[^>]*>(.*?)<\/th>/si', $mHeader[1], $ths);
        $this->assertCount(33, $ths[0], 'Participant table must have exactly 33 TH columns');

        preg_match('/columns:\s*\[(.*?)\]\s*,\s*language:/s', $content, $mJs);
        $this->assertNotEmpty($mJs);
        preg_match_all('/\{\s*data:\s*[\'"][^\'"]+[\'"]/s', $mJs[1], $jsCols);
        $this->assertCount(33, $jsCols[0], 'Participant table must have exactly 33 JS columns');

        // 2. Action header has action-column class and 80px width
        $this->assertMatchesRegularExpression(
            '/<th\s+class="text-center\s+action-column"\s+style="min-width:\s*80px;\s*width:\s*80px;">ACTION<\/th>/i',
            $content,
            'Participant ACTION header must have action-column class and 80px width'
        );

        // 3. scrollX is false to avoid split table desynchronization
        $this->assertStringContainsString('scrollX: false,', $content);

        // 4. columnDefs targets last column (-1) with action-column class and 80px width
        $this->assertStringContainsString("targets: -1,", $content);
        $this->assertStringContainsString("className: 'text-center action-column align-middle',", $content);

        // 5. Action column in JS columns array
        $this->assertStringContainsString("data: 'action'", $content);

        // 6. CSS rules for action column, dropdown centering, and empty state
        $this->assertStringContainsString('#participant-training-table th.action-column,', $content);
        $this->assertStringContainsString('#participant-training-table td.action-column', $content);
        $this->assertStringContainsString('#participant-training-table td.action-column .dropdown', $content);
        $this->assertStringContainsString('#participant-training-table td.dataTables_empty', $content);
    }

    /**
     * Test course blade table structure, column counts, and action column configuration.
     */
    public function test_offline_course_table_structure_and_action_alignment()
    {
        $bladePath = resource_path('views/main/record/offline/course.blade.php');
        $this->assertFileExists($bladePath);
        $content = file_get_contents($bladePath);

        // 1. Column count matching
        preg_match('/<thead>(.*?)<\/thead>/s', $content, $mHeader);
        $this->assertNotEmpty($mHeader);
        preg_match_all('/<th\b[^>]*>(.*?)<\/th>/si', $mHeader[1], $ths);
        $this->assertCount(33, $ths[0], 'Course table must have exactly 33 TH columns');

        preg_match('/columns:\s*\[(.*?)\]\s*,\s*language:/s', $content, $mJs);
        $this->assertNotEmpty($mJs);
        preg_match_all('/\{\s*data:\s*[\'"][^\'"]+[\'"]/s', $mJs[1], $jsCols);
        $this->assertCount(33, $jsCols[0], 'Course table must have exactly 33 JS columns');

        // 2. Action header has action-column class and 80px width
        $this->assertMatchesRegularExpression(
            '/<th\s+class="text-center\s+action-column"\s+style="min-width:\s*80px;\s*width:\s*80px;">ACTION<\/th>/i',
            $content,
            'Course ACTION header must have action-column class and 80px width'
        );

        // 3. scrollX is false to avoid split table desynchronization
        $this->assertStringContainsString('scrollX: false,', $content);

        // 4. columnDefs targets last column (-1) with action-column class and 80px width
        $this->assertStringContainsString("targets: -1,", $content);
        $this->assertStringContainsString("className: 'text-center action-column align-middle',", $content);

        // 5. CSS rules for action column, dropdown centering, and empty state
        $this->assertStringContainsString('#course-participants-table th.action-column,', $content);
        $this->assertStringContainsString('#course-participants-table td.action-column', $content);
        $this->assertStringContainsString('#course-participants-table td.action-column .dropdown', $content);
        $this->assertStringContainsString('#course-participants-table td.dataTables_empty', $content);
    }

    /**
     * Test participant datatable endpoint returns proper action markup.
     */
    public function test_participant_datatable_returns_action_dropdown()
    {
        $this->authenticateAdmin();

        $response = $this->postJson(route('manage.record.offline.participant.datatable', ['identifier' => 1004]), [
            'classification' => 'job-functional',
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]);

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertArrayHasKey('data', $json);

        if (!empty($json['data'])) {
            $first = $json['data'][0];
            $this->assertArrayHasKey('action', $first);
            $this->assertStringContainsString('dropdown', $first['action']);
            $this->assertStringContainsString('ti-dots-vertical', $first['action']);
        }
    }

    /**
     * Test course datatable endpoint returns proper action markup.
     */
    public function test_course_datatable_returns_action_dropdown()
    {
        $this->authenticateAdmin();

        $response = $this->postJson(route('manage.record.offline.course.datatable', ['training' => 20]), [
            'classification' => 'job-functional',
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]);

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertArrayHasKey('data', $json);

        if (!empty($json['data'])) {
            $first = $json['data'][0];
            $this->assertArrayHasKey('action', $first);
            $this->assertStringContainsString('dropdown', $first['action']);
            $this->assertStringContainsString('ti-dots-vertical', $first['action']);
        }
    }
}
