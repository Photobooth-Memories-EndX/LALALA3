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
        Schema::create('eventphoto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title')->nullable();
            $table->date('eventdate')->nullable();
            $table->text('photos')->nullable();
            $table->string('likes')->nullable();
            $table->string('slug')->nullable();
            $table->string('uploader')->nullable();
            $table->string('views')->nullable();
            $table->string('thumbnail')->nullable();
            $table->enum('active', ['Active', 'Inactive'])->default('Inactive');
            $table->softDeletes();
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
        Schema::dropIfExists('eventphoto');
    }
};
