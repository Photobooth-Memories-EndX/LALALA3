<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpgradeCertificateSystemV2 extends Migration
{
    public function up()
    {
        Schema::table('certificate_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('certificate_settings', 'use_default_template')) {
                $table->boolean('use_default_template')->default(true)->after('issuer_logo');
            }
            if (!Schema::hasColumn('certificate_settings', 'background_image')) {
                $table->string('background_image')->nullable()->after('certificate_template');
            }
            if (!Schema::hasColumn('certificate_settings', 'custom_background')) {
                $table->string('custom_background')->nullable()->after('certificate_template');
            }
        });

        Schema::table('certificates', function (Blueprint $table) {
            if (!Schema::hasColumn('certificates', 'certificate_type')) {
                $table->string('certificate_type')->default('content')->after('certificate_setting_id');
            }
            if (!Schema::hasColumn('certificates', 'source_id')) {
                $table->unsignedBigInteger('source_id')->nullable()->after('certificate_type');
            }
        });
    }

    public function down()
    {
        Schema::table('certificate_settings', function (Blueprint $table) {
            if (Schema::hasColumn('certificate_settings', 'custom_background')) {
                $table->dropColumn('custom_background');
            }
        });
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn(['certificate_type', 'source_id']);
        });
    }
}
