<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->enum('reference_type', ['content', 'news', 'gallery'])->nullable()->after('parent_id');
            $table->unsignedBigInteger('reference_id')->nullable()->after('reference_type');
            $table->boolean('is_edited')->default(false)->after('reference_id');
            $table->index(['reference_type', 'reference_id'], 'comments_reference_index');
        });
    }

    public function down()
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex('comments_reference_index');
            $table->dropColumn(['reference_type', 'reference_id', 'is_edited']);
        });
    }
};
