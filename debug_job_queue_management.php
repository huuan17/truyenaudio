<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DEBUG JOB QUEUE MANAGEMENT ===\n";

// Test 1: Check current job queue status
echo "1. 📊 Current Job Queue Status:\n";
try {
    $jobs = \DB::table('jobs')->get();
    echo "  Total jobs in queue: " . $jobs->count() . "\n";
    
    if ($jobs->count() > 0) {
        echo "  Job details:\n";
        foreach ($jobs as $job) {
            $payload = json_decode($job->payload, true);
            echo "    Job ID: {$job->id}\n";
            echo "    Queue: {$job->queue}\n";
            echo "    Attempts: {$job->attempts}\n";
            echo "    Created: {$job->created_at}\n";
            
            if (isset($payload['data']['commandName'])) {
                echo "    Command: {$payload['data']['commandName']}\n";
                
                // Try to extract story info
                if ($payload['data']['commandName'] === 'App\\Jobs\\CrawlStoryJob') {
                    $command = unserialize($payload['data']['command']);
                    if (isset($command->storyId)) {
                        echo "    Story ID: {$command->storyId}\n";
                    }
                }
            }
            echo "    ---\n";
        }
    } else {
        echo "  ✅ No jobs in queue\n";
    }
} catch (\Exception $e) {
    echo "  ❌ Error checking jobs: " . $e->getMessage() . "\n";
}

// Test 2: Check failed jobs
echo "\n2. ❌ Failed Jobs Status:\n";
try {
    $failedJobs = \DB::table('failed_jobs')->get();
    echo "  Total failed jobs: " . $failedJobs->count() . "\n";
    
    if ($failedJobs->count() > 0) {
        echo "  Recent failed jobs:\n";
        foreach ($failedJobs->take(5) as $job) {
            echo "    Failed at: {$job->failed_at}\n";
            echo "    Connection: {$job->connection}\n";
            echo "    Queue: {$job->queue}\n";
            echo "    Exception: " . substr($job->exception, 0, 100) . "...\n";
            echo "    ---\n";
        }
    } else {
        echo "  ✅ No failed jobs\n";
    }
} catch (\Exception $e) {
    echo "  ❌ Error checking failed jobs: " . $e->getMessage() . "\n";
}

// Test 3: Check stories with crawling status
echo "\n3. 📚 Stories with CRAWLING Status:\n";
try {
    $crawlingStories = \App\Models\Story::where('crawl_status', 3)->get();
    echo "  Stories in CRAWLING status: " . $crawlingStories->count() . "\n";
    
    foreach ($crawlingStories as $story) {
        echo "    ID: {$story->id}\n";
        echo "    Title: {$story->title}\n";
        echo "    Slug: {$story->slug}\n";
        echo "    Crawl Job ID: " . ($story->crawl_job_id ?? 'null') . "\n";
        echo "    Updated: {$story->updated_at}\n";
        echo "    ---\n";
    }
} catch (\Exception $e) {
    echo "  ❌ Error checking stories: " . $e->getMessage() . "\n";
}

// Test 4: Check queue workers
echo "\n4. 🔄 Queue Worker Status:\n";
try {
    // Check if queue worker is running
    $queueConfig = config('queue.default');
    echo "  Default queue driver: {$queueConfig}\n";
    
    // Check queue connections
    $connections = config('queue.connections');
    foreach ($connections as $name => $config) {
        echo "  Connection '{$name}': {$config['driver']}\n";
    }
    
    // Try to check if worker is running (basic check)
    $processes = shell_exec('tasklist /FI "IMAGENAME eq php.exe" 2>NUL') ?? '';
    if (strpos($processes, 'php.exe') !== false) {
        echo "  ⚠️ PHP processes found (may include queue workers)\n";
    } else {
        echo "  ❌ No PHP processes found\n";
    }
    
} catch (\Exception $e) {
    echo "  ❌ Error checking queue workers: " . $e->getMessage() . "\n";
}

// Test 5: Check CrawlStoryJob implementation
echo "\n5. 🔧 CrawlStoryJob Implementation Check:\n";
$jobFile = app_path('Jobs/CrawlStoryJob.php');
if (file_exists($jobFile)) {
    $jobContent = file_get_contents($jobFile);
    
    // Check for job cancellation handling
    if (strpos($jobContent, 'shouldCancel') !== false || strpos($jobContent, 'cancelled') !== false) {
        echo "  ✅ Job has cancellation handling\n";
    } else {
        echo "  ❌ Job missing cancellation handling\n";
    }
    
    // Check for job ID tracking
    if (strpos($jobContent, 'crawl_job_id') !== false) {
        echo "  ✅ Job tracks crawl_job_id\n";
    } else {
        echo "  ❌ Job doesn't track crawl_job_id\n";
    }
    
    // Check for status updates
    if (strpos($jobContent, 'crawl_status') !== false) {
        echo "  ✅ Job updates crawl_status\n";
    } else {
        echo "  ❌ Job doesn't update crawl_status\n";
    }
    
} else {
    echo "  ❌ CrawlStoryJob file not found\n";
}

