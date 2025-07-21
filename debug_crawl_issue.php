<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Story;
use App\Models\Chapter;
use Illuminate\Support\Facades\File;

echo "=== Debug Crawl Issue ===\n";

// 1. Find "Vô thượng sát thần" story
echo "\n1. Finding 'Vô thượng sát thần' story:\n";

$story = Story::where('title', 'LIKE', '%Vô thượng sát thần%')
    ->orWhere('title', 'LIKE', '%vo-thuong-sat-than%')
    ->orWhere('slug', 'LIKE', '%vo-thuong-sat-than%')
    ->orWhere('folder_name', 'LIKE', '%vo-thuong-sat-than%')
    ->first();

if (!$story) {
    echo "❌ Story 'Vô thượng sát thần' not found\n";
    echo "Available stories:\n";
    $stories = Story::select('id', 'title', 'slug', 'folder_name', 'crawl_status')->get();
    foreach ($stories as $s) {
        echo "  ID {$s->id}: {$s->title} (slug: {$s->slug}, folder: {$s->folder_name}, status: {$s->crawl_status})\n";
    }
    exit(1);
}

echo "✅ Found story:\n";
echo "  ID: {$story->id}\n";
echo "  Title: {$story->title}\n";
echo "  Slug: {$story->slug}\n";
echo "  Folder name: {$story->folder_name}\n";
echo "  Source URL: {$story->source_url}\n";
echo "  Start chapter: {$story->start_chapter}\n";
echo "  End chapter: {$story->end_chapter}\n";
echo "  Crawl status: {$story->crawl_status} (" . config('constants.CRAWL_STATUS.LABELS')[$story->crawl_status] . ")\n";
echo "  Crawl path: {$story->crawl_path}\n";

// 2. Check storage directory
echo "\n2. Storage Directory Check:\n";

// Expected path from CrawlStories command
$expectedPath = storage_path('app/content/' . $story->folder_name);
echo "  Expected path: {$expectedPath}\n";

if (is_dir($expectedPath)) {
    echo "  ✅ Directory exists\n";
    $files = glob($expectedPath . '/*.txt');
    echo "  📄 Text files: " . count($files) . "\n";
    
    if (count($files) > 0) {
        echo "  Files found:\n";
        foreach ($files as $file) {
            $filename = basename($file);
            $size = round(filesize($file) / 1024, 2);
            $modified = date('Y-m-d H:i:s', filemtime($file));
            echo "    - {$filename} ({$size} KB, {$modified})\n";
        }
    } else {
        echo "  ⚠️ No text files found in directory\n";
    }
} else {
    echo "  ❌ Directory does not exist\n";
}

// Check old crawl_path if different
if ($story->crawl_path && $story->crawl_path !== $expectedPath) {
    $oldPath = base_path($story->crawl_path);
    echo "  Old crawl path: {$oldPath}\n";
    if (is_dir($oldPath)) {
        $oldFiles = glob($oldPath . '/*.txt');
        echo "  📄 Old path files: " . count($oldFiles) . "\n";
    } else {
        echo "  ❌ Old path does not exist\n";
    }
}

// 3. Check chapters in database
echo "\n3. Database Chapters Check:\n";
$chapters = Chapter::where('story_id', $story->id)->orderBy('chapter_number')->get();
echo "  Total chapters in DB: " . count($chapters) . "\n";

$crawledChapters = $chapters->where('is_crawled', true);
echo "  Crawled chapters: " . count($crawledChapters) . "\n";

if (count($chapters) > 0) {
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
}

// 4. Test crawl command manually
echo "\n4. Manual Crawl Test:\n";

