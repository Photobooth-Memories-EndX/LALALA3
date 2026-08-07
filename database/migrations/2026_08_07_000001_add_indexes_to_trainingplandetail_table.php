<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trainingplandetail', function (Blueprint $table) {
            $table->index('trainingplan_id', 'idx_trainingplandetail_trainingplan_id');
            $table->index('content_id', 'idx_trainingplandetail_content_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trainingplandetail', function (Blueprint $table) {
            $table->dropIndex('idx_trainingplandetail_trainingplan_id');
            $table->dropIndex('idx_trainingplandetail_content_id');
        });
    }
};
