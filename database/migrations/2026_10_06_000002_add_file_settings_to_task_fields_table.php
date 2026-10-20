<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * File-upload question settings (ala Google Form):
 * - file_types: JSON array of allowed groups (document, spreadsheet, pdf,
 *   video, presentation, image, photo, audio); null = all types allowed.
 * - max_files: max number of files the user may upload for this question.
 * - max_size_mb: max size per file in MB; null = default (5).
 *
 * task_submission_answers.file_path becomes TEXT so it can hold a JSON
 * array of paths (one answer row may now carry several files).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_fields', function (Blueprint $table) {
            $table->json('file_types')->nullable()->after('correct_answer');
            $table->unsignedTinyInteger('max_files')->default(1)->after('file_types');
            $table->unsignedInteger('max_size_mb')->nullable()->after('max_files');
        });

        // TEXT via raw statement (avoids doctrine/dbal dependency for change()).
        DB::statement('ALTER TABLE `task_submission_answers` MODIFY `file_path` TEXT NULL');
    }

    public function down(): void
    {
        Schema::table('task_fields', function (Blueprint $table) {
            $table->dropColumn(['file_types', 'max_files', 'max_size_mb']);
        });

        DB::statement('ALTER TABLE `task_submission_answers` MODIFY `file_path` VARCHAR(255) NULL');
    }
};
