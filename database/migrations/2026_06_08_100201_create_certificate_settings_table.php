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

                $table->unsignedBigInteger('assessment_id')->nullable();
                $table->unsignedBigInteger('content_id')->nullable();

                $table->integer('minimum_progress')->default(100);
                $table->integer('minimum_score')->default(80);

                $table->string('issuer_name')
                    ->default('OWL Training Management');

                $table->string('issuer_logo')
                    ->nullable();

                $table->boolean('is_active')
                    ->default(true);

                $table->timestamps();
            });

        } else {

            Schema::table('certificate_settings', function (Blueprint $table) {

                if (!Schema::hasColumn('certificate_settings', 'assessment_id')) {
                    $table->unsignedBigInteger('assessment_id')->nullable()->after('id');
                }

                if (!Schema::hasColumn('certificate_settings', 'content_id')) {
                    $table->unsignedBigInteger('content_id')->nullable()->after('assessment_id');
                }

                if (!Schema::hasColumn('certificate_settings', 'minimum_progress')) {
                    $table->integer('minimum_progress')->default(100);
                }

                if (!Schema::hasColumn('certificate_settings', 'minimum_score')) {
                    $table->integer('minimum_score')->default(80);
                }

                if (!Schema::hasColumn('certificate_settings', 'issuer_name')) {
                    $table->string('issuer_name')
                        ->default('OWL Training Management');
                }

                if (!Schema::hasColumn('certificate_settings', 'issuer_logo')) {
                    $table->string('issuer_logo')->nullable();
                }

                if (!Schema::hasColumn('certificate_settings', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }
            });

        }
    }

    public function down()
    {
        Schema::dropIfExists('certificate_settings');
    }
}
