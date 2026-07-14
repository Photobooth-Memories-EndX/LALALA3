<?php

namespace App\Services\Learning;

use Illuminate\Support\Collection;

class LearningHourService
{
    private const BUCKET_SECONDS = 1800;
    private const BUCKET_HOURS = 0.5;

    public function calculate(?string $duration): float
    {
        return $this->calculateFromSeconds($this->durationToSeconds($duration));
    }

    public function calculateFromSeconds(?int $seconds): float
    {
        $seconds = max(0, (int) $seconds);

        if ($seconds === 0) {
            return 0.0;
        }

        return (float) (ceil($seconds / self::BUCKET_SECONDS) * self::BUCKET_HOURS);
    }

    public function bucket(?string $duration): float
    {
        return $this->calculate($duration);
    }

    public function bucketFromSeconds(?int $seconds): float
    {
        return $this->calculateFromSeconds($seconds);
    }

    public function format(?string $duration): string
    {
        return $this->formatHours($this->calculate($duration));
    }

    public function formatFromSeconds(?int $seconds): string
    {
        return $this->formatHours($this->calculateFromSeconds($seconds));
    }

    public function formatHours(float $hours): string
    {
        if ($hours <= 0) {
            return '-';
        }

        $label = rtrim(rtrim(number_format($hours, 1, '.', ''), '0'), '.');

        return $label . ' ' . ($hours <= 1.0 ? 'hour' : 'hours');
    }

    public function filterOptions(Collection $buckets): Collection
    {
        return $buckets
            ->map(fn ($bucket) => (float) $bucket)
            ->filter(fn (float $bucket) => $bucket > 0)
            ->unique()
            ->sort()
            ->values()
            ->map(fn (float $bucket) => [
                'value' => $this->value($bucket),
                'label' => $this->formatHours($bucket),
            ]);
    }

    public function value(float $hours): string
    {
        return rtrim(rtrim(number_format($hours, 1, '.', ''), '0'), '.');
    }

    private function durationToSeconds(?string $duration): int
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
}
