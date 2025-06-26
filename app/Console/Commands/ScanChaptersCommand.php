<?php

namespace App\Console\Commands;

use App\Models\Story;
use App\Models\Chapter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ScanChaptersCommand extends Command
{
    protected $signature = 'chapters:scan {story_id} {--force : Force rescan all chapters} {--with-content : Import content into database}';
    protected $description = 'Scan and import chapters from storage files into database';

    public function handle()
    {
        $storyId = $this->argument('story_id');
        $force = $this->option('force');
        $withContent = $this->option('with-content');

        // Tìm truyện
        $story = Story::find($storyId);
        if (!$story) {
            $this->error("Không tìm thấy truyện với ID: $storyId");
            return 1;
        }

        $this->info("Bắt đầu quét chapter cho truyện: {$story->title}");
        
        // Đường dẫn thư mục chứa file text
        $textFolder = base_path($story->crawl_path);
        
        if (!File::isDirectory($textFolder)) {
            $this->error("Thư mục không tồn tại: $textFolder");
            return 1;
        }
        
        // Tìm tất cả file .txt
        $files = File::glob("$textFolder/*.txt");
        
        if (empty($files)) {
            $this->warn("Không tìm thấy file .txt nào trong thư mục: $textFolder");
            return 0;
        }
        
        $this->info("Tìm thấy " . count($files) . " file text");
        
        $bar = $this->output->createProgressBar(count($files));
        $bar->start();
        
        $imported = 0;
        $skipped = 0;
        $errors = 0;
        
        foreach ($files as $filePath) {
            $fileName = pathinfo($filePath, PATHINFO_FILENAME);
            
            // Trích xuất số chương từ tên file (ví dụ: chuong-1.txt -> 1)
            if (preg_match('/chuong[_-](\d+)/i', $fileName, $matches)) {
                $chapterNumber = (int)$matches[1];
            } else {
                $this->newLine();
                $this->warn("Không thể trích xuất số chương từ file: $fileName");
                $errors++;
                $bar->advance();
                continue;
            }
            
            // Kiểm tra xem chapter đã tồn tại chưa
            $existingChapter = Chapter::where('story_id', $story->id)
                ->where('chapter_number', $chapterNumber)
                ->first();
                
            if ($existingChapter && !$force) {
                $skipped++;
                $bar->advance();
                continue;
            }
            
            // Đọc nội dung file để lấy tiêu đề
            $content = File::get($filePath);

            if (empty(trim($content))) {
                $this->newLine();
                $this->warn("File rỗng: $fileName");
                $errors++;
                $bar->advance();
                continue;
            }

            // Tạo tiêu đề chapter từ nội dung hoặc số chương
            $title = $this->extractChapterTitle($content, $chapterNumber);

            // Chuẩn bị dữ liệu để lưu
            $chapterData = [
                'title' => $title,
                'is_crawled' => true,
                'file_path' => $filePath
            ];

            // Chỉ lưu content nếu có option --with-content
            if ($withContent) {
                $chapterData['content'] = $content;
            }

            if ($existingChapter) {
                // Cập nhật chapter hiện có
                $existingChapter->update($chapterData);
            } else {
                // Tạo chapter mới
                $chapterData['story_id'] = $story->id;
                $chapterData['chapter_number'] = $chapterNumber;
                Chapter::create($chapterData);
            }
            
            $imported++;
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        $this->info("Hoàn thành quét chapter:");
        $this->info("- Đã import/cập nhật: $imported chapter");
        $this->info("- Đã bỏ qua: $skipped chapter");
        $this->info("- Lỗi: $errors file");
        
        return 0;
    }
    
    private function extractChapterTitle($content, $chapterNumber)
    {
        // Tìm tiêu đề trong nội dung (thường ở dòng đầu tiên)
        $lines = explode("\n", trim($content));
        $firstLine = trim($lines[0]);
        
        // Nếu dòng đầu tiên có vẻ như tiêu đề (không quá dài và có từ "chương")
        if (strlen($firstLine) < 100 && 
            (stripos($firstLine, 'chương') !== false || 
             stripos($firstLine, 'chapter') !== false ||
             preg_match('/^[^\w]*\d+[^\w]*/', $firstLine))) {
            return $firstLine;
        }
        
        // Nếu không tìm thấy tiêu đề phù hợp, tạo tiêu đề mặc định
        return "Chương $chapterNumber";
    }
}
