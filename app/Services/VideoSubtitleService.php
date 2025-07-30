<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class VideoSubtitleService
{
    private $subtitleService;
    
    public function __construct(SubtitleService $subtitleService)
    {
        $this->subtitleService = $subtitleService;
    }
    
    /**
     * Tạo video với subtitle tiếng Việt
     */
    public function createVideoWithVietnameseSubtitle($videoPath, $subtitleText, $audioDuration = null, $options = [])
    {
        try {
            $outputPath = $options['output_path'] ?? $this->generateOutputPath($videoPath);

            Log::info('VIDEO SUBTITLE: Creating video with Vietnamese subtitle', [
                'video_path' => $videoPath,
                'subtitle_length' => strlen($subtitleText),
                'audio_duration' => $audioDuration,
                'output_path' => $outputPath
            ]);

            // 🔥🔥🔥 FORCE VIETNAMESE ENCODING: Tạo file SRT với UTF-8 encoding
            $srtResult = $this->subtitleService->createSrtFile($subtitleText, $audioDuration);

            if (!$srtResult['success']) {
                throw new \Exception('Failed to create SRT file: ' . $srtResult['error']);
            }
            
            $srtPath = $srtResult['srt_path'];
            
            // Bước 2: Gắn subtitle vào video với FFmpeg
            $result = $this->embedSubtitleToVideo($videoPath, $srtPath, $outputPath, $options);

            // Nếu SRT method thất bại, thử ASS method
            if (!$result['success'] && isset($srtResult['ass_path'])) {
                Log::warning('VIDEO SUBTITLE: SRT method failed, trying ASS method', [
                    'srt_error' => $result['ffmpeg_output'],
                    'return_code' => $result['return_code']
                ]);

                $result = $this->embedAssSubtitle($videoPath, $srtResult['ass_path'], $outputPath, $options);
            }

            // Nếu ASS cũng thất bại, thử drawtext method
            if (!$result['success']) {
                Log::warning('VIDEO SUBTITLE: ASS method also failed, trying drawtext method');
                $result = $this->embedSubtitleWithDrawtext($videoPath, $srtResult['segments'], $outputPath, $options);
            }

            // Cleanup SRT file
            if (File::exists($srtPath)) {
                File::delete($srtPath);
            }

            return $result;
            
        } catch (\Exception $e) {
            Log::error('VIDEO SUBTITLE: Failed to create video with subtitle', [
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
     * Gắn subtitle vào video bằng FFmpeg
     */
    private function embedSubtitleToVideo($videoPath, $srtPath, $outputPath, $options = [])
    {
        // Cấu hình mặc định với font fallback cho Windows
        $defaultFont = $this->getVietnameseFontName();
        $config = array_merge([
            'font_name' => $defaultFont, // Font hỗ trợ tiếng Việt
            'font_size' => 24,
            'font_color' => 'white',
            'outline_color' => 'black',
            'outline_width' => 2,
            'position' => 'bottom', // top, center, bottom
            'margin' => 50,
            'hard_subtitle' => true, // Gắn cứng
            'encoding' => 'UTF-8'
        ], $options);
        
        Log::info('VIDEO SUBTITLE: Embedding subtitle with FFmpeg', [
            'video_path' => $videoPath,
            'srt_path' => $srtPath,
            'config' => $config
        ]);
        
        if ($config['hard_subtitle']) {
            return $this->embedHardSubtitle($videoPath, $srtPath, $outputPath, $config);
        } else {
            return $this->embedSoftSubtitle($videoPath, $srtPath, $outputPath, $config);
        }
    }
    
    /**
     * Gắn cứng subtitle (hard subtitle) - FIXED VERSION
     */
    private function embedHardSubtitle($videoPath, $srtPath, $outputPath, $config)
    {
        // Escape paths cho Windows
        $videoPathEscaped = $this->escapePath($videoPath);
        $outputPathEscaped = $this->escapePath($outputPath);

        // SRT path cần escape đặc biệt cho subtitles filter - FIXED
        // Không dùng quotes, chỉ escape colon và backslash
        $srtPathForFilter = str_replace('\\', '/', $srtPath);
        $srtPathForFilter = str_replace(':', '\\:', $srtPathForFilter);

        // Thử approach đơn giản hơn - không dùng force_style
        $subtitleFilter = "subtitles='{$srtPathForFilter}':charenc=UTF-8";

        // Command FFmpeg
        $cmd = "ffmpeg -i {$videoPathEscaped} -vf \"{$subtitleFilter}\" -c:a copy {$outputPathEscaped} -y";

        Log::info('VIDEO SUBTITLE: FFmpeg hard subtitle command (SIMPLE)', [
            'command' => $cmd,
            'subtitle_filter' => $subtitleFilter,
            'srt_path_original' => $srtPath,
            'srt_path_for_filter' => $srtPathForFilter
        ]);

        exec($cmd, $output, $returnCode);

        // Remove quotes from output path for file existence check
        $actualOutputPath = trim($outputPath, '"');

        $result = [
            'success' => $returnCode === 0 && File::exists($actualOutputPath),
            'output_path' => $actualOutputPath,
            'return_code' => $returnCode,
            'ffmpeg_output' => implode("\n", $output),
            'subtitle_type' => 'hard_simple'
        ];

        Log::info('VIDEO SUBTITLE: Hard subtitle result (SIMPLE)', $result);

        // Nếu simple approach fail, thử với absolute path
        if (!$result['success']) {
            Log::warning('VIDEO SUBTITLE: Simple approach failed, trying absolute path approach');
            return $this->embedHardSubtitleAbsolutePath($videoPath, $srtPath, $outputPath, $config);
        }

        return $result;
    }

    /**
     * Fallback method với absolute path
     */
    private function embedHardSubtitleAbsolutePath($videoPath, $srtPath, $outputPath, $config)
    {
        $videoPathEscaped = $this->escapePath($videoPath);
        $outputPathEscaped = $this->escapePath($outputPath);

        // Sử dụng absolute path trực tiếp
        $absoluteSrtPath = realpath($srtPath);
        if (!$absoluteSrtPath) {
            $absoluteSrtPath = $srtPath;
        }

        // Convert to forward slashes cho Windows
        $srtPathForFilter = str_replace('\\', '/', $absoluteSrtPath);

        // Simple subtitles filter
        $subtitleFilter = "subtitles='{$srtPathForFilter}'";

        $cmd = "ffmpeg -i {$videoPathEscaped} -vf \"{$subtitleFilter}\" -c:a copy {$outputPathEscaped} -y";

        Log::info('VIDEO SUBTITLE: FFmpeg absolute path command', [
            'command' => $cmd,
            'subtitle_filter' => $subtitleFilter,
            'absolute_srt_path' => $absoluteSrtPath,
            'srt_path_for_filter' => $srtPathForFilter
        ]);

        exec($cmd, $output, $returnCode);

        $actualOutputPath = trim($outputPath, '"');

        $result = [
            'success' => $returnCode === 0 && File::exists($actualOutputPath),
            'output_path' => $actualOutputPath,
            'return_code' => $returnCode,
            'ffmpeg_output' => implode("\n", $output),
            'subtitle_type' => 'hard_absolute'
        ];

        Log::info('VIDEO SUBTITLE: Absolute path result', $result);

        return $result;
    }
    
    /**
     * Gắn mềm subtitle (soft subtitle)
     */
    private function embedSoftSubtitle($videoPath, $srtPath, $outputPath, $config)
    {
        $videoPath = $this->escapePath($videoPath);
        $srtPath = $this->escapePath($srtPath);
        $outputPath = $this->escapePath($outputPath);
        
        // Command FFmpeg cho soft subtitle
        $cmd = "ffmpeg -i {$videoPath} -i {$srtPath} -c copy -c:s srt -metadata:s:s:0 language=vie {$outputPath} -y";
        
        Log::info('VIDEO SUBTITLE: FFmpeg soft subtitle command', [
            'command' => $cmd
        ]);
        
        exec($cmd, $output, $returnCode);
        
        $result = [
            'success' => $returnCode === 0 && File::exists($outputPath),
            'output_path' => $outputPath,
            'return_code' => $returnCode,
            'ffmpeg_output' => implode("\n", $output),
            'subtitle_type' => 'soft'
        ];
        
        Log::info('VIDEO SUBTITLE: Soft subtitle result', $result);
        
        return $result;
    }

    /**
     * Embed ASS subtitle (Advanced SubStation Alpha)
     */
    private function embedAssSubtitle($videoPath, $assPath, $outputPath, $config)
    {
        $videoPathEscaped = $this->escapePath($videoPath);
        $outputPathEscaped = $this->escapePath($outputPath);

        // ASS path cho filter
        $assPathForFilter = str_replace('\\', '/', $assPath);

        // ASS filter - đơn giản hơn SRT
        $subtitleFilter = "ass='{$assPathForFilter}'";

        $cmd = "ffmpeg -i {$videoPathEscaped} -vf \"{$subtitleFilter}\" -c:a copy {$outputPathEscaped} -y";

        Log::info('VIDEO SUBTITLE: FFmpeg ASS command', [
            'command' => $cmd,
            'subtitle_filter' => $subtitleFilter,
            'ass_path' => $assPath,
            'ass_path_for_filter' => $assPathForFilter
        ]);

        exec($cmd, $output, $returnCode);

        $actualOutputPath = trim($outputPath, '"');

        $result = [
            'success' => $returnCode === 0 && File::exists($actualOutputPath),
            'output_path' => $actualOutputPath,
            'return_code' => $returnCode,
            'ffmpeg_output' => implode("\n", $output),
            'subtitle_type' => 'ass'
        ];

        Log::info('VIDEO SUBTITLE: ASS subtitle result', $result);

        return $result;
    }

    /**
     * Embed subtitle using textfile approach (preserves Vietnamese)
     */
    private function embedSubtitleWithDrawtext($videoPath, $segments, $outputPath, $config)
    {
        // Thử textfile approach trước để giữ nguyên tiếng Việt
        $textfileResult = $this->embedSubtitleWithTextfile($videoPath, $segments, $outputPath, $config);

        if ($textfileResult['success']) {
            return $textfileResult;
        }

        // Fallback to ASCII drawtext
        Log::warning('VIDEO SUBTITLE: Textfile approach failed, falling back to ASCII drawtext');
        return $this->embedSubtitleWithDrawtextASCII($videoPath, $segments, $outputPath, $config);
    }

    /**
     * Embed subtitle using textfile (preserves Vietnamese characters)
     */
    private function embedSubtitleWithTextfile($videoPath, $segments, $outputPath, $config)
    {
        $videoPathEscaped = $this->escapePath($videoPath);
        $outputPathEscaped = $this->escapePath($outputPath);

        // Tạo text files cho từng segment
        $tempDir = dirname($outputPath) . '/textfiles';
        if (!File::isDirectory($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        $filters = [];
        foreach ($segments as $index => $segment) {
            $text = $segment['text'];
            $start = $segment['start'];
            $end = $segment['end'];

            // Tạo text file với UTF-8 encoding
            $textFilePath = $tempDir . "/segment_{$index}.txt";
            File::put($textFilePath, $text);

            // Normalize path cho FFmpeg
            $textFileForFilter = str_replace('\\', '/', $textFilePath);

            $filter = "drawtext=textfile='{$textFileForFilter}':fontsize={$config['font_size']}:fontcolor={$config['font_color']}:x=(w-text_w)/2:y=h-text_h-{$config['margin']}:enable='between(t,{$start},{$end})'";
            $filters[] = $filter;
        }

        $combinedFilter = implode(',', $filters);
        $cmd = "ffmpeg -i {$videoPathEscaped} -vf \"{$combinedFilter}\" -c:a copy {$outputPathEscaped} -y";

        Log::info('VIDEO SUBTITLE: FFmpeg textfile command', [
            'command' => $cmd,
            'segments_count' => count($segments),
            'filter_length' => strlen($combinedFilter),
            'temp_dir' => $tempDir
        ]);

        exec($cmd, $output, $returnCode);

        // Cleanup text files
        if (File::isDirectory($tempDir)) {
            File::deleteDirectory($tempDir);
        }

        $actualOutputPath = trim($outputPath, '"');

        $result = [
            'success' => $returnCode === 0 && File::exists($actualOutputPath),
            'output_path' => $actualOutputPath,
            'return_code' => $returnCode,
            'ffmpeg_output' => implode("\n", $output),
            'subtitle_type' => 'textfile_vietnamese'
        ];

        Log::info('VIDEO SUBTITLE: Textfile result', $result);

        return $result;
    }

    /**
     * Embed subtitle using drawtext with ASCII conversion (final fallback)
     */
    private function embedSubtitleWithDrawtextASCII($videoPath, $segments, $outputPath, $config)
    {
        $videoPathEscaped = $this->escapePath($videoPath);
        $outputPathEscaped = $this->escapePath($outputPath);

        // Tạo drawtext filters cho từng segment với ASCII conversion
        $filters = [];
        foreach ($segments as $segment) {
            $text = $segment['text'];
            $start = $segment['start'];
            $end = $segment['end'];

            // Convert Vietnamese to safe characters for drawtext
            $safeText = $this->convertVietnameseToSafe($text);

            $filter = "drawtext=text='{$safeText}':fontsize={$config['font_size']}:fontcolor={$config['font_color']}:x=(w-text_w)/2:y=h-text_h-{$config['margin']}:enable='between(t,{$start},{$end})'";
            $filters[] = $filter;
        }

        $combinedFilter = implode(',', $filters);
        $cmd = "ffmpeg -i {$videoPathEscaped} -vf \"{$combinedFilter}\" -c:a copy {$outputPathEscaped} -y";

        Log::info('VIDEO SUBTITLE: FFmpeg drawtext ASCII fallback command', [
            'command' => $cmd,
            'segments_count' => count($segments),
            'filter_length' => strlen($combinedFilter)
        ]);

        exec($cmd, $output, $returnCode);

        $actualOutputPath = trim($outputPath, '"');

        $result = [
            'success' => $returnCode === 0 && File::exists($actualOutputPath),
            'output_path' => $actualOutputPath,
            'return_code' => $returnCode,
            'ffmpeg_output' => implode("\n", $output),
            'subtitle_type' => 'drawtext_ascii_fallback'
        ];

        Log::info('VIDEO SUBTITLE: Drawtext ASCII fallback result', $result);

        return $result;
    }

    /**
     * Convert Vietnamese text to safe characters for drawtext
     */
    private function convertVietnameseToSafe($text)
    {
        // Vietnamese to ASCII mapping
        $vietnameseMap = [
            'á' => 'a', 'à' => 'a', 'ả' => 'a', 'ã' => 'a', 'ạ' => 'a',
            'ă' => 'a', 'ắ' => 'a', 'ằ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a', 'ặ' => 'a',
            'â' => 'a', 'ấ' => 'a', 'ầ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a', 'ậ' => 'a',
            'é' => 'e', 'è' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ẹ' => 'e',
            'ê' => 'e', 'ế' => 'e', 'ề' => 'e', 'ể' => 'e', 'ễ' => 'e', 'ệ' => 'e',
            'í' => 'i', 'ì' => 'i', 'ỉ' => 'i', 'ĩ' => 'i', 'ị' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ỏ' => 'o', 'õ' => 'o', 'ọ' => 'o',
            'ô' => 'o', 'ố' => 'o', 'ồ' => 'o', 'ổ' => 'o', 'ỗ' => 'o', 'ộ' => 'o',
            'ơ' => 'o', 'ớ' => 'o', 'ờ' => 'o', 'ở' => 'o', 'ỡ' => 'o', 'ợ' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ủ' => 'u', 'ũ' => 'u', 'ụ' => 'u',
            'ư' => 'u', 'ứ' => 'u', 'ừ' => 'u', 'ử' => 'u', 'ữ' => 'u', 'ự' => 'u',
            'ý' => 'y', 'ỳ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y', 'ỵ' => 'y',
            'đ' => 'd', 'Đ' => 'D',
            // Uppercase
            'Á' => 'A', 'À' => 'A', 'Ả' => 'A', 'Ã' => 'A', 'Ạ' => 'A',
            'Ă' => 'A', 'Ắ' => 'A', 'Ằ' => 'A', 'Ẳ' => 'A', 'Ẵ' => 'A', 'Ặ' => 'A',
            'Â' => 'A', 'Ấ' => 'A', 'Ầ' => 'A', 'Ẩ' => 'A', 'Ẫ' => 'A', 'Ậ' => 'A',
            'É' => 'E', 'È' => 'E', 'Ẻ' => 'E', 'Ẽ' => 'E', 'Ẹ' => 'E',
            'Ê' => 'E', 'Ế' => 'E', 'Ề' => 'E', 'Ể' => 'E', 'Ễ' => 'E', 'Ệ' => 'E',
            'Í' => 'I', 'Ì' => 'I', 'Ỉ' => 'I', 'Ĩ' => 'I', 'Ị' => 'I',
            'Ó' => 'O', 'Ò' => 'O', 'Ỏ' => 'O', 'Õ' => 'O', 'Ọ' => 'O',
            'Ô' => 'O', 'Ố' => 'O', 'Ồ' => 'O', 'Ổ' => 'O', 'Ỗ' => 'O', 'Ộ' => 'O',
            'Ơ' => 'O', 'Ớ' => 'O', 'Ờ' => 'O', 'Ở' => 'O', 'Ỡ' => 'O', 'Ợ' => 'O',
            'Ú' => 'U', 'Ù' => 'U', 'Ủ' => 'U', 'Ũ' => 'U', 'Ụ' => 'U',
            'Ư' => 'U', 'Ứ' => 'U', 'Ừ' => 'U', 'Ử' => 'U', 'Ữ' => 'U', 'Ự' => 'U',
            'Ý' => 'Y', 'Ỳ' => 'Y', 'Ỷ' => 'Y', 'Ỹ' => 'Y', 'Ỵ' => 'Y'
        ];

        // Convert Vietnamese to ASCII
        $asciiText = str_replace(array_keys($vietnameseMap), array_values($vietnameseMap), $text);

        // Remove special characters that might break drawtext
        $safeText = preg_replace('/[^a-zA-Z0-9\s]/', '', $asciiText);
        $safeText = preg_replace('/\s+/', ' ', trim($safeText));

        return $safeText;
    }

    /**
     * Tạo video hoàn chỉnh với tất cả thành phần
     */
    public function createCompleteVideo($components, $options = [])
    {
        try {
            Log::info('VIDEO SUBTITLE: Creating complete video', [
                'components' => array_keys($components),
                'options' => $options
            ]);
            
            $tempDir = storage_path('app/temp/complete_video/' . uniqid());
            File::makeDirectory($tempDir, 0755, true);
            
            // Bước 1: Tạo video nền từ images
            $baseVideoPath = null;
            if (isset($components['images']) && !empty($components['images'])) {
                $baseVideoPath = $this->createImageSlideshow($components['images'], $tempDir, $options);
            }
            
            // Bước 2: Thêm audio (TTS hoặc upload)
            if (isset($components['audio'])) {
                $baseVideoPath = $this->addAudioToVideo($baseVideoPath, $components['audio'], $tempDir, $options);
            }
            
            // Bước 3: Thêm nhạc nền
            if (isset($components['background_music'])) {
                $baseVideoPath = $this->addBackgroundMusic($baseVideoPath, $components['background_music'], $tempDir, $options);
            }
            
            // Bước 4: Thêm subtitle tiếng Việt
            $finalVideoPath = $baseVideoPath;
            if (isset($components['subtitle']) && !empty($components['subtitle'])) {
                $audioDuration = $this->getVideoDuration($baseVideoPath);
                $subtitleResult = $this->createVideoWithVietnameseSubtitle(
                    $baseVideoPath, 
                    $components['subtitle'], 
                    $audioDuration, 
                    $options
                );
                
                if ($subtitleResult['success']) {
                    $finalVideoPath = $subtitleResult['output_path'];
                }
            }
            
            // Move final video to desired location
            $outputPath = $options['final_output_path'] ?? storage_path('app/videos/' . uniqid('complete_') . '.mp4');
            File::move($finalVideoPath, $outputPath);
            
            // Cleanup temp directory
            File::deleteDirectory($tempDir);
            
            return [
                'success' => true,
                'output_path' => $outputPath,
                'components_processed' => array_keys($components)
            ];
            
        } catch (\Exception $e) {
            Log::error('VIDEO SUBTITLE: Failed to create complete video', [
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
     * Tính vị trí subtitle
     */
    private function calculateSubtitlePosition($position, $margin)
    {
        switch ($position) {
            case 'top':
                return ['alignment' => 2, 'y' => $margin];
            case 'center':
                return ['alignment' => 2, 'y' => '(h-text_h)/2'];
            case 'bottom':
            default:
                return ['alignment' => 2, 'y' => "h-text_h-{$margin}"];
        }
    }
    
    /**
     * Convert color name to hex
     */
    private function colorToHex($color)
    {
        $colors = [
            'white' => 'FFFFFF',
            'black' => '000000',
            'red' => 'FF0000',
            'green' => '00FF00',
            'blue' => '0000FF',
            'yellow' => 'FFFF00'
        ];
        
        return $colors[strtolower($color)] ?? 'FFFFFF';
    }
    
    /**
     * Escape path cho Windows
     */
    private function escapePath($path)
    {
        // Normalize path separators
        $path = str_replace('\\', '/', $path);
        return '"' . str_replace('"', '""', $path) . '"';
    }
    
    /**
     * Generate output path
     */
    private function generateOutputPath($inputPath)
    {
        $pathInfo = pathinfo($inputPath);
        $outputDir = storage_path('app/videos');
        
        if (!File::isDirectory($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }
        
        return $outputDir . '/' . uniqid('subtitle_') . '.mp4';
    }
    
    /**
     * Get video duration
     */
    private function getVideoDuration($videoPath)
    {
        $cmd = "ffprobe -v quiet -show_entries format=duration -of csv=p=0 \"{$videoPath}\"";
        $duration = exec($cmd);
        return floatval($duration);
    }

    /**
     * Create image slideshow for complete video
     */
    private function createImageSlideshow($images, $tempDir, $options = [])
    {
        $outputPath = $tempDir . '/slideshow.mp4';
        $imageDuration = $options['image_duration'] ?? 3;
        $resolution = $options['resolution'] ?? '1080:1920';

        if (count($images) === 1) {
            $imagePath = $images[0];
            $totalDuration = $imageDuration * 3; // Minimum 3 cycles for single image
            $cmd = "ffmpeg -loop 1 -i \"{$imagePath}\" -vf \"scale={$resolution}:force_original_aspect_ratio=increase,crop={$resolution}\" -t {$totalDuration} -c:v libx264 -preset fast -crf 23 -pix_fmt yuv420p -r 25 \"{$outputPath}\" -y";
        } else {
            // Multiple images slideshow
            $inputListPath = $tempDir . '/images.txt';
            $inputList = '';

            foreach ($images as $image) {
                $inputList .= "file '" . str_replace('\\', '/', $image) . "'\n";
                $inputList .= "duration {$imageDuration}\n";
            }

            // Add last image again for proper duration
            if (!empty($images)) {
                $lastImage = end($images);
                $inputList .= "file '" . str_replace('\\', '/', $lastImage) . "'\n";
            }

            File::put($inputListPath, $inputList);

            $totalDuration = count($images) * $imageDuration;
            $cmd = "ffmpeg -f concat -safe 0 -i \"{$inputListPath}\" -vf \"scale={$resolution}:force_original_aspect_ratio=increase,crop={$resolution}\" -t {$totalDuration} -c:v libx264 -preset fast -crf 23 -pix_fmt yuv420p \"{$outputPath}\" -y";
        }

        exec($cmd, $output, $returnCode);

        if ($returnCode === 0 && File::exists($outputPath)) {
            return $outputPath;
        }

        throw new \Exception('Failed to create image slideshow');
    }

    /**
     * Add audio to video
     */
    private function addAudioToVideo($videoPath, $audioData, $tempDir, $options = [])
    {
        $outputPath = $tempDir . '/video_with_audio.mp4';

        if (isset($audioData['type']) && $audioData['type'] === 'tts') {
            // Generate TTS audio (simplified for now)
            $audioPath = $this->generateTTSAudio($audioData['text'], $tempDir);
        } else {
            $audioPath = $audioData['file'];
        }

        if (!$audioPath || !File::exists($audioPath)) {
            return $videoPath;
        }

        $cmd = "ffmpeg -i \"{$videoPath}\" -i \"{$audioPath}\" -c:v copy -c:a aac -shortest \"{$outputPath}\" -y";
        exec($cmd, $output, $returnCode);

        if ($returnCode === 0 && File::exists($outputPath)) {
            return $outputPath;
        }

        return $videoPath;
    }

    /**
     * Add background music
     */
    private function addBackgroundMusic($videoPath, $musicData, $tempDir, $options = [])
    {
        $outputPath = $tempDir . '/video_with_music.mp4';
        $musicPath = $musicData['file'];
        $volume = $musicData['volume'] ?? 0.3;

        if (!File::exists($musicPath)) {
            return $videoPath;
        }

        $cmd = "ffmpeg -i \"{$videoPath}\" -i \"{$musicPath}\" -filter_complex \"[1:a]volume={$volume}[bg];[0:a][bg]amix=inputs=2:duration=first\" -c:v copy \"{$outputPath}\" -y";
        exec($cmd, $output, $returnCode);

        if ($returnCode === 0 && File::exists($outputPath)) {
            return $outputPath;
        }

        return $videoPath;
    }

    /**
     * Generate TTS audio (simplified)
     */
    private function generateTTSAudio($text, $tempDir)
    {
        $audioPath = $tempDir . '/tts_audio.mp3';
        $duration = min(strlen($text) * 0.1, 30); // Estimate duration

        // Create silent audio for now (can be replaced with actual TTS)
        $cmd = "ffmpeg -f lavfi -i anullsrc=channel_layout=stereo:sample_rate=44100 -t {$duration} \"{$audioPath}\" -y";
        exec($cmd, $output, $returnCode);

        if ($returnCode === 0 && File::exists($audioPath)) {
            return $audioPath;
        }

        return null;
    }

    /**
     * Get Vietnamese-compatible font name
     */
    private function getVietnameseFontName()
    {
        // List of fonts that support Vietnamese characters, in order of preference
        $vietnameseFonts = [
            'Arial Unicode MS',
            'Times New Roman',
            'Arial',
            'Segoe UI',
            'Tahoma',
            'Verdana',
            'DejaVu Sans'
        ];

        // For Windows, try to detect available fonts
        if (PHP_OS_FAMILY === 'Windows') {
            // Check common Windows fonts that support Vietnamese
            $windowsFonts = [
                'Times New Roman', // Usually available and supports Vietnamese
                'Arial',
                'Segoe UI',
                'Tahoma'
            ];

            foreach ($windowsFonts as $font) {
                // Return first available font (simplified check)
                return $font;
            }
        }

        // Default fallback
        return 'Arial';
    }
}
