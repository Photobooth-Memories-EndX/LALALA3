<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCertificatesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('certificates')) {

            Schema::create('certificates', function (Blueprint $table) {

                $table->id();

                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('certificate_setting_id');

                $table->unsignedBigInteger('content_user_id')->nullable();
                $table->unsignedBigInteger('assessment_result_id')->nullable();

                $table->string('certificate_number')->unique();
                $table->string('credential_id')->unique();
                $table->string('credential_url')->nullable();

                $table->string('title');
                $table->string('participant_name');

                $table->integer('score')->nullable();

                $table->date('issued_at');
                $table->string('file_path')->nullable();

                $table->timestamps();

            });

        }
    }

    public function down()
    {
        Schema::dropIfExists('certificates');
    }
}