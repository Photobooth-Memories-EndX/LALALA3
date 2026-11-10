<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('certificate_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('certificate_settings', 'offline_training_id')) {
                $table->unsignedBigInteger('offline_training_id')
                    ->nullable()
                    ->after('trainingplan_id')
                    ->index();

                $table->foreign('offline_training_id')
                    ->references('id')
                    ->on('offline_trainings')
                    ->cascadeOnDelete();
            }
        });

        Schema::table('certificates', function (Blueprint $table) {
            if (!Schema::hasColumn('certificates', 'offline_training_user_id')) {
                $table->unsignedBigInteger('offline_training_user_id')
                    ->nullable()
                    ->after('content_user_id')
                    ->index();

                $table->foreign('offline_training_user_id')
                    ->references('id')
                    ->on('offline_training_users')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certificate_settings', function (Blueprint $table) {
            if (Schema::hasColumn('certificate_settings', 'offline_training_id')) {
                $table->dropForeign(['offline_training_id']);
                $table->dropColumn('offline_training_id');
            }
        });

        Schema::table('certificates', function (Blueprint $table) {
            if (Schema::hasColumn('certificates', 'offline_training_user_id')) {
                $table->dropForeign(['offline_training_user_id']);
                $table->dropColumn('offline_training_user_id');
            }
        });
    }
};
