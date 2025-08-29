<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class VideoPreviewService
{
    /**
     * Generate preview video from components
     */
    public function generatePreview($components, $previewId)
    {
        try {
            Log::info('PREVIEW SUBTITLE: Processing Vietnamese subtitle', [
                'subtitle_data' => $components['subtitle'],
                'text_length' => strlen($components['subtitle']['text']),
                'size' => $components['subtitle']['size'],
                'video_path' => $this->getTempVideoPath($previewId)
            ]);

            // Create temp directory
            $tempDir = storage_path('app/temp/preview/' . $previewId);
            if (!File::isDirectory($tempDir)) {
                File::makeDirectory($tempDir, 0755, true);
            }

            // Step 1: Create slideshow from images
            $slideshowPath = $this->createSlideshow($components['images'], $tempDir);
            
            if (!$slideshowPath) {
                return [
                    'success' => false,
                    'error' => 'Failed to create slideshow'
                ];
            }

            // Step 2: Add Vietnamese subtitle if provided
            $finalVideoPath = $slideshowPath;
            if (!empty($components['subtitle']['text'])) {
                $subtitleResult = $this->addVietnameseSubtitle(
                    $slideshowPath,
                    $components['subtitle'],
                    $tempDir
                );
                
                if ($subtitleResult['success']) {
                    $finalVideoPath = $subtitleResult['output_path'];
                } else {
                    Log::warning('Failed to add subtitle, using video without subtitle');
                }
            }

            return [
                'success' => true,
                'video_path' => $finalVideoPath,
                'preview_id' => $previewId
            ];

        } catch (\Exception $e) {
            Log::error('Preview generation failed', [
                'error' => $e->getMessage(),
                'preview_id' => $previewId
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create slideshow from images
     */
    private function createSlideshow($images, $tempDir)
    {
        $outputPath = $tempDir . '/slideshow.mp4';
        
        if (empty($images)) {
            // Create default demo video
            return $this->createDefaultDemoVideo($outputPath);
        }

        try {
            $firstImage = $images[0];
            
            if (!File::exists($firstImage)) {
                Log::warning('Image not found, creating default demo video', ['image' => $firstImage]);
                return $this->createDefaultDemoVideo($outputPath);
            }

            // Create 3-second slideshow from single image
            $duration = 3.0; // Short duration for preview
            
            $cmd = "ffmpeg -loop 1 -i \"{$firstImage}\" " .
                   "-vf \"scale=1920:1080:force_original_aspect_ratio=increase,crop=1920:1080\" " .
                   "-t {$duration} -c:v libx264 -preset fast -crf 23 -pix_fmt yuv420p -r 25 " .
                   "\"{$outputPath}\" -y";

            Log::info('Creating slideshow with FFmpeg', [
                'command' => $cmd,
                'input_image' => $firstImage,
                'duration' => $duration
            ]);

            exec($cmd, $output, $returnCode);

            if ($returnCode === 0 && File::exists($outputPath)) {
                Log::info('Slideshow created successfully', ['output' => $outputPath]);
                return $outputPath;
            } else {
                Log::error('FFmpeg slideshow creation failed', [
                    'return_code' => $returnCode,
                    'output' => implode("\n", $output)
                ]);
                return $this->createDefaultDemoVideo($outputPath);
            }

        } catch (\Exception $e) {
            Log::error('Slideshow creation error', ['error' => $e->getMessage()]);
            return $this->createDefaultDemoVideo($outputPath);
        }
    }

    /**
     * Create default demo video
     */
    private function createDefaultDemoVideo($outputPath)
    {
        try {
            // Create simple blue demo video
            $cmd = "ffmpeg -f lavfi -i color=blue:size=1920x1080:duration=3 " .
                   "-c:v libx264 -pix_fmt yuv420p \"{$outputPath}\" -y";

            exec($cmd, $output, $returnCode);

            if ($returnCode === 0 && File::exists($outputPath)) {
                Log::info('Default demo video created', ['output' => $outputPath]);
                return $outputPath;
            } else {
                Log::error('Failed to create default demo video');
                return null;
            }

        } catch (\Exception $e) {
            Log::error('Default demo video creation error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Add Vietnamese subtitle to video with template settings
     */
    private function addVietnameseSubtitle($videoPath, $subtitleData, $tempDir)
    {
        try {
            $outputPath = $tempDir . '/preview_with_subtitle.mp4';

            // Use VideoSubtitleService for Vietnamese subtitle
            $subtitleService = app(VideoSubtitleService::class);

            Log::info('PREVIEW SUBTITLE: Creating video with Vietnamese subtitle and template settings', [
                'video_path' => $videoPath,
                'subtitle_length' => strlen($subtitleData['text']),
                'subtitle_settings' => $subtitleData,
                'audio_duration' => 3.0, // Fixed duration for preview
                'output_path' => $outputPath
            ]);

            // Apply template settings to subtitle
            $subtitleOptions = [
                'font_size' => $subtitleData['size'] ?? 24,
                'font_color' => $subtitleData['color'] ?? '#FFFFFF',
                'position' => $subtitleData['position'] ?? 'bottom',
                'font_family' => $subtitleData['font'] ?? 'Arial'
            ];

            // Merge subtitle options with output path
            $options = array_merge($subtitleOptions, [
                'output_path' => $outputPath
            ]);

            $result = $subtitleService->createVideoWithVietnameseSubtitle(
                $videoPath,
                $subtitleData['text'],
                3.0, // Fixed duration for preview
                $options
            );

            Log::info('PREVIEW SUBTITLE: Vietnamese subtitle with template settings result', [
                'success' => $result['success'],
                'output_path' => $result['output_path'] ?? null,
                'subtitle_options' => $subtitleOptions,
                'error' => $result['error'] ?? null
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Vietnamese subtitle addition failed', [
                'error' => $e->getMessage(),
                'video_path' => $videoPath,
                'subtitle_data' => $subtitleData
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get temp video path for preview
     */
    private function getTempVideoPath($previewId)
    {
        return storage_path('app/temp/preview/' . $previewId . '/slideshow.mp4');
    }
}
