<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\VideoGenerationTask;
use App\Models\GeneratedVideo;

class GenerateUniversalVideoCommand extends Command
{
    protected $signature = 'video:generate
                            {--platform= : Platform (tiktok/youtube/none)}
                            {--media-type= : Media type (images/video/mixed)}
                            {--script= : Script text for TikTok}
                            {--text= : Text content for YouTube TTS}
                            {--audio-file= : Audio file path for YouTube}
                            {--library-audio-id= : Audio library ID for using existing audio}
                            {--product-video= : Product video path for TikTok}
                            {--product-image= : Product image path for TikTok}
                            {--product-images= : Comma-separated product image paths for TikTok}
                            {--images= : Comma-separated image paths for YouTube}
                            {--background-video= : Background video path for YouTube}
                            {--video-content-type= : YouTube video content type (images/video/mixed)}
                            {--image-duration=3 : Duration for each image in seconds}
                            {--video-loop : Loop background video}
                            {--remove-video-audio : Remove audio from background video}
                            {--transition-effects= : Transition effects for YouTube}
                            {--voice=hn_female_ngochuyen_full_48k-fhg : Voice for TTS}
                            {--temp-dir= : Temporary directory for processing}
                            {--task-id= : Task ID for progress tracking}
                            {--bitrate=128 : Audio bitrate}
                            {--speed=1.0 : Speech speed}
                            {--volume=18 : Audio volume in dB (default: 18dB)}
                            {--output= : Output filename}
                            {--temp-dir= : Temporary directory}
                            {--use-logo : Use logo overlay (TikTok)}
                            {--logo-file= : Logo file path}
                            {--logo-position=bottom-right : Logo position}
                            {--logo-size=100 : Logo size in pixels}
                            {--subtitle-text= : Subtitle text}
                            {--subtitle-position=bottom : Subtitle position}
                            {--subtitle-size=24 : Subtitle font size}
                            {--subtitle-color=#FFFFFF : Subtitle text color}
                            {--subtitle-background=#000000 : Subtitle background color}
                            {--subtitle-font=Arial : Subtitle font family}
                            {--subtitle-duration=5 : Subtitle duration in seconds}
                            {--subtitle-timing-mode=auto : Subtitle timing mode (auto/image_sync/custom_timing)}
                            {--subtitle-per-image=auto : Subtitle per image mode (auto/sentence/word_count)}
                            {--words-per-image=10 : Number of words per image}
                            {--subtitle-delay=0.5 : Delay between subtitle segments}
                            {--subtitle-fade=in : Subtitle fade effect (none/in/out/both)}
                            {--resolution=1920x1080 : Video resolution for none platform}
                            {--fps=30 : Video FPS for none platform}
                            {--quality=high : Video quality for none platform (medium/high/very_high)}
                            {--slide-duration=3 : Duration for each slide in slideshow}
                            {--slide-transition=slide : Transition effect for slideshow}
                            {--video-sections= : JSON array of video sections with timing}
                            {--image-overlays= : JSON array of image overlays with timing and effects}
                            {--section-transitions= : JSON array of transition effects between sections}
                            {--per-image-subtitles= : JSON array of subtitles per image}
                            {--channel-metadata= : JSON object with channel upload metadata}
                            {--auto-upload=false : Auto upload to channel after generation}
                            {--task-id= : Task ID for progress tracking}';

    protected $description = 'Generate video for any platform (TikTok/YouTube/None) with unified processing';

    protected $taskId;
    protected $platform;
    protected $tempDir;

    public function handle()
    {
        $this->taskId = $this->option('task-id');
        $this->platform = $this->option('platform');
        $this->tempDir = $this->option('temp-dir');

        // Validate platform
        if (!in_array($this->platform, ['tiktok', 'youtube', 'none'])) {
            $this->error('Invalid platform. Must be tiktok, youtube, or none.');
            return 1;
        }

        $this->info("Starting {$this->platform} video generation...");
        $this->updateProgress(10, "Initializing {$this->platform} video generation");

        try {
            // Create temp directory if not exists
            if (!File::isDirectory($this->tempDir)) {
                File::makeDirectory($this->tempDir, 0755, true);
            }

            // Generate audio (optional)
            $this->updateProgress(20, 'Generating audio...');
            $audioPath = null;

            // Check if we need to generate audio based on platform and available options
            $needsAudio = $this->shouldGenerateAudio();

            if ($needsAudio) {
                $audioPath = $this->generateAudio();

                if (!$audioPath) {
                    $this->info('No audio generated - creating video without audio');
                }
            } else {
                $this->info('Audio generation skipped - not required for this configuration');
            }

            // Process video based on platform
            $this->updateProgress(40, 'Processing video content...');
            
            if ($this->platform === 'tiktok') {
                $videoPath = $this->processTikTokVideo($audioPath);
            } elseif ($this->platform === 'youtube') {
                $videoPath = $this->processYouTubeVideo($audioPath);
            } else { // none
                $videoPath = $this->processNoneVideo($audioPath);
            }

            if (!$videoPath) {
                throw new \Exception('Failed to process video');
            }

            // Add advanced features
            $this->updateProgress(70, 'Processing advanced features...');

            // Add image overlays if specified
            if ($this->option('image-overlays')) {
                $this->updateProgress(75, 'Adding image overlays...');
                $videoPath = $this->addImageOverlays($videoPath);
            }

            // Add subtitle if specified
            if ($this->option('subtitle-text') || $this->option('per-image-subtitles')) {
                $this->updateProgress(80, 'Adding subtitles...');
                $videoPath = $this->addAdvancedSubtitle($videoPath);
            }

            // Move to final location
            $this->updateProgress(90, 'Finalizing video...');
            $finalPath = $this->moveToFinalLocation($videoPath);

            // Save to database
            $this->updateProgress(95, 'Saving video information...');
            $this->saveVideoToDatabase($finalPath);

            $this->updateProgress(100, 'Video generation completed');
            $this->info("Video generated successfully: {$finalPath}");

            return 0;

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            Log::error("Universal video generation failed", [
                'platform' => $this->platform,
                'task_id' => $this->taskId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->updateProgress(0, 'Failed: ' . $e->getMessage());
            return 1;
        } finally {
            // Cleanup temp directory
            if (File::isDirectory($this->tempDir)) {
                File::deleteDirectory($this->tempDir);
            }
        }
    }

    /**
     * Check if audio generation is needed
     */
    private function shouldGenerateAudio()
    {
        // Check if we have text content for TTS
        $text = $this->option('script') ?: $this->option('text');

        // Check if we have an audio file provided
        $audioFile = $this->option('audio-file');

        // Check if we have background audio from library
        $libraryAudio = $this->option('library-audio-id');

        // We need audio generation if:
        // 1. We have text content for TTS, OR
        // 2. We have an audio file to use, OR
        // 3. We have library audio selected AND it exists
        $hasValidLibraryAudio = false;
        if (!empty($libraryAudio)) {
            $audio = \App\Models\AudioLibrary::find($libraryAudio);
            $hasValidLibraryAudio = $audio && $audio->fileExists();
        }

        return !empty($text) || !empty($audioFile) || $hasValidLibraryAudio;
    }

    /**
     * Generate audio from text or use uploaded file
     */
    private function generateAudio()
    {
        // Check for library audio first
        $libraryAudioId = $this->option('library-audio-id');
        if ($libraryAudioId) {
            $audio = \App\Models\AudioLibrary::find($libraryAudioId);
            if ($audio && $audio->fileExists()) {
                $this->info('Using library audio: ' . $audio->title);

                // Copy to temp directory
                $tempAudioPath = $this->tempDir . '/audio.mp3';
                File::copy($audio->getFullPath(), $tempAudioPath);
                return $tempAudioPath;
            } else {
                $this->warn('Library audio not found or file missing: ' . $libraryAudioId);
                // Continue to check other audio sources instead of failing
            }
        }

        if ($this->option('audio-file')) {
            // Use uploaded audio file (YouTube)
            $audioFile = $this->option('audio-file');
            if (!File::exists($audioFile)) {
                throw new \Exception("Audio file not found: {$audioFile}");
            }

            // Copy to temp directory
            $tempAudioPath = $this->tempDir . '/audio.mp3';
            File::copy($audioFile, $tempAudioPath);
            return $tempAudioPath;
        }

        // Generate TTS audio only if text is provided
        $text = $this->option('script') ?: $this->option('text');
        if (!$text) {
            // No text provided - skip TTS audio generation
            $this->info('No text content provided - skipping TTS audio generation');
            return null;
        }

        $voice = $this->option('voice') ?: 'hn_female_ngochuyen_full_48k-fhg';
        $bitrate = $this->option('bitrate') ?: 128;
        $speed = $this->option('speed') ?: 1.0;

        // Get VBee API credentials
        $appId = config('services.vbee.app_id');
        $accessToken = config('services.vbee.access_token');

        if (!$appId || !$accessToken) {
            throw new \Exception('VBee API credentials not configured');
        }

        // Call VBee TTS API with proper authentication
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer $accessToken",
        ])->timeout(120)->post('https://vbee.vn/api/v1/tts', [
            'app_id' => $appId,
            'input_text' => $text,
            'voice_code' => $voice,
            'audio_type' => 'mp3',
            'bitrate' => (int)$bitrate,
            'speed_rate' => (float)$speed,
            'response_type' => 'direct'
        ]);

        if (!$response->successful()) {
            throw new \Exception('TTS API request failed: ' . $response->body());
        }

        $responseData = $response->json();
        if (!isset($responseData['result']['audio_link'])) {
            throw new \Exception('TTS API response missing audio_link: ' . $response->body());
        }

        $audioUrl = $responseData['result']['audio_link'];
        $audioContent = Http::get($audioUrl)->body();

        if (!$audioContent) {
            throw new \Exception('Failed to download audio from VBee');
        }

        $audioPath = $this->tempDir . '/audio.mp3';
        File::put($audioPath, $audioContent);

        // Adjust volume if specified
        $volume = $this->option('volume');
        if ($volume != 0) {
            $adjustedAudioPath = $this->tempDir . '/audio_adjusted.mp3';
            $volumeFilter = $volume > 0 ? "volume={$volume}dB" : "volume={$volume}dB";
            
            $cmd = "ffmpeg -i \"{$audioPath}\" -af \"{$volumeFilter}\" \"{$adjustedAudioPath}\" -y";
            exec($cmd, $output, $returnCode);
            
            if ($returnCode === 0 && File::exists($adjustedAudioPath)) {
                File::delete($audioPath);
                $audioPath = $adjustedAudioPath;
            }
        }

        return $audioPath;
    }

    /**
     * Process TikTok video (9:16 aspect ratio)
     */
    private function processTikTokVideo($audioPath)
    {
        $mediaType = $this->option('media-type') ?: 'video';

        if ($mediaType === 'images') {
            return $this->processTikTokFromImages($audioPath);
        } else {
            return $this->processTikTokFromVideo($audioPath);
        }
    }

    /**
     * Process TikTok video from product video
     */
    private function processTikTokFromVideo($audioPath)
    {
        $productVideo = $this->option('product-video');

        if (!$productVideo || !File::exists($productVideo)) {
            throw new \Exception("Product video not found: {$productVideo}");
        }

        // Get duration based on template settings or audio
        $audioDuration = $this->calculateVideoDurationFromTemplate($audioPath);
        
        // Prepare video for TikTok (9:16, 1080x1920)
        $processedVideoPath = $this->tempDir . '/processed_video.mp4';
        
        // Scale and crop video to 9:16 aspect ratio
        $videoFilter = "scale=1080:1920:force_original_aspect_ratio=increase,crop=1080:1920";
        
        $cmd = "ffmpeg -i \"{$productVideo}\" -vf \"{$videoFilter}\" -t {$audioDuration} -c:v libx264 -preset fast -crf 23 \"{$processedVideoPath}\" -y";
        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0 || !File::exists($processedVideoPath)) {
            throw new \Exception('Failed to process TikTok video');
        }

        // Combine with audio if available
        if ($audioPath && File::exists($audioPath)) {
            $outputPath = $this->tempDir . '/tiktok_with_audio.mp4';
            $cmd = "ffmpeg -i \"{$processedVideoPath}\" -i \"{$audioPath}\" -c:v copy -c:a aac -shortest \"{$outputPath}\" -y";
            exec($cmd, $output, $returnCode);

            if ($returnCode !== 0 || !File::exists($outputPath)) {
                throw new \Exception('Failed to combine TikTok video with audio');
            }
        } else {
            // No audio - use processed video as final output
            $outputPath = $processedVideoPath;
            $this->info('No audio provided - creating video without audio');
        }

        // Add logo if specified
        if ($this->option('use-logo') && $this->option('logo-file')) {
            $outputPath = $this->addLogo($outputPath);
        }

        return $outputPath;
    }

    /**
     * Process TikTok video from images
     */
    private function processTikTokFromImages($audioPath)
    {
        // Try product-images first (for TikTok), then fallback to images (for compatibility)
        $images = $this->option('product-images') ?: $this->option('images');
        if (!$images) {
            throw new \Exception("No images provided for TikTok video generation");
        }

        $imageFiles = explode(',', $images);
        $imageFiles = array_filter($imageFiles, function($file) {
            $file = trim($file);
            // Convert to absolute path if relative
            if (!str_starts_with($file, '/') && !preg_match('/^[A-Z]:/i', $file)) {
                $file = base_path($file);
            }
            return File::exists($file);
        });

        if (empty($imageFiles)) {
            throw new \Exception("No valid image files found");
        }

        // Get duration based on template settings or audio
        $audioDuration = $this->calculateVideoDurationFromTemplate($audioPath);
        $imageDuration = $this->option('image-duration') ?: 3;

        // Create slideshow video from images
        $slideshowPath = $this->tempDir . '/slideshow.mp4';

        // Create input file list for ffmpeg
        $inputListPath = $this->tempDir . '/images.txt';
        $inputList = '';

        foreach ($imageFiles as $imageFile) {
            $imageFile = trim($imageFile);
            // Convert to absolute path if relative
            if (!str_starts_with($imageFile, '/') && !preg_match('/^[A-Z]:/i', $imageFile)) {
                $imageFile = base_path($imageFile);
            }
            $inputList .= "file '" . str_replace('\\', '/', $imageFile) . "'\n";
            $inputList .= "duration {$imageDuration}\n";
        }

        // Add last image again for proper duration
        if (!empty($imageFiles)) {
            $lastImage = trim(end($imageFiles));
            // Convert to absolute path if relative
            if (!str_starts_with($lastImage, '/') && !preg_match('/^[A-Z]:/i', $lastImage)) {
                $lastImage = base_path($lastImage);
            }
            $inputList .= "file '" . str_replace('\\', '/', $lastImage) . "'\n";
        }

        File::put($inputListPath, $inputList);

        // Create slideshow with 9:16 aspect ratio for TikTok
        $videoFilter = "scale=1080:1920:force_original_aspect_ratio=increase,crop=1080:1920";

        $cmd = "ffmpeg -f concat -safe 0 -i \"{$inputListPath}\" -vf \"{$videoFilter}\" -t {$audioDuration} -c:v libx264 -preset fast -crf 23 -pix_fmt yuv420p \"{$slideshowPath}\" -y";
        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0 || !File::exists($slideshowPath)) {
            throw new \Exception('Failed to create TikTok slideshow from images');
        }

        // Merge with audio if available
        if ($audioPath && File::exists($audioPath)) {
            $mergedVideoPath = $this->tempDir . '/merged_video.mp4';
            $cmd = "ffmpeg -i \"{$slideshowPath}\" -i \"{$audioPath}\" -c:v copy -c:a aac -shortest \"{$mergedVideoPath}\" -y";
            exec($cmd, $output, $returnCode);

            if ($returnCode !== 0 || !File::exists($mergedVideoPath)) {
                throw new \Exception('Failed to merge TikTok video with audio');
            }

            // Add subtitles and logo
            return $this->addSubtitlesAndLogo($mergedVideoPath);
        } else {
            // No audio - use slideshow as final output
            $this->info('No audio provided - creating slideshow without audio');

            // Add subtitles and logo
            return $this->addSubtitlesAndLogo($slideshowPath);
        }
    }

