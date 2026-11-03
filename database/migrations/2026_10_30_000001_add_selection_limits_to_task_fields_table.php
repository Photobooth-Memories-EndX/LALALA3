<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_fields', function (Blueprint $table) {
            $table->unsignedInteger('min_select')->nullable()->after('max_files');
            $table->unsignedInteger('max_select')->nullable()->after('min_select');
        });
    }

    public function down(): void
    {
        Schema::table('task_fields', function (Blueprint $table) {
            $table->dropColumn(['min_select', 'max_select']);
        });
    }
};

