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
        Schema::create('assesments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->nullable()->constrained('content');
            $table->foreignId('trainingplan_id')->nullable()->constrained('trainingplan');
            $table->enum('type', ['content', 'trainingplan', 'global'])->default('global');
            $table->string('slug')->nullable();
            $table->string('title')->nullable();
            $table->string('duration')->nullable();
            $table->date('date')->nullable();
            $table->enum('status', ['OK', 'NOT'])->default('NOT');
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
        Schema::dropIfExists('assesments');
    }
};
