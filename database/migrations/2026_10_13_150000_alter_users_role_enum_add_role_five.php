<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE users
            MODIFY role ENUM('1','2','3','4','5') NOT NULL DEFAULT '1'
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        DB::table('users')
            ->where('role', '5')
            ->update(['role' => '1']);

        DB::statement("
            ALTER TABLE users
            MODIFY role ENUM('1','2','3','4') NOT NULL DEFAULT '1'
        ");
    }
};
