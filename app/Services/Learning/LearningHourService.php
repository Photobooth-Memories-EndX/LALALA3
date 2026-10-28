<?php

namespace App\Services\Learning;

use App\Models\ContentUser;
use App\Models\User;
use Illuminate\Support\Collection;

class LearningHourService
{
    private const BUCKET_SECONDS = 1800;
    private const BUCKET_HOURS = 0.5;

    /**
     * Canonical total used by every user-facing Learning Hour summary.
     *
     * Playlist duration is deliberately not added: a playlist is composed of
     * the same completed content and would otherwise count it a second time.
     */
    public function getTotalLearningHours(User|int $user, ?Collection $completedContentUsers = null): float
    {
        return $this->getCompletedContentLearningHours($user, $completedContentUsers);
    }

    public function getCompletedContentLearningHours(
        User|int $user,
        ?Collection $completedContentUsers = null
    ): float {
        $durations = $completedContentUsers === null
            ? $this->completedContentDurations($this->userId($user))
            : $completedContentUsers
                ->filter(fn ($contentUser) => $contentUser->content !== null
                    && (string) $contentUser->finish === '1'
                    && $contentUser->content->deleted_at === null)
                ->unique('content_id')
                ->pluck('content.duration');

        return $this->calculateTotal($durations);
    }

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

    /**
     * Calculate Total Recognized Duration in seconds.
     * Formula: min(actual_watch_seconds, content_duration_seconds * 2)
     */
    public function calculateRecognizedDuration(int $actualWatchSeconds, int $contentDurationSeconds): int
    {
        $actualWatchSeconds = max(0, $actualWatchSeconds);
        if ($contentDurationSeconds > 0) {
            return min($actualWatchSeconds, $contentDurationSeconds * 2);
        }

        return $actualWatchSeconds;
    }

    /**
     * Format recognized duration seconds to HH:MM:SS.
     */
    public function formatRecognizedDuration(int $seconds): string
    {
        return \App\Support\DurationFormatter::fromSeconds($seconds);
    }

    private function calculateTotal(Collection $durations): float
    {
        return (float) $durations->sum(
            fn ($duration) => $this->calculate(is_string($duration) ? $duration : null)
        );
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

    private function completedContentDurations(int $userId): Collection
    {
        return ContentUser::query()
            ->join('content', 'content.id', '=', 'content_users.content_id')
            ->where('content_users.user_id', $userId)
            ->where('content_users.finish', '1')
            ->whereNull('content.deleted_at')
            ->whereNotNull('content.duration')
            ->where('content.duration', '!=', '')
            ->distinct()
            ->get(['content.id as content_id', 'content.duration'])
            ->pluck('duration');
    }

    private function userId(User|int $user): int
    {
        return $user instanceof User ? (int) $user->getKey() : $user;
    }
}
