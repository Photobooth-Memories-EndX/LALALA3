<?php

namespace Tests\Unit;

use App\Models\Content;
use App\Models\ContentUser;
use App\Models\User;
use App\Services\LearningHours\ContentUserLearningHourManagementService;
use App\Services\LearningHours\DurationParserService;
use App\Services\LearningHours\LearningHourConverterService;
use Tests\TestCase;

class ContentUserLearningHourManagementServiceTest extends TestCase
{
    protected ContentUserLearningHourManagementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ContentUserLearningHourManagementService(
            new LearningHourConverterService(new DurationParserService()),
            new DurationParserService()
        );
    }

    /**
     * @dataProvider learningHourBucketDataProvider
     */
    public function test_it_calculates_canonical_learning_hours_correctly(string $duration, float $expectedHours): void
    {
        $calculated = $this->service->calculateFromDuration($duration);
        $this->assertEquals($expectedHours, $calculated, "Failed calculating hours for duration {$duration}");
    }

    public function learningHourBucketDataProvider(): array
    {
        return [
            '00:20:00' => ['00:20:00', 0.5],
            '00:29:21' => ['00:29:21', 0.5],
            '00:30:00' => ['00:30:00', 0.5],
            '00:31:59' => ['00:31:59', 1.0],
            '00:50:00' => ['00:50:00', 1.0],
            '00:59:59' => ['00:59:59', 1.0],
            '01:00:00' => ['01:00:00', 1.0],
            '01:00:01' => ['01:00:01', 1.5],
            '01:01:21' => ['01:01:21', 1.5],
            '01:06:26' => ['01:06:26', 1.5],
            '02:00:00' => ['02:00:00', 2.0],
        ];
    }

    public function test_it_formats_hours_correctly(): void
    {
        $this->assertEquals('0.5 hour', $this->service->formatHours(0.5));
        $this->assertEquals('1 hour', $this->service->formatHours(1.0));
        $this->assertEquals('1.5 hours', $this->service->formatHours(1.5));
        $this->assertEquals('2 hours', $this->service->formatHours(2.0));
        $this->assertEquals('-', $this->service->formatHours(0.0));
        $this->assertEquals('-', $this->service->formatHours(null));
    }
}
