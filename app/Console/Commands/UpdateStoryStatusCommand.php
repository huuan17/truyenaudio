<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Story;
use App\Models\Chapter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class UpdateStoryStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stories:update-status
                            {--story= : Specific story ID to check}
                            {--dry-run : Show what would be updated without making changes}
                            {--force : Force update even if status seems correct}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update story crawl status based on actual chapter count';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 CHECKING STORY CRAWL STATUS');
        $this->line('');

        $storyId = $this->option('story');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($storyId) {
            $this->checkSingleStory($storyId, $dryRun, $force);
        } else {
            $this->checkAllStories($dryRun, $force);
        }
    }

    /**
     * Check single story status
     */
    private function checkSingleStory($storyId, $dryRun = false, $force = false)
    {
        $story = Story::find($storyId);

        if (!$story) {
            $this->error("Story with ID {$storyId} not found");
            return;
        }

        $this->info("📖 Checking: {$story->title} (ID: {$story->id})");
        $this->checkAndUpdateStory($story, $dryRun, $force);
    }

    /**
     * Check all stories status
     */
    private function checkAllStories($dryRun = false, $force = false)
    {
        // Get stories that might need status update
        $stories = Story::whereIn('crawl_status', [
            config('constants.CRAWL_STATUS.VALUES.NOT_CRAWLED'),
            config('constants.CRAWL_STATUS.VALUES.CRAWLING'),
            config('constants.CRAWL_STATUS.VALUES.RE_CRAWL')
        ])->get();

        if ($stories->isEmpty()) {
            $this->info('✅ No stories need status checking');
            return;
        }

        $this->info("📚 Found {$stories->count()} stories to check");
        $this->line('');

        $updated = 0;
        $alreadyCorrect = 0;
        $needsAction = 0;

        foreach ($stories as $story) {
            $result = $this->checkAndUpdateStory($story, $dryRun, $force);

            switch ($result) {
                case 'updated':
                    $updated++;
                    break;
                case 'correct':
                    $alreadyCorrect++;
                    break;
                case 'needs_action':
                    $needsAction++;
                    break;
            }
        }

        $this->line('');
        $this->info('📊 SUMMARY:');
        $this->info("  ✅ Updated: {$updated} stories");
        $this->info("  ✓ Already correct: {$alreadyCorrect} stories");
        $this->info("  ⚠️ Needs manual action: {$needsAction} stories");

        if ($dryRun && $updated > 0) {
            $this->line('');
            $this->warn('💡 This was a dry run. Use without --dry-run to actually update.');
        }
    }

    /**
     * Check and update individual story
     */
    private function checkAndUpdateStory(Story $story, $dryRun = false, $force = false)
    {
        $expectedTotal = $story->end_chapter - $story->start_chapter + 1;
        $actualChapters = $story->chapters()->count();
        $crawledChapters = $story->chapters()->where('is_crawled', true)->count();

        // Check files in storage
        $storageDir = storage_path('app/content/' . $story->folder_name);
        $fileCount = 0;
        if (File::isDirectory($storageDir)) {
            $files = File::glob($storageDir . '/chapter_*.txt');
            $fileCount = count($files);
        }

        $currentStatus = $story->crawl_status;
        $currentStatusLabel = $this->getStatusLabel($currentStatus);

        $this->line("  📊 Progress: {$actualChapters}/{$expectedTotal} chapters in DB");
        $this->line("  📁 Files: {$fileCount}/{$expectedTotal} files in storage");
        $this->line("  🏷️ Current status: {$currentStatusLabel}");

        // Determine what the status should be
        $shouldBeStatus = null;
        $reason = '';

        if ($actualChapters >= $expectedTotal) {
            $shouldBeStatus = config('constants.CRAWL_STATUS.VALUES.CRAWLED');
            $reason = 'All chapters are in database';
        } elseif ($fileCount >= $expectedTotal) {
            $shouldBeStatus = config('constants.CRAWL_STATUS.VALUES.CRAWLED');
            $reason = 'All files exist in storage (will import to DB)';
        } elseif ($actualChapters > 0 || $fileCount > 0) {
            if ($currentStatus == config('constants.CRAWL_STATUS.VALUES.NOT_CRAWLED')) {
                $shouldBeStatus = config('constants.CRAWL_STATUS.VALUES.RE_CRAWL');
                $reason = 'Partial progress found, should be marked for re-crawl';
            } else {
                // Already in correct partial state
                $shouldBeStatus = $currentStatus;
                $reason = 'Status is appropriate for partial progress';
            }
        } else {
            // No progress
            $shouldBeStatus = config('constants.CRAWL_STATUS.VALUES.NOT_CRAWLED');
            $reason = 'No progress found';
        }

        $shouldBeStatusLabel = $this->getStatusLabel($shouldBeStatus);

        if ($currentStatus == $shouldBeStatus && !$force) {
            $this->info("  ✅ Status is correct: {$currentStatusLabel}");
            $this->line('');
            return 'correct';
        }

        $this->warn("  🔄 Status should be: {$shouldBeStatusLabel}");
        $this->line("  💡 Reason: {$reason}");

        if ($dryRun) {
            $this->warn("  🔍 DRY RUN: Would update status to {$shouldBeStatusLabel}");
            $this->line('');
            return 'updated';
        }

        // Perform the update
        try {
            $story->update([
                'crawl_status' => $shouldBeStatus,
                'crawl_job_id' => null // Clear job ID when updating status
            ]);

            Log::info("Story status auto-updated", [
                'story_id' => $story->id,
                'story_title' => $story->title,
                'old_status' => $currentStatus,
                'new_status' => $shouldBeStatus,
                'chapters_in_db' => $actualChapters,
                'files_in_storage' => $fileCount,
                'expected_total' => $expectedTotal,
                'reason' => $reason
            ]);

            $this->info("  ✅ Updated status to: {$shouldBeStatusLabel}");
            $this->line('');
            return 'updated';

        } catch (\Exception $e) {
            $this->error("  ❌ Failed to update status: " . $e->getMessage());
            $this->line('');
            return 'needs_action';
        }
    }

    /**
     * Get human-readable status label
     */
    private function getStatusLabel($status)
    {
        $labels = [
            config('constants.CRAWL_STATUS.VALUES.NOT_CRAWLED') => 'Chưa crawl',
            config('constants.CRAWL_STATUS.VALUES.CRAWLED') => 'Đã crawl',
            config('constants.CRAWL_STATUS.VALUES.RE_CRAWL') => 'Cần crawl lại',
            config('constants.CRAWL_STATUS.VALUES.CRAWLING') => 'Đang crawl',
            config('constants.CRAWL_STATUS.VALUES.FAILED') => 'Lỗi crawl'
        ];

        return $labels[$status] ?? "Unknown ({$status})";
    }
}
