<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
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
        if (Schema::hasTable('content') && Schema::hasColumn('content', 'status')) {
            // 1. Expand enum temporarily so 'optional' can be updated to 'non mandatory' without truncation
            DB::statement("
                ALTER TABLE content
                MODIFY COLUMN status ENUM('mandatory', 'non mandatory', 'optional') NOT NULL DEFAULT 'non mandatory'
            ");

            // 2. Convert any 'optional' or NULL records to 'non mandatory'
            DB::statement("
                UPDATE content
                SET status = 'non mandatory'
                WHERE status = 'optional' OR status IS NULL OR status NOT IN ('mandatory', 'non mandatory')
            ");

            // 3. Restrict column strictly to ENUM('mandatory', 'non mandatory')
            DB::statement("
                ALTER TABLE content
                MODIFY COLUMN status ENUM('mandatory', 'non mandatory') DEFAULT 'non mandatory'
            ");
        }

        if (Schema::hasTable('content_users') && Schema::hasColumn('content_users', 'status')) {
            DB::statement("
                ALTER TABLE content_users
                MODIFY COLUMN status ENUM('mandatory', 'non mandatory', 'optional') NOT NULL DEFAULT 'non mandatory'
            ");

            DB::statement("
                UPDATE content_users
                SET status = 'non mandatory'
                WHERE status = 'optional' OR status IS NULL OR status NOT IN ('mandatory', 'non mandatory')
            ");

            DB::statement("
                ALTER TABLE content_users
                MODIFY COLUMN status ENUM('mandatory', 'non mandatory') NOT NULL DEFAULT 'non mandatory'
            ");
        }

        if (Schema::hasTable('playlist_content_users') && Schema::hasColumn('playlist_content_users', 'status')) {
            DB::statement("
                ALTER TABLE playlist_content_users
                MODIFY COLUMN status ENUM('mandatory', 'non mandatory', 'optional') NOT NULL DEFAULT 'non mandatory'
            ");

            DB::statement("
                UPDATE playlist_content_users
                SET status = 'non mandatory'
                WHERE status = 'optional' OR status IS NULL OR status NOT IN ('mandatory', 'non mandatory')
            ");

            DB::statement("
                ALTER TABLE playlist_content_users
                MODIFY COLUMN status ENUM('mandatory', 'non mandatory') NOT NULL DEFAULT 'non mandatory'
            ");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        if (Schema::hasTable('content') && Schema::hasColumn('content', 'status')) {
            DB::statement("
                ALTER TABLE content
                MODIFY COLUMN status ENUM('mandatory', 'non mandatory', 'optional') NOT NULL DEFAULT 'optional'
            ");

            DB::statement("
                UPDATE content SET status = 'optional' WHERE status = 'non mandatory' OR status IS NULL
            ");

            DB::statement("
                ALTER TABLE content
                MODIFY COLUMN status ENUM('mandatory', 'optional') NOT NULL DEFAULT 'optional'
            ");
        }
    }
};
