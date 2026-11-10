<?php

namespace App\Console\Commands;

use App\Models\Task;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Delete dummy tasks (titles starting with [DUMMY]) created by
 * TaskDummySeeder — including their questions, submissions and
 * uploaded files on disk.
 *
 * Usage: php artisan task:clean-dummy
 */
class CleanDummyTasks extends Command
{
    protected $signature = 'task:clean-dummy';

    protected $description = 'Delete [DUMMY] tasks including their submissions and uploaded files';

    public function handle(): int
    {
        $tasks = Task::where('title', 'like', '[DUMMY]%')
            ->with('submissions')
            ->get();

        if ($tasks->isEmpty()) {
            $this->info('No dummy tasks found.');

            return self::SUCCESS;
        }

        foreach ($tasks as $task) {
            if ($task->attachment) {
                Storage::disk('public')->delete($task->attachment);
            }

            foreach ($task->submissions as $submission) {
                foreach ($submission->answers as $answer) {
                    foreach ($answer->filePaths() as $path) {
                        Storage::disk('public')->delete($path);
                    }
                }
            }
            Storage::disk('public')->deleteDirectory("task-submissions/{$task->id}");

            $title = $task->title;
            // Questions, submissions & answers are removed by DB cascade.
            // forceDelete: cleanup must wipe for real, not just move to trash.
            $task->forceDelete();

            $this->info("Deleted: {$title}");
        }

        $this->info("Done. {$tasks->count()} dummy task(s) removed.");

        return self::SUCCESS;
    }
}
