<?php

namespace App\Jobs;

use App\Services\LearningHours\ManualPlaylistContentUserLearningHourService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ManualCalculatePlaylistContentUserLearningHoursJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * The maximum number of seconds the job can run.
     *
     * @var int
     */
    public $timeout = 600;

    /**
     * Create a new job instance.
     *
     * @param array<string, mixed> $criteria
     * @param array<string, mixed> $options
     */
    public function __construct(
        public readonly array $criteria = [],
        public readonly array $options = []
    ) {}

    /**
     * Execute the job using the dedicated Manual Service.
     */
    public function handle(ManualPlaylistContentUserLearningHourService $manualService): array
    {
        Log::info('ManualCalculatePlaylistContentUserLearningHoursJob started', [
            'criteria' => $this->criteria,
            'options'  => $this->options,
        ]);

        $result = $manualService->calculate($this->criteria, $this->options);

        Log::info('ManualCalculatePlaylistContentUserLearningHoursJob completed', [
            'mode'             => $result['mode'] ?? null,
            'total_scanned'    => $result['total_scanned'] ?? 0,
            'total_updated'    => $result['total_updated'] ?? 0,
            'total_skipped'    => $result['total_skipped'] ?? 0,
            'duration_seconds' => $result['duration_seconds'] ?? 0,
        ]);

        return $result;
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('ManualCalculatePlaylistContentUserLearningHoursJob failed', [
            'criteria'  => $this->criteria,
            'options'   => $this->options,
            'exception' => $exception->getMessage(),
        ]);
    }
}
