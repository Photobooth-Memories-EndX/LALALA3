<?php

namespace App\Services\LearningHours;

class LearningHourConverterService
{
    public function __construct(
        protected ?DurationParserService $parser = null,
        protected ?ActualLearningHourCalculator $calculator = null
    ) {
        $this->parser ??= new DurationParserService();
        $this->calculator ??= new ActualLearningHourCalculator();
    }

    /**
     * Convert HH:MM:SS / MM:SS string or numeric seconds to actual learning hours float.
     * Formula: round(total_seconds / 3600, 4) via ActualLearningHourCalculator.
     */
    public function convert(string|int|float|null $duration): float
    {
        if ($duration === null) {
            return 0.0;
        }

        if (is_int($duration) || is_float($duration)) {
            return $this->convertFromSeconds((int) $duration);
        }

        $durationStr = trim((string) $duration);

        if ($durationStr === '') {
            return 0.0;
        }

        if (is_numeric($durationStr) && strpos($durationStr, ':') === false) {
            return $this->convertFromSeconds((int) $durationStr);
        }

        $seconds = $this->parser->toSeconds($durationStr);

        return $this->convertFromSeconds($seconds);
    }

    /**
     * Convert duration in total seconds to actual learning hours float.
     * Delegates to ActualLearningHourCalculator (round(seconds / 3600, 4)).
     */
    public function convertFromSeconds(?int $seconds): float
    {
        $seconds = max(0, (int) $seconds);

        if ($seconds === 0) {
            return 0.0;
        }

        return $this->calculator->calculate($seconds) ?? 0.0;
    }
}
