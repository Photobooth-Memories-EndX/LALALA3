<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds auto learning progress tracking columns to content_users.
     * NOTE: `status` already exists with different semantics (optional/mandatory),
     * so the learning state lives in a new `learning_status` column.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('content_users', function (Blueprint $table) {
            if (!Schema::hasColumn('content_users', 'last_page')) {
                $table->unsignedInteger('last_page')->nullable()->after('progress');
            }

            if (!Schema::hasColumn('content_users', 'total_pages')) {
                $table->unsignedInteger('total_pages')->nullable()->after('last_page');
            }

            if (!Schema::hasColumn('content_users', 'current_time')) {
                $table->integer('current_time')->default(0)->after('total_pages');
            }

            if (!Schema::hasColumn('content_users', 'duration_seconds')) {
                $table->integer('duration_seconds')->nullable()->after('current_time');
            }

            if (!Schema::hasColumn('content_users', 'learning_status')) {
                $table->string('learning_status', 20)->default('not_started')->after('duration_seconds');
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
        Schema::table('content_users', function (Blueprint $table) {
            foreach (['last_page', 'total_pages', 'current_time', 'duration_seconds', 'learning_status'] as $column) {
                if (Schema::hasColumn('content_users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
