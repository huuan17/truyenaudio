<?php

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
}