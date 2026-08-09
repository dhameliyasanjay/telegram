<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$parser = app(\App\Services\MessageParserService::class);

$texts = [
    "🟢NIMA COFFEE GRIANDER\n\nRS.210/-\n\nMute",
    "Aluminium Fruit Juicer\n\n🔹ALUMINIUM FRUIT JUICER\n🔹metal body press juicer\n🔹Hand Press Metal Orange & Lime Juicer,\n🔹Heavy Duty Steel Handle Squeezer Citrus Press\n\nRATE 249/-🔥🔥🔥🔥🔥🔥🔥🔥\nCall and message\n9909334000\n\nUnmute",
    "Apple Shape Fruit Basket / Vegetable - Fruit Basket With Net Cover / Metal Fruit Basket / Apple Fruit Basket (Big Size) - 90 ₹\n\nAsk for best CTN rate\n8222970701\n\nMute",
    "code = 2610\n1 ctn pcs = 500\n\nbamboo knife\n\nBamboo Style Paring Knife with Sheath | Utility Kitchen Knife, Laser-Textured Stainless Steel Blade, ABS Handle, Anti-Slip Grip, Multipurpose Fruit, Meat & Vegetable Cutter\n\nrate = 50 /-\n\nMute",
    "crochet flower / flower bouquet / artificial flowers / handmade flowers / cotton flowers / flower decor / home decor / table decor / bedroom decor / flowers for vase / vase flowers / flower decoration / decorative flowers / home decoration / crochet gift\n\n📦 Ask For Best Ctn Rate\n\nMute"
];

foreach ($texts as $i => $text) {
    echo "--- EXAMPLE " . ($i+1) . " ---\n";
    echo "TEXT:\n$text\n";
    $parsed = $parser->parse([
        '_' => 'message',
        'message' => $text,
        'media' => ['_' => 'messageMediaPhoto']
    ]);
    echo "\nEXTRACTED:\n";
    echo "Name: " . ($parsed['product_name'] ?? 'NULL') . "\n";
    echo "Price: " . ($parsed['price'] ?? 'NULL') . "\n";
    echo "Key: " . ($parsed['product_key'] ?? 'NULL') . "\n";
    echo "\n";
}
