<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Models\VideoGenerationTask;
use App\Models\GeneratedVideo;

class GenerateUniversalVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $taskId;
    protected $platform;
    protected $parameters;
    protected $tempDir;

    /**
     * Job timeout in seconds (30 minutes)
     */
    public $timeout = 1800;

    /**
     * Number of times the job may be attempted
     */
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct($taskId, $platform, $parameters, $tempDir)
    {
        $this->taskId = $taskId;
        $this->platform = $platform;
        $this->parameters = $parameters;
        $this->tempDir = $tempDir;
        
        // Set queue to 'video' for dedicated video processing
        $this->onQueue('video');
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $task = VideoGenerationTask::find($this->taskId);
        
        if (!$task) {
            Log::error("Video generation task not found: {$this->taskId}");
            return;
        }

        try {
            // Update task status to processing
            $task->update([
                'status' => 'processing',
                'started_at' => now(),
                'progress' => 0
            ]);

            Log::info("Starting universal video generation for task {$this->taskId}", [
                'platform' => $this->platform,
                'parameters' => $this->parameters
            ]);

            // Call universal video generation command
            $exitCode = $this->executeVideoGeneration();

            if ($exitCode === 0) {
                // Success
                $videoPath = $this->parameters['--output'] ?? null;
                $fullVideoPath = storage_path('app/videos/' . $videoPath);

                $task->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'progress' => 100,
                    'result' => [
                        'success' => true,
                        'video_path' => $videoPath,
                        'message' => 'Video generated successfully',
                        'platform' => $this->platform
                    ]
                ]);

                // Create GeneratedVideo record for video management
                Log::info("🔥🔥🔥 FORCE OVERRIDE: Checking GeneratedVideo creation", [
                    'video_path' => $videoPath,
                    'full_video_path' => $fullVideoPath,
                    'file_exists' => File::exists($fullVideoPath),
                    'storage_path_check' => storage_path('app/videos/' . $videoPath),
                    'file_exists_storage_path' => File::exists(storage_path('app/videos/' . $videoPath))
                ]);

                if ($videoPath) {
                    // Try both paths to find the file
                    $actualVideoPath = null;
                    if (File::exists($fullVideoPath)) {
                        $actualVideoPath = $fullVideoPath;
                    } elseif (File::exists(storage_path('app/videos/' . $videoPath))) {
                        $actualVideoPath = storage_path('app/videos/' . $videoPath);
                    }

                    if ($actualVideoPath) {
                        Log::info("🔥🔥🔥 FORCE OVERRIDE: Found video file, creating GeneratedVideo record", [
                            'actual_video_path' => $actualVideoPath
                        ]);
                        $this->createGeneratedVideoRecord($task, $videoPath, $actualVideoPath);
                    } else {
                        Log::warning("🔥🔥🔥 FORCE OVERRIDE: GeneratedVideo not created - file not found", [
                            'video_path' => $videoPath,
                            'checked_paths' => [
                                $fullVideoPath,
                                storage_path('app/videos/' . $videoPath)
                            ]
                        ]);
                    }
                } else {
                    Log::warning("🔥🔥🔥 FORCE OVERRIDE: GeneratedVideo not created - no video path", [
                        'parameters' => $this->parameters
                    ]);
                }

                Log::info("Universal video generation completed successfully for task {$this->taskId}");

            } else {
                // Failed
                $output = Artisan::output();
                $task->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                    'result' => [
                        'success' => false,
                        'error' => "Command failed with exit code {$exitCode}",
                        'output' => $output,
                        'platform' => $this->platform
                    ]
                ]);

                Log::error("Universal video generation failed for task {$this->taskId}", [
                    'exit_code' => $exitCode,
                    'output' => $output,
                    'platform' => $this->platform
                ]);
            }

        } catch (\Exception $e) {
            // Handle exceptions
            $task->update([
                'status' => 'failed',
                'completed_at' => now(),
                'result' => [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'platform' => $this->platform
                ]
            ]);

            Log::error("Universal video generation exception for task {$this->taskId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'platform' => $this->platform
            ]);

            throw $e; // Re-throw to trigger retry mechanism
        } finally {
            // Clean up temp directory
            $this->cleanupTempDirectory();
        }
    }

    /**
     * Execute universal video generation command
     */
    private function executeVideoGeneration()
    {
        // Add required parameters
        $this->parameters['--platform'] = $this->platform;
        $this->parameters['--task-id'] = $this->taskId;

        return Artisan::call('video:generate', $this->parameters);
    }

    /**
     * Create GeneratedVideo record for video management
     */
    private function createGeneratedVideoRecord($task, $videoPath, $fullVideoPath)
    {
        try {
            // Get file info
            $fileSize = File::exists($fullVideoPath) ? File::size($fullVideoPath) : null;
            $fileName = basename($videoPath);

            // Extract title from parameters or use filename
            $title = $this->extractVideoTitle();

            // Create GeneratedVideo record
            $generatedVideo = GeneratedVideo::create([
                'title' => $title,
                'description' => $this->extractVideoDescription(),
                'platform' => $this->platform,
                'media_type' => $this->parameters['--media-type'] ?? 'mixed',
                'file_path' => 'videos/' . $videoPath,
                'file_name' => $fileName,
                'file_size' => $fileSize,
                'duration' => $this->extractVideoDuration(),
                'metadata' => [
                    'generation_parameters' => $this->parameters,
                    'platform' => $this->platform,
                    'created_via' => 'template_generation',
                    'subtitle_text' => $this->parameters['--subtitle-text'] ?? null,
                    'slide_duration' => $this->parameters['--slide-duration'] ?? null,
                    'media_type' => $this->parameters['--media-type'] ?? null
                ],
                'status' => 'generated',
                'task_id' => $task->id
            ]);

            Log::info("Created GeneratedVideo record for video management", [
                'generated_video_id' => $generatedVideo->id,
                'task_id' => $task->id,
                'file_path' => $generatedVideo->file_path,
                'title' => $title
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to create GeneratedVideo record", [
                'task_id' => $task->id,
                'video_path' => $videoPath,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Extract video title from parameters
     */
    private function extractVideoTitle()
    {
        // Try to get title from subtitle text (first 50 chars)
        if (!empty($this->parameters['--subtitle-text'])) {
            $title = substr($this->parameters['--subtitle-text'], 0, 50);
            if (strlen($this->parameters['--subtitle-text']) > 50) {
                $title .= '...';
            }
            return $title;
        }

        // Fallback to filename
        $filename = $this->parameters['--output'] ?? 'Generated Video';
        return str_replace('.mp4', '', $filename);
    }

    /**
     * Extract video description from parameters
     */
    private function extractVideoDescription()
    {
        $description = "Video được tạo tự động từ template.\n\n";

        if (!empty($this->parameters['--subtitle-text'])) {
            $description .= "Nội dung phụ đề:\n" . $this->parameters['--subtitle-text'] . "\n\n";
        }

        $description .= "Platform: " . ucfirst($this->platform) . "\n";
        $description .= "Media type: " . ($this->parameters['--media-type'] ?? 'mixed') . "\n";
        $description .= "Thời gian tạo: " . now()->format('d/m/Y H:i:s');

        return $description;
    }

    /**
     * Extract video duration from parameters
     */
    private function extractVideoDuration()
    {
        // Try to get duration from parameters
        if (!empty($this->parameters['--custom-duration'])) {
            return (int) $this->parameters['--custom-duration'];
        }

        if (!empty($this->parameters['--slide-duration'])) {
            return (int) $this->parameters['--slide-duration'];
        }

        // Default duration
        return 30;
    }

    /**
     * Clean up temporary directory
     */
    private function cleanupTempDirectory()
    {
        try {
            if ($this->tempDir && File::isDirectory($this->tempDir)) {
                File::deleteDirectory($this->tempDir);
                Log::info("Cleaned up temp directory: {$this->tempDir}");
            }
        } catch (\Exception $e) {
            Log::warning("Failed to clean up temp directory: {$this->tempDir}", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception)
    {
        $task = VideoGenerationTask::find($this->taskId);
        
        if ($task) {
            $task->update([
                'status' => 'failed',
                'completed_at' => now(),
                'result' => [
                    'success' => false,
                    'error' => 'Job failed after maximum retries',
                    'exception' => $exception->getMessage(),
                    'platform' => $this->platform
                ]
            ]);
        }

        Log::error("Universal video generation job failed permanently for task {$this->taskId}", [
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'platform' => $this->platform
        ]);

        // Clean up temp directory
        $this->cleanupTempDirectory();
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff()
    {
        return [30, 60, 120]; // Wait 30s, 1min, 2min between retries
    }
}
