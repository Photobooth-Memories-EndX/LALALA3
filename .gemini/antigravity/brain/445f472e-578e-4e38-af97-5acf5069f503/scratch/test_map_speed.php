<?php

require_once 'c:/laragon/www/owl/vendor/autoload.php';
$app = require_once 'c:/laragon/www/owl/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$start = microtime(true);
$contentToPlaylistMap = DB::table('trainingplandetail as tpd')
    ->join('trainingplan as tp', 'tp.id', '=', 'tpd.trainingplan_id')
    ->whereNull('tp.deleted_at')
    ->orderBy('tpd.id', 'asc')
    ->pluck('tp.title', 'tpd.content_id')
    ->toArray();
$elapsed = microtime(true) - $start;

echo "Loaded " . count($contentToPlaylistMap) . " mappings in " . round($elapsed * 1000, 2) . " ms.\n";

echo "Content 78 playlist: " . ($contentToPlaylistMap[78] ?? 'NONE') . "\n";
echo "Content 141 playlist: " . ($contentToPlaylistMap[141] ?? 'NONE') . "\n";
echo "Content 9999 playlist: " . ($contentToPlaylistMap[9999] ?? 'NONE') . "\n";

