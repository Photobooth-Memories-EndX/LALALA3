<?php

namespace App\Jobs;

use App\Models\TrainingPlan;
use App\Services\LearningHours\PlaylistLearningHourService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncPlaylistLearningHourJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly ?int $playlistId = null
    ) {}

    public function handle(PlaylistLearningHourService $service): void
    {
        if ($this->playlistId !== null) {
            $playlist = TrainingPlan::find($this->playlistId);
            if ($playlist) {
                $service->syncPlaylist($playlist);
            }
        } else {
            $service->syncAll();
        }
    }
}
