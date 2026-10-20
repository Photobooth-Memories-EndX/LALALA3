<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('content', function (Blueprint $table) {
            if (!Schema::hasColumn('content', 'status')) {
                $table->enum('status', ['mandatory', 'optional'])
                    ->default('optional')
                    ->after('playlistonly');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('content', function (Blueprint $table) {
            if (Schema::hasColumn('content', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
