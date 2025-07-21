<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Story;
use App\Jobs\CrawlStoryJob;
use Illuminate\Support\Facades\DB;

echo "🚀 Create Fresh Test Job\n";
echo "=======================\n\n";

// 1. Complete reset
echo "1. Complete system reset...\n";

// Kill all jobs
DB::table('jobs')->delete();
DB::table('failed_jobs')->delete();
echo "   ✅ All jobs cleared\n";

// Reset story 9
$story = Story::find(9);
$story->update([
    'crawl_status' => 0,
    'crawl_job_id' => null
]);
echo "   ✅ Story 9 reset\n";

// Clear caches
\Illuminate\Support\Facades\Artisan::call('config:clear');
\Illuminate\Support\Facades\Artisan::call('cache:clear');
echo "   ✅ Caches cleared\n";

// 2. Create new job
echo "\n2. Creating fresh job...\n";
CrawlStoryJob::dispatch(9);

$job = DB::table('jobs')->where('queue', 'crawl')->first();
if ($job) {
    echo "   ✅ New job created: ID {$job->id}\n";
    echo "   Queue: {$job->queue}\n";
    echo "   Created: {$job->created_at}\n";
} else {
    echo "   ❌ No job created\n";
    exit;
}

// 3. Verify story state
echo "\n3. Story state after job creation:\n";
$story->refresh();
echo "   Status: {$story->crawl_status}\n";
echo "   Job ID: " . ($story->crawl_job_id ?? 'NULL') . "\n";

echo "\n4. Ready for testing!\n";
echo "   Job ID in queue: {$job->id}\n";
echo "   Story status: {$story->crawl_status}\n";
echo "   Story job ID: " . ($story->crawl_job_id ?? 'NULL') . "\n";

echo "\n💡 Next step:\n";
echo "   Run: php artisan queue:work --once --queue=crawl\n";
echo "   Expected: Job should NOT cancel itself\n";

echo "\n✅ Fresh test job ready!\n";
