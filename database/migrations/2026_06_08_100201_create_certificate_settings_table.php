<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCertificateSettingsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('certificate_settings')) {

            Schema::create('certificate_settings', function (Blueprint $table) {

                $table->id();

                $table->string('certificate_type')->default('content');

                $table->unsignedBigInteger('content_id')->nullable();
                $table->unsignedBigInteger('trainingplan_id')->nullable();
                $table->unsignedBigInteger('assessment_id')->nullable();

                $table->boolean('is_assessment_required')->default(false);

                $table->integer('minimum_progress')->default(100);
                $table->integer('minimum_score')->nullable()->default(80);

                $table->string('issuer_name')->default('OWL Training Management');
                $table->string('issuer_logo')->nullable();

                $table->string('certificate_template')->nullable();

                $table->boolean('is_active')->default(true);

                $table->timestamps();
            });

        } else {

            Schema::table('certificate_settings', function (Blueprint $table) {

                if (!Schema::hasColumn('certificate_settings', 'certificate_type')) {
                    $table->string('certificate_type')->default('content')->after('id');
                }

                if (!Schema::hasColumn('certificate_settings', 'content_id')) {
                    $table->unsignedBigInteger('content_id')->nullable()->after('certificate_type');
                }

                if (!Schema::hasColumn('certificate_settings', 'trainingplan_id')) {
                    $table->unsignedBigInteger('trainingplan_id')->nullable()->after('content_id');
                }

                if (!Schema::hasColumn('certificate_settings', 'assessment_id')) {
                    $table->unsignedBigInteger('assessment_id')->nullable()->after('trainingplan_id');
                }

                if (!Schema::hasColumn('certificate_settings', 'is_assessment_required')) {
                    $table->boolean('is_assessment_required')->default(false)->after('assessment_id');
                }

                if (!Schema::hasColumn('certificate_settings', 'minimum_progress')) {
                    $table->integer('minimum_progress')->default(100)->after('is_assessment_required');
                }

                if (!Schema::hasColumn('certificate_settings', 'minimum_score')) {
                    $table->integer('minimum_score')->nullable()->after('minimum_progress');
                }

                if (!Schema::hasColumn('certificate_settings', 'issuer_name')) {
                    $table->string('issuer_name')->default('OWL Training Management')->after('minimum_score');
                }

                if (!Schema::hasColumn('certificate_settings', 'issuer_logo')) {
                    $table->string('issuer_logo')->nullable()->after('issuer_name');
                }

                if (!Schema::hasColumn('certificate_settings', 'certificate_template')) {
                    $table->string('certificate_template')->nullable()->after('issuer_logo');
                }

                if (!Schema::hasColumn('certificate_settings', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('certificate_template');
                }
            });

        }
    }

    public function down()
    {
        Schema::dropIfExists('certificate_settings');
    }
}