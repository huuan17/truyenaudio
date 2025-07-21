<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Story;
use Illuminate\Support\Facades\DB;

echo "🔧 Reset Stuck Crawl Jobs\n";
echo "========================\n\n";

// 1. Clear all pending crawl jobs
echo "1. Clearing pending crawl jobs...\n";
$deletedJobs = DB::table('jobs')->where('queue', 'crawl')->delete();
echo "   Deleted {$deletedJobs} pending crawl jobs\n\n";

// 2. Reset stories with CRAWLING status
echo "2. Resetting stories with CRAWLING status...\n";
$crawlingStories = Story::where('crawl_status', config('constants.CRAWL_STATUS.VALUES.CRAWLING'))->get();

foreach ($crawlingStories as $story) {
    echo "   Resetting story ID: {$story->id} - {$story->title}\n";
    $story->update([
        'crawl_status' => config('constants.CRAWL_STATUS.VALUES.NOT_CRAWLED'),
        'crawl_job_id' => null
    ]);
}

echo "   Reset {$crawlingStories->count()} stories\n\n";

// 3. Clear failed jobs
echo "3. Clearing failed jobs...\n";
$deletedFailedJobs = DB::table('failed_jobs')->delete();
echo "   Deleted {$deletedFailedJobs} failed jobs\n\n";

// 4. Show current status
echo "4. Current status after reset:\n";
$stories = Story::whereIn('id', [7, 8, 9])->get();

foreach ($stories as $story) {
    echo "   Story ID: {$story->id}\n";
    echo "   Title: {$story->title}\n";
    echo "   Status: {$story->crawl_status}\n";
    echo "   Job ID: " . ($story->crawl_job_id ?? 'NULL') . "\n";
    echo "   Auto Crawl: " . ($story->auto_crawl ? 'YES' : 'NO') . "\n";
    echo "   ---\n";
}

echo "\n✅ Reset completed!\n";
echo "\n💡 Next steps:\n";
echo "   1. Start queue worker: start-queue-worker.bat (option 2)\n";
echo "   2. Test auto crawl from admin panel\n";
echo "   3. Monitor logs: tail -f storage/logs/laravel.log\n";
