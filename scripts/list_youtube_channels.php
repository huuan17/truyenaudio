<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use App\Models\Channel;

$chs = Channel::where('platform','youtube')->orderBy('id')->get();
$out = $chs->map(function($c){
    $creds = $c->api_credentials ?: [];
    return [
        'id' => $c->id,
        'name' => $c->name,
        'is_active' => (bool)$c->is_active,
        'has_client_id' => !empty($creds['client_id']) || !empty(config('services.youtube.client_id')),
        'has_client_secret' => !empty($creds['client_secret']) || !empty(config('services.youtube.client_secret')),
        'has_refresh_token' => !empty($creds['refresh_token']),
    ];
});

echo json_encode($out, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) . "\n";

