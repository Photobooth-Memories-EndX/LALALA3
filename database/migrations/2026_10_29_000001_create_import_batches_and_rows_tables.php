<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('import_batches')) {
            Schema::create('import_batches', function (Blueprint $table) {
                $table->id();
                $table->char('batch_uuid', 36)->unique();
                $table->string('source_type', 20)->index(); // 'PTKP' or 'NON_PTKP'
                $table->string('original_filename', 255);
                $table->string('file_path', 500)->nullable();
                $table->string('file_disk', 50)->nullable()->default('local');
                $table->string('file_hash', 64)->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->unsignedBigInteger('imported_by')->index();
                $table->string('status', 30)->default('IMPORTING')->index();

                // Counters
                $table->unsignedInteger('total_rows')->default(0);
                $table->unsignedInteger('valid_rows')->default(0);
                $table->unsignedInteger('warning_rows')->default(0);
                $table->unsignedInteger('duplicate_rows')->default(0);
                $table->unsignedInteger('invalid_rows')->default(0);
                $table->unsignedInteger('imported_rows')->default(0);
                $table->unsignedInteger('skipped_rows')->default(0);
                $table->unsignedInteger('failed_rows')->default(0);
                $table->unsignedInteger('trainings_created')->default(0);
                $table->unsignedInteger('trainings_reused')->default(0);

                // Timestamps & Summary
                $table->dateTime('started_at')->index();
                $table->dateTime('finished_at')->nullable();
                $table->text('error_summary')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->foreign('imported_by')
                    ->references('id')
                    ->on('users')
                    ->onDelete('restrict');

                $table->index(['source_type', 'status', 'created_at'], 'idx_import_batches_source_status');
            });
        }

        if (!Schema::hasTable('import_batch_rows')) {
            Schema::create('import_batch_rows', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('import_batch_id')->index();
                $table->unsignedInteger('row_number');
                $table->string('sheet_name', 100)->nullable();
                $table->string('employee_id', 50)->nullable()->index();
                $table->string('employee_name', 255)->nullable();
                $table->string('course_name', 255)->nullable();
                $table->string('validation_status', 30)->index(); // 'VALID', 'WARNING', 'DUPLICATE', 'INVALID', 'INVALID COMPANY'
                $table->string('action', 30)->index(); // 'IMPORTED', 'SKIPPED', 'FAILED'

                // Target business entity references
                $table->unsignedBigInteger('offline_training_id')->nullable()->index();
                $table->unsignedBigInteger('offline_training_user_id')->nullable()->index();

                $table->string('error_code', 50)->nullable();
                $table->json('messages')->nullable();
                $table->json('row_data')->nullable();
                $table->timestamps();

                $table->foreign('import_batch_id')
                    ->references('id')
                    ->on('import_batches')
                    ->onDelete('cascade');

                $table->foreign('offline_training_id')
                    ->references('id')
                    ->on('offline_trainings')
                    ->onDelete('set null');

                $table->foreign('offline_training_user_id')
                    ->references('id')
                    ->on('offline_training_users')
                    ->onDelete('set null');

                $table->unique(['import_batch_id', 'row_number'], 'uq_batch_row_number');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_batch_rows');
        Schema::dropIfExists('import_batches');
    }
};
