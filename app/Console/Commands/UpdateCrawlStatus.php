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
        $this->info("🔍 Checking crawl status for all stories...");

        // Get stories that might need status update (not CRAWLED)
        $stories = Story::whereNotIn('crawl_status', [2])->get(); // Exclude CRAWLED (2)
        $updated = 0;

        foreach ($stories as $story) {
            $actualChapters = $story->chapters()->count();
            $expectedChapters = $story->end_chapter - $story->start_chapter + 1;

            $this->line("📖 {$story->title} (ID: {$story->id}):");
            $this->line("   Current Status: {$story->crawl_status} (" . $this->getStatusLabel($story->crawl_status) . ")");
            $this->line("   Chapters: {$actualChapters}/{$expectedChapters}");

            // If story has all expected chapters, mark as CRAWLED
            if ($actualChapters >= $expectedChapters && $expectedChapters > 0) {
                $oldStatus = $story->crawl_status;
                $story->update(['crawl_status' => 2]); // CRAWLED
                $updated++;
                $this->info("   ✅ Updated: {$this->getStatusLabel($oldStatus)} → CRAWLED");
            } else {
                $this->line("   ⏳ No update needed");
            }

            $this->line("");
        }

        $this->info("🎉 Updated {$updated} stories to CRAWLED status");
        return 0;
    }

    private function getStatusLabel($status)
    {
        $labels = [
            0 => 'NOT_CRAWLED',
            1 => 'PENDING',
            2 => 'CRAWLED',
            3 => 'CRAWLING',
            4 => 'FAILED',
            5 => 'RE_CRAWL'
        ];

        return $labels[$status] ?? 'UNKNOWN';
    }
}