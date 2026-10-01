<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('offline_trainings', function (Blueprint $table) {
            // Modify shared fields to be nullable to support both Manage Record and Training Schedule workflows
            $table->string('title', 255)->nullable()->change();
            $table->string('category', 100)->nullable()->change();
            $table->string('instructor', 255)->nullable()->change();
            $table->string('location', 255)->nullable()->change();
            $table->date('start_date')->nullable()->change();

            // Add composite index for Manage Record queries (preserving existing start_date + end_date index)
            $table->index(['start_date', 'finish_date'], 'offline_trainings_start_finish_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('offline_trainings', function (Blueprint $table) {
            // Drop composite index added by this migration
            $table->dropIndex('offline_trainings_start_finish_index');

            // Intentionally not reverting columns back to NOT NULL to prevent data truncation
            // and MySQL 1138 errors if rows with NULL values were created while this migration was active.
        });
    }
};
