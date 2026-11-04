<?php

require_once 'c:/laragon/www/owl/vendor/autoload.php';
$app = require_once 'c:/laragon/www/owl/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "--- trainingplandetail columns ---\n";
print_r(Schema::getColumnListing('trainingplandetail'));

echo "--- playlist_content_users columns (if exists) ---\n";
if (Schema::hasTable('playlist_content_users')) {
    print_r(Schema::getColumnListing('playlist_content_users'));
}

echo "--- content_users columns ---\n";
print_r(Schema::getColumnListing('content_users'));

echo "--- Sample trainingplandetail ---\n";
$sampleTpd = DB::table('trainingplandetail')->limit(3)->get();
print_r($sampleTpd);

echo "--- Sample playlist_content_users ---\n";
if (Schema::hasTable('playlist_content_users')) {
    $samplePcu = DB::table('playlist_content_users')->limit(3)->get();
    print_r($samplePcu);
}

