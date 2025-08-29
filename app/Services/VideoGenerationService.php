<?php

namespace App\Services;

use App\Jobs\GenerateUniversalVideoJob;
use App\Models\VideoGenerationTask;
use App\Models\AudioLibrary;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class VideoGenerationService
{
    /**
     * Queue a single video generation task
     */
    public function queueSingleVideo($platform, Request $request, $userId)
    {
        // 🔥 DEBUG: Log service input
        Log::info('🔥🔥🔥 VIDEO SERVICE DEBUG: queueSingleVideo called', [
            'platform' => $platform,
            'user_id' => $userId,
            'request_all' => $request->all(),
            'request_files' => $request->allFiles(),
            'media_type' => $request->input('media_type'),
            'audio_source' => $request->input('audio_source'),
            'slide_duration' => $request->input('slide_duration'),
            'slide_transition' => $request->input('slide_transition'),
            'images_uploaded' => $request->hasFile('images') ? count($request->file('images')) : 0
        ]);

        // Create temp directory
        $tempId = uniqid();
        $tempDir = storage_path("app/videos/temp/{$platform}_{$tempId}");

        if (!File::isDirectory($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        // Prepare parameters based on platform
        $parameters = $this->prepareParameters($platform, $request, $tempDir);
        
        // Estimate duration based on content
        $estimatedDuration = $this->estimateProcessingTime($platform, $request);

        // Create task record
        $task = VideoGenerationTask::create([
            'user_id' => $userId,
            'platform' => $platform,
            'type' => VideoGenerationTask::TYPE_SINGLE,
            'status' => VideoGenerationTask::STATUS_PENDING,
            'priority' => $this->calculatePriority($request),
            'parameters' => $parameters,
            'estimated_duration' => $estimatedDuration
        ]);

        // Dispatch unified video generation job
        \App\Jobs\GenerateUniversalVideoJob::dispatch($task->id, $platform, $parameters, $tempDir)
                                          ->onQueue('video');

        return $task;
    }

    /**
     * Generate video with Vietnamese subtitle support (direct processing)
     */
    public function generateVideoWithVietnameseSubtitle($requestData)
    {
        try {
            Log::info('🔥🔥🔥 FORCE OVERRIDE: VideoGenerationService with Vietnamese subtitle', [
                'request_data' => $requestData
            ]);

            // Use existing logic but force Vietnamese subtitle processing
            $platform = $requestData['platform'] ?? 'tiktok';
            $tempDir = $requestData['temp_dir'] ?? storage_path('app/videos/temp/' . uniqid());

            // Ensure temp directory exists
            if (!File::isDirectory($tempDir)) {
                File::makeDirectory($tempDir, 0755, true);
            }

            // Create a mock request object for existing methods
            $mockRequest = new \Illuminate\Http\Request();
            $mockRequest->merge($requestData);

            // Use existing parameter preparation but force Vietnamese subtitle
            $parameters = $this->prepareParameters($platform, $mockRequest, $tempDir);

            // Force Vietnamese subtitle processing
            $parameters['--force-vietnamese-subtitle'] = true;

            Log::info('🔥🔥🔥 FORCE OVERRIDE: Parameters prepared with Vietnamese subtitle force', [
                'parameters' => $parameters
            ]);

            // Execute video generation directly (not queued)
            return $this->executeVideoGenerationDirect($platform, $parameters, $tempDir);

        } catch (\Exception $e) {
            Log::error('🔥🔥🔥 FORCE OVERRIDE: Failed to generate video with Vietnamese subtitle', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Execute video generation directly (not queued)
     */
    private function executeVideoGenerationDirect($platform, $parameters, $tempDir)
    {
        try {
            Log::info('🔥🔥🔥 FORCE OVERRIDE: Executing video generation directly', [
                'platform' => $platform,
                'temp_dir' => $tempDir
            ]);

            // Use VideoSubtitleService for complete video generation with Vietnamese subtitle
            $videoSubtitleService = app(\App\Services\VideoSubtitleService::class);

            // Prepare components for complete video generation
            $components = [];

            // Add images if provided
            if (isset($parameters['--product-images']) && !empty($parameters['--product-images'])) {
                $imagePaths = explode(',', $parameters['--product-images']);
                $components['images'] = $imagePaths;
            }

            // Add subtitle if provided
            if (isset($parameters['--subtitle-text']) && !empty($parameters['--subtitle-text'])) {
                $components['subtitle'] = $parameters['--subtitle-text'];
            }

            // Add audio if provided
            if (isset($parameters['--library-audio-id']) && !empty($parameters['--library-audio-id'])) {
                $audioLibrary = \App\Models\AudioLibrary::find($parameters['--library-audio-id']);
                if ($audioLibrary) {
                    $components['audio'] = storage_path('app/' . $audioLibrary->file_path);
                }
            }

            // Prepare options for video generation
            $options = [
                'final_output_path' => $parameters['--output'] ?? storage_path('app/videos/generated/' . uniqid('vietnamese_') . '.mp4'),
                'platform' => $platform,
                'slide_duration' => $parameters['--slide-duration'] ?? 3,
                'slide_transition' => $parameters['--slide-transition'] ?? 'slide',
                'subtitle_size' => $parameters['--subtitle-size'] ?? 24,
                'subtitle_color' => $parameters['--subtitle-color'] ?? 'white',
                'subtitle_background' => $parameters['--subtitle-background'] ?? 'none',
                'subtitle_position' => $parameters['--subtitle-position'] ?? 'bottom',
                'subtitle_font' => $parameters['--subtitle-font'] ?? 'Arial Unicode MS',
                'resolution' => $parameters['--resolution'] ?? '1920x1080',
                'fps' => $parameters['--fps'] ?? '25',
                'force_vietnamese_encoding' => true // Force Vietnamese encoding
            ];

            Log::info('🔥🔥🔥 FORCE OVERRIDE: Using simple video generation approach', [
                'components' => array_keys($components),
                'options' => $options
            ]);

            // Generate video step by step with Vietnamese subtitle
            $result = $this->generateVideoStepByStep($components, $options, $tempDir);

            if ($result['success']) {
                Log::info('🔥🔥🔥 FORCE OVERRIDE: Video generation completed successfully', [
                    'output_path' => $result['output_path']
                ]);
            } else {
                Log::error('🔥🔥🔥 FORCE OVERRIDE: Video generation failed', [
                    'error' => $result['error']
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('🔥🔥🔥 FORCE OVERRIDE: Exception in direct video generation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate video step by step with Vietnamese subtitle
     */
    private function generateVideoStepByStep($components, $options, $tempDir)
    {
        try {
            Log::info('🔥🔥🔥 FORCE OVERRIDE: Starting step-by-step video generation', [
                'temp_dir' => $tempDir,
                'components' => array_keys($components)
            ]);

            // Step 1: Create base video from images
            $baseVideoPath = null;
            if (isset($components['images']) && !empty($components['images'])) {
                $baseVideoPath = $this->createVideoFromImages($components['images'], $tempDir, $options);
            }

            if (!$baseVideoPath || !File::exists($baseVideoPath)) {
                throw new \Exception('Failed to create base video from images');
            }

            Log::info('🔥🔥🔥 FORCE OVERRIDE: Base video created successfully', [
                'base_video_path' => $baseVideoPath
            ]);

            // Step 2: Add Vietnamese subtitle if provided
            $finalVideoPath = $baseVideoPath;
            if (isset($components['subtitle']) && !empty($components['subtitle'])) {
                $videoSubtitleService = app(\App\Services\VideoSubtitleService::class);

                $subtitleOptions = [
                    'output_path' => $tempDir . '/video_with_vietnamese_subtitle.mp4',
                    'font_size' => $options['subtitle_size'] ?? 24,
                    'font_color' => $options['subtitle_color'] ?? 'white',
                    'background_color' => $options['subtitle_background'] ?? 'none',
                    'position' => $options['subtitle_position'] ?? 'bottom',
                    'font_name' => $options['subtitle_font'] ?? 'Arial Unicode MS',
                    'hard_subtitle' => true,
                    'encoding' => 'UTF-8'
                ];

                Log::info('🔥🔥🔥 FORCE OVERRIDE: Adding Vietnamese subtitle', [
                    'subtitle_text_preview' => substr($components['subtitle'], 0, 50),
                    'subtitle_options' => $subtitleOptions
                ]);

                $subtitleResult = $videoSubtitleService->createVideoWithVietnameseSubtitle(
                    $baseVideoPath,
                    $components['subtitle'],
                    null, // audio duration
                    $subtitleOptions
                );

                if ($subtitleResult['success']) {
                    $finalVideoPath = $subtitleResult['output_path'];
                    Log::info('🔥🔥🔥 FORCE OVERRIDE: Vietnamese subtitle added successfully', [
                        'final_video_path' => $finalVideoPath
                    ]);
                } else {
                    Log::warning('🔥🔥🔥 FORCE OVERRIDE: Failed to add Vietnamese subtitle, using base video', [
                        'error' => $subtitleResult['error'] ?? 'Unknown error'
                    ]);
                }
            }

            // Step 3: Move to final location
            $outputPath = $options['final_output_path'] ?? storage_path('app/videos/generated/' . uniqid('vietnamese_') . '.mp4');

            // Ensure output directory exists
            $outputDir = dirname($outputPath);
            if (!File::isDirectory($outputDir)) {
                File::makeDirectory($outputDir, 0755, true);
            }

            // Copy to final location
            if (File::exists($finalVideoPath)) {
                File::copy($finalVideoPath, $outputPath);

                Log::info('🔥🔥🔥 FORCE OVERRIDE: Video moved to final location', [
                    'output_path' => $outputPath
                ]);

                return [
                    'success' => true,
                    'output_path' => $outputPath
                ];
            } else {
                throw new \Exception('Final video file not found: ' . $finalVideoPath);
            }

        } catch (\Exception $e) {
            Log::error('🔥🔥🔥 FORCE OVERRIDE: Failed in step-by-step generation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create video from images using FFmpeg
     */
    private function createVideoFromImages($imagePaths, $tempDir, $options)
    {
        try {
            $slideDuration = $options['slide_duration'] ?? 3;
            $outputPath = $tempDir . '/slideshow.mp4';

            // Get resolution from options, default to 1920x1080
            $resolution = $options['resolution'] ?? '1920x1080';
            list($width, $height) = explode('x', $resolution);
            $fps = $options['fps'] ?? '25';

            Log::info('🔥🔥🔥 FORCE OVERRIDE: Creating slideshow from images', [
                'image_count' => count($imagePaths),
                'slide_duration' => $slideDuration,
                'output_path' => $outputPath,
                'resolution' => $resolution,
                'fps' => $fps
            ]);

            if (count($imagePaths) === 1) {
                // Single image - create looping video
                $imagePath = $imagePaths[0];
                $totalDuration = $slideDuration * 3; // Minimum duration

                $cmd = "ffmpeg -loop 1 -i \"{$imagePath}\" -vf \"scale={$width}:{$height}:force_original_aspect_ratio=increase,crop={$width}:{$height}\" -t {$totalDuration} -c:v libx264 -preset fast -crf 23 -pix_fmt yuv420p -r {$fps} \"{$outputPath}\" -y";

                Log::info('🔥🔥🔥 FORCE OVERRIDE: Single image FFmpeg command', [
                    'command' => $cmd,
                    'resolution' => $resolution,
                    'fps' => $fps
                ]);

                exec($cmd, $output, $returnCode);

                if ($returnCode === 0 && File::exists($outputPath)) {
                    Log::info('🔥🔥🔥 FORCE OVERRIDE: Single image video created successfully');
                    return $outputPath;
                } else {
                    throw new \Exception('Failed to create video from single image. Return code: ' . $returnCode);
                }
            } else {
                // Multiple images slideshow with precise timing
                $inputListPath = $tempDir . '/images.txt';
                $inputList = '';
                $totalExpectedDuration = 0;

                foreach ($imagePaths as $index => $image) {
                    $inputList .= "file '" . str_replace('\\', '/', $image) . "'\n";
                    $inputList .= "duration {$slideDuration}\n";
                    $totalExpectedDuration += $slideDuration;
                }

                // Add transition duration if transitions are enabled
                $transition = $options['slide_transition'] ?? 'slide';

                Log::info('🔥🔥🔥 DURATION DEBUG: Checking transition conditions', [
                    'slide_transition_from_options' => $options['slide_transition'] ?? 'NOT_SET',
                    'transition_final' => $transition,
                    'transition_not_none' => $transition !== 'none',
                    'image_count' => count($imagePaths),
                    'image_count_gt_1' => count($imagePaths) > 1,
                    'will_add_transition_duration' => ($transition !== 'none' && count($imagePaths) > 1)
                ]);

                if ($transition !== 'none' && count($imagePaths) > 1) {
                    $transitionDuration = 0.5; // 0.5 second per transition
                    $transitionCount = count($imagePaths) - 1; // n-1 transitions for n images
                    $totalExpectedDuration += ($transitionCount * $transitionDuration);

                    Log::info('🔥🔥🔥 DURATION: Added transition duration', [
                        'slide_duration_per_image' => $slideDuration,
                        'image_count' => count($imagePaths),
                        'transition_duration_per_transition' => $transitionDuration,
                        'transition_count' => $transitionCount,
                        'total_transition_duration' => $transitionCount * $transitionDuration,
                        'total_expected_duration' => $totalExpectedDuration
                    ]);
                }

                // Add last image again for proper duration (FFmpeg requirement)
                if (!empty($imagePaths)) {
                    $lastImage = end($imagePaths);
                    $inputList .= "file '" . str_replace('\\', '/', $lastImage) . "'\n";
                }

                File::put($inputListPath, $inputList);

                // Check if transition effects are enabled
                $transition = $options['slide_transition'] ?? 'slide';

                Log::info('🔥🔥🔥 TRANSITION DEBUG: Checking transition conditions', [
                    'transition' => $transition,
                    'transition_not_none' => $transition !== 'none',
                    'image_count' => count($imagePaths),
                    'image_count_gt_1' => count($imagePaths) > 1,
                    'will_use_transitions' => ($transition !== 'none' && count($imagePaths) > 1)
                ]);

                if ($transition !== 'none' && count($imagePaths) > 1) {
                    // Use transition effects
                    Log::info('🔥🔥🔥 TRANSITION DEBUG: Using transition effects');
                    $cmd = $this->createSlideshowWithTransitions($imagePaths, $outputPath, $slideDuration, $transition, $width, $height, $fps);
                } else {
                    // Use simple concat without transitions
                    Log::info('🔥🔥🔥 TRANSITION DEBUG: Using simple concat without transitions');
                    $cmd = "ffmpeg -f concat -safe 0 -i \"{$inputListPath}\" -vf \"scale={$width}:{$height}:force_original_aspect_ratio=increase,crop={$width}:{$height}\" -c:v libx264 -preset fast -crf 23 -pix_fmt yuv420p -r {$fps} -t {$totalExpectedDuration} \"{$outputPath}\" -y";
                }

                Log::info('🔥🔥🔥 FORCE OVERRIDE: Multiple images FFmpeg command', [
                    'command' => $cmd,
                    'input_list_content' => $inputList,
                    'resolution' => $resolution,
                    'fps' => $fps,
                    'image_count' => count($imagePaths),
                    'slide_duration' => $slideDuration,
                    'total_expected_duration' => $totalExpectedDuration
                ]);

                exec($cmd, $output, $returnCode);

                if ($returnCode === 0 && File::exists($outputPath)) {
                    Log::info('🔥🔥🔥 FORCE OVERRIDE: Multiple images video created successfully');
                    return $outputPath;
                } else {
                    throw new \Exception('Failed to create video from multiple images. Return code: ' . $returnCode);
                }
            }

        } catch (\Exception $e) {
            Log::error('🔥🔥🔥 FORCE OVERRIDE: Failed to create video from images', [
                'error' => $e->getMessage(),
                'image_paths' => $imagePaths
            ]);
            throw $e;
        }
    }

    /**
     * Create slideshow with transition effects
     */
    private function createSlideshowWithTransitions($imagePaths, $outputPath, $slideDuration, $transition, $width, $height, $fps)
    {
        $transitionDuration = 0.5; // 0.5 second transition
        $imageCount = count($imagePaths);

        // Calculate total duration with transitions
        $totalDuration = ($imageCount * $slideDuration) + (($imageCount - 1) * $transitionDuration);

        Log::info('🔥🔥🔥 DURATION: Added transition duration', [
            'slide_duration_per_image' => $slideDuration,
            'image_count' => $imageCount,
            'transition_duration_per_transition' => $transitionDuration,
            'transition_count' => $imageCount - 1,
            'total_transition_duration' => ($imageCount - 1) * $transitionDuration,
            'total_expected_duration' => $totalDuration,
            'transition_type' => $transition
        ]);

        // Build complex filter for transitions
        $inputs = '';
        $filters = [];

        // Add all images as inputs
        foreach ($imagePaths as $index => $imagePath) {
            $inputs .= "-loop 1 -t " . ($slideDuration + $transitionDuration) . " -i \"" . str_replace('\\', '/', $imagePath) . "\" ";
        }

        // Scale all inputs
        for ($i = 0; $i < $imageCount; $i++) {
            $filters[] = "[{$i}:v]scale={$width}:{$height}:force_original_aspect_ratio=increase,crop={$width}:{$height},setpts=PTS-STARTPTS[v{$i}]";
        }

        // Create transitions between images
        $currentLabel = 'v0';
        for ($i = 1; $i < $imageCount; $i++) {
            $nextLabel = "v{$i}";
            $outputLabel = ($i == $imageCount - 1) ? 'out' : "t{$i}";

            $offset = ($i - 1) * $slideDuration + ($i - 1) * $transitionDuration;

            switch ($transition) {
                case 'fade':
                    $filters[] = "[{$currentLabel}][{$nextLabel}]xfade=transition=fade:duration={$transitionDuration}:offset={$offset}[{$outputLabel}]";
                    break;
                case 'slide':
                    $filters[] = "[{$currentLabel}][{$nextLabel}]xfade=transition=slideleft:duration={$transitionDuration}:offset={$offset}[{$outputLabel}]";
                    break;
                case 'zoom':
                    $filters[] = "[{$currentLabel}][{$nextLabel}]xfade=transition=zoomin:duration={$transitionDuration}:offset={$offset}[{$outputLabel}]";
                    break;
                case 'dissolve':
                    $filters[] = "[{$currentLabel}][{$nextLabel}]xfade=transition=dissolve:duration={$transitionDuration}:offset={$offset}[{$outputLabel}]";
                    break;
                case 'wipe':
                    $filters[] = "[{$currentLabel}][{$nextLabel}]xfade=transition=wipeleft:duration={$transitionDuration}:offset={$offset}[{$outputLabel}]";
                    break;
                default:
                    $filters[] = "[{$currentLabel}][{$nextLabel}]xfade=transition=fade:duration={$transitionDuration}:offset={$offset}[{$outputLabel}]";
            }

            $currentLabel = $outputLabel;
        }

        $filterComplex = implode(';', $filters);
        $totalDuration = ($imageCount * $slideDuration) + (($imageCount - 1) * $transitionDuration);

        $cmd = "ffmpeg {$inputs} -filter_complex \"{$filterComplex}\" -map \"[out]\" -c:v libx264 -preset fast -crf 23 -pix_fmt yuv420p -r {$fps} -t {$totalDuration} \"{$outputPath}\" -y";

        Log::info('🔥🔥🔥 FORCE OVERRIDE: Creating slideshow with transitions', [
            'transition' => $transition,
            'image_count' => $imageCount,
            'slide_duration' => $slideDuration,
            'transition_duration' => $transitionDuration,
            'total_duration' => $totalDuration,
            'command_preview' => substr($cmd, 0, 200) . '...'
        ]);

        return $cmd;
    }

    /**
     * Queue batch video generation tasks
     */
    public function queueBatchVideos($platform, Request $request, $userId)
    {
        $batchId = Str::uuid();
        $tasks = [];
        
        // Get batch data based on platform
        $batchData = $this->prepareBatchData($platform, $request);
        $totalVideos = count($batchData);

        foreach ($batchData as $index => $videoData) {
            // Create temp directory for each video
            $tempId = uniqid();
            $tempDir = storage_path("app/videos/temp/{$platform}_batch_{$tempId}");
            
            if (!File::isDirectory($tempDir)) {
                File::makeDirectory($tempDir, 0755, true);
            }

            // Prepare parameters for individual video
            $parameters = $this->prepareBatchVideoParameters($platform, $request, $videoData, $tempDir, $index);
            
            // Estimate duration
            $estimatedDuration = $this->estimateProcessingTime($platform, $request, $videoData);

            // Create task record
            $task = VideoGenerationTask::create([
                'user_id' => $userId,
                'platform' => $platform,
                'type' => VideoGenerationTask::TYPE_BATCH,
                'status' => VideoGenerationTask::STATUS_PENDING,
                'priority' => $this->calculatePriority($request),
                'parameters' => $parameters,
                'estimated_duration' => $estimatedDuration,
                'batch_id' => $batchId,
                'batch_index' => $index + 1,
                'total_in_batch' => $totalVideos
            ]);

            // Dispatch unified job to queue with delay to prevent overwhelming
            $delay = $index * 5; // 5 second delay between jobs
            \App\Jobs\GenerateUniversalVideoJob::dispatch($task->id, $platform, $parameters, $tempDir)
                                              ->onQueue('video')
                                              ->delay(now()->addSeconds($delay));

            $tasks[] = $task;
        }

        return [
            'batch_id' => $batchId,
            'tasks' => $tasks,
            'total_count' => $totalVideos
        ];
    }

    /**
     * Prepare parameters for single video
     */
    private function prepareParameters($platform, Request $request, $tempDir)
    {
        $outputName = $request->output_name ?: $this->generateDefaultVideoName($platform, $request);
        if (!Str::endsWith($outputName, '.mp4')) {
            $outputName .= '.mp4';
        }

        $baseParams = [
            '--output' => $outputName,
            '--temp-dir' => $tempDir,
            '--task-id' => uniqid('video_', true),
        ];

        if ($platform === 'none') {
            // Parameters for videos without channel publishing
            $noneParams = [
                '--platform' => 'none',
                '--media-type' => $request->media_type ?: 'images',
            ];

            // Get output name from none-specific field
            if ($request->none_output_name) {
                $noneParams['--output'] = $request->none_output_name . '.mp4';
            }

            // Video settings
            if ($request->none_resolution) {
                $noneParams['--resolution'] = $request->none_resolution;
            }
            if ($request->none_fps) {
                $noneParams['--fps'] = $request->none_fps;
            }
            if ($request->none_quality) {
                $noneParams['--quality'] = $request->none_quality;
            }

            // Handle script/audio - only pass script if using TTS and have TTS text
            if ($request->tts_text && $request->audio_source === 'tts') {
                $noneParams['--script'] = $request->tts_text;
                \Log::info('VideoGenerationService: Adding TTS script for none platform', [
                    'audio_source' => $request->audio_source,
                    'has_tts_text' => !empty($request->tts_text),
                    'tts_text_preview' => substr($request->tts_text, 0, 50)
                ]);
            } else {
                \Log::info('VideoGenerationService: Skipping TTS script for none platform', [
                    'audio_source' => $request->audio_source,
                    'has_tts_text' => !empty($request->tts_text),
                    'has_script_text' => !empty($request->script_text)
                ]);
            }

            // Handle audio source
            if ($request->audio_source === 'upload' && $request->hasFile('audio_file')) {
                $audioPath = $request->file('audio_file')->store("temp/audio", 'local');
                $noneParams['--audio-file'] = storage_path("app/{$audioPath}");
            } elseif ($request->audio_source === 'library') {
                $libraryAudioId = $request->library_audio_id;

                Log::info('🔥🔥🔥 AUDIO LIBRARY DEBUG: Processing library audio', [
                    'audio_source' => $request->audio_source,
                    'library_audio_id_raw' => $libraryAudioId,
                    'library_audio_id_type' => gettype($libraryAudioId),
                    'library_audio_id_empty' => empty($libraryAudioId),
                    'library_audio_id_numeric' => is_numeric($libraryAudioId)
                ]);

                if ($libraryAudioId && is_numeric($libraryAudioId)) {
                    $noneParams['--library-audio-id'] = $libraryAudioId;
                    // Increment usage count
                    $audioLibrary = \App\Models\AudioLibrary::find($libraryAudioId);
                    if ($audioLibrary) {
                        $audioLibrary->incrementUsage();
                        Log::info('🔥🔥🔥 AUDIO LIBRARY DEBUG: Found and incremented usage', [
                            'audio_id' => $audioLibrary->id,
                            'audio_title' => $audioLibrary->title
                        ]);
                    } else {
                        Log::warning('🔥🔥🔥 AUDIO LIBRARY DEBUG: Audio not found in database', [
                            'library_audio_id' => $libraryAudioId
                        ]);
                    }
                } else {
                    Log::warning('🔥🔥🔥 AUDIO LIBRARY DEBUG: Invalid library audio ID', [
                        'library_audio_id' => $libraryAudioId,
                        'audio_source' => $request->audio_source
                    ]);
                }
            }

            // Handle background audio/music
            if ($request->background_audio_id && is_numeric($request->background_audio_id)) {
                $noneParams['--background-audio-id'] = $request->background_audio_id;
                $noneParams['--background-audio-volume'] = $request->background_audio_volume ?: 30;

                // Increment usage count for background audio
                $backgroundAudio = \App\Models\AudioLibrary::find($request->background_audio_id);
                if ($backgroundAudio) {
                    $backgroundAudio->incrementUsage();
                    Log::info('🔥🔥🔥 BACKGROUND AUDIO DEBUG: Found and incremented usage', [
                        'background_audio_id' => $backgroundAudio->id,
                        'background_audio_title' => $backgroundAudio->title,
                        'background_audio_volume' => $request->background_audio_volume ?: 30
                    ]);
                } else {
                    Log::warning('🔥🔥🔥 BACKGROUND AUDIO DEBUG: Background audio not found in database', [
                        'background_audio_id' => $request->background_audio_id
                    ]);
                }
            }

            // Handle logo settings
            if ($request->enable_logo) {
                $noneParams['--enable-logo'] = true;
                $noneParams['--logo-source'] = $request->logo_source ?: 'library';

                if ($request->logo_source === 'library' && $request->selected_logo) {
                    $noneParams['--selected-logo'] = $request->selected_logo;
                } elseif ($request->logo_source === 'upload' && $request->hasFile('logo_file')) {
                    // Handle logo file upload
                    $logoFile = $request->file('logo_file');
                    $logoPath = $logoFile->store('temp/logos', 'public');
                    $noneParams['--logo-file'] = storage_path('app/public/' . $logoPath);
                }

                // Logo positioning and styling
                if ($request->logo_position) {
                    $noneParams['--logo-position'] = $request->logo_position;
                }
                if ($request->logo_size) {
                    $noneParams['--logo-size'] = $request->logo_size;
                }
                if ($request->logo_width) {
                    $noneParams['--logo-width'] = $request->logo_width;
                }
                if ($request->logo_height) {
                    $noneParams['--logo-height'] = $request->logo_height;
                }
                if ($request->logo_opacity) {
                    $noneParams['--logo-opacity'] = $request->logo_opacity;
                }
                if ($request->logo_margin) {
                    $noneParams['--logo-margin'] = $request->logo_margin;
                }
                if ($request->logo_duration) {
                    $noneParams['--logo-duration'] = $request->logo_duration;
                }
                if ($request->logo_start_time) {
                    $noneParams['--logo-start-time'] = $request->logo_start_time;
                }
                if ($request->logo_end_time) {
                    $noneParams['--logo-end-time'] = $request->logo_end_time;
                }

                Log::info('🔥🔥🔥 LOGO DEBUG: Logo parameters added', [
                    'logo_source' => $request->logo_source,
                    'selected_logo' => $request->selected_logo,
                    'logo_position' => $request->logo_position,
                    'logo_size' => $request->logo_size
                ]);
            }

            // Handle media files based on type
            if ($request->media_type === 'images') {
                // 🔥 DEBUG: Log image processing
                Log::info('🔥🔥🔥 VIDEO SERVICE DEBUG: Processing images', [
                    'media_type' => $request->media_type,
                    'has_product_images' => $request->hasFile('product_images'),
                    'has_images' => $request->hasFile('images'),
                    'has_inputs_images' => $request->hasFile('inputs.images'),
                    'slide_duration_input' => $request->slide_duration,
                    'slide_transition_input' => $request->slide_transition,
                    'all_files' => array_keys($request->allFiles())
                ]);

                // Check for images from different sources (template vs direct upload)
                $imageFiles = null;

                if ($request->hasFile('product_images')) {
                    $imageFiles = $request->file('product_images');
                    Log::info('🔥🔥🔥 Using product_images', ['count' => count($imageFiles)]);
                } elseif ($request->hasFile('images')) {
                    $imageFiles = $request->file('images');
                    Log::info('🔥🔥🔥 Using images', ['count' => count($imageFiles)]);
                } elseif ($request->hasFile('inputs.images')) {
                    $imageFiles = $request->file('inputs.images');
                    Log::info('🔥🔥🔥 Using inputs.images', ['count' => count($imageFiles)]);
                }

                if ($imageFiles) {
                    $slideDuration = $request->slide_duration ?: 3;
                    $slideTransition = $request->slide_transition ?: 'slide';

                    $noneParams['--slide-duration'] = $slideDuration;
                    $noneParams['--slide-transition'] = $slideTransition;

                    // Handle individual image durations and transitions
                    $imageDurations = $request->input('image_durations', []);
                    $imageTransitions = $request->input('image_transitions', []);

                    if (!empty($imageDurations) && is_array($imageDurations)) {
                        $noneParams['--image-durations'] = json_encode($imageDurations);
                        Log::info('🔥🔥🔥 VIDEO SERVICE DEBUG: Individual image durations', [
                            'image_durations_raw' => $imageDurations,
                            'image_durations_json' => json_encode($imageDurations),
                            'image_count' => count($imageFiles)
                        ]);
                    }

                    if (!empty($imageTransitions) && is_array($imageTransitions)) {
                        $noneParams['--image-transitions'] = json_encode($imageTransitions);
                        Log::info('🔥🔥🔥 VIDEO SERVICE DEBUG: Individual image transitions', [
                            'image_transitions_raw' => $imageTransitions,
                            'image_transitions_json' => json_encode($imageTransitions),
                            'image_count' => count($imageFiles)
                        ]);
                    }

                    Log::info('🔥🔥🔥 VIDEO SERVICE DEBUG: Image settings', [
                        'slide_duration' => $slideDuration,
                        'slide_transition' => $slideTransition,
                        'image_count' => count($imageFiles),
                        'has_individual_durations' => !empty($imageDurations)
                    ]);

                    // Store multiple images
                    $imagePaths = [];
                    foreach ($imageFiles as $index => $image) {
                        $imagePath = $image->store("temp/images", 'local');
                        $imagePaths[] = storage_path("app/{$imagePath}");
                        Log::info("🔥🔥🔥 Stored image {$index}", [
                            'original_name' => $image->getClientOriginalName(),
                            'stored_path' => storage_path("app/{$imagePath}")
                        ]);
                    }

                    // Apply image order if specified and get the mapping
                    Log::info('🔄 Before applyImageOrder', [
                        'request_image_orders' => $request->input('image_orders'),
                        'request_image_order_mapping' => $request->input('image_order_mapping'),
                        'original_image_paths' => array_map('basename', $imagePaths)
                    ]);

                    $originalImagePaths = $imagePaths; // Store original order
                    $imagePaths = $this->applyImageOrder($imagePaths, $request);

                    $noneParams['--images'] = implode(',', $imagePaths);

                    // Also reorder durations and transitions if they exist
                    $imageDurations = $request->input('image_durations', []);
                    $imageTransitions = $request->input('image_transitions', []);

                    if (!empty($imageDurations) && is_array($imageDurations)) {
                        // Get the order mapping used for reordering
                        $orderMapping = $request->input('image_order_mapping');
                        $imageOrders = $request->input('image_orders');

                        // Use image_orders if no mapping provided
                        if (!$orderMapping && $imageOrders && is_array($imageOrders)) {
                            $orderMappingArray = [];
                            foreach ($imageOrders as $originalIndex => $orderValue) {
                                $orderMappingArray[(string)$originalIndex] = intval($orderValue);
                            }
                            $orderMapping = json_encode((object)$orderMappingArray);
                        }

                        if ($orderMapping) {
                            $originalDurations = $imageDurations;
                            $imageDurations = $this->reorderArrayByMapping($imageDurations, $orderMapping);

                            Log::info('🔄 Reordered image durations', [
                                'original_durations' => $originalDurations,
                                'reordered_durations' => $imageDurations,
                                'order_mapping' => $orderMapping
                            ]);
                        }
                    }

                    if (!empty($imageTransitions) && is_array($imageTransitions)) {
                        // Get the order mapping used for reordering
                        $orderMapping = $request->input('image_order_mapping');
                        $imageOrders = $request->input('image_orders');

                        // Use image_orders if no mapping provided
                        if (!$orderMapping && $imageOrders && is_array($imageOrders)) {
                            $orderMappingArray = [];
                            foreach ($imageOrders as $originalIndex => $orderValue) {
                                $orderMappingArray[(string)$originalIndex] = intval($orderValue);
                            }
                            $orderMapping = json_encode((object)$orderMappingArray);
                        }

                        if ($orderMapping) {
                            $originalTransitions = $imageTransitions;
                            $imageTransitions = $this->reorderArrayByMapping($imageTransitions, $orderMapping);

                            Log::info('🔄 Reordered image transitions', [
                                'original_transitions' => $originalTransitions,
                                'reordered_transitions' => $imageTransitions,
                                'order_mapping' => $orderMapping
                            ]);
                        }
                    }

                    // Update the parameters with reordered arrays
                    if (!empty($imageDurations) && is_array($imageDurations)) {
                        $noneParams['--image-durations'] = json_encode($imageDurations);
                    }

                    if (!empty($imageTransitions) && is_array($imageTransitions)) {
                        $noneParams['--image-transitions'] = json_encode($imageTransitions);
                    }

                    // Create image order mapping for Universal Command
                    $imageOrderMapping = $request->input('image_order_mapping');
                    $imageOrders = $request->input('image_orders');

                    // Create mapping from image_orders if no mapping provided
                    if (!$imageOrderMapping && $imageOrders && is_array($imageOrders)) {
                        Log::info('🔄 Creating image order mapping from image_orders', [
                            'image_orders' => $imageOrders
                        ]);

                        // Create mapping: originalIndex => orderValue
                        $orderMapping = [];
                        foreach ($imageOrders as $originalIndex => $orderValue) {
                            // Force string key to create associative array
                            $orderMapping[(string)$originalIndex] = intval($orderValue);
                        }
                        // Force JSON to be object by casting to object
                        $imageOrderMapping = json_encode((object)$orderMapping);

                        Log::info('🔄 Created image order mapping', [
                            'image_orders' => $imageOrders,
                            'order_mapping_object' => $orderMapping,
                            'order_mapping_keys' => array_keys($orderMapping),
                            'order_mapping_values' => array_values($orderMapping),
                            'mapping_json' => $imageOrderMapping,
                            'mapping_json_decoded' => json_decode($imageOrderMapping, true)
                        ]);
                    }

                    if ($imageOrderMapping) {
                        $noneParams['--image-order-mapping'] = $imageOrderMapping;
                        Log::info('🔄 Added image order mapping to parameters', [
                            'mapping' => $imageOrderMapping
                        ]);
                    }

                    Log::info('🔥🔥🔥 VIDEO SERVICE DEBUG: Final image parameter', [
                        'images_parameter' => $noneParams['--images'],
                        'total_images' => count($imagePaths)
                    ]);
                } else {
                    Log::warning('🔥🔥🔥 VIDEO SERVICE DEBUG: No image files found!', [
                        'media_type' => $request->media_type,
                        'all_files' => array_keys($request->allFiles())
                    ]);
                }
            } elseif ($request->media_type === 'mixed') {
                // Handle mixed media (images + videos)
                Log::info('🔥🔥🔥 VIDEO SERVICE DEBUG: Processing mixed media', [
                    'has_mixed_images' => $request->hasFile('mixed_images'),
                    'has_mixed_videos' => $request->hasFile('mixed_videos'),
                    'mixed_mode' => $request->input('mixed_mode', 'sequence')
                ]);

                $allMediaPaths = [];

                // Process mixed images
                if ($request->hasFile('mixed_images')) {
                    $imageFiles = $request->file('mixed_images');
                    foreach ($imageFiles as $index => $image) {
                        $imagePath = $image->store("temp/mixed_images", 'local');
                        $allMediaPaths[] = storage_path("app/{$imagePath}");
                    }
                    Log::info('🔥🔥🔥 Processed mixed images', ['count' => count($imageFiles)]);
                }

                // Process mixed videos
                if ($request->hasFile('mixed_videos')) {
                    $videoFiles = $request->file('mixed_videos');
                    foreach ($videoFiles as $index => $video) {
                        $videoPath = $video->store("temp/mixed_videos", 'local');
                        $allMediaPaths[] = storage_path("app/{$videoPath}");
                    }
                    Log::info('🔥🔥🔥 Processed mixed videos', ['count' => count($videoFiles)]);
                }

                if (!empty($allMediaPaths)) {
                    // For mixed media, we'll use the images parameter to pass all media files
                    $noneParams['--images'] = implode(',', $allMediaPaths);
                    $noneParams['--slide-duration'] = $request->input('mixed_image_duration', 3);
                    $noneParams['--slide-transition'] = $request->input('mixed_image_transition', 'fade');

                    // Add mixed media specific parameters
                    $noneParams['--mixed-mode'] = $request->input('mixed_mode', 'sequence');
                    $noneParams['--sequence-strategy'] = $request->input('sequence_strategy', 'even_distribution');
                    $noneParams['--image-display-duration'] = $request->input('image_display_duration', 2);
                    $noneParams['--image-distribution-mode'] = $request->input('image_distribution_mode', 'auto_even');

                    // Add custom timing if specified
                    if ($request->input('image_distribution_mode') === 'custom_timing' && $request->has('image_timings')) {
                        $noneParams['--image-timings'] = json_encode($request->input('image_timings'));
                    }

                    Log::info('🔥🔥🔥 VIDEO SERVICE DEBUG: Mixed media parameters', [
                        'total_media_files' => count($allMediaPaths),
                        'media_paths_preview' => array_slice($allMediaPaths, 0, 3),
                        'mixed_mode' => $noneParams['--mixed-mode'],
                        'sequence_strategy' => $noneParams['--sequence-strategy'],
                        'image_display_duration' => $noneParams['--image-display-duration'],
                        'image_distribution_mode' => $noneParams['--image-distribution-mode']
                    ]);
                }
            } elseif ($request->hasFile('product_video') || $request->hasFile('background_video')) {
                $videoFile = $request->file('product_video') ?: $request->file('background_video');
                $videoPath = $videoFile->store("temp/videos", 'local');
                $noneParams['--product-video'] = storage_path("app/{$videoPath}");
            }

            // Handle subtitle settings
            if ($request->boolean('enable_subtitle')) {
                $noneParams['--enable-subtitle'] = true;
                if ($request->subtitle_text) {
                    $noneParams['--subtitle-text'] = $request->subtitle_text;
                }
                if ($request->subtitle_position) {
                    $noneParams['--subtitle-position'] = $request->subtitle_position;
                }
                if ($request->subtitle_size) {
                    $noneParams['--subtitle-size'] = $request->subtitle_size;
                }
                if ($request->subtitle_color) {
                    $noneParams['--subtitle-color'] = $request->subtitle_color;
                }
                if ($request->subtitle_background) {
                    $noneParams['--subtitle-background'] = $request->subtitle_background;
                }
                if ($request->subtitle_font) {
                    $noneParams['--subtitle-font'] = $request->subtitle_font;
                }
            }

            $finalParams = array_merge($baseParams, $noneParams);

            // 🔥 DEBUG: Log final parameters for none platform
            Log::info('🔥🔥🔥 VIDEO SERVICE DEBUG: Final parameters for none platform', [
                'base_params' => $baseParams,
                'none_params' => $noneParams,
                'final_params' => $finalParams,
                'has_images_param' => isset($finalParams['--images']),
                'images_param_preview' => isset($finalParams['--images']) ? substr($finalParams['--images'], 0, 100) . '...' : 'NOT SET',
                'slide_duration' => $finalParams['--slide-duration'] ?? 'NOT SET',
                'slide_transition' => $finalParams['--slide-transition'] ?? 'NOT SET'
            ]);

            return $finalParams;
        } elseif ($platform === 'tiktok') {
            $tiktokParams = [
                '--voice' => $request->voice,
                '--bitrate' => $request->bitrate,
                '--speed' => $request->speed,
                '--volume' => $request->volume,
                '--media-type' => $request->media_type ?: 'video',
            ];

            // Only add script if using TTS and have TTS text
            if ($request->tts_text && $request->audio_source === 'tts') {
                $tiktokParams['--script'] = $request->tts_text;
                \Log::info('VideoGenerationService: Adding TTS script for TikTok platform', [
                    'audio_source' => $request->audio_source,
                    'has_tts_text' => !empty($request->tts_text),
                    'tts_text_preview' => substr($request->tts_text, 0, 50)
                ]);
            } else {
                \Log::info('VideoGenerationService: Skipping TTS script for TikTok platform', [
                    'audio_source' => $request->audio_source,
                    'has_tts_text' => !empty($request->tts_text),
                    'has_script_text' => !empty($request->script_text)
                ]);
            }

            // Handle audio source for TikTok
            if ($request->audio_source === 'upload' && $request->hasFile('audio_file')) {
                $audioPath = $request->file('audio_file')->store("temp/audio", 'local');
                $tiktokParams['--audio-file'] = storage_path("app/{$audioPath}");
            } elseif ($request->audio_source === 'library' && $request->library_audio_id) {
                $tiktokParams['--library-audio-id'] = $request->library_audio_id;
                // Increment usage count
                $audioLibrary = \App\Models\AudioLibrary::find($request->library_audio_id);
                if ($audioLibrary) {
                    $audioLibrary->incrementUsage();
                }
            }

            // Handle media files based on type
            $mediaType = $request->input('media_type', 'images');
            $hasVideo = $request->hasFile('product_video') || $request->hasFile('background_video') || $request->hasFile('mixed_videos');
            $hasImages = $request->hasFile('product_images') || $request->hasFile('images') || $request->hasFile('inputs.images') || $request->hasFile('mixed_images');

            // Log media type detection
            Log::info('TikTok media type detection', [
                'original_media_type' => $mediaType,
                'has_video' => $hasVideo,
                'has_images' => $hasImages,
                'video_files' => [
                    'product_video' => $request->hasFile('product_video'),
                    'background_video' => $request->hasFile('background_video'),
                    'mixed_videos' => $request->hasFile('mixed_videos'),
                ],
                'image_files' => [
                    'product_images' => $request->hasFile('product_images'),
                    'images' => $request->hasFile('images'),
                    'inputs.images' => $request->hasFile('inputs.images'),
                    'mixed_images' => $request->hasFile('mixed_images'),
                ]
            ]);

            // Determine actual content type for mixed media
            if ($mediaType === 'mixed') {
                if ($hasVideo) {
                    $actualContentType = 'video';
                } elseif ($hasImages) {
                    $actualContentType = 'images';
                } else {
                    $actualContentType = 'images'; // Default fallback
                }
            } elseif ($mediaType === 'video' && !$hasVideo) {
                // If video type but no video file, fallback to images
                $actualContentType = 'images';
            } else {
                $actualContentType = $mediaType;
            }

            Log::info('TikTok final content type', [
                'original_media_type' => $mediaType,
                'actual_content_type' => $actualContentType
            ]);

            $tiktokParams['--media-type'] = $actualContentType;

            if ($actualContentType === 'images') {
                // Check for images from different sources (template vs direct upload)
                $imageFiles = null;

                if ($request->hasFile('product_images')) {
                    $imageFiles = $request->file('product_images');
                } elseif ($request->hasFile('images')) {
                    $imageFiles = $request->file('images');
                } elseif ($request->hasFile('inputs.images')) {
                    $imageFiles = $request->file('inputs.images');
                }

                if ($imageFiles) {
                    $tiktokParams['--slide-duration'] = $request->slide_duration ?: 3;
                    $tiktokParams['--slide-transition'] = $request->slide_transition ?: 'slide';

                    // Store multiple images
                    $imagePaths = [];
                    foreach ($imageFiles as $index => $image) {
                        $imagePath = $image->store("temp/images", 'local');
                        $imagePaths[] = storage_path("app/{$imagePath}");
                    }
                    $tiktokParams['--product-images'] = implode(',', $imagePaths);
                }
            } elseif ($actualContentType === 'video' && $hasVideo) {
                $videoFile = $request->file('product_video') ?: $request->file('background_video');
                $videoPath = $videoFile->store("temp/videos", 'local');
                $tiktokParams['--product-video'] = storage_path("app/{$videoPath}");
            }

            // Handle subtitle (optional) - support both checkbox mode and template mode
            Log::info('TikTok subtitle processing', [
                'has_enable_subtitle' => $request->has('enable_subtitle'),
                'enable_subtitle' => $request->boolean('enable_subtitle'),
                'has_subtitle_text' => $request->has('subtitle_text'),
                'subtitle_text_preview' => $request->subtitle_text ? substr($request->subtitle_text, 0, 50) : 'none'
            ]);

            if (($request->boolean('enable_subtitle') && $request->subtitle_text) ||
                (!$request->has('enable_subtitle') && $request->subtitle_text)) {
                $tiktokParams['--subtitle-text'] = $request->subtitle_text;
                $tiktokParams['--subtitle-position'] = $request->subtitle_position ?: 'bottom';
                $tiktokParams['--subtitle-size'] = $request->subtitle_size ?: 24;
                $tiktokParams['--subtitle-color'] = $request->subtitle_color ?: '#FFFFFF';
                $tiktokParams['--subtitle-background'] = $request->subtitle_background ?: '#000000';
                $tiktokParams['--subtitle-font'] = $request->subtitle_font ?: 'Arial';
                $tiktokParams['--subtitle-duration'] = $request->subtitle_duration ?: 5;

                // Add timing parameters
                $tiktokParams['--subtitle-timing-mode'] = $request->subtitle_timing_mode ?: 'auto';
                $tiktokParams['--subtitle-per-image'] = $request->subtitle_per_image ?: 'auto';

                Log::info('TikTok subtitle parameters added', [
                    'subtitle_text_preview' => substr($request->subtitle_text, 0, 50),
                    'subtitle_position' => $tiktokParams['--subtitle-position'],
                    'subtitle_size' => $tiktokParams['--subtitle-size']
                ]);
                $tiktokParams['--words-per-image'] = $request->words_per_image ?: 10;
                $tiktokParams['--subtitle-delay'] = $request->subtitle_delay ?: 0.5;
                $tiktokParams['--subtitle-fade'] = $request->subtitle_fade ?: 'in';
            }

            // Add duration control parameters
            $durationBasedOn = $request->input('duration_based_on');
            $customDuration = $request->input('custom_duration');
            $imageDuration = $request->input('image_duration');
            $syncWithAudio = $request->input('sync_with_audio');
            $slideDuration = $request->input('slide_duration');

            // ENHANCED WORKAROUND: Force custom duration for template-based generation
            if ($slideDuration == 30 && !$durationBasedOn && !$customDuration) {
                Log::info('TikTok WORKAROUND: Detected template with slide_duration=30, applying custom duration settings');
                $durationBasedOn = 'custom';
                $customDuration = 30;
                $imageDuration = 30;
            }

            // ADDITIONAL FIX: If still no duration settings but slide_duration exists, use it
            if (!$durationBasedOn && $slideDuration) {
                Log::info('TikTok ADDITIONAL FIX: Using slide_duration as custom_duration', [
                    'slide_duration' => $slideDuration
                ]);
                $durationBasedOn = 'custom';
                $customDuration = $slideDuration;
                $imageDuration = $slideDuration;
            }

            Log::info('TikTok duration parameters AFTER workaround', [
                'duration_based_on' => $durationBasedOn,
                'custom_duration' => $customDuration,
                'image_duration' => $imageDuration,
                'slide_duration' => $slideDuration,
                'workaround_1_triggered' => ($slideDuration == 30 && $durationBasedOn == 'custom'),
                'workaround_2_triggered' => (!$durationBasedOn && $slideDuration)
            ]);

            Log::info('TikTok duration parameters BEFORE workaround', [
                'duration_based_on' => $durationBasedOn,
                'custom_duration' => $customDuration,
                'image_duration' => $imageDuration,
                'sync_with_audio' => $syncWithAudio,
                'slide_duration' => $slideDuration,
                'request_all_keys' => array_keys($request->all())
            ]);

            // Force add duration parameters if they exist
            if ($durationBasedOn) {
                $tiktokParams['--duration-based-on'] = $durationBasedOn;
                Log::info('Added duration-based-on parameter', ['value' => $durationBasedOn]);
            }
            if ($customDuration) {
                $tiktokParams['--custom-duration'] = $customDuration;
                Log::info('Added custom-duration parameter', ['value' => $customDuration]);
            }
            if ($imageDuration) {
                $tiktokParams['--image-duration'] = $imageDuration;
                Log::info('Added image-duration parameter', ['value' => $imageDuration]);
            }
            if ($syncWithAudio !== null) {
                $tiktokParams['--sync-with-audio'] = $syncWithAudio ? 'true' : 'false';
                Log::info('Added sync-with-audio parameter', ['value' => $syncWithAudio]);
            }
            if ($request->has('max_duration')) {
                $tiktokParams['--max-duration'] = $request->max_duration;
            }
            if ($request->has('sync_tolerance')) {
                $tiktokParams['--sync-tolerance'] = $request->sync_tolerance;
            }

            // Handle channel settings (optional)
            $tiktokChannelId = $request->tiktok_channel_id ?: $request->channel_id;
            if ($tiktokChannelId) {
                $tiktokParams['--channel-id'] = $tiktokChannelId;
                $tiktokParams['--schedule-post'] = $request->boolean('schedule_post');
                if ($request->boolean('schedule_post')) {
                    $tiktokParams['--scheduled-date'] = $request->scheduled_date;
                    $tiktokParams['--scheduled-time'] = $request->scheduled_time;
                }
                $tiktokParams['--post-title'] = $request->post_title;
                $tiktokParams['--post-description'] = $request->post_description;
                $tiktokParams['--post-tags'] = $request->post_tags;
            }

            return array_merge($baseParams, $tiktokParams);
        } elseif ($platform === 'youtube') {
            $youtubeParams = [
                // Map UI content type to console command's expected option
                '--media-type' => $request->video_content_type ?: ($request->media_type ?: 'images'),
                '--image-duration' => $request->image_duration ?: 3,
            ];

            // Handle audio source
            if ($request->audio_source === 'tts' && $request->tts_text) {
                $youtubeParams['--text-content'] = $request->tts_text;
                $youtubeParams['--voice'] = $request->tts_voice;
                $youtubeParams['--bitrate'] = $request->tts_bitrate;
                $youtubeParams['--speed'] = $request->tts_speed;
                $youtubeParams['--volume'] = $request->tts_volume;
            } elseif ($request->audio_source === 'upload' && $request->hasFile('audio_file')) {
                $audioPath = $request->file('audio_file')->store("temp/audio", 'local');
                $youtubeParams['--audio-file'] = storage_path("app/{$audioPath}");
            } elseif ($request->audio_source === 'library' && $request->library_audio_id) {
                $youtubeParams['--library-audio-id'] = $request->library_audio_id;
                // Increment usage count
                $audioLibrary = \App\Models\AudioLibrary::find($request->library_audio_id);
                if ($audioLibrary) {
                    $audioLibrary->incrementUsage();
                }
            }

            // Handle video content - check media_type instead of video_content_type
            if ($request->media_type === 'images' || $request->video_content_type === 'images') {
                // Check for images from different sources (template vs direct upload)
                $imageFiles = null;

                if ($request->hasFile('images')) {
                    $imageFiles = $request->file('images');
                } elseif ($request->hasFile('product_images')) {
                    $imageFiles = $request->file('product_images');
                } elseif ($request->hasFile('inputs.images')) {
                    $imageFiles = $request->file('inputs.images');
                }

                if ($imageFiles) {
                    $imagePaths = [];
                    foreach ($imageFiles as $image) {
                        $imagePath = $image->store("temp/images", 'local');
                        $imagePaths[] = storage_path("app/{$imagePath}");
                    }
                    $youtubeParams['--images'] = implode(',', $imagePaths);

                    Log::info('🔥🔥🔥 YOUTUBE DEBUG: Images processed for command', [
                        'images_count' => count($imageFiles),
                        'image_paths' => $imagePaths,
                        'media_type' => $request->media_type,
                        'video_content_type' => $request->video_content_type
                    ]);
                } else {
                    Log::warning('🔥🔥🔥 YOUTUBE DEBUG: No image files found', [
                        'media_type' => $request->media_type,
                        'video_content_type' => $request->video_content_type,
                        'has_images' => $request->hasFile('images'),
                        'has_product_images' => $request->hasFile('product_images'),
                        'has_inputs_images' => $request->hasFile('inputs.images')
                    ]);
                }
            } elseif ($request->hasFile('background_video') || $request->hasFile('product_video')) {
                $videoFile = $request->file('background_video') ?: $request->file('product_video');
                $videoPath = $videoFile->store("temp/videos", 'local');
                $youtubeParams['--background-video'] = storage_path("app/{$videoPath}");
            }

            // Handle subtitle (optional)
            if ($request->boolean('enable_subtitle') && $request->subtitle_text) {
                $youtubeParams['--subtitle-text'] = $request->subtitle_text;
                $youtubeParams['--subtitle-position'] = $request->subtitle_position ?: 'bottom';
                $youtubeParams['--subtitle-size'] = $request->subtitle_size ?: 24;
                $youtubeParams['--subtitle-color'] = $request->subtitle_color ?: '#FFFFFF';
                $youtubeParams['--subtitle-background'] = $request->subtitle_background ?: '#000000';
                $youtubeParams['--subtitle-font'] = $request->subtitle_font ?: 'Arial';
                $youtubeParams['--subtitle-duration'] = $request->subtitle_duration ?: 5;

                // Add timing parameters
                $youtubeParams['--subtitle-timing-mode'] = $request->subtitle_timing_mode ?: 'auto';
                $youtubeParams['--subtitle-per-image'] = $request->subtitle_per_image ?: 'auto';
                $youtubeParams['--words-per-image'] = $request->words_per_image ?: 10;
                $youtubeParams['--subtitle-delay'] = $request->subtitle_delay ?: 0.5;
                $youtubeParams['--subtitle-fade'] = $request->subtitle_fade ?: 'in';

                // Add duration control parameters
                if ($request->has('duration_based_on')) {
                    $youtubeParams['--duration-based-on'] = $request->duration_based_on;
                }
                if ($request->has('custom_duration')) {
                    $youtubeParams['--custom-duration'] = $request->custom_duration;
                }
                if ($request->has('sync_with_audio')) {
                    $youtubeParams['--sync-with-audio'] = $request->sync_with_audio ? 'true' : 'false';
                }
                if ($request->has('max_duration')) {
                    $youtubeParams['--max-duration'] = $request->max_duration;
                }
                if ($request->has('sync_tolerance')) {
                    $youtubeParams['--sync-tolerance'] = $request->sync_tolerance;
                }
            }

            // Handle channel settings and publishing options
            $youtubeChannelId = $request->youtube_channel_id ?: $request->channel_id;
            if ($youtubeChannelId) {
                $youtubeParams['--channel-id'] = $youtubeChannelId;

                // Publishing mode handling
                $publishMode = $request->input('youtube_publish_mode', 'draft');

                switch ($publishMode) {
                    case 'auto':
                        $youtubeParams['--auto-publish'] = 'true';
                        $youtubeParams['--schedule-post'] = 'false';
                        break;
                    case 'schedule':
                        $youtubeParams['--auto-publish'] = 'false';
                        $youtubeParams['--schedule-post'] = 'true';
                        $youtubeParams['--scheduled-date'] = $request->youtube_scheduled_date;
                        $youtubeParams['--scheduled-time'] = $request->youtube_scheduled_time;
                        break;
                    case 'draft':
                    default:
                        $youtubeParams['--auto-publish'] = 'false';
                        $youtubeParams['--schedule-post'] = 'false';
                        break;
                }

                // Video metadata
                $youtubeParams['--post-title'] = $request->video_title ?: $request->post_title;
                $youtubeParams['--post-description'] = $request->video_description ?: $request->post_description;
                $youtubeParams['--post-tags'] = $request->youtube_tags ?: $request->post_tags;
                $youtubeParams['--post-privacy'] = $request->youtube_privacy ?: 'private';

                Log::info('🔥🔥🔥 YOUTUBE DEBUG: Channel settings configured', [
                    'channel_id' => $youtubeChannelId,
                    'publish_mode' => $publishMode,
                    'auto_publish' => $youtubeParams['--auto-publish'],
                    'schedule_post' => $youtubeParams['--schedule-post'],
                    'video_title' => $request->video_title,
                    'video_description' => $request->video_description
                ]);
            }

            return array_merge($baseParams, $youtubeParams);
        }
    }

    /**
     * Prepare batch data
     */
    private function prepareBatchData($platform, Request $request)
    {
        $batchMode = $request->input('batch_mode', 'multiple_content');
        $batchData = [];

        if ($batchMode === 'template') {
            // Template mode: split text by lines
            $templateTexts = $request->input('template_texts', '');
            $lines = array_filter(explode("\n", $templateTexts));

            foreach ($lines as $index => $line) {
                $line = trim($line);
                if (!empty($line)) {
                    $outputName = $request->boolean('template_auto_name')
                        ? "video_" . ($index + 1)
                        : null;

                    $batchData[] = [
                        'text_content' => $line,
                        'audio_source' => 'tts', // Default to TTS for template mode
                        'output_name' => $outputName,
                        'subtitle_text' => $line, // Use same text as subtitle
                    ];
                }
            }
        } else {
            // Multiple content mode: use individual inputs
            $batchTexts = $request->input('batch_texts', []);
            $batchAudioFiles = $request->file('batch_audio_files') ?: [];
            $batchImages = $request->file('batch_images') ?: [];
            $batchBackgroundVideos = $request->file('batch_background_videos') ?: [];
            $batchSubtitles = $request->input('batch_subtitles', []);
            $batchOutputNames = $request->input('batch_output_names', []);
            $batchDurations = $request->input('batch_durations', []);
            $batchTransitions = $request->input('batch_transitions', []);
            $batchVolumes = $request->input('batch_volumes', []);

            // Get the maximum count from all arrays
            $maxCount = max(
                count($batchTexts),
                count($batchAudioFiles),
                count($batchImages),
                count($batchBackgroundVideos),
                count($batchSubtitles),
                count($batchOutputNames)
            );

            for ($i = 1; $i <= $maxCount; $i++) {
                $hasContent = !empty($batchTexts[$i]) ||
                             !empty($batchAudioFiles[$i]) ||
                             !empty($batchImages[$i]) ||
                             !empty($batchBackgroundVideos[$i]);

                if ($hasContent) {
                    $audioSource = 'none';
                    if (!empty($batchTexts[$i])) {
                        $audioSource = 'tts';
                    } elseif (!empty($batchAudioFiles[$i])) {
                        $audioSource = 'upload';
                    }

                    $batchData[] = [
                        'text_content' => $batchTexts[$i] ?? null,
                        'audio_source' => $audioSource,
                        'audio_file' => $batchAudioFiles[$i] ?? null,
                        'images' => $batchImages[$i] ?? null,
                        'background_video' => $batchBackgroundVideos[$i] ?? null,
                        'subtitle_text' => $batchSubtitles[$i] ?? null,
                        'output_name' => $batchOutputNames[$i] ?? "video_$i",
                        'duration' => $batchDurations[$i] ?? null,
                        'transition' => $batchTransitions[$i] ?? 'fade',
                        'volume' => $batchVolumes[$i] ?? 100,
                    ];
                }
            }
        }

        return $batchData;
    }

    /**
     * Prepare parameters for batch video
     */
    private function prepareBatchVideoParameters($platform, Request $request, $videoData, $tempDir, $index)
    {
        $outputName = $videoData['output_name'] ?: $this->generateDefaultBatchVideoName($platform, $request, $index);
        if (!Str::endsWith($outputName, '.mp4')) {
            $outputName .= '.mp4';
        }

        $baseParams = [
            '--output' => $outputName,
            '--temp-dir' => $tempDir,
            '--task-id' => uniqid('batch_video_', true),
        ];

        // Add subtitle parameters if enabled
        if ($request->boolean('enable_subtitle') && !empty($videoData['subtitle_text'])) {
            $baseParams = array_merge($baseParams, [
                '--subtitle-text' => $videoData['subtitle_text'],
                '--subtitle-position' => $request->subtitle_position ?: 'bottom',
                '--subtitle-size' => $request->subtitle_size ?: 24,
                '--subtitle-color' => $request->subtitle_color ?: '#FFFFFF',
                '--subtitle-background' => $request->subtitle_background ?: '#000000',
                '--subtitle-font' => $request->subtitle_font ?: 'Arial',
                '--subtitle-duration' => $request->subtitle_duration ?: 5,
            ]);
        }

        if ($platform === 'none') {
            // Parameters for videos without channel publishing
            $params = array_merge($baseParams, [
                '--platform' => 'none',
                '--media-type' => $request->media_type ?: 'images',
            ]);

            // Only add script if using TTS and have TTS text
            if (!empty($videoData['script']) && $request->audio_source === 'tts') {
                $params['--script'] = $videoData['script'];
            }

            // Video settings from none-specific fields
            if ($request->none_resolution) {
                $params['--resolution'] = $request->none_resolution;
            }
            if ($request->none_fps) {
                $params['--fps'] = $request->none_fps;
            }
            if ($request->none_quality) {
                $params['--quality'] = $request->none_quality;
            }

            // Handle file uploads
            if ($videoData['product_video']) {
                $videoPath = $this->saveUploadedFile($videoData['product_video'], $tempDir, 'product_video.' . $videoData['product_video']->getClientOriginalExtension());
                $params['--product-video'] = $videoPath;
            }

            if ($videoData['product_image']) {
                $imagePath = $this->saveUploadedFile($videoData['product_image'], $tempDir, 'product_image.' . $videoData['product_image']->getClientOriginalExtension());
                $params['--product-image'] = $imagePath;
            }

            return $params;
        } elseif ($platform === 'tiktok') {
            $params = array_merge($baseParams, [
                '--voice' => $request->voice,
                '--bitrate' => $request->bitrate,
                '--speed' => $request->speed,
                '--volume' => $request->volume,
            ]);

            // Only add script if using TTS and have TTS text
            if (!empty($videoData['script']) && $request->audio_source === 'tts') {
                $params['--script'] = $videoData['script'];
            }

            // Handle audio source for TikTok batch
            if ($videoData['audio_file']) {
                $audioPath = $this->saveUploadedFile($videoData['audio_file'], $tempDir, 'audio.' . $videoData['audio_file']->getClientOriginalExtension());
                $params['--audio-file'] = $audioPath;
            } elseif ($videoData['audio_source'] === 'library' && !empty($videoData['library_audio_id'])) {
                $params['--library-audio-id'] = $videoData['library_audio_id'];
                // Increment usage count
                $audioLibrary = \App\Models\AudioLibrary::find($videoData['library_audio_id']);
                if ($audioLibrary) {
                    $audioLibrary->incrementUsage();
                }
            }

            // Handle file uploads
            if ($videoData['product_video']) {
                $videoPath = $this->saveUploadedFile($videoData['product_video'], $tempDir, 'product_video.' . $videoData['product_video']->getClientOriginalExtension());
                $params['--product-video'] = $videoPath;
            }

            if ($videoData['product_image']) {
                $imagePath = $this->saveUploadedFile($videoData['product_image'], $tempDir, 'product_image.' . $videoData['product_image']->getClientOriginalExtension());
                $params['--product-image'] = $imagePath;
            }

            return $params;
        } else { // youtube
            $params = array_merge($baseParams, [
                '--media-type' => $videoData['video_content_type'] ?? ($request->media_type ?: 'images'),
                '--image-duration' => $request->image_duration ?: 3,
            ]);

            // Handle audio
            if ($videoData['audio_source'] === 'text') {
                $params['--text'] = $videoData['text_content'];
                $params['--voice'] = $request->voice;
                $params['--bitrate'] = $request->bitrate;
                $params['--speed'] = $request->speed;
                $params['--volume'] = $request->volume;
            } elseif ($videoData['audio_file']) {
                $audioPath = $this->saveUploadedFile($videoData['audio_file'], $tempDir, 'audio.' . $videoData['audio_file']->getClientOriginalExtension());
                $params['--audio-file'] = $audioPath;
            } elseif ($videoData['audio_source'] === 'library' && !empty($videoData['library_audio_id'])) {
                $params['--library-audio-id'] = $videoData['library_audio_id'];
                // Increment usage count
                $audioLibrary = \App\Models\AudioLibrary::find($videoData['library_audio_id']);
                if ($audioLibrary) {
                    $audioLibrary->incrementUsage();
                }
            }

            // Handle images and videos
            if ($videoData['images']) {
                $imagePaths = [];
                foreach ($videoData['images'] as $idx => $image) {
                    $imagePath = $this->saveUploadedFile($image, $tempDir, "image_{$idx}." . $image->getClientOriginalExtension());
                    $imagePaths[] = $imagePath;
                }
                $params['--images'] = implode(',', $imagePaths);
            }

            if ($videoData['background_video']) {
                $videoPath = $this->saveUploadedFile($videoData['background_video'], $tempDir, 'background_video.' . $videoData['background_video']->getClientOriginalExtension());
                $params['--background-video'] = $videoPath;
            }

            return $params;
        }
    }

    /**
     * Save uploaded file to temp directory
     */
    private function saveUploadedFile($file, $tempDir, $filename)
    {
        $path = $tempDir . '/' . $filename;
        $file->move($tempDir, $filename);
        return $path;
    }

    /**
     * Estimate processing time based on content
     */
    private function estimateProcessingTime($platform, Request $request, $videoData = null)
    {
        $baseTime = 180; // 3 minutes base time
        
        if ($platform === 'none') {
            // For videos without channel publishing, use similar estimation as TikTok
            $script = $videoData['script'] ?? $request->script_text ?? '';
            $scriptLength = strlen($script);

            // Estimate based on script length (roughly 1 minute per 100 characters)
            $estimatedTime = $baseTime + ($scriptLength / 100) * 60;

            // Add time for subtitle processing
            if ($request->boolean('enable_subtitle')) {
                $estimatedTime += 30;
            }

            // Add time based on media type
            $mediaType = $request->media_type ?: 'images';
            if ($mediaType === 'video') {
                $estimatedTime += 60; // Extra time for video processing
            }

        } elseif ($platform === 'tiktok') {
            $script = $videoData['script'] ?? $request->script_text ?? '';
            $scriptLength = strlen($script);

            // Estimate based on script length (roughly 1 minute per 100 characters)
            $estimatedTime = $baseTime + ($scriptLength / 100) * 60;

            // Add time for subtitle processing
            if ($request->boolean('enable_subtitle')) {
                $estimatedTime += 30;
            }

        } else { // youtube
            $estimatedTime = $baseTime;
            
            // Add time based on content type
            $contentType = $videoData['video_content_type'] ?? $request->video_content_type ?? 'images';
            
            switch ($contentType) {
                case 'images':
                    $estimatedTime += 120; // 2 minutes for image processing
                    break;
                case 'video':
                    $estimatedTime += 180; // 3 minutes for video processing
                    break;
                case 'mixed':
                    $estimatedTime += 240; // 4 minutes for mixed content
                    break;
            }
            
            // Add time for subtitle processing
            if ($request->boolean('enable_subtitle')) {
                $estimatedTime += 30;
            }
        }
        
        return min(1800, max(120, $estimatedTime)); // Between 2 minutes and 30 minutes
    }

    /**
     * Calculate task priority
     */
    private function calculatePriority(Request $request)
    {
        // Default normal priority
        $priority = VideoGenerationTask::PRIORITY_NORMAL;
        
        // Increase priority for single videos
        if ($request->creation_mode === 'single') {
            $priority = VideoGenerationTask::PRIORITY_HIGH;
        }
        
        return $priority;
    }

    /**
     * Get queue status
     */
    public function getQueueStatus()
    {
        return [
            'pending' => VideoGenerationTask::pending()->count(),
            'processing' => VideoGenerationTask::processing()->count(),
            'completed_today' => VideoGenerationTask::completed()
                                ->whereDate('completed_at', today())
                                ->count(),
            'failed_today' => VideoGenerationTask::failed()
                            ->whereDate('completed_at', today())
                            ->count(),
        ];
    }

    /**
     * Get user's tasks
     */
    public function getUserTasks($userId, $limit = 10)
    {
        return VideoGenerationTask::forUser($userId)
                                 ->orderBy('created_at', 'desc')
                                 ->limit($limit)
                                 ->get();
    }

    /**
     * Generate default video name with date and sequence number
     */
    private function generateDefaultVideoName($platform, Request $request)
    {
        $date = date('Y-m-d');
        $time = date('H-i');

        // Get today's video count for this platform and user
        $todayCount = VideoGenerationTask::where('user_id', auth()->id())
                                        ->where('platform', $platform)
                                        ->whereDate('created_at', today())
                                        ->count();

        $sequence = str_pad($todayCount + 1, 3, '0', STR_PAD_LEFT);

        // Create descriptive name based on content
        $baseName = $this->generateDescriptiveName($platform, $request);

        return "{$baseName}_{$date}_{$time}_{$sequence}";
    }

    /**
     * Generate descriptive name based on content
     */
    private function generateDescriptiveName($platform, Request $request)
    {
        if ($platform === 'tiktok') {
            // For TikTok, use media type
            $mediaType = $request->media_type === 'images' ? 'slide' : 'video';
            return "tiktok_{$mediaType}";
        } else {
            // For YouTube, use content type
            $contentType = $request->video_content_type ?: 'video';
            $audioSource = $request->audio_source === 'text' ? 'tts' : 'audio';
            return "youtube_{$contentType}_{$audioSource}";
        }
    }

    /**
     * Generate default batch video name with sequence
     */
    private function generateDefaultBatchVideoName($platform, Request $request, $index)
    {
        $date = date('Y-m-d');
        $time = date('H-i');

        // Get today's video count for this platform and user
        $todayCount = VideoGenerationTask::where('user_id', auth()->id())
                                        ->where('platform', $platform)
                                        ->whereDate('created_at', today())
                                        ->count();

        $batchSequence = str_pad($todayCount + 1, 3, '0', STR_PAD_LEFT);
        $videoSequence = str_pad($index + 1, 2, '0', STR_PAD_LEFT);

        // Create descriptive name based on content
        $baseName = $this->generateDescriptiveName($platform, $request);

        return "{$baseName}_batch_{$date}_{$time}_{$batchSequence}_{$videoSequence}";
    }

    /**
     * Apply image order based on user input
     */
    private function applyImageOrder($imagePaths, $request)
    {
        // Check if image order mapping is provided
        $orderMapping = $request->input('image_order_mapping');
        $imageOrders = $request->input('image_orders');

        Log::info('🔄 VIDEO GEN: Input data received', [
            'has_image_order_mapping' => !empty($orderMapping),
            'has_image_orders' => !empty($imageOrders),
            'image_order_mapping' => $orderMapping,
            'image_orders' => $imageOrders
        ]);

        // ALWAYS prioritize image_order_mapping over image_orders
        if (!$orderMapping && !$imageOrders) {
            // No custom order specified, return original order
            Log::info('🔄 No image order specified, using original order', [
                'image_count' => count($imagePaths),
                'image_order_mapping' => $orderMapping,
                'image_orders' => $imageOrders
            ]);
            return $imagePaths;
        }

        // If we have image_orders array AND no image_order_mapping, convert it to mapping
        if (!$orderMapping && $imageOrders && is_array($imageOrders)) {
            Log::info('🔄 Converting image_orders array to mapping', [
                'image_orders' => $imageOrders,
                'image_count' => count($imagePaths)
            ]);

            // Create array of [originalIndex, orderValue] pairs
            $orderPairs = [];
            foreach ($imageOrders as $originalIndex => $orderValue) {
                $orderPairs[] = [
                    'originalIndex' => $originalIndex,
                    'orderValue' => intval($orderValue)
                ];
            }

            // Sort by order value (smaller order value = earlier position)
            usort($orderPairs, function($a, $b) {
                return $a['orderValue'] - $b['orderValue'];
            });

            // Create mapping: originalIndex => orderValue (NOT position!)
            $orderMapping = [];
            foreach ($imageOrders as $originalIndex => $orderValue) {
                // Force string key to create associative array
                $orderMapping[(string)$originalIndex] = intval($orderValue);
            }
            // Force JSON to be object by casting to object
            $orderMapping = json_encode((object)$orderMapping);

            Log::info('🔄 Created order mapping from image_orders', [
                'original_image_orders' => $imageOrders,
                'order_pairs_sorted' => $orderPairs,
                'created_mapping' => $orderMapping
            ]);
        }

        if (!$orderMapping) {
            // Still no valid order mapping
            return $imagePaths;
        }

        try {
            $orderMap = json_decode($orderMapping, true);

            if (!is_array($orderMap)) {
                Log::warning('Invalid image order mapping format', ['mapping' => $orderMapping]);
                return $imagePaths;
            }

            // Create new array with reordered images
            $reorderedPaths = [];
            $originalCount = count($imagePaths);

            // Convert order value mapping to position mapping
            // orderMap now contains: originalIndex => orderValue
            $orderPairs = [];
            foreach ($orderMap as $originalIndex => $orderValue) {
                if (isset($imagePaths[$originalIndex])) {
                    $orderPairs[] = [
                        'originalIndex' => $originalIndex,
                        'orderValue' => intval($orderValue),
                        'imagePath' => $imagePaths[$originalIndex]
                    ];
                }
            }

            // Sort by order value (smaller order value = earlier position)
            usort($orderPairs, function($a, $b) {
                return $a['orderValue'] - $b['orderValue'];
            });

            // Create final reordered array
            $reorderedPaths = array_map(function($pair) {
                return $pair['imagePath'];
            }, $orderPairs);

            // Add any missing images that weren't in the mapping
            for ($i = 0; $i < $originalCount; $i++) {
                if (!in_array($imagePaths[$i], $reorderedPaths)) {
                    $reorderedPaths[] = $imagePaths[$i];
                }
            }

            Log::info('🔄 Applied image reordering', [
                'original_count' => $originalCount,
                'reordered_count' => count($reorderedPaths),
                'order_mapping' => $orderMap,
                'original_paths' => array_map('basename', $imagePaths),
                'reordered_paths' => array_map('basename', $reorderedPaths)
            ]);

            return $reorderedPaths;

        } catch (\Exception $e) {
            Log::error('Failed to apply image order', [
                'error' => $e->getMessage(),
                'mapping' => $orderMapping
            ]);

            // Return original order on error
            return $imagePaths;
        }
    }

    /**
     * Reorder array based on the same order mapping used for images
     */
    private function reorderArrayByMapping($array, $orderMapping)
    {
        if (empty($array) || !is_array($array) || empty($orderMapping)) {
            return $array;
        }

        try {
            $orderMap = is_string($orderMapping) ? json_decode($orderMapping, true) : $orderMapping;

            if (!is_array($orderMap)) {
                return $array;
            }

            // Convert order value mapping to position mapping
            $orderPairs = [];
            foreach ($orderMap as $originalIndex => $orderValue) {
                if (isset($array[$originalIndex])) {
                    $orderPairs[] = [
                        'originalIndex' => $originalIndex,
                        'orderValue' => intval($orderValue),
                        'arrayValue' => $array[$originalIndex]
                    ];
                }
            }

            // Sort by order value (smaller order value = earlier position)
            usort($orderPairs, function($a, $b) {
                return $a['orderValue'] - $b['orderValue'];
            });

            // Create final reordered array
            $reorderedArray = array_map(function($pair) {
                return $pair['arrayValue'];
            }, $orderPairs);

            // Add any missing values that weren't in the mapping
            for ($i = 0; $i < count($array); $i++) {
                if (!in_array($array[$i], $reorderedArray)) {
                    $reorderedArray[] = $array[$i];
                }
            }

            return $reorderedArray;

        } catch (\Exception $e) {
            Log::error('Failed to reorder array by mapping', [
                'error' => $e->getMessage(),
                'array_count' => count($array),
                'mapping' => $orderMapping
            ]);
            return $array;
        }
    }
}
