<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Story;
use App\Jobs\CrawlStoryJob;
use Illuminate\Support\Facades\Log;

class AutoCrawlStories extends Command
{
    protected $signature = 'auto:crawl-stories {--dry-run : Chỉ hiển thị stories sẽ được crawl, không thực hiện}';
    protected $description = 'Tự động crawl các truyện có auto_crawl = true và cần crawl';

    public function handle()
    {
        $this->info('🔍 Đang kiểm tra stories cần auto crawl...');

        // Lấy các stories cần auto crawl
        $stories = Story::where('auto_crawl', true)
            ->where('is_active', true)
            ->where(function($query) {
                $query->where('crawl_status', config('constants.CRAWL_STATUS.VALUES.NOT_CRAWLED'))
                      ->orWhereNull('crawl_status');
            })
            ->whereNull('crawl_job_id') // Không có job đang chạy
            ->orderBy('created_at', 'asc') // Ưu tiên truyện cũ trước
            ->limit(10) // Giới hạn tối đa 10 truyện mỗi lần chạy
            ->get();

        if ($stories->isEmpty()) {
            $this->info('✅ Không có truyện nào cần auto crawl.');
            return;
        }

        $this->info("📚 Tìm thấy {$stories->count()} truyện cần auto crawl:");

        // Tính toán delay để phân bổ đều trong 1 giờ
        $delayBetweenJobs = $this->calculateOptimalDelay($stories->count());

        foreach ($stories as $index => $story) {
            $this->line("  - ID: {$story->id} | {$story->title}");

            if ($this->option('dry-run')) {
                $this->line("    → Sẽ được dispatch với delay: {$delayBetweenJobs} phút");
                continue;
            }

            try {
                // Tính delay cho job này (phân bổ đều trong 1 giờ)
                $delayMinutes = $index * $delayBetweenJobs;

                // Dispatch crawl job với delay và queue riêng
                CrawlStoryJob::dispatch($story->id)
                    ->onQueue('crawl') // Queue riêng cho crawl
                    ->delay(now()->addMinutes($delayMinutes));

                $this->info("  ✅ Đã schedule job cho story ID: {$story->id} (delay: {$delayMinutes} phút)");
                Log::info("Auto crawl job scheduled for story ID: {$story->id}", [
                    'delay_minutes' => $delayMinutes,
                    'scheduled_at' => now()->addMinutes($delayMinutes)->toDateTimeString()
                ]);

            } catch (\Exception $e) {
                $this->error("  ❌ Lỗi khi schedule job cho story ID: {$story->id} - {$e->getMessage()}");
                Log::error("Auto crawl scheduling failed for story ID: {$story->id}", ['error' => $e->getMessage()]);
            }
        }

        if (!$this->option('dry-run')) {
            $this->info("🎉 Đã schedule {$stories->count()} truyện với delay phân bổ đều!");
            $this->info("⏰ Jobs sẽ chạy trong vòng {$delayBetweenJobs} x {$stories->count()} = " . ($delayBetweenJobs * $stories->count()) . " phút");
        } else {
            $this->info("🔍 Dry run hoàn thành. Sử dụng lệnh không có --dry-run để thực hiện schedule.");
        }
    }

    /**
     * Tính toán delay tối ưu giữa các jobs
     */
    private function calculateOptimalDelay($storyCount)
    {
        if ($storyCount <= 1) {
            return 0;
        }

        // Phân bổ đều trong 50 phút (để có 10 phút buffer trước lần chạy tiếp theo)
        $totalMinutes = 50;
        $delayBetweenJobs = floor($totalMinutes / $storyCount);

        // Tối thiểu 2 phút, tối đa 10 phút giữa các jobs
        return max(2, min(10, $delayBetweenJobs));
    }
}
