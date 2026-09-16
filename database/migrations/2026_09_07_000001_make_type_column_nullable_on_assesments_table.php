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
            DB::statement("ALTER TABLE assesments MODIFY COLUMN type ENUM('content', 'trainingplan', 'global') NULL DEFAULT 'global'");
        } else {
            Schema::table('assesments', function (Blueprint $table) {
                $table->string('type')->default('global')->nullable()->change();
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
            DB::statement("UPDATE assesments SET type = 'global' WHERE type IS NULL");
            DB::statement("ALTER TABLE assesments MODIFY COLUMN type ENUM('content', 'trainingplan', 'global') NOT NULL DEFAULT 'global'");
        } else {
            Schema::table('assesments', function (Blueprint $table) {
                $table->string('type')->default('global')->nullable(false)->change();
            });
        }
    }
};