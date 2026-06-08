<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('bookmark_contents')) {

            Schema::create('bookmark_contents', function (Blueprint $table) {
                $table->id();

                $table->foreignId('user_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->foreignId('content_id')
                    ->constrained('content')
                    ->cascadeOnDelete();

                $table->timestamps();

                $table->unique([
                    'user_id',
                    'content_id'
                ]);
            });

        }
    }

    public function down()
    {
        Schema::dropIfExists('bookmark_contents');
    }
};
