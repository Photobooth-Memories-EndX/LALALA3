<?php

namespace Tests\Unit;

use App\Support\DurationFormatter;
use PHPUnit\Framework\TestCase;

class DurationFormatterTest extends TestCase
{
    public function test_it_normalizes_legacy_minute_second_values(): void
    {
        $this->assertSame('00:06:26', DurationFormatter::normalize('06:26'));
        $this->assertSame('00:30:00', DurationFormatter::normalize('30:00'));
        $this->assertSame('01:00:00', DurationFormatter::normalize('60:00'));
        $this->assertSame('01:30:15', DurationFormatter::normalize('90:15'));
    }

    public function test_it_keeps_hms_values_unchanged(): void
    {
        $this->assertSame('01:15:20', DurationFormatter::normalize('01:15:20'));
    }

    public function test_it_formats_learning_record_duration(): void
    {
        $this->assertSame('6 min', DurationFormatter::formatForLearningRecord('00:06:26'));
        $this->assertSame('30 min', DurationFormatter::formatForLearningRecord('00:30:00'));
        $this->assertSame('1 hour', DurationFormatter::formatForLearningRecord('01:00:00'));
        $this->assertSame('2 hours', DurationFormatter::formatForLearningRecord('02:00:00'));
        $this->assertSame('1 hour 15 min', DurationFormatter::formatForLearningRecord('01:15:00'));
        $this->assertSame('2 hours 45 min', DurationFormatter::formatForLearningRecord('02:45:00'));
    }

    public function test_it_formats_aggregated_seconds_as_hms(): void
    {
        $this->assertSame('18:42:30', DurationFormatter::fromSeconds(67350));
        $this->assertSame('00:00:00', DurationFormatter::fromSeconds(-1));
    }
}
