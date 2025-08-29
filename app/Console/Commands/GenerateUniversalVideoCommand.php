<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use App\Services\VideoSubtitleService;

class GenerateUniversalVideoCommand extends Command
{
    /**
     * The name and signature of the console command.
     * Updated to include image-durations parameter
     */
    protected $signature = 'video:generate
                            {--platform= : Platform (tiktok/youtube/none)}
                            {--output= : Output filename}
                            {--temp-dir= : Temporary directory}
                            {--task-id= : Task ID}
                            {--voice= : TTS voice}
                            {--bitrate= : Audio bitrate}
                            {--speed= : TTS speed}
                            {--volume= : Audio volume}
                            {--script= : TTS script text}
                            {--media-type= : Media type}
                            {--library-audio-id= : Library audio ID}
                            {--background-audio-id= : Background music library ID}
                            {--background-audio-volume= : Background music volume (0-100)}
                            {--enable-logo : Enable logo overlay}
                            {--logo-source= : Logo source (library/upload)}
                            {--selected-logo= : Selected logo filename}
                            {--logo-file= : Uploaded logo file path}
                            {--logo-position= : Logo position}
                            {--logo-size= : Logo size}
                            {--logo-width= : Custom logo width}
                            {--logo-height= : Custom logo height}
                            {--logo-opacity= : Logo opacity}
                            {--logo-margin= : Logo margin from edges}
                            {--logo-duration= : Logo duration}
                            {--logo-start-time= : Logo start time}
                            {--logo-end-time= : Logo end time}
                            {--slide-duration= : Slide duration}
                            {--slide-transition= : Slide transition}
                            {--images= : Comma-separated list of image paths}
                            {--image-durations= : JSON string of individual image durations}
                            {--image-transitions= : JSON string of individual image transitions}
                            {--image-order-mapping= : JSON string mapping original index to order value}
                            {--product-images= : Product images}
                            {--product-video= : Product video file path}
                            {--mixed-mode= : Mixed media mode (sequence/overlay/split)}
                            {--sequence-strategy= : Sequence strategy (even_distribution/alternating)}
                            {--image-display-duration= : Duration for each image display (seconds)}
                            {--image-distribution-mode= : Image distribution mode (auto_even/custom_timing)}
                            {--image-timings= : Custom timing for images (JSON array)}
                            {--enable-subtitle : Enable subtitle}
                            {--subtitle-text= : Subtitle text}
                            {--subtitle-position= : Subtitle position}
                            {--subtitle-size= : Subtitle size}
                            {--subtitle-color= : Subtitle color}
                            {--subtitle-background= : Subtitle background}
                            {--subtitle-font= : Subtitle font}
                            {--subtitle-duration= : Subtitle duration}
                            {--subtitle-timing-mode= : Subtitle timing mode}
                            {--subtitle-per-image= : Subtitle per image}
                            {--words-per-image= : Words per image}
                            {--subtitle-delay= : Subtitle delay}
                            {--subtitle-fade= : Subtitle fade}
                            {--duration-based-on= : Duration based on}
                            {--custom-duration= : Custom duration}
                            {--image-duration= : Image duration}
                            {--sync-with-audio= : Sync with audio}
                            {--max-duration= : Max duration}
                            {--resolution= : Video resolution (e.g., 1920x1080)}
                            {--fps= : Video FPS}
                            {--quality= : Video quality}';

    /**
     * The console command description.
     */
    protected $description = 'Generate universal video with Vietnamese subtitle support';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('🔥🔥🔥 FORCE OVERRIDE COMMAND: Starting universal video generation 🔥🔥🔥', [
            'command_file' => __FILE__,
            'timestamp' => now()->toDateTimeString(),
            'platform' => $this->option('platform'),
            'all_options' => $this->options()
        ]);

