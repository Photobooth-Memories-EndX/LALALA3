<?php

namespace Tests\Unit;

use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Services\Learning\LearningRecordRecognizedDurationService;
use App\Support\DurationFormatter;
use Tests\TestCase;

class LearningRecordRecognizedDurationServiceTest extends TestCase
{
    protected LearningRecordRecognizedDurationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LearningRecordRecognizedDurationService();
    }

    public function test_it_formats_duration_exceeding_24_hours_accurately(): void
    {
        // 0 seconds -> 00:00:00
        $this->assertEquals('00:00:00', $this->service->formatSeconds(0));

        // 6 seconds -> 00:00:06
        $this->assertEquals('00:00:06', $this->service->formatSeconds(6));

        // 1 hour, 2 minutes, 3 seconds = 3723 seconds -> 01:02:03
        $this->assertEquals('01:02:03', $this->service->formatSeconds(3723));

        // 8 hours = 28800 seconds -> 08:00:00
        $this->assertEquals('08:00:00', $this->service->formatSeconds(28800));

        // 16 hours = 57600 seconds -> 16:00:00
        $this->assertEquals('16:00:00', $this->service->formatSeconds(57600));

        // 27 hours, 15 minutes = 98100 seconds -> 27:15:00 (Must NOT wrap to 03:15:00!)
        $this->assertEquals('27:15:00', $this->service->formatSeconds(98100));
    }

    public function test_content_without_duration_yields_zero(): void
    {
        $cu = new ContentUser([
            'current_time' => 100,
            'actual_watch_seconds' => 100,
            'finish' => '1',
            'progress' => 100,
        ]);
        $cu->setRelation('content', null);

        $sec = $this->service->resolveForContentUser($cu);
        $this->assertEquals(0, $sec);
        $this->assertEquals('00:00:00', $this->service->formatSeconds($sec));
    }

    public function test_single_watch_does_not_exceed_content_duration(): void
    {
        $content = new Content([
            'duration' => '01:00:00', // 3600 seconds
            'learning_hours' => 1.0,
        ]);

        $cu = new ContentUser([
            'actual_watch_seconds' => 1800, // half watched
            'finish' => '0',
            'progress' => 50,
        ]);
        $cu->setRelation('content', $content);

        // First watch generates 0 recognized rewatch seconds
        $sec = $this->service->resolveForContentUser($cu);
        $this->assertEquals(0, $sec);
        $this->assertEquals('00:00:00', $this->service->formatSeconds($sec));
    }

    public function test_rewatched_content_can_reach_two_times_duration_max(): void
    {
        $content = new Content([
            'duration' => '00:45:00', // 2700 seconds
            'learning_hours' => 0.75,
        ]);

        // Watched 1.5 times = 4050 seconds -> rewatch = 4050 - 2700 = 1350s (00:22:30)
        $cu = new ContentUser([
            'actual_watch_seconds' => 4050,
            'finish' => '1',
            'progress' => 100,
        ]);
        $cu->setRelation('content', $content);

        $sec = $this->service->resolveForContentUser($cu);
        $this->assertEquals(1350, $sec);
        $this->assertEquals('00:22:30', $this->service->formatSeconds($sec));

        // Watched 2.0 times = 5400 seconds -> max rewatch = 2700s (00:45:00)
        $cu->actual_watch_seconds = 5400;
        $sec = $this->service->resolveForContentUser($cu);
        $this->assertEquals(2700, $sec);
        $this->assertEquals('00:45:00', $this->service->formatSeconds($sec));
    }

    public function test_content_never_exceeds_two_times_cap(): void
    {
        $content = new Content([
            'duration' => '01:30:00', // 5400 seconds, max rewatch cap = 5400s (01:30:00)
            'learning_hours' => 1.5,
        ]);

        // Watched 3 times = 16200 seconds -> capped at 1x duration rewatch = 5400s
        $cu = new ContentUser([
            'actual_watch_seconds' => 16200,
            'finish' => '1',
            'progress' => 100,
        ]);
        $cu->setRelation('content', $content);

        $sec = $this->service->resolveForContentUser($cu);
        $this->assertEquals(5400, $sec);
        $this->assertEquals('01:30:00', $this->service->formatSeconds($sec));
    }

    public function test_resolve_from_row_handles_caps_correctly(): void
    {
        $row = (object) [
            'duration' => '00:09:30', // 570s, max rewatch = 570s
            'actual_watch_seconds' => 2000,
            'current_time' => 570,
            'finish' => '1',
            'progress' => 100,
            'learning_hours' => 0.1583,
        ];

        $sec = $this->service->resolveFromRow($row);
        $this->assertEquals(570, $sec);
        $this->assertEquals('00:09:30', $this->service->formatSeconds($sec));
    }

    public function test_resolve_from_row_returns_zero_for_offline_records(): void
    {
        $row = (object) [
            'source' => 'offline',
            'duration' => '02:00:00',
            'learning_hours' => 2.0,
        ];

        $sec = $this->service->resolveFromRow($row);
        $this->assertEquals(0, $sec);
    }
}
