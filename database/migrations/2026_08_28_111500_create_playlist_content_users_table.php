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
        if (!Schema::hasTable('playlist_content_users')) {
            Schema::create('playlist_content_users', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('trainingplan_id')->constrained('trainingplan')->cascadeOnDelete();
                $table->foreignId('content_id')->constrained('content')->cascadeOnDelete();
                $table->integer('progress')->default(0);
                $table->unsignedInteger('last_page')->nullable();
                $table->unsignedInteger('total_pages')->nullable();
                $table->integer('current_time')->default(0);
                $table->integer('duration_seconds')->nullable();
                $table->decimal('learning_hour', 6, 1)->nullable();
                $table->string('learning_status', 20)->default('not_started');
                $table->enum('finish', ['0', '1'])->default('0');
                $table->timestamps();

                $table->unique(['user_id', 'trainingplan_id', 'content_id'], 'unique_user_playlist_content');
                $table->index(['trainingplan_id', 'content_id'], 'idx_pcu_playlist_content');
                $table->index('content_id', 'idx_pcu_content_id');
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
        Schema::dropIfExists('playlist_content_users');
    }
};