        try {
            $tempDir = $this->option('temp-dir');
            $outputFilename = $this->option('output');

            // Create temp directory if not provided
            if (empty($tempDir)) {
                $tempDir = sys_get_temp_dir() . '/video_generation_' . uniqid();
                // Ensure the temp directory is created
                if (!File::isDirectory($tempDir)) {
                    File::makeDirectory($tempDir, 0755, true);
                }
            }

            Log::info('🔥🔥🔥 FORCE OVERRIDE: Initialization complete', [
                'temp_dir' => $tempDir,
                'output_filename' => $outputFilename
            ]);
            
            // Step 1: Create base video
            $baseVideoPath = $this->createBaseVideo($tempDir);
            
            Log::info('🔥🔥🔥 FORCE OVERRIDE: Base video created', [
                'video_path' => $baseVideoPath
            ]);
            
            // Step 2: Handle audio (TTS script or library audio)
            $scriptText = $this->option('script');
            $libraryAudioId = $this->option('library-audio-id');

            // Ensure scriptText is a string
            if ($scriptText && !is_string($scriptText)) {
                $scriptText = (string) $scriptText;
            }

            if (!empty($scriptText)) {
                Log::info('🔥🔥🔥 FORCE OVERRIDE: Processing TTS script', [
                    'script_text_preview' => substr($scriptText, 0, 50),
                    'script_text_length' => strlen($scriptText)
                ]);

                // Create TTS audio
                $audioPath = $this->createTTSAudio($scriptText, $tempDir);
                if ($audioPath) {
                    // Merge audio with video (TTS is main audio, should not loop)
                    $baseVideoPath = $this->mergeAudioWithVideo($baseVideoPath, $audioPath, $tempDir, 'main');
                    Log::info('🔥🔥🔥 FORCE OVERRIDE: TTS audio merged with video', [
                        'audio_path' => $audioPath,
                        'final_video_path' => $baseVideoPath
                    ]);
                }
            } elseif (!empty($libraryAudioId)) {
                Log::info('🔥🔥🔥 FORCE OVERRIDE: Processing library audio', [
                    'library_audio_id' => $libraryAudioId
                ]);

                // Get library audio file
                $audioPath = $this->getLibraryAudio($libraryAudioId, $tempDir);
                if ($audioPath) {
                    // Determine audio type based on library audio category
                    $audioType = $this->determineLibraryAudioType($libraryAudioId);
                    // Merge audio with video
                    $baseVideoPath = $this->mergeAudioWithVideo($baseVideoPath, $audioPath, $tempDir, $audioType);
                    Log::info('🔥🔥🔥 FORCE OVERRIDE: Library audio merged with video', [
                        'audio_path' => $audioPath,
                        'final_video_path' => $baseVideoPath,
                        'audio_type' => $audioType
                    ]);
                }
            } else {
                Log::info('🔥🔥🔥 FORCE OVERRIDE: No main audio source provided', [
                    'has_script' => !empty($scriptText),
                    'has_library_audio' => !empty($libraryAudioId)
                ]);
            }

            // Step 2.5: Add background music if provided
            $backgroundAudioId = $this->option('background-audio-id');
            if (!empty($backgroundAudioId)) {
                Log::info('🔥🔥🔥 FORCE OVERRIDE: Processing background music', [
                    'background_audio_id' => $backgroundAudioId
                ]);

                $backgroundAudioPath = $this->getLibraryAudio($backgroundAudioId, $tempDir);
                if ($backgroundAudioPath) {
                    $backgroundVolume = floatval($this->option('background-audio-volume') ?: 30) / 100; // Convert percentage to decimal
                    $baseVideoPath = $this->addBackgroundMusic($baseVideoPath, $backgroundAudioPath, $tempDir, $backgroundVolume);
                    Log::info('🔥🔥🔥 FORCE OVERRIDE: Background music added to video', [
                        'background_audio_path' => $backgroundAudioPath,
                        'background_volume' => $backgroundVolume,
                        'final_video_path' => $baseVideoPath
                    ]);
                }
            }

            // Step 3: Add Vietnamese subtitle if provided
            $enableSubtitle = $this->option('enable-subtitle');
            $subtitleText = $this->option('subtitle-text');

            // If subtitle is enabled but no text provided, use script text as subtitle
            if ($enableSubtitle && empty($subtitleText) && !empty($scriptText)) {
                // Fix encoding issues with Vietnamese text
                $subtitleText = mb_convert_encoding($scriptText, 'UTF-8', 'UTF-8');
                $subtitleText = preg_replace('/[^\x{0000}-\x{FFFF}]/u', '', $subtitleText); // Remove invalid UTF-8 chars

                Log::info('🔥🔥🔥 FORCE OVERRIDE: Using script text as subtitle', [
                    'script_text_preview' => substr($scriptText, 0, 50),
                    'script_text_length' => strlen($scriptText),
                    'subtitle_text_preview' => substr($subtitleText, 0, 50),
                    'subtitle_text_length' => strlen($subtitleText)
                ]);
            }

            Log::info('🔥🔥🔥 FORCE OVERRIDE: About to process subtitle options 🔥🔥🔥', [
                'enable_subtitle' => $enableSubtitle,
                'subtitle_text_exists' => !empty($subtitleText),
                'subtitle_text_preview' => substr($subtitleText ?? '', 0, 50),
                'subtitle_text_length' => strlen($subtitleText ?? ''),
                'used_script_as_subtitle' => $enableSubtitle && empty($this->option('subtitle-text')) && !empty($scriptText),
                'file_name' => __FILE__,
                'line_number' => __LINE__,
                'timestamp' => now()->toDateTimeString()
            ]);

            $finalVideoPath = $baseVideoPath;

            if ($enableSubtitle && !empty($subtitleText)) {
                Log::info('🚨 FORCE OVERRIDE: Using Vietnamese subtitle service directly', [
                    'video_path' => $baseVideoPath,
                    'subtitle_text_preview' => substr($subtitleText, 0, 50),
                    'method' => 'VideoSubtitleService::createVideoWithVietnameseSubtitle',
                    'subtitle_options_input' => [
                        'position' => $this->option('subtitle-position'),
                        'size' => $this->option('subtitle-size'),
                        'color' => $this->option('subtitle-color'),
                        'background' => $this->option('subtitle-background')
                    ]
                ]);
                
                Log::info('🚨 FORCE OVERRIDE: Calling Vietnamese subtitle service directly');
                
                $outputVideoPath = $tempDir . '/video_with_vietnamese_subtitle.mp4';
                
                Log::info('🎯 FORCE OVERRIDE: Creating Vietnamese subtitle directly', [
                    'input_video' => $baseVideoPath,
                    'output_video' => $outputVideoPath,
                    'subtitle_text' => substr($subtitleText, 0, 100)
                ]);
                
                $videoSubtitleService = app(VideoSubtitleService::class);
                
                $subtitleOptions = [
                    'output_path' => $outputVideoPath,
                    'font_size' => $this->option('subtitle-size') ?: 'medium',
                    'font_color' => $this->option('subtitle-color') ?: 'white',
                    'background_color' => $this->option('subtitle-background') ?: 'none',
                    'position' => $this->option('subtitle-position') ?: 'bottom',
                    'font_name' => $this->option('subtitle-font') ?: 'Arial Unicode MS',
                    'hard_subtitle' => true,
                    'encoding' => 'UTF-8'
                ];
                
                // Get video duration for subtitle sync
                $videoDuration = $this->getVideoDuration($baseVideoPath);

                Log::info('🔥🔥🔥 FORCE OVERRIDE: Passing video duration to subtitle service', [
                    'video_path' => $baseVideoPath,
                    'video_duration' => $videoDuration,
                    'duration_based_on' => $this->option('duration-based-on') ?: 'images'
                ]);

                $result = $videoSubtitleService->createVideoWithVietnameseSubtitle(
                    $baseVideoPath,
                    $subtitleText,
                    $videoDuration, // Pass actual video duration
                    $subtitleOptions
                );

                // Ensure result is an array
                if (!is_array($result)) {
                    Log::error('🔥🔥🔥 FORCE OVERRIDE: Subtitle service returned non-array result', [
                        'result_type' => gettype($result),
                        'result_value' => $result
                    ]);
                    $result = [
                        'success' => false,
                        'error' => 'Subtitle service returned invalid result: ' . gettype($result)
                    ];
                }

                if (isset($result['success']) && $result['success']) {
                    $finalVideoPath = $result['output_path'] ?? $baseVideoPath;
                    Log::info('🎯 FORCE OVERRIDE: Vietnamese subtitle SUCCESS', [
                        'result_video_path' => $finalVideoPath
                    ]);
                } else {
                    Log::warning('🔥🔥🔥 FORCE OVERRIDE: Failed to add Vietnamese subtitle, using base video', [
                        'error' => $result['error'] ?? 'Unknown error'
                    ]);
                }
            }

            // Step 4: Add logo if enabled
            $enableLogo = $this->option('enable-logo');
            if ($enableLogo) {
                Log::info('🔥🔥🔥 FORCE OVERRIDE: Processing logo overlay', [
                    'logo_source' => $this->option('logo-source'),
                    'selected_logo' => $this->option('selected-logo'),
                    'logo_position' => $this->option('logo-position'),
                    'logo_size' => $this->option('logo-size')
                ]);

                $logoPath = $this->getLogoPath();
                if ($logoPath && File::exists($logoPath)) {
                    $finalVideoPath = $this->addLogoToVideo($finalVideoPath, $logoPath, $tempDir);
                    Log::info('🔥🔥🔥 FORCE OVERRIDE: Logo added to video', [
                        'logo_path' => $logoPath,
                        'final_video_path' => $finalVideoPath
                    ]);
                } else {
                    Log::warning('🔥🔥🔥 FORCE OVERRIDE: Logo file not found, skipping logo overlay', [
                        'logo_path' => $logoPath
                    ]);
                }
            }

            // Step 5: Move to final location
            try {
                $outputPath = storage_path('app/videos/generated/' . $outputFilename);

                Log::info('🔥🔥🔥 FORCE OVERRIDE: Final output path debug', [
                    'outputFilename' => $outputFilename,
                    'outputPath' => $outputPath,
                    'finalVideoPath' => $finalVideoPath,
                    'finalVideoPath_exists' => File::exists($finalVideoPath)
                ]);
            } catch (\Exception $e) {
                Log::error('🔥🔥🔥 FORCE OVERRIDE: Error in final path creation', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }

            // Ensure output directory exists
            $outputDir = dirname($outputPath);
            if (!File::isDirectory($outputDir)) {
                File::makeDirectory($outputDir, 0755, true);
            }
            
            // Copy to final location
            if (File::exists($finalVideoPath)) {
                File::copy($finalVideoPath, $outputPath);
                
                Log::info('🔥🔥🔥 FORCE OVERRIDE: Universal video generation completed successfully 🔥🔥🔥', [
                    'final_path' => $outputPath
                ]);
                
                return 0;
            } else {
                throw new \Exception('Final video file not found: ' . $finalVideoPath);
            }
            
        } catch (\Exception $e) {
            Log::error('🔥🔥🔥 FORCE OVERRIDE: Exception in universal video generation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
    
    /**
     * Create base video from real inputs
     */
    private function createBaseVideo($tempDir)
    {
        // Ensure temp directory exists
        if (!File::isDirectory($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        $videoPath = $tempDir . '/merged_video.mp4';

        // Try both --images and --product-images for backward compatibility
        $imagesOption = $this->option('images');
        $productImagesOption = $this->option('product-images');
        $productImages = $imagesOption ?: $productImagesOption;
        $mediaType = $this->option('media-type');
        $slideDuration = floatval($this->option('slide-duration') ?: 3);

        Log::info('🔥🔥🔥 FORCE OVERRIDE: Creating base video from real inputs', [
            'media_type' => $mediaType,
            'images_option_raw' => $imagesOption,
            'images_option_type' => gettype($imagesOption),
            'images_option_empty' => empty($imagesOption),
            'images_option_length' => is_string($imagesOption) ? strlen($imagesOption) : 'not_string',
            'product_images_option_raw' => $productImagesOption,
            'product_images_option_type' => gettype($productImagesOption),
            'product_images_option_empty' => empty($productImagesOption),
            'final_product_images' => $productImages,
            'final_product_images_type' => gettype($productImages),
            'final_product_images_empty' => empty($productImages),
            'final_product_images_length' => is_string($productImages) ? strlen($productImages) : 'not_string',
            'slide_duration' => $slideDuration,
            'temp_dir' => $tempDir,
            'all_options_debug' => [
                'platform' => $this->option('platform'),
                'media-type' => $this->option('media-type'),
                'images' => $this->option('images'),
                'product-images' => $this->option('product-images'),
                'slide-duration' => $this->option('slide-duration'),
                'slide-transition' => $this->option('slide-transition')
            ]
        ]);

        // Get product video option
        $productVideo = $this->option('product-video');

        if ($mediaType === 'images' && !empty($productImages)) {
            return $this->createVideoFromImages($productImages, $tempDir, $slideDuration);
        } elseif ($mediaType === 'video' && !empty($productVideo)) {
            return $this->createVideoFromVideo($productVideo, $tempDir);
        } elseif ($mediaType === 'mixed' && !empty($productImages)) {
            // For mixed media, the service passes all media files through the images parameter
            return $this->createVideoFromMixedMedia($productImages, $tempDir, $slideDuration);
        } else {
            // Fallback: create simple video
            Log::warning('🔥🔥🔥 FORCE OVERRIDE: No real media provided, creating simple video', [
                'media_type' => $mediaType,
                'images_option' => $this->option('images'),
                'product_images_option' => $this->option('product-images'),
                'product_video_option' => $this->option('product-video'),
                'final_product_images' => $productImages,
                'final_product_video' => $productVideo
            ]);
            return $this->createSimpleVideo($tempDir);
        }
    }

    /**
     * Create video from real images
     */
    private function createVideoFromImages($productImages, $tempDir, $slideDuration)
    {
        $videoPath = $tempDir . '/merged_video.mp4';
        $imagePaths = explode(',', $productImages);

        // Apply image order mapping if provided
        $imageOrderMapping = $this->option('image-order-mapping');
        Log::info('🔥🔥🔥 FORCE OVERRIDE: Image order mapping check', [
            'image_order_mapping_option' => $imageOrderMapping,
            'has_mapping' => !empty($imageOrderMapping),
            'original_image_paths' => array_map('basename', $imagePaths)
        ]);

        if ($imageOrderMapping) {
            $originalPaths = $imagePaths;
            $imagePaths = $this->reorderImages($imagePaths, $imageOrderMapping);

            Log::info('🔥🔥🔥 FORCE OVERRIDE: Image order applied', [
                'original_order' => array_map('basename', $originalPaths),
                'reordered_paths' => array_map('basename', $imagePaths),
                'order_mapping_input' => $imageOrderMapping,
                'order_changed' => $originalPaths !== $imagePaths,
                'comparison' => [
                    'original' => array_map('basename', $originalPaths),
                    'reordered' => array_map('basename', $imagePaths)
                ]
            ]);
        }

        // Get resolution, FPS and transition from options
        $resolution = $this->option('resolution') ?: '1920x1080';
        $fps = $this->option('fps') ?: '30';
        $slideTransition = $this->option('slide-transition') ?: 'slide';

        // Use user-specified slide duration
        // $slideDuration is already validated and converted to float

        // Parse resolution
        list($width, $height) = explode('x', $resolution);

        Log::info('🔥🔥🔥 FORCE OVERRIDE: Creating video from real images', [
            'image_count' => count($imagePaths),
            'slide_duration' => $slideDuration,
            'first_image' => $imagePaths[0] ?? 'none',
            'resolution' => $resolution,
            'fps' => $fps,
            'width' => $width,
            'height' => $height
        ]);

        if (count($imagePaths) === 1) {
            // Single image - create looping video
            $imagePath = trim($imagePaths[0]);

            if (!File::exists($imagePath)) {
                throw new \Exception('Image file not found: ' . $imagePath);
            }

            $cmd = "ffmpeg -loop 1 -i \"{$imagePath}\" -vf \"scale={$width}:{$height}:force_original_aspect_ratio=decrease,pad={$width}:{$height}:(ow-iw)/2:(oh-ih)/2:color=black\" -t {$slideDuration} -c:v libx264 -preset fast -crf 23 -pix_fmt yuv420p -r {$fps} \"{$videoPath}\" -y";

            Log::info('🔥🔥🔥 FORCE OVERRIDE: Single image FFmpeg command', [
                'command' => $cmd,
                'image_path' => $imagePath,
                'resolution' => $resolution,
                'fps' => $fps
            ]);

            exec($cmd, $output, $returnCode);

            if ($returnCode === 0 && File::exists($videoPath)) {
                Log::info('🔥🔥🔥 FORCE OVERRIDE: Single image video created successfully');
                return $videoPath;
            } else {
                throw new \Exception('Failed to create video from single image. Return code: ' . $returnCode);
            }
        } else {
            // Multiple images slideshow with transition support
            // Initialize variables to avoid undefined variable errors
            $inputList = '';
            $inputListPath = '';

            Log::info('🔥🔥🔥 FORCE OVERRIDE: Checking transition condition', [
                'slide_transition' => $slideTransition,
                'slide_transition_type' => gettype($slideTransition),
                'slide_transition_empty' => empty($slideTransition),
                'slide_transition_not_none' => $slideTransition !== 'none',
                'image_count' => count($imagePaths),
                'image_count_gt_1' => count($imagePaths) > 1,
                'final_condition' => ($slideTransition !== 'none' && count($imagePaths) > 1)
            ]);

            // FORCE TRANSITION EFFECTS FOR ALL MULTI-IMAGE VIDEOS
            if (count($imagePaths) > 1) {
                try {
                    // Use transition effects
                    Log::info('🔥🔥🔥 FORCE OVERRIDE: About to call createSlideshowWithTransitions', [
                        'image_count' => count($imagePaths),
                        'slide_duration' => $slideDuration,
                        'slide_transition' => $slideTransition,
                        'width' => $width,
                        'height' => $height,
                        'fps' => $fps
                    ]);

                    $cmd = $this->createSlideshowWithTransitions($imagePaths, $videoPath, $slideDuration, $slideTransition, $width, $height, $fps);
                    $expectedDuration = $this->calculateTransitionDuration($imagePaths, $slideDuration);

                    Log::info('🔥🔥🔥 FORCE OVERRIDE: Using transition effects', [
                        'transition' => $slideTransition,
                        'expected_duration' => $expectedDuration,
                        'image_count' => count($imagePaths),
                        'slide_duration' => $slideDuration,
                        'cmd_preview' => substr($cmd, 0, 200) . '...'
                    ]);
                } catch (\Exception $e) {
                    Log::error('🔥🔥🔥 FORCE OVERRIDE: Transition method failed, falling back to simple concat', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);

                    // Fall back to simple concat
                    $inputListPath = $tempDir . '/images.txt';
                    $inputList = '';

                    // Calculate total expected duration
                    $expectedDuration = count($imagePaths) * $slideDuration;

                    foreach ($imagePaths as $index => $image) {
                        $imagePath = trim($image);
                        if (File::exists($imagePath)) {
                            $inputList .= "file '" . str_replace('\\', '/', $imagePath) . "'\n";
                            $inputList .= "duration {$slideDuration}\n";
                        }
                    }

                    // Add last image again with explicit duration for proper ending
                    if (!empty($imagePaths)) {
                        $lastImage = trim(end($imagePaths));
                        if (File::exists($lastImage)) {
                            $inputList .= "file '" . str_replace('\\', '/', $lastImage) . "'\n";
                            $inputList .= "duration 0.1\n"; // Very short duration to end properly
                        }
                    }

                    File::put($inputListPath, $inputList);

                    // Simple concat command
                    $cmd = "ffmpeg -f concat -safe 0 -i \"{$inputListPath}\" -vf \"scale={$width}:{$height}:force_original_aspect_ratio=decrease,pad={$width}:{$height}:(ow-iw)/2:(oh-ih)/2:color=black\" -c:v libx264 -preset fast -crf 23 -pix_fmt yuv420p -r {$fps} -t {$expectedDuration} \"{$videoPath}\" -y";
                }
            } else {
                // Fallback: Use simple concat without transitions
                $inputListPath = $tempDir . '/images.txt';
                $inputList = '';

                // Calculate total expected duration
                $expectedDuration = count($imagePaths) * $slideDuration;

                foreach ($imagePaths as $index => $image) {
                    $imagePath = trim($image);
                    if (File::exists($imagePath)) {
                        $inputList .= "file '" . str_replace('\\', '/', $imagePath) . "'\n";
                        $inputList .= "duration {$slideDuration}\n";
                    }
                }

                // Add last image again with explicit duration for proper ending
                if (!empty($imagePaths)) {
                    $lastImage = trim(end($imagePaths));
                    if (File::exists($lastImage)) {
                        $inputList .= "file '" . str_replace('\\', '/', $lastImage) . "'\n";
                        $inputList .= "duration 0.1\n"; // Very short duration to end properly
                    }
                }

                File::put($inputListPath, $inputList);

                // Simple concat command
                $cmd = "ffmpeg -f concat -safe 0 -i \"{$inputListPath}\" -vf \"scale={$width}:{$height}:force_original_aspect_ratio=decrease,pad={$width}:{$height}:(ow-iw)/2:(oh-ih)/2:color=black\" -c:v libx264 -preset fast -crf 23 -pix_fmt yuv420p -r {$fps} -t {$expectedDuration} \"{$videoPath}\" -y";
            }

            Log::info('🔥🔥🔥 FORCE OVERRIDE: Multiple images FFmpeg command', [
                'command' => $cmd,
                'input_list_content' => isset($inputList) ? $inputList : 'N/A (using transitions)',
                'resolution' => $resolution,
                'fps' => $fps,
                'image_count' => count($imagePaths),
                'slide_duration' => $slideDuration,
                'expected_duration' => $expectedDuration,
                'input_list_path' => isset($inputListPath) ? $inputListPath : 'N/A (using transitions)'
            ]);

            exec($cmd, $output, $returnCode);

            if ($returnCode === 0 && File::exists($videoPath)) {
                Log::info('🔥🔥🔥 FORCE OVERRIDE: Multiple images video created successfully');
                return $videoPath;
            } else {
                throw new \Exception('Failed to create video from multiple images. Return code: ' . $returnCode);
            }
        }
    }

    /**
     * Create video from uploaded video file
     */
    private function createVideoFromVideo($productVideo, $tempDir)
    {
        $videoPath = $tempDir . '/merged_video.mp4';

        Log::info('🔥🔥🔥 FORCE OVERRIDE: Creating video from uploaded video', [
            'product_video' => $productVideo,
            'temp_dir' => $tempDir,
            'output_path' => $videoPath
        ]);

        // Check if source video exists
        if (!File::exists($productVideo)) {
            throw new \Exception("Source video file not found: {$productVideo}");
        }

        // Get video resolution settings
        $resolution = $this->option('resolution') ?: '1920x1080';
        $fps = intval($this->option('fps') ?: 30);
        [$width, $height] = explode('x', $resolution);

        // Copy and process the video with proper scaling and format
        $cmd = "ffmpeg -i \"{$productVideo}\" " .
               "-vf \"scale={$width}:{$height}:force_original_aspect_ratio=decrease,pad={$width}:{$height}:(ow-iw)/2:(oh-ih)/2:color=black\" " .
               "-c:v libx264 -preset fast -crf 23 -pix_fmt yuv420p -r {$fps} " .
               "\"{$videoPath}\" -y";

        Log::info('🔥🔥🔥 FORCE OVERRIDE: Video processing FFmpeg command', [
            'command' => $cmd,
            'source_video' => $productVideo,
            'resolution' => $resolution,
            'fps' => $fps
        ]);

        exec($cmd, $output, $returnCode);

        if ($returnCode === 0 && File::exists($videoPath)) {
            Log::info('🔥🔥🔥 FORCE OVERRIDE: Video processed successfully', [
                'output_path' => $videoPath,
                'file_size' => File::size($videoPath)
            ]);
            return $videoPath;
        } else {
            Log::error('🔥🔥🔥 FORCE OVERRIDE: Failed to process video', [
                'return_code' => $returnCode,
                'output' => $output,
                'command' => $cmd
            ]);
            throw new \Exception('Failed to process video file. Return code: ' . $returnCode);
        }
    }

    /**
     * Create video from mixed media (images + videos)
     */
    private function createVideoFromMixedMedia($mediaFiles, $tempDir, $slideDuration)
    {
        $videoPath = $tempDir . '/merged_video.mp4';
        $mixedMode = $this->option('mixed-mode') ?: 'sequence';

        Log::info('🔥🔥🔥 FORCE OVERRIDE: Creating video from mixed media', [
            'media_files' => $mediaFiles,
            'mixed_mode' => $mixedMode,
            'temp_dir' => $tempDir,
            'output_path' => $videoPath
        ]);

        // Parse media files
        $mediaPaths = explode(',', $mediaFiles);

        if (empty($mediaPaths)) {
            throw new \Exception("No media files provided for mixed media");
        }

        // Separate images and videos
        $imagePaths = [];
        $videoPaths = [];

        foreach ($mediaPaths as $mediaPath) {
            $mediaPath = trim($mediaPath);
            if (!File::exists($mediaPath)) {
                Log::warning("Media file not found: {$mediaPath}");
                continue;
            }

            // Detect file type by extension
            $extension = strtolower(pathinfo($mediaPath, PATHINFO_EXTENSION));
            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                $imagePaths[] = $mediaPath;
            } elseif (in_array($extension, ['mp4', 'avi', 'mov'])) {
                $videoPaths[] = $mediaPath;
            }
        }

        Log::info('🔥🔥🔥 FORCE OVERRIDE: Mixed media analysis', [
            'total_files' => count($mediaPaths),
            'images_count' => count($imagePaths),
            'videos_count' => count($videoPaths),
            'mixed_mode' => $mixedMode
        ]);

        // Get video resolution settings
        $resolution = $this->option('resolution') ?: '1920x1080';
        $fps = intval($this->option('fps') ?: 30);
        [$width, $height] = explode('x', $resolution);

        // Create mixed media video based on mode
        switch ($mixedMode) {
            case 'sequence':
            default:
                return $this->createSequenceMixedVideo($imagePaths, $videoPaths, $videoPath, $slideDuration, $width, $height, $fps);

            case 'overlay':
                return $this->createOverlayMixedVideo($imagePaths, $videoPaths, $videoPath, $width, $height, $fps);

            case 'split':
                return $this->createSplitMixedVideo($imagePaths, $videoPaths, $videoPath, $width, $height, $fps);
        }
    }

    /**
     * Create sequence mixed video (images and videos alternating)
     */
    private function createSequenceMixedVideo($imagePaths, $videoPaths, $outputPath, $slideDuration, $width, $height, $fps)
    {
        $tempDir = dirname($outputPath);
        $sequenceStrategy = $this->option('sequence-strategy') ?: 'even_distribution';

        Log::info('🔥🔥🔥 FORCE OVERRIDE: Creating sequence mixed video', [
            'strategy' => $sequenceStrategy,
            'images_count' => count($imagePaths),
            'videos_count' => count($videoPaths)
        ]);

        if ($sequenceStrategy === 'even_distribution') {
            return $this->createEvenDistributionVideo($imagePaths, $videoPaths, $outputPath, $width, $height, $fps);
        } else {
            return $this->createAlternatingSequenceVideo($imagePaths, $videoPaths, $outputPath, $slideDuration, $width, $height, $fps);
        }
    }

    /**
     * Create even distribution video (images overlaid on video at specific times)
     */
    private function createEvenDistributionVideo($imagePaths, $videoPaths, $outputPath, $width, $height, $fps)
    {
        if (empty($videoPaths)) {
            throw new \Exception('Even distribution mode requires at least one video');
        }

        $tempDir = dirname($outputPath);
        $baseVideo = $videoPaths[0]; // Use first video as base
        $imageDisplayDuration = floatval($this->option('image-display-duration') ?: 2);
        $distributionMode = $this->option('image-distribution-mode') ?: 'auto_even';

        // Get video duration first
        $videoDurationCmd = "ffprobe -v quiet -show_entries format=duration -of csv=p=0 \"{$baseVideo}\"";
        $videoDuration = floatval(trim(shell_exec($videoDurationCmd)));

        Log::info('🔥🔥🔥 FORCE OVERRIDE: Even distribution settings', [
            'base_video' => $baseVideo,
            'video_duration' => $videoDuration,
            'image_display_duration' => $imageDisplayDuration,
            'distribution_mode' => $distributionMode,
            'images_count' => count($imagePaths)
        ]);

        if (empty($imagePaths)) {
            // No images, just process the video
            $cmd = "ffmpeg -i \"{$baseVideo}\" -vf \"scale={$width}:{$height}:force_original_aspect_ratio=decrease,pad={$width}:{$height}:(ow-iw)/2:(oh-ih)/2:color=black\" -r {$fps} \"{$outputPath}\" -y";
            exec($cmd, $output, $returnCode);

            if ($returnCode === 0 && File::exists($outputPath)) {
                Log::info('🔥🔥🔥 FORCE OVERRIDE: Video processed without images');
                return $outputPath;
            } else {
                throw new \Exception('Failed to process video. Return code: ' . $returnCode);
            }
        }

        // Calculate image timing
        $imageTimings = [];
        if ($distributionMode === 'custom_timing') {
            $customTimings = $this->option('image-timings');
            if ($customTimings) {
                $imageTimings = json_decode($customTimings, true) ?: [];
            }
        }

        if (empty($imageTimings)) {
            // Auto even distribution
            $imageCount = count($imagePaths);
            $interval = $videoDuration / ($imageCount + 1);

            for ($i = 0; $i < $imageCount; $i++) {
                $imageTimings[] = ($i + 1) * $interval;
            }
        }

        Log::info('🔥🔥🔥 FORCE OVERRIDE: Image timings calculated', [
            'timings' => $imageTimings,
            'image_count' => count($imagePaths)
        ]);

        // Build FFmpeg filter for overlaying images
        $filterComplex = "[0:v]scale={$width}:{$height}:force_original_aspect_ratio=decrease,pad={$width}:{$height}:(ow-iw)/2:(oh-ih)/2:color=black[base]";
        $inputs = "-i \"{$baseVideo}\"";

        foreach ($imagePaths as $index => $imagePath) {
            $inputs .= " -i \"{$imagePath}\"";
            $imageIndex = $index + 1;
            $startTime = isset($imageTimings[$index]) ? $imageTimings[$index] : ($index * 5);
            $endTime = $startTime + $imageDisplayDuration;

            // Scale and position image (overlay in center)
            $filterComplex .= ";[{$imageIndex}:v]scale=300:200[img{$index}]";

            if ($index === 0) {
                $filterComplex .= ";[base][img{$index}]overlay=(W-w)/2:(H-h)/2:enable='between(t,{$startTime},{$endTime})'[v{$index}]";
            } else {
                $prevIndex = $index - 1;
                $filterComplex .= ";[v{$prevIndex}][img{$index}]overlay=(W-w)/2:(H-h)/2:enable='between(t,{$startTime},{$endTime})'[v{$index}]";
            }
        }

        $lastIndex = count($imagePaths) - 1;
        $outputMap = $lastIndex >= 0 ? "[v{$lastIndex}]" : "[base]";

        $cmd = "ffmpeg {$inputs} -filter_complex \"{$filterComplex}\" -map \"{$outputMap}\" -map 0:a? -c:v libx264 -c:a copy -r {$fps} \"{$outputPath}\" -y";

        Log::info('🔥🔥🔥 FORCE OVERRIDE: FFmpeg command for even distribution', [
            'command_preview' => substr($cmd, 0, 200) . '...'
        ]);

        exec($cmd, $output, $returnCode);

        if ($returnCode === 0 && File::exists($outputPath)) {
            Log::info('🔥🔥🔥 FORCE OVERRIDE: Even distribution video created successfully');
            return $outputPath;
        } else {
            Log::error('🔥🔥🔥 FORCE OVERRIDE: Failed to create even distribution video', [
                'return_code' => $returnCode,
                'output' => implode("\n", $output)
            ]);
            throw new \Exception('Failed to create even distribution video. Return code: ' . $returnCode);
        }
    }

    /**
     * Create alternating sequence video (original logic)
     */
    private function createAlternatingSequenceVideo($imagePaths, $videoPaths, $outputPath, $slideDuration, $width, $height, $fps)
    {
        $tempDir = dirname($outputPath);
        $inputListPath = $tempDir . '/mixed_sequence.txt';
        $inputList = '';

        // Create alternating sequence
        $maxCount = max(count($imagePaths), count($videoPaths));

        for ($i = 0; $i < $maxCount; $i++) {
            // Add image if available
            if (isset($imagePaths[$i])) {
                $imageVideoPath = $tempDir . "/image_video_{$i}.mp4";
                $cmd = "ffmpeg -loop 1 -i \"{$imagePaths[$i]}\" -c:v libx264 -t {$slideDuration} -pix_fmt yuv420p -vf \"scale={$width}:{$height}:force_original_aspect_ratio=decrease,pad={$width}:{$height}:(ow-iw)/2:(oh-ih)/2:color=black\" -r {$fps} \"{$imageVideoPath}\" -y";
                exec($cmd, $output, $returnCode);

                if ($returnCode === 0 && File::exists($imageVideoPath)) {
                    $inputList .= "file '{$imageVideoPath}'\n";
                }
            }

            // Add video if available
            if (isset($videoPaths[$i])) {
                $processedVideoPath = $tempDir . "/processed_video_{$i}.mp4";
                $cmd = "ffmpeg -i \"{$videoPaths[$i]}\" -c:v libx264 -vf \"scale={$width}:{$height}:force_original_aspect_ratio=decrease,pad={$width}:{$height}:(ow-iw)/2:(oh-ih)/2:color=black\" -r {$fps} \"{$processedVideoPath}\" -y";
                exec($cmd, $output, $returnCode);

                if ($returnCode === 0 && File::exists($processedVideoPath)) {
                    $inputList .= "file '{$processedVideoPath}'\n";
                }
            }
        }

        File::put($inputListPath, $inputList);

        // Concatenate all videos
        $cmd = "ffmpeg -f concat -safe 0 -i \"{$inputListPath}\" -c copy \"{$outputPath}\" -y";
        exec($cmd, $output, $returnCode);

        if ($returnCode === 0 && File::exists($outputPath)) {
            Log::info('🔥🔥🔥 FORCE OVERRIDE: Alternating sequence video created successfully');
            return $outputPath;
        } else {
            throw new \Exception('Failed to create alternating sequence video. Return code: ' . $returnCode);
        }
    }

    /**
     * Create overlay mixed video (images overlaid on videos)
     */
    private function createOverlayMixedVideo($imagePaths, $videoPaths, $outputPath, $width, $height, $fps)
    {
        // For now, use the first video as base and overlay first image
        if (empty($videoPaths)) {
            throw new \Exception('Overlay mode requires at least one video');
        }

        $baseVideo = $videoPaths[0];

        if (!empty($imagePaths)) {
            $overlayImage = $imagePaths[0];
            $cmd = "ffmpeg -i \"{$baseVideo}\" -i \"{$overlayImage}\" -filter_complex \"[0:v]scale={$width}:{$height}[base];[1:v]scale=300:200[overlay];[base][overlay]overlay=W-w-20:20\" -c:a copy \"{$outputPath}\" -y";
        } else {
            // Just process the video
            $cmd = "ffmpeg -i \"{$baseVideo}\" -vf \"scale={$width}:{$height}:force_original_aspect_ratio=decrease,pad={$width}:{$height}:(ow-iw)/2:(oh-ih)/2:color=black\" -r {$fps} \"{$outputPath}\" -y";
        }

        exec($cmd, $output, $returnCode);

        if ($returnCode === 0 && File::exists($outputPath)) {
            Log::info('🔥🔥🔥 FORCE OVERRIDE: Overlay mixed video created successfully');
            return $outputPath;
        } else {
            throw new \Exception('Failed to create overlay mixed video. Return code: ' . $returnCode);
        }
    }

    /**
     * Create split screen mixed video
     */
    private function createSplitMixedVideo($imagePaths, $videoPaths, $outputPath, $width, $height, $fps)
    {
        // For now, create a simple side-by-side if we have both images and videos
        if (empty($videoPaths) || empty($imagePaths)) {
            // Fall back to sequence mode
            return $this->createSequenceMixedVideo($imagePaths, $videoPaths, $outputPath, 3, $width, $height, $fps);
        }

        $baseVideo = $videoPaths[0];
        $sideImage = $imagePaths[0];

        $cmd = "ffmpeg -i \"{$baseVideo}\" -i \"{$sideImage}\" -filter_complex \"[0:v]scale=" . ($width/2) . ":{$height}[left];[1:v]scale=" . ($width/2) . ":{$height}[right];[left][right]hstack\" \"{$outputPath}\" -y";

        exec($cmd, $output, $returnCode);

        if ($returnCode === 0 && File::exists($outputPath)) {
            Log::info('🔥🔥🔥 FORCE OVERRIDE: Split mixed video created successfully');
            return $outputPath;
        } else {
            throw new \Exception('Failed to create split mixed video. Return code: ' . $returnCode);
        }
    }

    /**
     * Create slideshow with transition effects
     */
    private function createSlideshowWithTransitions($imagePaths, $outputPath, $slideDuration, $transition, $width, $height, $fps)
    {
        $transitionDuration = 0.5; // 0.5 second transition
        $imageCount = count($imagePaths);

        // Get individual image durations if provided
        $imageDurationsOption = $this->option('image-durations');
        $imageDurations = [];

        if ($imageDurationsOption) {
            $imageDurations = json_decode($imageDurationsOption, true) ?: [];
            Log::info('🔥🔥🔥 FORCE OVERRIDE: Using individual image durations', [
                'image_durations_raw' => $imageDurationsOption,
                'image_durations_parsed' => $imageDurations,
                'image_count' => $imageCount
            ]);
        }

        // Get individual image transitions if provided
        $imageTransitionsOption = $this->option('image-transitions');
        $imageTransitions = [];

        if ($imageTransitionsOption) {
            $imageTransitions = json_decode($imageTransitionsOption, true) ?: [];
            Log::info('🔥🔥🔥 FORCE OVERRIDE: Using individual image transitions', [
                'image_transitions_raw' => $imageTransitionsOption,
                'image_transitions_parsed' => $imageTransitions,
                'image_count' => $imageCount,
                'fallback_transition' => $transition
            ]);
        }

        // Build complex filter for transitions
        $inputs = '';
        $filters = [];

        // Calculate total expected duration using individual durations
        $totalDuration = $this->calculateTransitionDurationWithIndividualDurations($imagePaths, $slideDuration, $imageDurations);

        // Add all images as inputs with correct duration
        foreach ($imagePaths as $index => $imagePath) {
            $imagePath = trim($imagePath);
            if (File::exists($imagePath)) {
                // Get individual duration for this image, fallback to slide_duration
                $individualDuration = isset($imageDurations[$index]) ? floatval($imageDurations[$index]) : $slideDuration;

                // Each image needs to be available for its full slide duration + transition time
                $imageDuration = $individualDuration + $transitionDuration;

                Log::info('🔥🔥🔥 FORCE OVERRIDE: Image input duration', [
                    'image_index' => $index,
                    'image_name' => basename($imagePath),
                    'individual_duration' => $individualDuration,
                    'calculated_duration' => $imageDuration,
                    'is_first' => $index === 0,
                    'is_last' => $index === $imageCount - 1,
                    'fallback_slide_duration' => $slideDuration,
                    'transition_duration' => $transitionDuration,
                    'has_individual_duration' => isset($imageDurations[$index])
                ]);

                $inputs .= "-loop 1 -t " . $imageDuration . " -i \"" . str_replace('\\', '/', $imagePath) . "\" ";
            }
        }

        // Scale all inputs
        for ($i = 0; $i < $imageCount; $i++) {
            $filters[] = "[{$i}:v]scale={$width}:{$height}:force_original_aspect_ratio=decrease,pad={$width}:{$height}:(ow-iw)/2:(oh-ih)/2:color=black,setpts=PTS-STARTPTS[v{$i}]";
        }

        // Create transitions between images
        $currentLabel = 'v0';
        $cumulativeTime = 0; // Track cumulative time for accurate offset calculation

        for ($i = 1; $i < $imageCount; $i++) {
            $nextLabel = "v{$i}";
            $outputLabel = ($i == $imageCount - 1) ? 'out' : "t{$i}";

            // Calculate cumulative time up to this transition
            for ($j = 0; $j < $i; $j++) {
                $imageDuration = isset($imageDurations[$j]) ? floatval($imageDurations[$j]) : $slideDuration;
                $cumulativeTime += $imageDuration;
            }

            // Transition starts at (cumulative_time - transition_duration)
            $offset = $cumulativeTime - $transitionDuration;

            Log::info('🔥🔥🔥 FORCE OVERRIDE: Transition timing calculation', [
                'transition_index' => $i,
                'from_image' => $i - 1,
                'to_image' => $i,
                'cumulative_time_at_transition' => $cumulativeTime,
                'transition_duration' => $transitionDuration,
                'calculated_offset' => $offset,
                'individual_durations_used' => array_slice($imageDurations, 0, $i),
                'formula' => "cumulative_time({$cumulativeTime}) - transition_duration({$transitionDuration}) = {$offset}"
            ]);

            // Reset cumulative time for next iteration
            $cumulativeTime = 0;

            // Get individual transition for this transition (between image i-1 and i)
            // Use transition for the target image (image i)
            $individualTransition = isset($imageTransitions[$i]) ? $imageTransitions[$i] : $transition;

            Log::info('🔥🔥🔥 FORCE OVERRIDE: Using individual transition', [
                'transition_index' => $i,
                'from_image' => $i - 1,
                'to_image' => $i,
                'individual_transition' => $individualTransition,
                'fallback_transition' => $transition,
                'has_individual_transition' => isset($imageTransitions[$i])
            ]);

            switch ($individualTransition) {
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
                case 'none':
                    // No transition - just cut between images
                    $filters[] = "[{$currentLabel}][{$nextLabel}]xfade=transition=fade:duration=0.01:offset={$offset}[{$outputLabel}]";
                    break;
                default:
                    $filters[] = "[{$currentLabel}][{$nextLabel}]xfade=transition=fade:duration={$transitionDuration}:offset={$offset}[{$outputLabel}]";
            }

            $currentLabel = $outputLabel;
        }

        $filterComplex = implode(';', $filters);

        $cmd = "ffmpeg {$inputs} -filter_complex \"{$filterComplex}\" -map \"[out]\" -c:v libx264 -preset fast -crf 23 -pix_fmt yuv420p -r {$fps} -t {$totalDuration} \"{$outputPath}\" -y";

        Log::info('🔥🔥🔥 FORCE OVERRIDE: Final FFmpeg command analysis', [
            'total_duration' => $totalDuration,
            'image_count' => $imageCount,
            'slide_duration' => $slideDuration,
            'transition_duration' => $transitionDuration,
            'inputs_preview' => substr($inputs, 0, 200) . '...',
            'filter_complex_preview' => substr($filterComplex, 0, 300) . '...',
            'full_command_length' => strlen($cmd),
            'command_preview' => substr($cmd, 0, 500) . '...'
        ]);

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
     * Calculate total duration with transitions
     */
    private function calculateTransitionDuration($imagePaths, $slideDuration)
    {
        $imageCount = count($imagePaths);
        $transitionDuration = 0.5;
        $totalDuration = ($imageCount * $slideDuration) + (($imageCount - 1) * $transitionDuration);

        Log::info('🔥🔥🔥 FORCE OVERRIDE: Duration calculation', [
            'image_count' => $imageCount,
            'slide_duration' => $slideDuration,
            'transition_duration' => $transitionDuration,
            'calculated_total' => $totalDuration,
            'formula' => "({$imageCount} × {$slideDuration}) + (" . ($imageCount - 1) . " × {$transitionDuration}) = {$totalDuration}"
        ]);

        return $totalDuration;
    }

    /**
     * Calculate total duration with individual image durations and transitions
     */
    private function calculateTransitionDurationWithIndividualDurations($imagePaths, $slideDuration, $imageDurations)
    {
        $imageCount = count($imagePaths);
        $transitionDuration = 0.5;
        $totalImageDuration = 0;

        // Sum individual image durations
        for ($i = 0; $i < $imageCount; $i++) {
            $individualDuration = isset($imageDurations[$i]) ? floatval($imageDurations[$i]) : $slideDuration;
            $totalImageDuration += $individualDuration;
        }

        // Add transition durations
        $totalTransitionDuration = ($imageCount - 1) * $transitionDuration;
        $totalDuration = $totalImageDuration + $totalTransitionDuration;

        Log::info('🔥🔥🔥 FORCE OVERRIDE: Individual duration calculation', [
            'image_count' => $imageCount,
            'individual_durations' => $imageDurations,
            'fallback_slide_duration' => $slideDuration,
            'total_image_duration' => $totalImageDuration,
            'transition_duration' => $transitionDuration,
            'total_transition_duration' => $totalTransitionDuration,
            'calculated_total' => $totalDuration,
            'formula' => "total_image_duration({$totalImageDuration}) + total_transition_duration({$totalTransitionDuration}) = {$totalDuration}"
        ]);

        return $totalDuration;
    }

    /**
     * Create simple fallback video
     */
    private function createSimpleVideo($tempDir)
    {
        $videoPath = $tempDir . '/merged_video.mp4';

        if (!File::exists($videoPath)) {
            // Try to use default images first
            $defaultImagesPath = $this->getDefaultImagesPath();

            if ($defaultImagesPath && !empty($defaultImagesPath)) {
                Log::info('🔥🔥🔥 FORCE OVERRIDE: Using default images for audio-only video', [
                    'default_images_path' => $defaultImagesPath
                ]);

                // Create video from default images
                $slideDuration = floatval($this->option('slide-duration') ?: 3);
                return $this->createVideoFromImages($defaultImagesPath, $tempDir, $slideDuration);
            }

            // Fallback to solid color video if no default images available
            Log::warning('🔥🔥🔥 FORCE OVERRIDE: No default images available, creating solid color video');
            $resolution = $this->option('resolution') ?: '1920x1080';
            $duration = $this->option('custom-duration') ?: 30;

            // Use a more neutral color instead of blue
            $command = "ffmpeg -f lavfi -i color=black:size={$resolution}:duration={$duration} -c:v libx264 -pix_fmt yuv420p \"{$videoPath}\" -y";
            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new \Exception('Failed to create simple video file');
            }
        }

        return $videoPath;
    }

    /**
     * Get default images path for audio-only videos
     */
    private function getDefaultImagesPath()
    {
        // Check for default images in storage
        $defaultImagesPaths = [
            storage_path('app/default-images'),
            public_path('images/default-video'),
            resource_path('images/default')
        ];

        foreach ($defaultImagesPaths as $path) {
            if (File::exists($path) && File::isDirectory($path)) {
                $images = File::glob($path . '/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
                if (!empty($images)) {
                    Log::info('🔥🔥🔥 FORCE OVERRIDE: Found default images', [
                        'path' => $path,
                        'images_count' => count($images)
                    ]);
                    return implode(',', $images);
                }
            }
        }

        return null;
    }

    /**
     * Create TTS audio from script text
     */
    private function createTTSAudio($scriptText, $tempDir)
    {
        try {
            $audioPath = $tempDir . '/tts_audio.mp3';

            // Use VBee TTS service
            $vbeeService = app(\App\Services\VBeeService::class);

            $voice = $this->option('voice') ?: 'hn_female_ngochuyen_full_48k-fhg';
            $speed = $this->option('speed') ?: '1.0';
            $volume = $this->option('volume') ?: '1.0';

            Log::info('🔥🔥🔥 FORCE OVERRIDE: Creating TTS audio', [
                'script_text_preview' => substr($scriptText, 0, 100),
                'voice' => $voice,
                'speed' => $speed,
                'volume' => $volume,
                'output_path' => $audioPath
            ]);

            $result = $vbeeService->textToSpeech($scriptText, $audioPath, [
                'voice' => $voice,
                'speed' => floatval($speed),
                'volume' => floatval($volume)
            ]);

            if ($result && File::exists($result)) {
                Log::info('🔥🔥🔥 FORCE OVERRIDE: TTS audio created successfully', [
                    'audio_path' => $result,
                    'file_size' => File::size($result)
                ]);
                return $result;
            } else {
                Log::error('🔥🔥🔥 FORCE OVERRIDE: TTS audio creation failed', [
                    'result' => $result,
                    'expected_audio_path' => $audioPath
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('🔥🔥🔥 FORCE OVERRIDE: Exception in TTS audio creation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Merge audio with video
     */
    private function mergeAudioWithVideo($videoPath, $audioPath, $tempDir, $audioType = 'main')
    {
        try {
            $outputPath = $tempDir . '/video_with_audio.mp4';

            Log::info('🔥🔥🔥 FORCE OVERRIDE: Merging audio with video', [
                'video_path' => $videoPath,
                'audio_path' => $audioPath,
                'output_path' => $outputPath,
                'audio_type' => $audioType
            ]);

            // Get video duration first
            $videoDurationCmd = "ffprobe -v quiet -show_entries format=duration -of csv=p=0 \"{$videoPath}\"";
            $videoDuration = trim(shell_exec($videoDurationCmd));

            // Get audio duration
            $audioDurationCmd = "ffprobe -v quiet -show_entries format=duration -of csv=p=0 \"{$audioPath}\"";
            $audioDuration = trim(shell_exec($audioDurationCmd));

            Log::info('🔥🔥🔥 FORCE OVERRIDE: Duration comparison', [
                'video_duration' => $videoDuration,
                'audio_duration' => $audioDuration,
                'video_path' => $videoPath,
                'audio_path' => $audioPath,
                'audio_type' => $audioType
            ]);

            // Determine final duration and looping strategy based on audio type
            $finalDuration = $videoDuration;
            $shouldLoopAudio = false;
            $cmd = '';

            if ($audioType === 'background' || $audioType === 'music') {
                // Background music can be looped to match video duration
                if (floatval($audioDuration) > 0 && floatval($videoDuration) > floatval($audioDuration)) {
                    $shouldLoopAudio = true;
                    Log::info('🔥🔥🔥 FORCE OVERRIDE: Background music will be looped', [
                        'video_duration' => $videoDuration,
                        'audio_duration' => $audioDuration,
                        'audio_type' => $audioType
                    ]);
                }

                if ($shouldLoopAudio) {
                    // Loop background music to match video duration
                    $cmd = "ffmpeg -i \"{$videoPath}\" -stream_loop -1 -i \"{$audioPath}\" -c:v copy -c:a aac -map 0:v:0 -map 1:a:0 -t {$finalDuration} \"{$outputPath}\" -y";
                } else {
                    // Don't loop if audio is longer than video
                    $cmd = "ffmpeg -i \"{$videoPath}\" -i \"{$audioPath}\" -c:v copy -c:a aac -map 0:v:0 -map 1:a:0 -t {$finalDuration} \"{$outputPath}\" -y";
                }
            } else {
                // Main audio (TTS, voice) should NOT be looped
                // If audio is shorter than video, video will be cut to audio length
                // If audio is longer than video, audio will be cut to video length
                if (floatval($audioDuration) > 0 && floatval($audioDuration) < floatval($videoDuration)) {
                    $finalDuration = $audioDuration; // Cut video to match audio length
                    Log::info('🔥🔥🔥 FORCE OVERRIDE: Main audio is shorter, cutting video to match', [
                        'original_video_duration' => $videoDuration,
                        'audio_duration' => $audioDuration,
                        'final_duration' => $finalDuration,
                        'audio_type' => $audioType
                    ]);
                }

                // Never loop main audio - use shortest duration
                $cmd = "ffmpeg -i \"{$videoPath}\" -i \"{$audioPath}\" -c:v copy -c:a aac -map 0:v:0 -map 1:a:0 -shortest \"{$outputPath}\" -y";
            }

            Log::info('🔥🔥🔥 FORCE OVERRIDE: Audio merge FFmpeg command', [
                'command' => $cmd,
                'final_duration' => $finalDuration,
                'audio_type' => $audioType,
                'should_loop_audio' => $shouldLoopAudio
            ]);

            $output = [];
            $returnCode = 0;
            exec($cmd, $output, $returnCode);

            if ($returnCode === 0 && File::exists($outputPath)) {
                Log::info('🔥🔥🔥 FORCE OVERRIDE: Audio merge successful', [
                    'output_path' => $outputPath,
                    'file_size' => File::size($outputPath),
                    'audio_type' => $audioType
                ]);
                return $outputPath;
            } else {
                Log::error('🔥🔥🔥 FORCE OVERRIDE: Audio merge failed', [
                    'return_code' => $returnCode,
                    'ffmpeg_output' => implode("\n", $output),
                    'audio_type' => $audioType
                ]);
                return $videoPath; // Return original video if merge fails
            }
        } catch (\Exception $e) {
            Log::error('🔥🔥🔥 FORCE OVERRIDE: Exception in audio merge', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'audio_type' => $audioType
            ]);
            return $videoPath; // Return original video if merge fails
        }
    }

    /**
     * Add background music to video
     */
    private function addBackgroundMusic($videoPath, $backgroundAudioPath, $tempDir, $volume = 0.3)
    {
        try {
            $outputPath = $tempDir . '/video_with_background_music.mp4';

            Log::info('🔥🔥🔥 FORCE OVERRIDE: Adding background music to video', [
                'video_path' => $videoPath,
                'background_audio_path' => $backgroundAudioPath,
                'output_path' => $outputPath,
                'volume' => $volume
            ]);

            // Get video duration
            $videoDurationCmd = "ffprobe -v quiet -show_entries format=duration -of csv=p=0 \"{$videoPath}\"";
            $videoDuration = trim(shell_exec($videoDurationCmd));

            // Get background audio duration
            $audioDurationCmd = "ffprobe -v quiet -show_entries format=duration -of csv=p=0 \"{$backgroundAudioPath}\"";
            $audioDuration = trim(shell_exec($audioDurationCmd));

            Log::info('🔥🔥🔥 FORCE OVERRIDE: Background music duration comparison', [
                'video_duration' => $videoDuration,
                'background_audio_duration' => $audioDuration
            ]);

            // Check if video already has audio
            $hasAudioCmd = "ffprobe -v quiet -select_streams a -show_entries stream=codec_type -of csv=p=0 \"{$videoPath}\"";
            $hasAudio = !empty(trim(shell_exec($hasAudioCmd)));

            if ($hasAudio) {
                // Video has existing audio - mix background music with existing audio
                if (floatval($audioDuration) > 0 && floatval($videoDuration) > floatval($audioDuration)) {
                    // Loop background music to match video duration
                    $cmd = "ffmpeg -i \"{$videoPath}\" -stream_loop -1 -i \"{$backgroundAudioPath}\" " .
                           "-filter_complex \"[1:a]volume={$volume}[bg];[0:a][bg]amix=inputs=2:duration=first:dropout_transition=2\" " .
                           "-c:v copy -t {$videoDuration} \"{$outputPath}\" -y";
                } else {
                    // Don't loop if background music is longer than video
                    $cmd = "ffmpeg -i \"{$videoPath}\" -i \"{$backgroundAudioPath}\" " .
                           "-filter_complex \"[1:a]volume={$volume}[bg];[0:a][bg]amix=inputs=2:duration=first:dropout_transition=2\" " .
                           "-c:v copy \"{$outputPath}\" -y";
                }
            } else {
                // Video has no audio - just add background music
                if (floatval($audioDuration) > 0 && floatval($videoDuration) > floatval($audioDuration)) {
                    // Loop background music to match video duration
                    $cmd = "ffmpeg -i \"{$videoPath}\" -stream_loop -1 -i \"{$backgroundAudioPath}\" " .
                           "-filter_complex \"[1:a]volume={$volume}[bg]\" " .
                           "-c:v copy -map 0:v:0 -map \"[bg]\" -t {$videoDuration} \"{$outputPath}\" -y";
                } else {
                    // Don't loop if background music is longer than video
                    $cmd = "ffmpeg -i \"{$videoPath}\" -i \"{$backgroundAudioPath}\" " .
                           "-filter_complex \"[1:a]volume={$volume}[bg]\" " .
                           "-c:v copy -map 0:v:0 -map \"[bg]\" -shortest \"{$outputPath}\" -y";
                }
            }

            Log::info('🔥🔥🔥 FORCE OVERRIDE: Background music FFmpeg command', [
                'command' => $cmd,
                'has_existing_audio' => $hasAudio,
                'volume' => $volume
            ]);

            $output = [];
            $returnCode = 0;
            exec($cmd, $output, $returnCode);

            if ($returnCode === 0 && File::exists($outputPath)) {
                Log::info('🔥🔥🔥 FORCE OVERRIDE: Background music added successfully', [
                    'output_path' => $outputPath,
                    'file_size' => File::size($outputPath)
                ]);
                return $outputPath;
            } else {
                Log::error('🔥🔥🔥 FORCE OVERRIDE: Background music addition failed', [
                    'return_code' => $returnCode,
                    'ffmpeg_output' => implode("\n", $output)
                ]);
                return $videoPath; // Return original video if addition fails
            }
        } catch (\Exception $e) {
            Log::error('🔥🔥🔥 FORCE OVERRIDE: Exception in background music addition', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $videoPath; // Return original video if addition fails
        }
    }

    /**
     * Determine audio type from library audio category
     */
    private function determineLibraryAudioType($libraryAudioId)
    {
        try {
            $audioLibrary = \App\Models\AudioLibrary::find($libraryAudioId);

            if (!$audioLibrary) {
                return 'main'; // Default to main audio
            }

            // Determine type based on category
            $category = strtolower($audioLibrary->category ?? '');

            if (in_array($category, ['music', 'background', 'bgm', 'instrumental'])) {
                return 'background';
            } elseif (in_array($category, ['voice', 'speech', 'narration', 'tts'])) {
                return 'main';
            } else {
                // Default based on title or description
                $title = strtolower($audioLibrary->title ?? '');
                $description = strtolower($audioLibrary->description ?? '');

                if (strpos($title, 'background') !== false ||
                    strpos($title, 'music') !== false ||
                    strpos($description, 'background') !== false ||
                    strpos($description, 'music') !== false) {
                    return 'background';
                }

                return 'main'; // Default to main audio
            }
        } catch (\Exception $e) {
            Log::error('🔥🔥🔥 FORCE OVERRIDE: Error determining audio type', [
                'library_audio_id' => $libraryAudioId,
                'error' => $e->getMessage()
            ]);
            return 'main'; // Safe default
        }
    }

    /**
     * Get library audio file
     */
    private function getLibraryAudio($libraryAudioId, $tempDir)
    {
        try {
            // Get audio from library
            $audioLibrary = \App\Models\AudioLibrary::find($libraryAudioId);

            if (!$audioLibrary) {
                Log::error('🔥🔥🔥 FORCE OVERRIDE: Library audio not found', [
                    'library_audio_id' => $libraryAudioId
                ]);
                return null;
            }

            Log::info('🔥🔥🔥 FORCE OVERRIDE: Found library audio', [
                'audio_id' => $audioLibrary->id,
                'title' => $audioLibrary->title,
                'file_path' => $audioLibrary->file_path,
                'duration' => $audioLibrary->duration
            ]);

            // Get full path to audio file - try multiple possible paths
            $possiblePaths = [
                storage_path('app/' . $audioLibrary->file_path),
                storage_path('app/public/' . $audioLibrary->file_path),
                storage_path('app/public/audio-library/' . basename($audioLibrary->file_path))
            ];

            $originalPath = null;
            foreach ($possiblePaths as $path) {
                if (File::exists($path)) {
                    $originalPath = $path;
                    break;
                }
            }

            // If still not found, try to find by pattern in audio-library directory
            if (!$originalPath) {
                $audioLibraryDir = storage_path('app/public/audio-library/');
                if (File::exists($audioLibraryDir)) {
                    $files = File::files($audioLibraryDir);
                    $targetBasename = basename($audioLibrary->file_path, '.mp3');

                    foreach ($files as $file) {
                        if (str_contains($file->getFilename(), $targetBasename)) {
                            $originalPath = $file->getPathname();
                            break;
                        }
                    }
                }
            }

            if (!$originalPath) {
                Log::error('🔥🔥🔥 FORCE OVERRIDE: Library audio file not found in any location', [
                    'tried_paths' => $possiblePaths,
                    'file_path' => $audioLibrary->file_path,
                    'audio_library_dir' => storage_path('app/public/audio-library/')
                ]);
                return null;
            }

            // Copy to temp directory
            $audioPath = $tempDir . '/library_audio.mp3';
            File::copy($originalPath, $audioPath);

            Log::info('🔥🔥🔥 FORCE OVERRIDE: Library audio copied to temp', [
                'original_path' => $originalPath,
                'temp_path' => $audioPath,
                'file_size' => File::size($audioPath)
            ]);

            return $audioPath;

        } catch (\Exception $e) {
            Log::error('🔥🔥🔥 FORCE OVERRIDE: Exception in library audio processing', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Reorder images based on order mapping
     */
    private function reorderImages($imagePaths, $imageOrderMapping)
    {
        try {
            $orderMap = json_decode($imageOrderMapping, true);
            if (!$orderMap) {
                Log::warning('🔥🔥🔥 FORCE OVERRIDE: Invalid image order mapping', [
                    'mapping' => $imageOrderMapping
                ]);
                return $imagePaths;
            }

            Log::info('🔥🔥🔥 FORCE OVERRIDE: Applying image order mapping', [
                'original_count' => count($imagePaths),
                'order_mapping' => $orderMap,
                'original_paths' => array_map('basename', $imagePaths)
            ]);

            // Convert order value mapping to position mapping
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

            Log::info('🔥🔥🔥 FORCE OVERRIDE: Image reordering completed', [
                'original_paths' => array_map('basename', $imagePaths),
                'reordered_paths' => array_map('basename', $reorderedPaths),
                'order_pairs_sorted' => array_map(function($pair) {
                    return ['originalIndex' => $pair['originalIndex'], 'orderValue' => $pair['orderValue']];
                }, $orderPairs)
            ]);

            return $reorderedPaths;

        } catch (\Exception $e) {
            Log::error('🔥🔥🔥 FORCE OVERRIDE: Error reordering images', [
                'error' => $e->getMessage(),
                'mapping' => $imageOrderMapping
            ]);
            return $imagePaths;
        }
    }

    /**
     * Get video duration using FFprobe
     */
    private function getVideoDuration($videoPath)
    {
        $command = "ffprobe -v quiet -show_entries format=duration -of csv=p=0 \"$videoPath\"";
        $output = shell_exec($command);
        return floatval(trim($output));
    }

    /**
     * Get logo file path based on source
     */
    private function getLogoPath()
    {
        $logoSource = $this->option('logo-source');

        if ($logoSource === 'library') {
            $selectedLogo = $this->option('selected-logo');
            if ($selectedLogo) {
                // Use logo management system path
                return storage_path('app/logos/' . $selectedLogo);
            }
        } elseif ($logoSource === 'upload') {
            $logoFile = $this->option('logo-file');
            if ($logoFile) {
                return $logoFile;
            }
        }

        return null;
    }

    /**
     * Add logo overlay to video
     */
    private function addLogoToVideo($videoPath, $logoPath, $tempDir)
    {
        try {
            $outputPath = $tempDir . '/video_with_logo.mp4';

            Log::info('🔥🔥🔥 FORCE OVERRIDE: Adding logo to video', [
                'video_path' => $videoPath,
                'logo_path' => $logoPath,
                'output_path' => $outputPath
            ]);

            // Get logo settings
            $position = $this->option('logo-position') ?: 'top-right';
            $size = $this->option('logo-size') ?: 'medium';
            $opacity = floatval($this->option('logo-opacity') ?: 1.0);
            $margin = intval($this->option('logo-margin') ?: 20);
            $duration = $this->option('logo-duration') ?: 'full';
            $startTime = floatval($this->option('logo-start-time') ?: 0);
            $endTime = floatval($this->option('logo-end-time') ?: 0);

            // Get video dimensions
            $videoDimensionsCmd = "ffprobe -v quiet -select_streams v:0 -show_entries stream=width,height -of csv=p=0 \"$videoPath\"";
            $videoDimensions = trim(shell_exec($videoDimensionsCmd));
            list($videoWidth, $videoHeight) = explode(',', $videoDimensions);

            // Calculate logo size
            $logoSize = $this->calculateLogoSize($size, $videoWidth, $videoHeight);

            // Calculate logo position
            $logoPosition = $this->calculateLogoPosition($position, $videoWidth, $videoHeight, $logoSize, $margin);

            // Build FFmpeg filter
            $filter = $this->buildLogoFilter($logoPath, $logoPosition, $logoSize, $opacity, $duration, $startTime, $endTime);

            // FFmpeg command
            $cmd = "ffmpeg -i \"$videoPath\" -i \"$logoPath\" -filter_complex \"$filter\" -c:a copy \"$outputPath\" -y";

            Log::info('🔥🔥🔥 FORCE OVERRIDE: Logo FFmpeg command', [
                'command' => $cmd,
                'position' => $position,
                'size' => $size,
                'opacity' => $opacity,
                'logo_size' => $logoSize,
                'logo_position' => $logoPosition
            ]);

            $output = [];
            $returnCode = 0;
            exec($cmd, $output, $returnCode);

            if ($returnCode === 0 && File::exists($outputPath)) {
                Log::info('🔥🔥🔥 FORCE OVERRIDE: Logo added successfully', [
                    'output_path' => $outputPath,
                    'file_size' => File::size($outputPath)
                ]);
                return $outputPath;
            } else {
                Log::error('🔥🔥🔥 FORCE OVERRIDE: Logo addition failed', [
                    'return_code' => $returnCode,
                    'ffmpeg_output' => implode("\n", $output)
                ]);
                return $videoPath; // Return original video if logo addition fails
            }
        } catch (\Exception $e) {
            Log::error('🔥🔥🔥 FORCE OVERRIDE: Exception in logo addition', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $videoPath; // Return original video if logo addition fails
        }
    }

    /**
     * Calculate logo size based on settings
     */
    private function calculateLogoSize($size, $videoWidth, $videoHeight)
    {
        if ($size === 'custom') {
            $width = intval($this->option('logo-width') ?: 100);
            $height = intval($this->option('logo-height') ?: 100);
            return ['width' => $width, 'height' => $height];
        }

        // Calculate percentage-based sizes
        $percentage = match($size) {
            'small' => 0.05,
            'medium' => 0.10,
            'large' => 0.15,
            'xlarge' => 0.20,
            default => 0.10
        };

        $logoWidth = intval($videoWidth * $percentage);
        $logoHeight = intval($videoHeight * $percentage);

        return ['width' => $logoWidth, 'height' => $logoHeight];
    }

    /**
     * Calculate logo position coordinates
     */
    private function calculateLogoPosition($position, $videoWidth, $videoHeight, $logoSize, $margin)
    {
        $logoWidth = $logoSize['width'];
        $logoHeight = $logoSize['height'];

        return match($position) {
            'top-left' => ['x' => $margin, 'y' => $margin],
            'top-right' => ['x' => $videoWidth - $logoWidth - $margin, 'y' => $margin],
            'top-center' => ['x' => ($videoWidth - $logoWidth) / 2, 'y' => $margin],
            'bottom-left' => ['x' => $margin, 'y' => $videoHeight - $logoHeight - $margin],
            'bottom-right' => ['x' => $videoWidth - $logoWidth - $margin, 'y' => $videoHeight - $logoHeight - $margin],
            'bottom-center' => ['x' => ($videoWidth - $logoWidth) / 2, 'y' => $videoHeight - $logoHeight - $margin],
            'center' => ['x' => ($videoWidth - $logoWidth) / 2, 'y' => ($videoHeight - $logoHeight) / 2],
            'center-left' => ['x' => $margin, 'y' => ($videoHeight - $logoHeight) / 2],
            'center-right' => ['x' => $videoWidth - $logoWidth - $margin, 'y' => ($videoHeight - $logoHeight) / 2],
            default => ['x' => $videoWidth - $logoWidth - $margin, 'y' => $margin] // top-right default
        };
    }

    /**
     * Build FFmpeg filter for logo overlay
     */
    private function buildLogoFilter($logoPath, $position, $size, $opacity, $duration, $startTime, $endTime)
    {
        $x = $position['x'];
        $y = $position['y'];
        $width = $size['width'];
        $height = $size['height'];

        // Base overlay filter
        $filter = "[1:v]scale={$width}:{$height}[logo];[0:v][logo]overlay={$x}:{$y}";

        // Add opacity if not 100%
        if ($opacity < 1.0) {
            $filter = "[1:v]scale={$width}:{$height},format=rgba,colorchannelmixer=aa={$opacity}[logo];[0:v][logo]overlay={$x}:{$y}";
        }

        // Add timing if not full duration
        if ($duration === 'custom' && $endTime > $startTime) {
            $filter .= ":enable='between(t,{$startTime},{$endTime})'";
        } elseif ($duration === 'start') {
            $filter .= ":enable='between(t,0,5)'";
        } elseif ($duration === 'end') {
            // For end timing, we'd need video duration - will implement if needed
            $filter .= ":enable='gte(t,t-5)'";
        }

        return $filter;
    }
}
