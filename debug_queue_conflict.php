<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Story;
use Illuminate\Support\Facades\DB;

echo "🔍 Debug Queue Conflict\n";
echo "======================\n\n";

// 1. Check all stories status
echo "1. Stories 7, 8, 9 current status:\n";
$stories = Story::whereIn('id', [7, 8, 9])->get();
foreach ($stories as $story) {
    echo "   Story {$story->id}: {$story->title}\n";
    echo "     Status: {$story->crawl_status}\n";
    echo "     Job ID: " . ($story->crawl_job_id ?? 'NULL') . "\n";
    echo "     Updated: {$story->updated_at}\n";
    echo "   ---\n";
}

// 2. Detailed queue analysis
echo "\n2. Detailed queue analysis:\n";
$jobs = DB::table('jobs')->where('queue', 'crawl')->orderBy('id')->get();

if ($jobs->isEmpty()) {
    echo "   ✅ No jobs in crawl queue\n";
} else {
    echo "   Found {$jobs->count()} jobs in crawl queue:\n";
    
    foreach ($jobs as $job) {
        echo "   Job ID: {$job->id}\n";
        echo "     Queue: {$job->queue}\n";
        echo "     Created: {$job->created_at}\n";
        echo "     Available: {$job->available_at}\n";
        echo "     Attempts: {$job->attempts}\n";
        
        // Decode payload to get story ID
        $payload = json_decode($job->payload, true);
        if (isset($payload['data']['storyId'])) {
            $storyId = $payload['data']['storyId'];
            echo "     Story ID: {$storyId}\n";
            
            // Check if this story exists and its current status
            $story = Story::find($storyId);
            if ($story) {
                echo "     Story Title: {$story->title}\n";
                echo "     Story Status: {$story->crawl_status}\n";
                echo "     Story Job ID: " . ($story->crawl_job_id ?? 'NULL') . "\n";
                
                // Check for conflicts
                if ($story->crawl_job_id && $story->crawl_job_id != $job->id) {
                    echo "     ⚠️ CONFLICT: Story job ID ({$story->crawl_job_id}) != Queue job ID ({$job->id})\n";
                }
            } else {
                echo "     ❌ Story not found!\n";
            }
        } else {
            echo "     ❌ Cannot extract story ID from payload\n";
            echo "     Payload: " . substr($job->payload, 0, 200) . "...\n";
        }
        echo "   ---\n";
    }
}

// 3. Check for running processes
echo "\n3. Check for conflicts:\n";

// Find stories with CRAWLING status but no matching job in queue
$crawlingStories = Story::where('crawl_status', 3)->get(); // CRAWLING = 3
foreach ($crawlingStories as $story) {
    $hasMatchingJob = false;
    foreach ($jobs as $job) {
        $payload = json_decode($job->payload, true);
        $jobStoryId = $payload['data']['storyId'] ?? null;
        if ($jobStoryId == $story->id) {
            $hasMatchingJob = true;
            break;
        }
    }
    
    if (!$hasMatchingJob) {
        echo "   ⚠️ Story {$story->id} is CRAWLING but has no job in queue\n";
    }
}

// Find jobs in queue for stories not in CRAWLING status
foreach ($jobs as $job) {
    $payload = json_decode($job->payload, true);
    $jobStoryId = $payload['data']['storyId'] ?? null;
    if ($jobStoryId) {
        $story = Story::find($jobStoryId);
        if ($story && $story->crawl_status != 3) {
            echo "   ⚠️ Job {$job->id} for story {$jobStoryId} but story status is {$story->crawl_status} (not CRAWLING)\n";
        }
    }
}

// 4. Recommendations
echo "\n4. Recommendations:\n";

if ($jobs->count() > 1) {
    echo "   ⚠️ Multiple jobs in queue may cause conflicts\n";
    echo "   💡 Consider processing one job at a time\n";
}

$crawlingCount = Story::where('crawl_status', 3)->count();
if ($crawlingCount > 1) {
    echo "   ⚠️ Multiple stories in CRAWLING status: {$crawlingCount}\n";
    echo "   💡 Only one story should be crawling at a time\n";
}

echo "\n5. Suggested actions:\n";
echo "   1. Stop all queue workers\n";
echo "   2. Reset all CRAWLING stories to NOT_CRAWLED\n";
echo "   3. Clear all jobs from queue\n";
echo "   4. Start fresh with one story at a time\n";

echo "\n✅ Debug completed!\n";
