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
        if (!Schema::hasColumn('offline_trainings', 'requirement')) {
            Schema::table('offline_trainings', function (Blueprint $table) {
                $table->enum('requirement', ['mandatory', 'non mandatory'])
                      ->default('non mandatory')
                      ->after('status');
            });
        }

        DB::table('offline_trainings')
            ->whereNull('requirement')
            ->orWhereNotIn('requirement', ['mandatory', 'non mandatory'])
            ->update(['requirement' => 'non mandatory']);

        DB::table('offline_trainings')
            ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = 'mandatory'")
            ->update(['requirement' => 'mandatory']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('offline_trainings', 'requirement')) {
            Schema::table('offline_trainings', function (Blueprint $table) {
                $table->dropColumn('requirement');
            });
        }
    }
};
