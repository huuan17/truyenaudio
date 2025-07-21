<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Story;

echo "=== Update Story URL for 'Vô thượng sát thần' ===\n";

$story = Story::find(3);
if (!$story) {
    echo "❌ Story not found\n";
    exit(1);
}

echo "Current story info:\n";
echo "  Title: {$story->title}\n";
echo "  Current URL: {$story->source_url}\n";
echo "  Chapter range: {$story->start_chapter} - {$story->end_chapter}\n";

// Test different possible URLs
$possibleUrls = [
    'https://truyencom.com/vo-thuong-sat-than/chuong-',
    'https://truyencom.com/vo-thuong-sat-than/chapter-',
    'https://truyencom.com/truyen/vo-thuong-sat-than/chuong-',
    'https://truyencom.com/truyen/vo-thuong-sat-than/chapter-',
    'https://truyencom.net/vo-thuong-sat-than/chuong-',
    'https://truyencom.net/truyen/vo-thuong-sat-than/chuong-',
    'https://truyencom.vn/vo-thuong-sat-than/chuong-',
    'https://truyencom.vn/truyen/vo-thuong-sat-than/chuong-',
];

echo "\nTesting possible URLs:\n";
foreach ($possibleUrls as $baseUrl) {
    $testUrl = $baseUrl . '1.html';
    echo "  Testing: {$testUrl}\n";
    
    $headers = @get_headers($testUrl, 1);
    if ($headers && strpos($headers[0], '200') !== false) {
        echo "    ✅ URL is accessible!\n";
        
        // Update story URL
        $story->source_url = $baseUrl;
        $story->save();
        echo "    ✅ Updated story URL to: {$baseUrl}\n";
        break;
    } else {
        echo "    ❌ Not accessible\n";
    }
}

// Also test with different chapter numbers
echo "\nTesting with different chapter numbers:\n";
$testChapters = [1, 2, 3, 5, 10];
foreach ($testChapters as $chapter) {
    $testUrl = $story->source_url . $chapter . '.html';
    echo "  Testing chapter {$chapter}: {$testUrl}\n";
    
    $headers = @get_headers($testUrl, 1);
    if ($headers && strpos($headers[0], '200') !== false) {
        echo "    ✅ Chapter {$chapter} is accessible\n";
    } else {
        echo "    ❌ Chapter {$chapter} not accessible\n";
    }
}

// Suggest reasonable chapter range
echo "\nSuggesting reasonable chapter range:\n";
echo "  Current range: {$story->start_chapter} - {$story->end_chapter} (5400 chapters is very large!)\n";
echo "  Suggested: Start with 1-50 for testing\n";

$response = readline("Do you want to update chapter range to 1-50 for testing? (y/n): ");
if (strtolower($response) === 'y') {
    $story->start_chapter = 1;
    $story->end_chapter = 50;
    $story->save();
    echo "  ✅ Updated chapter range to 1-50\n";
}

echo "\n✅ Story URL update completed!\n";
echo "Next steps:\n";
echo "1. Test crawl: php artisan crawl:stories --story_id=3\n";
echo "2. Check admin page: http://localhost:8000/admin/stories/vo-thuong-sat-than\n";

?>
