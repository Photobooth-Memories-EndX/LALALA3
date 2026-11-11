<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_trash_hidden', function (Blueprint $table) {
            $table->id();
            $table->string('item_type', 50); // 'task', 'course'
            $table->string('course_type', 50)->nullable(); // 'playlist', 'content', 'offline_training', 'standalone'
            $table->unsignedBigInteger('item_id');
            $table->timestamps();

            $table->index(['item_type', 'item_id']);
            $table->index(['item_type', 'course_type', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_trash_hidden');
    }
};
