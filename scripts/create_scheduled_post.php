<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use App\Models\ScheduledPost;

$channelId = (int)($argv[1] ?? 0);
$videoRel = $argv[2] ?? 'test/test.mp4';
$title = $argv[3] ?? 'YouTube API Upload Test';
$privacy = $argv[4] ?? 'private';

try {
    // Validate inputs
    if ($channelId <= 0) {
        fwrite(STDERR, "Invalid channel_id. Usage: php scripts/create_scheduled_post.php <channel_id> [video_rel] [title] [privacy]\n");
        exit(2);
    }

    // Normalize and check file existence
    $normalized = $videoRel;
    if (!preg_match('/^[A-Za-z]:\\\\|^\\\\\\\\|^\//', $normalized)) {
        $normalized = storage_path('app/' . ltrim(str_replace('\\','/', $normalized), '/'));
    }
    if (!file_exists($normalized)) {
        fwrite(STDERR, "Video file not found at: {$normalized}\n");
        exit(3);
    }

    $post = ScheduledPost::create([
        'channel_id' => $channelId,
        'video_path' => $videoRel,
        'video_type' => 'custom',
        'title' => $title,
        'description' => 'Test upload via CLI',
        'tags' => ['test','api'],
        'category' => null,
        'privacy' => $privacy,
        'scheduled_at' => now(),
        'timezone' => 'Asia/Ho_Chi_Minh',
        'status' => 'pending'
    ]);

    echo $post->id . "\n";
    exit(0);

} catch (\\Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
}
