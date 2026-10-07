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
        if (!Schema::hasTable('offline_training_registers')) {
            Schema::create('offline_training_registers', function (Blueprint $table) {
                $table->id();

                $table->foreignId('offline_training_id')
                    ->constrained('offline_trainings')
                    ->cascadeOnDelete();

                $table->foreignId('user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $table->string('status', 50)->default('Pending');

                $table->timestamp('registered_at')->useCurrent();

                $table->timestamp('approved_at')->nullable();

                $table->foreignId('approved_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamp('rejected_at')->nullable();

                $table->text('rejection_reason')->nullable();

                $table->timestamp('completed_at')->nullable();

                $table->timestamps();

                $table->unique(
                    ['offline_training_id', 'user_id'],
                    'uq_training_user_registration'
                );

                $table->index(['offline_training_id', 'status']);
                $table->index(['user_id', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offline_training_registers');
    }
};
