<?php

namespace App\Jobs;

use App\Models\Story;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CrawlStoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $storyId;

    // Set timeout to 4 hours for large crawls
    public $timeout = 14400; // 4 hours

    // Prevent job from timing out
    public $tries = 1;

    // Make job cancellable
    public $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     */
    public function __construct($storyId)
    {
        $this->storyId = $storyId;

        // Set queue name for crawl processing
        $this->onQueue('crawl');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Starting crawl job for story ID: {$this->storyId}");

            // Set story status to "Đang crawl" at the beginning
            $story = Story::find($this->storyId);
            if (!$story) {
                Log::error("Story not found: {$this->storyId}");
                return;
            }

            // Set status to CRAWLING first (without job ID)
            $story->update([
                'crawl_status' => config('constants.CRAWL_STATUS.VALUES.CRAWLING')
            ]);

            // Check if job should be cancelled after setting status
            if ($this->shouldCancelAfterStart($story)) {
                Log::info("Job cancelled after starting for story ID: {$this->storyId}");
                // Reset status if cancelled
                $story->update([
                    'crawl_status' => config('constants.CRAWL_STATUS.VALUES.NOT_CRAWLED'),
                    'crawl_job_id' => null
                ]);
                return;
            }

            // If not cancelled, set job ID
            $story->update(['crawl_job_id' => $this->job->getJobId()]);
            Log::info("Set story {$this->storyId} status to CRAWLING with job ID: " . $this->job->getJobId());

            // Set unlimited execution time for this job
            set_time_limit(0);
            ini_set('memory_limit', '1G');

            // Run the crawl command with smart flag and cancellation check
            $exitCode = $this->runCrawlWithCancellationCheck($story);

            // Refresh story to get latest status (crawl command might have updated it)
            $story = $story->fresh();

            if ($exitCode === 0) {
                Log::info("Crawl job completed successfully for story ID: {$this->storyId}");

                // Always check final status after completion
                $this->checkAndUpdateFinalStatus($story);

                // Auto-fix status if needed
                $story->fresh()->autoFixCrawlStatus();

            } else {
                Log::error("Crawl job failed with exit code {$exitCode} for story ID: {$this->storyId}");
                $story->update([
                    'crawl_status' => config('constants.CRAWL_STATUS.VALUES.RE_CRAWL'),
                    'crawl_job_id' => null // Clear job ID on failure
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Crawl job exception for story ID {$this->storyId}: " . $e->getMessage());

            // Update story status to indicate failure
            $story = Story::find($this->storyId);
            if ($story) {
                $story->update([
                    'crawl_status' => config('constants.CRAWL_STATUS.VALUES.RE_CRAWL'),
                    'crawl_job_id' => null // Clear job ID on exception
                ]);
            }

            throw $e;
        }
    }

    /**
     * Check if the job should be cancelled (before starting)
     */
    private function shouldCancel(Story $story): bool
    {
        // Refresh story from database to get latest status
        $story = $story->fresh();

        // Allow NOT_CRAWLED status at the beginning
        $allowedStatuses = [
            config('constants.CRAWL_STATUS.VALUES.NOT_CRAWLED'),
            config('constants.CRAWL_STATUS.VALUES.CRAWLING')
        ];

        if (!in_array($story->crawl_status, $allowedStatuses)) {
            Log::info("Job cancelled: Story {$this->storyId} status is {$story->crawl_status} (not allowed)");
            return true;
        }

        return false;
    }

    /**
     * Check if the job should be cancelled (after starting)
     */
    private function shouldCancelAfterStart(Story $story): bool
    {
        // Refresh story from database to get latest status
        $story = $story->fresh();

        // After starting, only CRAWLING status is allowed
        if ($story->crawl_status !== config('constants.CRAWL_STATUS.VALUES.CRAWLING')) {
            Log::info("Job cancelled: Story {$this->storyId} status changed to {$story->crawl_status}");
            return true;
        }

        // Check job ID logic with improved handling
        $currentJobId = $this->job->getJobId();
        $storedJobId = $story->crawl_job_id;

        // Convert to same type for comparison (both as strings)
        $currentJobIdStr = (string) $currentJobId;
        $storedJobIdStr = (string) $storedJobId;

        Log::info("Job ID comparison: Story {$this->storyId} - Current: {$currentJobIdStr} (type: " . gettype($currentJobId) . "), Stored: {$storedJobIdStr} (type: " . gettype($storedJobId) . ")");

        // If no job ID stored, this is expected (job ID will be set after this check)
        if ($storedJobId === null) {
            Log::info("Story {$this->storyId} has no job ID stored yet, job {$currentJobId} will proceed");
            return false; // Allow job to continue, job ID will be set later
        }

        // If job IDs don't match, check if stored job still exists
        if ($storedJobIdStr !== $currentJobIdStr) {
            Log::info("Job ID mismatch detected: Story {$this->storyId} - stored: '{$storedJobIdStr}', current: '{$currentJobIdStr}'");

            // Check if the stored job ID still exists in the queue
            $storedJobExists = DB::table('jobs')->where('id', $storedJobId)->exists();
            Log::info("Stored job {$storedJobId} exists in queue: " . ($storedJobExists ? 'YES' : 'NO'));

            if (!$storedJobExists) {
                Log::info("Job takeover: Story {$this->storyId} stored job {$storedJobId} no longer exists, job {$currentJobId} taking over");
                // Stored job doesn't exist, current job can take over
                $story->update(['crawl_job_id' => $currentJobId]);
                return false;
            }

            // Both jobs exist, use numeric comparison
            $currentJobIdInt = (int) $currentJobId;
            $storedJobIdInt = (int) $storedJobId;

            Log::info("Numeric comparison: current {$currentJobIdInt} vs stored {$storedJobIdInt}");

            if ($currentJobIdInt > $storedJobIdInt) {
                Log::info("Job takeover: Story {$this->storyId} newer job {$currentJobIdInt} taking over from {$storedJobIdInt}");
                // Update with newer job ID and continue
                $story->update(['crawl_job_id' => $currentJobId]);
                return false;
            } else {
                Log::info("Job cancelled: Story {$this->storyId} older/duplicate job {$currentJobIdInt}, current job is {$storedJobIdInt}");
                return true;
            }
        }

        // Job ID matches, continue
        Log::info("Job ID matches: Story {$this->storyId} job {$currentJobIdStr} continuing");
        return false;
    }

    /**
     * Run crawl command with periodic cancellation checks
     */
    private function runCrawlWithCancellationCheck(Story $story): int
    {
        // For now, use simple approach - just run the command
        // TODO: Implement proper process monitoring and cancellation
        Log::info("Running crawl command for story {$this->storyId} with cancellation check");

        // Check cancellation before starting
        if ($this->shouldCancelAfterStart($story)) {
            Log::info("Job cancelled before running command for story {$this->storyId}");
            return -1;
        }

        // Run the crawl command
        $exitCode = Artisan::call('crawl:stories', [
            '--story_id' => $this->storyId,
            '--smart' => true
        ]);

        // Check cancellation after command
        if ($this->shouldCancelAfterStart($story)) {
            Log::info("Job cancelled after running command for story {$this->storyId}");
            return -1;
        }

        return $exitCode;
    }

    /**
     * Check and update final status after crawl completion
     */
    private function checkAndUpdateFinalStatus($story)
    {
        // Refresh story data
        $story = $story->fresh();

        // Get current progress
        $progress = $story->getCrawlProgress();

        Log::info("Checking final status for story {$this->storyId}", [
            'expected_total' => $progress['expected_total'],
            'chapters_in_db' => $progress['chapters_in_db'],
            'files_in_storage' => $progress['files_in_storage'],
            'db_complete' => $progress['db_complete'],
            'files_complete' => $progress['files_complete']
        ]);

        if ($progress['db_complete']) {
            // Story is complete - update to CRAWLED
            $story->update([
                'crawl_status' => config('constants.CRAWL_STATUS.VALUES.CRAWLED'),
                'crawl_job_id' => null
            ]);

            Log::info("Story {$this->storyId} marked as CRAWLED - all chapters complete", [
                'chapters_count' => $progress['chapters_in_db'],
                'expected_total' => $progress['expected_total']
            ]);

        } elseif ($progress['files_complete']) {
            // Files are complete but not imported - keep current status but clear job
            $story->update(['crawl_job_id' => null]);

            Log::info("Story {$this->storyId} has complete files but needs import", [
                'files_count' => $progress['files_in_storage'],
                'chapters_in_db' => $progress['chapters_in_db']
            ]);

        } else {
            // Incomplete - mark for re-crawl
            $story->update([
                'crawl_status' => config('constants.CRAWL_STATUS.VALUES.RE_CRAWL'),
                'crawl_job_id' => null
            ]);

            Log::info("Story {$this->storyId} marked for re-crawl - incomplete", [
                'chapters_count' => $progress['chapters_in_db'],
                'files_count' => $progress['files_in_storage'],
                'expected_total' => $progress['expected_total']
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Crawl job failed for story ID {$this->storyId}: " . $exception->getMessage());

        // Update story status to indicate failure
        $story = Story::find($this->storyId);
        if ($story) {
            $story->update([
                'crawl_status' => config('constants.CRAWL_STATUS.VALUES.RE_CRAWL'),
                'crawl_job_id' => null
            ]);
        }
    }
}
