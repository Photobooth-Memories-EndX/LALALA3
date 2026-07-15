<?php

namespace App\Support;

class DurationFormatter
{
    public static function normalize(?string $duration): ?string
    {
        $duration = trim((string) $duration);

        if ($duration === '') {
            return null;
        }

        $parts = explode(':', $duration);

        if (count($parts) === 3) {
            return $duration;
        }

        if (count($parts) !== 2) {
            return $duration;
        }

        $minutes = (int) $parts[0];
        $seconds = (int) $parts[1];
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        return sprintf('%02d:%02d:%02d', $hours, $remainingMinutes, $seconds);
    }

    public static function toHms(?string $duration): string
    {
        return self::normalize($duration) ?? '';
    }

    public static function fromSeconds(int $seconds): string
    {
        $seconds = max(0, $seconds);

        return sprintf(
            '%02d:%02d:%02d',
            intdiv($seconds, 3600),
            intdiv($seconds % 3600, 60),
            $seconds % 60
        );
    }

    public static function format(?string $duration, string $fallback = '-'): string
    {
        $formatted = self::normalize($duration);

        return $formatted === null ? $fallback : $formatted;
    }

    public static function formatForLearningRecord(?string $duration, string $fallback = '-'): string
    {
        $seconds = self::toSeconds($duration);

        if ($seconds === null) {
            return $fallback;
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        $parts = [];

        if ($hours > 0) {
            $parts[] = $hours . ' ' . ($hours === 1 ? 'hour' : 'hours');
        }

        if ($minutes > 0 || $hours === 0) {
            $parts[] = $minutes . ' min';
        }

        return implode(' ', $parts);
    }

    public static function toSeconds(?string $duration): ?int
    {
        $duration = trim((string) $duration);

        if ($duration === '') {
            return null;
        }

        $parts = explode(':', $duration);

        if (count($parts) === 2) {
            return ((int) $parts[0] * 60) + (int) $parts[1];
        }

        if (count($parts) === 3) {
            return ((int) $parts[0] * 3600) + ((int) $parts[1] * 60) + (int) $parts[2];
        }

        return null;
    }

    public static function toHours(?string $duration): float
    {
        $seconds = self::toSeconds($duration);

        return $seconds === null ? 0 : $seconds / 3600;
    }

    public static function isValid(?string $duration): bool
    {
        $duration = trim((string) $duration);

        if ($duration === '') {
            return true;
        }

        return (bool) preg_match('/^\d+:[0-5]\d$/', $duration)
            || (bool) preg_match('/^\d{2}:[0-5]\d:[0-5]\d$/', $duration);
    }
}
