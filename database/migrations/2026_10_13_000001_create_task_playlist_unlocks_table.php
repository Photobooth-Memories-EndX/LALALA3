<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_playlist_unlocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('training_plan_id')->index();
            $table->timestamps();
            $table->unique(['user_id', 'training_plan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_playlist_unlocks');
    }
};
