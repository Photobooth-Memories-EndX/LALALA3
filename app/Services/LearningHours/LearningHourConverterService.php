<?php

namespace App\Services\LearningHours;

class LearningHourConverterService
{
    public const STEP = 0.5;
    public const BUCKET_SECONDS = 1800;

    public function __construct(
        protected ?DurationParserService $parser = null
    ) {
        $this->parser ??= new DurationParserService();
    }

    /**
     * Convert HH:MM:SS / MM:SS string or numeric seconds to learning hours float.
     * Output is always a multiple of step (0, 0.5, 1, 1.5, 2, 2.5, etc.).
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
     * Convert duration in total seconds to learning hours float.
     */
    public function convertFromSeconds(?int $seconds): float
    {
        $seconds = max(0, (int) $seconds);

        if ($seconds === 0) {
            return 0.0;
        }

        $step = self::STEP;
        $bucketSeconds = self::BUCKET_SECONDS;

        try {
            if (function_exists('config') && function_exists('app') && app()->bound('config')) {
                $step = (float) config('learning_hours.step', self::STEP);
                $bucketSeconds = (int) config('learning_hours.bucket_seconds', self::BUCKET_SECONDS);
            }
        } catch (\Throwable $e) {
            // Fallback to constants
        }

        return (float) (ceil($seconds / $bucketSeconds) * $step);
    }
}
