<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Merge "Short answer" (text) into "Answer" (textarea).
 * Existing short-answer questions become written-answer questions.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('task_fields')
            ->where('type', 'text')
            ->update(['type' => 'textarea']);
    }

    public function down(): void
    {
        // No data reversal — merged types cannot be split back automatically.
    }
};
