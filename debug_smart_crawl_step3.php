<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Story;

echo "=== DEBUG SMART CRAWL - STEP 3: Queue Worker & Job Execution ===\n";

$story = Story::find(3);

// Test 1: Check current queue status
echo "1. ⚡ Current Queue Status:\n";
$jobs = DB::table('jobs')->get();
echo "  Total jobs in queue: " . count($jobs) . "\n";

foreach ($jobs as $job) {
    $payload = json_decode($job->payload, true);
    $displayName = $payload['displayName'] ?? 'Unknown';
    $created = date('Y-m-d H:i:s', $job->created_at);
    
    echo "  Job ID {$job->id}:\n";
    echo "    Command: {$displayName}\n";
    echo "    Attempts: {$job->attempts}\n";
    echo "    Created: {$created}\n";
    echo "    Available at: " . date('Y-m-d H:i:s', $job->available_at) . "\n";
    
    // Check if it's CrawlStoryJob
    if (isset($payload['data']['commandName']) && $payload['data']['commandName'] === 'App\\Jobs\\CrawlStoryJob') {
        try {
            $jobData = unserialize($payload['data']['command']);
            if (isset($jobData->storyId)) {
                echo "    Story ID: {$jobData->storyId}\n";
                if ($jobData->storyId == $story->id) {
                    echo "    ✅ This is our story's job\n";
                }
            }
        } catch (Exception $e) {
            echo "    ⚠️ Could not parse job data: " . $e->getMessage() . "\n";
        }
    }
    echo "\n";
}

// Test 2: Check failed jobs
echo "2. ❌ Failed Jobs Check:\n";
$failedJobs = DB::table('failed_jobs')->get();
echo "  Total failed jobs: " . count($failedJobs) . "\n";

if (count($failedJobs) > 0) {
    foreach ($failedJobs as $failedJob) {
        $payload = json_decode($failedJob->payload, true);
        $displayName = $payload['displayName'] ?? 'Unknown';
        
        echo "  Failed Job ID {$failedJob->id}:\n";
        echo "    Command: {$displayName}\n";
        echo "    Failed at: {$failedJob->failed_at}\n";
        echo "    Exception: " . substr($failedJob->exception, 0, 200) . "...\n";
        echo "\n";
    }
}

// Test 3: Check queue worker processes
echo "3. 🔄 Queue Worker Check:\n";

// Check if queue worker is running (Windows)
$processes = shell_exec('tasklist /FI "IMAGENAME eq php.exe" 2>nul');
if ($processes) {
    $lines = explode("\n", $processes);
    $phpProcesses = array_filter($lines, function($line) {
        return strpos($line, 'php.exe') !== false;
    });
    
    echo "  PHP processes running: " . count($phpProcesses) . "\n";
    
    foreach ($phpProcesses as $process) {
        if (trim($process)) {
            echo "    " . trim($process) . "\n";
        }
    }
} else {
    echo "  ⚠️ Could not check running processes\n";
}

// Test 4: Test manual job execution
echo "\n4. 🧪 Manual Job Execution Test:\n";

if (count($jobs) > 0) {
    $job = $jobs->first();
    echo "  Testing job ID: {$job->id}\n";
    
    try {
        // Try to process the job manually
        echo "  Attempting manual job processing...\n";
        
        $payload = json_decode($job->payload, true);
        if (isset($payload['data']['commandName']) && $payload['data']['commandName'] === 'App\\Jobs\\CrawlStoryJob') {
            $jobData = unserialize($payload['data']['command']);
            
            echo "  Job type: CrawlStoryJob\n";
            echo "  Story ID: {$jobData->storyId}\n";
            
            // Check if we can instantiate the job
            $crawlJob = new App\Jobs\CrawlStoryJob($jobData->storyId);
            echo "  ✅ Job instantiated successfully\n";
            
            // Don't actually run it, just check if it's valid
            echo "  ⚠️ Skipping actual execution for safety\n";
            
        } else {
            echo "  ❌ Not a CrawlStoryJob\n";
        }
        
    } catch (Exception $e) {
        echo "  ❌ Manual job test error: " . $e->getMessage() . "\n";
    }
} else {
    echo "  ℹ️ No jobs to test\n";
}

// Test 5: Check queue configuration
echo "\n5. ⚙️ Queue Configuration:\n";
echo "  Queue driver: " . config('queue.default') . "\n";
echo "  Queue connection: " . config('queue.connections.' . config('queue.default') . '.driver') . "\n";

if (config('queue.default') === 'database') {
    echo "  Database queue table exists: " . (Schema::hasTable('jobs') ? "✅ Yes" : "❌ No") . "\n";
    echo "  Failed jobs table exists: " . (Schema::hasTable('failed_jobs') ? "✅ Yes" : "❌ No") . "\n";
}

// Test 6: Check story status and job tracking
echo "\n6. 📊 Story Status & Job Tracking:\n";
$story = $story->fresh();
echo "  Current crawl status: {$story->crawl_status}\n";
echo "  Current job ID: " . ($story->crawl_job_id ?? 'NULL') . "\n";

$statusLabels = config('constants.CRAWL_STATUS.LABELS');
echo "  Status label: " . ($statusLabels[$story->crawl_status] ?? 'Unknown') . "\n";

// Check if the job ID matches any queue job
if ($story->crawl_job_id) {
    $matchingJob = DB::table('jobs')->where('id', $story->crawl_job_id)->first();
    if ($matchingJob) {
        echo "  ✅ Job ID matches queue job\n";
    } else {
        echo "  ❌ Job ID doesn't match any queue job\n";
    }
}

echo "\n📋 STEP 3 SUMMARY:\n";
echo "Queue jobs: " . count($jobs) . "\n";
echo "Failed jobs: " . count($failedJobs) . "\n";
echo "Queue driver: " . config('queue.default') . "\n";
echo "Story status: {$story->crawl_status} (" . ($statusLabels[$story->crawl_status] ?? 'Unknown') . ")\n";

if (count($jobs) > 0 && count($failedJobs) === 0) {
    echo "✅ DIAGNOSIS: Jobs are queued but may need queue worker\n";
    echo "💡 SOLUTION: Start queue worker with: php artisan queue:work\n";
} elseif (count($failedJobs) > 0) {
    echo "❌ DIAGNOSIS: Jobs are failing\n";
    echo "💡 SOLUTION: Check failed job exceptions above\n";
} elseif (count($jobs) === 0) {
    echo "⚠️ DIAGNOSIS: No jobs in queue\n";
    echo "💡 SOLUTION: Jobs may have been processed or failed\n";
}

echo "\n➡️ NEXT: Start queue worker or check specific error\n";

?>
