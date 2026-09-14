<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds actual_watch_seconds column to content_users and playlist_content_users
     * for tracking accumulated actual video watch time (independent of playhead current_time).
     */
    public function up(): void
    {
        if (Schema::hasTable('content_users')) {
            Schema::table('content_users', function (Blueprint $table) {
                if (!Schema::hasColumn('content_users', 'actual_watch_seconds')) {
                    $table->unsignedInteger('actual_watch_seconds')
                        ->default(0)
                        ->after('duration_seconds')
                        ->comment('Accumulated actual video watch duration in seconds');
                }
            });
        }

        if (Schema::hasTable('playlist_content_users')) {
            Schema::table('playlist_content_users', function (Blueprint $table) {
                if (!Schema::hasColumn('playlist_content_users', 'actual_watch_seconds')) {
                    $table->unsignedInteger('actual_watch_seconds')
                        ->default(0)
                        ->after('duration_seconds')
                        ->comment('Accumulated actual video watch duration in seconds');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('content_users')) {
            Schema::table('content_users', function (Blueprint $table) {
                if (Schema::hasColumn('content_users', 'actual_watch_seconds')) {
                    $table->dropColumn('actual_watch_seconds');
                }
            });
        }

        if (Schema::hasTable('playlist_content_users')) {
            Schema::table('playlist_content_users', function (Blueprint $table) {
                if (Schema::hasColumn('playlist_content_users', 'actual_watch_seconds')) {
                    $table->dropColumn('actual_watch_seconds');
                }
            });
        }
    }
};
