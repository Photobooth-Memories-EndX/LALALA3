<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tambahkan role '4' (Subadmin Division) ke kolom enum users.role.
     * Memakai raw statement karena Doctrine DBAL tidak menangani perubahan
     * tipe ENUM dengan baik.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('1','2','3','4') NOT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Kembalikan user role 4 ke role 1 agar tidak melanggar enum lama.
        DB::statement("UPDATE users SET role = '1' WHERE role = '4'");
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('1','2','3') NOT NULL");
    }
};
