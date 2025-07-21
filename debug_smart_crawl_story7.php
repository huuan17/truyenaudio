<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Story;
use App\Jobs\CrawlStoryJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

echo "=== DEBUG SMART CRAWL FOR STORY 7 ===\n";

$story = Story::find(7);

if (!$story) {
    echo "❌ Story 7 not found!\n";
    exit(1);
}

echo "📖 Story Info:\n";
echo "  ID: {$story->id}\n";
echo "  Title: {$story->title}\n";
echo "  Current crawl_status: {$story->crawl_status}\n";
echo "  Auto crawl: " . ($story->auto_crawl ? 'Yes' : 'No') . "\n";
echo "  Crawl job ID: " . ($story->crawl_job_id ?? 'null') . "\n";

echo "\n🔍 Constants Check:\n";
echo "  NOT_CRAWLED: " . config('constants.CRAWL_STATUS.VALUES.NOT_CRAWLED') . "\n";
echo "  CRAWLING: " . config('constants.CRAWL_STATUS.VALUES.CRAWLING') . "\n";
echo "  CRAWLED: " . config('constants.CRAWL_STATUS.VALUES.CRAWLED') . "\n";

echo "\n📊 Jobs in Queue:\n";
$jobs = DB::table('jobs')->get();
echo "  Total jobs: " . $jobs->count() . "\n";
foreach($jobs as $job) {
    echo "  Job ID: {$job->id}, Queue: {$job->queue}, Attempts: {$job->attempts}\n";
}

echo "\n🧪 Testing Smart Crawl Logic:\n";

// Step 1: Check existing chapters
$existingChapters = $story->chapters()->pluck('chapter_number')->toArray();
echo "  Existing chapters: " . implode(', ', $existingChapters) . "\n";

// Step 2: Calculate missing chapters
$allChapters = range($story->start_chapter, $story->end_chapter);
$missingChapters = array_diff($allChapters, $existingChapters);
echo "  Missing chapters: " . implode(', ', $missingChapters) . "\n";

if (empty($missingChapters)) {
    echo "  ✅ No missing chapters - smart crawl not needed\n";
} else {
    echo "  📝 Missing " . count($missingChapters) . " chapters - smart crawl needed\n";
    
    // Step 3: Test job dispatch
    echo "\n🚀 Testing Job Dispatch:\n";
    
    // Update story status first
    echo "  Setting story status to NOT_CRAWLED...\n";
    $story->update([
        'crawl_status' => config('constants.CRAWL_STATUS.VALUES.NOT_CRAWLED')
    ]);
    
    echo "  Story status after update: " . $story->fresh()->crawl_status . "\n";
    
    // Dispatch job
    echo "  Dispatching CrawlStoryJob...\n";
    try {
        CrawlStoryJob::dispatch($story->id);
        echo "  ✅ Job dispatched successfully\n";
        
        // Check if job was added to queue
        $newJobs = DB::table('jobs')->get();
        echo "  Jobs in queue after dispatch: " . $newJobs->count() . "\n";
        
        if ($newJobs->count() > $jobs->count()) {
            $newJob = $newJobs->last();
            echo "  New job ID: {$newJob->id}\n";
            echo "  Job payload preview: " . substr($newJob->payload, 0, 100) . "...\n";
        }
        
    } catch (Exception $e) {
        echo "  ❌ Job dispatch failed: " . $e->getMessage() . "\n";
    }
}

echo "\n🔚 Debug completed.\n";
