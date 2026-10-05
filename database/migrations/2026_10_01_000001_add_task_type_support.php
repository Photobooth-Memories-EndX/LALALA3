<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mentor revision (2026-10-01): 6 task types.
 *
 * 1. file_submission   — user uploads file(s)
 * 2. course_assignment — user opens a course (LMS course or external link), then marks it done
 * 3. assessment        — quiz, multiple choice with automatic scoring
 * 4. survey            — opinion collection, no scoring
 * 5. essay             — written reflection, long text, manual scoring
 * 6. action_plan       — Google Form-style (file/short/long answers)
 *
 * - tasks: link target columns for course_assignment
 *   (link_type lms|external, linked_training_plan_id, external_url).
 * - task_fields: options (JSON, for multiple choice) + correct_answer
 *   (for quiz auto-scoring).
 * - Existing tasks with type 'assignment' become 'action_plan'
 *   (same Google Form behaviour as before).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('link_type', 20)->nullable()->after('type');
            $table->unsignedBigInteger('linked_training_plan_id')->nullable()->after('link_type');
            $table->string('external_url', 500)->nullable()->after('linked_training_plan_id');
        });

        Schema::table('task_fields', function (Blueprint $table) {
            $table->json('options')->nullable()->after('type');
            $table->string('correct_answer')->nullable()->after('options');
        });

        // Backfill legacy type values.
        DB::table('tasks')->where('type', 'assignment')->update(['type' => 'action_plan']);
        DB::table('tasks')
            ->whereNotIn('type', [
                'file_submission', 'course_assignment', 'assessment',
                'survey', 'essay', 'action_plan',
            ])
            ->update(['type' => 'action_plan']);
    }

    public function down(): void
    {
        Schema::table('task_fields', function (Blueprint $table) {
            $table->dropColumn(['options', 'correct_answer']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['link_type', 'linked_training_plan_id', 'external_url']);
        });
    }
};
