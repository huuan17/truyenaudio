<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Story;
use App\Models\Chapter;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

echo "=== Fix Crawl Issue for 'Vô thượng sát thần' ===\n";

// 1. Find the story
$story = Story::where('title', 'LIKE', '%Vô thượng sát thần%')
    ->orWhere('slug', 'vo-thuong-sat-than')
    ->orWhere('folder_name', 'vo-thuong-sat-than')
    ->first();

if (!$story) {
    echo "❌ Story not found. Let's check all stories:\n";
    $stories = Story::select('id', 'title', 'slug', 'folder_name', 'crawl_status', 'source_url')->get();
    foreach ($stories as $s) {
        echo "  ID {$s->id}: {$s->title}\n";
        echo "    Slug: {$s->slug}\n";
        echo "    Folder: {$s->folder_name}\n";
        echo "    Status: {$s->crawl_status}\n";
        echo "    URL: {$s->source_url}\n\n";
    }
    exit(1);
}

echo "✅ Found story:\n";
echo "  ID: {$story->id}\n";
echo "  Title: {$story->title}\n";
echo "  Slug: {$story->slug}\n";
echo "  Folder: {$story->folder_name}\n";
echo "  Crawl Status: {$story->crawl_status}\n";
echo "  Source URL: {$story->source_url}\n";
echo "  Start Chapter: {$story->start_chapter}\n";
echo "  End Chapter: {$story->end_chapter}\n";

// 2. Check if content directory exists
$contentDir = storage_path('app/content/' . $story->folder_name);
echo "\n2. Content Directory Check:\n";
echo "  Expected path: {$contentDir}\n";

if (is_dir($contentDir)) {
    $files = glob($contentDir . '/*.txt');
    echo "  ✅ Directory exists with " . count($files) . " files\n";
    
    if (count($files) > 0) {
        echo "  📄 Sample files:\n";
        foreach (array_slice($files, 0, 5) as $file) {
            $filename = basename($file);
            $size = round(filesize($file) / 1024, 2);
            echo "    - {$filename} ({$size} KB)\n";
        }
        
        echo "\n✅ Content files exist! The crawl was successful.\n";
        echo "💡 The issue might be that chapters are not imported to database.\n";
        
        // Check chapters in database
        $chapters = Chapter::where('story_id', $story->id)->count();
        echo "  Chapters in database: {$chapters}\n";
        
        if ($chapters == 0) {
            echo "\n🔧 Solution: Import content files to database\n";
            echo "  Run: php artisan import:chapters {$story->id}\n";
        }
        
        exit(0);
    }
} else {
    echo "  ❌ Directory does not exist\n";
}

// 3. Reset crawl status and try again
echo "\n3. Resetting crawl status and retrying...\n";

if ($story->crawl_status == 1) {
    echo "  Resetting crawl status from 'crawled' to 'not crawled'\n";
    $story->crawl_status = 0;
    $story->save();
}

// 4. Validate crawl parameters
echo "\n4. Validating crawl parameters:\n";

if (!$story->source_url) {
    echo "  ❌ Missing source URL\n";
    echo "  💡 Please set source_url for this story\n";
    exit(1);
}

if (!$story->start_chapter || !$story->end_chapter) {
    echo "  ❌ Missing chapter range\n";
    echo "  💡 Please set start_chapter and end_chapter\n";
    exit(1);
}

echo "  ✅ Source URL: {$story->source_url}\n";
echo "  ✅ Chapter range: {$story->start_chapter} - {$story->end_chapter}\n";

// 5. Test first chapter URL
echo "\n5. Testing first chapter URL:\n";
$testUrl = $story->source_url . $story->start_chapter . '.html';
echo "  Test URL: {$testUrl}\n";

// Simple URL test
$headers = @get_headers($testUrl);
if ($headers && strpos($headers[0], '200') !== false) {
    echo "  ✅ URL is accessible\n";
} else {
    echo "  ❌ URL is not accessible\n";
    echo "  💡 Check if the source website is working\n";
}

