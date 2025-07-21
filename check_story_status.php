<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Story;

echo "=== Check Story Status ===\n";

$story = Story::find(3);
if (!$story) {
    echo "❌ Story not found\n";
    exit(1);
}

echo "Story info:\n";
echo "  ID: {$story->id}\n";
echo "  Title: {$story->title}\n";
echo "  Crawl Status: {$story->crawl_status}\n";

$statusLabels = config('constants.CRAWL_STATUS.LABELS');
$statusColors = config('constants.CRAWL_STATUS.COLORS');

echo "  Status Label: " . $statusLabels[$story->crawl_status] . "\n";
echo "  Status Color: " . $statusColors[$story->crawl_status] . "\n";

// Check content files
$contentDir = storage_path('app/content/' . $story->folder_name);
echo "\nContent directory: {$contentDir}\n";

if (is_dir($contentDir)) {
    $files = glob($contentDir . '/*.txt');
    echo "  Files found: " . count($files) . "\n";
    
    if (count($files) > 0) {
        echo "  Sample files:\n";
        foreach (array_slice($files, 0, 5) as $file) {
            $filename = basename($file);
            $size = round(filesize($file) / 1024, 2);
            echo "    - {$filename} ({$size} KB)\n";
        }
    }
} else {
    echo "  ❌ Directory not found\n";
}

echo "\n✅ Status check completed!\n";

?>
