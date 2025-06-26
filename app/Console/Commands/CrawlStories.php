<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Story;
use App\Models\Chapter;
use Illuminate\Support\Facades\File;

class CrawlStories extends Command
{
    protected $signature = 'crawl:stories {--story_id= : Chỉ định ID cụ thể nếu muốn}';
    protected $description = 'Tự động crawl truyện chưa hoặc cần crawl lại từ database';

    public function handle()
    {
        $storyId = $this->option('story_id');

        // Nếu truyền story_id → chỉ crawl 1
        if ($storyId) {
            $stories = Story::where('id', $storyId)
                ->whereIn('crawl_status', [0, 2])
                ->get();
        } else {
            // Ngược lại → lấy toàn bộ truyện cần crawl
            $stories = Story::whereIn('crawl_status', [0, 2])->get();
        }

        if ($stories->isEmpty()) {
            $this->info('✅ Không có truyện nào cần crawl.');
            return 0;
        }

        foreach ($stories as $story) {
            $this->info("🔍 Bắt đầu crawl truyện ID {$story->id}: {$story->title}");

            $baseUrl = $story->source_url;
            $start = $story->start_chapter;
            $end = $story->end_chapter;
            $output = base_path($story->crawl_path);
            $scriptPath = base_path('node_scripts/crawl.js');

            // Kiểm tra thư mục đầu ra
            if (!File::isDirectory($output)) {
                $this->info("📁 Tạo thư mục đầu ra: $output");
                File::makeDirectory($output, 0755, true);
            }

            // Lấy danh sách các chương đã tồn tại trong database
            $existingChapters = Chapter::where('story_id', $story->id)
                ->where('is_crawled', true)
                ->pluck('chapter_number')
                ->toArray();

            $this->info("📊 Đã tìm thấy " . count($existingChapters) . " chương đã crawl trong database");

            // Tạo danh sách các chương cần crawl
            $chaptersToFetch = [];
            for ($i = $start; $i <= $end; $i++) {
                if (!in_array($i, $existingChapters)) {
                    $chaptersToFetch[] = $i;
                }
            }

            if (empty($chaptersToFetch)) {
                $this->info("✅ Tất cả các chương từ $start đến $end đã tồn tại. Không cần crawl lại.");
                $story->crawl_status = 1; // đã crawl
                $story->save();
                continue;
            }

            $this->info("🔍 Cần crawl " . count($chaptersToFetch) . " chương: " . implode(', ', array_slice($chaptersToFetch, 0, 10)) . (count($chaptersToFetch) > 10 ? '...' : ''));

            // Crawl từng chương một
            $bar = $this->output->createProgressBar(count($chaptersToFetch));
            $bar->start();
            
            $successCount = 0;
            $failCount = 0;

            foreach ($chaptersToFetch as $chapterNumber) {
                $chapterOutput = $output . '/chuong-' . $chapterNumber . '.txt';
                
                $command = sprintf(
                    'node %s %s %d %d %s %d',
                    escapeshellarg($scriptPath),
                    escapeshellarg($baseUrl),
                    $chapterNumber, // start = current chapter
                    $chapterNumber, // end = current chapter
                    escapeshellarg($output),
                    1 // single mode flag
                );

                exec($command, $outputLines, $exitCode);

                if ($exitCode === 0 && File::exists($chapterOutput)) {
                    $successCount++;
                } else {
                    $failCount++;
                    $this->newLine();
                    $this->error("❌ Lỗi crawl chương $chapterNumber");
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            $this->info("📊 Kết quả crawl:");
            $this->info("   - Thành công: $successCount chương");
            $this->info("   - Thất bại: $failCount chương");

            // Kiểm tra xem đã crawl đủ chương chưa
            $totalCrawled = count($existingChapters) + $successCount;
            $expectedTotal = $end - $start + 1;
            
            if ($totalCrawled >= $expectedTotal) {
                $this->info("✅ Đã crawl đủ số chương từ $start đến $end");
                $story->crawl_status = 1; // đã crawl
            } else {
                $this->warn("⚠️ Chưa crawl đủ số chương (có $totalCrawled/$expectedTotal chương)");
                $story->crawl_status = 2; // cần crawl lại
            }
            
            $story->save();
            $this->line(str_repeat('-', 50));
        }

        return 0;
    }
}



