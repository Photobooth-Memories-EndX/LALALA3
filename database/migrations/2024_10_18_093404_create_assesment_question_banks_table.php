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
    public function up()
    {
        Schema::create('assesment_question_banks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assesment_id')->constrained('assesments')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('question_bank_id')->constrained('question_banks')->cascadeOnDelete()->cascadeOnUpdate();
            $table->integer('questions_amount')->default(0);
            $table->timestamps();
        });

        Schema::table('assesment_questions', function (Blueprint $table) {
            $table->foreignId('question_bank_id')->nullable()->after('assesment_id')->constrained('question_banks')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('question_id')->nullable()->after('question_bank_id')->constrained('questions')->nullOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('assesment_question_banks');

        Schema::table('assesment_questions', function (Blueprint $table) {
            $table->dropForeign('assesment_questions_question_bank_id_foreign');
            $table->dropColumn('question_bank_id');
            $table->dropForeign('assesment_questions_question_id_foreign');
            $table->dropColumn('question_id');
        });
    }
};
