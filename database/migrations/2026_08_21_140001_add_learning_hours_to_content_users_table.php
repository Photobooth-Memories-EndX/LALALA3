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
        Schema::table('content_users', function (Blueprint $table) {
            if (!Schema::hasColumn('content_users', 'learning_hours')) {
                $table->decimal('learning_hours', 6, 1)
                    ->nullable()
                    ->after('duration_seconds')
                    ->comment('Calculated learning hours derived from content duration');
                
                $table->index('learning_hours', 'content_users_learning_hours_idx');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('content_users', function (Blueprint $table) {
            if (Schema::hasColumn('content_users', 'learning_hours')) {
                $table->dropIndex('content_users_learning_hours_idx');
                $table->dropColumn('learning_hours');
            }
        });
    }
};
