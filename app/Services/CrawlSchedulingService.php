<?php

namespace App\Services;

use App\Models\Story;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CrawlSchedulingService
{
    /**
     * Crawl speed: 2 seconds per chapter
     */
    const SECONDS_PER_CHAPTER = 2;
    
    /**
     * Buffer time between jobs (in seconds)
     */
    const BUFFER_TIME = 30;

    /**
     * Calculate estimated crawl duration for a story
     */
    public static function calculateCrawlDuration(Story $story): int
    {
        $chapterCount = $story->end_chapter - $story->start_chapter + 1;
        return $chapterCount * self::SECONDS_PER_CHAPTER;
    }

    /**
     * Calculate optimal delay for a new crawl job
     */
    public static function calculateOptimalDelay(Story $story): int
    {
        // Get all pending crawl jobs ordered by available_at
        $pendingJobs = DB::table('jobs')
                        ->where('queue', 'crawl')
                        ->where('available_at', '>', time())
                        ->orderBy('available_at', 'desc')
                        ->get();

        if ($pendingJobs->isEmpty()) {
            // No pending jobs, can start immediately
            return 0;
        }

        // Get the last job's completion time
        $lastJob = $pendingJobs->first();
        $lastJobStartTime = $lastJob->available_at;
        
        // Try to extract story info from last job to estimate its duration
        $lastJobDuration = self::estimateJobDuration($lastJob);
        
        // Calculate when the last job will finish
        $lastJobEndTime = $lastJobStartTime + $lastJobDuration;
        
        // Add buffer time and return delay from now
        $optimalStartTime = $lastJobEndTime + self::BUFFER_TIME;
        
        return max(0, $optimalStartTime - time());
    }

    /**
     * Estimate duration of an existing job
     */
    private static function estimateJobDuration($job): int
    {
        try {
            $payload = json_decode($job->payload, true);
            
            // Extract story ID from payload
            $storyId = null;
            if (isset($payload['data']['storyId'])) {
                $storyId = $payload['data']['storyId'];
            } elseif (isset($payload['data']['command'])) {
                $commandData = $payload['data']['command'] ?? '';
                if (preg_match('/storyId["\']?\s*[:=]\s*["\']?(\d+)/', $commandData, $matches)) {
                    $storyId = (int)$matches[1];
                }
            }
            
            if ($storyId) {
                $story = Story::find($storyId);
                if ($story) {
                    return self::calculateCrawlDuration($story);
                }
            }
            
            // Default duration if can't determine
            return 3600; // 1 hour default
            
        } catch (\Exception $e) {
            Log::warning("Failed to estimate job duration", [
                'job_id' => $job->id,
                'error' => $e->getMessage()
            ]);
            return 3600; // 1 hour default
        }
    }

    /**
     * Get queue scheduling information
     */
    public static function getQueueSchedule(): array
    {
        $pendingJobs = DB::table('jobs')
                        ->where('queue', 'crawl')
                        ->orderBy('available_at', 'asc')
                        ->get();

        $schedule = [];
        $currentTime = time();
        
        foreach ($pendingJobs as $job) {
            $payload = json_decode($job->payload, true);
            $storyId = null;
            
            // Extract story ID
            if (isset($payload['data']['storyId'])) {
                $storyId = $payload['data']['storyId'];
            } elseif (isset($payload['data']['command'])) {
                $commandData = $payload['data']['command'] ?? '';
                if (preg_match('/storyId["\']?\s*[:=]\s*["\']?(\d+)/', $commandData, $matches)) {
                    $storyId = (int)$matches[1];
                }
            }
            
            $story = $storyId ? Story::find($storyId) : null;
            $duration = $story ? self::calculateCrawlDuration($story) : 3600;
            
            $schedule[] = [
                'job_id' => $job->id,
                'story_id' => $storyId,
                'story_title' => $story ? $story->title : 'Unknown',
                'chapter_count' => $story ? ($story->end_chapter - $story->start_chapter + 1) : 0,
                'start_time' => $job->available_at,
                'estimated_duration' => $duration,
                'estimated_end_time' => $job->available_at + $duration,
                'delay_from_now' => max(0, $job->available_at - $currentTime),
                'is_ready' => $job->available_at <= $currentTime
            ];
        }
        
        return $schedule;
    }

    /**
     * Rebalance queue delays to prevent overlaps
     */
    public static function rebalanceQueue(): array
    {
        $jobs = DB::table('jobs')
                 ->where('queue', 'crawl')
                 ->orderBy('available_at', 'asc')
                 ->get();

        if ($jobs->count() <= 1) {
            return ['message' => 'No rebalancing needed', 'jobs_updated' => 0];
        }

        $updatedJobs = 0;
        $currentTime = time();
        $nextAvailableTime = $currentTime;

        foreach ($jobs as $job) {
            $estimatedDuration = self::estimateJobDuration($job);
            
            // If job is scheduled before next available time, update it
            if ($job->available_at < $nextAvailableTime) {
                DB::table('jobs')
                  ->where('id', $job->id)
                  ->update(['available_at' => $nextAvailableTime]);
                
                $updatedJobs++;
                
                Log::info("Rebalanced job", [
                    'job_id' => $job->id,
                    'old_time' => date('Y-m-d H:i:s', $job->available_at),
                    'new_time' => date('Y-m-d H:i:s', $nextAvailableTime)
                ]);
            }
            
            // Calculate next available time
            $jobStartTime = max($job->available_at, $nextAvailableTime);
            $nextAvailableTime = $jobStartTime + $estimatedDuration + self::BUFFER_TIME;
        }

        return [
            'message' => "Rebalanced {$updatedJobs} jobs",
            'jobs_updated' => $updatedJobs,
            'next_available_slot' => date('Y-m-d H:i:s', $nextAvailableTime)
        ];
    }

    /**
     * Format duration for display
     */
    public static function formatDuration(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%dh %dm %ds', $hours, $minutes, $secs);
        } elseif ($minutes > 0) {
            return sprintf('%dm %ds', $minutes, $secs);
        } else {
            return sprintf('%ds', $secs);
        }
    }

    /**
     * Get queue statistics
     */
    public static function getQueueStats(): array
    {
        $schedule = self::getQueueSchedule();
        
        $totalJobs = count($schedule);
        $readyJobs = count(array_filter($schedule, fn($job) => $job['is_ready']));
        $pendingJobs = $totalJobs - $readyJobs;
        
        $totalChapters = array_sum(array_column($schedule, 'chapter_count'));
        $totalEstimatedTime = array_sum(array_column($schedule, 'estimated_duration'));
        
        $nextAvailableSlot = $totalJobs > 0 ? 
            max(array_column($schedule, 'estimated_end_time')) : time();

        return [
            'total_jobs' => $totalJobs,
            'ready_jobs' => $readyJobs,
            'pending_jobs' => $pendingJobs,
            'total_chapters' => $totalChapters,
            'total_estimated_time' => $totalEstimatedTime,
            'total_estimated_time_formatted' => self::formatDuration($totalEstimatedTime),
            'next_available_slot' => $nextAvailableSlot,
            'next_available_slot_formatted' => date('Y-m-d H:i:s', $nextAvailableSlot),
            'queue_end_time' => date('Y-m-d H:i:s', $nextAvailableSlot)
        ];
    }
}
