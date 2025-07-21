<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\Story;

class ManageCrawlQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawl:manage
                            {action : Action to perform (status|clear|stats|limit)}
                            {--reset-limit : Reset rate limiting cache}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Quản lý queue crawl và rate limiting';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'status':
                $this->showQueueStatus();
                break;
            case 'clear':
                $this->clearQueue();
                break;
            case 'stats':
                $this->showCrawlStats();
                break;
            case 'limit':
                $this->manageLimits();
                break;
            default:
                $this->error("Unknown action: {$action}");
                $this->info("Available actions: status, clear, stats, limit");
        }
    }

    private function showQueueStatus()
    {
        $this->info('📊 CRAWL QUEUE STATUS');
        $this->line('');

        // Jobs in crawl queue
        $crawlJobs = DB::table('jobs')->where('queue', 'crawl')->get();
        $this->info("🔄 Jobs trong crawl queue: {$crawlJobs->count()}");

        if ($crawlJobs->count() > 0) {
            $this->table(
                ['ID', 'Payload (Story ID)', 'Attempts', 'Available At'],
                $crawlJobs->map(function($job) {
                    $payload = json_decode($job->payload, true);
                    $storyId = $payload['data']['storyId'] ?? 'Unknown';
                    return [
                        $job->id,
                        $storyId,
                        $job->attempts,
                        date('Y-m-d H:i:s', $job->available_at)
                    ];
                })->toArray()
            );
        }

        // Stories đang crawl
        $crawlingStories = Story::where('crawl_status', config('constants.CRAWL_STATUS.VALUES.CRAWLING'))
                                ->whereNotNull('crawl_job_id')
                                ->get();

        $this->info("🏃 Stories đang crawl: {$crawlingStories->count()}");

        if ($crawlingStories->count() > 0) {
            $this->table(
                ['ID', 'Title', 'Job ID', 'Updated At'],
                $crawlingStories->map(function($story) {
                    return [
                        $story->id,
                        substr($story->title, 0, 30) . '...',
                        $story->crawl_job_id,
                        $story->updated_at->format('Y-m-d H:i:s')
                    ];
                })->toArray()
            );
        }

        // Rate limiting status
        $this->showRateLimitStatus();
    }

    private function showRateLimitStatus()
    {
        $this->line('');
        $this->info('⏱️ RATE LIMITING STATUS');

        $rateLimitKey = 'crawl_rate_limit';
        $requests = Cache::get($rateLimitKey, []);
        $now = time();

        // Remove old requests
        $recentRequests = array_filter($requests, function($timestamp) use ($now) {
            return ($now - $timestamp) < 3600; // Last hour
        });

        $this->info("📈 Requests trong 1 giờ qua: " . count($recentRequests));

        if (count($recentRequests) > 0) {
            $lastRequest = max($recentRequests);
            $timeSinceLastRequest = $now - $lastRequest;
            $this->info("⏰ Request cuối cùng: " . $timeSinceLastRequest . " giây trước");
        }

        if ($this->option('reset-limit')) {
            Cache::forget($rateLimitKey);
            $this->info("✅ Đã reset rate limiting cache");
        }
    }

    private function clearQueue()
    {
        $this->warn('⚠️ Xóa tất cả jobs trong crawl queue...');

        if (!$this->confirm('Bạn có chắc chắn muốn xóa tất cả crawl jobs?')) {
            $this->info('Hủy bỏ.');
            return;
        }

        $deletedCount = DB::table('jobs')->where('queue', 'crawl')->delete();
        $this->info("✅ Đã xóa {$deletedCount} jobs khỏi crawl queue");

        // Reset crawl status for stories
        $updatedStories = Story::where('crawl_status', config('constants.CRAWL_STATUS.VALUES.CRAWLING'))
                               ->whereNotNull('crawl_job_id')
                               ->update([
                                   'crawl_status' => config('constants.CRAWL_STATUS.VALUES.NOT_CRAWLED'),
                                   'crawl_job_id' => null
                               ]);

        $this->info("✅ Đã reset status cho {$updatedStories} stories");
    }

    private function showCrawlStats()
    {
        $this->info('📈 CRAWL STATISTICS');
        $this->line('');

        $stats = [
            'Tổng số truyện' => Story::count(),
            'Auto crawl enabled' => Story::where('auto_crawl', true)->count(),
            'Chưa crawl' => Story::where('crawl_status', config('constants.CRAWL_STATUS.VALUES.NOT_CRAWLED'))->count(),
            'Đang crawl' => Story::where('crawl_status', config('constants.CRAWL_STATUS.VALUES.CRAWLING'))->count(),
            'Đã crawl' => Story::where('crawl_status', config('constants.CRAWL_STATUS.VALUES.CRAWLED'))->count(),
            'Cần crawl lại' => Story::where('crawl_status', config('constants.CRAWL_STATUS.VALUES.RE_CRAWL'))->count(),
        ];

        foreach ($stats as $label => $count) {
            $this->info("{$label}: {$count}");
        }

        $this->line('');
        $this->info('🎯 Stories sẵn sàng cho auto crawl:');
        $readyStories = Story::where('auto_crawl', true)
                            ->where('is_active', true)
                            ->where(function($query) {
                                $query->where('crawl_status', config('constants.CRAWL_STATUS.VALUES.NOT_CRAWLED'))
                                      ->orWhereNull('crawl_status');
                            })
                            ->whereNull('crawl_job_id')
                            ->count();

        $this->info("Sẵn sàng crawl: {$readyStories}");
    }

    private function manageLimits()
    {
        $this->info('⚙️ CRAWL LIMITS MANAGEMENT');
        $this->line('');

        $this->info('Cấu hình hiện tại:');
        $this->info('- Tối đa 10 truyện mỗi lần auto crawl');
        $this->info('- Delay 2-10 phút giữa các jobs');
        $this->info('- Phân bổ đều trong 50 phút');
        $this->info('- Queue riêng: crawl');
        $this->info('- Timeout: 4 giờ mỗi job');

        $this->line('');
        $this->info('Để thay đổi cấu hình, chỉnh sửa:');
        $this->info('- app/Console/Commands/AutoCrawlStories.php');
        $this->info('- app/Jobs/CrawlStoryJob.php');
        $this->info('- config/queue.php');
    }
}
