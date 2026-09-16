<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Exactly one single migration adding AFK tracking and configuration columns
     * to the audited tables in the LMS system.
     *
     * @return void
     */
    public function up(): void
    {
        // 1. Dynamic AFK Inactivity Threshold Configuration on Assesments table
        if (Schema::hasTable('assesments')) {
            Schema::table('assesments', function (Blueprint $table) {
                if (!Schema::hasColumn('assesments', 'afk_threshold_seconds')) {
                    $table->unsignedInteger('afk_threshold_seconds')
                        ->nullable()
                        ->default(60)
                        ->after('time_limit_minutes')
                        ->comment('Inactivity threshold in seconds before candidate is flagged as AFK');
                }
            });
        }

        // 2. Actual Cumulative AFK Duration on Content Users (Standalone Content Tracking)
        if (Schema::hasTable('content_users')) {
            Schema::table('content_users', function (Blueprint $table) {
                if (!Schema::hasColumn('content_users', 'afk_duration_seconds')) {
                    $table->unsignedInteger('afk_duration_seconds')
                        ->default(0)
                        ->after('duration_seconds')
                        ->comment('Cumulative AFK duration in seconds spent by user on this content');
                }
            });
        }

        // 3. Actual Cumulative AFK Duration on Playlist Content Users (Playlist-scoped Tracking)
        if (Schema::hasTable('playlist_content_users')) {
            Schema::table('playlist_content_users', function (Blueprint $table) {
                if (!Schema::hasColumn('playlist_content_users', 'afk_duration_seconds')) {
                    $table->unsignedInteger('afk_duration_seconds')
                        ->default(0)
                        ->after('duration_seconds')
                        ->comment('Cumulative AFK duration in seconds spent by user on this playlist content');
                }
            });
        }

        // 4. Attempt-level Actual AFK Duration on Assesment Results (Idempotent Attempt Tracking)
        if (Schema::hasTable('assesment_results')) {
            Schema::table('assesment_results', function (Blueprint $table) {
                if (!Schema::hasColumn('assesment_results', 'afk_duration_seconds')) {
                    $table->unsignedInteger('afk_duration_seconds')
                        ->default(0)
                        ->after('user_answer')
                        ->comment('Cumulative AFK duration in seconds recorded for this assessment attempt');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        if (Schema::hasTable('assesment_results')) {
            Schema::table('assesment_results', function (Blueprint $table) {
                if (Schema::hasColumn('assesment_results', 'afk_duration_seconds')) {
                    $table->dropColumn('afk_duration_seconds');
                }
            });
        }

        if (Schema::hasTable('playlist_content_users')) {
            Schema::table('playlist_content_users', function (Blueprint $table) {
                if (Schema::hasColumn('playlist_content_users', 'afk_duration_seconds')) {
                    $table->dropColumn('afk_duration_seconds');
                }
            });
        }

        if (Schema::hasTable('content_users')) {
            Schema::table('content_users', function (Blueprint $table) {
                if (Schema::hasColumn('content_users', 'afk_duration_seconds')) {
                    $table->dropColumn('afk_duration_seconds');
                }
            });
        }

        if (Schema::hasTable('assesments')) {
            Schema::table('assesments', function (Blueprint $table) {
                if (Schema::hasColumn('assesments', 'afk_threshold_seconds')) {
                    $table->dropColumn('afk_threshold_seconds');
                }
            });
        }
    }
};
