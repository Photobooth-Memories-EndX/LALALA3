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
        if (Schema::hasTable('offline_trainings')) {
            Schema::table('offline_trainings', function (Blueprint $table) {
                $table->time('start_time')->nullable()->default(null)->change();
                $table->time('end_time')->nullable()->default(null)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('offline_trainings')) {
            Schema::table('offline_trainings', function (Blueprint $table) {
                $table->time('start_time')->default('08:00:00')->change();
                $table->time('end_time')->default('16:00:00')->change();
            });
        }
    }
};
