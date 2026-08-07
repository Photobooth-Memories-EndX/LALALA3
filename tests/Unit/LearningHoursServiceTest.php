<?php

namespace Tests\Unit;

use App\Services\LearningHours\DurationParserService;
use App\Services\LearningHours\LearningHourConverterService;
use PHPUnit\Framework\TestCase;

class LearningHoursServiceTest extends TestCase
{
    /**
     * @dataProvider durationProvider
     */
    public function test_converter_data_provider(string $duration, float $expectedHours): void
    {
        $converter = new LearningHourConverterService();
        $this->assertSame($expectedHours, $converter->convert($duration));
    }

    public function durationProvider(): array
    {
        return [
            'zero duration' => ['00:00:00', 0.0],
            '29m 21s'       => ['00:29:21', 0.5],
            '31m 59s'       => ['00:31:59', 1.0],
            '1h 1m 21s'     => ['01:01:21', 1.5],
            '2h 31m'        => ['02:31:00', 3.0],
            '5h 59m 59s'    => ['05:59:59', 6.0],
            '8h 0m 0s'      => ['08:00:00', 8.0],
        ];
    }

    public function test_converter_handles_seconds_input(): void
    {
        $converter = new LearningHourConverterService();

        $this->assertSame(0.0, $converter->convertFromSeconds(0));
        $this->assertSame(0.5, $converter->convertFromSeconds(1761)); // 29m 21s
        $this->assertSame(1.0, $converter->convertFromSeconds(1919)); // 31m 59s
        $this->assertSame(1.5, $converter->convertFromSeconds(3681)); // 1h 1m 21s
    }

    public function test_duration_parser_service(): void
    {
        $parser = new DurationParserService();

        $this->assertSame(0, $parser->toSeconds('00:00:00'));
        $this->assertSame(1761, $parser->toSeconds('00:29:21'));
        $this->assertSame(3681, $parser->toSeconds('01:01:21'));
        $this->assertSame('01:01:21', $parser->fromSeconds(3681));
        $this->assertSame('01:01:21', $parser->normalize('01:01:21'));
    }
}
