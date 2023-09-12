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
        Schema::create('assesment_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assesment_id')->constrained()->onDelete('cascade');
            $table->text('question')->nullable();
            $table->string('image', 255)->nullable();
            $table->json('option');
            $table->string('answer')->nullable();
            $table->enum('status', ['0', '1'])->default('1');
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
        Schema::dropIfExists('assesment_questions');
    }
};
