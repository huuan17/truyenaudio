<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Story;
use App\Models\Chapter;
use App\Jobs\CrawlStoryJob;
use App\Jobs\SmartCrawlStoryJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use App\Traits\HasToastMessages;
use App\Services\CrawlSchedulingService;

class CrawlMonitorController extends Controller
{
    use HasToastMessages;

    /**
     * Show crawl monitoring dashboard
     */
    public function index()
    {
        // Get crawling stories
        $crawlingStories = Story::where('crawl_status', config('constants.CRAWL_STATUS.VALUES.CRAWLING'))
                                ->with('chapters')
                                ->get();

        // Get stuck stories (no update for 2 hours)
        $stuckStories = Story::where('crawl_status', config('constants.CRAWL_STATUS.VALUES.CRAWLING'))
                            ->where('updated_at', '<', now()->subHours(2))
                            ->with('chapters')
                            ->get();

        // Get recent completed stories
        $recentCompleted = Story::where('crawl_status', config('constants.CRAWL_STATUS.VALUES.CRAWLED'))
                                ->where('updated_at', '>=', now()->subDay())
                                ->with('chapters')
                                ->orderBy('updated_at', 'desc')
                                ->limit(10)
                                ->get();

        // Get detailed queue information
        $crawlJobs = DB::table('jobs')
                      ->where('queue', 'crawl')
                      ->orderBy('available_at', 'asc')
                      ->get()
                      ->map(function($job) {
                          $payload = json_decode($job->payload, true);

                          // Try multiple ways to extract story ID
                          $storyId = null;
                          if (isset($payload['data']['storyId'])) {
                              $storyId = $payload['data']['storyId'];
                          } elseif (isset($payload['data']['command'])) {
                              // For command jobs, extract from command data
                              $commandData = $payload['data']['command'] ?? '';
                              if (preg_match('/storyId["\']?\s*[:=]\s*["\']?(\d+)/', $commandData, $matches)) {
                                  $storyId = (int)$matches[1];
                              }
                          } elseif (isset($payload['displayName'])) {
                              // Extract from displayName if available
                              if (preg_match('/CrawlStoryJob.*?(\d+)/', $payload['displayName'], $matches)) {
                                  $storyId = (int)$matches[1];
                              }
                          }

                          // Try to extract from serialized data
                          if (!$storyId && isset($payload['data'])) {
                              $serializedData = serialize($payload['data']);
                              if (preg_match('/storyId["\']?[;:]\s*i:(\d+)/', $serializedData, $matches)) {
                                  $storyId = (int)$matches[1];
                              }
                          }

                          $story = $storyId ? Story::find($storyId) : null;

                          return (object)[
                              'id' => $job->id,
                              'story_id' => $storyId,
                              'story_title' => $story ? $story->title : "Unknown Story (ID: {$storyId})",
                              'attempts' => $job->attempts,
                              'available_at' => $job->available_at,
                              'created_at' => $job->created_at,
                              'delay_seconds' => max(0, $job->available_at - time()),
                              'priority' => $payload['priority'] ?? 0,
                              'story' => $story,
                              'payload_debug' => $payload // For debugging
                          ];
                      });

        // Get enhanced queue statistics
        $queueStats = CrawlSchedulingService::getQueueStats();
        $queueStats['total_jobs'] = DB::table('jobs')->count();
        $queueStats['failed_jobs'] = DB::table('failed_jobs')->count();

        // Calculate progress for each crawling story
        $crawlingStories->each(function ($story) {
            $progress = $story->getCrawlProgress();
            $story->progress = $progress;
        });

        $stuckStories->each(function ($story) {
            $progress = $story->getCrawlProgress();
            $story->progress = $progress;
        });

        // Get queue schedule for enhanced display
        $queueSchedule = CrawlSchedulingService::getQueueSchedule();

        return view('admin.crawl-monitor.index', compact(
            'crawlingStories',
            'stuckStories',
            'recentCompleted',
            'queueStats',
            'crawlJobs',
            'queueSchedule'
        ));
    }

