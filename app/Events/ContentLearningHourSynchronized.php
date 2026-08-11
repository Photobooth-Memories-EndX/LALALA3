<?php

namespace App\Events;

use App\Models\Content;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContentLearningHourSynchronized
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Content $content,
        public readonly ?string $oldDuration,
        public readonly ?string $newDuration,
        public readonly float $oldLearningHours,
        public readonly float $newLearningHours,
        public readonly bool $wasUpdated
    ) {}
}
