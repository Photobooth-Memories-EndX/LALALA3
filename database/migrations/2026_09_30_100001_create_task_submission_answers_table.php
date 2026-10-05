<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel task_submission_answers — jawaban user PER FIELD.
 * Satu submission bisa berisi banyak jawaban (satu per field task).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_submission_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('task_submissions')->cascadeOnDelete();
            $table->foreignId('task_field_id')->constrained('task_fields')->cascadeOnDelete();
            $table->text('answer_text')->nullable();  // untuk field text / textarea
            $table->string('file_path')->nullable();  // untuk field file
            $table->timestamps();

            $table->unique(['submission_id', 'task_field_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_submission_answers');
    }
};
