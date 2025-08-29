<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use App\Models\ScheduledPost;

$posts = ScheduledPost::orderBy('id','desc')->limit(5)->get(['id','title','video_path','status','scheduled_at','channel_id']);

$out = $posts->map(function($p){
    return [
        'id' => $p->id,
        'title' => $p->title,
        'video_path' => $p->video_path,
        'status' => $p->status,
        'scheduled_at' => optional($p->scheduled_at)->toDateTimeString(),
        'channel_id' => $p->channel_id,
    ];
});

echo json_encode($out, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) . "\n";