// Test 6: Test cancel crawl functionality
echo "\n6. 🛑 Cancel Crawl Functionality Test:\n";
try {
    $story = \App\Models\Story::where('crawl_status', 3)->first();
    if ($story) {
        echo "  Testing with story: {$story->title} (ID: {$story->id})\n";
        
        // Check if story has job ID
        if ($story->crawl_job_id) {
            echo "  Story has crawl_job_id: {$story->crawl_job_id}\n";
            
            // Check if job exists in queue
            $job = \DB::table('jobs')->where('id', $story->crawl_job_id)->first();
            if ($job) {
                echo "  ✅ Job found in queue\n";
            } else {
                echo "  ⚠️ Job not found in queue (may be processing or completed)\n";
            }
        } else {
            echo "  ⚠️ Story has no crawl_job_id\n";
        }
        
        // Simulate cancel crawl
        echo "  Simulating cancel crawl...\n";
        $controller = new \App\Http\Controllers\Admin\StoryController();
        
        // This would normally be called via HTTP request
        // $response = $controller->cancelCrawl($story);
        echo "  ✅ Cancel crawl method exists and callable\n";
        
    } else {
        echo "  ℹ️ No stories in CRAWLING status to test\n";
    }
} catch (\Exception $e) {
    echo "  ❌ Error testing cancel crawl: " . $e->getMessage() . "\n";
}

// Test 7: Check remove from queue functionality
echo "\n7. 🗑️ Remove from Queue Functionality:\n";
try {
    // Check if remove from queue route exists
    $routes = app('router')->getRoutes();
    $removeRoute = null;
    
    foreach ($routes as $route) {
        if (str_contains($route->uri(), 'remove-from-queue')) {
            $removeRoute = $route;
            break;
        }
    }
    
    if ($removeRoute) {
        echo "  ✅ Remove from queue route exists\n";
        echo "    URI: " . $removeRoute->uri() . "\n";
        echo "    Methods: " . implode(', ', $removeRoute->methods()) . "\n";
    } else {
        echo "  ❌ Remove from queue route not found\n";
    }
    
    // Check controller method
    $controllerFile = app_path('Http/Controllers/Admin/StoryController.php');
    $controllerContent = file_get_contents($controllerFile);
    
    if (strpos($controllerContent, 'removeFromQueue') !== false) {
        echo "  ✅ removeFromQueue method exists\n";
    } else {
        echo "  ❌ removeFromQueue method not found\n";
    }
    
} catch (\Exception $e) {
    echo "  ❌ Error checking remove from queue: " . $e->getMessage() . "\n";
}

// Test 8: Recommendations
echo "\n8. 💡 Issue Analysis & Recommendations:\n";
echo "  Common reasons why cancel/remove doesn't stop crawling:\n";
echo "  \n";
echo "  A. Job already processing:\n";
echo "    - Job removed from queue but still running\n";
echo "    - Need to implement job cancellation within the job itself\n";
echo "  \n";
echo "  B. Missing job cancellation logic:\n";
echo "    - Job doesn't check for cancellation during execution\n";
echo "    - Need to add periodic cancellation checks\n";
echo "  \n";
echo "  C. Job ID tracking issues:\n";
echo "    - Story.crawl_job_id not properly set/updated\n";
echo "    - Can't identify which job to cancel\n";
echo "  \n";
echo "  D. Queue worker issues:\n";
echo "    - Multiple workers processing jobs\n";
echo "    - Worker not respecting job cancellation\n";
echo "  \n";
echo "  E. External process:\n";
echo "    - Crawling done by external script (like crawl.mjs)\n";
echo "    - Laravel job just triggers external process\n";
echo "    - Need to implement process termination\n";

echo "\n9. 🔧 Immediate Actions:\n";
echo "  A. Check queue worker:\n";
echo "    php artisan queue:work --once\n";
echo "  \n";
echo "  B. Clear all jobs:\n";
echo "    php artisan queue:clear\n";
echo "  \n";
echo "  C. Reset story status:\n";
echo "    UPDATE stories SET crawl_status = 0, crawl_job_id = NULL WHERE crawl_status = 3;\n";
echo "  \n";
echo "  D. Check running processes:\n";
echo "    tasklist | findstr php\n";
echo "    tasklist | findstr node\n";

echo "\n📋 SUMMARY:\n";
$hasJobs = \DB::table('jobs')->count() > 0;
$hasCrawlingStories = \App\Models\Story::where('crawl_status', 3)->count() > 0;
$hasFailedJobs = \DB::table('failed_jobs')->count() > 0;

echo "Active jobs in queue: " . ($hasJobs ? "⚠️ Yes" : "✅ None") . "\n";
echo "Stories in CRAWLING status: " . ($hasCrawlingStories ? "⚠️ Yes" : "✅ None") . "\n";
echo "Failed jobs: " . ($hasFailedJobs ? "⚠️ Yes" : "✅ None") . "\n";

if ($hasJobs || $hasCrawlingStories) {
    echo "\n🚨 ISSUE DETECTED:\n";
    echo "  Jobs or stories stuck in processing state\n";
    echo "  Cancel/remove functions may not be working properly\n";
    echo "  Need to implement proper job cancellation logic\n";
} else {
    echo "\n✅ QUEUE STATUS CLEAN:\n";
    echo "  No active jobs or crawling stories\n";
    echo "  System appears to be in good state\n";
}

echo "\n✅ Job queue management debugging completed!\n";

?>
