<?php

namespace App\Console\Commands;

use App\Models\Task;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Delete all Assessment/Quiz tasks (type = 'assessment') — the task type
 * was removed because the LMS already has its own Assessment module.
 * Includes their questions, submissions and uploaded files on disk.
 *
 * Usage: php artisan task:clean-assessment
 */
class CleanAssessmentTasks extends Command
{
    protected $signature = 'task:clean-assessment';

    protected $description = 'Delete Assessment/Quiz tasks including their submissions and uploaded files';

    public function handle(): int
    {
        $tasks = Task::where('type', 'assessment')
            ->with('submissions')
            ->get();

        if ($tasks->isEmpty()) {
            $this->info('No assessment tasks found.');

            return self::SUCCESS;
        }

        if (! $this->confirm("Delete {$tasks->count()} assessment task(s) permanently?")) {
            $this->info('Cancelled.');

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
            $this->line("Deleted: {$title}");
        }

        $this->info("Done. {$tasks->count()} assessment task(s) removed.");

        return self::SUCCESS;
    }
}
