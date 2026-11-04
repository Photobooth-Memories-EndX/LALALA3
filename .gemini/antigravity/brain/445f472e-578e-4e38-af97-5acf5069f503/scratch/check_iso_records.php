<?php

require_once 'c:/laragon/www/owl/vendor/autoload.php';
$app = require_once 'c:/laragon/www/owl/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Let's inspect some records from LearningDatatableService for classification 'iso-standard'
$query = App\Services\Learning\LearningDatatableService::buildBaseQuery(['classification' => 'iso-standard']);
$records = $query->limit(10)->get();

echo "Sample 10 records for iso-standard:\n";
foreach ($records as $r) {
    echo "ID: {$r->source_id}, Type: {$r->source_type}, CU/PCU TP_ID: " . ($r->trainingplan_id ?? 'NULL') 
        . ", ContentID: {$r->content_id}, ContentTitle: " . substr($r->content_title ?? '', 0, 30)
        . ", PlaylistTitle: " . ($r->playlist_title ?? 'NULL') . "\n";
}

// Check trainingplandetail mapping for these content_ids
$contentIds = $records->pluck('content_id')->filter()->unique()->toArray();
echo "\nCheck trainingplandetail for content_ids:\n";
$tpds = DB::table('trainingplandetail as tpd')
    ->join('trainingplan as tp', 'tp.id', '=', 'tpd.trainingplan_id')
    ->whereIn('tpd.content_id', $contentIds)
    ->select('tpd.content_id', 'tpd.trainingplan_id', 'tp.title as tp_title')
    ->get();
foreach ($tpds as $t) {
    echo "ContentID {$t->content_id} belongs to TP {$t->trainingplan_id} ({$t->tp_title})\n";
}

