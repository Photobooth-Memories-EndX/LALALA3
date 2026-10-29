<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'id_emp')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('id_emp', 'users_id_emp_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'id_emp')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('users_id_emp_index');
            });
        }
    }
};
