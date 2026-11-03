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
            if (!Schema::hasColumn('offline_training_users', 'certificate_issued_date')) {
                $table->date('certificate_issued_date')->nullable()->after('certificate');
            }
            if (!Schema::hasColumn('offline_training_users', 'certificate_expired_date')) {
                $table->date('certificate_expired_date')->nullable()->after('certificate_issued_date');
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
            if (Schema::hasColumn('offline_training_users', 'certificate_expired_date')) {
                $colsToDrop[] = 'certificate_expired_date';
            }
            if (Schema::hasColumn('offline_training_users', 'certificate_issued_date')) {
                $colsToDrop[] = 'certificate_issued_date';
            }
            if (!empty($colsToDrop)) {
                $table->dropColumn($colsToDrop);
            }
        });
    }
};
