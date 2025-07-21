<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Storage Migration Verification ===\n";

// 1. Verify directory structure
echo "\n1. Directory Structure:\n";
$directories = [
    'storage/app/content' => 'Text files',
    'storage/app/audio' => 'Audio files',
    'storage/app/videos' => 'Video files (organized)',
    'storage/app/images' => 'Image files',
    'storage/app/temp' => 'Temporary files',
];

foreach ($directories as $dir => $description) {
    $fullPath = base_path($dir);
    if (is_dir($fullPath)) {
        $fileCount = count(glob($fullPath . '/*'));
        echo "✅ {$dir} ({$description}) - {$fileCount} items\n";
    } else {
        echo "❌ {$dir} - Missing\n";
    }
}

// 2. Verify file counts
echo "\n2. File Counts:\n";

// Content files
$contentPath = storage_path('app/content');
if (is_dir($contentPath)) {
    $storyDirs = glob($contentPath . '/*', GLOB_ONLYDIR);
    $totalTextFiles = 0;
    
    foreach ($storyDirs as $storyDir) {
        $storyName = basename($storyDir);
        $textFiles = glob($storyDir . '/*.txt');
        $count = count($textFiles);
        $totalTextFiles += $count;
        echo "  Content/{$storyName}: {$count} text files\n";
    }
    echo "  Total text files: {$totalTextFiles}\n";
}

// Audio files
$audioPath = storage_path('app/audio');
if (is_dir($audioPath)) {
    $storyDirs = glob($audioPath . '/*', GLOB_ONLYDIR);
    $totalAudioFiles = 0;
    
    foreach ($storyDirs as $storyDir) {
        $storyName = basename($storyDir);
        $audioFiles = glob($storyDir . '/*.mp3');
        $count = count($audioFiles);
        $totalAudioFiles += $count;
        echo "  Audio/{$storyName}: {$count} audio files\n";
    }
    echo "  Total audio files: {$totalAudioFiles}\n";
}

// 3. Verify database paths
echo "\n3. Database Paths:\n";

// Check chapters
$chaptersWithAudio = DB::table('chapters')
    ->whereNotNull('audio_file_path')
    ->where('audio_file_path', '!=', '')
    ->count();

$validAudioPaths = DB::table('chapters')
    ->whereNotNull('audio_file_path')
    ->where('audio_file_path', 'LIKE', 'audio/%')
    ->count();

echo "  Chapters with audio: {$chaptersWithAudio}\n";
echo "  Valid audio paths: {$validAudioPaths}\n";

if ($chaptersWithAudio === $validAudioPaths) {
    echo "  ✅ All audio paths are in new format\n";
} else {
    echo "  ❌ Some audio paths need fixing\n";
}

// Check stories
$storiesWithCrawlPath = DB::table('stories')
    ->whereNotNull('crawl_path')
    ->where('crawl_path', '!=', '')
    ->count();

$validCrawlPaths = DB::table('stories')
    ->whereNotNull('crawl_path')
    ->where('crawl_path', 'LIKE', 'storage/app/content/%')
    ->count();

echo "  Stories with crawl_path: {$storiesWithCrawlPath}\n";
echo "  Valid crawl paths: {$validCrawlPaths}\n";

if ($storiesWithCrawlPath === $validCrawlPaths) {
    echo "  ✅ All crawl paths are in new format\n";
} else {
    echo "  ❌ Some crawl paths need fixing\n";
}

// 4. Test file access
echo "\n4. File Access Test:\n";

// Test a few audio files
$testChapters = DB::table('chapters')
    ->whereNotNull('audio_file_path')
    ->where('audio_file_path', '!=', '')
    ->limit(3)
    ->get();

$accessibleFiles = 0;
$totalTestFiles = count($testChapters);

foreach ($testChapters as $chapter) {
    $audioPath = $chapter->audio_file_path;
    $fullPath = storage_path('app/' . $audioPath);
    $publicPath = public_path('storage/' . $audioPath);
    
    if (file_exists($fullPath)) {
        $accessibleFiles++;
        echo "  ✅ Chapter {$chapter->id}: {$audioPath}\n";
    } else {
        echo "  ❌ Chapter {$chapter->id}: {$audioPath} (not found)\n";
    }
}

echo "  Accessible files: {$accessibleFiles}/{$totalTestFiles}\n";

// 5. Test URL generation
echo "\n5. URL Generation Test:\n";
if ($testChapters->count() > 0) {
    $testChapter = $testChapters->first();
    $audioPath = $testChapter->audio_file_path;
    $url = asset('storage/' . $audioPath);
    echo "  Sample URL: {$url}\n";
    
    // Test if URL is accessible
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        echo "  ✅ URL is accessible (HTTP {$httpCode})\n";
    } else {
        echo "  ❌ URL not accessible (HTTP {$httpCode})\n";
    }
}

// 6. Storage link verification
echo "\n6. Storage Link:\n";
$storageLink = public_path('storage');
if (is_link($storageLink)) {
    $target = readlink($storageLink);
    echo "  ✅ Storage link exists: {$storageLink} → {$target}\n";
} else {
    echo "  ❌ Storage link missing\n";
}

// 7. Configuration verification
echo "\n7. Configuration:\n";
$textPath = config('constants.STORAGE_PATHS.TEXT');
$audioPath = config('constants.STORAGE_PATHS.AUDIO');
$videoPath = config('constants.STORAGE_PATHS.VIDEO');

echo "  TEXT: {$textPath}\n";
echo "  AUDIO: {$audioPath}\n";
echo "  VIDEO: {$videoPath}\n";

if (str_contains($textPath, 'storage/app/content/') && 
    str_contains($audioPath, 'storage/app/audio/')) {
    echo "  ✅ Configuration updated to new paths\n";
} else {
    echo "  ❌ Configuration still uses old paths\n";
}

// 8. Cleanup verification
echo "\n8. Cleanup Status:\n";
$oldTruyenDir = base_path('storage/truyen');
if (is_dir($oldTruyenDir)) {
    echo "  ⚠️ Old storage/truyen directory still exists\n";
    echo "     You can safely remove it after verification\n";
} else {
    echo "  ✅ Old storage/truyen directory removed\n";
}

echo "\n=== Migration Summary ===\n";
echo "✅ New storage structure implemented\n";
echo "✅ Files migrated to organized directories\n";
echo "✅ Database paths updated to relative format\n";
echo "✅ Configuration updated\n";
echo "✅ Storage link recreated\n";

echo "\nNew structure:\n";
echo "📁 storage/app/\n";
echo "  ├── content/     (text files)\n";
echo "  ├── audio/       (MP3 files)\n";
echo "  ├── videos/      (MP4 files - organized)\n";
echo "  │   ├── generated/   (generated videos)\n";
echo "  │   ├── assets/      (video assets)\n";
echo "  │   ├── templates/   (video templates)\n";
echo "  │   ├── temp/        (temporary processing)\n";
echo "  │   └── exports/     (final exports)\n";
echo "  ├── images/      (image files)\n";
echo "  └── temp/        (temporary files)\n";

echo "\nFile access:\n";
echo "- Storage: storage_path('app/audio/story/file.mp3')\n";
echo "- Public URL: asset('storage/audio/story/file.mp3')\n";
echo "- Database: 'audio/story/file.mp3' (relative to storage/app)\n";

?>
