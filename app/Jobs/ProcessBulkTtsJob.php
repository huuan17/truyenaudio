<?php

namespace App\Jobs;

use App\Models\Chapter;
use App\Models\BulkTtsTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class ProcessBulkTtsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $bulkTaskId;
    protected $chapterIds;
    protected $currentIndex;
    
    // Rate limiting settings
    const RATE_LIMIT_KEY = 'vbee_api_rate_limit';
    const MAX_REQUESTS_PER_MINUTE = 10; // Adjust based on VBee limits
    const DELAY_BETWEEN_REQUESTS = 6; // seconds (60/10 = 6)

    public $timeout = 300; // 5 minutes per job
    public $tries = 3;

    public function __construct($bulkTaskId, $chapterIds, $currentIndex = 0)
    {
        $this->bulkTaskId = $bulkTaskId;
        $this->chapterIds = $chapterIds;
        $this->currentIndex = $currentIndex;
        
        // Set queue name for TTS processing
        $this->onQueue('tts');
    }

    public function handle()
    {
        $bulkTask = BulkTtsTask::find($this->bulkTaskId);
        if (!$bulkTask || $bulkTask->status === 'cancelled') {
            Log::info("Bulk TTS task {$this->bulkTaskId} not found or cancelled");
            return;
        }

        try {
            // Check if we've processed all chapters
            if ($this->currentIndex >= count($this->chapterIds)) {
                $this->completeBulkTask($bulkTask);
                return;
            }

            // Get current chapter
            $chapterId = $this->chapterIds[$this->currentIndex];
            $chapter = Chapter::find($chapterId);
            
            if (!$chapter) {
                Log::warning("Chapter {$chapterId} not found, skipping");
                $this->processNextChapter($bulkTask);
                return;
            }

            // Update bulk task progress
            $this->updateBulkTaskProgress($bulkTask, $chapter);

            // Apply rate limiting
            $this->applyRateLimit();

            // Process TTS for current chapter
            $success = $this->processSingleChapterTts($chapter);

            // Update chapter status
            $this->updateChapterStatus($chapter, $success);

            // Update bulk task statistics
            $this->updateBulkTaskStats($bulkTask, $success);

            // Schedule next chapter processing
            $this->processNextChapter($bulkTask);

        } catch (Exception $e) {
            Log::error("Bulk TTS Job failed: " . $e->getMessage(), [
                'bulk_task_id' => $this->bulkTaskId,
                'chapter_id' => $chapterId ?? null,
                'current_index' => $this->currentIndex,
                'error' => $e->getTraceAsString()
            ]);

            $this->handleJobFailure($bulkTask, $e);
        }
    }

    protected function applyRateLimit()
    {
        $cacheKey = self::RATE_LIMIT_KEY;
        $requests = Cache::get($cacheKey, []);
        $now = time();
        
        // Remove requests older than 1 minute
        $requests = array_filter($requests, function($timestamp) use ($now) {
            return ($now - $timestamp) < 60;
        });

        // Check if we've hit the rate limit
        if (count($requests) >= self::MAX_REQUESTS_PER_MINUTE) {
            $oldestRequest = min($requests);
            $waitTime = 60 - ($now - $oldestRequest) + 1;
            
            Log::info("Rate limit reached, waiting {$waitTime} seconds");
            sleep($waitTime);
            
            // Clear the cache after waiting
            $requests = [];
        }

        // Add current request timestamp
        $requests[] = $now;
        Cache::put($cacheKey, $requests, 120); // Cache for 2 minutes

        // Add delay between requests
        if (count($requests) > 1) {
            sleep(self::DELAY_BETWEEN_REQUESTS);
        }
    }

    protected function processSingleChapterTts(Chapter $chapter)
    {
        try {
            // Update chapter status to processing
            $chapter->update([
                'audio_status' => 'processing',
                'tts_started_at' => now(),
                'tts_progress' => 0
            ]);

            // Broadcast real-time update
            $this->broadcastChapterUpdate($chapter, 'processing');

            // Check if chapter has content
            if (empty($chapter->content)) {
                throw new Exception("Chapter has no content");
            }

            // Call VBee TTS API (implement your actual TTS logic here)
            $audioFilePath = $this->callVbeeTtsApi($chapter);

            if ($audioFilePath) {
                $chapter->update([
                    'audio_file_path' => $audioFilePath,
                    'audio_status' => 'completed',
                    'tts_completed_at' => now(),
                    'tts_progress' => 100
                ]);

                $this->broadcastChapterUpdate($chapter, 'completed');
                return true;
            } else {
                throw new Exception("TTS API returned no audio file");
            }

        } catch (Exception $e) {
            $chapter->update([
                'audio_status' => 'failed',
                'tts_error' => $e->getMessage(),
                'tts_progress' => 0
            ]);

            $this->broadcastChapterUpdate($chapter, 'failed', $e->getMessage());
            return false;
        }
    }

    protected function callVbeeTtsApi(Chapter $chapter)
    {
        // Implement your actual VBee TTS API call here
        // This is a placeholder implementation
        
        Log::info("Processing TTS for chapter {$chapter->id}: {$chapter->title}");
        
        // Simulate API call delay
        sleep(2);
        
        // For demo purposes, return a dummy path
        // Replace this with actual VBee API integration
        return "storage/audio/chapter_{$chapter->id}_" . time() . ".mp3";
    }

    protected function updateBulkTaskProgress(BulkTtsTask $bulkTask, Chapter $chapter)
    {
        $progress = (($this->currentIndex + 1) / count($this->chapterIds)) * 100;
        
        $bulkTask->update([
            'current_chapter_id' => $chapter->id,
            'current_chapter_title' => $chapter->title,
            'progress' => round($progress, 2),
            'status' => 'processing'
        ]);

        // Broadcast real-time update
        $this->broadcastBulkTaskUpdate($bulkTask);
    }

    protected function updateChapterStatus(Chapter $chapter, bool $success)
    {
        if ($success) {
            Log::info("TTS completed successfully for chapter {$chapter->id}");
        } else {
            Log::warning("TTS failed for chapter {$chapter->id}");
        }
    }

    protected function updateBulkTaskStats(BulkTtsTask $bulkTask, bool $success)
    {
        if ($success) {
            $bulkTask->increment('completed_count');
        } else {
            $bulkTask->increment('failed_count');
        }
    }

    protected function processNextChapter(BulkTtsTask $bulkTask)
    {
        $nextIndex = $this->currentIndex + 1;
        
        if ($nextIndex < count($this->chapterIds)) {
            // Dispatch next chapter processing
            self::dispatch($this->bulkTaskId, $this->chapterIds, $nextIndex)
                ->delay(now()->addSeconds(2)); // Small delay between chapters
        } else {
            // All chapters processed
            $this->completeBulkTask($bulkTask);
        }
    }

    protected function completeBulkTask(BulkTtsTask $bulkTask)
    {
        $bulkTask->update([
            'status' => 'completed',
            'completed_at' => now(),
            'progress' => 100
        ]);

        Log::info("Bulk TTS task {$this->bulkTaskId} completed", [
            'total_chapters' => count($this->chapterIds),
            'completed' => $bulkTask->completed_count,
            'failed' => $bulkTask->failed_count
        ]);

        // Broadcast completion
        $this->broadcastBulkTaskUpdate($bulkTask);
    }

    protected function handleJobFailure(BulkTtsTask $bulkTask, Exception $e)
    {
        $bulkTask->update([
            'status' => 'failed',
            'error_message' => $e->getMessage(),
            'failed_at' => now()
        ]);

        $this->broadcastBulkTaskUpdate($bulkTask);
    }

    protected function broadcastChapterUpdate(Chapter $chapter, string $status, string $error = null)
    {
        // Implement broadcasting for real-time updates
        // You can use Laravel Broadcasting, Pusher, or WebSockets
        
        $data = [
            'chapter_id' => $chapter->id,
            'status' => $status,
            'progress' => $chapter->tts_progress ?? 0,
            'error' => $error
        ];

        // Example: broadcast('chapter-tts-update')->toOthers()->with($data);
        Log::info("Chapter TTS update", $data);
    }

    protected function broadcastBulkTaskUpdate(BulkTtsTask $bulkTask)
    {
        // Implement broadcasting for real-time bulk task updates
        
        $data = [
            'bulk_task_id' => $bulkTask->id,
            'status' => $bulkTask->status,
            'progress' => $bulkTask->progress,
            'current_chapter' => $bulkTask->current_chapter_title,
            'completed_count' => $bulkTask->completed_count,
            'failed_count' => $bulkTask->failed_count
        ];

        // Example: broadcast('bulk-tts-update')->toOthers()->with($data);
        Log::info("Bulk TTS update", $data);
    }

    public function failed(Exception $exception)
    {
        Log::error("Bulk TTS Job permanently failed", [
            'bulk_task_id' => $this->bulkTaskId,
            'current_index' => $this->currentIndex,
            'error' => $exception->getMessage()
        ]);

        $bulkTask = BulkTtsTask::find($this->bulkTaskId);
        if ($bulkTask) {
            $bulkTask->update([
                'status' => 'failed',
                'error_message' => 'Job failed permanently: ' . $exception->getMessage(),
                'failed_at' => now()
            ]);

            $this->broadcastBulkTaskUpdate($bulkTask);
        }
    }
}
