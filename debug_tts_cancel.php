<?php

require_once 'vendor/autoload.php';

use App\Models\Story;
use App\Models\Chapter;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Debug TTS Cancel for Tiên Nghịch...\n\n";

// 1. Tìm story Tiên Nghịch
$story = Story::where('slug', 'tien-nghich')->first();
if (!$story) {
    echo "❌ Story 'Tiên Nghịch' not found\n";
    exit;
}

echo "📖 Found story: {$story->title} (ID: {$story->id})\n\n";

// 2. Kiểm tra TTS status trước khi cancel
echo "📊 TTS Status BEFORE cancel:\n";
$pendingBefore = $story->chapters()->where('audio_status', 'pending')->count();
$processingBefore = $story->chapters()->where('audio_status', 'processing')->count();
$doneBefore = $story->chapters()->where('audio_status', 'done')->count();
$noneBefore = $story->chapters()->where('audio_status', 'none')->count();

echo "  - Pending: {$pendingBefore}\n";
echo "  - Processing: {$processingBefore}\n";
echo "  - Done: {$doneBefore}\n";
echo "  - None: {$noneBefore}\n\n";

// 3. Kiểm tra chi tiết pending chapters
if ($pendingBefore > 0) {
    echo "🔍 Pending chapters details:\n";
    $pendingChapters = $story->chapters()
        ->where('audio_status', 'pending')
        ->select('id', 'chapter_number', 'title', 'audio_status', 'tts_started_at', 'tts_error')
        ->get();
    
    foreach ($pendingChapters as $chapter) {
        echo "  - Chapter {$chapter->chapter_number} (ID: {$chapter->id})\n";
        echo "    Status: {$chapter->audio_status}\n";
        echo "    TTS Started: " . ($chapter->tts_started_at ?? 'NULL') . "\n";
        echo "    TTS Error: " . ($chapter->tts_error ?? 'NULL') . "\n\n";
    }
}

// 4. Test cancelPendingTTS method
echo "🔧 Testing cancelPendingTTS method...\n";
try {
    $cancelled = $story->cancelPendingTTS();
    echo "✅ Successfully cancelled: {$cancelled} TTS requests\n\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n\n";
    exit;
}

// 5. Kiểm tra TTS status sau khi cancel
echo "📊 TTS Status AFTER cancel:\n";
$pendingAfter = $story->chapters()->where('audio_status', 'pending')->count();
$processingAfter = $story->chapters()->where('audio_status', 'processing')->count();
$doneAfter = $story->chapters()->where('audio_status', 'done')->count();
$noneAfter = $story->chapters()->where('audio_status', 'none')->count();

echo "  - Pending: {$pendingAfter}\n";
echo "  - Processing: {$processingAfter}\n";
echo "  - Done: {$doneAfter}\n";
echo "  - None: {$noneAfter}\n\n";

// 6. Kiểm tra chapters đã được cancel
if ($cancelled > 0) {
    echo "🔍 Cancelled chapters details:\n";
    $cancelledChapters = $story->chapters()
        ->where('audio_status', 'none')
        ->where('tts_error', 'Cancelled by user')
        ->select('id', 'chapter_number', 'title', 'audio_status', 'tts_started_at', 'tts_error')
        ->take(5)
        ->get();
    
    foreach ($cancelledChapters as $chapter) {
        echo "  - Chapter {$chapter->chapter_number} (ID: {$chapter->id})\n";
        echo "    Status: {$chapter->audio_status}\n";
        echo "    TTS Started: " . ($chapter->tts_started_at ?? 'NULL') . "\n";
        echo "    TTS Error: " . ($chapter->tts_error ?? 'NULL') . "\n\n";
    }
}

// 7. Test controller method
echo "🌐 Testing controller method...\n";
try {
    $controller = new App\Http\Controllers\Admin\StoryMaintenanceController();
    $request = new Illuminate\Http\Request();
    
    // Create a test pending chapter
    $testChapter = $story->chapters()->create([
        'chapter_number' => 99999,
        'title' => 'Test Cancel Chapter',
        'content' => 'Test content for cancel',
        'audio_status' => 'pending',
        'tts_started_at' => now()
    ]);
    
    echo "  📝 Created test chapter: {$testChapter->id}\n";
    
    // Test controller method
    $response = $controller->cancelPendingTTS($request, $story);
    $responseData = json_decode($response->getContent(), true);
    
    echo "  📡 Controller response:\n";
    echo "    Success: " . ($responseData['success'] ? 'Yes' : 'No') . "\n";
    echo "    Message: " . $responseData['message'] . "\n";
    
    // Cleanup test chapter
    $testChapter->delete();
    echo "  🗑️  Test chapter deleted\n\n";
    
} catch (Exception $e) {
    echo "❌ Controller error: " . $e->getMessage() . "\n\n";
}

// 8. Summary
echo "📋 Summary:\n";
echo "  ✅ Story found: {$story->title}\n";
echo "  ✅ Pending chapters before: {$pendingBefore}\n";
echo "  ✅ Cancelled chapters: {$cancelled}\n";
echo "  ✅ Pending chapters after: {$pendingAfter}\n";
echo "  ✅ Method working: " . ($cancelled >= 0 ? 'Yes' : 'No') . "\n";

if ($pendingAfter == 0 && $cancelled > 0) {
    echo "\n🎉 TTS Cancel functionality is working correctly!\n";
} elseif ($pendingBefore == 0) {
    echo "\n✅ No pending TTS requests to cancel.\n";
} else {
    echo "\n⚠️  Some issues may remain. Check the details above.\n";
}

echo "\n✨ Debug completed!\n";
