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
        Schema::create('content', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('type')->nullable();
            $table->string('classification_id')->nullable();
            $table->string('duration')->nullable();
            $table->date('date')->nullable();
            $table->text('description')->nullable();
            $table->string('author')->nullable();
            $table->string('slug')->nullable();
            $table->string('file')->nullable();
            $table->text('link')->nullable();
            $table->enum('exam', ['OK', 'NOT'])->default('NOT');
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
        Schema::dropIfExists('content');
    }
};
