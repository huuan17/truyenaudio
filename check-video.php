<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "=== Checking Latest Video ===\n\n";

try {
    $video = App\Models\GeneratedVideo::latest()->first();
    
    if (!$video) {
        echo "No videos found in database.\n";
        exit;
    }
    
    echo "Video ID: " . $video->id . "\n";
    echo "Title: " . $video->title . "\n";
    echo "File path: " . $video->file_path . "\n";
    echo "File name: " . $video->file_name . "\n";
    echo "File exists: " . ($video->fileExists() ? 'YES' : 'NO') . "\n";
    
    if ($video->fileExists()) {
        echo "File size: " . filesize($video->file_path) . " bytes\n";
        echo "Preview URL: " . $video->preview_url . "\n";
        echo "Download URL: " . $video->download_url . "\n";
    } else {
        echo "File does not exist at: " . $video->file_path . "\n";
        
        // Check if file exists in different locations
        $possiblePaths = [
            storage_path('app/videos/' . $video->file_name),
            storage_path('app/tiktok_videos/' . $video->file_name),
            storage_path('app/youtube_videos/' . $video->file_name),
        ];
        
        echo "\nChecking possible locations:\n";
        foreach ($possiblePaths as $path) {
            echo "- " . $path . ": " . (file_exists($path) ? 'EXISTS' : 'NOT FOUND') . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Check Complete ===\n";
?>
