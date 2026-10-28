<?php

namespace App\Services\EngagementTime;

use App\Models\Content;
use App\Models\TrainingPlan;

class EngagementTimeService
{
    /**
     * Exact canonical slug for the authorized target content.
     */
    public const TARGET_CONTENT_SLUG = 'penanggulangan-hivaids-di-tempat-kerja-part-2';

    /**
     * Exact canonical slugs for the authorized target playlist and its child content.
     */
    public const TARGET_PLAYLIST_SLUG = 'ptkp-global-assessment-2026-for-job-clan-ms-t';
    public const TARGET_PLAYLIST_CONTENT_SLUG = 'hr-a1-budaya-perusahaan-pengembangan-organisasi-2025';

    /**
     * Check if a content instance or slug matches the target standalone content page.
     *
     * @param Content|string|null $content
     * @return bool
     */
    public static function isTargetContent($content): bool
    {
        if (!$content) {
            return false;
        }

        $slug = is_string($content) ? $content : ($content->slug ?? '');

        return $slug === self::TARGET_CONTENT_SLUG;
    }

    /**
     * Check if a playlist and child content pair matches the target playlist page.
     *
     * @param TrainingPlan|string|null $playlist
     * @param Content|string|null $content
     * @return bool
     */
    public static function isTargetPlaylistContent($playlist, $content): bool
    {
        if (!$playlist || !$content) {
            return false;
        }

        $playlistSlug = is_string($playlist) ? $playlist : ($playlist->slug ?? '');
        $contentSlug  = is_string($content) ? $content : ($content->slug ?? '');

        return $playlistSlug === self::TARGET_PLAYLIST_SLUG
            && $contentSlug === self::TARGET_PLAYLIST_CONTENT_SLUG;
    }
}
