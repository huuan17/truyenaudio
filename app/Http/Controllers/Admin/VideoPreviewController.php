<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\SubtitleService;
use App\Services\VideoSubtitleService;

class VideoPreviewController extends Controller
{
    private $subtitleService;
    private $videoSubtitleService;

    public function __construct(SubtitleService $subtitleService, VideoSubtitleService $videoSubtitleService)
    {
        $this->subtitleService = $subtitleService;
        $this->videoSubtitleService = $videoSubtitleService;
    }
    /**
     * Upload files for preview
     */
    public function uploadFiles(Request $request)
    {
        try {
            $files = $request->file('files', []);
            $type = $request->input('type', 'images');

            if (empty($files)) {
                throw new \Exception('No files uploaded');
            }

            $uploadedPaths = [];
            $tempDir = storage_path('app/temp/preview_uploads');

            // Create temp directory
            if (!File::isDirectory($tempDir)) {
                File::makeDirectory($tempDir, 0755, true);
            }

            foreach ($files as $file) {
                if (!$file->isValid()) {
                    continue;
                }

                // Generate unique filename
                $filename = uniqid('preview_') . '.' . $file->getClientOriginalExtension();
                $filePath = $tempDir . '/' . $filename;

                // Move file to temp directory
                $file->move($tempDir, $filename);

                $uploadedPaths[] = $filePath;
            }

            return response()->json([
                'success' => true,
                'file_paths' => $uploadedPaths,
                'count' => count($uploadedPaths)
            ]);

        } catch (\Exception $e) {
            Log::error('Preview file upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate preview video with current components
     */
    public function generatePreview(Request $request)
    {
        try {
            $components = $request->input('components', []);
            $previewId = uniqid('preview_');
            $tempDir = storage_path("app/temp/preview/{$previewId}");
            
            // Create temp directory
            if (!File::isDirectory($tempDir)) {
                File::makeDirectory($tempDir, 0755, true);
            }
            
            Log::info('Preview generation started', [
                'preview_id' => $previewId,
                'components' => $components
            ]);
            
            // Generate preview based on available components
            $previewPath = $this->buildPreview($components, $tempDir);
            
            if (!$previewPath || !File::exists($previewPath)) {
                throw new \Exception('Failed to generate preview');
            }
            
            // Move to public preview directory
            $publicPreviewDir = public_path('previews');
            if (!File::isDirectory($publicPreviewDir)) {
                File::makeDirectory($publicPreviewDir, 0755, true);
            }
            
            $publicPreviewPath = $publicPreviewDir . '/' . $previewId . '.mp4';
            File::move($previewPath, $publicPreviewPath);
            
            // Clean up temp directory
            if (File::isDirectory($tempDir)) {
                File::deleteDirectory($tempDir);
            }
            
            return response()->json([
                'success' => true,
                'preview_url' => asset('previews/' . $previewId . '.mp4'),
                'preview_id' => $previewId
            ]);
            
        } catch (\Exception $e) {
            Log::error('Preview generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Build preview video from components
     */
    private function buildPreview($components, $tempDir)
    {
        $previewPath = $tempDir . '/preview.mp4';
        
        // Step 1: Create base video from images
        if (isset($components['images']) && !empty($components['images'])) {
            $previewPath = $this->createImageSlideshow($components['images'], $tempDir);
        } else {
            // Create black video if no images
            $previewPath = $this->createBlackVideo($tempDir);
        }
        
        // Step 2: Add audio if available
        if (isset($components['audio']) && !empty($components['audio'])) {
            $previewPath = $this->addAudioToPreview($previewPath, $components['audio'], $tempDir);
        }
        
        // Step 3: Add subtitle if available
        if (isset($components['subtitle']) && !empty($components['subtitle'])) {
            $previewPath = $this->addSubtitleToPreview($previewPath, $components['subtitle'], $tempDir);
        }
        
        return $previewPath;
    }
    
    /**
     * Create slideshow from images
     */
    private function createImageSlideshow($images, $tempDir)
    {
        $slideshowPath = $tempDir . '/slideshow.mp4';
        $imageDuration = 3; // 3 seconds per image
        $totalDuration = count($images) * $imageDuration;

        // Default to TikTok resolution for preview
        $resolution = '1080:1920'; // 9:16 aspect ratio

        if (count($images) === 1) {
            // Single image
            $imagePath = $this->getImagePath($images[0]);
            $cmd = "ffmpeg -loop 1 -i \"{$imagePath}\" -vf \"scale={$resolution}:force_original_aspect_ratio=increase,crop={$resolution}\" -t {$totalDuration} -c:v libx264 -preset fast -crf 23 -pix_fmt yuv420p -r 25 \"{$slideshowPath}\" -y";
        } else {
            // Multiple images
            $inputListPath = $tempDir . '/images.txt';
            $inputList = '';
            
            foreach ($images as $image) {
                $imagePath = $this->getImagePath($image);
                $inputList .= "file '" . str_replace('\\', '/', $imagePath) . "'\n";
                $inputList .= "duration {$imageDuration}\n";
            }
            
            // Add last image again for proper duration
            if (!empty($images)) {
                $lastImagePath = $this->getImagePath(end($images));
                $inputList .= "file '" . str_replace('\\', '/', $lastImagePath) . "'\n";
            }
            
            File::put($inputListPath, $inputList);

            $cmd = "ffmpeg -f concat -safe 0 -i \"{$inputListPath}\" -vf \"scale={$resolution}:force_original_aspect_ratio=increase,crop={$resolution}\" -t {$totalDuration} -c:v libx264 -preset fast -crf 23 -pix_fmt yuv420p \"{$slideshowPath}\" -y";
        }
        
        exec($cmd, $output, $returnCode);
        
        if ($returnCode !== 0 || !File::exists($slideshowPath)) {
            throw new \Exception('Failed to create image slideshow for preview');
        }
        
        return $slideshowPath;
    }
    
    /**
     * Create black video as fallback
     */
    private function createBlackVideo($tempDir)
    {
        $blackVideoPath = $tempDir . '/black.mp4';
        $duration = 10; // 10 seconds default
        
        $cmd = "ffmpeg -f lavfi -i color=black:size=1080x1920:duration={$duration} -c:v libx264 -preset fast -crf 23 -pix_fmt yuv420p \"{$blackVideoPath}\" -y";
        exec($cmd, $output, $returnCode);
        
        if ($returnCode !== 0 || !File::exists($blackVideoPath)) {
            throw new \Exception('Failed to create black video for preview');
        }
        
        return $blackVideoPath;
    }
    
    /**
     * Add audio to preview
     */
    private function addAudioToPreview($videoPath, $audioData, $tempDir)
    {
        $outputPath = $tempDir . '/preview_with_audio.mp4';
        
        if (isset($audioData['type']) && $audioData['type'] === 'tts') {
            // Generate TTS audio
            $audioPath = $this->generateTTSAudio($audioData['text'], $tempDir);
        } else {
            // Use uploaded audio file
            $audioPath = $this->getAudioPath($audioData['file']);
        }
        
        if (!$audioPath || !File::exists($audioPath)) {
            return $videoPath; // Return original if audio failed
        }
        
        $cmd = "ffmpeg -i \"{$videoPath}\" -i \"{$audioPath}\" -c:v copy -c:a aac -shortest \"{$outputPath}\" -y";
        exec($cmd, $output, $returnCode);
        
        if ($returnCode === 0 && File::exists($outputPath)) {
            File::delete($videoPath);
            return $outputPath;
        }
        
        return $videoPath;
    }
    
    /**
     * Add subtitle to preview using new Vietnamese subtitle service
     */
    private function addSubtitleToPreview($videoPath, $subtitleData, $tempDir)
    {
        $outputPath = $tempDir . '/preview_with_subtitle.mp4';
        $text = $subtitleData['text'] ?? '';
        $size = $subtitleData['size'] ?? 24;

        Log::info('PREVIEW SUBTITLE: Processing Vietnamese subtitle', [
            'subtitle_data' => $subtitleData,
            'text_length' => strlen($text),
            'size' => $size,
            'video_path' => $videoPath
        ]);

        if (empty($text)) {
            Log::info('PREVIEW SUBTITLE: Empty text, skipping subtitle');
            return $videoPath;
        }

        // Get video duration for proper timing
        $videoDuration = $this->getVideoDuration($videoPath);

        // Use new Vietnamese subtitle service
        $result = $this->videoSubtitleService->createVideoWithVietnameseSubtitle(
            $videoPath,
            $text,
            $videoDuration,
            [
                'output_path' => $outputPath,
                'font_size' => $size,
                'font_name' => 'Arial Unicode MS',
                'font_color' => 'white',
                'outline_color' => 'black',
                'outline_width' => 2,
                'position' => 'bottom',
                'margin' => 100,
                'hard_subtitle' => true
            ]
        );

        Log::info('PREVIEW SUBTITLE: Vietnamese subtitle result', [
            'success' => $result['success'],
            'output_path' => $result['output_path'] ?? null,
            'error' => $result['error'] ?? null
        ]);

        if ($result['success'] && File::exists($result['output_path'])) {
            // Delete original video
            if (File::exists($videoPath)) {
                File::delete($videoPath);
            }
            return $result['output_path'];
        }

        // Fallback to original video if subtitle failed
        Log::warning('PREVIEW SUBTITLE: Failed to add Vietnamese subtitle, using original video');
        return $videoPath;
    }

    /**
     * Get video duration using ffprobe
     */
    private function getVideoDuration($videoPath)
    {
        $cmd = "ffprobe -v quiet -show_entries format=duration -of csv=p=0 \"{$videoPath}\"";
        $duration = exec($cmd);
        $duration = floatval($duration);

        // Default to 10 seconds if unable to get duration
        return $duration > 0 ? $duration : 10;
    }
    
    /**
     * Get full path for image
     */
    private function getImagePath($imagePath)
    {
        if (str_starts_with($imagePath, '/') || preg_match('/^[A-Z]:/i', $imagePath)) {
            return $imagePath; // Already absolute
        }
        
        // Try different possible locations
        $possiblePaths = [
            storage_path('app/' . $imagePath),
            storage_path('app/temp/images/' . basename($imagePath)),
            public_path($imagePath),
            base_path($imagePath)
        ];
        
        foreach ($possiblePaths as $path) {
            if (File::exists($path)) {
                return $path;
            }
        }
        
        throw new \Exception("Image not found: {$imagePath}");
    }
    
    /**
     * Get full path for audio
     */
    private function getAudioPath($audioPath)
    {
        if (str_starts_with($audioPath, '/') || preg_match('/^[A-Z]:/i', $audioPath)) {
            return $audioPath; // Already absolute
        }
        
        // Try different possible locations
        $possiblePaths = [
            storage_path('app/' . $audioPath),
            storage_path('app/temp/audio/' . basename($audioPath)),
            public_path($audioPath),
            base_path($audioPath)
        ];
        
        foreach ($possiblePaths as $path) {
            if (File::exists($path)) {
                return $path;
            }
        }
        
        throw new \Exception("Audio not found: {$audioPath}");
    }
    
    /**
     * Generate TTS audio for preview
     */
    private function generateTTSAudio($text, $tempDir)
    {
        // Simplified TTS for preview - just create silent audio
        $audioPath = $tempDir . '/tts_audio.mp3';
        $duration = min(strlen($text) * 0.1, 30); // Estimate duration, max 30s
        
        $cmd = "ffmpeg -f lavfi -i anullsrc=channel_layout=stereo:sample_rate=44100 -t {$duration} \"{$audioPath}\" -y";
        exec($cmd, $output, $returnCode);
        
        if ($returnCode === 0 && File::exists($audioPath)) {
            return $audioPath;
        }
        
        return null;
    }
    
    /**
     * Delete preview file
     */
    public function deletePreview(Request $request)
    {
        $previewId = $request->input('preview_id');
        $previewPath = public_path('previews/' . $previewId . '.mp4');

        if (File::exists($previewPath)) {
            File::delete($previewPath);
        }

        // Also cleanup uploaded temp files
        $tempUploadDir = storage_path('app/temp/preview_uploads');
        if (File::isDirectory($tempUploadDir)) {
            $files = File::files($tempUploadDir);
            $now = time();

            // Delete files older than 1 hour
            foreach ($files as $file) {
                if ($now - File::lastModified($file) > 3600) {
                    File::delete($file);
                }
            }
        }

        return response()->json(['success' => true]);
    }
}
