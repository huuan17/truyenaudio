<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Story;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class TtsMonitorController extends Controller
{
    /**
     * Display TTS monitoring dashboard
     */
    public function index(Request $request)
    {
        // Get overall statistics
        $stats = $this->getTtsStatistics();

        // Get TTS queue status
        $queueStats = $this->getQueueStatistics();

        // Get pending and running jobs
        $pendingJobs = $this->getPendingJobs();
        $runningJobs = $this->getRunningJobs();

        // Get stories with TTS progress
        $storiesWithTts = $this->getStoriesWithTtsProgress();

        return view('admin.tts-monitor.index', compact(
            'stats', 'queueStats', 'pendingJobs', 'runningJobs', 'storiesWithTts'
        ));
    }

    /**
     * Get TTS statistics
     */
    private function getTtsStatistics()
    {
        return [
            'total' => Chapter::count(),
            'completed' => Chapter::where('audio_status', 'completed')->count(),
            'processing' => Chapter::where('audio_status', 'processing')->count(),
            'pending' => Chapter::where('audio_status', 'pending')->count(),
            'failed' => Chapter::where('audio_status', 'failed')->count(),
            'none' => Chapter::whereNull('audio_status')->orWhere('audio_status', '')->count(),
        ];
    }

    /**
     * Get queue statistics
     */
    private function getQueueStatistics()
    {
        try {
            // Get pending TTS jobs from queue
            $pendingJobs = DB::table('jobs')
                ->where('payload', 'like', '%ProcessChapterTtsJob%')
                ->whereNull('reserved_at')
                ->count();

            // Get running TTS jobs from queue
            $runningJobs = DB::table('jobs')
                ->where('payload', 'like', '%ProcessChapterTtsJob%')
                ->whereNotNull('reserved_at')
                ->count();

            $failedJobs = DB::table('failed_jobs')
                ->where('payload', 'like', '%ProcessChapterTtsJob%')
                ->count();

            return [
                'pending_jobs' => $pendingJobs,
                'running_jobs' => $runningJobs,
                'failed_jobs' => $failedJobs,
                'queue_size' => $pendingJobs + $runningJobs + $failedJobs,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting queue statistics: ' . $e->getMessage());
            return [
                'pending_jobs' => 0,
                'running_jobs' => 0,
                'failed_jobs' => 0,
                'queue_size' => 0,
            ];
        }
    }

    /**
     * Get pending jobs with details
     */
    private function getPendingJobs()
    {
        try {
            $pendingJobs = DB::table('jobs')
                ->where('payload', 'like', '%ProcessChapterTtsJob%')
                ->whereNull('reserved_at')
                ->orderBy('created_at', 'asc')
                ->limit(10)
                ->get()
                ->map(function($job) {
                    $payload = json_decode($job->payload, true);
                    $chapterId = null;

                    // Extract chapter ID from serialized command object
                    if (isset($payload['data']['command'])) {
                        $command = $payload['data']['command'];

                        // Parse the serialized object to extract chapterId
                        if (preg_match('/\*chapterId";i:(\d+);/', $command, $matches)) {
                            $chapterId = (int)$matches[1];
                        } elseif (preg_match('/"chapterId";i:(\d+);/', $command, $matches)) {
                            $chapterId = (int)$matches[1];
                        } elseif (preg_match('/chapterId";i:(\d+);/', $command, $matches)) {
                            $chapterId = (int)$matches[1];
                        }


                    }

                    // Get chapter and story info
                    $chapter = null;
                    $story = null;
                    if ($chapterId) {
                        $chapter = Chapter::with('story')->find($chapterId);
                        if ($chapter) {
                            $story = $chapter->story;
                        }
                    }

                    return (object)[
                        'id' => $job->id,
                        'chapter_id' => $chapterId,
                        'chapter_title' => $chapter ? $chapter->title : 'N/A',
                        'story_name' => $story ? $story->title : 'N/A',
                        'created_at' => \Carbon\Carbon::parse($job->created_at),
                        'attempts' => $job->attempts,
                    ];
                });

            return $pendingJobs;
        } catch (\Exception $e) {
            Log::error('Error getting pending jobs: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get running jobs with details
     */
    private function getRunningJobs()
    {
        try {
            $runningJobs = DB::table('jobs')
                ->where('payload', 'like', '%ProcessChapterTtsJob%')
                ->whereNotNull('reserved_at')
                ->orderBy('reserved_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function($job) {
                    $payload = json_decode($job->payload, true);
                    $chapterId = null;

                    // Extract chapter ID from serialized command object
                    if (isset($payload['data']['command'])) {
                        $command = $payload['data']['command'];
                        // Parse the serialized object to extract chapterId
                        if (preg_match('/\*chapterId";i:(\d+);/', $command, $matches)) {
                            $chapterId = (int)$matches[1];
                        } elseif (preg_match('/"chapterId";i:(\d+);/', $command, $matches)) {
                            $chapterId = (int)$matches[1];
                        } elseif (preg_match('/chapterId";i:(\d+);/', $command, $matches)) {
                            $chapterId = (int)$matches[1];
                        }
                    }

                    // Get chapter and story info
                    $chapter = null;
                    $story = null;
                    if ($chapterId) {
                        $chapter = Chapter::with('story')->find($chapterId);
                        if ($chapter) {
                            $story = $chapter->story;
                        }
                    }

                    return (object)[
                        'id' => $job->id,
                        'chapter_id' => $chapterId,
                        'chapter_title' => $chapter ? $chapter->title : 'N/A',
                        'story_name' => $story ? $story->title : 'N/A',
                        'reserved_at' => $job->reserved_at,
                        'attempts' => $job->attempts,
                    ];
                });

            return $runningJobs;
        } catch (\Exception $e) {
            Log::error('Error getting running jobs: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Test method to debug job parsing
     */
    public function testJobParsing()
    {
        $job = DB::table('jobs')->where('payload', 'like', '%ProcessChapterTtsJob%')->first();

        if (!$job) {
            return response()->json(['error' => 'No TTS jobs found']);
        }

        $payload = json_decode($job->payload, true);
        $chapterId = null;

        // Extract chapter ID from serialized command object
        if (isset($payload['data']['command'])) {
            $command = $payload['data']['command'];

            // Parse the serialized object to extract chapterId
            if (preg_match('/s:12:"\*chapterId";i:(\d+);/', $command, $matches)) {
                $chapterId = (int)$matches[1];
            }
        }

        // Get chapter and story info
        $chapter = null;
        $story = null;
        if ($chapterId) {
            $chapter = Chapter::with('story')->find($chapterId);
            if ($chapter) {
                $story = $chapter->story;
            }
        }

        return response()->json([
            'job_id' => $job->id,
            'payload_keys' => array_keys($payload),
            'data_keys' => isset($payload['data']) ? array_keys($payload['data']) : null,
            'command_preview' => isset($payload['data']['command']) ? substr($payload['data']['command'], 0, 200) : null,
            'chapter_id' => $chapterId,
            'chapter_title' => $chapter ? $chapter->title : null,
            'story_title' => $story ? $story->title : null,
        ]);
    }

    /**
     * Get real-time status updates
     */
    public function status(Request $request)
    {
        $stats = $this->getTtsStatistics();
        $queueStats = $this->getQueueStatistics();

        // Get pending and running jobs
        $pendingJobs = $this->getPendingJobs();
        $runningJobs = $this->getRunningJobs();

        // Get recent activity (last 10 updates) - using available columns
        $recentActivity = Chapter::with('story')
            ->where('audio_status', '!=', 'pending')
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($chapter) {
                return [
                    'id' => $chapter->id,
                    'story_title' => $chapter->story->title ?? 'Unknown Story',
                    'chapter_title' => $chapter->title ?? "Chapter {$chapter->chapter_number}",
                    'chapter_number' => $chapter->chapter_number,
                    'status' => $chapter->audio_status,
                    'updated_at' => $chapter->updated_at->diffForHumans(),
                    'progress' => $chapter->audio_status === 'done' ? 100 :
                                 ($chapter->audio_status === 'processing' ? 50 : 0),
                ];
            });

        return response()->json([
            'stats' => $stats,
            'queue' => $queueStats,
            'pending_jobs' => $pendingJobs,
            'running_jobs' => $runningJobs,
            'recent_activity' => $recentActivity,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Cancel TTS for specific chapter
     */
    public function cancelChapter(Request $request, Chapter $chapter)
    {
        try {
            // Update chapter status
            $chapter->update([
                'audio_status' => 'pending'
            ]);

            // Try to remove from queue (if possible)
            $this->removeChapterFromQueue($chapter);

            Log::info("TTS cancelled for chapter {$chapter->id} by user " . auth()->id());

            return response()->json([
                'success' => true,
                'message' => "Đã hủy TTS cho chapter {$chapter->chapter_number}"
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to cancel TTS for chapter {$chapter->id}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi hủy TTS: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retry TTS for failed chapter
     */
    public function retryChapter(Request $request, Chapter $chapter)
    {
        try {
            // Reset chapter status
            $chapter->update([
                'audio_status' => 'pending'
            ]);

            // Get story default settings
            $story = $chapter->story;
            $voice = $story->default_tts_voice ?? 'hn_female_ngochuyen_full_48k-fhg';
            $bitrate = $story->default_tts_bitrate ?? 128;
            $speed = $story->default_tts_speed ?? 1.0;
            $volume = $story->default_tts_volume ?? 1.0;

            // Queue TTS job
            \App\Jobs\ProcessChapterTtsJob::dispatch($chapter->id, $voice, $bitrate, $speed, $volume);

            Log::info("TTS retried for chapter {$chapter->id} by user " . auth()->id());

            return response()->json([
                'success' => true,
                'message' => "Đã thử lại TTS cho chapter {$chapter->chapter_number}"
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to retry TTS for chapter {$chapter->id}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi thử lại TTS: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear all failed TTS jobs
     */
    public function clearFailed(Request $request)
    {
        try {
            // Reset failed chapters
            $count = Chapter::where('audio_status', 'failed')->count();
            
            Chapter::where('audio_status', 'error')->update([
                'audio_status' => 'pending'
            ]);

            // Clear failed jobs from queue
            DB::table('failed_jobs')
                ->where('payload', 'like', '%ProcessChapterTtsJob%')
                ->delete();

            Log::info("Cleared {$count} failed TTS jobs by user " . auth()->id());

            return response()->json([
                'success' => true,
                'message' => "Đã xóa {$count} TTS jobs thất bại"
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to clear failed TTS jobs: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa failed jobs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove chapter from queue (helper method)
     */
    private function removeChapterFromQueue(Chapter $chapter)
    {
        try {
            // This is a simplified approach - in production you might need more sophisticated queue management
            DB::table('jobs')
                ->where('payload', 'like', "%chapter_id.*{$chapter->id}%")
                ->delete();
        } catch (\Exception $e) {
            Log::warning("Could not remove chapter {$chapter->id} from queue: " . $e->getMessage());
        }
    }

    /**
     * Add story to TTS queue
     */
    public function addStory(Request $request)
    {
        $request->validate([
            'story_id' => 'required|exists:stories,id',
            'voice' => 'required|string',
            'bitrate' => 'required|integer|in:128,192,256',
            'speed' => 'required|numeric|in:0.5,1.0,1.5,2.0',
            'volume' => 'required|numeric|in:1.0,1.5,2.0',
            'conversion_type' => 'required|in:pending_only,all,range,specific',
            'from_chapter' => 'required_if:conversion_type,range,specific|integer|min:1',
            'to_chapter' => 'required_if:conversion_type,range|integer|min:1'
        ]);

        try {
            $story = Story::findOrFail($request->story_id);
            $conversionType = $request->conversion_type;

            // Get chapters to process based on conversion type
            $chaptersQuery = $story->chapters()->orderBy('chapter_number');

            switch ($conversionType) {
                case 'pending_only':
                    // Only chapters without audio
                    $chaptersQuery->where('audio_status', 'pending');
                    break;

                case 'all':
                    // All chapters (will skip existing audio in processing)
                    break;

                case 'range':
                    $fromChapter = $request->from_chapter;
                    $toChapter = $request->to_chapter;
                    $chaptersQuery->whereBetween('chapter_number', [$fromChapter, $toChapter]);
                    break;

                case 'specific':
                    $chapterNumber = $request->from_chapter;
                    $chaptersQuery->where('chapter_number', $chapterNumber);
                    break;
            }

            $chapters = $chaptersQuery->get();

            if ($chapters->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có chapter nào cần xử lý TTS'
                ], 400);
            }

            // Filter out chapters that already have audio (except for 'all' type which forces reprocess)
            $chaptersToProcess = $chapters->filter(function($chapter) use ($conversionType) {
                if ($conversionType === 'all') {
                    // For 'all' type, still skip chapters that already have audio to avoid waste
                    return $chapter->audio_status !== 'done';
                }
                // For other types, skip chapters with audio
                return $chapter->audio_status !== 'done';
            });

            if ($chaptersToProcess->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tất cả các chapter đã có audio rồi'
                ], 400);
            }

            // Dispatch TTS jobs for each chapter
            $successCount = 0;
            $skippedCount = 0;

            foreach ($chaptersToProcess as $chapter) {
                try {
                    // Double check if chapter has readable content
                    if (!$chapter->hasReadableContent()) {
                        $skippedCount++;
                        Log::warning("Chapter {$chapter->id} skipped - no readable content");
                        continue;
                    }

                    \App\Jobs\ProcessChapterTtsJob::dispatch(
                        $chapter->id,
                        $request->voice,
                        $request->bitrate,
                        $request->speed,
                        $request->volume
                    );
                    $successCount++;
                } catch (\Exception $e) {
                    Log::error("Failed to dispatch TTS job for chapter {$chapter->id}: " . $e->getMessage());
                    $skippedCount++;
                }
            }

            Log::info("Added {$successCount} chapters of story '{$story->title}' to TTS queue by user " . auth()->id());

            $message = "Đã thêm {$successCount} chapter(s) của truyện '{$story->title}' vào queue TTS";
            if ($skippedCount > 0) {
                $message .= ". Bỏ qua {$skippedCount} chapter(s) (đã có audio hoặc không có nội dung)";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'chapters_count' => $successCount,
                'skipped_count' => $skippedCount
            ]);

        } catch (\Exception $e) {
            Log::error('Error adding story to TTS: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi thêm truyện vào TTS: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get stories with TTS progress information
     */
    private function getStoriesWithTtsProgress()
    {
        return Story::select([
                'stories.id',
                'stories.title',
                'stories.slug',
                'stories.author',
                'stories.folder_name',
                'stories.created_at',
                DB::raw('COUNT(chapters.id) as total_chapters'),
                DB::raw('COUNT(CASE WHEN chapters.audio_status = "completed" THEN 1 END) as completed_chapters'),
                DB::raw('COUNT(CASE WHEN chapters.audio_status = "processing" THEN 1 END) as processing_chapters'),
                DB::raw('COUNT(CASE WHEN chapters.audio_status = "pending" THEN 1 END) as pending_chapters'),
                DB::raw('COUNT(CASE WHEN chapters.audio_status = "error" THEN 1 END) as failed_chapters'),
                DB::raw('COUNT(CASE WHEN chapters.audio_status IS NULL OR chapters.audio_status = "" THEN 1 END) as none_chapters'),
                DB::raw('MAX(chapters.updated_at) as last_tts_activity'),
                DB::raw('COUNT(CASE WHEN chapters.audio_status = "done" THEN 1 END) as audio_files_count')
            ])
            ->leftJoin('chapters', 'stories.id', '=', 'chapters.story_id')
            ->groupBy('stories.id', 'stories.title', 'stories.slug', 'stories.author', 'stories.folder_name', 'stories.created_at')
            ->having('total_chapters', '>', 0)
            ->orderByDesc('last_tts_activity')
            ->orderByDesc('completed_chapters')
            ->paginate(20);
    }
}
