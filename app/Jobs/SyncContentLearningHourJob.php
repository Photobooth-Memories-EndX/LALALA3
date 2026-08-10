<?php

namespace App\Jobs;

use App\Models\Content;
use App\Services\LearningHours\ContentLearningHourService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncContentLearningHourJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly ?int $contentId = null
    ) {}

    public function handle(ContentLearningHourService $service): void
    {
        if ($this->contentId !== null) {
            $content = Content::find($this->contentId);
            if ($content) {
                $service->syncContent($content);
            }
        } else {
            $service->syncAll();
        }
    }
}
