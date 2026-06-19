<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE users
            MODIFY role ENUM('1','2','3','4') NOT NULL DEFAULT '1'
        ");
    }

    public function down(): void
    {
        DB::statement("
            UPDATE users SET role = '1' WHERE role = '4'
        ");

        DB::statement("
            ALTER TABLE users
            MODIFY role ENUM('1','2','3') NOT NULL DEFAULT '1'
        ");
    }
};
