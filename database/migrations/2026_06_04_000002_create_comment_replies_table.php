<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('comment_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('comment_id')->constrained('comments')->cascadeOnDelete();
            $table->enum('reference_type', ['content', 'news', 'gallery']);
            $table->unsignedBigInteger('reference_id');
            $table->text('reply');
            $table->boolean('is_edited')->default(false);
            $table->timestamps();

            $table->index(['reference_type', 'reference_id'], 'comment_replies_reference_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('comment_replies');
    }
};
