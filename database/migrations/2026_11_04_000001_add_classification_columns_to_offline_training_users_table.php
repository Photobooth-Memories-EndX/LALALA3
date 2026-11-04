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
        Schema::table('offline_training_users', function (Blueprint $table) {
            if (!Schema::hasColumn('offline_training_users', 'main_classification')) {
                $table->string('main_classification', 255)->nullable()->after('company');
                $table->index('main_classification', 'offline_training_users_main_classification_index');
            }
            if (!Schema::hasColumn('offline_training_users', 'sub_classification')) {
                $table->string('sub_classification', 255)->nullable()->after('main_classification');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offline_training_users', function (Blueprint $table) {
            $colsToDrop = [];
            if (Schema::hasColumn('offline_training_users', 'main_classification')) {
                $table->dropIndex('offline_training_users_main_classification_index');
                $colsToDrop[] = 'main_classification';
            }
            if (Schema::hasColumn('offline_training_users', 'sub_classification')) {
                $colsToDrop[] = 'sub_classification';
            }
            if (!empty($colsToDrop)) {
                $table->dropColumn($colsToDrop);
            }
        });
    }
};
