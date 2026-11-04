<?php

require_once 'c:/laragon/www/owl/vendor/autoload.php';
$app = require_once 'c:/laragon/www/owl/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Services\Learning\LearningDatatableService;

$req = new Request([
    'classification' => 'iso-standard',
    'classification_slug' => 'iso-standard',
    'slug' => 'iso-standard',
    'length' => 5,
]);

$service = app(LearningDatatableService::class);
$response = $service->make($req);
$data = $response->getData(true);

echo "Total records: " . $data['recordsTotal'] . "\n";
echo "First 5 records:\n";
foreach ($data['data'] as $row) {
    echo "ID: " . $row['employee_id'] . " | Course: " . strip_tags($row['course_title']) 
        . " | Playlist: " . strip_tags($row['playlist']) 
        . " | Category: " . strip_tags($row['category']) . "\n";
}

