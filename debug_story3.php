<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DEBUGGING STORY 3 ===\n";

$story = App\Models\Story::find(3);

if (!$story) {
    echo "Story 3 not found!\n";
    exit;
}

echo "Story: {$story->title}\n";
echo "Start chapter: {$story->start_chapter}\n";
echo "End chapter: {$story->end_chapter}\n";

$expectedTotal = $story->end_chapter - $story->start_chapter + 1;
echo "Expected total: {$expectedTotal}\n";

$actualChapters = $story->chapters()->count();
echo "Actual chapters in DB: {$actualChapters}\n";

$dbComplete = $actualChapters >= $expectedTotal;
echo "DB complete: " . ($dbComplete ? 'YES' : 'NO') . "\n";

echo "\nTesting getCrawlProgress():\n";
$progress = $story->getCrawlProgress();
print_r($progress);

echo "\nCurrent crawl_status: {$story->crawl_status}\n";

$statusLabels = [
    1 => 'Chưa crawl',
    2 => 'Đã crawl', 
    3 => 'Đang crawl',
    4 => 'Lỗi crawl',
    5 => 'Cần crawl lại'
];

echo "Status label: " . ($statusLabels[$story->crawl_status] ?? 'Unknown') . "\n";

if ($progress['db_complete'] && $story->crawl_status != 2) {
    echo "\n⚠️ This story should be updated to CRAWLED status!\n";
    echo "Running update...\n";
    
    $story->update([
        'crawl_status' => config('constants.CRAWL_STATUS.VALUES.CRAWLED'),
        'crawl_job_id' => null
    ]);
    
    echo "✅ Updated to CRAWLED status!\n";
}
