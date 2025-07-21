<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Story;

echo "🔧 Reset Story 9\n";
echo "===============\n\n";

$story = Story::find(9);
if ($story) {
    echo "Before reset:\n";
    echo "   Status: {$story->crawl_status}\n";
    echo "   Job ID: " . ($story->crawl_job_id ?? 'NULL') . "\n\n";
    
    $story->update([
        'crawl_status' => 0,
        'crawl_job_id' => null
    ]);
    
    $story->refresh();
    
    echo "After reset:\n";
    echo "   Status: {$story->crawl_status}\n";
    echo "   Job ID: " . ($story->crawl_job_id ?? 'NULL') . "\n\n";
    
    echo "✅ Story 9 reset successfully!\n";
} else {
    echo "❌ Story 9 not found\n";
}
