<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Story;
use Illuminate\Support\Facades\DB;

echo "🔧 Force Reset and Test\n";
echo "======================\n\n";

// 1. Clear all jobs in queue
echo "1. Clearing all jobs in queue...\n";
$deletedJobs = DB::table('jobs')->delete();
echo "   Deleted {$deletedJobs} jobs\n";

// 2. Clear failed jobs
echo "2. Clearing failed jobs...\n";
$deletedFailedJobs = DB::table('failed_jobs')->delete();
echo "   Deleted {$deletedFailedJobs} failed jobs\n";

// 3. Reset all stories with CRAWLING status
echo "3. Resetting all CRAWLING stories...\n";
$crawlingStories = Story::where('crawl_status', 3)->get();
foreach ($crawlingStories as $story) {
    echo "   Resetting story {$story->id}: {$story->title}\n";
    $story->update([
        'crawl_status' => 0,
        'crawl_job_id' => null
    ]);
}

// 4. Verify story 9 status
echo "\n4. Story 9 final status:\n";
$story9 = Story::find(9);
if ($story9) {
    echo "   ID: {$story9->id}\n";
    echo "   Title: {$story9->title}\n";
    echo "   Status: {$story9->crawl_status}\n";
    echo "   Job ID: " . ($story9->crawl_job_id ?? 'NULL') . "\n";
} else {
    echo "   ❌ Story 9 not found\n";
}

echo "\n✅ Force reset completed!\n";
echo "\n💡 Next steps:\n";
echo "   1. Restart queue worker to use new code\n";
echo "   2. Test add story from crawl monitor\n";
echo "   3. Monitor logs for new behavior\n";
