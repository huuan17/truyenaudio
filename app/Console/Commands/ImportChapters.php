<?php
namespace App\Console\Commands;

use App\Models\Story;
use App\Models\Chapter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportChapters extends Command
{
    protected $signature = 'import:chapters {story_id}';
    protected $description = 'Import các chương từ file txt vào database';

    public function handle()
    {
        $storyId = $this->argument('story_id');
        $story = Story::find($storyId);

        if (!$story) {
            $this->error("❌ Không tìm thấy truyện ID: $storyId");
            return 1;
        }

        $folderPath = storage_path("truyen/{$story->slug}");

        if (!File::isDirectory($folderPath)) {
            $this->error("❌ Thư mục không tồn tại: $folderPath");
            return 1;
        }

        $files = File::files($folderPath);
        $imported = 0;

        foreach ($files as $file) {
            $filename = $file->getFilename();

            if (!preg_match('/chuong-(\d+)\.txt$/', $filename, $matches)) {
                continue;
            }

            $chapterNumber = (int)$matches[1];

            if (Chapter::where('story_id', $story->id)->where('chapter_number', $chapterNumber)->exists()) {
                $this->warn("⚠️ Đã tồn tại chương $chapterNumber");
                continue;
            }

            $content = File::get($file->getPathname());

            Chapter::create([
                'story_id' => $story->id,
                'title' => "Chương $chapterNumber",
                'chapter_number' => $chapterNumber,
                'content' => $content,
            ]);

            $this->info("✅ Nhập chương $chapterNumber");
            $imported++;
        }

        $this->info("🎉 Hoàn tất: Đã nhập $imported chương.");
        return 0;
    }
}
