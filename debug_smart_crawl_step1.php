<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Story;
use App\Models\Chapter;

echo "=== DEBUG SMART CRAWL - STEP 1: Current Status ===\n";

// Check story
$story = Story::find(3);
if (!$story) {
    echo "❌ CRITICAL: Story ID 3 not found\n";
    exit(1);
}

echo "✅ Story found:\n";
echo "  ID: {$story->id}\n";
echo "  Title: {$story->title}\n";
echo "  Slug: {$story->slug}\n";
echo "  Crawl Status: {$story->crawl_status}\n";
echo "  Start Chapter: {$story->start_chapter}\n";
echo "  End Chapter: {$story->end_chapter}\n";
echo "  Folder Name: {$story->folder_name}\n";

// Check status labels
$statusLabels = config('constants.CRAWL_STATUS.LABELS');
$statusColors = config('constants.CRAWL_STATUS.COLORS');
echo "  Status Label: " . ($statusLabels[$story->crawl_status] ?? 'Unknown') . "\n";
echo "  Status Color: " . ($statusColors[$story->crawl_status] ?? 'Unknown') . "\n";

// Check chapters in database
echo "\n📊 Database Chapters:\n";
$totalChapters = $story->chapters()->count();
echo "  Total chapters in DB: {$totalChapters}\n";

if ($totalChapters > 0) {
    $minChapter = $story->chapters()->min('chapter_number');
    $maxChapter = $story->chapters()->max('chapter_number');
    echo "  Chapter range in DB: {$minChapter} - {$maxChapter}\n";
    
    // Sample chapters
    $sampleChapters = $story->chapters()->orderBy('chapter_number')->limit(5)->pluck('chapter_number')->toArray();
    echo "  Sample chapters: " . implode(', ', $sampleChapters) . "\n";
}

// Check expected vs actual
echo "\n🎯 Expected vs Actual:\n";
$expectedTotal = $story->end_chapter - $story->start_chapter + 1;
echo "  Expected total: {$expectedTotal} chapters\n";
echo "  Actual in DB: {$totalChapters} chapters\n";
echo "  Difference: " . ($expectedTotal - $totalChapters) . " chapters\n";

// Calculate missing chapters
$existingChapters = $story->chapters()->pluck('chapter_number')->toArray();
$allChapters = range($story->start_chapter, $story->end_chapter);
$missingChapters = array_diff($allChapters, $existingChapters);

echo "\n🔍 Missing Chapters Analysis:\n";
echo "  Missing count: " . count($missingChapters) . "\n";
if (count($missingChapters) > 0) {
    echo "  First 10 missing: " . implode(', ', array_slice($missingChapters, 0, 10)) . "\n";
    if (count($missingChapters) > 10) {
        echo "  ... and " . (count($missingChapters) - 10) . " more\n";
    }
}

// Check storage files
echo "\n📁 Storage Files Check:\n";
$storagePath = storage_path('app/content/' . $story->folder_name);
echo "  Storage path: {$storagePath}\n";
echo "  Path exists: " . (is_dir($storagePath) ? "✅ Yes" : "❌ No") . "\n";

if (is_dir($storagePath)) {
    $files = glob($storagePath . '/*.txt');
    echo "  Files found: " . count($files) . "\n";
    
    if (count($files) > 0) {
        $sampleFiles = array_slice($files, 0, 3);
        echo "  Sample files:\n";
        foreach ($sampleFiles as $file) {
            $filename = basename($file);
            $size = filesize($file);
            echo "    - {$filename} ({$size} bytes)\n";
        }
    }
}

// Check queue status
echo "\n⚡ Queue Status:\n";
$totalJobs = DB::table('jobs')->count();
echo "  Total jobs in queue: {$totalJobs}\n";

if ($totalJobs > 0) {
    $jobs = DB::table('jobs')->limit(3)->get();
    foreach ($jobs as $job) {
        $payload = json_decode($job->payload, true);
        $displayName = $payload['displayName'] ?? 'Unknown';
        echo "  Job: {$displayName} (attempts: {$job->attempts})\n";
    }
}

// Check crawl job ID
echo "\n🔗 Crawl Job Tracking:\n";
echo "  Story crawl_job_id: " . ($story->crawl_job_id ?? 'NULL') . "\n";

if ($story->crawl_job_id) {
    $job = DB::table('jobs')->where('id', $story->crawl_job_id)->first();
    if ($job) {
        echo "  ✅ Job found in queue\n";
        echo "  Job attempts: {$job->attempts}\n";
        echo "  Job created: " . date('Y-m-d H:i:s', $job->created_at) . "\n";
    } else {
        echo "  ❌ Job not found in queue (may have completed or failed)\n";
    }
}

echo "\n📋 STEP 1 SUMMARY:\n";
echo "Story: ✅ Found\n";
echo "Chapters: {$totalChapters} in DB, " . count($missingChapters) . " missing\n";
echo "Storage: " . (is_dir($storagePath) ? "✅ Exists" : "❌ Missing") . "\n";
echo "Queue: {$totalJobs} jobs\n";
echo "Status: {$story->crawl_status} (" . ($statusLabels[$story->crawl_status] ?? 'Unknown') . ")\n";

echo "\n➡️ NEXT: Run debug_smart_crawl_step2.php to test routes\n";

?>
