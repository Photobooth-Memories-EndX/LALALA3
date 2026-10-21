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
    public function up(): void
    {
        if (Schema::hasTable('offline_training_users')) {
            Schema::table('offline_training_users', function (Blueprint $table) {
                if (Schema::hasColumn('offline_training_users', 'bond_amount') && !Schema::hasColumn('offline_training_users', 'bond_amount_idr')) {
                    $table->renameColumn('bond_amount', 'bond_amount_idr');
                }
            });

            Schema::table('offline_training_users', function (Blueprint $table) {
                if (!Schema::hasColumn('offline_training_users', 'bond_amount_usd')) {
                    $table->decimal('bond_amount_usd', 12, 2)->nullable()->after('bond_amount_idr');
                }
            });
        }

        if (Schema::hasTable('offline_trainings')) {
            Schema::table('offline_trainings', function (Blueprint $table) {
                if (Schema::hasColumn('offline_trainings', 'bond_amount') && !Schema::hasColumn('offline_trainings', 'bond_amount_idr')) {
                    $table->renameColumn('bond_amount', 'bond_amount_idr');
                }
            });

            Schema::table('offline_trainings', function (Blueprint $table) {
                if (!Schema::hasColumn('offline_trainings', 'bond_amount_usd')) {
                    if (Schema::hasColumn('offline_trainings', 'bond_amount_idr')) {
                        $table->decimal('bond_amount_usd', 12, 2)->nullable()->after('bond_amount_idr');
                    } else {
                        $table->decimal('bond_amount_idr', 15, 2)->nullable();
                        $table->decimal('bond_amount_usd', 12, 2)->nullable()->after('bond_amount_idr');
                    }
                }
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
        if (Schema::hasTable('offline_training_users')) {
            Schema::table('offline_training_users', function (Blueprint $table) {
                if (Schema::hasColumn('offline_training_users', 'bond_amount_usd')) {
                    $table->dropColumn('bond_amount_usd');
                }
                if (Schema::hasColumn('offline_training_users', 'bond_amount_idr')) {
                    $table->renameColumn('bond_amount_idr', 'bond_amount');
                }
            });
        }

        if (Schema::hasTable('offline_trainings')) {
            Schema::table('offline_trainings', function (Blueprint $table) {
                if (Schema::hasColumn('offline_trainings', 'bond_amount_usd')) {
                    $table->dropColumn('bond_amount_usd');
                }
                if (Schema::hasColumn('offline_trainings', 'bond_amount_idr')) {
                    $table->renameColumn('bond_amount_idr', 'bond_amount');
                }
            });
        }
    }
};
