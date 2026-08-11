<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('comment_likes', function (Blueprint $table) {
            if (!Schema::hasColumn('comment_likes', 'reference_type')) {
                $table->string('reference_type')->nullable();
            }

            if (!Schema::hasColumn('comment_likes', 'reference_id')) {
                $table->unsignedBigInteger('reference_id')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('comment_likes', function (Blueprint $table) {
            if (Schema::hasColumn('comment_likes', 'reference_type')) {
                $table->dropColumn('reference_type');
            }

            if (Schema::hasColumn('comment_likes', 'reference_id')) {
                $table->dropColumn('reference_id');
            }
        });
    }
};
