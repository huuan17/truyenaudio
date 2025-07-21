<?php

namespace App\Jobs;

use App\Models\Story;
use App\Models\Chapter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class SmartCrawlStoryJob implements ShouldQueue
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
     * Execute the smart crawl job.
     */
    public function handle(): void
    {
        try {
            Log::info("Starting smart crawl job for story ID: {$this->storyId}");

            // Get story
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
                Log::info("Smart crawl job cancelled after starting for story ID: {$this->storyId}");
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

            // Execute smart crawl workflow
            $result = $this->executeSmartCrawlWorkflow($story);

            // Refresh story to get latest status
            $story = $story->fresh();

            if ($result['success']) {
                Log::info("Smart crawl job completed successfully for story ID: {$this->storyId}", $result);

                // Always check final status after completion
                $this->checkAndUpdateFinalStatus($story);

                // Auto-fix status if needed
                $story->fresh()->autoFixCrawlStatus();

            } else {
                Log::error("Smart crawl job failed for story ID: {$this->storyId}", $result);
                $story->update([
                    'crawl_status' => config('constants.CRAWL_STATUS.VALUES.RE_CRAWL'),
                    'crawl_job_id' => null
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Smart crawl job exception for story ID {$this->storyId}: " . $e->getMessage());
            
            // Update story status to indicate failure
            $story = Story::find($this->storyId);
            if ($story) {
                $story->update([
                    'crawl_status' => config('constants.CRAWL_STATUS.VALUES.RE_CRAWL'),
                    'crawl_job_id' => null
                ]);
            }
            
            throw $e;
        }
    }

    /**
     * Execute smart crawl workflow
     */
    private function executeSmartCrawlWorkflow(Story $story): array
    {
        Log::info("Executing smart crawl workflow for story: {$story->title}");

        // Step 1: Scan existing chapters and import to database (without content)
        $scanResult = $this->scanAndImportChapters($story);
        Log::info("Chapter scan result", $scanResult);

        // Step 2: Determine missing chapters
        $missingChapters = $this->findMissingChapters($story);
        Log::info("Missing chapters analysis", [
            'story_id' => $story->id,
            'missing_count' => count($missingChapters),
            'missing_chapters' => array_slice($missingChapters, 0, 10), // Log first 10 for brevity
            'all_missing_chapters' => $missingChapters // Log all missing chapters for debugging
        ]);

        // Step 3: Smart crawl missing chapters only
        if (empty($missingChapters)) {
            Log::info("No missing chapters found, story is complete");

            // Update status to CRAWLED since all chapters exist
            $story->update([
                'crawl_status' => config('constants.CRAWL_STATUS.VALUES.CRAWLED'),
                'crawl_job_id' => null
            ]);

            Log::info("Updated story status to CRAWLED (all chapters complete)", [
                'story_id' => $story->id,
                'title' => $story->title,
                'scanned_chapters' => $scanResult['imported']
            ]);

            return [
                'success' => true,
                'action' => 'completed',
                'message' => 'Story already complete, status updated to CRAWLED',
                'scanned_chapters' => $scanResult['imported'],
                'missing_chapters' => 0,
                'crawled_chapters' => 0
            ];
        }

        // Step 4: Crawl only missing chapters
        $crawlResult = $this->crawlMissingChapters($story, $missingChapters);
        
        return [
            'success' => $crawlResult['success'],
            'action' => $crawlResult['success'] ? 'crawled' : 'failed',
            'message' => $crawlResult['message'],
            'scanned_chapters' => $scanResult['imported'],
            'missing_chapters' => count($missingChapters),
            'crawled_chapters' => $crawlResult['crawled_count'] ?? 0
        ];
    }

    /**
     * Scan existing files and import chapters to database (without content)
     */
    private function scanAndImportChapters(Story $story): array
    {
        Log::info("Scanning existing chapters for story: {$story->title}");

        $storageDir = storage_path('app/content/' . $story->folder_name);
        $imported = 0;
        $skipped = 0;

        if (!File::isDirectory($storageDir)) {
            Log::info("Storage directory does not exist: {$storageDir}");
            return ['imported' => 0, 'skipped' => 0];
        }

        // Find all .txt files
        $files = File::glob($storageDir . '/*.txt');
        
        foreach ($files as $filePath) {
            $fileName = basename($filePath);
            
            // Extract chapter number from filename (e.g., chuong-1.txt)
            if (preg_match('/chuong-(\d+)\.txt/', $fileName, $matches)) {
                $chapterNumber = (int) $matches[1];
                
                // Check if chapter already exists in database
                $existingChapter = Chapter::where('story_id', $story->id)
                    ->where('chapter_number', $chapterNumber)
                    ->first();

                if ($existingChapter) {
                    $skipped++;
                    continue;
                }

                // Read file to get title (first line or generate from number)
                $content = File::get($filePath);
                $title = $this->extractChapterTitle($content, $chapterNumber);
                
                // Create chapter record without content
                Chapter::create([
                    'story_id' => $story->id,
                    'chapter_number' => $chapterNumber,
                    'title' => $title,
                    'is_crawled' => true,
                    'file_path' => 'content/' . $story->folder_name . '/' . $fileName,
                    'crawled_at' => now(),
                    'content' => '' // Don't store content to save space
                ]);

                $imported++;
                Log::info("Imported chapter {$chapterNumber} for story {$story->id}");
            }
        }

        Log::info("Chapter scan completed", [
            'story_id' => $story->id,
            'imported' => $imported,
            'skipped' => $skipped
        ]);

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    /**
     * Find missing chapters that need to be crawled
     */
    private function findMissingChapters(Story $story): array
    {
        $missingChapters = [];
        
        // Get all chapter numbers that should exist
        $expectedChapters = range($story->start_chapter, $story->end_chapter);
        
        // Get existing chapters in database
        $existingChapters = Chapter::where('story_id', $story->id)
            ->where('is_crawled', true)
            ->pluck('chapter_number')
            ->toArray();

        // Find missing chapters
        $missingChapters = array_diff($expectedChapters, $existingChapters);
        
        return array_values($missingChapters);
    }

    /**
     * Crawl only missing chapters
     */
    private function crawlMissingChapters(Story $story, array $missingChapters): array
    {
        Log::info("Starting to crawl missing chapters", [
            'story_id' => $story->id,
            'missing_count' => count($missingChapters),
            'missing_chapters' => $missingChapters
        ]);

        // Use the existing smart crawl command but with specific chapters
        $exitCode = Artisan::call('crawl:stories', [
            '--story_id' => $story->id,
            '--smart' => true
        ]);

        Log::info("Crawl command completed", [
            'story_id' => $story->id,
            'exit_code' => $exitCode,
            'command' => 'crawl:stories --story_id=' . $story->id . ' --force'
        ]);

        if ($exitCode === 0) {
            // Verify which chapters were actually crawled
            $nowCrawledChapters = Chapter::where('story_id', $story->id)
                ->where('is_crawled', true)
                ->whereIn('chapter_number', $missingChapters)
                ->pluck('chapter_number')
                ->toArray();

            $stillMissing = array_diff($missingChapters, $nowCrawledChapters);

            Log::info("Crawl verification", [
                'story_id' => $story->id,
                'attempted_chapters' => $missingChapters,
                'successfully_crawled' => $nowCrawledChapters,
                'still_missing' => $stillMissing
            ]);

            // Log detailed info about chapters that are still missing
            if (!empty($stillMissing)) {
                Log::warning("Chapters not found at source", [
                    'story_id' => $story->id,
                    'story_title' => $story->title,
                    'missing_chapters' => $stillMissing,
                    'source_url_pattern' => $story->source_url,
                    'message' => 'These chapters may not exist at the crawl source or have different URL structure'
                ]);

                // Update story with missing chapters info
                $story->update([
                    'missing_chapters_info' => json_encode([
                        'chapters' => $stillMissing,
                        'last_check' => now()->toDateTimeString(),
                        'reason' => 'not_found_at_source'
                    ])
                ]);
            }

            return [
                'success' => true,
                'message' => 'Successfully crawled missing chapters',
                'crawled_count' => count($nowCrawledChapters),
                'attempted_count' => count($missingChapters),
                'still_missing' => $stillMissing
            ];
        } else {
            Log::error("Crawl command failed", [
                'story_id' => $story->id,
                'exit_code' => $exitCode,
                'missing_chapters' => $missingChapters
            ]);

            return [
                'success' => false,
                'message' => "Failed to crawl missing chapters (exit code: {$exitCode})",
                'crawled_count' => 0
            ];
        }
    }

    /**
     * Extract chapter title from content
     */
    private function extractChapterTitle(string $content, int $chapterNumber): string
    {
        $lines = explode("\n", trim($content));
        $firstLine = trim($lines[0] ?? '');
        
        // If first line looks like a title, use it
        if (!empty($firstLine) && strlen($firstLine) < 100) {
            return $firstLine;
        }
        
        // Otherwise generate a title
        return "Chương {$chapterNumber}";
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
            Log::info("Smart crawl job cancelled: Story {$this->storyId} status changed to {$story->crawl_status}");
            return true;
        }

        return false;
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
        Log::error("Smart crawl job failed for story ID {$this->storyId}: " . $exception->getMessage());

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
