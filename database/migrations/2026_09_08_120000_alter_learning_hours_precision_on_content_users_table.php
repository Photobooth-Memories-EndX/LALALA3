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
    public function up()
    {
        if (Schema::hasColumn('content_users', 'learning_hours')) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE content_users MODIFY COLUMN learning_hours DECIMAL(10,4) NULL');
            } else {
                Schema::table('content_users', function (Blueprint $table) {
                    $table->decimal('learning_hours', 10, 4)->nullable()->change();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('content_users', 'learning_hours')) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE content_users MODIFY COLUMN learning_hours DECIMAL(6,1) NULL');
            } else {
                Schema::table('content_users', function (Blueprint $table) {
                    $table->decimal('learning_hours', 6, 1)->nullable()->change();
                });
            }
        }
    }
};
