<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE content MODIFY COLUMN learning_hours DECIMAL(8,4) NULL');
            DB::statement('ALTER TABLE trainingplan MODIFY COLUMN learning_hours DECIMAL(8,4) NULL');
        } else {
            Schema::table('content', function (Blueprint $table) {
                $table->decimal('learning_hours', 8, 4)->nullable()->change();
            });
            Schema::table('trainingplan', function (Blueprint $table) {
                $table->decimal('learning_hours', 8, 4)->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE content MODIFY COLUMN learning_hours DECIMAL(4,1) NULL');
            DB::statement('ALTER TABLE trainingplan MODIFY COLUMN learning_hours DECIMAL(8,2) NULL');
        } else {
            Schema::table('content', function (Blueprint $table) {
                $table->decimal('learning_hours', 4, 1)->nullable()->change();
            });
            Schema::table('trainingplan', function (Blueprint $table) {
                $table->decimal('learning_hours', 8, 2)->nullable()->change();
            });
        }
    }
};
