<?php

namespace App\Events;

use App\Models\TrainingPlan;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlaylistLearningHourSynchronized
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly TrainingPlan $playlist,
        public readonly int $oldDurationSeconds,
        public readonly int $newDurationSeconds,
        public readonly float $oldLearningHours,
        public readonly float $newLearningHours,
        public readonly bool $wasUpdated
    ) {}
}
