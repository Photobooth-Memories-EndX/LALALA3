<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\User;
use App\Services\Learning\LearningDatatableService;
use App\Support\DurationFormatter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Tests\TestCase;

class LearningRecordsRawHtmlAndEngagementTimeTest extends TestCase
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
    public function category_learning_records_page_loads_successfully()
    {
        $response = $this->get('/manage/learning/category/innovation');
        $response->assertStatus(200);
        $response->assertSee('PROVIDER');
        $response->assertSee('DATE (START - END)');
        $response->assertSee('TIME (START - END)');
        $response->assertSee('CERTIFICATE AVAILABILITY');
        $response->assertSee('TOTAL ENGAGEMENT TIME');
        $response->assertDontSee('&lt;span class=');
    }

    /** @test */
    public function content_learning_records_page_loads_successfully()
    {
        $response = $this->get('/manage/learning/content/5s-whiteboard-animation');
        $response->assertStatus(200);
        $response->assertSee('PROVIDER');
        $response->assertSee('DATE (START - END)');
        $response->assertSee('TIME (START - END)');
        $response->assertSee('CERTIFICATE AVAILABILITY');
        $response->assertSee('TOTAL ENGAGEMENT TIME');
        $response->assertDontSee('&lt;span class=');
    }

    /** @test */
    public function datatable_payload_renders_visual_html_and_does_not_escape_markup()
    {
        $service = app(LearningDatatableService::class);
        $request = Request::create('/manage/learning/datatable', 'GET', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'entries' => 10,
        ]);
        app()->instance('request', $request);

        $response = $service->make($request);
        $json = json_decode($response->getContent(), true);

        $this->assertNotEmpty($json['data'], 'DataTables returned empty records');

        foreach ($json['data'] as $row) {
            // 1. Provider must be visual badge, not escaped HTML
            $this->assertStringContainsString('<span class="badge', $row['provider']);
            $this->assertStringNotContainsString('&lt;span', $row['provider']);

            // 2. Sub provider must not be escaped
            $this->assertStringNotContainsString('&lt;span', $row['sub_provider']);

            // 3. Date merged must be HTML div with Start and End
            if ($row['date_merged'] !== '<span class="text-muted">-</span>') {
                $this->assertStringContainsString('<div class="text-nowrap small">', $row['date_merged']);
                $this->assertStringContainsString('Start:', $row['date_merged']);
                $this->assertStringContainsString('End:', $row['date_merged']);
                $this->assertStringNotContainsString('&lt;div', $row['date_merged']);
            }

            // 4. Time merged must be HTML div with Start and End
            if ($row['time_merged'] !== '<span class="text-muted">-</span>') {
                $this->assertStringContainsString('<div class="text-nowrap small">', $row['time_merged']);
                $this->assertStringContainsString('Start:', $row['time_merged']);
                $this->assertStringContainsString('End:', $row['time_merged']);
                $this->assertStringNotContainsString('&lt;div', $row['time_merged']);
            }

            // 5. Certificate availability must not be escaped
            $this->assertStringNotContainsString('&lt;span', $row['certificate_availability']);

            // 6. Total engagement time must NOT end with ' h'
            $this->assertStringNotContainsString(' h<', $row['total_engagement_time']);
            $this->assertMatchesRegularExpression('/>\d{2,}:\d{2}:\d{2}</', $row['total_engagement_time']);
        }
    }

    /** @test */
    public function total_engagement_time_converts_decimal_hours_accurately()
    {
        $service = app(LearningDatatableService::class);

        // 0.08 hours = round(0.08 * 3600) = 288s => 00:04:48
        $row1 = (object) ['learning_hours' => '0.08'];
        $sec1 = $service->resolveEngagementSeconds($row1);
        $this->assertEquals(288, $sec1);
        $this->assertEquals('00:04:48', DurationFormatter::fromSeconds($sec1));

        // 0.5 hours = round(0.5 * 3600) = 1800s => 00:30:00
        $row2 = (object) ['learning_hours' => 0.5];
        $sec2 = $service->resolveEngagementSeconds($row2);
        $this->assertEquals(1800, $sec2);
        $this->assertEquals('00:30:00', DurationFormatter::fromSeconds($sec2));

        // 0.34 hours = round(0.34 * 3600) = 1224s => 00:20:24
        $row3 = (object) ['learning_hours' => '0.34'];
        $sec3 = $service->resolveEngagementSeconds($row3);
        $this->assertEquals(1224, $sec3);
        $this->assertEquals('00:20:24', DurationFormatter::fromSeconds($sec3));

        // 1.0 hours = 3600s => 01:00:00
        $row4 = (object) ['learning_hours' => 1.0];
        $sec4 = $service->resolveEngagementSeconds($row4);
        $this->assertEquals(3600, $sec4);
        $this->assertEquals('01:00:00', DurationFormatter::fromSeconds($sec4));

        // 25.5 hours = round(25.5 * 3600) = 91800s => 25:30:00 (> 24 hours support)
        $row5 = (object) ['learning_hours' => 25.5];
        $sec5 = $service->resolveEngagementSeconds($row5);
        $this->assertEquals(91800, $sec5);
        $this->assertEquals('25:30:00', DurationFormatter::fromSeconds($sec5));
    }

    /** @test */
    public function total_engagement_time_handles_zero_and_null_consistently()
    {
        $service = app(LearningDatatableService::class);

        // Zero hours
        $rowZero = (object) ['learning_hours' => 0];
        $secZero = $service->resolveEngagementSeconds($rowZero);
        $this->assertEquals(0, $secZero);
        $this->assertEquals('00:00:00', DurationFormatter::fromSeconds($secZero));

        // Null hours
        $rowNull = (object) ['learning_hours' => null];
        $secNull = $service->resolveEngagementSeconds($rowNull);
        $this->assertEquals(0, $secNull);
        $this->assertEquals('00:00:00', DurationFormatter::fromSeconds($secNull));
    }

    /** @test */
    public function authoritative_seconds_are_prioritized_without_reconversion()
    {
        $service = app(LearningDatatableService::class);

        // Record with raw actual_watch_seconds = 75 seconds (1 min 15 sec)
        $rowWatch = (object) [
            'actual_watch_seconds' => 75,
            'learning_hours' => '0.0208',
        ];
        $sec = $service->resolveEngagementSeconds($rowWatch);
        $this->assertEquals(75, $sec);
        $this->assertEquals('00:01:15', DurationFormatter::fromSeconds($sec));
    }

    /** @test */
    public function dynamic_user_input_is_safely_escaped_against_xss()
    {
        $service = app(LearningDatatableService::class);

        $maliciousRow = (object) [
            'source_id' => 99999,
            'source_type' => 'content',
            'user_id' => 99999,
            'user_id_emp' => '<script>alert("xss")</script>',
            'id_emp' => '<script>alert("xss")</script>',
            'employee_name' => 'John <img src=x onerror=alert(1)> Doe',
            'user_name' => 'John <img src=x onerror=alert(1)> Doe',
            'user_team' => '<script>xss()</script>',
            'user_department' => '<b>Dep</b>',
            'user_company' => '<iframe src=evil.com>',
            'content_title' => 'Title <script>alert(1)</script>',
            'content_duration' => '00:10:00',
            'classification_name' => 'Class <script>',
            'progress' => 50,
            'finish' => 0,
            'learning_hours' => 0.5,
            'created_at' => '2026-01-01 10:00:00',
            'completed_at' => null,
            'updated_at' => null,
            'status' => 'non mandatory',
            'category_source' => 'All Content',
        ];

        // Access private helper reflection if needed or verify rendered output
        $reflector = new \ReflectionClass($service);
        $employeeCol = $reflector->getMethod('employeeColumn');
        $employeeCol->setAccessible(true);
        $renderedEmployee = $employeeCol->invoke($service, $maliciousRow);

        $this->assertStringNotContainsString('<img src=x', $renderedEmployee);
        $this->assertStringContainsString('&lt;img src=x', $renderedEmployee);

        $titleCol = $reflector->getMethod('learningTitleColumn');
        $titleCol->setAccessible(true);
        $renderedTitle = $titleCol->invoke($service, $maliciousRow);

        $this->assertStringNotContainsString('<script>alert(1)</script>', $renderedTitle);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $renderedTitle);
    }
}
