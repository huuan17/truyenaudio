<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class SubtitleService
{
    /**
     * Tạo file SRT từ văn bản tiếng Việt
     */
    public function createSrtFile($text, $audioDuration = null, $outputPath = null)
    {
        try {
            // Tạo output path nếu không có
            if (!$outputPath) {
                $outputPath = storage_path('app/temp/subtitles/' . uniqid('subtitle_') . '.srt');
            }
            
            // Tạo thư mục nếu chưa có
            $dir = dirname($outputPath);
            if (!File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
            
            Log::info('SUBTITLE SERVICE: Creating SRT file', [
                'text_length' => strlen($text),
                'audio_duration' => $audioDuration,
                'output_path' => $outputPath
            ]);
            
            // Chia text thành các câu
            $segments = $this->splitTextIntoSegments($text);
            
            // Tính timing cho từng segment
            $timedSegments = $this->calculateTiming($segments, $audioDuration);
            
            // Tạo nội dung SRT
            $srtContent = $this->generateSrtContent($timedSegments);
            
            // Ghi file với UTF-8 không BOM
            $this->writeSrtFile($outputPath, $srtContent);

            Log::info('SUBTITLE SERVICE: SRT file created successfully', [
                'segments_count' => count($timedSegments),
                'file_size' => File::size($outputPath),
                'output_path' => $outputPath,
                'srt_content_preview' => substr($srtContent, 0, 200),
                'encoding_check' => mb_detect_encoding($srtContent)
            ]);
            
            // Tạo thêm ASS file để backup
            $assPath = str_replace('.srt', '.ass', $outputPath);
            $this->createAssFile($timedSegments, $assPath);

            return [
                'success' => true,
                'srt_path' => $outputPath,
                'ass_path' => $assPath,
                'segments' => $timedSegments,
                'segments_count' => count($timedSegments)
            ];
            
        } catch (\Exception $e) {
            Log::error('SUBTITLE SERVICE: Failed to create SRT file', [
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
     * Chia text thành các segment nhỏ
     */
    private function splitTextIntoSegments($text)
    {
        // Làm sạch text
        $text = trim($text);
        
        // Chia theo dấu xuống dòng trước
        $lines = explode("\n", $text);
        $segments = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Chia câu dài thành các phần nhỏ hơn
            $lineSegments = $this->splitLongSentence($line);
            $segments = array_merge($segments, $lineSegments);
        }
        
        // Nếu không có xuống dòng, chia theo dấu câu
        if (count($segments) <= 1) {
            $segments = $this->splitBySentences($text);
        }
        
        // Đảm bảo mỗi segment không quá dài
        $finalSegments = [];
        foreach ($segments as $segment) {
            if (mb_strlen($segment) > 80) {
                $subSegments = $this->splitByLength($segment, 80);
                $finalSegments = array_merge($finalSegments, $subSegments);
            } else {
                $finalSegments[] = $segment;
            }
        }
        
        return array_filter($finalSegments, function($segment) {
            return !empty(trim($segment));
        });
    }
    
    /**
     * Chia câu dài thành các phần nhỏ
     */
    private function splitLongSentence($sentence, $maxLength = 80)
    {
        if (mb_strlen($sentence) <= $maxLength) {
            return [$sentence];
        }
        
        // Chia theo dấu phẩy, chấm phẩy
        $parts = preg_split('/[,;]/', $sentence);
        $segments = [];
        $currentSegment = '';
        
        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;
            
            if (mb_strlen($currentSegment . ' ' . $part) <= $maxLength) {
                $currentSegment = $currentSegment ? $currentSegment . ', ' . $part : $part;
            } else {
                if ($currentSegment) {
                    $segments[] = $currentSegment;
                }
                $currentSegment = $part;
            }
        }
        
        if ($currentSegment) {
            $segments[] = $currentSegment;
        }
        
        return $segments;
    }
    
    /**
     * Chia text theo dấu câu
     */
    private function splitBySentences($text)
    {
        // Chia theo dấu chấm, chấm hỏi, chấm than
        $sentences = preg_split('/[.!?]+/', $text);
        
        return array_filter(array_map('trim', $sentences), function($sentence) {
            return !empty($sentence);
        });
    }
    
    /**
     * Chia text theo độ dài
     */
    private function splitByLength($text, $maxLength)
    {
        $words = explode(' ', $text);
        $segments = [];
        $currentSegment = '';
        
        foreach ($words as $word) {
            if (mb_strlen($currentSegment . ' ' . $word) <= $maxLength) {
                $currentSegment = $currentSegment ? $currentSegment . ' ' . $word : $word;
            } else {
                if ($currentSegment) {
                    $segments[] = $currentSegment;
                }
                $currentSegment = $word;
            }
        }
        
        if ($currentSegment) {
            $segments[] = $currentSegment;
        }
        
        return $segments;
    }
    
    /**
     * Tính timing cho từng segment
     */
    private function calculateTiming($segments, $audioDuration = null)
    {
        $segmentCount = count($segments);
        if ($segmentCount === 0) {
            return [];
        }
        
        // Nếu không có audio duration, ước tính dựa trên text
        if (!$audioDuration) {
            $totalChars = array_sum(array_map('mb_strlen', $segments));
            $audioDuration = max($totalChars * 0.1, $segmentCount * 2); // Tối thiểu 2s/segment
        }
        
        $timedSegments = [];
        
        // Tính thời gian cho từng segment dựa trên độ dài text
        $totalWeight = 0;
        $weights = [];
        
        foreach ($segments as $segment) {
            $weight = max(mb_strlen($segment), 10); // Tối thiểu weight = 10
            $weights[] = $weight;
            $totalWeight += $weight;
        }
        
        $currentTime = 0;
        
        for ($i = 0; $i < $segmentCount; $i++) {
            $segmentDuration = ($weights[$i] / $totalWeight) * $audioDuration;
            $segmentDuration = max($segmentDuration, 1.5); // Tối thiểu 1.5s
            
            $startTime = $currentTime;
            $endTime = $currentTime + $segmentDuration;
            
            $timedSegments[] = [
                'text' => trim($segments[$i]),
                'start' => $startTime,
                'end' => $endTime,
                'duration' => $segmentDuration
            ];
            
            $currentTime = $endTime;
        }
        
        return $timedSegments;
    }
    
    /**
     * Tạo nội dung SRT
     */
    private function generateSrtContent($timedSegments)
    {
        $srtContent = '';
        
        foreach ($timedSegments as $index => $segment) {
            $sequenceNumber = $index + 1;
            $startTime = $this->formatSrtTime($segment['start']);
            $endTime = $this->formatSrtTime($segment['end']);
            $text = $segment['text'];
            
            $srtContent .= "{$sequenceNumber}\n";
            $srtContent .= "{$startTime} --> {$endTime}\n";
            $srtContent .= "{$text}\n\n";
        }
        
        return $srtContent;
    }
    
    /**
     * Format thời gian cho SRT (HH:MM:SS,mmm)
     */
    private function formatSrtTime($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = floor($seconds % 60);
        $milliseconds = floor(($seconds - floor($seconds)) * 1000);
        
        return sprintf('%02d:%02d:%02d,%03d', $hours, $minutes, $secs, $milliseconds);
    }
    
    /**
     * Ghi file SRT với UTF-8 không BOM
     */
    private function writeSrtFile($outputPath, $content)
    {
        // Đảm bảo content là UTF-8
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8');
        }
        
        // Ghi file không có BOM
        File::put($outputPath, $content);
        
        // Verify file được tạo đúng
        if (!File::exists($outputPath)) {
            throw new \Exception('Failed to create SRT file');
        }
        
        return true;
    }
    
    /**
     * Validate SRT file
     */
    public function validateSrtFile($srtPath)
    {
        if (!File::exists($srtPath)) {
            return false;
        }
        
        $content = File::get($srtPath);
        
        // Check basic SRT format
        return preg_match('/\d+\s*\n\d{2}:\d{2}:\d{2},\d{3}\s*-->\s*\d{2}:\d{2}:\d{2},\d{3}/', $content);
    }

    /**
     * Tạo ASS subtitle file (Advanced SubStation Alpha)
     */
    public function createAssFile($timedSegments, $outputPath)
    {
        try {
            // ASS header với Unicode support
            $assContent = "[Script Info]\n";
            $assContent .= "Title: Vietnamese Subtitle\n";
            $assContent .= "ScriptType: v4.00+\n";
            $assContent .= "Collisions: Normal\n";
            $assContent .= "PlayDepth: 0\n";
            $assContent .= "Timer: 100.0000\n";
            $assContent .= "Video Aspect Ratio: 0\n";
            $assContent .= "Video Zoom: 6\n";
            $assContent .= "Video Position: 0\n\n";

            $assContent .= "[V4+ Styles]\n";
            $assContent .= "Format: Name, Fontname, Fontsize, PrimaryColour, SecondaryColour, OutlineColour, BackColour, Bold, Italic, Underline, StrikeOut, ScaleX, ScaleY, Spacing, Angle, BorderStyle, Outline, Shadow, Alignment, MarginL, MarginR, MarginV, Encoding\n";
            $assContent .= "Style: Default,Arial Unicode MS,24,&Hffffff,&Hffffff,&H0,&H80000000,0,0,0,0,100,100,0,0,1,2,0,2,10,10,10,1\n\n";

            $assContent .= "[Events]\n";
            $assContent .= "Format: Layer, Start, End, Style, Name, MarginL, MarginR, MarginV, Effect, Text\n";

            // Thêm dialogue events
            foreach ($timedSegments as $segment) {
                $startTime = $this->formatAssTime($segment['start']);
                $endTime = $this->formatAssTime($segment['end']);
                $text = str_replace("\n", "\\N", $segment['text']); // ASS line break

                $assContent .= "Dialogue: 0,{$startTime},{$endTime},Default,,0,0,0,,{$text}\n";
            }

            // Ghi file với UTF-8 BOM cho ASS
            $bom = "\xEF\xBB\xBF";
            File::put($outputPath, $bom . $assContent);

            Log::info('SUBTITLE SERVICE: ASS file created', [
                'output_path' => $outputPath,
                'segments_count' => count($timedSegments),
                'file_size' => File::size($outputPath)
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('SUBTITLE SERVICE: Failed to create ASS file', [
                'error' => $e->getMessage(),
                'output_path' => $outputPath
            ]);
            return false;
        }
    }

    /**
     * Format time for ASS format (H:MM:SS.cc)
     */
    private function formatAssTime($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = floor($seconds % 60);
        $centiseconds = floor(($seconds - floor($seconds)) * 100);

        return sprintf('%d:%02d:%02d.%02d', $hours, $minutes, $secs, $centiseconds);
    }
}