// 6. Create content directory
echo "\n6. Creating content directory:\n";
if (!is_dir($contentDir)) {
    File::makeDirectory($contentDir, 0755, true);
    echo "  ✅ Created directory: {$contentDir}\n";
} else {
    echo "  ✅ Directory already exists\n";
}

// 7. Test crawl script
echo "\n7. Testing crawl script:\n";
$scriptPath = base_path('node_scripts/crawl-production.js');
if (File::exists($scriptPath)) {
    echo "  ✅ Crawl script exists: {$scriptPath}\n";
} else {
    echo "  ❌ Crawl script not found\n";
    exit(1);
}

// 8. Manual test crawl for first chapter
echo "\n8. Manual test crawl for first chapter:\n";
$testOutput = storage_path('app/temp/test_crawl_' . time());
File::makeDirectory($testOutput, 0755, true);

$command = sprintf(
    'node %s %s %d %d %s %d',
    escapeshellarg($scriptPath),
    escapeshellarg($story->source_url),
    $story->start_chapter,
    $story->start_chapter,
    escapeshellarg($testOutput),
    1 // single mode
);

echo "  Command: {$command}\n";
echo "  Running test crawl...\n";

$output = [];
$exitCode = 0;
exec($command . ' 2>&1', $output, $exitCode);

echo "  Exit code: {$exitCode}\n";
if ($exitCode === 0) {
    echo "  ✅ Test crawl successful\n";
    
    $testFile = $testOutput . '/chuong-' . $story->start_chapter . '.txt';
    if (File::exists($testFile)) {
        $content = File::get($testFile);
        echo "  ✅ Content file created: " . strlen($content) . " characters\n";
        echo "  Preview: " . substr($content, 0, 100) . "...\n";
        
        // Copy to actual location
        $actualFile = $contentDir . '/chuong-' . $story->start_chapter . '.txt';
        File::copy($testFile, $actualFile);
        echo "  ✅ Copied to actual location\n";
    } else {
        echo "  ❌ Content file not created\n";
    }
} else {
    echo "  ❌ Test crawl failed\n";
    echo "  Output:\n";
    foreach ($output as $line) {
        echo "    {$line}\n";
    }
}

// Cleanup test directory
if (File::isDirectory($testOutput)) {
    File::deleteDirectory($testOutput);
}

// 9. Run actual crawl command
if ($exitCode === 0) {
    echo "\n9. Running actual crawl command:\n";
    echo "  php artisan crawl:stories --story_id={$story->id}\n";
    
    try {
        Artisan::call('crawl:stories', ['--story_id' => $story->id]);
        echo "  ✅ Crawl command executed\n";
        echo "  Output: " . Artisan::output() . "\n";
    } catch (Exception $e) {
        echo "  ❌ Crawl command failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "\n❌ Skipping actual crawl due to test failure\n";
}

// 10. Final check
echo "\n10. Final verification:\n";
if (is_dir($contentDir)) {
    $files = glob($contentDir . '/*.txt');
    echo "  📄 Content files: " . count($files) . "\n";
    
    if (count($files) > 0) {
        echo "  ✅ Crawl successful!\n";
        
        // Update crawl status
        $story->crawl_status = 1;
        $story->save();
        echo "  ✅ Updated crawl status to 'crawled'\n";
        
        echo "\n🎉 Success! Next steps:\n";
        echo "  1. Import chapters to database: php artisan import:chapters {$story->id}\n";
        echo "  2. Check story page: http://localhost:8000/admin/stories/{$story->slug}\n";
        echo "  3. Run TTS if needed\n";
    } else {
        echo "  ❌ No content files found\n";
    }
} else {
    echo "  ❌ Content directory not found\n";
}

echo "\n✅ Fix crawl issue completed!\n";

?>
