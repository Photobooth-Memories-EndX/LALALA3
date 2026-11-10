<?php

namespace Tests\Feature;

use App\Http\Controllers\Manage\Record\RecordOfflineController;
use App\Models\OfflineTraining;
use App\Models\User;
use Tests\TestCase;

class OfflineLearningRecordPaginationAndEngagementTimeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::whereIn('role', [2, 3])->first() ?? User::first();
        if ($admin) {
            $this->actingAs($admin);
        }
    }

    /** @test */
    public function format_engagement_time_helper_converts_correctly()
    {
        // Null / empty / dash
        $this->assertSame(
            '<span class="fw-semibold font-monospace">00:00:00</span>',
            RecordOfflineController::formatEngagementTime(null)
        );
        $this->assertSame(
            '<span class="fw-semibold font-monospace">00:00:00</span>',
            RecordOfflineController::formatEngagementTime('')
        );
        $this->assertSame(
            '<span class="fw-semibold font-monospace">00:00:00</span>',
            RecordOfflineController::formatEngagementTime('-')
        );

        // Zero hours
        $this->assertSame(
            '<span class="fw-semibold font-monospace">00:00:00</span>',
            RecordOfflineController::formatEngagementTime(0)
        );
        $this->assertSame(
            '<span class="fw-semibold font-monospace">00:00:00</span>',
            RecordOfflineController::formatEngagementTime('0 h')
        );

        // 0.5 hours = 30 minutes
        $this->assertSame(
            '<span class="fw-semibold font-monospace">00:30:00</span>',
            RecordOfflineController::formatEngagementTime(0.5)
        );
        $this->assertSame(
            '<span class="fw-semibold font-monospace">00:30:00</span>',
            RecordOfflineController::formatEngagementTime('0.5 h')
        );

        // 1 hour
        $this->assertSame(
            '<span class="fw-semibold font-monospace">01:00:00</span>',
            RecordOfflineController::formatEngagementTime(1)
        );

        // 2 hours
        $this->assertSame(
            '<span class="fw-semibold font-monospace">02:00:00</span>',
            RecordOfflineController::formatEngagementTime(2)
        );
        $this->assertSame(
            '<span class="fw-semibold font-monospace">02:00:00</span>',
            RecordOfflineController::formatEngagementTime('2 h')
        );

        // 4 hours
        $this->assertSame(
            '<span class="fw-semibold font-monospace">04:00:00</span>',
            RecordOfflineController::formatEngagementTime(4)
        );
        $this->assertSame(
            '<span class="fw-semibold font-monospace">04:00:00</span>',
            RecordOfflineController::formatEngagementTime('4 h')
        );

        // 8 hours
        $this->assertSame(
            '<span class="fw-semibold font-monospace">08:00:00</span>',
            RecordOfflineController::formatEngagementTime(8)
        );
        $this->assertSame(
            '<span class="fw-semibold font-monospace">08:00:00</span>',
            RecordOfflineController::formatEngagementTime('8 h')
        );

        // 25.5 hours = 25:30:00
        $this->assertSame(
            '<span class="fw-semibold font-monospace">25:30:00</span>',
            RecordOfflineController::formatEngagementTime(25.5)
        );
        $this->assertSame(
            '<span class="fw-semibold font-monospace">25:30:00</span>',
            RecordOfflineController::formatEngagementTime('25.5 h')
        );
    }

    /** @test */
    public function classification_page_loads_successfully()
    {
        $response = $this->get('/manage/record/offline/classification/basic-common-duties');
        $response->assertStatus(200);
        $response->assertSee('TOTAL ENGAGEMENT TIME (LH)');
        $response->assertSee('offline-table-pagination-container');
    }

    /** @test */
    public function participant_page_loads_successfully()
    {
        $response = $this->get('/manage/record/offline/participant/756?classification=basic-common-duties');
        $response->assertStatus(200);
        $response->assertSee('TOTAL ENGAGEMENT TIME (LH)');
        $response->assertSee('participant-table-pagination-container');
    }

    /** @test */
    public function course_page_loads_successfully()
    {
        $response = $this->get('/manage/record/offline/course/3047?classification=basic-common-duties');
        $response->assertStatus(200);
        $response->assertSee('TOTAL ENGAGEMENT TIME (LH)');
        $response->assertSee('course-table-pagination-container');
    }

    /** @test */
    public function classification_datatable_returns_total_engagement_time_in_hh_mm_ss_format()
    {
        $response = $this->getJson('/manage/record/offline/classification/basic-common-duties/datatable?draw=1&start=0&length=10');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertIsArray($data);
        if (count($data) > 0) {
            foreach ($data as $row) {
                $this->assertArrayHasKey('total_engagement_time', $row);
                $timeHtml = $row['total_engagement_time'];
                // Should match HH:MM:SS format (e.g. 00:00:00, 04:00:00, etc.)
                $this->assertMatchesRegularExpression('/(\d{2,}|\d{2}):\d{2}:\d{2}/', $timeHtml);
                // Should NOT contain raw "h" without HMS format
                $this->assertDoesNotMatchRegularExpression('/^\d+(\.\d+)?\s*h$/', strip_tags($timeHtml));
            }
        }
    }

    /** @test */
    public function participant_datatable_returns_total_engagement_time_in_hh_mm_ss_format()
    {
        $response = $this->getJson('/manage/record/offline/participant/756/datatable?draw=1&start=0&length=10');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertIsArray($data);
        if (count($data) > 0) {
            foreach ($data as $row) {
                $this->assertArrayHasKey('total_engagement_time', $row);
                $timeHtml = $row['total_engagement_time'];
                $this->assertMatchesRegularExpression('/(\d{2,}|\d{2}):\d{2}:\d{2}/', $timeHtml);
                $this->assertDoesNotMatchRegularExpression('/^\d+(\.\d+)?\s*h$/', strip_tags($timeHtml));
            }
        }
    }

    /** @test */
    public function course_datatable_returns_total_engagement_time_in_hh_mm_ss_format()
    {
        $response = $this->getJson('/manage/record/offline/course/3047/datatable?draw=1&start=0&length=10');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertIsArray($data);
        if (count($data) > 0) {
            foreach ($data as $row) {
                $this->assertArrayHasKey('total_engagement_time', $row);
                $timeHtml = $row['total_engagement_time'];
                $this->assertMatchesRegularExpression('/(\d{2,}|\d{2}):\d{2}:\d{2}/', $timeHtml);
                $this->assertDoesNotMatchRegularExpression('/^\d+(\.\d+)?\s*h$/', strip_tags($timeHtml));
            }
        }
    }

    /** @test */
    public function blade_views_contain_sliding_window_pagination_and_css_styling()
    {
        $views = [
            'classification' => resource_path('views/main/record/offline/classification.blade.php'),
            'participant'    => resource_path('views/main/record/offline/participant.blade.php'),
            'course'         => resource_path('views/main/record/offline/course.blade.php'),
        ];

        foreach ($views as $name => $path) {
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            // Verify sliding window logic
            $this->assertStringContainsString('startPage = Math.max(0, currentPage - 2)', $content, "{$name} missing sliding window startPage");
            $this->assertStringContainsString('endPage = Math.min(totalPages - 1, currentPage + 2)', $content, "{$name} missing sliding window endPage");
            $this->assertStringContainsString('…', $content, "{$name} missing ellipsis for pagination");

            // Verify pagination CSS
            $this->assertStringContainsString('display: flex;', $content, "{$name} missing flex styling");
            $this->assertStringContainsString('justify-content: flex-end;', $content, "{$name} missing flex-end styling");
            $this->assertStringContainsString('gap: 4px;', $content, "{$name} missing gap styling");

            // Verify formatEngagementTime JS helper
            $this->assertStringContainsString('function formatEngagementTime(val)', $content, "{$name} missing formatEngagementTime helper");
        }
    }
}
