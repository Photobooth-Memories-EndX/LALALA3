<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomBackgroundToCertificateSettings extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('certificate_settings', 'custom_background')) {
            Schema::table('certificate_settings', function (Blueprint $table) {
                $table->string('custom_background')->nullable()->after('certificate_template');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('certificate_settings', 'custom_background')) {
            Schema::table('certificate_settings', function (Blueprint $table) {
                $table->dropColumn('custom_background');
            });
        }
    }
}
