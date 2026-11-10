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
        Schema::table('offline_training_options', function (Blueprint $table) {
            if (!Schema::hasColumn('offline_training_options', 'classification_id')) {
                $table->unsignedBigInteger('classification_id')->nullable()->after('parent_id')->index();
                $table->foreign('classification_id')
                    ->references('id')
                    ->on('classification')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('offline_training_options', 'source')) {
                $table->string('source', 50)->nullable()->default('all')->after('scope_key')->index();
            }

            if (!Schema::hasColumn('offline_training_options', 'explanation')) {
                $table->text('explanation')->nullable()->after('value');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offline_training_options', function (Blueprint $table) {
            if (Schema::hasColumn('offline_training_options', 'classification_id')) {
                $table->dropForeign(['classification_id']);
                $table->dropColumn('classification_id');
            }
            if (Schema::hasColumn('offline_training_options', 'source')) {
                $table->dropColumn('source');
            }
            if (Schema::hasColumn('offline_training_options', 'explanation')) {
                $table->dropColumn('explanation');
            }
        });
    }
};
