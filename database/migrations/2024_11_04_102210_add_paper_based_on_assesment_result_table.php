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
        Schema::table('assesment_results', function (Blueprint $table) {
            $table->boolean('paper_based')->default(false)->after('pass');
            $table->text('evidence_paper_based')->nullable()->after('paper_based');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assesment_results', function (Blueprint $table) {
            $table->dropColumn('paper_based');
            $table->dropColumn('evidence_paper_based');
        });
    }
};
