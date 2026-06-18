<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_access_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('module_key');
            $table->boolean('can_access')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'module_key']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_access_permissions');
    }
};