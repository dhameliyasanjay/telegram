<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\BoardController;
use Illuminate\Http\UploadedFile;

$p = Product::whereNotNull('photo_hash')->first();
$path = public_path(str_replace('/storage/', 'storage/', $p->latest_photo_url));

$file = new UploadedFile($path, basename($path), 'image/jpeg', null, true);
$request = Request::create('/api/board/hash-photo', 'POST', [], [], ['photo' => $file]);

$controller = new BoardController();
$response = $controller->hashPhoto($request);

echo "DB Hash: " . $p->photo_hash . "\n";
echo "Response: " . $response->getContent() . "\n";
