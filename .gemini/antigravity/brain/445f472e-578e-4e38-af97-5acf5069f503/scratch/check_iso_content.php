<?php

require_once 'c:/laragon/www/owl/vendor/autoload.php';
$app = require_once 'c:/laragon/www/owl/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$content = DB::table('content')
    ->where('title', 'like', '%ISO 14001:2015 Sistem Manajemen%')
    ->first();

if ($content) {
    echo "Found content: ID {$content->id}, Title: {$content->title}\n";
    $tpds = DB::table('trainingplandetail as tpd')
        ->join('trainingplan as tp', 'tp.id', '=', 'tpd.trainingplan_id')
        ->where('tpd.content_id', $content->id)
        ->select('tp.id', 'tp.title', 'tp.deleted_at')
        ->get();
    echo "In trainingplandetail:\n";
    print_r($tpds);

    $pcuCount = DB::table('playlist_content_users')->where('content_id', $content->id)->count();
    $cuCount = DB::table('content_users')->where('content_id', $content->id)->count();
    echo "PCU count: {$pcuCount}, CU count: {$cuCount}\n";

    // Let's see some PCU records for this content
    $samplePcu = DB::table('playlist_content_users as pcu')
        ->join('trainingplan as tp', 'tp.id', '=', 'pcu.trainingplan_id')
        ->where('pcu.content_id', $content->id)
        ->select('pcu.id', 'pcu.user_id', 'pcu.trainingplan_id', 'tp.title as tp_title')
        ->limit(3)
        ->get();
    echo "Sample PCU:\n";
    print_r($samplePcu);
} else {
    echo "Content not found by title\n";
}

