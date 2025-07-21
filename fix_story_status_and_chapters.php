<?php

require_once 'vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as DB;
use App\Models\Story;
use App\Models\Chapter;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔧 Fixing Story Status and Chapters...\n\n";

// 1. Cập nhật số chương thực tế cho từng story
echo "1. Updating chapter counts...\n";
$stories = Story::select('id', 'title', 'slug', 'start_chapter', 'end_chapter', 'crawl_status')->get();
$updatedCount = 0;

foreach ($stories as $story) {
    // Đếm số chapters thực tế
    $actualChapters = Chapter::where('story_id', $story->id)->count();
    
    if ($actualChapters > 0) {
        // Lấy chapter number cao nhất và thấp nhất
        $maxChapter = Chapter::where('story_id', $story->id)->max('chapter_number');
        $minChapter = Chapter::where('story_id', $story->id)->min('chapter_number');
        
        // Cập nhật end_chapter nếu khác với thực tế
        if ($story->end_chapter != $maxChapter || $story->start_chapter != $minChapter) {
            $story->start_chapter = $minChapter;
            $story->end_chapter = $maxChapter;
            $story->save();
            
            echo "  ✅ Updated {$story->title}: Chapters {$minChapter}-{$maxChapter} (Total: {$actualChapters})\n";
            $updatedCount++;
        }
    }
}

echo "  📊 Updated {$updatedCount} stories\n\n";

// 2. Cập nhật trạng thái crawl cho stories đã đủ chương
echo "2. Updating crawl status...\n";
$crawlUpdated = 0;

foreach ($stories as $story) {
    $actualChapters = Chapter::where('story_id', $story->id)->count();
    $expectedChapters = $story->end_chapter - $story->start_chapter + 1;
    
    // Nếu đã có đủ chapters nhưng crawl_status vẫn là 0 (chờ)
    if ($actualChapters >= $expectedChapters && $story->crawl_status == 0) {
        $story->crawl_status = 1; // Đã crawl xong
        $story->save();
        
        echo "  ✅ Marked as completed: {$story->title} ({$actualChapters} chapters)\n";
        $crawlUpdated++;
    }
}

echo "  📊 Updated crawl status for {$crawlUpdated} stories\n\n";

// 3. Hủy trạng thái chờ TTS để tránh tốn tài nguyên (sử dụng bulk update)
echo "3. Cancelling pending TTS requests...\n";

// Hủy các chapters đang pending TTS bằng bulk update (bao gồm cả những cái có tts_started_at)
$cancelledTTS = Chapter::where('audio_status', 'pending')
    ->update([
        'audio_status' => 'none',
        'tts_started_at' => null,
        'tts_error' => 'Cancelled by maintenance script'
    ]);

echo "  ❌ Cancelled {$cancelledTTS} pending TTS requests\n";

// Hủy các chapters đang processing nhưng đã quá lâu (>30 phút)
$stuckCancelled = Chapter::where('audio_status', 'processing')
    ->where('tts_started_at', '<', now()->subMinutes(30))
    ->update([
        'audio_status' => 'error',
        'tts_error' => 'Timeout - cancelled to save resources'
    ]);

echo "  ⏰ Cancelled {$stuckCancelled} stuck TTS requests (>30 min)\n\n";

// 4. Thống kê tổng quan (sử dụng count queries thay vì load data)
echo "4. Summary Statistics:\n";

$totalStories = Story::count();
$completedCrawl = Story::where('crawl_status', 1)->count();
$pendingCrawl = Story::where('crawl_status', 0)->count();

$totalChapters = Chapter::count();
$chaptersWithContent = Chapter::whereNotNull('file_path')->count();
$chaptersWithAudio = Chapter::whereNotNull('audio_file_path')->count();
$pendingTTSRemaining = Chapter::where('audio_status', 'pending')->count();

echo "  📚 Stories:\n";
echo "    - Total: {$totalStories}\n";
echo "    - Crawl completed: {$completedCrawl}\n";
echo "    - Crawl pending: {$pendingCrawl}\n";

echo "  📄 Chapters:\n";
echo "    - Total: {$totalChapters}\n";
echo "    - With content: {$chaptersWithContent}\n";
echo "    - With audio: {$chaptersWithAudio}\n";
echo "    - TTS pending: {$pendingTTSRemaining}\n";

// 5. Tìm stories có vấn đề (sử dụng SQL query để tránh memory issue)
echo "\n5. Stories with Issues:\n";

$problemQuery = "
    SELECT s.id, s.title, s.start_chapter, s.end_chapter, s.crawl_status,
           COUNT(c.id) as actual_chapters,
           (s.end_chapter - s.start_chapter + 1) as expected_chapters
    FROM stories s
    LEFT JOIN chapters c ON s.id = c.story_id
    GROUP BY s.id, s.title, s.start_chapter, s.end_chapter, s.crawl_status
    HAVING actual_chapters != expected_chapters AND actual_chapters > 0
    LIMIT 10
";

$problemStories = \Illuminate\Support\Facades\DB::select($problemQuery);

if (count($problemStories) > 0) {
    echo "  ⚠️  Found " . count($problemStories) . " stories with chapter count mismatches:\n";
    foreach ($problemStories as $problem) {
        echo "    - {$problem->title}: Expected {$problem->expected_chapters}, Got {$problem->actual_chapters}\n";
    }
} else {
    echo "  ✅ All stories have correct chapter counts!\n";
}

echo "\n🎉 Cleanup completed!\n";
echo "  ✅ Chapter counts updated: {$updatedCount}\n";
echo "  ✅ Crawl status updated: {$crawlUpdated}\n";
echo "  ✅ TTS requests cancelled: " . ($cancelledTTS + $stuckCancelled) . "\n";

echo "\n✨ Done!\n";
