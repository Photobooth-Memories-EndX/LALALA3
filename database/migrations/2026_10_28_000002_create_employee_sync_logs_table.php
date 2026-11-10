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
        if (!Schema::hasTable('employee_sync_logs')) {
            Schema::create('employee_sync_logs', function (Blueprint $table) {
                $table->id();
                $table->dateTime('started_at');
                $table->dateTime('finished_at')->nullable();
                $table->string('status', 30)->default('running');
                $table->boolean('is_dry_run')->default(false);
                $table->unsignedInteger('total_api')->default(0);
                $table->unsignedInteger('total_owl')->default(0);
                $table->unsignedInteger('created_count')->default(0);
                $table->unsignedInteger('updated_count')->default(0);
                $table->unsignedInteger('unchanged_count')->default(0);
                $table->unsignedInteger('skipped_count')->default(0);
                $table->unsignedInteger('conflict_count')->default(0);
                $table->unsignedInteger('failed_count')->default(0);
                $table->longText('details')->nullable();
                $table->timestamps();

                $table->index('status', 'idx_employee_sync_logs_status');
                $table->index('started_at', 'idx_employee_sync_logs_started_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_sync_logs');
    }
};
