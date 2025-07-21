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
                $task->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'progress' => 100,
                    'result' => [
                        'success' => true,
                        'video_path' => $this->parameters['--output'] ?? null,
                        'message' => 'Video generated successfully',
                        'platform' => $this->platform
                    ]
                ]);

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
        // Add task ID for progress tracking
        $this->parameters['--task-id'] = $this->taskId;
        
        return Artisan::call('video:generate', $this->parameters);
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
