<?php

require_once 'c:/laragon/www/owl/vendor/autoload.php';
$app = require_once 'c:/laragon/www/owl/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Test subquery in CU select
$cuSelect = DB::table('content_users as cu')
    ->select([
        'cu.id as source_id',
        DB::raw("'content' as source_type"),
        'cu.user_id',
        'cu.content_id',
        DB::raw('(SELECT tpd.trainingplan_id FROM trainingplandetail tpd JOIN trainingplan tp ON tp.id = tpd.trainingplan_id WHERE tpd.content_id = cu.content_id AND tp.deleted_at IS NULL ORDER BY tpd.id ASC LIMIT 1) as trainingplan_id'),
    ])
    ->where('cu.content_id', 78)
    ->limit(3)
    ->get();

print_r($cuSelect);

