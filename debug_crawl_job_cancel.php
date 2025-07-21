<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Story;
use Illuminate\Support\Facades\DB;

echo "🔍 Debug Crawl Job Cancellation Issue\n";
echo "=====================================\n\n";

// 1. Check stories that had cancelled jobs
echo "1. Stories with recent job cancellations:\n";
$stories = Story::whereIn('id', [7, 8, 9])->get();

foreach ($stories as $story) {
    echo "   Story ID: {$story->id}\n";
    echo "   Title: {$story->title}\n";
    echo "   Crawl Status: {$story->crawl_status}\n";
    echo "   Job ID: " . ($story->crawl_job_id ?? 'NULL') . "\n";
    echo "   Auto Crawl: " . ($story->auto_crawl ? 'YES' : 'NO') . "\n";
    echo "   ---\n";
}

// 2. Check jobs table
echo "\n2. Recent jobs in queue:\n";
$jobs = DB::table('jobs')
    ->where('queue', 'crawl')
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get(['id', 'queue', 'payload', 'created_at']);

if ($jobs->isEmpty()) {
    echo "   No jobs in crawl queue\n";
} else {
    foreach ($jobs as $job) {
        $payload = json_decode($job->payload, true);
        $storyId = $payload['data']['storyId'] ?? 'Unknown';
        echo "   Job ID: {$job->id} | Story ID: {$storyId} | Created: {$job->created_at}\n";
    }
}

// 3. Check failed jobs
echo "\n3. Failed jobs:\n";
$failedJobs = DB::table('failed_jobs')
    ->orderBy('failed_at', 'desc')
    ->limit(5)
    ->get(['id', 'payload', 'exception', 'failed_at']);

if ($failedJobs->isEmpty()) {
    echo "   No failed jobs\n";
} else {
    foreach ($failedJobs as $job) {
        $payload = json_decode($job->payload, true);
        $storyId = $payload['data']['storyId'] ?? 'Unknown';
        echo "   Failed Job ID: {$job->id} | Story ID: {$storyId} | Failed: {$job->failed_at}\n";
        echo "   Exception: " . substr($job->exception, 0, 100) . "...\n";
        echo "   ---\n";
    }
}

// 4. Check constants
echo "\n4. Crawl status constants:\n";
$constants = config('constants.CRAWL_STATUS.VALUES');
foreach ($constants as $key => $value) {
    echo "   {$key}: {$value}\n";
}

// 5. Simulate job ID check
echo "\n5. Simulating job ID check logic:\n";
foreach ($stories as $story) {
    echo "   Story {$story->id}:\n";
    echo "     Current status: {$story->crawl_status}\n";
    echo "     Expected status (CRAWLING): " . config('constants.CRAWL_STATUS.VALUES.CRAWLING') . "\n";
    echo "     Status match: " . ($story->crawl_status === config('constants.CRAWL_STATUS.VALUES.CRAWLING') ? 'YES' : 'NO') . "\n";
    echo "     Job ID: " . ($story->crawl_job_id ?? 'NULL') . "\n";
    echo "     Would cancel: " . (
        $story->crawl_status !== config('constants.CRAWL_STATUS.VALUES.CRAWLING') || 
        $story->crawl_job_id === null ? 'YES' : 'NO'
    ) . "\n";
    echo "   ---\n";
}

echo "\n✅ Debug completed!\n";
