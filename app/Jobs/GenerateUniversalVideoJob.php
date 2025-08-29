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
use App\Services\VideoPublishingService;

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

        // Debug log constructor
        Log::info('🔥🔥🔥 FORCE OVERRIDE: Job constructor called', [
            'task_id' => $taskId,
            'platform' => $platform,
            'temp_dir' => $tempDir,
            'temp_dir_type' => gettype($tempDir),
            'temp_dir_empty' => empty($tempDir)
        ]);

        // Set queue to 'default' for now (video queue has connection issues)
        // $this->onConnection('database_video')->onQueue('video');
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
                $fullVideoPath = storage_path('app/videos/generated/' . $videoPath);

                $task->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'progress' => 100,
                    'result' => [
                        'success' => true,
                        'video_path' => 'generated/' . $videoPath,
                        'message' => 'Video generated successfully',
                        'platform' => $this->platform
                    ]
                ]);

                // Create GeneratedVideo record for video management
                Log::info("🔥🔥🔥 FORCE OVERRIDE: Checking GeneratedVideo creation", [
                    'video_path' => $videoPath,
                    'full_video_path' => $fullVideoPath,
                    'file_exists' => File::exists($fullVideoPath),
                    'storage_path_check' => storage_path('app/videos/generated/' . $videoPath),
                    'file_exists_storage_path' => File::exists(storage_path('app/videos/generated/' . $videoPath))
                ]);

                if ($videoPath) {
                    // Try multiple paths to find the video file
                    $possiblePaths = [
                        $fullVideoPath,
                        storage_path('app/videos/generated/' . $videoPath),
                        storage_path('app/videos/generated/' . basename($videoPath)),
                        storage_path('app/' . $videoPath),
                        $videoPath // In case it's already absolute path
                    ];

                    $actualVideoPath = null;
                    foreach ($possiblePaths as $path) {
                        if (File::exists($path)) {
                            $actualVideoPath = $path;
                            break;
                        }
                    }

                    if ($actualVideoPath) {
                        // Normalize the video path for database storage
                        $relativeVideoPath = str_replace(storage_path('app/'), '', $actualVideoPath);

                        Log::info("🔥🔥🔥 FORCE OVERRIDE: Found video file, creating GeneratedVideo record", [
                            'actual_video_path' => $actualVideoPath,
                            'relative_path' => $relativeVideoPath,
                            'original_video_path' => $videoPath
                        ]);

                        $this->createGeneratedVideoRecord($task, $relativeVideoPath, $actualVideoPath);
                    } else {
                        Log::warning("🔥🔥🔥 FORCE OVERRIDE: GeneratedVideo not created - file not found", [
                            'original_video_path' => $videoPath,
                            'checked_paths' => $possiblePaths
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
        $this->parameters['--temp-dir'] = $this->tempDir;


        // Whitelist only supported console options to avoid InvalidOptionException
        $originalParameters = $this->parameters;
        $allowedKeys = [
            '--platform','--output','--temp-dir','--task-id',
            '--voice','--bitrate','--speed','--volume','--script',
            '--media-type','--library-audio-id','--background-audio-id','--background-audio-volume',
            '--enable-logo','--logo-source','--selected-logo','--logo-file','--logo-position','--logo-size','--logo-width','--logo-height','--logo-opacity','--logo-margin','--logo-duration','--logo-start-time','--logo-end-time',
            '--slide-duration','--slide-transition','--images','--image-durations','--image-transitions','--image-order-mapping',
            '--product-images','--product-video','--mixed-mode','--sequence-strategy',
            '--image-display-duration','--image-distribution-mode','--image-timings',
            '--enable-subtitle','--subtitle-text','--subtitle-position','--subtitle-size','--subtitle-color','--subtitle-background','--subtitle-font','--subtitle-duration','--subtitle-timing-mode','--subtitle-per-image','--words-per-image','--subtitle-delay','--subtitle-fade',
            '--duration-based-on','--custom-duration','--image-duration','--sync-with-audio','--max-duration','--resolution','--fps','--quality',
        ];
        $this->parameters = array_intersect_key($this->parameters, array_flip($allowedKeys));
        $removedKeys = array_diff_key($originalParameters, $this->parameters);
        if (!empty($removedKeys)) {
            Log::info('GenerateUniversalVideoJob: Removed unsupported CLI options', [
                'removed_keys' => array_keys($removedKeys)
            ]);
        }

        // Validate and sanitize parameters to prevent string offset errors
        $sanitizedParameters = [];
        foreach ($this->parameters as $key => $value) {
            // Ensure all parameters are strings or null
            if (is_array($value)) {
                $sanitizedParameters[$key] = json_encode($value);
                Log::warning('🔥🔥🔥 FORCE OVERRIDE: Converting array parameter to JSON', [
                    'key' => $key,
                    'original_value' => $value,
                    'json_value' => json_encode($value)
                ]);
            } elseif (is_object($value)) {
                $sanitizedParameters[$key] = json_encode($value);
                Log::warning('🔥🔥🔥 FORCE OVERRIDE: Converting object parameter to JSON', [
                    'key' => $key,
                    'object_class' => get_class($value)
                ]);
            } elseif (is_bool($value)) {
                // Handle boolean flags properly for Laravel console commands
                if ($value === true) {
                    // For flag options like --enable-logo, --enable-subtitle, use null to indicate presence
                    if (in_array($key, ['--enable-logo', '--enable-subtitle'])) {
                        $sanitizedParameters[$key] = null; // Laravel treats null as flag presence
                    } else {
                        $sanitizedParameters[$key] = '1';
                    }
                } else {
                    // For false values, don't include the parameter at all for flags
                    if (!in_array($key, ['--enable-logo', '--enable-subtitle'])) {
                        $sanitizedParameters[$key] = '0';
                    }
                    // Skip false flag parameters entirely
                }
            } elseif (is_numeric($value)) {
                $sanitizedParameters[$key] = (string) $value;
            } elseif (is_null($value)) {
                $sanitizedParameters[$key] = '';
            } else {
                $sanitizedParameters[$key] = (string) $value;
            }
        }

        // Debug log parameters
        Log::info('🔥🔥🔥 FORCE OVERRIDE: Job calling video:generate with sanitized parameters', [
            'original_parameters' => $this->parameters,
            'sanitized_parameters' => $sanitizedParameters,
            'platform' => $this->platform,
            'task_id' => $this->taskId,
            'temp_dir' => $this->tempDir,
            'logo_enabled' => isset($sanitizedParameters['--enable-logo']),
            'subtitle_enabled' => isset($sanitizedParameters['--enable-subtitle'])
        ]);

        return Artisan::call('video:generate', $sanitizedParameters);
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

            // Normalize file path for database storage
            // Path should be relative to storage/app/
            $normalizedPath = $videoPath;

            // Remove any duplicate videos/ prefix
            $normalizedPath = preg_replace('#videos/+videos/#', 'videos/', $normalizedPath);

            // Prepare data with UTF-8 validation
            $cleanTitle = mb_convert_encoding(mb_substr($title, 0, 255, 'UTF-8'), 'UTF-8', 'UTF-8');
            $cleanDescription = mb_convert_encoding(mb_substr($this->extractVideoDescription(), 0, 1000, 'UTF-8'), 'UTF-8', 'UTF-8');

            // Create GeneratedVideo record
            $generatedVideo = GeneratedVideo::create([
                'title' => $cleanTitle,
                'description' => $cleanDescription,
                'platform' => $this->platform,
                'media_type' => $this->parameters['--media-type'] ?? 'mixed',
                'file_path' => $normalizedPath,
                'file_name' => $fileName,
                'file_size' => $fileSize,
                'duration' => $this->extractVideoDuration(),
                'metadata' => [
                    'generation_parameters' => $this->parameters,
                    'platform' => $this->platform,
                    'created_via' => 'video_generator',
                    'subtitle_text' => $this->parameters['--subtitle-text'] ?? null,
                    'slide_duration' => $this->parameters['--slide-duration'] ?? null,
                    'media_type' => $this->parameters['--media-type'] ?? null,
                    'full_video_path' => $fullVideoPath // Store full path for debugging
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

            // Create publishing records for the video
            $this->createPublishingRecords($generatedVideo, $task);

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
            $title = mb_substr($this->parameters['--subtitle-text'], 0, 50, 'UTF-8');
            if (mb_strlen($this->parameters['--subtitle-text'], 'UTF-8') > 50) {
                $title .= '...';
            }
            return $title;
        }

        // Fallback to filename
        $filename = $this->parameters['--output'] ?? 'Generated Video';
        return str_replace('.mp4', '', $filename);
    }

    /**
     * Create publishing records for the generated video
     */
    private function createPublishingRecords(GeneratedVideo $video, VideoGenerationTask $task)
    {
        try {
            $publishingService = new VideoPublishingService();

            // Determine platforms to create publishing records for
            $platforms = $this->determinePlatformsForPublishing($task);

            if (!empty($platforms)) {
                $publishingOptions = $this->extractPublishingOptions($task);

                $publishingRecords = $publishingService->createPublishingRecords($video, $publishingOptions);

                Log::info("Created publishing records for video", [
                    'video_id' => $video->id,
                    'platforms' => $platforms,
                    'records_created' => count($publishingRecords)
                ]);
            } else {
                Log::info("No publishing records created - platform is 'none' or not specified", [
                    'video_id' => $video->id,
                    'platform' => $this->platform
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Failed to create publishing records", [
                'video_id' => $video->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Determine which platforms to create publishing records for
     */
    private function determinePlatformsForPublishing(VideoGenerationTask $task)
    {
        // Don't create publishing records for 'none' platform
        if ($this->platform === 'none') {
            return [];
        }

        // For specific platforms, create publishing record for that platform
        if (in_array($this->platform, ['youtube', 'tiktok', 'facebook', 'instagram'])) {
            return [$this->platform];
        }

        return [];
    }

    /**
     * Extract publishing options from task parameters
     */
    private function extractPublishingOptions(VideoGenerationTask $task)
    {
        $options = [
            'platforms' => $this->determinePlatformsForPublishing($task),
            'publish_mode' => 'manual', // Default to manual
        ];

        // Extract platform-specific options from parameters
        $params = $this->parameters;

        // Check for auto-publish settings
        if (isset($params['--auto-publish']) && $params['--auto-publish'] === 'true') {
            $options['publish_mode'] = 'auto';
        } elseif (isset($params['--schedule-post']) && $params['--schedule-post'] === 'true') {
            $options['publish_mode'] = 'scheduled';
            if (isset($params['--schedule-time'])) {
                $options['scheduled_at'] = $params['--schedule-time'];
            }
        }

        // Extract titles and descriptions
        if (isset($params['--post-title'])) {
            $options['titles'][$this->platform] = $params['--post-title'];
        }

        if (isset($params['--post-description'])) {
            $options['descriptions'][$this->platform] = $params['--post-description'];
        }

        // Extract privacy settings
        if (isset($params['--privacy'])) {
            $options['privacy'] = $params['--privacy'];
        }

        // Extract channel settings
        if (isset($params['--channel-id'])) {
            $options['channels'][$this->platform] = $params['--channel-id'];
        }

        return $options;
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
