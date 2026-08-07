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
        if (!Schema::hasColumn('trainingplan', 'learning_hours')) {
            Schema::table('trainingplan', function (Blueprint $table) {
                $table->decimal('learning_hours', 8, 2)
                      ->nullable()
                      ->after('total_duration_seconds');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('trainingplan', 'learning_hours')) {
            Schema::table('trainingplan', function (Blueprint $table) {
                $table->dropColumn('learning_hours');
            });
        }
    }
};
