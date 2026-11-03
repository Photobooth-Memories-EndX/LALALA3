<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * File-upload questions may optionally accept a Drive link
 * as an alternative to uploading files (per-question setting).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('task_fields') && !Schema::hasColumn('task_fields', 'allow_link')) {
            Schema::table('task_fields', function (Blueprint $table) {
                $table->boolean('allow_link')->default(false)->after('max_size_mb');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('task_fields') && Schema::hasColumn('task_fields', 'allow_link')) {
            Schema::table('task_fields', function (Blueprint $table) {
                $table->dropColumn('allow_link');
            });
        }
    }
};
