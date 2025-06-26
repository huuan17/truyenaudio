<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Story;
use App\Models\Chapter;
use Illuminate\Support\Facades\File;

class ImportCrawledChapters extends Command
{
    protected $signature = 'import:crawled {folder} {--story_id=} {--title=Truyện không tên} {--author=Không rõ}';
    protected $description = 'Đọc file .txt từ thư mục crawl và lưu vào bảng stories + chapters';

    public function handle()
    {
        $folder = $this->argument('folder');
        $storyId = $this->option('story_id');

        if (!File::isDirectory($folder)) {
            $this->error("❌ Thư mục không tồn tại: $folder");
            return Command::FAILURE;
        }

        // Lấy story hiện có hoặc tạo mới
        if ($storyId) {
            $story = Story::find($storyId);
            if (!$story) {
                $this->error("❌ Không tìm thấy story với ID: $storyId");
                return Command::FAILURE;
            }
            $this->info("📚 Sử dụng story hiện có: {$story->title} (ID: {$story->id})");
        } else {
            // Tạo story mới
            $story = Story::create([
                'title'       => $this->option('title'),
                'author'      => $this->option('author'),
                'source_url'  => null,
                'description' => null,
            ]);
            $this->info("📚 Đã tạo story mới: {$story->title} (ID: {$story->id})");
        }

        // Lấy danh sách file txt
        $files = collect(File::files($folder))
            ->filter(fn ($file) => $file->getExtension() === 'txt')
            ->sortBy(fn ($file) => intval(preg_replace('/\D/', '', $file->getFilename()))); // sort theo số chương

        if ($files->isEmpty()) {
            $this->error("❌ Không tìm thấy file .txt nào trong thư mục: $folder");
            return Command::FAILURE;
        }

        $bar = $this->output->createProgressBar($files->count());
        $bar->start();

        $importedCount = 0;
        $highestChapterNumber = 0;

        foreach ($files as $file) {
            $filename = $file->getFilename();
            $content = File::get($file->getRealPath());

            // Trích số chương từ tên file: chuong-123.txt
            preg_match('/chuong-(\d+)\.txt/', $filename, $match);
            $chapterNumber = $match[1] ?? null;

            if (!$chapterNumber) continue;

            // Cập nhật số chương cao nhất
            $highestChapterNumber = max($highestChapterNumber, (int)$chapterNumber);

            // Kiểm tra xem chương đã tồn tại chưa
            $chapter = Chapter::where('story_id', $story->id)
                ->where('chapter_number', $chapterNumber)
                ->first();

            if ($chapter) {
                // Cập nhật chương hiện có
                $chapter->update([
                    'content' => $content,
                    'is_crawled' => true,
                    'crawled_at' => now(),
                ]);
            } else {
                // Tạo chương mới
                Chapter::create([
                    'story_id'       => $story->id,
                    'chapter_number' => $chapterNumber,
                    'title'          => "Chương $chapterNumber",
                    'content'        => $content,
                    'audio_status'   => 'pending',
                    'is_crawled'     => true,
                    'crawled_at'     => now(),
                ]);
                $importedCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Thống kê và kiểm tra trạng thái
        $totalChapters = Chapter::where('story_id', $story->id)->count();
        $this->info("📊 Thống kê:");
        $this->info("   - Tổng số file đã xử lý: {$files->count()}");
        $this->info("   - Số chương đã nhập mới: $importedCount");
        $this->info("   - Tổng số chương hiện có: $totalChapters");
        $this->info("   - Chương cao nhất đã nhập: $highestChapterNumber");

        // Kiểm tra nếu số chương nhỏ hơn end_chapter
        if ($story->end_chapter > $highestChapterNumber) {
            $this->warn("⚠️ Số chương hiện có ($highestChapterNumber) nhỏ hơn end_chapter ({$story->end_chapter})");
            $this->info("🔄 Đang cập nhật trạng thái crawl sang 'Cần crawl lại'...");
            
            $story->crawl_status = 2; // Cần crawl lại
            $story->save();
            
            $this->info("✅ Đã cập nhật trạng thái crawl của story!");
        } else {
            $this->info("✅ Đã crawl đủ số chương!");
            
            $story->crawl_status = 1; // Đã crawl
            $story->save();
        }

        $this->info("✅ Hoàn tất nhập dữ liệu!");
        return Command::SUCCESS;
    }
}


