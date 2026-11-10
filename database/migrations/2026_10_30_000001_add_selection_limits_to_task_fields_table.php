<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('task_fields')) {
            Schema::table('task_fields', function (Blueprint $table) {
                if (!Schema::hasColumn('task_fields', 'min_select')) {
                    $table->unsignedInteger('min_select')->nullable()->after('max_files');
                }
                if (!Schema::hasColumn('task_fields', 'max_select')) {
                    $table->unsignedInteger('max_select')->nullable()->after('min_select');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('task_fields')) {
            Schema::table('task_fields', function (Blueprint $table) {
                $columns = array_filter(['min_select', 'max_select'], function ($col) {
                    return Schema::hasColumn('task_fields', $col);
                });
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};

