<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Story;
use Illuminate\Support\Facades\File;

echo "=== Create Test Content for Vô thượng sát thần ===\n";

$story = Story::find(3);
if (!$story) {
    echo "❌ Story not found\n";
    exit(1);
}

$outputFolder = storage_path('app/content/' . $story->folder_name);

// Create output directory
if (!File::isDirectory($outputFolder)) {
    File::makeDirectory($outputFolder, 0755, true);
    echo "✅ Created directory: {$outputFolder}\n";
}

// Create test content files
$testContent = "Đây là nội dung test cho truyện Vô thượng sát thần.\n\nChương này được tạo để test hệ thống crawl.\n\nNội dung sẽ được thay thế khi crawl thực tế hoạt động.";

for ($chapter = 1; $chapter <= 5; $chapter++) {
    $filename = $outputFolder . "/chuong-{$chapter}.txt";
    $chapterContent = "=== CHƯƠNG {$chapter} ===\n\n" . $testContent . "\n\n(Test chapter {$chapter} - " . date('Y-m-d H:i:s') . ")";
    
    File::put($filename, $chapterContent);
    echo "✅ Created: chuong-{$chapter}.txt\n";
}

// Update story status
$story->crawl_status = 1; // Mark as crawled
$story->save();
echo "✅ Updated story status to 'crawled'\n";

echo "\n🎉 Test content created successfully!\n";
echo "Next steps:\n";
echo "1. Check admin page: http://localhost:8000/admin/stories/vo-thuong-sat-than\n";
echo "2. Import chapters: php artisan import:chapters 3\n";
echo "3. Test TTS if needed\n";

?>
