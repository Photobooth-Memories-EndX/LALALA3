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
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['news', 'content', 'eventphoto'])->default('content');
            $table->foreignId('content_id')->nullable()->constrained('content');
            $table->foreignId('news_id')->nullable()->constrained('news');
            $table->foreignId('eventphoto_id')->nullable()->constrained('eventphoto');
            $table->foreignId('user_id')->constrained();
            $table->text('description');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('comments');
    }
};
