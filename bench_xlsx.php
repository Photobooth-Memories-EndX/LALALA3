<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$admin = User::where('role', '2')->first() ?? User::first();
auth()->login($admin);

$request = \Illuminate\Http\Request::create('/manage/learning/category/basic-common-duties', 'GET', ['action' => 'download']);
$controller = new \App\Http\Controllers\LearningManageController();
$response = $controller->category('basic-common-duties', $request);

file_put_contents('bench_xlsx.txt', "Status: " . $response->getStatusCode() . " Content-Type: " . $response->headers->get('Content-Type'));
