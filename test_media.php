<?php
require 'vendor/autoload.php';
$client = new \danog\MadelineProto\API('storage/app/telegram/session.madeline');
$client->start();
$messages = $client->messages->getHistory([
    'peer' => '@test_group_something', // or just use a known group
    'limit' => 1
]);
// Actually, I can just use a database query to find a recent message_id and fetch it, but I can also just look at MadelineProto docs.
