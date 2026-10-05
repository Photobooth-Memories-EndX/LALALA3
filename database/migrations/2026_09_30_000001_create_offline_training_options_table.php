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
        Schema::create('offline_training_options', function (Blueprint $table) {
            $table->id();
            $table->string('option_type', 50)->index();
            $table->string('value', 255);
            $table->string('normalized_value', 255);
            $table->unsignedSmallInteger('year')->nullable()->index();
            $table->string('scope_key', 60)->default('global')->index();
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->integer('sort_order')->default(0);
            $table->string('created_from', 20)->default('system');
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('parent_id')
                ->references('id')
                ->on('offline_training_options')
                ->nullOnDelete();

            $table->unique(['option_type', 'scope_key', 'normalized_value'], 'oto_type_scope_norm_unique');
            $table->index(['option_type', 'is_active', 'sort_order'], 'oto_type_active_sort_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offline_training_options');
    }
};