    /**
     * Process YouTube video (16:9 aspect ratio)
     */
    private function processYouTubeVideo($audioPath)
    {
        $contentType = $this->option('video-content-type');
        $audioDuration = $this->calculateVideoDurationFromTemplate($audioPath);

        switch ($contentType) {
            case 'images':
                return $this->createImageSlideshow($audioPath, $audioDuration);
            case 'video':
                return $this->processBackgroundVideo($audioPath, $audioDuration);
            case 'mixed':
                return $this->createMixedContent($audioPath, $audioDuration);
            default:
                throw new \Exception("Invalid video content type: {$contentType}");
        }
    }

    /**
     * Create image slideshow for YouTube
     */
    private function createImageSlideshow($audioPath, $audioDuration)
    {
        $imagesOption = $this->option('images');
        if (!$imagesOption) {
            throw new \Exception('No images provided for slideshow');
        }

        $imagePaths = explode(',', $imagesOption);
        $imageDuration = (float)$this->option('image-duration');
        $transitions = $this->option('transition-effects') ? explode(',', $this->option('transition-effects')) : ['fade'];

        // Create slideshow
        $slideshowPath = $this->tempDir . '/slideshow.mp4';
        
        // Build FFmpeg filter for slideshow
        $filterComplex = $this->buildSlideshowFilter($imagePaths, $imageDuration, $transitions, $audioDuration);
        
        $cmd = "ffmpeg " . implode(' ', array_map(function($path) {
            return "-i \"{$path}\"";
        }, $imagePaths)) . " -filter_complex \"{$filterComplex}\" -c:v libx264 -preset fast -crf 23 -pix_fmt yuv420p \"{$slideshowPath}\" -y";
        
        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0 || !File::exists($slideshowPath)) {
            throw new \Exception('Failed to create image slideshow');
        }

