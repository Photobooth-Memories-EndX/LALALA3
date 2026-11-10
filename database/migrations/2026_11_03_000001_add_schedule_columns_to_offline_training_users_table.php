<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('offline_training_users')) {
            Schema::table('offline_training_users', function (Blueprint $table) {
                if (!Schema::hasColumn('offline_training_users', 'start_date')) {
                    $table->date('start_date')->nullable()->after('learning_hours')->index();
                }
                if (!Schema::hasColumn('offline_training_users', 'end_date')) {
                    $table->date('end_date')->nullable()->after('start_date')->index();
                }
                if (!Schema::hasColumn('offline_training_users', 'start_time')) {
                    $table->time('start_time')->nullable()->after('end_date');
                }
                if (!Schema::hasColumn('offline_training_users', 'end_time')) {
                    $table->time('end_time')->nullable()->after('start_time');
                }
            });

            // Safely backfill existing participant records from joined offline_trainings table
            if (Schema::hasTable('offline_trainings')) {
                DB::statement("
                    UPDATE offline_training_users otu
                    INNER JOIN offline_trainings ot ON ot.id = otu.offline_training_id
                    SET
                        otu.start_date = ot.start_date,
                        otu.end_date = COALESCE(ot.end_date, ot.finish_date),
                        otu.start_time = ot.start_time,
                        otu.end_time = ot.end_time
                    WHERE otu.start_date IS NULL
                ");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('offline_training_users')) {
            Schema::table('offline_training_users', function (Blueprint $table) {
                $colsToDrop = [];
                foreach (['start_date', 'end_date', 'start_time', 'end_time'] as $col) {
                    if (Schema::hasColumn('offline_training_users', $col)) {
                        $colsToDrop[] = $col;
                    }
                }
                if (!empty($colsToDrop)) {
                    $table->dropColumn($colsToDrop);
                }
            });
        }
    }
};
