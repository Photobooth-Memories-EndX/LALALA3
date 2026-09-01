<?php

namespace App\Console\Commands;

use App\Services\LearningHours\LearningHourConverterService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillContentUserLearningHoursCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'learning-hours:backfill-content-users {--chunk=2500 : Number of records per chunk}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely backfill learning_hours and completed_at on content_users using hybrid calculation rules without mutating existing timestamps';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(LearningHourConverterService $converter): int
    {
        $this->info('Starting ContentUser Learning Hours and Completed At Backfill...');

        $startTime = microtime(true);
        $totalRecords = DB::table('content_users')->count();
        $this->info("Total content_users records in database: {$totalRecords}");

        if ($totalRecords === 0) {
            $this->info('No records to backfill.');
            return Command::SUCCESS;
        }

        // 1. Preload master contents into memory
        $contents = DB::table('content')
            ->whereNull('deleted_at')
            ->get(['id', 'duration', 'learning_hours'])
            ->keyBy('id');

        $this->info("Preloaded {$contents->count()} active content records.");

        $chunkSize = max(100, (int) $this->option('chunk'));
        $lastId = 0;
        $processed = 0;
        $completedCount = 0;
        $partialCount = 0;
        $zeroCount = 0;
        $orphanCount = 0;

        $bar = $this->output->createProgressBar($totalRecords);
        $bar->start();

        while (true) {
            $chunk = DB::table('content_users')
                ->where('id', '>', $lastId)
                ->orderBy('id')
                ->limit($chunkSize)
                ->get(['id', 'content_id', 'progress', 'finish', 'created_at', 'updated_at']);

            if ($chunk->isEmpty()) {
                break;
            }

            $hoursGroups = [];
            $completedIds = [];
            $notCompletedIds = [];

            foreach ($chunk as $item) {
                $lastId = $item->id;
                $processed++;

                $content = $contents->get($item->content_id);

                if (!$content) {
                    // Orphan record (content missing or soft-deleted)
                    $orphanCount++;
                    $hoursGroups['NULL'][] = $item->id;
                    $notCompletedIds[] = $item->id;
                    continue;
                }

                $masterHours = $content->learning_hours !== null
                    ? (float) $content->learning_hours
                    : $converter->convert($content->duration);

                if ($masterHours === null) {
                    $orphanCount++;
                    $hoursGroups['NULL'][] = $item->id;
                    $notCompletedIds[] = $item->id;
                    continue;
                }

                $isFinished = ((string) $item->finish === '1' || (int) $item->progress >= 100);

                if ($isFinished) {
                    $completedCount++;
                    $userHours = (float) $masterHours;
                    $hoursGroups[(string) $userHours][] = $item->id;
                    $completedIds[] = $item->id;
                } else {
                    $notCompletedIds[] = $item->id;
                    $progress = (int) ($item->progress ?? 0);

                    if ($progress > 0) {
                        $partialCount++;
                        $userHours = round((float) $masterHours * ($progress / 100), 1);
                        $hoursGroups[(string) $userHours][] = $item->id;
                    } else {
                        $zeroCount++;
                        $userHours = 0.0;
                        $hoursGroups['0.0'][] = $item->id;
                    }
                }
            }

            // Perform batch updates via DB::table (without triggering Eloquent timestamps)
            DB::transaction(function () use ($hoursGroups, $completedIds, $notCompletedIds) {
                foreach ($hoursGroups as $hoursKey => $ids) {
                    if ($hoursKey === 'NULL') {
                        DB::table('content_users')->whereIn('id', $ids)->update(['learning_hours' => null]);
                    } else {
                        DB::table('content_users')->whereIn('id', $ids)->update(['learning_hours' => (float) $hoursKey]);
                    }
                }

                if (!empty($completedIds)) {
                    DB::table('content_users')->whereIn('id', $completedIds)->update([
                        'completed_at' => DB::raw('COALESCE(updated_at, created_at)'),
                    ]);
                }

                if (!empty($notCompletedIds)) {
                    DB::table('content_users')->whereIn('id', $notCompletedIds)->update([
                        'completed_at' => null,
                    ]);
                }
            });

            $bar->advance($chunk->count());
        }

        $bar->finish();
        $this->newLine(2);

        $duration = round(microtime(true) - $startTime, 2);

        $this->info("Backfill completed successfully in {$duration} seconds!");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Records Processed', number_format($processed)],
                ['Completed Records (Full Master Hours)', number_format($completedCount)],
                ['Partial Progress Records (Proportional Hours)', number_format($partialCount)],
                ['Zero Progress Records (0.0 Hours)', number_format($zeroCount)],
                ['Orphan / Invalid Records (NULL Hours)', number_format($orphanCount)],
            ]
        );

        return Command::SUCCESS;
    }
}
