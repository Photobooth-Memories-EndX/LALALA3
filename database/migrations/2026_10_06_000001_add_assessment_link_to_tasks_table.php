<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Assessment/Quiz tasks no longer use the manual question builder.
 * The admin picks an existing LMS assessment (assesments table) instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tasks') && !Schema::hasColumn('tasks', 'assessment_id')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->foreignId('assessment_id')->nullable()->after('type')
                    ->constrained('assesments')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tasks') && Schema::hasColumn('tasks', 'assessment_id')) {
            Schema::table('tasks', function (Blueprint $table) {
                try {
                    $table->dropConstrainedForeignId('assessment_id');
                } catch (\Throwable $e) {
                    $table->dropColumn('assessment_id');
                }
            });
        }
    }
};
