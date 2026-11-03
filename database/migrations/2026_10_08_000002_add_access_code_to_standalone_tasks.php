<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Access codes for standalone tasks: a standalone task is only visible to
 * users who entered its code (redeemed once, then the task appears in
 * their My Task list). Admin shares the code with specific users.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tasks') && !Schema::hasColumn('tasks', 'access_code')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->string('access_code', 12)->nullable()->unique()->after('parent_type');
            });
        }

        if (!Schema::hasTable('task_code_redemptions')) {
            Schema::create('task_code_redemptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['task_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('task_code_redemptions');

        if (Schema::hasTable('tasks') && Schema::hasColumn('tasks', 'access_code')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropColumn('access_code');
            });
        }
    }
};
