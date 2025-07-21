<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Story;

echo "=== Clear Old Jobs & Test Clean Cancel ===\n";

// 1. Show current jobs
echo "1. Current jobs in queue:\n";
$jobs = DB::table('jobs')->get();
echo "  Total jobs: " . count($jobs) . "\n";

foreach ($jobs as $job) {
    $payload = json_decode($job->payload, true);
    $created = date('Y-m-d H:i:s', $job->created_at);
    echo "  Job {$job->id}: {$payload['displayName']} (created: {$created}, attempts: {$job->attempts})\n";
}

// 2. Clear all old jobs
echo "\n2. Clearing all old jobs...\n";
$deleted = DB::table('jobs')->delete();
echo "  ✅ Deleted {$deleted} jobs\n";

// 3. Reset all stories crawl status
echo "\n3. Resetting all stories crawl status...\n";
$updated = Story::where('crawl_status', '!=', 0)->update([
    'crawl_status' => 0,
    'crawl_job_id' => null
]);
echo "  ✅ Reset {$updated} stories to NOT_CRAWLED\n";

// 4. Test clean crawl dispatch
echo "\n4. Testing clean crawl dispatch...\n";
$story = Story::find(3);
if ($story) {
    echo "  Story: {$story->title}\n";
    echo "  Current status: {$story->crawl_status}\n";
    
    // Dispatch new clean job
    $job = \App\Jobs\CrawlStoryJob::dispatch($story->id);
    echo "  ✅ Dispatched new crawl job\n";
    
    // Wait for job to be queued
    sleep(1);
    
    // Check new job
    $newJobs = DB::table('jobs')->get();
    echo "  New jobs in queue: " . count($newJobs) . "\n";
    
    if (count($newJobs) > 0) {
        $latestJob = $newJobs->first();
        echo "  Latest job ID: {$latestJob->id}\n";
        
        // Update story with job ID
        $story->update([
            'crawl_status' => config('constants.CRAWL_STATUS.VALUES.CRAWLING'),
            'crawl_job_id' => $latestJob->id
        ]);
        
        echo "  ✅ Story updated: status=CRAWLING, job_id={$latestJob->id}\n";
    }
}

// 5. Verify final state
echo "\n5. Final verification:\n";
$story = $story->fresh();
$statusLabels = config('constants.CRAWL_STATUS.LABELS');
echo "  Story status: {$story->crawl_status} ({$statusLabels[$story->crawl_status]})\n";
echo "  Job ID: " . ($story->crawl_job_id ?? 'NULL') . "\n";

$finalJobs = DB::table('jobs')->count();
echo "  Jobs in queue: {$finalJobs}\n";

echo "\n6. Test instructions:\n";
echo "  1. Open: http://localhost:8000/admin/stories\n";
echo "  2. Find 'Vô thượng sát thần' story\n";
echo "  3. Should show 'Đang crawl' status with blue badge\n";
echo "  4. Should show cancel button (X) next to status\n";
echo "  5. Click cancel button to test cancellation\n";
echo "  6. Should change to 'Chưa crawl' and remove cancel button\n";
echo "  7. Real-time updates should work every 5 seconds\n";

echo "\n✅ Clean test setup completed!\n";

?>
