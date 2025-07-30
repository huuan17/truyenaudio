<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\GeneratedVideo;

try {
    $video = new GeneratedVideo();
    $video->title = 'Video tiếng Việt - Test subtitle UTF-8';
    $video->description = 'Video được tạo từ template với subtitle tiếng Việt UTF-8 encoding';
    $video->platform = 'tiktok';
    $video->media_type = 'images';
    $video->file_path = 'videos/tiktok_video_2025-07-29_16-11_055.mp4';
    $video->file_name = 'tiktok_video_2025-07-29_16-11_055.mp4';
    
    $fullPath = storage_path('app/videos/tiktok_video_2025-07-29_16-11_055.mp4');
    $video->file_size = file_exists($fullPath) ? filesize($fullPath) : null;
    $video->duration = 30;
    $video->metadata = [
        'generation_parameters' => ['platform' => 'tiktok', 'media_type' => 'images'],
        'subtitle_text' => 'Nếu bạn đã làm theo đúng quy trình tạo phụ đề từ tiếng Việt...',
        'created_via' => 'manual_fix'
    ];
    $video->status = 'generated';
    $video->task_id = 112;
    $video->save();
    
    echo "✅ GeneratedVideo created successfully with ID: " . $video->id . PHP_EOL;
    echo "📁 File path: " . $video->file_path . PHP_EOL;
    echo "📊 File size: " . ($video->file_size ? number_format($video->file_size / 1024 / 1024, 2) . ' MB' : 'Unknown') . PHP_EOL;
    echo "🎬 Platform: " . $video->platform . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
}
