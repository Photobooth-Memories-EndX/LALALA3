<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('task_groups')) {
            Schema::create('task_groups', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('tasks') && !Schema::hasColumn('tasks', 'task_group_id')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->foreignId('task_group_id')->nullable()->after('offline_training_id')->constrained('task_groups')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tasks') && Schema::hasColumn('tasks', 'task_group_id')) {
            Schema::table('tasks', function (Blueprint $table) {
                try {
                    $table->dropForeign(['task_group_id']);
                } catch (\Throwable $e) {}
                $table->dropColumn('task_group_id');
            });
        }

        Schema::dropIfExists('task_groups');
    }
};

