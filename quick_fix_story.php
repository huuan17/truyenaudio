<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Story;

echo "=== Quick Fix Story Chapter Range ===\n";

$story = Story::find(3);
if (!$story) {
    echo "❌ Story not found\n";
    exit(1);
}

echo "Current story:\n";
echo "  Title: {$story->title}\n";
echo "  Current range: {$story->start_chapter} - {$story->end_chapter}\n";

// Update to small range for testing
$story->start_chapter = 1;
$story->end_chapter = 5;
$story->crawl_status = 0; // Reset status
$story->save();

echo "✅ Updated story:\n";
echo "  New range: {$story->start_chapter} - {$story->end_chapter}\n";
echo "  Status: Reset to 'chưa crawl'\n";

echo "\nNow you can test crawl with smaller range:\n";
echo "php artisan crawl:stories --story_id=3\n";

?>
