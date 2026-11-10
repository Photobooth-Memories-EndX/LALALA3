<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Membuat tabel-tabel dasar sistem Task (tasks, task_submissions, task_fields, task_submission_answers).
     * Dibuat idempoten dengan pengecekan Schema::hasTable sehingga aman dijalankan di database baru maupun database lama.
     */
    public function up(): void
    {
        // 1. Tabel tasks
        if (!Schema::hasTable('tasks')) {
            Schema::create('tasks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('training_plan_id')->nullable()->constrained('trainingplan')->cascadeOnDelete();
                $table->foreignId('content_id')->nullable()->constrained('content')->nullOnDelete();
                $table->unsignedBigInteger('offline_training_id')->nullable();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('type')->default('assignment');
                $table->string('parent_type', 20)->default('playlist');
                $table->string('link_type', 20)->nullable();
                $table->unsignedBigInteger('linked_training_plan_id')->nullable();
                $table->string('external_url', 500)->nullable();
                $table->timestamp('deadline')->nullable();
                $table->string('attachment')->nullable();
                $table->foreignId('created_by')->constrained('users');
                $table->string('status')->default('draft');
                $table->timestamps();
            });
        }

        // 2. Tabel task_submissions
        if (!Schema::hasTable('task_submissions')) {
            Schema::create('task_submissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('status')->default('pending');
                $table->text('answer')->nullable();
                $table->string('file_path')->nullable();
                $table->unsignedTinyInteger('score')->nullable();
                $table->text('feedback')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamps();

                $table->unique(['task_id', 'user_id']);
            });
        }

        // 3. Tabel task_fields
        if (!Schema::hasTable('task_fields')) {
            Schema::create('task_fields', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
                $table->string('label');
                $table->string('type', 20)->default('file');
                $table->json('options')->nullable();
                $table->string('correct_answer')->nullable();
                $table->boolean('required')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        // 4. Tabel task_submission_answers
        if (!Schema::hasTable('task_submission_answers')) {
            Schema::create('task_submission_answers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('submission_id')->constrained('task_submissions')->cascadeOnDelete();
                $table->foreignId('task_field_id')->constrained('task_fields')->cascadeOnDelete();
                $table->text('answer_text')->nullable();
                $table->string('file_path')->nullable();
                $table->timestamps();

                $table->unique(['submission_id', 'task_field_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_submission_answers');
        Schema::dropIfExists('task_fields');
        Schema::dropIfExists('task_submissions');
        Schema::dropIfExists('tasks');
    }
};
