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
            if (!Schema::hasColumn('assesments', 'content_for')) {
                $table->enum('content_for', ['content', 'playlist'])->default('content')->after('type');
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
        Schema::table('assesments', function (Blueprint $table) {
            if (Schema::hasColumn('assesments', 'content_for')) {
                $table->dropColumn('content_for');
            }
        });
    }
};
