<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content', function (Blueprint $table) {
            $table->unsignedInteger('page_duration')
                ->default(30)
                ->comment('Minimum viewing time per page (seconds)')
                ->after('duration');
        });
    }

    public function down(): void
    {
        Schema::table('content', function (Blueprint $table) {
            $table->dropColumn('page_duration');
        });
    }
};
