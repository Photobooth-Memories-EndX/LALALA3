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
                if (!Schema::hasColumn('offline_trainings', 'course_completion_requirement')) {
                    $table->text('course_completion_requirement')->nullable()->after('prerequisites');
                }
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
                if (Schema::hasColumn('offline_trainings', 'course_completion_requirement')) {
                    $table->dropColumn('course_completion_requirement');
                }
            });
        }
    }
};
