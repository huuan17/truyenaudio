<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Story;

class FixCrawlStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawl:fix-status {--dry-run : Show what would be fixed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix incorrect crawl status for stories that are complete but not marked as CRAWLED';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info('Checking all stories for incorrect crawl status...');
        $this->line(str_repeat('=', 60));

        $stories = Story::all();
        $fixedCount = 0;
        $issuesFound = [];

        foreach ($stories as $story) {
            $expectedTotal = $story->end_chapter - $story->start_chapter + 1;
            $actualChapters = $story->chapters()->count();
            $isComplete = $actualChapters >= $expectedTotal;
            
            // If story is complete but not marked as CRAWLED
            if ($isComplete && $story->crawl_status != config('constants.CRAWL_STATUS.VALUES.CRAWLED')) {
                $statusLabels = config('constants.CRAWL_STATUS.LABELS');
                $currentStatusLabel = $statusLabels[$story->crawl_status] ?? 'Unknown';
                
                $issuesFound[] = [
                    'id' => $story->id,
                    'title' => $story->title,
                    'expected' => $expectedTotal,
                    'actual' => $actualChapters,
                    'current_status' => $story->crawl_status,
                    'current_label' => $currentStatusLabel
                ];

                $this->warn("Story: {$story->title} (ID: {$story->id})");
                $this->line("  Expected: {$expectedTotal}, Actual: {$actualChapters}");
                $this->line("  Status: {$story->crawl_status} ({$currentStatusLabel}) -> 2 (Đã crawl)");
                
                if (!$dryRun) {
                    $story->update([
                        'crawl_status' => config('constants.CRAWL_STATUS.VALUES.CRAWLED'),
                        'crawl_job_id' => null
                    ]);
                    
                    $this->info("  ✅ Fixed!");
                } else {
                    $this->comment("  🔍 Would be fixed (dry-run mode)");
                }
                
                $fixedCount++;
                $this->newLine();
            }
        }

        $this->line(str_repeat('=', 60));
        
        if ($fixedCount > 0) {
            if ($dryRun) {
                $this->warn("Found {$fixedCount} stories that need fixing.");
                $this->info("Run without --dry-run to apply fixes.");
            } else {
                $this->info("Total stories fixed: {$fixedCount}");
            }
        } else {
            $this->info("No stories need fixing. All crawl statuses are correct!");
        }

        return Command::SUCCESS;
    }
}
