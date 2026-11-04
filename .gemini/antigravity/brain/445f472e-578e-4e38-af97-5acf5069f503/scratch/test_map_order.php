<?php

require_once 'c:/laragon/www/owl/vendor/autoload.php';
$app = require_once 'c:/laragon/www/owl/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$contentToPlaylistMap = [];
$tpdRows = DB::table('trainingplandetail as tpd')
    ->join('trainingplan as tp', 'tp.id', '=', 'tpd.trainingplan_id')
    ->whereNull('tp.deleted_at')
    ->orderBy('tpd.id', 'asc')
    ->select('tpd.content_id', 'tp.title')
    ->get();

foreach ($tpdRows as $r) {
    if (!isset($contentToPlaylistMap[$r->content_id])) {
        $contentToPlaylistMap[$r->content_id] = trim($r->title);
    }
}

echo "Content 78 playlist: " . ($contentToPlaylistMap[78] ?? 'NONE') . "\n";
echo "Content 141 playlist: " . ($contentToPlaylistMap[141] ?? 'NONE') . "\n";

