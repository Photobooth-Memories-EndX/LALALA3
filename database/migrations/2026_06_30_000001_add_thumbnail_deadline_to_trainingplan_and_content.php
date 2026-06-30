<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trainingplan', function (Blueprint $table) {
            if (!Schema::hasColumn('trainingplan', 'thumbnail')) {
                $table->string('thumbnail')->nullable()->after('url_link');
            }
            if (!Schema::hasColumn('trainingplan', 'has_deadline')) {
                $table->boolean('has_deadline')->default(false)->after('thumbnail');
            }
            if (!Schema::hasColumn('trainingplan', 'from_date')) {
                $table->date('from_date')->nullable()->after('has_deadline');
            }
            if (!Schema::hasColumn('trainingplan', 'to_date')) {
                $table->date('to_date')->nullable()->after('from_date');
            }
        });

        Schema::table('content', function (Blueprint $table) {
            if (!Schema::hasColumn('content', 'has_deadline')) {
                $table->boolean('has_deadline')->default(false)->after('active');
            }
            if (!Schema::hasColumn('content', 'from_date')) {
                $table->date('from_date')->nullable()->after('has_deadline');
            }
            if (!Schema::hasColumn('content', 'to_date')) {
                $table->date('to_date')->nullable()->after('from_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('trainingplan', function (Blueprint $table) {
            $cols = array_filter(['thumbnail', 'has_deadline', 'from_date', 'to_date'],
                fn ($c) => Schema::hasColumn('trainingplan', $c));
            if ($cols) {
                $table->dropColumn(array_values($cols));
            }
        });

        Schema::table('content', function (Blueprint $table) {
            $cols = array_filter(['has_deadline', 'from_date', 'to_date'],
                fn ($c) => Schema::hasColumn('content', $c));
            if ($cols) {
                $table->dropColumn(array_values($cols));
            }
        });
    }
};
