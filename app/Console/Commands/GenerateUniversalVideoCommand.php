<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\VideoGenerationTask;

class GenerateUniversalVideoCommand extends Command
{
    protected $signature = 'video:generate 
                            {--platform= : Platform (tiktok/youtube)}
                            {--script= : Script text for TikTok}
                            {--text= : Text content for YouTube TTS}
                            {--audio-file= : Audio file path for YouTube}
                            {--product-video= : Product video path for TikTok}
                            {--product-image= : Product image path for TikTok}
                            {--images= : Comma-separated image paths for YouTube}
                            {--background-video= : Background video path for YouTube}
                            {--video-content-type= : YouTube video content type (images/video/mixed)}
                            {--image-duration=3 : Duration for each image in seconds}
                            {--video-loop : Loop background video}
                            {--remove-video-audio : Remove audio from background video}
                            {--transition-effects= : Transition effects for YouTube}
                            {--voice=hn_female_ngochuyen_full_48k-fhg : Voice for TTS}
                            {--bitrate=128 : Audio bitrate}
                            {--speed=1.0 : Speech speed}
                            {--volume=18 : Audio volume in dB}
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
                            {--task-id= : Task ID for progress tracking}';

    protected $description = 'Generate video for any platform (TikTok/YouTube) with unified processing';

    protected $taskId;
    protected $platform;
    protected $tempDir;

    public function handle()
    {
        $this->taskId = $this->option('task-id');
        $this->platform = $this->option('platform');
        $this->tempDir = $this->option('temp-dir');

        // Validate platform
        if (!in_array($this->platform, ['tiktok', 'youtube'])) {
            $this->error('Invalid platform. Must be tiktok or youtube.');
            return 1;
        }

        $this->info("Starting {$this->platform} video generation...");
        $this->updateProgress(10, "Initializing {$this->platform} video generation");

        try {
            // Create temp directory if not exists
            if (!File::isDirectory($this->tempDir)) {
                File::makeDirectory($this->tempDir, 0755, true);
            }

            // Generate audio
            $this->updateProgress(20, 'Generating audio...');
            $audioPath = $this->generateAudio();

            if (!$audioPath) {
                throw new \Exception('Failed to generate audio');
            }

            // Process video based on platform
            $this->updateProgress(40, 'Processing video content...');
            
            if ($this->platform === 'tiktok') {
                $videoPath = $this->processTikTokVideo($audioPath);
            } else {
                $videoPath = $this->processYouTubeVideo($audioPath);
            }

            if (!$videoPath) {
                throw new \Exception('Failed to process video');
            }

            // Add subtitle if specified
            if ($this->option('subtitle-text')) {
                $this->updateProgress(80, 'Adding subtitles...');
                $videoPath = $this->addSubtitle($videoPath);
            }

            // Move to final location
            $this->updateProgress(90, 'Finalizing video...');
            $finalPath = $this->moveToFinalLocation($videoPath);

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
     * Generate audio from text or use uploaded file
     */
    private function generateAudio()
    {
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

        // Generate TTS audio
        $text = $this->option('script') ?: $this->option('text');
        if (!$text) {
            throw new \Exception('No text content provided for TTS');
        }

        $voice = $this->option('voice');
        $bitrate = $this->option('bitrate');
        $speed = $this->option('speed');

        // Call VBee TTS API
        $response = Http::timeout(120)->post('https://vbee.vn/api/v1/convert-text-to-speech', [
            'text' => $text,
            'voice_code' => $voice,
            'bitrate' => (int)$bitrate,
            'speed' => (float)$speed,
            'format' => 'mp3'
        ]);

        if (!$response->successful()) {
            throw new \Exception('TTS API request failed: ' . $response->body());
        }

        $audioPath = $this->tempDir . '/audio.mp3';
        File::put($audioPath, $response->body());

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
        $productVideo = $this->option('product-video');
        $productImage = $this->option('product-image');

        if (!File::exists($productVideo)) {
            throw new \Exception("Product video not found: {$productVideo}");
        }

        // Get audio duration
        $audioDuration = $this->getMediaDuration($audioPath);
        
        // Prepare video for TikTok (9:16, 1080x1920)
        $processedVideoPath = $this->tempDir . '/processed_video.mp4';
        
        // Scale and crop video to 9:16 aspect ratio
        $videoFilter = "scale=1080:1920:force_original_aspect_ratio=increase,crop=1080:1920";
        
        $cmd = "ffmpeg -i \"{$productVideo}\" -vf \"{$videoFilter}\" -t {$audioDuration} -c:v libx264 -preset fast -crf 23 \"{$processedVideoPath}\" -y";
        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0 || !File::exists($processedVideoPath)) {
            throw new \Exception('Failed to process TikTok video');
        }

        // Combine with audio
        $outputPath = $this->tempDir . '/tiktok_with_audio.mp4';
        $cmd = "ffmpeg -i \"{$processedVideoPath}\" -i \"{$audioPath}\" -c:v copy -c:a aac -shortest \"{$outputPath}\" -y";
        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0 || !File::exists($outputPath)) {
            throw new \Exception('Failed to combine TikTok video with audio');
        }

        // Add logo if specified
        if ($this->option('use-logo') && $this->option('logo-file')) {
            $outputPath = $this->addLogo($outputPath);
        }

        return $outputPath;
    }

    /**
     * Process YouTube video (16:9 aspect ratio)
     */
    private function processYouTubeVideo($audioPath)
    {
        $contentType = $this->option('video-content-type');
        $audioDuration = $this->getMediaDuration($audioPath);

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
     * Add subtitle overlay
     */
    private function addSubtitle($videoPath)
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
}
