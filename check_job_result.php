<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Story;
use Illuminate\Support\Facades\DB;

echo "📊 Check Job Result\n";
echo "==================\n\n";

$story = Story::find(9);
echo "Story 9 status: {$story->crawl_status}\n";
echo "Story 9 job ID: " . ($story->crawl_job_id ?? 'NULL') . "\n";

$jobCount = DB::table('jobs')->where('queue', 'crawl')->count();
echo "Jobs in queue: {$jobCount}\n";

$failedCount = DB::table('failed_jobs')->count();
echo "Failed jobs: {$failedCount}\n";

// Check crawl status meaning
$statuses = [
    0 => 'NOT_CRAWLED',
    1 => 'CRAWLING', 
    2 => 'CRAWLED',
    3 => 'RE_CRAWL'
];

echo "\nStatus meaning: " . ($statuses[$story->crawl_status] ?? 'UNKNOWN') . "\n";

if ($story->crawl_status === 2) {
    echo "✅ Job completed successfully!\n";
} elseif ($story->crawl_status === 1) {
    echo "⏳ Job still running...\n";
} elseif ($story->crawl_status === 0) {
    echo "❓ Job may have been cancelled or not started\n";
} else {
    echo "❌ Job failed or needs re-crawl\n";
}
