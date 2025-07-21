<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Story;
use App\Models\Chapter;
use Illuminate\Support\Facades\File;

echo "=== Comprehensive Crawl Debug ===\n";

// 1. Check story details
$story = Story::find(3);
if (!$story) {
    echo "❌ Story not found\n";
    exit(1);
}

echo "1. Story Information:\n";
echo "  ID: {$story->id}\n";
echo "  Title: {$story->title}\n";
echo "  Slug: {$story->slug}\n";
echo "  Source URL: {$story->source_url}\n";
echo "  Chapter Range: {$story->start_chapter} - {$story->end_chapter}\n";
echo "  Crawl Status: {$story->crawl_status} (" . config('constants.CRAWL_STATUS.LABELS')[$story->crawl_status] . ")\n";
echo "  Folder Name: {$story->folder_name}\n";

// 2. Check content directory
$contentDir = storage_path('app/content/' . $story->folder_name);
echo "\n2. Content Directory Check:\n";
echo "  Path: {$contentDir}\n";

if (is_dir($contentDir)) {
    $files = glob($contentDir . '/*.txt');
    echo "  ✅ Directory exists\n";
    echo "  📄 Files found: " . count($files) . "\n";
    
    if (count($files) > 0) {
        echo "  Recent files:\n";
        // Sort by modification time
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        foreach (array_slice($files, 0, 5) as $file) {
            $filename = basename($file);
            $size = round(filesize($file) / 1024, 2);
            $modified = date('Y-m-d H:i:s', filemtime($file));
            echo "    - {$filename} ({$size} KB, modified: {$modified})\n";
        }
    }
} else {
    echo "  ❌ Directory not found\n";
}

// 3. Check database chapters
echo "\n3. Database Chapters Check:\n";
$chapters = Chapter::where('story_id', $story->id)->orderBy('chapter_number')->get();
echo "  Total chapters in DB: " . count($chapters) . "\n";

if (count($chapters) > 0) {
    $crawledChapters = $chapters->where('is_crawled', true);
    echo "  Crawled chapters: " . count($crawledChapters) . "\n";
    echo "  Chapter range: {$chapters->min('chapter_number')} - {$chapters->max('chapter_number')}\n";
    
    echo "  Sample chapters:\n";
    foreach ($chapters->take(5) as $chapter) {
        $status = $chapter->is_crawled ? '✅' : '❌';
        $contentLength = strlen($chapter->content ?? '');
        $fileExists = $chapter->file_path && File::exists(storage_path('app/' . $chapter->file_path)) ? '📄' : '❌';
        echo "    {$status} Chapter {$chapter->chapter_number}: {$chapter->title} (content: {$contentLength} chars, file: {$fileExists})\n";
    }
} else {
    echo "  ⚠️ No chapters found in database\n";
    echo "  💡 This might be the issue - files exist but not imported to DB\n";
}

// 4. Check Node.js script
echo "\n4. Node.js Script Check:\n";
$scriptPath = base_path('node_scripts/crawl_original_cjs.cjs');
echo "  Script path: {$scriptPath}\n";

if (File::exists($scriptPath)) {
    echo "  ✅ Script exists\n";
    $scriptSize = round(File::size($scriptPath) / 1024, 2);
    echo "  Script size: {$scriptSize} KB\n";
    
    // Check script syntax
    $syntaxCheck = shell_exec("node -c {$scriptPath} 2>&1");
    if (empty(trim($syntaxCheck))) {
        echo "  ✅ Script syntax valid\n";
    } else {
        echo "  ❌ Script syntax error: " . trim($syntaxCheck) . "\n";
    }
} else {
    echo "  ❌ Script not found\n";
}

// 5. Test Node.js execution
echo "\n5. Node.js Environment Test:\n";
$nodeVersion = shell_exec('node --version 2>&1');
echo "  Node.js version: " . trim($nodeVersion) . "\n";

// Test simple script execution
$testScript = 'console.log("Node.js test successful"); process.exit(0);';
file_put_contents('test_node_debug.js', $testScript);
$nodeTest = shell_exec('node test_node_debug.js 2>&1');
echo "  Node.js test: " . trim($nodeTest) . "\n";
unlink('test_node_debug.js');

// 6. Test crawl script with single chapter
echo "\n6. Manual Crawl Test:\n";
$testOutputDir = storage_path('app/temp/debug_crawl_' . time());
File::makeDirectory($testOutputDir, 0755, true);

$testCommand = sprintf(
    'node %s %s %d %d %s %d',
    escapeshellarg($scriptPath),
    escapeshellarg($story->source_url),
    $story->start_chapter,
    $story->start_chapter, // Test only first chapter
    escapeshellarg($testOutputDir),
    1 // Single mode
);

echo "  Test command: {$testCommand}\n";
echo "  Executing...\n";

$output = [];
$exitCode = 0;
exec($testCommand . ' 2>&1', $output, $exitCode);

echo "  Exit code: {$exitCode}\n";
echo "  Output:\n";
foreach ($output as $line) {
    echo "    {$line}\n";
}

// Check if test file was created
$testFile = $testOutputDir . '/chuong-' . $story->start_chapter . '.txt';
if (File::exists($testFile)) {
    $content = File::get($testFile);
    echo "  ✅ Test file created: " . strlen($content) . " characters\n";
    echo "  Content preview: " . substr($content, 0, 100) . "...\n";
} else {
    echo "  ❌ Test file not created\n";
}

// Cleanup test directory
if (File::isDirectory($testOutputDir)) {
    File::deleteDirectory($testOutputDir);
}

// 7. Check CrawlStories command execution
echo "\n7. CrawlStories Command Analysis:\n";
echo "  Command class: App\\Console\\Commands\\CrawlStories\n";
echo "  Script used: {$scriptPath}\n";

// Check if command is registered
try {
    $commands = app('Illuminate\Contracts\Console\Kernel')->all();
    if (isset($commands['crawl:stories'])) {
        echo "  ✅ Command registered\n";
    } else {
        echo "  ❌ Command not registered\n";
    }
} catch (Exception $e) {
    echo "  ⚠️ Could not check command registration: " . $e->getMessage() . "\n";
}

// 8. Recommendations
echo "\n8. Debug Analysis:\n";

if (count($files) > 0 && count($chapters) == 0) {
    echo "  🔍 ISSUE IDENTIFIED: Files exist but not imported to database\n";
    echo "  💡 SOLUTION: Run import command\n";
    echo "    Command: php artisan import:chapters {$story->id}\n";
} elseif (count($files) == 0) {
    echo "  🔍 ISSUE IDENTIFIED: No content files found\n";
    echo "  💡 SOLUTION: Node.js script not creating files\n";
    if ($exitCode !== 0) {
        echo "    - Fix Node.js script execution error\n";
        echo "    - Check Puppeteer installation\n";
        echo "    - Check Chrome browser availability\n";
    }
} elseif ($story->crawl_status == 1 && count($chapters) > 0) {
    echo "  ✅ SYSTEM WORKING: Files exist and imported to database\n";
    echo "  💡 Check if chapters are displaying correctly in frontend\n";
} else {
    echo "  🔍 MIXED STATE: Need further investigation\n";
}

echo "\n9. Quick Fixes:\n";
echo "  Reset and re-crawl:\n";
echo "    php artisan tinker\n";
echo "    >>> Story::find({$story->id})->update(['crawl_status' => 0])\n";
echo "    >>> exit\n";
echo "    php artisan crawl:stories --story_id={$story->id}\n";
echo "\n  Import existing files:\n";
echo "    php artisan import:chapters {$story->id}\n";

echo "\n✅ Comprehensive debug completed!\n";

?>
