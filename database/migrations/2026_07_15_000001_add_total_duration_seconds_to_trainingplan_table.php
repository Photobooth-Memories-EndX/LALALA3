<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Support\DurationFormatter;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('trainingplan', 'total_duration_seconds')) {
            Schema::table('trainingplan', function (Blueprint $table) {
                $table->unsignedInteger('total_duration_seconds')->default(0)->after('title');
            });
        }

        $durations = DB::table('trainingplandetail as detail')
            ->join('content', 'content.id', '=', 'detail.content_id')
            ->select('detail.trainingplan_id', 'content.duration')
            ->get()
            ->groupBy('trainingplan_id')
            ->map(fn ($items) => $items->sum(fn ($item) => DurationFormatter::toSeconds($item->duration) ?? 0));

        foreach ($durations as $trainingPlanId => $seconds) {
            DB::table('trainingplan')->where('id', $trainingPlanId)->update([
                'total_duration_seconds' => (int) $seconds,
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('trainingplan', 'total_duration_seconds')) {
            Schema::table('trainingplan', function (Blueprint $table) {
                $table->dropColumn('total_duration_seconds');
            });
        }
    }
};
