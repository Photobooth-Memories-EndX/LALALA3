<?php

require_once 'c:/laragon/www/owl/vendor/autoload.php';
$app = require_once 'c:/laragon/www/owl/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$tps = DB::table('trainingplan as tp')
    ->leftJoin('classification as cl', 'cl.id', '=', 'tp.classification_id')
    ->select('tp.id', 'tp.title', 'tp.classification_id', 'cl.classification_name', 'cl.slug')
    ->get();

echo "All Trainingplans:\n";
foreach ($tps as $tp) {
    echo "TP {$tp->id}: {$tp->title} (Class: {$tp->classification_name} / {$tp->slug})\n";
}

// Check how many contents in trainingplandetail belong to trainingplans
echo "\nCheck content_id 78 in trainingplandetail with classification:\n";
$details = DB::table('trainingplandetail as tpd')
    ->join('trainingplan as tp', 'tp.id', '=', 'tpd.trainingplan_id')
    ->leftJoin('classification as cl', 'cl.id', '=', 'tp.classification_id')
    ->where('tpd.content_id', 78)
    ->select('tp.id', 'tp.title', 'tp.classification_id', 'cl.classification_name', 'cl.slug')
    ->get();
print_r($details);

