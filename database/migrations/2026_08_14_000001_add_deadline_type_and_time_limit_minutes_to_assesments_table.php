<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::table('assesments', function (Blueprint $table) {
            $table->enum('deadline_type', ['period', 'time_limit', 'none'])->default('period')->after('timer');
            $table->integer('time_limit_minutes')->nullable()->after('deadline_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assesments', function (Blueprint $table) {
            $table->dropColumn(['deadline_type', 'time_limit_minutes']);
        });
    }
};
