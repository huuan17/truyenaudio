<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Setting up Public Audio Access ===\n";

// Copy audio files to public/storage for web access
$sourceAudioPath = storage_path('app/audio');
$targetAudioPath = storage_path('app/public/audio');

echo "\n1. Creating audio directory in public storage...\n";
if (!is_dir($targetAudioPath)) {
    mkdir($targetAudioPath, 0755, true);
    echo "✅ Created: {$targetAudioPath}\n";
} else {
    echo "✅ Already exists: {$targetAudioPath}\n";
}

echo "\n2. Copying audio files...\n";
if (is_dir($sourceAudioPath)) {
    $storyDirs = glob($sourceAudioPath . '/*', GLOB_ONLYDIR);
    $totalCopied = 0;
    
    foreach ($storyDirs as $storyDir) {
        $storyName = basename($storyDir);
        $targetStoryDir = $targetAudioPath . '/' . $storyName;
        
        echo "Processing story: {$storyName}\n";
        
        // Create story directory in public
        if (!is_dir($targetStoryDir)) {
            mkdir($targetStoryDir, 0755, true);
        }
        
        // Copy audio files
        $audioFiles = glob($storyDir . '/*.mp3');
        foreach ($audioFiles as $audioFile) {
            $fileName = basename($audioFile);
            $targetFile = $targetStoryDir . '/' . $fileName;
            
            if (!file_exists($targetFile)) {
                if (copy($audioFile, $targetFile)) {
                    echo "  ✅ Copied: {$fileName}\n";
                    $totalCopied++;
                } else {
                    echo "  ❌ Failed to copy: {$fileName}\n";
                }
            } else {
                echo "  ⚠️ Already exists: {$fileName}\n";
            }
        }
    }
    
    echo "\nTotal files copied: {$totalCopied}\n";
} else {
    echo "❌ Source audio directory not found: {$sourceAudioPath}\n";
}

// Alternative: Create additional symlinks
echo "\n3. Creating additional symlinks for direct access...\n";

$additionalLinks = [
    'public/audio' => '../storage/app/audio',
    'public/content' => '../storage/app/content',
];

foreach ($additionalLinks as $link => $target) {
    $linkPath = base_path($link);
    
    if (!file_exists($linkPath)) {
        if (symlink($target, $linkPath)) {
            echo "✅ Created symlink: {$link} → {$target}\n";
        } else {
            echo "❌ Failed to create symlink: {$link}\n";
        }
    } else {
        echo "⚠️ Already exists: {$link}\n";
    }
}

// Test access
echo "\n4. Testing file access...\n";

use Illuminate\Support\Facades\DB;

$testChapters = DB::table('chapters')
    ->whereNotNull('audio_file_path')
    ->where('audio_file_path', '!=', '')
    ->limit(3)
    ->get();

foreach ($testChapters as $chapter) {
    $audioPath = $chapter->audio_file_path;
    
    echo "Testing chapter {$chapter->id}: {$audioPath}\n";
    
    // Test different access methods
    $testPaths = [
        'Via storage/app/public' => public_path('storage/' . $audioPath),
        'Via direct symlink' => public_path(str_replace('audio/', 'audio/', $audioPath)),
        'Original storage' => storage_path('app/' . $audioPath),
    ];
    
    foreach ($testPaths as $method => $path) {
        if (file_exists($path)) {
            echo "  ✅ {$method}: {$path}\n";
        } else {
            echo "  ❌ {$method}: {$path}\n";
        }
    }
    
    // Test URL
    $url = asset('storage/' . $audioPath);
    echo "  URL: {$url}\n";
    echo "\n";
}

echo "\n✅ Public audio access setup completed!\n";
echo "\nAccess methods:\n";
echo "1. Via Laravel storage link: asset('storage/audio/story/file.mp3')\n";
echo "2. Via direct symlink: asset('audio/story/file.mp3')\n";
echo "3. Files copied to: storage/app/public/audio/\n";

?>
