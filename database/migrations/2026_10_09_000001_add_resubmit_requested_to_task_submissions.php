<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('task_submissions') && !Schema::hasColumn('task_submissions', 'resubmit_requested')) {
            Schema::table('task_submissions', function (Blueprint $table) {
                $table->boolean('resubmit_requested')->default(false)->after('feedback');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('task_submissions') && Schema::hasColumn('task_submissions', 'resubmit_requested')) {
            Schema::table('task_submissions', function (Blueprint $table) {
                $table->dropColumn('resubmit_requested');
            });
        }
    }
};
