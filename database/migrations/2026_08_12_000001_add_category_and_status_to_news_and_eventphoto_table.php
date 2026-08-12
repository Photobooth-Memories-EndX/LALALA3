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
        Schema::table('news', function (Blueprint $table) {
            if (!Schema::hasColumn('news', 'category')) {
                $table->string('category')->nullable()->after('title');
            }
            if (!Schema::hasColumn('news', 'status')) {
                $table->enum('status', ['approved', 'reject', 'pending'])->default('approved')->after('category');
            }
        });

        Schema::table('eventphoto', function (Blueprint $table) {
            if (!Schema::hasColumn('eventphoto', 'category')) {
                $table->string('category')->nullable()->after('title');
            }
            if (!Schema::hasColumn('eventphoto', 'status')) {
                $table->enum('status', ['approved', 'reject', 'pending'])->default('approved')->after('category');
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
        Schema::table('news', function (Blueprint $table) {
            if (Schema::hasColumn('news', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('news', 'category')) {
                $table->dropColumn('category');
            }
        });

        Schema::table('eventphoto', function (Blueprint $table) {
            if (Schema::hasColumn('eventphoto', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
