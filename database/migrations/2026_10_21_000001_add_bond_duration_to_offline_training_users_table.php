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
            if (!Schema::hasColumn('offline_training_users', 'bond_duration')) {
                $table->integer('bond_duration')->nullable()->after('bond_status')->comment('Duration in months');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offline_training_users', function (Blueprint $table) {
            if (Schema::hasColumn('offline_training_users', 'bond_duration')) {
                $table->dropColumn('bond_duration');
            }
        });
    }
};