if ($story->source_url && $story->start_chapter && $story->end_chapter) {
    echo "  Testing crawl parameters:\n";
    echo "    Source URL: {$story->source_url}\n";
    echo "    Chapter range: {$story->start_chapter} - {$story->end_chapter}\n";
    
    // Test first chapter URL
    $testUrl = $story->source_url . $story->start_chapter . '.html';
    echo "    Test URL: {$testUrl}\n";
    
    // Check if crawl script exists
    $scriptPath = base_path('node_scripts/crawl-production.js');
    if (File::exists($scriptPath)) {
        echo "  ✅ Crawl script exists: {$scriptPath}\n";
        
        // Test single chapter crawl
        echo "  🧪 Testing single chapter crawl...\n";
        $testOutput = storage_path('app/temp/crawl_test_' . time());
        if (!File::isDirectory($testOutput)) {
            File::makeDirectory($testOutput, 0755, true);
        }
        
        $command = sprintf(
            'node %s %s %d %d %s %d',
            escapeshellarg($scriptPath),
            escapeshellarg($story->source_url),
            $story->start_chapter,
            $story->start_chapter,
            escapeshellarg($testOutput),
            1 // single mode
        );
        
        echo "    Command: {$command}\n";
        
        $output = [];
        $exitCode = 0;
        exec($command . ' 2>&1', $output, $exitCode);
        
        echo "    Exit code: {$exitCode}\n";
        echo "    Output:\n";
        foreach ($output as $line) {
            echo "      {$line}\n";
        }
        
        // Check if file was created
        $testFile = $testOutput . '/chuong-' . $story->start_chapter . '.txt';
        if (File::exists($testFile)) {
            $content = File::get($testFile);
            echo "    ✅ Test file created: " . strlen($content) . " characters\n";
            echo "    Preview: " . substr($content, 0, 200) . "...\n";
        } else {
            echo "    ❌ Test file not created\n";
        }
        
        // Cleanup
        if (File::isDirectory($testOutput)) {
            File::deleteDirectory($testOutput);
        }
    } else {
        echo "  ❌ Crawl script not found: {$scriptPath}\n";
    }
} else {
    echo "  ❌ Missing crawl parameters:\n";
    echo "    Source URL: " . ($story->source_url ?: 'MISSING') . "\n";
    echo "    Start chapter: " . ($story->start_chapter ?: 'MISSING') . "\n";
    echo "    End chapter: " . ($story->end_chapter ?: 'MISSING') . "\n";
}

// 5. Check queue jobs
echo "\n5. Queue Jobs Check:\n";
try {
    $queueJobs = \Illuminate\Support\Facades\DB::table('jobs')->where('payload', 'LIKE', '%crawl:stories%')->get();
    echo "  Pending crawl jobs: " . count($queueJobs) . "\n";
    
    $failedJobs = \Illuminate\Support\Facades\DB::table('failed_jobs')->where('payload', 'LIKE', '%crawl:stories%')->get();
    echo "  Failed crawl jobs: " . count($failedJobs) . "\n";
    
    if (count($failedJobs) > 0) {
        echo "  Recent failed job:\n";
        $recentFailed = $failedJobs->sortByDesc('failed_at')->first();
        echo "    Failed at: {$recentFailed->failed_at}\n";
        echo "    Exception: " . substr($recentFailed->exception, 0, 200) . "...\n";
    }
} catch (Exception $e) {
    echo "  ⚠️ Could not check queue tables: " . $e->getMessage() . "\n";
}

// 6. Recommendations
echo "\n6. Recommendations:\n";

if ($story->crawl_status == 1 && count($crawledChapters) == 0) {
    echo "  🔍 Issue: Status is 'crawled' but no content found\n";
    echo "  💡 Possible causes:\n";
    echo "    - Crawl command failed silently\n";
    echo "    - Wrong storage path\n";
    echo "    - Node.js script errors\n";
    echo "    - Network/website issues\n";
    echo "  🛠️ Solutions:\n";
    echo "    1. Reset crawl status: UPDATE stories SET crawl_status = 0 WHERE id = {$story->id}\n";
    echo "    2. Run manual test crawl above\n";
    echo "    3. Check Laravel logs: storage/logs/laravel.log\n";
    echo "    4. Test website accessibility\n";
}

if (!$story->source_url) {
    echo "  ❌ Missing source URL - cannot crawl\n";
}

if (!$story->start_chapter || !$story->end_chapter) {
    echo "  ❌ Missing chapter range - cannot crawl\n";
}

echo "\n✅ Debug completed!\n";

echo "\nQuick fixes:\n";
echo "1. Reset crawl status: php artisan tinker -> Story::find({$story->id})->update(['crawl_status' => 0])\n";
echo "2. Manual crawl: php artisan crawl:stories --story_id={$story->id}\n";
echo "3. Check logs: tail -f storage/logs/laravel.log\n";

?>
