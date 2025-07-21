<?php

require_once 'vendor/autoload.php';

use App\Models\Story;
use App\Models\Chapter;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Creating Test Pending TTS for Maintenance Testing...\n\n";

// 1. Tìm story Tiên Nghịch
$story = Story::where('slug', 'tien-nghich')->first();
if (!$story) {
    echo "❌ Story 'Tiên Nghịch' not found\n";
    exit;
}

echo "📖 Found story: {$story->title} (ID: {$story->id})\n\n";

// 2. Tạo test chapters với pending TTS
echo "📝 Creating test pending TTS chapters...\n";

$testChapters = [];

// Tạo 3 test chapters với pending status
for ($i = 1; $i <= 3; $i++) {
    $testChapter = $story->chapters()->create([
        'chapter_number' => 20000 + $i,
        'title' => "Test Pending Chapter {$i}",
        'content' => "Test content for pending TTS chapter {$i}",
        'audio_status' => 'pending',
        'tts_started_at' => ($i == 1) ? null : now()->subMinutes($i * 5),
        'tts_voice' => $story->default_tts_voice,
        'tts_bitrate' => $story->default_tts_bitrate,
        'tts_speed' => $story->default_tts_speed,
        'tts_volume' => $story->default_tts_volume,
    ]);
    
    $testChapters[] = $testChapter;
    echo "  ✅ Created Chapter {$testChapter->chapter_number} (ID: {$testChapter->id})\n";
    echo "     Status: {$testChapter->audio_status}\n";
    echo "     TTS Started: " . ($testChapter->tts_started_at ? $testChapter->tts_started_at->format('Y-m-d H:i:s') : 'NULL') . "\n\n";
}

// 3. Hiển thị thống kê
echo "📊 Current TTS Status for {$story->title}:\n";
$pendingCount = $story->chapters()->where('audio_status', 'pending')->count();
$processingCount = $story->chapters()->where('audio_status', 'processing')->count();
$doneCount = $story->chapters()->where('audio_status', 'done')->count();
$noneCount = $story->chapters()->where('audio_status', 'none')->count();

echo "  - Pending: {$pendingCount}\n";
echo "  - Processing: {$processingCount}\n";
echo "  - Done: {$doneCount}\n";
echo "  - None: {$noneCount}\n\n";

// 4. Tạo CSRF token cho testing
echo "🔐 CSRF Token for testing:\n";
echo "  Token: " . csrf_token() . "\n\n";

// 5. Tạo curl command để test
echo "🌐 Test Commands:\n";
echo "You can now test the maintenance page at:\n";
echo "http://localhost:8000/admin/maintenance\n\n";

echo "Or test via curl:\n";
$token = csrf_token();
echo "curl -X POST 'http://localhost:8000/admin/maintenance/cancel-pending-tts/{$story->id}' \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -H 'X-CSRF-TOKEN: {$token}' \\\n";
echo "  -H 'X-Requested-With: XMLHttpRequest'\n\n";

// 6. Instructions
echo "📋 Testing Instructions:\n";
echo "1. Go to: http://localhost:8000/admin/maintenance\n";
echo "2. Look for '{$story->title}' in the 'Stories with Chapter Count Issues' section\n";
echo "3. Click 'Cancel TTS' button to test the functionality\n";
echo "4. Check if the pending TTS requests are cancelled\n\n";

echo "🗑️  To cleanup test data, run:\n";
echo "php -r \"\n";
echo "require_once 'vendor/autoload.php';\n";
echo "\$app = require_once 'bootstrap/app.php';\n";
echo "\$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();\n";
foreach ($testChapters as $chapter) {
    echo "App\\Models\\Chapter::find({$chapter->id})->delete();\n";
}
echo "echo 'Test chapters deleted';\n";
echo "\"\n\n";

echo "✨ Test setup completed!\n";
echo "📊 Created {" . count($testChapters) . "} test chapters with pending TTS status\n";
echo "🔧 Ready for maintenance testing!\n";
