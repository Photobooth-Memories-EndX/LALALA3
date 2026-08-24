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
        if (!Schema::hasTable('learning_hour_calculation_batches')) {
            Schema::create('learning_hour_calculation_batches', function (Blueprint $table) {
                $table->id();
                $table->string('type', 50)->default('all_remaining'); // all_remaining, recalculate_all, selected
                $table->string('status', 30)->default('pending'); // pending, processing, completed, failed, cancelled
                $table->unsignedBigInteger('total_records')->default(0);
                $table->unsignedBigInteger('processed_records')->default(0);
                $table->unsignedBigInteger('failed_records')->default(0);
                $table->unsignedBigInteger('last_processed_id')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();

                $table->index('status', 'lh_batches_status_idx');
                $table->index('created_at', 'lh_batches_created_at_idx');
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
        Schema::dropIfExists('learning_hour_calculation_batches');
    }
};
