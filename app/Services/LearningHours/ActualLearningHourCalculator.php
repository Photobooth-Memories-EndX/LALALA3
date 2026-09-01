<?php

namespace App\Services\LearningHours;

class ActualLearningHourCalculator
{
    public const SECONDS_IN_HOUR = 3600;
    public const PRECISION = 4;

    /**
     * Calculate actual learning hours from actual seconds.
     * Formula: round(actual_seconds / 3600, 4)
     *
     * @param int|float|string|null $seconds
     * @return float|null
     */
    public function calculate(mixed $seconds): ?float
    {
        if ($seconds === null) {
            return null;
        }

        if (is_string($seconds) && !is_numeric($seconds)) {
            return null;
        }

        $sec = (float) $seconds;

        // Negative seconds should never yield negative learning hours
        if ($sec <= 0) {
            return 0.0;
        }

        return (float) round($sec / self::SECONDS_IN_HOUR, self::PRECISION);
    }

    /**
     * Resolve actual seconds from user tracking data, applying historical fallback rules:
     *
     * 1. If current_time > 0: use current_time
     * 2. If current_time = 0 AND is finished (finish = '1' or progress >= 100):
     *    use official content duration as historical fallback
     * 3. If current_time = 0 AND not finished:
     *    do not assume full duration; return 0 seconds
     *
     * @param int|float|null $currentTime
     * @param string|int|null $finish
     * @param int|float|null $progress
     * @param int|float|string|null $contentDuration Duration string (HH:MM:SS) or seconds
     * @return int Total actual seconds
     */
    public function resolveActualSeconds(
        mixed $currentTime,
        mixed $finish = '0',
        mixed $progress = 0,
        mixed $contentDuration = null
    ): int {
        $curr = (int) round((float) ($currentTime ?? 0));

        // 1. If current_time > 0, it is the authoritative tracked seconds
        if ($curr > 0) {
            return $curr;
        }

        $isFinished = ((string) $finish === '1' || (int) $progress >= 100);

        // 2. If current_time = 0 and finished, fallback to official content duration
        if ($isFinished && $contentDuration !== null) {
            return $this->parseContentDurationToSeconds($contentDuration);
        }

        // 3. If current_time = 0 and not finished, return 0 seconds
        return 0;
    }

    /**
     * Calculate actual learning hours directly from tracking record attributes.
     * Pure and stateless. ZERO database queries.
     *
     * @param int|float|null $currentTime
     * @param string|int|null $finish
     * @param int|float|null $progress
     * @param int|float|string|null $contentDuration
     * @return float
     */
    public function calculateFromTracking(
        mixed $currentTime,
        mixed $finish = '0',
        mixed $progress = 0,
        mixed $contentDuration = null
    ): float {
        $actualSeconds = $this->resolveActualSeconds($currentTime, $finish, $progress, $contentDuration);

        return $this->calculate($actualSeconds) ?? 0.0;
    }

    /**
     * Calculate actual learning hours for multiple records in memory using preloaded content durations map.
     * Pure, stateless, and produces ZERO database queries.
     *
     * @param iterable $records Collection of records or array of items
     * @param array<int|string, int|string> $contentDurationsMap Map of [content_id => duration_string_or_seconds]
     * @return array<int|string, float> Map of [record_id => actual_learning_hours]
     */
    public function calculateBatch(iterable $records, array $contentDurationsMap = []): array
    {
        $results = [];

        foreach ($records as $record) {
            $id = is_object($record) ? ($record->id ?? null) : ($record['id'] ?? null);
            $contentId = is_object($record) ? ($record->content_id ?? null) : ($record['content_id'] ?? null);
            $currentTime = is_object($record) ? ($record->current_time ?? 0) : ($record['current_time'] ?? 0);
            $finish = is_object($record) ? ($record->finish ?? '0') : ($record['finish'] ?? '0');
            $progress = is_object($record) ? ($record->progress ?? 0) : ($record['progress'] ?? 0);
            $durationSeconds = is_object($record) ? ($record->duration_seconds ?? null) : ($record['duration_seconds'] ?? null);

            $contentDuration = $contentDurationsMap[$contentId] ?? $durationSeconds;

            $hours = $this->calculateFromTracking($currentTime, $finish, $progress, $contentDuration);

            if ($id !== null) {
                $results[$id] = $hours;
            } else {
                $results[] = $hours;
            }
        }

        return $results;
    }

    /**
     * Parse content duration to integer seconds safely.
     *
     * @param mixed $duration
     * @return int
     */
    public function parseContentDurationToSeconds(mixed $duration): int
    {
        if ($duration === null) {
            return 0;
        }

        if (is_int($duration) || is_float($duration)) {
            return max(0, (int) round($duration));
        }

        $str = trim((string) $duration);
        if ($str === '') {
            return 0;
        }

        if (is_numeric($str) && !str_contains($str, ':')) {
            return max(0, (int) $str);
        }

        $parts = explode(':', $str);
        if (count($parts) === 3) {
            return ((int) $parts[0] * 3600) + ((int) $parts[1] * 60) + (int) $parts[2];
        }

        if (count($parts) === 2) {
            return ((int) $parts[0] * 60) + (int) $parts[1];
        }

        return 0;
    }
}