        // Combine with audio
        $outputPath = $this->tempDir . '/youtube_with_audio.mp4';
        $cmd = "ffmpeg -i \"{$slideshowPath}\" -i \"{$audioPath}\" -c:v copy -c:a aac -shortest \"{$outputPath}\" -y";
        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0 || !File::exists($outputPath)) {
            throw new \Exception('Failed to combine slideshow with audio');
        }

        return $outputPath;
    }

    /**
     * Process background video for YouTube
     */
    private function processBackgroundVideo($audioPath, $audioDuration)
    {
        $backgroundVideo = $this->option('background-video');
        if (!File::exists($backgroundVideo)) {
            throw new \Exception("Background video not found: {$backgroundVideo}");
        }

        $processedVideoPath = $this->tempDir . '/processed_bg_video.mp4';
        
        // Scale to 16:9 (1920x1080)
        $videoFilter = "scale=1920:1080:force_original_aspect_ratio=increase,crop=1920:1080";
        
        // Loop video if needed
        if ($this->option('video-loop')) {
            $videoDuration = $this->getMediaDuration($backgroundVideo);
            if ($videoDuration < $audioDuration) {
                $loopCount = ceil($audioDuration / $videoDuration);
                $videoFilter = "loop={$loopCount}:1:0," . $videoFilter;
            }
        }

        $cmd = "ffmpeg -i \"{$backgroundVideo}\" -vf \"{$videoFilter}\" -t {$audioDuration} -c:v libx264 -preset fast -crf 23 \"{$processedVideoPath}\" -y";
        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0 || !File::exists($processedVideoPath)) {
            throw new \Exception('Failed to process background video');
        }

        // Combine with audio
        $outputPath = $this->tempDir . '/youtube_with_audio.mp4';
        
        if ($this->option('remove-video-audio')) {
            $cmd = "ffmpeg -i \"{$processedVideoPath}\" -i \"{$audioPath}\" -c:v copy -c:a aac -map 0:v:0 -map 1:a:0 -shortest \"{$outputPath}\" -y";
        } else {
            $cmd = "ffmpeg -i \"{$processedVideoPath}\" -i \"{$audioPath}\" -filter_complex \"[0:a][1:a]amix=inputs=2[a]\" -map 0:v -map \"[a]\" -c:v copy -c:a aac -shortest \"{$outputPath}\" -y";
        }
        
        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0 || !File::exists($outputPath)) {
            throw new \Exception('Failed to combine background video with audio');
        }

        return $outputPath;
    }

    /**
     * Create mixed content (images + video)
     */
    private function createMixedContent($audioPath, $audioDuration)
    {
        // For now, prioritize images if both are provided
        if ($this->option('images')) {
            return $this->createImageSlideshow($audioPath, $audioDuration);
        } elseif ($this->option('background-video')) {
            return $this->processBackgroundVideo($audioPath, $audioDuration);
        } else {
            throw new \Exception('No content provided for mixed mode');
        }
    }

    /**
     * Build slideshow filter for FFmpeg
     */
    private function buildSlideshowFilter($imagePaths, $imageDuration, $transitions, $totalDuration)
    {
        $imageCount = count($imagePaths);
        $totalImageTime = $imageCount * $imageDuration;
        
        // If images are shorter than audio, loop them
        if ($totalImageTime < $totalDuration) {
            $loopCount = ceil($totalDuration / $totalImageTime);
            $imagePaths = array_merge(...array_fill(0, $loopCount, $imagePaths));
        }

        // Build filter for each image
        $filters = [];
        foreach ($imagePaths as $i => $path) {
            $filters[] = "[{$i}:v]scale=1920:1080:force_original_aspect_ratio=increase,crop=1920:1080,setpts=PTS-STARTPTS+{$i}/{$imageDuration}/TB[v{$i}]";
        }

        // Concatenate with transitions
        $concatInputs = implode('', array_map(function($i) { return "[v{$i}]"; }, range(0, count($imagePaths) - 1)));
        $filters[] = "{$concatInputs}concat=n=" . count($imagePaths) . ":v=1:a=0[out]";

        return implode(';', $filters);
    }

    /**
     * Add logo overlay (TikTok only)
     */
    private function addLogo($videoPath)
    {
        $logoFile = $this->option('logo-file');
        $logoPosition = $this->option('logo-position');
        $logoSize = $this->option('logo-size');

        if (!File::exists($logoFile)) {
            $this->warn("Logo file not found: {$logoFile}");
            return $videoPath;
        }

        $outputPath = $this->tempDir . '/video_with_logo.mp4';
        
        // Calculate logo position
        $overlay = $this->getLogoOverlayFilter($logoPosition, $logoSize);
        
        $cmd = "ffmpeg -i \"{$videoPath}\" -i \"{$logoFile}\" -filter_complex \"[1:v]scale={$logoSize}:{$logoSize}[logo];[0:v][logo]overlay={$overlay}\" -c:a copy \"{$outputPath}\" -y";
        exec($cmd, $output, $returnCode);

        if ($returnCode === 0 && File::exists($outputPath)) {
            File::delete($videoPath);
            return $outputPath;
        }

        $this->warn('Failed to add logo, using video without logo');
        return $videoPath;
    }

    /**
     * Add advanced subtitle overlay with timing modes
     */
    private function addAdvancedSubtitle($videoPath)
    {
        // Check for per-image subtitles first
        $perImageSubtitles = $this->option('per-image-subtitles');
        if ($perImageSubtitles) {
            return $this->addPerImageSubtitles($videoPath);
        }

        // Fall back to regular subtitle modes
        $timingMode = $this->option('subtitle-timing-mode') ?: 'auto';

        switch ($timingMode) {
            case 'image_sync':
                return $this->addImageSyncSubtitle($videoPath);
            case 'custom_timing':
                return $this->addCustomTimingSubtitle($videoPath);
            default:
                return $this->addAutoSubtitle($videoPath);
        }
    }

    /**
     * Add per-image subtitles
     */
    private function addPerImageSubtitles($videoPath)
    {
        $subtitlesJson = $this->option('per-image-subtitles');
        if (!$subtitlesJson) {
            return $videoPath;
        }

        try {
            $subtitles = json_decode($subtitlesJson, true);
            if (!$subtitles || !is_array($subtitles)) {
                $this->warn('Invalid per-image subtitles JSON format');
                return $videoPath;
            }

            $outputPath = $this->tempDir . '/video_with_per_image_subtitles.mp4';
            $imageDuration = $this->option('image-duration') ?: 3;

            // Build drawtext filters for each subtitle
            $filters = [];
            foreach ($subtitles as $subtitle) {
                $imageIndex = $subtitle['image_index'] ?? 0;
                $text = $subtitle['subtitle'] ?? '';
                $startTime = $imageIndex * $imageDuration;
                $endTime = $startTime + $imageDuration;

                if (!empty($text)) {
                    $position = $subtitle['position'] ?? $this->option('subtitle-position') ?: 'bottom';
                    $size = $subtitle['size'] ?? $this->option('subtitle-size') ?: 24;
                    $color = $subtitle['color'] ?? $this->option('subtitle-color') ?: '#FFFFFF';
                    $background = $subtitle['background'] ?? $this->option('subtitle-background') ?: '#000000';
                    $font = $subtitle['font'] ?? $this->option('subtitle-font') ?: 'Arial';

                    $filter = $this->buildDrawtextFilter($text, $position, $size, $color, $background, $font, $endTime - $startTime);
                    $filter = str_replace("enable='between(t,0,{$imageDuration})'", "enable='between(t,{$startTime},{$endTime})'", $filter);
                    $filters[] = $filter;
                }
            }

            if (!empty($filters)) {
                $filterComplex = implode(',', $filters);
                $cmd = "ffmpeg -i \"{$videoPath}\" -vf \"{$filterComplex}\" -c:a copy \"{$outputPath}\" -y";

                exec($cmd, $output, $returnCode);

                if ($returnCode === 0 && File::exists($outputPath)) {
                    return $outputPath;
                }
            }

        } catch (\Exception $e) {
            $this->warn('Error adding per-image subtitles: ' . $e->getMessage());
        }

        return $videoPath;
    }

    /**
     * Add subtitle with auto timing (original method)
     */
    private function addAutoSubtitle($videoPath)
    {
        $subtitleText = $this->option('subtitle-text');
        $position = $this->option('subtitle-position');
        $size = $this->option('subtitle-size');
        $color = $this->option('subtitle-color');
        $background = $this->option('subtitle-background');
        $font = $this->option('subtitle-font');
        $duration = $this->option('subtitle-duration');

        $outputPath = $this->tempDir . '/video_with_subtitle.mp4';

        // Build drawtext filter
        $drawtext = $this->buildDrawtextFilter($subtitleText, $position, $size, $color, $background, $font, $duration);

        $cmd = "ffmpeg -i \"{$videoPath}\" -vf \"{$drawtext}\" -c:a copy \"{$outputPath}\" -y";
        exec($cmd, $output, $returnCode);

        if ($returnCode === 0 && File::exists($outputPath)) {
            File::delete($videoPath);
            return $outputPath;
        }

        $this->warn('Failed to add subtitle, using video without subtitle');
        return $videoPath;
    }

    /**
     * Add subtitle synced with images
     */
    private function addImageSyncSubtitle($videoPath)
    {
        $subtitleText = $this->option('subtitle-text');
        $perImageMode = $this->option('subtitle-per-image') ?: 'auto';
        $wordsPerImage = (int) $this->option('words-per-image') ?: 10;

        // Get video duration and image count
        $videoDuration = $this->getVideoDuration($videoPath);
        $imageCount = $this->getImageCount();

        if ($imageCount <= 0) {
            // Fallback to auto mode if no images
            return $this->addAutoSubtitle($videoPath);
        }

        $imageDuration = $videoDuration / $imageCount;

        // Split subtitle text based on mode
        $subtitleSegments = $this->splitSubtitleForImages($subtitleText, $imageCount, $perImageMode, $wordsPerImage);

        return $this->addSegmentedSubtitle($videoPath, $subtitleSegments, $imageDuration);
    }

    /**
     * Add subtitle with custom timing
     */
    private function addCustomTimingSubtitle($videoPath)
    {
        $subtitleText = $this->option('subtitle-text');
        $duration = (float) $this->option('subtitle-duration') ?: 3.0;
        $delay = (float) $this->option('subtitle-delay') ?: 0.5;

        // Split text into sentences
        $sentences = preg_split('/[.!?]+/', $subtitleText, -1, PREG_SPLIT_NO_EMPTY);
        $sentences = array_map('trim', $sentences);

        $subtitleSegments = [];
        $currentTime = 0;

        foreach ($sentences as $sentence) {
            if (!empty($sentence)) {
                $subtitleSegments[] = [
                    'text' => $sentence,
                    'start' => $currentTime,
                    'end' => $currentTime + $duration
                ];
                $currentTime += $duration + $delay;
            }
        }

        return $this->addTimedSubtitle($videoPath, $subtitleSegments);
    }

    /**
     * Add subtitle overlay (legacy method for compatibility)
     */
    private function addSubtitle($videoPath)
    {
        return $this->addAutoSubtitle($videoPath);
    }

    /**
     * Move video to final location
     */
    private function moveToFinalLocation($videoPath)
    {
        $outputName = $this->option('output');
        if (!$outputName) {
            $outputName = $this->platform . '_video_' . date('Y-m-d_H-i-s') . '.mp4';
        }

        if (!str_ends_with($outputName, '.mp4')) {
            $outputName .= '.mp4';
        }

        $outputDir = storage_path("app/videos/generated");
        if (!File::isDirectory($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }

        $finalPath = $outputDir . '/' . $outputName;
        File::move($videoPath, $finalPath);

        return $finalPath;
    }

    /**
     * Get media duration in seconds
     */
    private function getMediaDuration($filePath)
    {
        $cmd = "ffprobe -v quiet -show_entries format=duration -of csv=p=0 \"{$filePath}\"";
        $duration = trim(shell_exec($cmd));
        return (float)$duration;
    }

    /**
     * Calculate video duration based on template settings
     */
    private function calculateVideoDurationFromTemplate($audioPath = null)
    {
        // Use default values since template options are not passed to command
        $imageDuration = $this->option('image-duration') ?: 3;

        // Simple calculation based on available media

        // If audio exists, use audio duration
        if ($audioPath && File::exists($audioPath)) {
            return $this->getMediaDuration($audioPath);
        }

        // If video exists, use video duration
        $videoPath = $this->option('background-video') ?: $this->option('product-video');
        if ($videoPath && File::exists($videoPath)) {
            return $this->getMediaDuration($videoPath);
        }

        // Calculate based on image count
        $imageCount = $this->getImageCount();
        if ($imageCount > 0) {
            return $imageCount * $imageDuration;
        }

        // Default fallback
        return 30;
    }

    /**
     * Get logo overlay filter position
     */
    private function getLogoOverlayFilter($position, $size)
    {
        $margin = 20;
        
        switch ($position) {
            case 'top-left':
                return "{$margin}:{$margin}";
            case 'top-right':
                return "W-w-{$margin}:{$margin}";
            case 'bottom-left':
                return "{$margin}:H-h-{$margin}";
            case 'bottom-right':
                return "W-w-{$margin}:H-h-{$margin}";
            case 'center':
                return "(W-w)/2:(H-h)/2";
            default:
                return "W-w-{$margin}:H-h-{$margin}"; // bottom-right
        }
    }

    /**
     * Build drawtext filter for subtitle
     */
    private function buildDrawtextFilter($text, $position, $size, $color, $background, $font, $duration)
    {
        // Escape text for FFmpeg
        $escapedText = str_replace([':', "'", '"'], ['\:', "'\''", '\"'], $text);
        
        // Get position coordinates
        $coords = $this->getSubtitlePosition($position);
        
        // Convert hex colors to RGB
        $textColor = $this->hexToRgb($color);
        $bgColor = $this->hexToRgb($background);
        
        return "drawtext=text='{$escapedText}':fontfile=/Windows/Fonts/arial.ttf:fontsize={$size}:fontcolor={$textColor}:box=1:boxcolor={$bgColor}@0.8:boxborderw=5:{$coords}:enable='between(t,0,{$duration})'";
    }

    /**
     * Get subtitle position coordinates
     */
    private function getSubtitlePosition($position)
    {
        $margin = $this->platform === 'tiktok' ? 50 : 30;
        
        switch ($position) {
            case 'top':
                return "x=(w-text_w)/2:y={$margin}";
            case 'center':
                return "x=(w-text_w)/2:y=(h-text_h)/2";
            case 'bottom':
                return "x=(w-text_w)/2:y=h-text_h-{$margin}";
            case 'top-left':
                return "x={$margin}:y={$margin}";
            case 'top-right':
                return "x=w-text_w-{$margin}:y={$margin}";
            case 'bottom-left':
                return "x={$margin}:y=h-text_h-{$margin}";
            case 'bottom-right':
                return "x=w-text_w-{$margin}:y=h-text_h-{$margin}";
            default:
                return "x=(w-text_w)/2:y=h-text_h-{$margin}"; // bottom center
        }
    }

    /**
     * Convert hex color to RGB
     */
    private function hexToRgb($hex)
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return "0x{$hex}";
    }

    /**
     * Get video duration in seconds
     */
    private function getVideoDuration($videoPath)
    {
        $cmd = "ffprobe -v quiet -show_entries format=duration -of csv=p=0 \"{$videoPath}\"";
        $output = shell_exec($cmd);
        return (float) trim($output);
    }

    /**
     * Get image count from options
     */
    private function getImageCount()
    {
        $images = $this->option('images');
        if ($images) {
            return count(explode(',', $images));
        }
        return 0;
    }

    /**
     * Split subtitle text for images
     */
    private function splitSubtitleForImages($text, $imageCount, $mode, $wordsPerImage)
    {
        switch ($mode) {
            case 'sentence':
                $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
                $sentences = array_map('trim', $sentences);
                return array_slice($sentences, 0, $imageCount);

            case 'word_count':
                $words = explode(' ', $text);
                $segments = [];
                for ($i = 0; $i < $imageCount; $i++) {
                    $start = $i * $wordsPerImage;
                    $segment = array_slice($words, $start, $wordsPerImage);
                    if (!empty($segment)) {
                        $segments[] = implode(' ', $segment);
                    }
                }
                return $segments;

            default: // auto
                $words = explode(' ', $text);
                $wordsPerSegment = ceil(count($words) / $imageCount);
                $segments = [];
                for ($i = 0; $i < $imageCount; $i++) {
                    $start = $i * $wordsPerSegment;
                    $segment = array_slice($words, $start, $wordsPerSegment);
                    if (!empty($segment)) {
                        $segments[] = implode(' ', $segment);
                    }
                }
                return $segments;
        }
    }

    /**
     * Add segmented subtitle (for image sync)
     */
    private function addSegmentedSubtitle($videoPath, $segments, $imageDuration)
    {
        $position = $this->option('subtitle-position');
        $size = $this->option('subtitle-size');
        $color = $this->option('subtitle-color');
        $background = $this->option('subtitle-background');
        $fade = $this->option('subtitle-fade') ?: 'in';

        $outputPath = $this->tempDir . '/video_with_segmented_subtitle.mp4';

        $drawtextFilters = [];
        foreach ($segments as $index => $text) {
            $startTime = $index * $imageDuration;
            $endTime = ($index + 1) * $imageDuration;

            $escapedText = str_replace([':', "'", '"'], ['\:', "'\''", '\"'], $text);
            $coords = $this->getSubtitlePosition($position);
            $textColor = $this->hexToRgb($color);
            $bgColor = $this->hexToRgb($background);

            $enableCondition = "between(t,{$startTime},{$endTime})";
            if ($fade === 'in' || $fade === 'both') {
                $fadeInDuration = min(0.5, $imageDuration / 4);
                $enableCondition = "between(t,{$startTime},{$endTime})*fade(in,{$startTime},{$fadeInDuration})";
            }

            $drawtextFilters[] = "drawtext=text='{$escapedText}':fontfile=/Windows/Fonts/arial.ttf:fontsize={$size}:fontcolor={$textColor}:box=1:boxcolor={$bgColor}@0.8:boxborderw=5:{$coords}:enable='{$enableCondition}'";
        }

        $filterComplex = implode(',', $drawtextFilters);
        $cmd = "ffmpeg -i \"{$videoPath}\" -vf \"{$filterComplex}\" -c:a copy \"{$outputPath}\" -y";
        exec($cmd, $output, $returnCode);

        if ($returnCode === 0 && File::exists($outputPath)) {
            File::delete($videoPath);
            return $outputPath;
        }

        $this->warn('Failed to add segmented subtitle, using video without subtitle');
        return $videoPath;
    }

    /**
     * Add timed subtitle (for custom timing)
     */
    private function addTimedSubtitle($videoPath, $segments)
    {
        $position = $this->option('subtitle-position');
        $size = $this->option('subtitle-size');
        $color = $this->option('subtitle-color');
        $background = $this->option('subtitle-background');
        $fade = $this->option('subtitle-fade') ?: 'in';

        $outputPath = $this->tempDir . '/video_with_timed_subtitle.mp4';

        $drawtextFilters = [];
        foreach ($segments as $segment) {
            $escapedText = str_replace([':', "'", '"'], ['\:', "'\''", '\"'], $segment['text']);
            $coords = $this->getSubtitlePosition($position);
            $textColor = $this->hexToRgb($color);
            $bgColor = $this->hexToRgb($background);

            $enableCondition = "between(t,{$segment['start']},{$segment['end']})";

            $drawtextFilters[] = "drawtext=text='{$escapedText}':fontfile=/Windows/Fonts/arial.ttf:fontsize={$size}:fontcolor={$textColor}:box=1:boxcolor={$bgColor}@0.8:boxborderw=5:{$coords}:enable='{$enableCondition}'";
        }

        $filterComplex = implode(',', $drawtextFilters);
        $cmd = "ffmpeg -i \"{$videoPath}\" -vf \"{$filterComplex}\" -c:a copy \"{$outputPath}\" -y";
        exec($cmd, $output, $returnCode);

        if ($returnCode === 0 && File::exists($outputPath)) {
            File::delete($videoPath);
            return $outputPath;
        }

        $this->warn('Failed to add timed subtitle, using video without subtitle');
        return $videoPath;
    }

    /**
     * Add subtitles and logo to video
     */
    private function addSubtitlesAndLogo($videoPath)
    {
        $outputPath = $videoPath;

        // Add logo if specified
        if ($this->option('use-logo') && $this->option('logo-file')) {
            $outputPath = $this->addLogo($outputPath);
        }

        // Add subtitle if specified
        if ($this->option('subtitle-text')) {
            $outputPath = $this->addAdvancedSubtitle($outputPath);
        }

        return $outputPath;
    }

    /**
     * Save video information to database
     */
    private function saveVideoToDatabase($finalPath)
    {
        try {
            $fileName = basename($finalPath);
            $fileSize = File::size($finalPath);
            $duration = $this->getMediaDuration($finalPath);

            // Generate title from parameters
            $title = $this->generateVideoTitle();

            // Collect metadata
            $metadata = [
                'script' => $this->option('script'),
                'text' => $this->option('text'),
                'voice' => $this->option('voice'),
                'bitrate' => $this->option('bitrate'),
                'speed' => $this->option('speed'),
                'volume' => $this->option('volume'),
                'media_type' => $this->option('media-type'),
                'images' => $this->option('images'),
                'product_video' => $this->option('product-video'),
                'background_video' => $this->option('background-video'),
                'use_logo' => $this->option('use-logo'),
                'subtitle_text' => $this->option('subtitle-text'),
                'generated_at' => now()->toISOString(),
            ];

            GeneratedVideo::create([
                'title' => $title,
                'description' => $this->generateVideoDescription(),
                'platform' => $this->platform,
                'media_type' => $this->option('media-type') ?: 'video',
                'file_path' => $finalPath,
                'file_name' => $fileName,
                'file_size' => $fileSize,
                'duration' => (int)$duration,
                'metadata' => $metadata,
                'status' => 'generated',
                'task_id' => $this->taskId && VideoGenerationTask::find($this->taskId) ? $this->taskId : null,
                'publish_to_channel' => $this->platform !== 'none',
                'auto_publish' => false,
                'channel_id' => null,
            ]);

            $this->info("Video information saved to database");
        } catch (\Exception $e) {
            $this->warn("Failed to save video to database: " . $e->getMessage());
        }
    }

    /**
     * Generate video title
     */
    private function generateVideoTitle()
    {
        $platform = ucfirst($this->platform);
        $mediaType = $this->option('media-type') ?: 'video';
        $timestamp = now()->format('Y-m-d H:i');

        if ($this->option('script')) {
            $content = substr($this->option('script'), 0, 50);
        } elseif ($this->option('text')) {
            $content = substr($this->option('text'), 0, 50);
        } else {
            $content = 'Generated Content';
        }

        return "{$platform} Video - {$content}... ({$timestamp})";
    }

    /**
     * Generate video description
     */
    private function generateVideoDescription()
    {
        $description = "Video được tạo tự động cho platform {$this->platform}\n";
        $description .= "Loại media: " . ($this->option('media-type') ?: 'video') . "\n";

        if ($this->option('script')) {
            $description .= "Script: " . $this->option('script') . "\n";
        }

        if ($this->option('text')) {
            $description .= "Text: " . $this->option('text') . "\n";
        }

        $description .= "Tạo lúc: " . now()->format('d/m/Y H:i:s');

        return $description;
    }

    /**
     * Update task progress
     */
    private function updateProgress($percentage, $message = null)
    {
        if (!$this->taskId) {
            return;
        }

        try {
            $task = VideoGenerationTask::find($this->taskId);
            if ($task) {
                $updateData = ['progress' => $percentage];
                if ($message) {
                    $updateData['result'] = array_merge($task->result ?: [], ['current_step' => $message]);
                }
                $task->update($updateData);
            }
        } catch (\Exception $e) {
            // Ignore progress update errors
        }

        if ($message) {
            $this->info("Progress: {$percentage}% - {$message}");
        }
    }

    /**
     * Process video without channel publishing (flexible aspect ratio)
     */
    private function processNoneVideo($audioPath)
    {
        $mediaType = $this->option('media-type') ?: 'images';
        $audioDuration = $this->getMediaDuration($audioPath);

        // Get resolution from options or use default
        $resolution = $this->option('resolution') ?: '1920x1080';
        list($width, $height) = explode('x', $resolution);

        // Get FPS from options or use default
        $fps = $this->option('fps') ?: 30;

        // Get quality from options or use default (map to ffmpeg presets)
        $qualityOption = $this->option('quality') ?: 'high';
        $quality = $this->mapQualityToPreset($qualityOption);

        switch ($mediaType) {
            case 'images':
                return $this->createNoneImageSlideshow($audioPath, $audioDuration, $width, $height, $fps, $quality);

            case 'video':
                return $this->createNoneVideoWithAudio($audioPath, $audioDuration, $width, $height, $fps, $quality);

            default:
                throw new \Exception("Unsupported media type: {$mediaType}");
        }
    }

    /**
     * Create image slideshow for none platform
     */
    private function createNoneImageSlideshow($audioPath, $audioDuration, $width, $height, $fps, $quality)
    {
        $images = $this->option('images');
        if (!$images) {
            throw new \Exception('No images provided for slideshow');
        }

        $imagePaths = explode(',', $images);
        $slideDuration = $this->option('slide-duration') ?: 3;
        $transition = $this->option('slide-transition') ?: 'slide';

        $outputPath = $this->tempDir . '/' . ($this->option('output') ?: 'video_none.mp4');

        // Create slideshow with custom resolution
        $cmd = "ffmpeg -y ";

        // Input images with duration
        foreach ($imagePaths as $index => $imagePath) {
            $cmd .= "-loop 1 -t {$slideDuration} -i \"{$imagePath}\" ";
        }

        // Add audio
        $cmd .= "-i \"{$audioPath}\" ";

        // Video filters for slideshow with transitions
        $filterComplex = "";
        $imageCount = count($imagePaths);

        for ($i = 0; $i < $imageCount; $i++) {
            $filterComplex .= "[{$i}:v]scale={$width}:{$height}:force_original_aspect_ratio=decrease,pad={$width}:{$height}:(ow-iw)/2:(oh-ih)/2,setsar=1,fps={$fps}[v{$i}];";
        }

        // Concatenate videos
        for ($i = 0; $i < $imageCount; $i++) {
            $filterComplex .= "[v{$i}]";
        }
        $filterComplex .= "concat=n={$imageCount}:v=1:a=0[outv]";

        $cmd .= "-filter_complex \"{$filterComplex}\" ";
        $cmd .= "-map \"[outv]\" -map \"{$imageCount}:a\" ";
        $cmd .= "-c:v libx264 -preset {$quality} -crf 23 ";
        $cmd .= "-c:a aac -b:a 128k ";
        $cmd .= "-t {$audioDuration} ";
        $cmd .= "\"{$outputPath}\"";

        $this->info("Creating image slideshow: {$width}x{$height} @ {$fps}fps");
        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('Failed to create image slideshow: ' . implode("\n", $output));
        }

        return $outputPath;
    }

    /**
     * Create video with audio for none platform
     */
    private function createNoneVideoWithAudio($audioPath, $audioDuration, $width, $height, $fps, $quality)
    {
        $productVideo = $this->option('product-video');
        if (!$productVideo) {
            throw new \Exception('No product video provided');
        }

        $outputPath = $this->tempDir . '/' . ($this->option('output') ?: 'video_none.mp4');

        // Create video with custom resolution
        $cmd = "ffmpeg -y ";
        $cmd .= "-i \"{$productVideo}\" ";
        $cmd .= "-i \"{$audioPath}\" ";
        $cmd .= "-filter_complex \"[0:v]scale={$width}:{$height}:force_original_aspect_ratio=decrease,pad={$width}:{$height}:(ow-iw)/2:(oh-ih)/2,setsar=1,fps={$fps}[outv]\" ";
        $cmd .= "-map \"[outv]\" -map \"1:a\" ";
        $cmd .= "-c:v libx264 -preset {$quality} -crf 23 ";
        $cmd .= "-c:a aac -b:a 128k ";
        $cmd .= "-t {$audioDuration} ";
        $cmd .= "\"{$outputPath}\"";

        $this->info("Creating video: {$width}x{$height} @ {$fps}fps");
        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('Failed to create video: ' . implode("\n", $output));
        }

        return $outputPath;
    }

    /**
     * Map quality option to ffmpeg preset
     */
    private function mapQualityToPreset($quality)
    {
        switch ($quality) {
            case 'medium':
                return 'fast';
            case 'high':
                return 'medium';
            case 'very_high':
                return 'slow';
            default:
                return 'medium';
        }
    }

    /**
     * Add image overlays to video
     */
    private function addImageOverlays($videoPath)
    {
        $overlaysJson = $this->option('image-overlays');
        if (!$overlaysJson) {
            return $videoPath;
        }

        try {
            $overlays = json_decode($overlaysJson, true);
            if (!$overlays || !is_array($overlays)) {
                $this->warn('Invalid image overlays JSON format');
                return $videoPath;
            }

            $outputPath = $this->tempDir . '/video_with_overlays.mp4';
            $filterComplex = '';
            $inputs = "-i \"{$videoPath}\"";
            $overlayCount = 0;

            foreach ($overlays as $index => $overlay) {
                if (!isset($overlay['image']) || !File::exists($overlay['image'])) {
                    continue;
                }

                $inputs .= " -i \"{$overlay['image']}\"";
                $overlayCount++;

                // Build overlay filter
                $startTime = $overlay['start_time'] ?? 0;
                $duration = $overlay['duration'] ?? 3;
                $position = $this->getOverlayPosition($overlay['position'] ?? 'center');
                $opacity = $overlay['opacity'] ?? 0.8;
                $effect = $overlay['effect'] ?? 'none';

                $overlayFilter = $this->buildOverlayFilter($index + 1, $position, $opacity, $effect, $startTime, $duration);

                if ($index === 0) {
                    $filterComplex = "[0:v]{$overlayFilter}[v{$index}]";
                } else {
                    $filterComplex .= ";[v" . ($index - 1) . "]{$overlayFilter}[v{$index}]";
                }
            }

            if ($overlayCount > 0) {
                $finalOutput = "[v" . (count($overlays) - 1) . "]";
                $cmd = "ffmpeg {$inputs} -filter_complex \"{$filterComplex}\" -map \"{$finalOutput}\" -map 0:a? -c:v libx264 -c:a aac \"{$outputPath}\" -y";

                exec($cmd, $output, $returnCode);

                if ($returnCode === 0 && File::exists($outputPath)) {
                    return $outputPath;
                }
            }

        } catch (\Exception $e) {
            $this->warn('Error adding image overlays: ' . $e->getMessage());
        }

        return $videoPath;
    }

    /**
     * Get overlay position coordinates
     */
    private function getOverlayPosition($position)
    {
        switch ($position) {
            case 'top-left':
                return 'x=10:y=10';
            case 'top-center':
                return 'x=(main_w-overlay_w)/2:y=10';
            case 'top-right':
                return 'x=main_w-overlay_w-10:y=10';
            case 'center-left':
                return 'x=10:y=(main_h-overlay_h)/2';
            case 'center':
                return 'x=(main_w-overlay_w)/2:y=(main_h-overlay_h)/2';
            case 'center-right':
                return 'x=main_w-overlay_w-10:y=(main_h-overlay_h)/2';
            case 'bottom-left':
                return 'x=10:y=main_h-overlay_h-10';
            case 'bottom-center':
                return 'x=(main_w-overlay_w)/2:y=main_h-overlay_h-10';
            case 'bottom-right':
                return 'x=main_w-overlay_w-10:y=main_h-overlay_h-10';
            default:
                return 'x=(main_w-overlay_w)/2:y=(main_h-overlay_h)/2';
        }
    }

    /**
     * Build overlay filter with effects
     */
    private function buildOverlayFilter($inputIndex, $position, $opacity, $effect, $startTime, $duration)
    {
        $baseFilter = "[{$inputIndex}:v]";

        // Add opacity
        if ($opacity < 1.0) {
            $baseFilter .= "format=rgba,colorchannelmixer=aa={$opacity}";
        }

        // Add effects
        switch ($effect) {
            case 'fade_in':
                $baseFilter .= ",fade=t=in:st={$startTime}:d=0.5:alpha=1";
                break;
            case 'fade_out':
                $endTime = $startTime + $duration - 0.5;
                $baseFilter .= ",fade=t=out:st={$endTime}:d=0.5:alpha=1";
                break;
            case 'fade_in_out':
                $endTime = $startTime + $duration - 0.5;
                $baseFilter .= ",fade=t=in:st={$startTime}:d=0.5:alpha=1,fade=t=out:st={$endTime}:d=0.5:alpha=1";
                break;
        }

        $baseFilter .= "[overlay{$inputIndex}];[0:v][overlay{$inputIndex}]overlay={$position}:enable='between(t,{$startTime}," . ($startTime + $duration) . ")'";

        return $baseFilter;
    }
}
