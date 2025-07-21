<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Story;
use Illuminate\Support\Facades\DB;

echo "🔍 Debug Job ID Issue\n";
echo "====================\n\n";

// 1. Check story 9 current status
echo "1. Story 9 current status:\n";
$story = Story::find(9);
if ($story) {
    echo "   ID: {$story->id}\n";
    echo "   Title: {$story->title}\n";
    echo "   Crawl Status: {$story->crawl_status}\n";
    echo "   Job ID: " . ($story->crawl_job_id ?? 'NULL') . " (type: " . gettype($story->crawl_job_id) . ")\n";
    echo "   Updated: {$story->updated_at}\n";
} else {
    echo "   ❌ Story not found\n";
}

echo "\n2. Jobs in queue for story 9:\n";
$jobs = DB::table('jobs')->where('queue', 'crawl')->get();

$foundStory9Jobs = [];
foreach ($jobs as $job) {
    $payload = json_decode($job->payload, true);
    $storyId = $payload['data']['storyId'] ?? null;
    
    if ($storyId == 9) {
        $foundStory9Jobs[] = [
            'job_id' => $job->id,
            'story_id' => $storyId,
            'created_at' => $job->created_at,
            'available_at' => $job->available_at
        ];
        
        echo "   Job ID: {$job->id} (type: " . gettype($job->id) . ")\n";
        echo "   Story ID: {$storyId}\n";
        echo "   Created: {$job->created_at}\n";
        echo "   Available: {$job->available_at}\n";
        echo "   ---\n";
    }
}

if (empty($foundStory9Jobs)) {
    echo "   ✅ No jobs found for story 9 in queue\n";
}

echo "\n3. All jobs in crawl queue:\n";
foreach ($jobs as $job) {
    $payload = json_decode($job->payload, true);
    $storyId = $payload['data']['storyId'] ?? 'Unknown';
    
    echo "   Job ID: {$job->id} | Story ID: {$storyId} | Created: {$job->created_at}\n";
}

if ($jobs->isEmpty()) {
    echo "   ✅ No jobs in crawl queue\n";
}

echo "\n4. Failed jobs:\n";
$failedJobs = DB::table('failed_jobs')->orderBy('failed_at', 'desc')->limit(5)->get();

if ($failedJobs->isEmpty()) {
    echo "   ✅ No failed jobs\n";
} else {
    foreach ($failedJobs as $job) {
        $payload = json_decode($job->payload, true);
        $storyId = $payload['data']['storyId'] ?? 'Unknown';
        echo "   Failed Job ID: {$job->id} | Story ID: {$storyId} | Failed: {$job->failed_at}\n";
    }
}

echo "\n5. Analysis:\n";
if ($story && $story->crawl_job_id !== null) {
    $storedJobId = $story->crawl_job_id;
    echo "   Story has job ID: {$storedJobId} (type: " . gettype($storedJobId) . ")\n";
    
    // Check if this job ID exists in queue
    $jobExists = DB::table('jobs')->where('id', $storedJobId)->exists();
    echo "   Job exists in queue: " . ($jobExists ? 'YES' : 'NO') . "\n";
    
    if (!$jobExists) {
        echo "   ❌ Problem: Story references job ID that doesn't exist!\n";
        echo "   💡 Solution: Clear the job ID from story\n";
    }
    
    // Check if there are newer jobs for this story
    $newerJobs = [];
    foreach ($jobs as $job) {
        $payload = json_decode($job->payload, true);
        $storyId = $payload['data']['storyId'] ?? null;
        
        if ($storyId == 9 && $job->id > $storedJobId) {
            $newerJobs[] = $job->id;
        }
    }
    
    if (!empty($newerJobs)) {
        echo "   ⚠️ Found newer jobs: " . implode(', ', $newerJobs) . "\n";
        echo "   💡 These jobs should take over\n";
    }
}

echo "\n6. Recommended actions:\n";
if ($story && $story->crawl_job_id !== null) {
    $jobExists = DB::table('jobs')->where('id', $story->crawl_job_id)->exists();
    if (!$jobExists) {
        echo "   1. Clear orphaned job ID from story:\n";
        echo "      Story::find(9)->update(['crawl_job_id' => null]);\n";
    }
}

if (!empty($foundStory9Jobs)) {
    echo "   2. Process pending jobs manually:\n";
    echo "      php artisan queue:work --once --queue=crawl\n";
}

echo "   3. Reset story status if needed:\n";
echo "      Story::find(9)->update(['crawl_status' => 0, 'crawl_job_id' => null]);\n";

echo "\n✅ Debug completed!\n";
