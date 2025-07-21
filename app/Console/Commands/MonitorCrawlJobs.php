<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use App\Models\Story;
use App\Models\Chapter;
use Carbon\Carbon;

class MonitorCrawlJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawl:monitor
                            {action=status : Action to perform (status|check|recover|auto)}
                            {--story= : Specific story ID to check}
                            {--timeout=120 : Timeout in minutes for stuck detection}
                            {--fix : Actually fix stuck jobs (dry-run by default)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor crawl jobs and detect stuck processes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'status':
                $this->showCrawlStatus();
                break;
            case 'check':
                $this->checkStuckJobs();
                break;
            case 'recover':
                $this->recoverStuckJobs();
                break;
            case 'auto':
                $this->autoRecovery();
                break;
            default:
                $this->error("Unknown action: {$action}");
                $this->info("Available actions: status, check, recover, auto");
        }
    }

    /**
     * Show current crawl status
     */
    private function showCrawlStatus()
    {
        $this->info('🔍 CRAWL STATUS MONITOR');
        $this->line('');

        // Get all crawling stories
        $crawlingStories = Story::where('crawl_status', config('constants.CRAWL_STATUS.VALUES.CRAWLING'))
                                ->with('chapters')
                                ->get();

        if ($crawlingStories->isEmpty()) {
            $this->info('✅ No stories currently crawling');
            return;
        }

        $this->info("📚 Found {$crawlingStories->count()} stories in crawling status:");
        $this->line('');

        foreach ($crawlingStories as $story) {
            $this->showStoryStatus($story);
        }
    }

    /**
     * Show detailed status for a story
     */
    private function showStoryStatus(Story $story)
    {
        $this->info("📖 Story: {$story->title} (ID: {$story->id})");

        // Calculate expected vs actual chapters
        $expectedTotal = $story->end_chapter - $story->start_chapter + 1;
        $actualChapters = $story->chapters()->count();
        $crawledChapters = $story->chapters()->where('is_crawled', true)->count();

        // Check file system
        $storageDir = storage_path('app/content/' . $story->folder_name);
        $fileCount = 0;
        if (File::isDirectory($storageDir)) {
            $files = File::glob($storageDir . '/chapter_*.txt');
            $fileCount = count($files);
        }

        // Time analysis
        $lastUpdate = $story->updated_at;
        $timeSinceUpdate = $lastUpdate->diffInMinutes(now());
        $isStuck = $timeSinceUpdate > $this->option('timeout');

        // Display info
        $this->line("  📊 Progress: {$actualChapters}/{$expectedTotal} chapters in DB");
        $this->line("  📁 Files: {$fileCount} text files in storage");
        $this->line("  🕒 Last update: {$lastUpdate->format('Y-m-d H:i:s')} ({$timeSinceUpdate} min ago)");
        $this->line("  🔄 Job ID: " . ($story->crawl_job_id ?? 'None'));

        if ($isStuck) {
            $this->warn("  ⚠️ POTENTIALLY STUCK (>{$this->option('timeout')} min without update)");
        } else {
            $this->info("  ✅ Active (updated recently)");
        }

        // Check if crawl is actually complete
        if ($actualChapters >= $expectedTotal) {
            $this->info("  🎉 CRAWL APPEARS COMPLETE - should be marked as CRAWLED");
        } elseif ($fileCount >= $expectedTotal) {
            $this->info("  📁 FILES COMPLETE - may need database import");
        }

        $this->line('');
    }

    /**
     * Check for stuck jobs
     */
    private function checkStuckJobs()
    {
        $this->info('🔍 CHECKING FOR STUCK CRAWL JOBS');
        $this->line('');

        $timeoutMinutes = $this->option('timeout');
        $cutoffTime = now()->subMinutes($timeoutMinutes);

        $stuckStories = Story::where('crawl_status', config('constants.CRAWL_STATUS.VALUES.CRAWLING'))
                            ->where('updated_at', '<', $cutoffTime)
                            ->with('chapters')
                            ->get();

        if ($stuckStories->isEmpty()) {
            $this->info("✅ No stuck jobs found (timeout: {$timeoutMinutes} minutes)");
            return;
        }

        $this->warn("⚠️ Found {$stuckStories->count()} potentially stuck jobs:");
        $this->line('');

        foreach ($stuckStories as $story) {
            $this->analyzeStuckStory($story);
        }

        if (!$this->option('fix')) {
            $this->line('');
            $this->info('💡 Use --fix to automatically recover stuck jobs');
            $this->info('💡 Use "crawl:monitor recover" to fix them');
        }
    }

    /**
     * Analyze a stuck story
     */
    private function analyzeStuckStory(Story $story)
    {
        $this->warn("🚨 STUCK: {$story->title} (ID: {$story->id})");

        $expectedTotal = $story->end_chapter - $story->start_chapter + 1;
        $actualChapters = $story->chapters()->count();
        $crawledChapters = $story->chapters()->where('is_crawled', true)->count();

        // Check files
        $storageDir = storage_path('app/content/' . $story->folder_name);
        $fileCount = 0;
        if (File::isDirectory($storageDir)) {
            $files = File::glob($storageDir . '/chapter_*.txt');
            $fileCount = count($files);
        }

        $timeSinceUpdate = $story->updated_at->diffInMinutes(now());

        $this->line("  📊 DB Chapters: {$actualChapters}/{$expectedTotal}");
        $this->line("  📁 Files: {$fileCount}/{$expectedTotal}");
        $this->line("  🕒 Stuck for: {$timeSinceUpdate} minutes");

        // Determine recovery action
        if ($actualChapters >= $expectedTotal) {
            $this->info("  ✅ COMPLETE - should mark as CRAWLED");
            $action = 'mark_complete';
        } elseif ($fileCount >= $expectedTotal) {
            $this->info("  📥 FILES READY - should import to DB then mark complete");
            $action = 'import_and_complete';
        } elseif ($actualChapters > 0 || $fileCount > 0) {
            $this->warn("  🔄 PARTIAL - should mark for re-crawl (smart crawl)");
            $action = 'mark_recrawl';
        } else {
            $this->error("  ❌ NO PROGRESS - should reset to not crawled");
            $action = 'reset';
        }

        $this->line("  🎯 Recommended action: {$action}");
        $this->line('');

        return $action;
    }

    /**
     * Recover stuck jobs
     */
    private function recoverStuckJobs()
    {
        $this->info('🔧 RECOVERING STUCK CRAWL JOBS');
        $this->line('');

        $timeoutMinutes = $this->option('timeout');
        $cutoffTime = now()->subMinutes($timeoutMinutes);

        $stuckStories = Story::where('crawl_status', config('constants.CRAWL_STATUS.VALUES.CRAWLING'))
                            ->where('updated_at', '<', $cutoffTime)
                            ->with('chapters')
                            ->get();

        if ($stuckStories->isEmpty()) {
            $this->info("✅ No stuck jobs to recover");
            return;
        }

        $this->info("🔧 Recovering {$stuckStories->count()} stuck jobs...");
        $this->line('');

        $recovered = 0;
        foreach ($stuckStories as $story) {
            if ($this->recoverStory($story)) {
                $recovered++;
            }
        }

        $this->line('');
        $this->info("✅ Recovery complete: {$recovered}/{$stuckStories->count()} stories recovered");
    }

    /**
     * Recover a single story
     */
    private function recoverStory(Story $story)
    {
        $this->info("🔧 Recovering: {$story->title} (ID: {$story->id})");

        $expectedTotal = $story->end_chapter - $story->start_chapter + 1;
        $actualChapters = $story->chapters()->count();

        // Check files
        $storageDir = storage_path('app/content/' . $story->folder_name);
        $fileCount = 0;
        if (File::isDirectory($storageDir)) {
            $files = File::glob($storageDir . '/chapter_*.txt');
            $fileCount = count($files);
        }

        try {
            if ($actualChapters >= $expectedTotal) {
                // Complete - mark as crawled
                $story->update([
                    'crawl_status' => config('constants.CRAWL_STATUS.VALUES.CRAWLED'),
                    'crawl_job_id' => null
                ]);
                $this->info("  ✅ Marked as CRAWLED");

            } elseif ($fileCount >= $expectedTotal) {
                // Files ready - import then mark complete
                $this->info("  📥 Importing files to database...");
                $this->call('stories:scan', ['story_id' => $story->id]);

                // Check again after import
                $story->refresh();
                $newChapterCount = $story->chapters()->count();

                if ($newChapterCount >= $expectedTotal) {
                    $story->update([
                        'crawl_status' => config('constants.CRAWL_STATUS.VALUES.CRAWLED'),
                        'crawl_job_id' => null
                    ]);
                    $this->info("  ✅ Imported and marked as CRAWLED");
                } else {
                    $story->update([
                        'crawl_status' => config('constants.CRAWL_STATUS.VALUES.RE_CRAWL'),
                        'crawl_job_id' => null
                    ]);
                    $this->warn("  🔄 Import incomplete, marked for RE-CRAWL");
                }

            } elseif ($actualChapters > 0 || $fileCount > 0) {
                // Partial progress - mark for re-crawl
                $story->update([
                    'crawl_status' => config('constants.CRAWL_STATUS.VALUES.RE_CRAWL'),
                    'crawl_job_id' => null
                ]);
                $this->warn("  🔄 Marked for RE-CRAWL (smart crawl will resume)");

            } else {
                // No progress - reset
                $story->update([
                    'crawl_status' => config('constants.CRAWL_STATUS.VALUES.NOT_CRAWLED'),
                    'crawl_job_id' => null
                ]);
                $this->error("  ❌ Reset to NOT_CRAWLED");
            }

            Log::info("Crawl job recovered for story: {$story->title}", [
                'story_id' => $story->id,
                'old_status' => 'CRAWLING',
                'new_status' => $story->crawl_status,
                'chapters_in_db' => $actualChapters,
                'files_in_storage' => $fileCount,
                'expected_total' => $expectedTotal
            ]);

            return true;

        } catch (\Exception $e) {
            $this->error("  ❌ Recovery failed: " . $e->getMessage());
            Log::error("Failed to recover stuck crawl job", [
                'story_id' => $story->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Auto recovery - check and fix automatically
     */
    private function autoRecovery()
    {
        $this->info('🤖 AUTO RECOVERY MODE');
        $this->line('');

        // First check for stuck jobs
        $timeoutMinutes = $this->option('timeout');
        $cutoffTime = now()->subMinutes($timeoutMinutes);

        $stuckStories = Story::where('crawl_status', config('constants.CRAWL_STATUS.VALUES.CRAWLING'))
                            ->where('updated_at', '<', $cutoffTime)
                            ->with('chapters')
                            ->get();

        if ($stuckStories->isEmpty()) {
            $this->info("✅ No stuck jobs found - system healthy");

            // Also check for completed jobs that weren't marked
            $this->checkUnmarkedCompletedJobs();
            return;
        }

        $this->warn("🔧 Auto-recovering {$stuckStories->count()} stuck jobs...");

        $recovered = 0;
        foreach ($stuckStories as $story) {
            $this->line("Processing: {$story->title}");
            if ($this->recoverStory($story)) {
                $recovered++;
            }
        }

        $this->line('');
        $this->info("🎉 Auto-recovery complete: {$recovered}/{$stuckStories->count()} recovered");

        // Log the auto-recovery
        Log::info("Auto-recovery completed", [
            'stuck_jobs_found' => $stuckStories->count(),
            'successfully_recovered' => $recovered,
            'timeout_minutes' => $timeoutMinutes
        ]);
    }

    /**
     * Check for completed jobs that weren't properly marked
     */
    private function checkUnmarkedCompletedJobs()
    {
        $this->info('🔍 Checking for unmarked completed jobs...');

        $crawlingStories = Story::where('crawl_status', config('constants.CRAWL_STATUS.VALUES.CRAWLING'))
                                ->with('chapters')
                                ->get();

        $unmarked = 0;
        foreach ($crawlingStories as $story) {
            $expectedTotal = $story->end_chapter - $story->start_chapter + 1;
            $actualChapters = $story->chapters()->count();

            if ($actualChapters >= $expectedTotal) {
                $story->update([
                    'crawl_status' => config('constants.CRAWL_STATUS.VALUES.CRAWLED'),
                    'crawl_job_id' => null
                ]);
                $this->info("  ✅ Marked {$story->title} as CRAWLED");
                $unmarked++;
            }
        }

        if ($unmarked > 0) {
            $this->info("🎉 Fixed {$unmarked} unmarked completed jobs");
        } else {
            $this->info("✅ All completed jobs properly marked");
        }
    }
}
