<?php

require_once 'vendor/autoload.php';

use App\Models\Story;
use App\Models\Chapter;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔧 Auto-fixing Chapter Counts...\n\n";

// Tìm và fix các stories có vấn đề về số chương
$problemStories = [
    'tien-nghich' => ['expected' => 1974, 'actual' => 1972],
    'co-nang-huyen-hoc' => ['expected' => 146, 'actual' => 149], 
    'vo-thuong-sat-than' => ['expected' => 5400, 'actual' => 5399]
];

foreach ($problemStories as $slug => $info) {
    echo "📖 Fixing story: {$slug}\n";
    
    $story = Story::where('slug', $slug)->first();
    if (!$story) {
        echo "  ❌ Story not found: {$slug}\n";
        continue;
    }
    
    // Lấy thông tin chapters thực tế
    $actualCount = Chapter::where('story_id', $story->id)->count();
    $maxChapter = Chapter::where('story_id', $story->id)->max('chapter_number');
    $minChapter = Chapter::where('story_id', $story->id)->min('chapter_number');
    
    echo "  📊 Current: {$story->start_chapter}-{$story->end_chapter} (Expected: " . ($story->end_chapter - $story->start_chapter + 1) . ")\n";
    echo "  📊 Actual: {$minChapter}-{$maxChapter} (Count: {$actualCount})\n";
    
    // Cập nhật theo thực tế
    $story->start_chapter = $minChapter;
    $story->end_chapter = $maxChapter;
    $story->save();
    
    echo "  ✅ Updated to: {$minChapter}-{$maxChapter}\n\n";
}

// Tạo function để tự động cập nhật trạng thái crawl
echo "🔄 Creating auto-update crawl status function...\n";

// Tạo Artisan command để tự động cập nhật trạng thái
$commandContent = '<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Story;
use App\Models\Chapter;

class UpdateCrawlStatus extends Command
{
    protected $signature = "stories:update-crawl-status";
    protected $description = "Update crawl status for stories that have completed crawling";

    public function handle()
    {
        $this->info("Updating crawl status...");
        
        $stories = Story::where("crawl_status", 0)->get();
        $updated = 0;
        
        foreach ($stories as $story) {
            $actualChapters = Chapter::where("story_id", $story->id)->count();
            $expectedChapters = $story->end_chapter - $story->start_chapter + 1;
            
            if ($actualChapters >= $expectedChapters) {
                $story->crawl_status = 1;
                $story->save();
                $updated++;
                $this->info("✅ {$story->title}: Marked as completed");
            }
        }
        
        $this->info("Updated {$updated} stories");
        return 0;
    }
}';

file_put_contents('app/Console/Commands/UpdateCrawlStatus.php', $commandContent);
echo "  ✅ Created UpdateCrawlStatus command\n";

// Tạo scheduled task để tự động chạy
echo "\n📅 Setting up automatic crawl status updates...\n";

$kernelPath = 'app/Console/Kernel.php';
$kernelContent = file_get_contents($kernelPath);

// Kiểm tra xem đã có schedule chưa
if (strpos($kernelContent, 'stories:update-crawl-status') === false) {
    // Thêm vào schedule method
    $scheduleMethod = '
    protected function schedule(Schedule $schedule): void
    {
        // Update crawl status every hour
        $schedule->command(\'stories:update-crawl-status\')->hourly();
        
        // Cancel stuck TTS requests every 30 minutes
        $schedule->call(function () {
            \App\Models\Chapter::where(\'audio_status\', \'processing\')
                ->where(\'tts_started_at\', \'<\', now()->subMinutes(30))
                ->update([
                    \'audio_status\' => \'error\',
                    \'tts_error\' => \'Timeout - auto cancelled\'
                ]);
        })->everyThirtyMinutes();
    }';
    
    // Tìm và thay thế schedule method
    $kernelContent = preg_replace(
        '/protected function schedule\(Schedule \$schedule\): void\s*\{[^}]*\}/',
        $scheduleMethod,
        $kernelContent
    );
    
    file_put_contents($kernelPath, $kernelContent);
    echo "  ✅ Added automatic schedule tasks\n";
} else {
    echo "  ℹ️  Schedule tasks already exist\n";
}

// Tạo helper method trong Story model
echo "\n🔧 Adding helper methods to Story model...\n";

$storyModelPath = 'app/Models/Story.php';
$storyContent = file_get_contents($storyModelPath);

$helperMethods = '
    /**
     * Tự động cập nhật số chương dựa trên chapters thực tế
     */
    public function updateChapterCount()
    {
        $chapters = $this->chapters();
        $count = $chapters->count();
        
        if ($count > 0) {
            $this->start_chapter = $chapters->min(\'chapter_number\');
            $this->end_chapter = $chapters->max(\'chapter_number\');
            $this->save();
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Kiểm tra và cập nhật trạng thái crawl
     */
    public function updateCrawlStatus()
    {
        $actualChapters = $this->chapters()->count();
        $expectedChapters = $this->end_chapter - $this->start_chapter + 1;
        
        if ($actualChapters >= $expectedChapters && $this->crawl_status == 0) {
            $this->crawl_status = 1;
            $this->save();
            return true;
        }
        
        return false;
    }
    
    /**
     * Hủy tất cả TTS requests đang pending cho story này
     */
    public function cancelPendingTTS()
    {
        return $this->chapters()
            ->where(\'audio_status\', \'pending\')
            ->whereNull(\'tts_started_at\')
            ->update([\'audio_status\' => \'none\']);
    }';

// Thêm methods vào cuối class (trước dấu } cuối)
if (strpos($storyContent, 'updateChapterCount') === false) {
    $storyContent = str_replace(
        '}\n', // Dấu } cuối của class
        $helperMethods . "\n}\n",
        $storyContent
    );
    
    file_put_contents($storyModelPath, $storyContent);
    echo "  ✅ Added helper methods to Story model\n";
} else {
    echo "  ℹ️  Helper methods already exist\n";
}

echo "\n🎉 Auto-fix completed!\n";
echo "  ✅ Fixed chapter counts for problem stories\n";
echo "  ✅ Created UpdateCrawlStatus command\n";
echo "  ✅ Set up automatic scheduling\n";
echo "  ✅ Added helper methods to Story model\n";

echo "\n📋 Next steps:\n";
echo "  1. Run: php artisan stories:update-crawl-status\n";
echo "  2. Set up cron job: * * * * * cd /path/to/project && php artisan schedule:run\n";
echo "  3. Monitor TTS queue to avoid resource waste\n";

echo "\n✨ Done!\n";
