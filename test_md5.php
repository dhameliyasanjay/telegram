<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

$products = Product::whereNotNull('latest_photo_url')->get();
$hashes = [];
$duplicates = [];

foreach ($products as $p) {
    $path = public_path(str_replace('/storage/', 'storage/', $p->latest_photo_url));
    if (file_exists($path)) {
        $hash = md5_file($path);
        if (isset($hashes[$hash])) {
            $duplicates[] = [
                'hash' => $hash,
                'p1' => $hashes[$hash],
                'p2' => ['id' => $p->id, 'name' => $p->product_name]
            ];
        } else {
            $hashes[$hash] = ['id' => $p->id, 'name' => $p->product_name];
        }
    }
}
echo "Total products with photos: " . count($products) . "\n";
echo "Duplicates by MD5: " . count($duplicates) . "\n";
foreach(array_slice($duplicates, 0, 5) as $dup) {
    echo "Duplicate:\n  {$dup['p1']['name']} (ID: {$dup['p1']['id']})\n  {$dup['p2']['name']} (ID: {$dup['p2']['id']})\n\n";
}
