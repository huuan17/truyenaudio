<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Story;
use App\Models\Chapter;
use Illuminate\Support\Facades\File;

class CrawlStories extends Command
{
    protected $signature = 'crawl:stories {--story_id= : Chỉ định ID cụ thể nếu muốn} {--smart : Smart crawl - chỉ crawl chương thiếu}';
    protected $description = 'Tự động crawl truyện chưa hoặc cần crawl lại từ database';

    public function handle()
    {
        $storyId = $this->option('story_id');
        $smartCrawl = $this->option('smart');

        // Nếu truyền story_id → chỉ crawl 1
        if ($storyId) {
            if ($smartCrawl) {
                // Smart crawl: crawl bất kể status, chỉ crawl missing chapters
                $stories = Story::where('id', $storyId)->get();
            } else {
                // Normal crawl: chỉ crawl stories cần crawl
                $stories = Story::where('id', $storyId)
                    ->whereIn('crawl_status', [0, 2])
                    ->get();
            }
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

            // Set status to "đang crawl" at the beginning
            $story->crawl_status = config('constants.CRAWL_STATUS.VALUES.CRAWLING');
            $story->save();
            $this->info("📊 Cập nhật trạng thái: Đang crawl...");

            $baseUrl = $story->source_url;
            $start = $story->start_chapter;
            $end = $story->end_chapter;

            // Use new storage structure: storage/app/content/story-slug
            $output = storage_path('app/content/' . $story->folder_name);

            // Use original working crawl script (CommonJS)
            $scriptPath = base_path('node_scripts/crawl_original_cjs.cjs');

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
                $story->crawl_status = config('constants.CRAWL_STATUS.VALUES.CRAWLED');
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

                // Execute command with detailed error capture
                $outputLines = [];
                $exitCode = null;

                \Log::info("Executing crawl command for chapter {$chapterNumber}", [
                    'command' => $command,
                    'story_id' => $story->id,
                    'chapter' => $chapterNumber
                ]);

                exec($command . ' 2>&1', $outputLines, $exitCode);

                // Log command output for debugging
                if (!empty($outputLines)) {
                    \Log::info("Crawl command output for chapter {$chapterNumber}", [
                        'output' => implode("\n", $outputLines),
                        'exit_code' => $exitCode
                    ]);
                }

                if ($exitCode === 0 && File::exists($chapterOutput)) {
                    $fileSize = File::size($chapterOutput);
                    $successCount++;

                    \Log::info("Successfully crawled chapter {$chapterNumber}", [
                        'story_id' => $story->id,
                        'chapter' => $chapterNumber,
                        'file_path' => $chapterOutput,
                        'file_size' => $fileSize,
                        'success_count' => $successCount
                    ]);
                } else {
                    $failCount++;
                    $this->newLine();
                    $this->error("❌ Lỗi crawl chương $chapterNumber");

                    // Detailed error logging
                    \Log::error("Failed to crawl chapter {$chapterNumber}", [
                        'story_id' => $story->id,
                        'chapter' => $chapterNumber,
                        'exit_code' => $exitCode,
                        'command' => $command,
                        'output' => implode("\n", $outputLines),
                        'file_exists' => File::exists($chapterOutput),
                        'expected_file' => $chapterOutput,
                        'fail_count' => $failCount
                    ]);

                    // Check for specific error patterns
                    $outputText = implode("\n", $outputLines);
                    if (strpos($outputText, 'timeout') !== false) {
                        \Log::error("Timeout detected for chapter {$chapterNumber}", [
                            'story_id' => $story->id,
                            'chapter' => $chapterNumber
                        ]);
                    }
                    if (strpos($outputText, 'Chrome') !== false || strpos($outputText, 'browser') !== false) {
                        \Log::error("Browser issue detected for chapter {$chapterNumber}", [
                            'story_id' => $story->id,
                            'chapter' => $chapterNumber
                        ]);
                    }
                    if (strpos($outputText, 'chapter-c') !== false) {
                        \Log::error("Content selector issue detected for chapter {$chapterNumber}", [
                            'story_id' => $story->id,
                            'chapter' => $chapterNumber
                        ]);
                    }
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
            $successRate = $expectedTotal > 0 ? round(($successCount / $expectedTotal) * 100, 1) : 0;

            // Detailed completion logging
            \Log::info("Crawl completed for story: {$story->title}", [
                'story_id' => $story->id,
                'expected_total' => $expectedTotal,
                'existing_chapters' => count($existingChapters),
                'newly_crawled' => $successCount,
                'failed_chapters' => $failCount,
                'total_crawled' => $totalCrawled,
                'success_rate' => $successRate . '%',
                'start_chapter' => $start,
                'end_chapter' => $end
            ]);

            if ($totalCrawled >= $expectedTotal) {
                $this->info("✅ Đã crawl đủ số chương từ $start đến $end");
                $story->crawl_status = config('constants.CRAWL_STATUS.VALUES.CRAWLED');

                \Log::info("Story marked as CRAWLED", [
                    'story_id' => $story->id,
                    'total_crawled' => $totalCrawled,
                    'expected_total' => $expectedTotal
                ]);

                // Auto-import chapters to database
                $this->info("📥 Tự động import chapters vào database...");
                try {
                    $importExitCode = \Artisan::call('import:chapters', ['story_id' => $story->id]);
                    if ($importExitCode === 0) {
                        $this->info("✅ Import chapters thành công");
                        \Log::info("Chapters imported successfully", ['story_id' => $story->id]);
                    } else {
                        $this->warn("⚠️ Import chapters thất bại");
                        \Log::warning("Chapter import failed", [
                            'story_id' => $story->id,
                            'exit_code' => $importExitCode
                        ]);
                    }
                } catch (\Exception $e) {
                    $this->error("❌ Lỗi import chapters: " . $e->getMessage());
                    \Log::error("Chapter import error", [
                        'story_id' => $story->id,
                        'error' => $e->getMessage(),
                        'stack' => $e->getTraceAsString()
                    ]);
                }
            } else {
                $this->warn("⚠️ Chưa crawl đủ số chương (có $totalCrawled/$expectedTotal chương)");
                $story->crawl_status = config('constants.CRAWL_STATUS.VALUES.RE_CRAWL');

                \Log::warning("Story marked for RE_CRAWL", [
                    'story_id' => $story->id,
                    'total_crawled' => $totalCrawled,
                    'expected_total' => $expectedTotal,
                    'missing_chapters' => $expectedTotal - $totalCrawled
                ]);
            }

            $story->save();
            $statusLabel = config('constants.CRAWL_STATUS.LABELS')[$story->crawl_status] ?? 'Unknown';
            $this->info("📊 Cập nhật trạng thái hoàn thành: " . $statusLabel);

            \Log::info("Final crawl status updated", [
                'story_id' => $story->id,
                'final_status' => $story->crawl_status,
                'status_label' => $statusLabel
            ]);

            $this->line(str_repeat('-', 50));
        }

        return 0;
    }
}



