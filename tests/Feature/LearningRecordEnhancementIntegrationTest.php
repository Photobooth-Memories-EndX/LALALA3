<?php

namespace Tests\Feature;

use App\Exports\AllRecordExport;
use App\Exports\Manage\ClassificationAllRecordExport;
use App\Exports\Manage\ClassificationOfflineRecordExport;
use App\Exports\Manage\ClassificationOnlineRecordExport;
use App\Exports\OfflineRecordExport;
use App\Exports\OnlineRecordExport;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\User;
use App\Services\Learning\LearningDatatableService;
use App\Services\Learning\LearningExportService;
use App\Services\Learning\LearningRecordRecognizedDurationService;
use App\Services\Record\RecordAllCombinedService;
use App\Support\DurationFormatter;
use Tests\TestCase;

class LearningRecordEnhancementIntegrationTest extends TestCase
{
    /** @test */
    public function export_headings_adhere_strictly_to_recognized_duration_boundaries()
    {
        // 1. Online exports MUST have Total Recognized Duration or Recognized Duration
        $onlineHeaders = OnlineRecordExport::headers();
        $courseDurIdx = array_search('Course Duration', $onlineHeaders);
        $this->assertNotFalse($courseDurIdx);
        $this->assertContains($onlineHeaders[$courseDurIdx + 1], ['Total Recognized Duration', 'Recognized Duration']);

        $classificationOnline = (new ClassificationOnlineRecordExport())->headings();
        $classCourseDurIdx = array_search('Course Duration', $classificationOnline);
        $this->assertNotFalse($classCourseDurIdx);
        $this->assertContains($classificationOnline[$classCourseDurIdx + 1], ['Total Recognized Duration', 'Recognized Duration']);

        // 2. LearningExportService MUST have Total Recognized Duration between Course Duration and Learning Hours
        $learningHeadings = LearningExportService::headings();
        $durIdx = array_search('Course Duration', $learningHeadings);
        $this->assertNotFalse($durIdx);
        $this->assertEquals('Total Recognized Duration', $learningHeadings[$durIdx + 1]);
        $this->assertEquals('Learning Hours', $learningHeadings[$durIdx + 2]);

        // 3. All Record MUST have Total Recognized Duration
        $allHeaders = AllRecordExport::headings();
        $this->assertContains('Total Recognized Duration', $allHeaders);

        // 4. Non KP OWL (Offline) export MUST NOT have Total Recognized Duration or Source, with canonical 50 columns
        $offlineHeaders = OfflineRecordExport::headers();
        $this->assertNotContains('Total Recognized Duration', $offlineHeaders);
        $this->assertNotContains('Source', $offlineHeaders);
        $this->assertCount(50, $offlineHeaders);
    }

    /** @test */
    public function recognized_duration_service_correctly_caps_and_formats()
    {
        $service = app(LearningRecordRecognizedDurationService::class);

        // 1000s duration, actual watch 2500s -> capped at 2000s -> 00:33:20
        $row = (object) [
            'duration_seconds' => 1000,
            'actual_watch_seconds' => 2500,
            'current_time' => 1000,
            'finish' => '1',
            'progress' => 100,
            'source' => 'online',
        ];
        $sec = $service->resolveFromRow($row);
        $this->assertEquals(2000, $sec);
        $this->assertEquals('00:33:20', $service->formatSeconds($sec));

        // 27 hours 15 mins (exceeding 24h wrap test)
        $this->assertEquals('27:15:00', $service->formatSeconds(98100));

        // Offline record must return 0
        $offlineRow = (object) [
            'duration_seconds' => 1000,
            'actual_watch_seconds' => 1000,
            'source' => 'offline',
        ];
        $this->assertEquals(0, $service->resolveFromRow($offlineRow));
        $this->assertEquals('00:00:00', $service->formatSeconds(0));
    }

    /** @test */
    public function combined_query_selects_grade_and_job_position_for_online_and_offline()
    {
        $combinedService = app(RecordAllCombinedService::class);
        $query = $combinedService->buildCombinedQuery([]);

        $sql = $query->toSql();
        $this->assertStringContainsString('u.jobgrade', $sql);
        $this->assertStringContainsString('u.jobposition', $sql);
    }

    /** @test */
    public function content_record_detail_modal_endpoint_provides_all_four_enhanced_metrics()
    {
        $admin = User::whereIn('role', [2, 3])->first() ?? User::first();
        if (!$admin) {
            $this->markTestSkipped('No user found in database');
        }

        $this->actingAs($admin);

        // Find an existing ContentUser
        $cu = ContentUser::with(['user', 'content'])->whereNotNull('afk_duration_seconds')->first()
            ?? ContentUser::with(['user', 'content'])->first();

        if (!$cu) {
            $this->markTestSkipped('No ContentUser found in database');
        }

        $user = $cu->user;
        $expectedGrade = $user?->jobgrade ?: '-';
        $expectedJobPos = $user?->jobposition ?: '-';

        $response = $this->getJson(route('learning.manage.record-detail', [
            'type' => 'content',
            'id' => $cu->id,
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');

        // Fitur A: Grade & Job Position
        $this->assertEquals($expectedGrade, $data['employee']['grade']);
        $this->assertEquals($expectedJobPos, $data['employee']['job_position']);

        // Fitur B: Total Engagement Time formatted HH:MM:SS
        $this->assertMatchesRegularExpression('/^\d{2,}:\d{2}:\d{2}$/', $data['progress']['total_engagement_time']);

        // Fitur C: Total Recognized Duration formatted HH:MM:SS
        $this->assertMatchesRegularExpression('/^\d{2,}:\d{2}:\d{2}$/', $data['progress']['total_recognized_duration']);
        $this->assertMatchesRegularExpression('/^\d{2,}:\d{2}:\d{2}$/', $data['progress']['recognized_duration']);
    }

    /** @test */
    public function datatable_endpoint_provides_recognized_duration_and_employee_fields()
    {
        $admin = User::whereIn('role', [2, 3])->first() ?? User::first();
        if (!$admin) {
            $this->markTestSkipped('No user found in database');
        }

        $this->actingAs($admin);

        $response = $this->getJson(route('learning.manage.datatable', [
            'draw' => 1,
            'start' => 0,
            'length' => 5,
        ]));

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertArrayHasKey('data', $json);

        if (!empty($json['data'])) {
            $row = $json['data'][0];
            $this->assertArrayHasKey('recognized_duration', $row);
            $this->assertArrayHasKey('total_recognized_duration', $row);
            $this->assertArrayHasKey('grade', $row);
            $this->assertArrayHasKey('job_position', $row);
        }
    }
}
