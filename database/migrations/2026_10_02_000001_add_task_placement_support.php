<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Task placement: a task can now live under a Playlist (trainingplan),
 * a Content, or an Offline Training — not just a course/playlist.
 *
 * - tasks.parent_type: 'playlist' | 'content' | 'offline_training'
 * - tasks.training_plan_id: now nullable (parent when parent_type = playlist)
 * - tasks.content_id: parent when parent_type = content
 *   (kept its old "module inside a playlist" meaning for existing rows)
 * - tasks.offline_training_id: nullable, NO foreign key yet —
 *   offline training data has not been merged into the project.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('parent_type', 20)->default('playlist')->after('type');
            $table->unsignedBigInteger('offline_training_id')->nullable()->after('content_id');
        });

        // training_plan_id: required -> nullable (drop FK, modify, re-add).
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['training_plan_id']);
        });
        DB::statement('ALTER TABLE `tasks` MODIFY `training_plan_id` BIGINT UNSIGNED NULL');
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreign('training_plan_id')->references('id')->on('trainingplan')->cascadeOnDelete();
        });

        // Existing rows all live under a playlist.
        DB::table('tasks')->whereNull('parent_type')->orWhere('parent_type', '')->update([
            'parent_type' => 'playlist',
        ]);
    }

    public function down(): void
    {
        // NOTE: rolling back only works if no task uses content/offline
        // placement — move those tasks to a playlist first.
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['training_plan_id']);
        });
        DB::statement('ALTER TABLE `tasks` MODIFY `training_plan_id` BIGINT UNSIGNED NOT NULL');
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreign('training_plan_id')->references('id')->on('trainingplan')->cascadeOnDelete();
            $table->dropColumn(['parent_type', 'offline_training_id']);
        });
    }
};
