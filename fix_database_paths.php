<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Fixing Database Paths to Use Relative Paths Only ===\n";

// Fix chapters audio_file_path to use relative paths only
echo "\n1. Fixing chapters audio_file_path...\n";
$chapters = DB::table('chapters')
    ->whereNotNull('audio_file_path')
    ->where('audio_file_path', '!=', '')
    ->get();

$fixedChapters = 0;

foreach ($chapters as $chapter) {
    $oldPath = $chapter->audio_file_path;
    $newPath = $oldPath;
    
    echo "Chapter {$chapter->id}: {$oldPath}\n";
    
    // Extract only the relative part
    if (str_contains($oldPath, 'storage/app/audio/')) {
        // Extract: audio/story-slug/chuong_1.mp3
        $newPath = substr($oldPath, strpos($oldPath, 'audio/'));
    } elseif (str_contains($oldPath, 'storage\\app\\audio\\')) {
        // Windows path
        $newPath = substr($oldPath, strpos($oldPath, 'audio\\'));
        $newPath = str_replace('\\', '/', $newPath);
    } elseif (str_contains($oldPath, 'C:\\xampp\\htdocs\\audio-lara\\storage/audio/')) {
        // Full Windows path
        $newPath = substr($oldPath, strpos($oldPath, 'audio/'));
    }
    
    if ($newPath !== $oldPath) {
        DB::table('chapters')
            ->where('id', $chapter->id)
            ->update(['audio_file_path' => $newPath]);
            
        echo "  → Fixed to: {$newPath}\n";
        $fixedChapters++;
    } else {
        echo "  → Already correct\n";
    }
}

echo "Fixed {$fixedChapters} chapter audio paths.\n";

// Test file access with corrected paths
echo "\n2. Testing file access with corrected paths...\n";
$testChapters = DB::table('chapters')
    ->whereNotNull('audio_file_path')
    ->where('audio_file_path', '!=', '')
    ->limit(5)
    ->get();

foreach ($testChapters as $chapter) {
    $audioPath = $chapter->audio_file_path;
    
    echo "Chapter {$chapter->id}: {$audioPath}\n";
    
    // Test file existence
    $fullPath = storage_path('app/' . $audioPath);
    $publicPath = public_path('storage/' . $audioPath);
    
    echo "  Storage path: {$fullPath}\n";
    echo "  Public path: {$publicPath}\n";
    
    if (file_exists($fullPath)) {
        echo "  ✅ File exists in storage\n";
    } else {
        echo "  ❌ File not found in storage\n";
    }
    
    if (file_exists($publicPath)) {
        echo "  ✅ File accessible via public\n";
    } else {
        echo "  ❌ File not accessible via public\n";
    }
    
    // Test URL generation
    $url = asset('storage/' . $audioPath);
    echo "  URL: {$url}\n";
    echo "\n";
}

// Verify storage structure
echo "\n3. Verifying storage structure...\n";
$storageAudioPath = storage_path('app/audio');
if (is_dir($storageAudioPath)) {
    echo "✅ Audio directory exists: {$storageAudioPath}\n";
    
    $storyDirs = glob($storageAudioPath . '/*', GLOB_ONLYDIR);
    foreach ($storyDirs as $storyDir) {
        $storyName = basename($storyDir);
        $audioFiles = glob($storyDir . '/*.mp3');
        echo "  Story: {$storyName} ({" . count($audioFiles) . "} audio files)\n";
    }
} else {
    echo "❌ Audio directory not found: {$storageAudioPath}\n";
}

$storageContentPath = storage_path('app/content');
if (is_dir($storageContentPath)) {
    echo "✅ Content directory exists: {$storageContentPath}\n";
    
    $storyDirs = glob($storageContentPath . '/*', GLOB_ONLYDIR);
    foreach ($storyDirs as $storyDir) {
        $storyName = basename($storyDir);
        $textFiles = glob($storyDir . '/*.txt');
        echo "  Story: {$storyName} ({" . count($textFiles) . "} text files)\n";
    }
} else {
    echo "❌ Content directory not found: {$storageContentPath}\n";
}

echo "\n✅ Database path fix completed!\n";
echo "\nNew structure:\n";
echo "- Text files: storage/app/content/story-slug/chuong-1.txt\n";
echo "- Audio files: storage/app/audio/story-slug/chuong_1.mp3\n";
echo "- Database stores: audio/story-slug/chuong_1.mp3 (relative to storage/app)\n";
echo "- URLs: asset('storage/audio/story-slug/chuong_1.mp3')\n";

?>
