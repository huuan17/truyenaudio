<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Story;
use Illuminate\Support\Facades\DB;

echo "🔧 Fix Queue Conflict\n";
echo "====================\n\n";

// 1. Stop all queue processing
echo "1. Stopping queue processing...\n";
try {
    \Illuminate\Support\Facades\Artisan::call('queue:restart');
    echo "   ✅ Queue restart signal sent\n";
} catch (\Exception $e) {
    echo "   ⚠️ Queue restart failed: " . $e->getMessage() . "\n";
}

// 2. Clear all jobs from queue
echo "\n2. Clearing all jobs from queue...\n";
$deletedJobs = DB::table('jobs')->where('queue', 'crawl')->delete();
echo "   ✅ Deleted {$deletedJobs} jobs from crawl queue\n";

$deletedFailedJobs = DB::table('failed_jobs')->delete();
echo "   ✅ Deleted {$deletedFailedJobs} failed jobs\n";

// 3. Reset all stories to clean state
echo "\n3. Resetting all stories to clean state...\n";
$affectedStories = Story::whereIn('crawl_status', [1, 3])->get(); // CRAWLING or RE_CRAWL

foreach ($affectedStories as $story) {
    echo "   Resetting story {$story->id}: {$story->title}\n";
    $story->update([
        'crawl_status' => 0, // NOT_CRAWLED
        'crawl_job_id' => null
    ]);
}

echo "   ✅ Reset {$affectedStories->count()} stories\n";

// 4. Clear caches
echo "\n4. Clearing caches...\n";
\Illuminate\Support\Facades\Artisan::call('config:clear');
\Illuminate\Support\Facades\Artisan::call('cache:clear');
echo "   ✅ Caches cleared\n";

// 5. Verify clean state
echo "\n5. Verifying clean state...\n";

$remainingJobs = DB::table('jobs')->where('queue', 'crawl')->count();
echo "   Jobs in crawl queue: {$remainingJobs}\n";

$crawlingStories = Story::where('crawl_status', 3)->count();
echo "   Stories in CRAWLING status: {$crawlingStories}\n";

$storiesWithJobId = Story::whereNotNull('crawl_job_id')->count();
echo "   Stories with job ID: {$storiesWithJobId}\n";

// 6. Show current status of stories 7, 8, 9
echo "\n6. Current status of stories 7, 8, 9:\n";
$stories = Story::whereIn('id', [7, 8, 9])->get();
foreach ($stories as $story) {
    echo "   Story {$story->id}: {$story->title}\n";
    echo "     Status: {$story->crawl_status} (NOT_CRAWLED)\n";
    echo "     Job ID: " . ($story->crawl_job_id ?? 'NULL') . "\n";
    echo "   ---\n";
}

// 7. Recommendations for next steps
echo "\n7. Next steps:\n";
echo "   1. Start queue worker: start-queue-worker.bat (option 2)\n";
echo "   2. Add ONE story at a time from crawl monitor\n";
echo "   3. Wait for each story to complete before adding next\n";
echo "   4. Monitor logs for any issues\n";

echo "\n8. Prevention tips:\n";
echo "   - Only add one story to queue at a time\n";
echo "   - Wait for current crawl to finish before adding next\n";
echo "   - Don't interrupt running queue workers\n";
echo "   - Monitor queue status regularly\n";

if ($remainingJobs == 0 && $crawlingStories == 0 && $storiesWithJobId == 0) {
    echo "\n✅ System is now clean and ready for testing!\n";
} else {
    echo "\n⚠️ System may still have issues. Check the counts above.\n";
}

echo "\n🎯 Ready to test: Add one story at a time from crawl monitor\n";
