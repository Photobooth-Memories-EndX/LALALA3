<?php

namespace App\Services\Learning;

use App\Models\ContentUser;
use App\Models\PlaylistContentUser;

class ResolvedProgress
{
    public const SOURCE_PLAYLIST_CONTENT_USERS = 'playlist_content_users';
    public const SOURCE_HISTORICAL_CONTENT_USERS = 'historical_content_users';
    public const SOURCE_CONTENT_USERS = 'content_users';
    public const SOURCE_NONE = 'none';
    public const SOURCE_AMBIGUOUS = 'ambiguous';

    public function __construct(
        public readonly int $progress = 0,
        public readonly int $finish = 0,
        public readonly string $learningStatus = 'not_started',
        public readonly ?int $currentTime = null,
        public readonly ?int $lastPage = null,
        public readonly ?int $totalPages = null,
        public readonly ?int $durationSeconds = null,
        public readonly string $source = self::SOURCE_NONE,
        public readonly bool $isAuthoritative = false,
        public readonly bool $isHistoricalFallback = false
    ) {
    }

    public static function fromPlaylistContentUser(PlaylistContentUser $pcu): self
    {
        return new self(
            progress: (int) ($pcu->progress ?? 0),
            finish: (int) ($pcu->finish ?? 0),
            learningStatus: (string) ($pcu->learning_status ?? 'not_started'),
            currentTime: $pcu->current_time !== null ? (int) $pcu->current_time : null,
            lastPage: $pcu->last_page !== null ? (int) $pcu->last_page : null,
            totalPages: $pcu->total_pages !== null ? (int) $pcu->total_pages : null,
            durationSeconds: $pcu->duration_seconds !== null ? (int) $pcu->duration_seconds : null,
            source: self::SOURCE_PLAYLIST_CONTENT_USERS,
            isAuthoritative: true,
            isHistoricalFallback: false
        );
    }

    public static function fromContentUser(ContentUser $cu, bool $asHistoricalPlaylistFallback = false): self
    {
        return new self(
            progress: (int) ($cu->progress ?? 0),
            finish: (int) ($cu->finish ?? 0),
            learningStatus: (string) ($cu->learning_status ?? 'not_started'),
            currentTime: $cu->current_time !== null ? (int) $cu->current_time : null,
            lastPage: $cu->last_page !== null ? (int) $cu->last_page : null,
            totalPages: $cu->total_pages !== null ? (int) $cu->total_pages : null,
            durationSeconds: $cu->duration_seconds !== null ? (int) $cu->duration_seconds : null,
            source: $asHistoricalPlaylistFallback ? self::SOURCE_HISTORICAL_CONTENT_USERS : self::SOURCE_CONTENT_USERS,
            isAuthoritative: !$asHistoricalPlaylistFallback,
            isHistoricalFallback: $asHistoricalPlaylistFallback
        );
    }

    public static function empty(string $source = self::SOURCE_NONE): self
    {
        return new self(
            progress: 0,
            finish: 0,
            learningStatus: 'not_started',
            currentTime: null,
            lastPage: null,
            totalPages: null,
            durationSeconds: null,
            source: $source,
            isAuthoritative: false,
            isHistoricalFallback: false
        );
    }

    public function isFinished(): bool
    {
        return (int) $this->finish === 1 || (int) $this->progress >= 100;
    }

    public function hasStarted(): bool
    {
        return (int) $this->progress > 0 || (int) $this->currentTime > 0 || (int) $this->lastPage > 0;
    }
}
