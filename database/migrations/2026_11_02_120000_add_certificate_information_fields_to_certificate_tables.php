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
            if (!Schema::hasColumn('certificate_settings', 'institution')) {
                $table->string('institution')->nullable()->default('PT KRAKATAU POSCO')->after('issuer_name');
            }
            if (!Schema::hasColumn('certificate_settings', 'issued_date')) {
                $table->date('issued_date')->nullable()->after('institution');
            }
            if (!Schema::hasColumn('certificate_settings', 'expired_date')) {
                $table->date('expired_date')->nullable()->after('issued_date');
            }
        });

        Schema::table('certificates', function (Blueprint $table) {
            if (!Schema::hasColumn('certificates', 'institution')) {
                $table->string('institution')->nullable()->after('participant_name');
            }
            if (!Schema::hasColumn('certificates', 'issued_date')) {
                $table->date('issued_date')->nullable()->after('issued_at');
            }
            if (!Schema::hasColumn('certificates', 'expired_date')) {
                $table->date('expired_date')->nullable()->after('issued_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certificate_settings', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('certificate_settings', 'expired_date')) {
                $columnsToDrop[] = 'expired_date';
            }
            if (Schema::hasColumn('certificate_settings', 'issued_date')) {
                $columnsToDrop[] = 'issued_date';
            }
            if (Schema::hasColumn('certificate_settings', 'institution')) {
                $columnsToDrop[] = 'institution';
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });

        Schema::table('certificates', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('certificates', 'expired_date')) {
                $columnsToDrop[] = 'expired_date';
            }
            if (Schema::hasColumn('certificates', 'issued_date')) {
                $columnsToDrop[] = 'issued_date';
            }
            if (Schema::hasColumn('certificates', 'institution')) {
                $columnsToDrop[] = 'institution';
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
