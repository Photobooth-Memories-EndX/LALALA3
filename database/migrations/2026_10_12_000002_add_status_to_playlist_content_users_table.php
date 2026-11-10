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
        Schema::table('playlist_content_users', function (Blueprint $table) {
            if (!Schema::hasColumn('playlist_content_users', 'status')) {
                $table->enum('status', ['mandatory', 'optional'])
                    ->default('optional')
                    ->after('learning_status');
                $table->index('status', 'idx_pcu_status');
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
        Schema::table('playlist_content_users', function (Blueprint $table) {
            if (Schema::hasColumn('playlist_content_users', 'status')) {
                $table->dropIndex('idx_pcu_status');
                $table->dropColumn('status');
            }
        });
    }
};
