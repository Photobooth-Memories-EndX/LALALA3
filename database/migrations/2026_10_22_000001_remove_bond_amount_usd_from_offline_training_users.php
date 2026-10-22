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
        if (Schema::hasTable('offline_training_users') && Schema::hasColumn('offline_training_users', 'bond_amount_usd')) {
            Schema::table('offline_training_users', function (Blueprint $table) {
                $table->dropColumn('bond_amount_usd');
            });
        }

        if (Schema::hasTable('offline_trainings') && Schema::hasColumn('offline_trainings', 'bond_amount_usd')) {
            Schema::table('offline_trainings', function (Blueprint $table) {
                $table->dropColumn('bond_amount_usd');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('offline_training_users') && !Schema::hasColumn('offline_training_users', 'bond_amount_usd')) {
            Schema::table('offline_training_users', function (Blueprint $table) {
                $table->decimal('bond_amount_usd', 12, 2)->nullable()->after('bond_amount_idr');
            });
        }

        if (Schema::hasTable('offline_trainings') && !Schema::hasColumn('offline_trainings', 'bond_amount_usd')) {
            Schema::table('offline_trainings', function (Blueprint $table) {
                $table->decimal('bond_amount_usd', 12, 2)->nullable()->after('bond_amount_idr');
            });
        }
    }
};
