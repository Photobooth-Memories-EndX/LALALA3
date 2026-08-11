<?php

namespace App\Services\LearningHours;

class DurationParserService
{
    /**
     * Convert duration string (HH:MM:SS or MM:SS) or numeric input to total seconds.
     */
    public function toSeconds(?string $duration): int
    {
        $duration = trim((string) $duration);

        if ($duration === '') {
            return 0;
        }

        $parts = explode(':', $duration);

        if (count($parts) === 2) {
            return ((int) $parts[0] * 60) + (int) $parts[1];
        }

        if (count($parts) === 3) {
            return ((int) $parts[0] * 3600) + ((int) $parts[1] * 60) + (int) $parts[2];
        }

        return is_numeric($duration) ? (int) $duration : 0;
    }

    /**
     * Convert seconds integer to HH:MM:SS string.
     */
    public function fromSeconds(int $seconds): string
    {
        $seconds = max(0, $seconds);

        return sprintf(
            '%02d:%02d:%02d',
            intdiv($seconds, 3600),
            intdiv($seconds % 3600, 60),
            $seconds % 60
        );
    }

    /**
     * Normalize duration string to HH:MM:SS format.
     */
    public function normalize(?string $duration): ?string
    {
        $duration = trim((string) $duration);

        if ($duration === '') {
            return null;
        }

        $seconds = $this->toSeconds($duration);

        return $this->fromSeconds($seconds);
    }
}