    /**
     * Get real-time status via AJAX
     */
    public function status()
    {
        $crawlingStories = Story::where('crawl_status', config('constants.CRAWL_STATUS.VALUES.CRAWLING'))
                                ->with('chapters')
                                ->get();

        $data = $crawlingStories->map(function ($story) {
            $progress = $story->getCrawlProgress();
            return [
                'id' => $story->id,
                'title' => $story->title,
                'progress' => $progress,
                'last_update' => $story->updated_at->format('Y-m-d H:i:s'),
                'minutes_since_update' => $story->updated_at->diffInMinutes(now())
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * Show add story to queue form
     */
    public function addStory()
    {
        // Get all stories that are not currently crawling
        $availableStories = Story::whereNotIn('crawl_status', [
            config('constants.CRAWL_STATUS.VALUES.CRAWLING')
        ])->orderBy('title')->get();

        return view('admin.crawl-monitor.add-story', compact('availableStories'));
    }

    /**
     * Recover stuck jobs
     */
    public function recover(Request $request)
    {
        try {
            $storyId = $request->input('story_id');
            
            if ($storyId) {
                // Recover specific story
                $story = Story::findOrFail($storyId);
                $result = $story->updateCrawlStatusSmart();
                
                return $this->toastJsonSuccess("Story '{$story->title}' đã được recovery: {$result}");
            } else {
                // Recover all stuck jobs
                $exitCode = Artisan::call('crawl:monitor', ['action' => 'recover', '--timeout' => 120]);
                
                if ($exitCode === 0) {
                    return $this->toastJsonSuccess('Tất cả stuck jobs đã được recovery thành công!');
                } else {
                    return $this->toastJsonError('Có lỗi xảy ra khi recovery stuck jobs');
                }
            }
        } catch (\Exception $e) {
            Log::error('Crawl recovery failed', ['error' => $e->getMessage()]);
            return $this->toastJsonError('Recovery failed: ' . $e->getMessage());
        }
    }

    /**
     * Force stop a crawl job
     */
    public function stop(Request $request)
    {
        try {
            $storyId = $request->input('story_id');
            $story = Story::findOrFail($storyId);
            
            $story->update([
                'crawl_status' => config('constants.CRAWL_STATUS.VALUES.NOT_CRAWLED'),
                'crawl_job_id' => null
            ]);

            Log::info("Crawl job manually stopped for story: {$story->title}", [
                'story_id' => $story->id,
                'user_id' => auth()->id()
            ]);

            return $this->toastJsonSuccess("Đã dừng crawl job cho '{$story->title}'");
        } catch (\Exception $e) {
            return $this->toastJsonError('Không thể dừng crawl job: ' . $e->getMessage());
        }
    }

    /**
     * Clear all crawl queue
     */
    public function clearQueue()
    {
        try {
            $exitCode = Artisan::call('crawl:manage', ['action' => 'clear']);

            if ($exitCode === 0) {
                return $this->toastJsonSuccess('Đã xóa tất cả jobs trong crawl queue!');
            } else {
                return $this->toastJsonError('Có lỗi xảy ra khi xóa queue');
            }
        } catch (\Exception $e) {
            return $this->toastJsonError('Clear queue failed: ' . $e->getMessage());
        }
    }

    /**
     * Delete specific job from queue
     */
    public function deleteJob(Request $request)
    {
        try {
            $jobId = $request->input('job_id');

            $deleted = DB::table('jobs')->where('id', $jobId)->delete();

            if ($deleted) {
                Log::info("Queue job deleted", ['job_id' => $jobId, 'user_id' => auth()->id()]);
                return $this->toastJsonSuccess('Đã xóa job khỏi queue!');
            } else {
                return $this->toastJsonError('Không tìm thấy job để xóa');
            }
        } catch (\Exception $e) {
            return $this->toastJsonError('Xóa job failed: ' . $e->getMessage());
        }
    }

    /**
     * Prioritize job (move to front of queue)
     */
    public function prioritizeJob(Request $request)
    {
        try {
            $jobId = $request->input('job_id');

            // Set available_at to current time to make it run immediately
            $updated = DB::table('jobs')
                        ->where('id', $jobId)
                        ->update(['available_at' => time()]);

            if ($updated) {
                Log::info("Queue job prioritized", ['job_id' => $jobId, 'user_id' => auth()->id()]);
                return $this->toastJsonSuccess('Job đã được ưu tiên!');
            } else {
                return $this->toastJsonError('Không tìm thấy job để ưu tiên');
            }
        } catch (\Exception $e) {
            return $this->toastJsonError('Ưu tiên job failed: ' . $e->getMessage());
        }
    }

    /**
     * Delay job (move to back of queue)
     */
    public function delayJob(Request $request)
    {
        try {
            $jobId = $request->input('job_id');
            $delayMinutes = $request->input('delay_minutes', 30);

            // Add delay to available_at
            $newAvailableAt = time() + ($delayMinutes * 60);

            $updated = DB::table('jobs')
                        ->where('id', $jobId)
                        ->update(['available_at' => $newAvailableAt]);

            if ($updated) {
                Log::info("Queue job delayed", [
                    'job_id' => $jobId,
                    'delay_minutes' => $delayMinutes,
                    'user_id' => auth()->id()
                ]);
                return $this->toastJsonSuccess("Job đã được delay {$delayMinutes} phút!");
            } else {
                return $this->toastJsonError('Không tìm thấy job để delay');
            }
        } catch (\Exception $e) {
            return $this->toastJsonError('Delay job failed: ' . $e->getMessage());
        }
    }

    /**
     * Get queue details for AJAX
     */
    public function queueDetails()
    {
        try {
            $crawlJobs = DB::table('jobs')
                          ->where('queue', 'crawl')
                          ->orderBy('available_at', 'asc')
                          ->get()
                          ->map(function($job) {
                              $payload = json_decode($job->payload, true);

                              // Extract story ID using same logic as index method
                              $storyId = null;
                              if (isset($payload['data']['storyId'])) {
                                  $storyId = $payload['data']['storyId'];
                              } elseif (isset($payload['data']['command'])) {
                                  $commandData = $payload['data']['command'] ?? '';
                                  if (preg_match('/storyId["\']?\s*[:=]\s*["\']?(\d+)/', $commandData, $matches)) {
                                      $storyId = (int)$matches[1];
                                  }
                              } elseif (isset($payload['displayName'])) {
                                  if (preg_match('/CrawlStoryJob.*?(\d+)/', $payload['displayName'], $matches)) {
                                      $storyId = (int)$matches[1];
                                  }
                              }

                              if (!$storyId && isset($payload['data'])) {
                                  $serializedData = serialize($payload['data']);
                                  if (preg_match('/storyId["\']?[;:]\s*i:(\d+)/', $serializedData, $matches)) {
                                      $storyId = (int)$matches[1];
                                  }
                              }

                              $story = $storyId ? Story::find($storyId) : null;

                              return [
                                  'id' => $job->id,
                                  'story_id' => $storyId,
                                  'story_title' => $story ? $story->title : "Unknown Story (ID: {$storyId})",
                                  'attempts' => $job->attempts,
                                  'available_at' => date('Y-m-d H:i:s', $job->available_at),
                                  'delay_seconds' => max(0, $job->available_at - time()),
                                  'delay_minutes' => max(0, round(($job->available_at - time()) / 60)),
                                  'is_ready' => $job->available_at <= time(),
                                  'created_at' => date('Y-m-d H:i:s', $job->created_at),
                                  'chapter_count' => $story ? ($story->end_chapter - $story->start_chapter + 1) : 0
                              ];
                          });

            return response()->json([
                'success' => true,
                'jobs' => $crawlJobs,
                'stats' => [
                    'total' => $crawlJobs->count(),
                    'ready' => $crawlJobs->where('is_ready', true)->count(),
                    'pending' => $crawlJobs->where('is_ready', false)->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get queue details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rebalance queue to prevent overlaps
     */
    public function rebalanceQueue()
    {
        try {
            $result = CrawlSchedulingService::rebalanceQueue();

            Log::info("Queue rebalanced", [
                'jobs_updated' => $result['jobs_updated'],
                'user_id' => auth()->id()
            ]);

            return $this->toastJsonSuccess($result['message']);
        } catch (\Exception $e) {
            return $this->toastJsonError('Rebalance failed: ' . $e->getMessage());
        }
    }

    /**
     * Update story status based on actual progress
     */
    public function updateStoryStatus()
    {
        try {
            $exitCode = Artisan::call('stories:update-status');
            $output = Artisan::output();

            // Parse output to get summary
            if (preg_match('/Updated: (\d+) stories/', $output, $matches)) {
                $updatedCount = $matches[1];

                Log::info("Story status updated via dashboard", [
                    'updated_count' => $updatedCount,
                    'user_id' => auth()->id()
                ]);

                if ($updatedCount > 0) {
                    return $this->toastJsonSuccess("✅ Đã cập nhật trạng thái cho {$updatedCount} truyện!");
                } else {
                    return $this->toastJsonSuccess("✅ Tất cả truyện đã có trạng thái đúng!");
                }
            }

            return $this->toastJsonSuccess('✅ Kiểm tra trạng thái truyện hoàn thành!');
        } catch (\Exception $e) {
            return $this->toastJsonError('Update status failed: ' . $e->getMessage());
        }
    }

    /**
     * Show add story to queue form
     */
    public function showAddStoryForm()
    {
        // Get stories that can be added to crawl queue
        $availableStories = Story::whereNotIn('crawl_status', [
                config('constants.CRAWL_STATUS.VALUES.CRAWLING')
            ])
            ->orderBy('title')
            ->get();

        // Get stories currently in queue
        $queuedStories = $this->getStoriesInQueue();

        return view('admin.crawl-monitor.add-story', compact('availableStories', 'queuedStories'));
    }

    /**
     * Add story to crawl queue
     */
    public function addStoryToQueue(Request $request)
    {
        $request->validate([
            'story_id' => 'required|exists:stories,id',
            'start_chapter' => 'nullable|integer|min:1',
            'end_chapter' => 'nullable|integer|min:1',
            'delay_minutes' => 'nullable|integer|min:0|max:1440', // Max 24 hours
            'priority' => 'nullable|in:high,normal,low'
        ]);

        try {
            $story = Story::findOrFail($request->story_id);

            // Check if story already has an active crawl job
            if ($this->hasActiveCrawlJob($story)) {
                return $this->toastJsonError('Truyện này đã có job crawl đang chạy hoặc trong queue!');
            }

            // Update story chapter range if provided
            if ($request->start_chapter || $request->end_chapter) {
                $updateData = [];
                if ($request->start_chapter) {
                    $updateData['start_chapter'] = $request->start_chapter;
                }
                if ($request->end_chapter) {
                    $updateData['end_chapter'] = $request->end_chapter;
                }
                $story->update($updateData);
            }

            // Reset story status
            $story->update([
                'crawl_status' => config('constants.CRAWL_STATUS.VALUES.NOT_CRAWLED'),
                'crawl_job_id' => null
            ]);

            // Calculate delay
            $delayMinutes = $request->delay_minutes ?? 0;
            $optimalDelay = CrawlSchedulingService::calculateOptimalDelay($story);
            $finalDelay = max($delayMinutes * 60, $optimalDelay); // Use the larger delay

            // Dispatch smart crawl job with appropriate delay and priority
            $job = SmartCrawlStoryJob::dispatch($story->id);

            if ($finalDelay > 0) {
                $job->delay(now()->addSeconds($finalDelay));
            }

            // Set priority if specified
            if ($request->priority === 'high') {
                $job->onQueue('crawl-high');
            } elseif ($request->priority === 'low') {
                $job->onQueue('crawl-low');
            } else {
                $job->onQueue('crawl');
            }

            Log::info("Story added to smart crawl queue from monitor", [
                'story_id' => $story->id,
                'title' => $story->title,
                'delay_seconds' => $finalDelay,
                'priority' => $request->priority ?? 'normal',
                'user_id' => auth()->id()
            ]);

            $delayText = $finalDelay > 0 ? " (delay: " . gmdate('H:i:s', $finalDelay) . ")" : "";
            $priorityText = $request->priority ? " với priority {$request->priority}" : "";

            return $this->toastJsonSuccess("✅ Đã thêm truyện '{$story->title}' vào smart crawl queue{$delayText}{$priorityText}!");

        } catch (\Exception $e) {
            Log::error("Failed to add story to queue: " . $e->getMessage());
            return $this->toastJsonError('Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Check if story has an active crawl job
     */
    private function hasActiveCrawlJob(Story $story): bool
    {
        // Check if story is currently crawling
        if ($story->crawl_status === config('constants.CRAWL_STATUS.VALUES.CRAWLING')) {
            return true;
        }

        // Check if there are pending crawl jobs for this story in any crawl queue
        $crawlQueues = ['crawl', 'crawl-high', 'crawl-low'];

        foreach ($crawlQueues as $queue) {
            $pendingJobs = DB::table('jobs')
                ->where('queue', $queue)
                ->get();

            foreach ($pendingJobs as $job) {
                $payload = json_decode($job->payload, true);
                $storyId = $payload['data']['storyId'] ?? null;

                if ($storyId == $story->id) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get stories currently in queue
     */
    private function getStoriesInQueue(): array
    {
        $queuedStories = [];
        $crawlQueues = ['crawl', 'crawl-high', 'crawl-low'];

        foreach ($crawlQueues as $queue) {
            $jobs = DB::table('jobs')
                ->where('queue', $queue)
                ->orderBy('created_at')
                ->get();

            foreach ($jobs as $job) {
                $payload = json_decode($job->payload, true);
                $storyId = $payload['data']['storyId'] ?? null;

                if ($storyId) {
                    $story = Story::find($storyId);
                    if ($story) {
                        $queuedStories[] = [
                            'job_id' => $job->id,
                            'story' => $story,
                            'queue' => $queue,
                            'created_at' => $job->created_at,
                            'available_at' => $job->available_at
                        ];
                    }
                }
            }
        }

        return $queuedStories;
    }
}
