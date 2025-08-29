<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use App\Models\Channel;

$channelId = (int)($argv[1] ?? 0);
$videoRel = $argv[2] ?? '';
$title = $argv[3] ?? 'YouTube API Upload Test';
$privacy = $argv[4] ?? 'unlisted';

if ($channelId <= 0 || $videoRel === '') {
    fwrite(STDERR, "Usage: php scripts/upload_youtube_direct.php <channel_id> <video_rel_path_under_storage_app_or_abs> [title] [privacy]\n");
    exit(2);
}

$channel = Channel::find($channelId);
if (!$channel || $channel->platform !== 'youtube') {
    fwrite(STDERR, "Channel not found or not YouTube: {$channelId}\n");
    exit(3);
}

$creds = $channel->api_credentials ?: [];
$clientId = $creds['client_id'] ?? config('services.youtube.client_id');
$clientSecret = $creds['client_secret'] ?? config('services.youtube.client_secret');
$refreshToken = $creds['refresh_token'] ?? null;
if (!$clientId || !$clientSecret || !$refreshToken) {
    fwrite(STDERR, "Missing YouTube creds (client_id/client_secret/refresh_token)\n");
    exit(4);
}

$videoPath = $videoRel;
if (!preg_match('/^[A-Za-z]:\\\\|^\\\\\\\\|^\//', $videoPath)) {
    $videoPath = storage_path('app/' . ltrim(str_replace('\\','/', $videoPath), '/'));
}
if (!file_exists($videoPath)) {
    fwrite(STDERR, "Video not found: {$videoPath}\n");
    exit(5);
}

if (!class_exists(\App\Services\YouTubeUploader::class)) {
    fwrite(STDERR, "YouTubeUploader service missing\n");
    exit(6);
}

$uploader = new \App\Services\YouTubeUploader($clientId, $clientSecret, $refreshToken);
$result = $uploader->upload($videoPath, $title, '', [], $privacy, null);

echo json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . "\n";
