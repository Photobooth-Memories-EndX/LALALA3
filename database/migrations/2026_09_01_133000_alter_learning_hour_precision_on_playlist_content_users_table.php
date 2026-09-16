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
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE playlist_content_users MODIFY COLUMN learning_hour DECIMAL(10,4) NULL');
        } else {
            Schema::table('playlist_content_users', function (Blueprint $table) {
                $table->decimal('learning_hour', 10, 4)->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE playlist_content_users MODIFY COLUMN learning_hour DECIMAL(6,1) NULL');
        } else {
            Schema::table('playlist_content_users', function (Blueprint $table) {
                $table->decimal('learning_hour', 6, 1)->nullable()->change();
            });
        }
    }
};
