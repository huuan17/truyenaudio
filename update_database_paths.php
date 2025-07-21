<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Updating Database Paths to New Storage Structure ===\n";

// Update chapters audio_file_path
echo "\n1. Updating chapters audio_file_path...\n";
$chapters = DB::table('chapters')
    ->whereNotNull('audio_file_path')
    ->where('audio_file_path', '!=', '')
    ->get();

$updatedChapters = 0;

foreach ($chapters as $chapter) {
    $oldPath = $chapter->audio_file_path;
    $newPath = $oldPath;
    
    echo "Chapter {$chapter->id}: {$oldPath}\n";
    
    // Convert old paths to new structure
    if (str_contains($oldPath, 'truyen/mp3-')) {
        // Old: truyen/mp3-story-slug/chuong_1.mp3
        // New: audio/story-slug/chuong_1.mp3
        $newPath = str_replace('truyen/mp3-', 'audio/', $oldPath);
    } elseif (str_contains($oldPath, 'storage/truyen/mp3-')) {
        // Old: storage/truyen/mp3-story-slug/chuong_1.mp3
        // New: audio/story-slug/chuong_1.mp3
        $newPath = str_replace('storage/truyen/mp3-', 'audio/', $oldPath);
    } elseif (str_contains($oldPath, 'storage/app/audio/')) {
        // Already in new format, extract relative path
        $newPath = substr($oldPath, strpos($oldPath, 'audio/'));
    }
    
    if ($newPath !== $oldPath) {
        DB::table('chapters')
            ->where('id', $chapter->id)
            ->update(['audio_file_path' => $newPath]);
            
        echo "  → Updated to: {$newPath}\n";
        $updatedChapters++;
    } else {
        echo "  → No change needed\n";
    }
}

echo "Updated {$updatedChapters} chapter audio paths.\n";

// Update stories crawl_path
echo "\n2. Updating stories crawl_path...\n";
$stories = DB::table('stories')
    ->whereNotNull('crawl_path')
    ->where('crawl_path', '!=', '')
    ->get();

$updatedStories = 0;

foreach ($stories as $story) {
    $oldPath = $story->crawl_path;
    $newPath = $oldPath;
    
    echo "Story {$story->id} ({$story->slug}): {$oldPath}\n";
    
    // Convert old paths to new structure
    if (str_contains($oldPath, 'storage/truyen/')) {
        // Old: storage/truyen/story-slug
        // New: storage/app/content/story-slug
        $newPath = str_replace('storage/truyen/', 'storage/app/content/', $oldPath);
    }
    
    if ($newPath !== $oldPath) {
        DB::table('stories')
            ->where('id', $story->id)
            ->update(['crawl_path' => $newPath]);
            
        echo "  → Updated to: {$newPath}\n";
        $updatedStories++;
    } else {
        echo "  → No change needed\n";
    }
}

echo "Updated {$updatedStories} story crawl paths.\n";

// Test file access
echo "\n3. Testing file access...\n";
$testChapters = DB::table('chapters')
    ->whereNotNull('audio_file_path')
    ->where('audio_file_path', '!=', '')
    ->limit(5)
    ->get();

foreach ($testChapters as $chapter) {
    $audioPath = $chapter->audio_file_path;
    
    // Test different path combinations
    $testPaths = [
        storage_path('app/' . $audioPath),
        public_path('storage/' . $audioPath),
        base_path('storage/app/' . $audioPath),
    ];
    
    echo "Chapter {$chapter->id}: {$audioPath}\n";
    
    $found = false;
    foreach ($testPaths as $testPath) {
        if (file_exists($testPath)) {
            echo "  ✅ Found: {$testPath}\n";
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        echo "  ❌ Not found in any location\n";
        foreach ($testPaths as $testPath) {
            echo "    Checked: {$testPath}\n";
        }
    }
    
    // Test URL generation
    $url = asset('storage/' . $audioPath);
    echo "  URL: {$url}\n";
    echo "\n";
}

echo "\n✅ Database path update completed!\n";
echo "\nSummary:\n";
echo "- Updated {$updatedChapters} chapter audio paths\n";
echo "- Updated {$updatedStories} story crawl paths\n";
echo "- New storage structure: storage/app/content/ for text, storage/app/audio/ for audio\n";
echo "- Files accessible via: asset('storage/path')\n";

?>
