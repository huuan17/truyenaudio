<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use App\\Models\\Channel;

$channelId = (int)($argv[1] ?? 0);
if ($channelId <= 0) { fwrite(STDERR, "Usage: php scripts/debug_youtube_creds.php <channel_id>\n"); exit(2); }
$channel = Channel::find($channelId);
if (!$channel) { fwrite(STDERR, "Channel not found\n"); exit(3); }

$creds = $channel->api_credentials ?: [];
$clientId = $creds['client_id'] ?? null;
$clientSecret = $creds['client_secret'] ?? null;
$refreshToken = $creds['refresh_token'] ?? null;

$mask = function($s) { if (!$s) return null; $len = strlen($s); return ($len<=8)? str_repeat('*',$len) : substr($s,0,4) . '...' . substr($s,-4); };
$out = [
  'channel_id' => $channel->id,
  'channel_name' => $channel->name,
  'platform' => $channel->platform,
  'is_active' => (bool)$channel->is_active,
  'hasValidCredentials' => $channel->hasValidCredentials(),
  'client_id_masked' => $mask($clientId),
  'client_secret_masked' => $mask($clientSecret),
  'refresh_token_len' => $refreshToken ? strlen($refreshToken) : 0,
];

echo json_encode($out, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . "\n";

if (!class_exists('Google_Client')) { fwrite(STDERR, "Missing google/apiclient\n"); exit(0); }
if (!$clientId || !$clientSecret || !$refreshToken) { exit(0); }

$client = new Google_Client();
$client->setClientId($clientId);
$client->setClientSecret($clientSecret);
$client->setAccessType('offline');
$client->setScopes(['https://www.googleapis.com/auth/youtube.readonly']);
$token = $client->fetchAccessTokenWithRefreshToken($refreshToken);
$resp = [ 'refresh_result' => $token ];

echo json_encode($resp, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . "\n";

