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
        if (!Schema::hasTable('assesment_score_histories')) {
            Schema::create('assesment_score_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assesment_result_id')->constrained('assesment_results')->onDelete('cascade');
            $table->foreignId('assesment_id')->constrained('assesments')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->double('previous_score', 8, 2)->nullable();
            $table->double('new_score', 8, 2);
            $table->timestamps();

            $table->index(['assesment_result_id', 'created_at']);
            $table->index('assesment_id');
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
        Schema::dropIfExists('assesment_score_histories');
    }
};
