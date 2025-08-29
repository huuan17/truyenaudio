<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Channel;

$name = $argv[1] ?? null;
$q = Channel::query()->where('platform','youtube');
if ($name) {
    $q->where('name','like', "%$name%");
}
$ch = $q->first();
if (!$ch) {
    fwrite(STDERR, "No YouTube channel found\n");
    exit(1);
}
$creds = $ch->api_credentials ?: [];
$keys = array_keys($creds);
$hasRefresh = !empty($creds['refresh_token'] ?? null);
$hasClient = !empty($creds['client_id'] ?? null) && !empty($creds['client_secret'] ?? null);

$out = [
    'id' => $ch->id,
    'name' => $ch->name,
    'is_active' => (bool)$ch->is_active,
    'has_valid_credentials' => $ch->hasValidCredentials(),
    'cred_keys' => $keys,
    'has_refresh_token' => $hasRefresh,
    'has_client_pair' => $hasClient,
];

echo json_encode($out, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) . "\n";

