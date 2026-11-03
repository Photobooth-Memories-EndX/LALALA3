<?php

namespace Database\Seeders;

use App\Models\Assesment;
use App\Models\Content;
use App\Models\Task;
use App\Models\TaskField;
use App\Models\TrainingPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Dummy tasks for trying out the My Task feature (idempotent — safe to re-run).
 *
 * Spreads tasks across several playlists AND contents:
 *  - per playlist : 2x file submission, 1x course assignment, 1x assessment/quiz
 *  - per content   : 1x file submission, 1x course assignment, 1x assessment/quiz
 *  - plus 1 draft task to show the draft state
 *
 * Prerequisites: php artisan migrate  (tasks + task_fields tables must exist)
 *
 * Run: php artisan db:seed --class=Database\Seeders\TaskDummySeeder
 * Clean: php artisan task:clean-dummy
 */
class TaskDummySeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::orderBy('id')->first();
        if (! $admin) {
            $this->command->warn('No users found. Seed users first:');
            $this->command->warn('  php artisan db:seed --class=Database\\Seeders\\UserSeeder');

            return;
        }

        $playlists = TrainingPlan::orderBy('id')->take(3)->get();
        $contents = Content::orderBy('id')->take(3)->get();

        if ($playlists->isEmpty() && $contents->isEmpty()) {
            $this->command->warn('No playlists or contents found. Dummy tasks need at least one.');

            return;
        }

        $assessment = Assesment::where('status', 'OK')->orderBy('id')->first();
        if (! $assessment) {
            $this->command->warn('No published LMS assessment found — quiz tasks will be skipped.');
        }

        $created = 0;

        // ── Playlists ───────────────────────────────────────────────────
        foreach ($playlists as $playlist) {
            $tag = $this->tag($playlist->title);
            $base = [
                'parent_type'      => 'playlist',
                'training_plan_id' => $playlist->id,
                'content_id'       => null,
                'created_by'       => $admin->id,
                'status'           => 'published',
            ];

            $t = $this->makeTask($base + [
                'title'       => "[DUMMY] {$tag} — Upload Safety Report",
                'description' => 'Upload your safety report in PDF format, or paste a Drive link.',
                'type'        => 'file_submission',
                'deadline'    => now()->addDays(7),
            ]);
            $this->makeFileField($t, 'Safety report file', [
                'file_types' => ['pdf'], 'max_files' => 2, 'max_size_mb' => 10, 'allow_link' => true,
            ]);
            $created++;

            $t = $this->makeTask($base + [
                'title'       => "[DUMMY] {$tag} — Upload Training Photos",
                'description' => 'Upload up to 5 photos documenting the training session.',
                'type'        => 'file_submission',
                'deadline'    => now()->addDays(14),
            ]);
            $this->makeFileField($t, 'Training documentation photos', [
                'file_types' => ['image'], 'max_files' => 5, 'max_size_mb' => 5, 'allow_link' => false,
            ]);
            $created++;

            $this->makeTask($base + [
                'title'                 => "[DUMMY] {$tag} — Complete Course Module",
                'description'           => 'Finish the course module, then mark this task as complete.',
                'type'                  => 'course_assignment',
                'link_type'             => 'lms',
                'linked_training_plan_id' => $playlist->id,
                'deadline'              => now()->addDays(30),
            ]);
            $created++;

            if ($assessment) {
                $this->makeTask($base + [
                    'title'         => "[DUMMY] {$tag} — Weekly Quiz",
                    'description'   => 'Take the quiz in the LMS Assessment module.',
                    'type'          => 'assessment',
                    'assessment_id' => $assessment->id,
                    'deadline'      => now()->addDays(7),
                ]);
                $created++;
            }
        }

        // ── Contents ────────────────────────────────────────────────────
        foreach ($contents as $content) {
            $tag = $this->tag($content->title);
            $base = [
                'parent_type'      => 'content',
                'training_plan_id' => null,
                'content_id'       => $content->id,
                'created_by'       => $admin->id,
                'status'           => 'published',
            ];

            $t = $this->makeTask($base + [
                'title'       => "[DUMMY] {$tag} — Submit Assignment Document",
                'description' => 'Upload your assignment as a document, or paste a Drive link.',
                'type'        => 'file_submission',
                'deadline'    => now()->addDays(10),
            ]);
            $this->makeFileField($t, 'Assignment document', [
                'file_types' => ['document'], 'max_files' => 3, 'max_size_mb' => 20, 'allow_link' => true,
            ]);
            $created++;

            $this->makeTask($base + [
                'title'        => "[DUMMY] {$tag} — Watch External Training",
                'description'  => 'Watch the external training video, then mark this task as complete.',
                'type'         => 'course_assignment',
                'link_type'    => 'external',
                'external_url' => 'https://example.com/training/module-1',
                'deadline'     => now()->addDays(21),
            ]);
            $created++;

            if ($assessment) {
                $this->makeTask($base + [
                    'title'         => "[DUMMY] {$tag} — Module Quiz",
                    'description'   => 'Take the quiz in the LMS Assessment module.',
                    'type'          => 'assessment',
                    'assessment_id' => $assessment->id,
                    // Sengaja overdue untuk demo status overdue.
                    'deadline'      => now()->subDay(),
                ]);
                $created++;
            }
        }

        // ── Satu contoh draft ───────────────────────────────────────────
        if ($playlists->isNotEmpty()) {
            $playlist = $playlists->first();
            $t = $this->makeTask([
                'title'            => '[DUMMY] ' . $this->tag($playlist->title) . ' — Upload Field Video (Draft)',
                'parent_type'      => 'playlist',
                'training_plan_id' => $playlist->id,
                'content_id'       => null,
                'description'      => 'Draft task example — not visible to users until published.',
                'type'             => 'file_submission',
                'deadline'         => now()->addDays(7),
                'created_by'       => $admin->id,
                'status'           => 'draft',
            ]);
            $this->makeFileField($t, 'Field video', [
                'file_types' => ['video'], 'max_files' => 1, 'max_size_mb' => 50, 'allow_link' => true,
            ]);
            $created++;
        }

        $this->command->info("Dummy tasks ready: {$created} task(s) with the [DUMMY] prefix.");
    }

    protected function tag(string $title): string
    {
        return Str::limit(trim($title) !== '' ? $title : 'Untitled', 24);
    }

    protected function makeTask(array $attrs): Task
    {
        return Task::firstOrCreate(['title' => $attrs['title']], $attrs);
    }

    protected function makeFileField(Task $task, string $label, array $settings): void
    {
        TaskField::firstOrCreate(
            ['task_id' => $task->id, 'label' => $label],
            array_merge([
                'type'       => 'file',
                'required'   => true,
                'sort_order' => 0,
            ], $settings)
        );
    }
}
