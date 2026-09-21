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
        Schema::create('offline_trainings', function (Blueprint $table) {
            $table->id();
            $table->string('course_name')->index();
            $table->string('sub_course_name')->nullable();
            $table->string('type', 100)->nullable()->index();
            $table->string('category', 100)->nullable()->index();
            $table->string('level', 100)->nullable();
            $table->string('batch', 100)->nullable()->index();
            $table->string('main_classification')->nullable()->index();
            $table->string('sub_classification')->nullable();
            $table->boolean('counted_corporate_kpi')->nullable();
            $table->string('sub_corporate_kpi')->nullable();
            $table->string('delivery_method', 100)->nullable();
            $table->string('sub_delivery_method', 100)->nullable();
            $table->string('provider')->nullable();
            $table->string('sub_provider')->nullable();
            $table->string('provider_name')->nullable();
            $table->string('venue')->nullable();
            $table->string('instructor')->nullable();
            $table->unsignedBigInteger('duration_seconds')->nullable();
            $table->decimal('learning_hours', 10, 4)->nullable();
            $table->date('start_date')->nullable()->index();
            $table->date('finish_date')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(['start_date', 'finish_date']);
        });

        Schema::create('offline_training_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('offline_training_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('employee_id', 100)->nullable()->index();
            $table->string('name')->nullable();
            $table->string('grade', 100)->nullable();
            $table->string('job_position')->nullable();
            $table->string('team')->nullable();
            $table->string('department')->nullable()->index();
            $table->string('company')->nullable()->index();
            $table->decimal('learning_hours', 10, 4)->nullable();
            $table->decimal('progress', 7, 4)->nullable();
            $table->decimal('assessment_score', 7, 2)->nullable();
            $table->string('certificate')->nullable();
            $table->boolean('attendance')->nullable()->index();
            $table->text('attendance_reason')->nullable();
            $table->string('bond_status', 100)->nullable();
            $table->date('bond_end_date')->nullable();
            $table->decimal('bond_amount', 15, 2)->nullable();
            $table->string('l1_program')->nullable();
            $table->decimal('l2_numerical', 10, 4)->nullable();
            $table->string('l2_verbal')->nullable();
            $table->decimal('l3_numerical', 10, 4)->nullable();
            $table->string('l3_verbal')->nullable();
            $table->string('pic')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('offline_training_id')
                ->references('id')
                ->on('offline_trainings')
                ->cascadeOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(['offline_training_id', 'employee_id']);
            $table->index(['offline_training_id', 'progress']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offline_training_users');
        Schema::dropIfExists('offline_trainings');
    }
};
