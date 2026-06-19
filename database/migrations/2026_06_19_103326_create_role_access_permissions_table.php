<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('role_access_permissions')) {
            Schema::create('role_access_permissions', function (Blueprint $table) {
                $table->id();

                $table->string('role');
                $table->string('module_key');

                $table->boolean('can_access')->default(false);

                $table->timestamps();

                $table->unique([
                    'role',
                    'module_key'
                ]);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('role_access_permissions');
    }
};
