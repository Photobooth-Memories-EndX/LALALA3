<?php

namespace App\Services\LearningHours;

use Illuminate\Support\Facades\DB;

class LearningHourSynchronizeService
{
    public function __construct(
        protected ContentLearningHourService $contentService,
        protected PlaylistLearningHourService $playlistService
    ) {}

    /**
     * Run full synchronization for Content and Playlist learning hours sequentially.
     *
     * @return array{
     *     content_updated: int,
     *     content_skipped: int,
     *     content_total: int,
     *     playlist_updated: int,
     *     playlist_skipped: int,
     *     playlist_total: int,
     *     total_updated: int,
     *     total_skipped: int
     * }
     */
    public function synchronize(): array
    {
        return DB::transaction(function () {
            $contentStats = $this->contentService->syncAll();
            $playlistStats = $this->playlistService->syncAll();

            return [
                'content_updated'  => $contentStats['updated'],
                'content_skipped'  => $contentStats['skipped'],
                'content_total'    => $contentStats['total'],
                'playlist_updated' => $playlistStats['updated'],
                'playlist_skipped' => $playlistStats['skipped'],
                'playlist_total'   => $playlistStats['total'],
                'total_updated'    => $contentStats['updated'] + $playlistStats['updated'],
                'total_skipped'    => $contentStats['skipped'] + $playlistStats['skipped'],
            ];
        });
    }
}
