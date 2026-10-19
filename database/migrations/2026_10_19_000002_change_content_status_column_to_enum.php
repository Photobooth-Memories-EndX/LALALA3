<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
            // Ensure no invalid values exist before altering to strict ENUM('mandatory', 'non mandatory')
            DB::statement("
                ALTER TABLE content
                MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'non mandatory'
            ");

            DB::statement("
                UPDATE content
                SET status = 'non mandatory'
                WHERE status = 'optional' OR status IS NULL OR status NOT IN ('mandatory', 'non mandatory')
            ");

            DB::statement("
                ALTER TABLE content
                MODIFY COLUMN status ENUM('mandatory', 'non mandatory') DEFAULT 'non mandatory'
            ");
        }

        if (Schema::hasTable('content_users') && Schema::hasColumn('content_users', 'status')) {
            DB::statement("
                ALTER TABLE content_users
                MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'non mandatory'
            ");

            DB::statement("
                UPDATE content_users
                SET status = 'non mandatory'
                WHERE status = 'optional' OR status IS NULL OR status NOT IN ('mandatory', 'non mandatory')
            ");

            DB::statement("
                ALTER TABLE content_users
                MODIFY COLUMN status ENUM('mandatory', 'non mandatory') DEFAULT 'non mandatory'
            ");
        }

        if (Schema::hasTable('playlist_content_users') && Schema::hasColumn('playlist_content_users', 'status')) {
            DB::statement("
                ALTER TABLE playlist_content_users
                MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'non mandatory'
            ");

            DB::statement("
                UPDATE playlist_content_users
                SET status = 'non mandatory'
                WHERE status = 'optional' OR status IS NULL OR status NOT IN ('mandatory', 'non mandatory')
            ");

            DB::statement("
                ALTER TABLE playlist_content_users
                MODIFY COLUMN status ENUM('mandatory', 'non mandatory') DEFAULT 'non mandatory'
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
                MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'optional'
            ");

            DB::statement("
                UPDATE content SET status = 'optional' WHERE status = 'non mandatory' OR status IS NULL
            ");

            DB::statement("
                ALTER TABLE content
                MODIFY COLUMN status ENUM('mandatory', 'optional') DEFAULT 'optional'
            ");
        }
    }
};
