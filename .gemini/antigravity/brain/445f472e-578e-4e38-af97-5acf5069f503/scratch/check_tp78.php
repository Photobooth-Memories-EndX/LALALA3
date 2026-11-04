<?php

require_once 'c:/laragon/www/owl/vendor/autoload.php';
$app = require_once 'c:/laragon/www/owl/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$tps = DB::table('trainingplandetail as tpd')
    ->join('trainingplan as tp', 'tp.id', '=', 'tpd.trainingplan_id')
    ->where('tpd.content_id', 78)
    ->select('tp.id', 'tp.title', 'tp.active', 'tp.deleted_at', 'tp.created_at')
    ->get();

print_r($tps);

