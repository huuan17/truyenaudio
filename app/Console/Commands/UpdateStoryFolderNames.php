<?php

namespace App\Console\Commands;

use App\Models\Story;
use Illuminate\Console\Command;

class UpdateStoryFolderNames extends Command
{
    protected $signature = 'stories:update-folders';
    protected $description = 'Cập nhật folder_name và crawl_path cho tất cả truyện';

    public function handle()
    {
        $stories = Story::all();
        $count = $stories->count();
        
        $this->info("Bắt đầu cập nhật {$count} truyện...");
        $bar = $this->output->createProgressBar($count);
        $bar->start();
        
        $textBasePath = config('constants.STORAGE_PATHS.TEXT');
        
        foreach ($stories as $story) {
            // Đảm bảo slug không rỗng
            if (empty($story->slug)) {
                $story->slug = \Str::slug($story->title);
            }
            
            // Cập nhật folder_name và crawl_path
            $story->folder_name = $story->slug;
            $story->crawl_path = $textBasePath . $story->slug;
            $story->save();
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        $this->info("✅ Đã cập nhật thành công {$count} truyện!");
        
        return 0;
    }
}