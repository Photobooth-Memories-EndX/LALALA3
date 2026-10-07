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
        Schema::table('offline_trainings', function (Blueprint $table) {
            if (!Schema::hasColumn('offline_trainings', 'registration_close_date')) {
                $table->date('registration_close_date')->nullable()->after('end_date')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('offline_trainings', function (Blueprint $table) {
            if (Schema::hasColumn('offline_trainings', 'registration_close_date')) {
                $table->dropColumn('registration_close_date');
            }
        });
    }
};
