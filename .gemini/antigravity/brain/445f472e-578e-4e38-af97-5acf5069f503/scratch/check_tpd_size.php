<?php

require_once 'c:/laragon/www/owl/vendor/autoload.php';
$app = require_once 'c:/laragon/www/owl/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$count = DB::table('trainingplandetail')->count();
echo "Total trainingplandetail rows: {$count}\n";

$map = DB::table('trainingplandetail as tpd')
    ->join('trainingplan as tp', 'tp.id', '=', 'tpd.trainingplan_id')
    ->whereNull('tp.deleted_at')
    ->orderBy('tp.id', 'desc')
    ->select('tpd.content_id', 'tp.id as tp_id', 'tp.title as tp_title')
    ->get()
    ->groupBy('content_id');

echo "Unique content_ids in trainingplandetail: " . $map->count() . "\n";
foreach ($map->take(5) as $cid => $items) {
    echo "Content {$cid} in TP: " . $items->first()->tp_title . " (total TPs: " . $items->count() . ")\n";
}

