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
                            {--media-type= : Media type}
                            {--library-audio-id= : Library audio ID}
                            {--slide-duration= : Slide duration}
                            {--slide-transition= : Slide transition}
                            {--product-images= : Product images}
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
                            {--max-duration= : Max duration}';

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
            
            Log::info('🔥🔥🔥 FORCE OVERRIDE: Initialization complete', [
                'temp_dir' => $tempDir,
                'output_filename' => $outputFilename
            ]);
            
            // Step 1: Create base video
            $baseVideoPath = $this->createBaseVideo($tempDir);
            
            Log::info('🔥🔥🔥 FORCE OVERRIDE: Base video created', [
                'video_path' => $baseVideoPath
            ]);
            
            // Step 2: Add Vietnamese subtitle if provided
            $subtitleText = $this->option('subtitle-text');
            
            Log::info('🔥🔥🔥 FORCE OVERRIDE: About to process subtitle options 🔥🔥🔥', [
                'subtitle_text_exists' => !empty($subtitleText),
                'subtitle_text_preview' => substr($subtitleText ?? '', 0, 50),
                'subtitle_text_length' => strlen($subtitleText ?? ''),
                'file_name' => __FILE__,
                'line_number' => __LINE__,
                'timestamp' => now()->toDateTimeString()
            ]);
            
            $finalVideoPath = $baseVideoPath;
            
            if (!empty($subtitleText)) {
                Log::info('🚨 FORCE OVERRIDE: Using Vietnamese subtitle service directly', [
                    'video_path' => $baseVideoPath,
                    'subtitle_text_preview' => substr($subtitleText, 0, 50),
                    'method' => 'VideoSubtitleService::createVideoWithVietnameseSubtitle'
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
                    'font_size' => $this->option('subtitle-size') ?: 24,
                    'font_color' => $this->option('subtitle-color') ?: 'white',
                    'position' => $this->option('subtitle-position') ?: 'bottom',
                    'font_name' => 'Arial Unicode MS',
                    'hard_subtitle' => true,
                    'encoding' => 'UTF-8'
                ];
                
                $result = $videoSubtitleService->createVideoWithVietnameseSubtitle(
                    $baseVideoPath,
                    $subtitleText,
                    null, // audio duration
                    $subtitleOptions
                );
                
                if ($result['success']) {
                    $finalVideoPath = $result['output_path'];
                    Log::info('🎯 FORCE OVERRIDE: Vietnamese subtitle SUCCESS', [
                        'result_video_path' => $finalVideoPath
                    ]);
                } else {
                    Log::warning('🔥🔥🔥 FORCE OVERRIDE: Failed to add Vietnamese subtitle, using base video', [
                        'error' => $result['error'] ?? 'Unknown error'
                    ]);
                }
            }
            
            // Step 3: Move to final location
            $outputPath = storage_path('app/videos/' . $outputFilename);
            
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
        $productImages = $this->option('product-images');
        $mediaType = $this->option('media-type');
        $slideDuration = $this->option('slide-duration') ?: 30;

        Log::info('🔥🔥🔥 FORCE OVERRIDE: Creating base video from real inputs', [
            'media_type' => $mediaType,
            'product_images' => $productImages,
            'slide_duration' => $slideDuration,
            'temp_dir' => $tempDir
        ]);

        if ($mediaType === 'images' && !empty($productImages)) {
            return $this->createVideoFromImages($productImages, $tempDir, $slideDuration);
        } else {
            // Fallback: create simple video
            Log::warning('🔥🔥🔥 FORCE OVERRIDE: No real images provided, creating simple video', [
                'media_type' => $mediaType,
                'product_images' => $productImages
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

        Log::info('🔥🔥🔥 FORCE OVERRIDE: Creating video from real images', [
            'image_count' => count($imagePaths),
            'slide_duration' => $slideDuration,
            'first_image' => $imagePaths[0] ?? 'none'
        ]);

        if (count($imagePaths) === 1) {
            // Single image - create looping video
            $imagePath = trim($imagePaths[0]);

            if (!File::exists($imagePath)) {
                throw new \Exception('Image file not found: ' . $imagePath);
            }

            $cmd = "ffmpeg -loop 1 -i \"{$imagePath}\" -vf \"scale=1920:1080:force_original_aspect_ratio=increase,crop=1920:1080\" -t {$slideDuration} -c:v libx264 -preset fast -crf 23 -pix_fmt yuv420p -r 25 \"{$videoPath}\" -y";

            Log::info('🔥🔥🔥 FORCE OVERRIDE: Single image FFmpeg command', [
                'command' => $cmd,
                'image_path' => $imagePath
            ]);

            exec($cmd, $output, $returnCode);

            if ($returnCode === 0 && File::exists($videoPath)) {
                Log::info('🔥🔥🔥 FORCE OVERRIDE: Single image video created successfully');
                return $videoPath;
            } else {
                throw new \Exception('Failed to create video from single image. Return code: ' . $returnCode);
            }
        } else {
            // Multiple images slideshow
            $inputListPath = $tempDir . '/images.txt';
            $inputList = '';

            foreach ($imagePaths as $image) {
                $imagePath = trim($image);
                if (File::exists($imagePath)) {
                    $inputList .= "file '" . str_replace('\\', '/', $imagePath) . "'\n";
                    $inputList .= "duration {$slideDuration}\n";
                }
            }

            // Add last image again for proper duration
            if (!empty($imagePaths)) {
                $lastImage = trim(end($imagePaths));
                if (File::exists($lastImage)) {
                    $inputList .= "file '" . str_replace('\\', '/', $lastImage) . "'\n";
                }
            }

            File::put($inputListPath, $inputList);

            $cmd = "ffmpeg -f concat -safe 0 -i \"{$inputListPath}\" -vf \"scale=1920:1080:force_original_aspect_ratio=increase,crop=1920:1080\" -c:v libx264 -preset fast -crf 23 -pix_fmt yuv420p -r 25 \"{$videoPath}\" -y";

            Log::info('🔥🔥🔥 FORCE OVERRIDE: Multiple images FFmpeg command', [
                'command' => $cmd,
                'input_list_content' => $inputList
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
     * Create simple fallback video
     */
    private function createSimpleVideo($tempDir)
    {
        $videoPath = $tempDir . '/merged_video.mp4';

        if (!File::exists($videoPath)) {
            $command = "ffmpeg -f lavfi -i color=blue:size=1920x1080:duration=30 -c:v libx264 -pix_fmt yuv420p \"{$videoPath}\" -y";
            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new \Exception('Failed to create simple video file');
            }
        }

        return $videoPath;
    }
}
