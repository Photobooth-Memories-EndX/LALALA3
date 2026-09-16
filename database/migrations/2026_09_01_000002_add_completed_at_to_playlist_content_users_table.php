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
    public function up()
    {
        Schema::table('playlist_content_users', function (Blueprint $table) {
            if (!Schema::hasColumn('playlist_content_users', 'completed_at')) {
                $table->timestamp('completed_at')
                    ->nullable()
                    ->after('learning_hour')
                    ->comment('Timestamp when user completed the playlist content');

                $table->index('completed_at', 'playlist_content_users_completed_at_idx');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('playlist_content_users', function (Blueprint $table) {
            if (Schema::hasColumn('playlist_content_users', 'completed_at')) {
                $table->dropIndex('playlist_content_users_completed_at_idx');
                $table->dropColumn('completed_at');
            }
        });
    }
};
