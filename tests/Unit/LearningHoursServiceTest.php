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
            '20m 0s'        => ['00:20:00', 0.3333],
            '29m 21s'       => ['00:29:21', 0.4892],
            '30m 0s'        => ['00:30:00', 0.5000],
            '31m 59s'       => ['00:31:59', 0.5331],
            '40m 0s'        => ['00:40:00', 0.6667],
            '50m 0s'        => ['00:50:00', 0.8333],
            '1h 0m 0s'      => ['01:00:00', 1.0000],
            '1h 1m 21s'     => ['01:01:21', 1.0225],
            '1h 6m 26s'     => ['01:06:26', 1.1072],
            '1h 30m 0s'     => ['01:30:00', 1.5000],
            '2h 0m 0s'      => ['02:00:00', 2.0000],
            '2h 31m'        => ['02:31:00', 2.5167],
            '5h 59m 59s'    => ['05:59:59', 5.9997],
            '8h 0m 0s'      => ['08:00:00', 8.0000],
        ];
    }

    public function test_converter_handles_seconds_input(): void
    {
        $converter = new LearningHourConverterService();

        $this->assertSame(0.0, $converter->convertFromSeconds(0));
        $this->assertSame(0.3333, $converter->convertFromSeconds(1200)); // 20m 0s
        $this->assertSame(0.4892, $converter->convertFromSeconds(1761)); // 29m 21s
        $this->assertSame(0.5, $converter->convertFromSeconds(1800));    // 30m 0s
        $this->assertSame(0.5331, $converter->convertFromSeconds(1919)); // 31m 59s
        $this->assertSame(0.8333, $converter->convertFromSeconds(3000)); // 50m 0s
        $this->assertSame(1.0, $converter->convertFromSeconds(3600));    // 1h 0m 0s
        $this->assertSame(1.0225, $converter->convertFromSeconds(3681)); // 1h 1m 21s
        $this->assertSame(1.1072, $converter->convertFromSeconds(3986)); // 1h 6m 26s
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
