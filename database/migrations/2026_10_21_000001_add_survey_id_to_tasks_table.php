<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add survey_id column to tasks table for future survey integration.
     */
    public function up(): void
    {
        if (Schema::hasTable('tasks') && !Schema::hasColumn('tasks', 'survey_id')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->unsignedBigInteger('survey_id')->nullable()->after('type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tasks') && Schema::hasColumn('tasks', 'survey_id')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropColumn('survey_id');
            });
        }
    }
};
