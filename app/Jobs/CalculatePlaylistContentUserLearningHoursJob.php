<?php

namespace App\Jobs;

use App\Services\LearningHours\PlaylistContentUserBatchCalculatorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class CalculatePlaylistContentUserLearningHoursJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     *
     * @var int
     */
    public $timeout = 600;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * Create a new job instance.
     *
     * @param array{
     *     chunk_size?: int,
     *     user_id?: int|null,
     *     content_id?: int|null,
     *     trainingplan_id?: int|null,
     *     limit?: int|null,
     *     force?: bool,
     *     dry_run?: bool
     * } $options
     */
    public function __construct(
        public readonly array $options = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(PlaylistContentUserBatchCalculatorService $batchService): void
    {
        $logChannel = config('learning_hours.log_channel', 'single');

        Log::channel($logChannel)->info('Learning Hour Playlist calculation job started in background queue.', [
            'options' => $this->options,
        ]);

        $results = $batchService->process($this->options);

        Log::channel($logChannel)->info('Learning Hour Playlist calculation job finished successfully.', [
            'total_scanned' => $results['total_scanned'],
            'total_updated' => $results['total_updated'],
            'total_skipped' => $results['total_skipped'],
            'duration'      => $results['duration_seconds'],
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::channel(config('learning_hours.log_channel', 'single'))->error('Learning Hour Playlist calculation job failed after retries.', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
