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
            // Validate input text
            if (empty(trim($text))) {
                throw new \Exception('Subtitle text cannot be empty');
            }

            // If text is too short, expand it
            $originalText = trim($text);
            if (strlen($originalText) < 10) {
                $text = str_repeat($originalText . ' ', 3); // Repeat 3 times
                Log::info('SUBTITLE SERVICE: Expanded short text', [
                    'original' => $originalText,
                    'expanded' => $text
                ]);
            }

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

        Log::info('SUBTITLE SERVICE: Splitting text into segments', [
            'original_text_length' => mb_strlen($text),
            'text_preview' => substr($text, 0, 100)
        ]);

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

        // Nếu không có xuống dòng hoặc ít segment, chia theo dấu câu
        if (count($segments) <= 1) {
            $segments = $this->splitBySentences($text);
        }

        // Đảm bảo mỗi segment không quá dài nhưng vẫn có nghĩa
        $finalSegments = [];
        foreach ($segments as $segment) {
            if (mb_strlen($segment) > 60) { // Giảm xuống 60 để tạo nhiều segment hơn
                $subSegments = $this->splitByLength($segment, 60);
                $finalSegments = array_merge($finalSegments, $subSegments);
            } else {
                $finalSegments[] = $segment;
            }
        }

        // Nếu vẫn ít segment, chia thêm các segment dài
        if (count($finalSegments) < 5) { // Đảm bảo ít nhất 5 segments
            $extraSegments = [];
            foreach ($finalSegments as $segment) {
                if (mb_strlen($segment) > 40) {
                    $subSegments = $this->splitByLength($segment, 40);
                    $extraSegments = array_merge($extraSegments, $subSegments);
                } else {
                    $extraSegments[] = $segment;
                }
            }
            $finalSegments = $extraSegments;
        }

        // Lọc bỏ segment rỗng và log kết quả
        $finalSegments = array_filter($finalSegments, function($segment) {
            return !empty(trim($segment));
        });

        // Nếu vẫn ít segment, lặp lại text để đủ subtitle
        if (count($finalSegments) < 3 && count($finalSegments) > 0) {
            $originalSegments = $finalSegments;
            $targetSegments = max(5, count($finalSegments) * 2);
            $maxIterations = 10; // Prevent infinite loop
            $iterations = 0;

            while (count($finalSegments) < $targetSegments && $iterations < $maxIterations) {
                $iterations++;
                $beforeCount = count($finalSegments);

                foreach ($originalSegments as $segment) {
                    if (count($finalSegments) >= $targetSegments) break;
                    $finalSegments[] = $segment;
                }

                // If no progress made, break to prevent infinite loop
                if (count($finalSegments) === $beforeCount) {
                    Log::warning('SUBTITLE SERVICE: No progress in segment duplication, breaking loop');
                    break;
                }
            }

            Log::info('SUBTITLE SERVICE: Repeated segments to ensure sufficient content', [
                'original_segments' => count($originalSegments),
                'final_segments' => count($finalSegments),
                'iterations' => $iterations
            ]);
        } elseif (count($finalSegments) === 0) {
            // If no segments at all, create a default one
            $finalSegments = [trim($text) ?: 'Default subtitle'];
            Log::warning('SUBTITLE SERVICE: No segments created, using default', [
                'original_text' => $text
            ]);
        }

        Log::info('SUBTITLE SERVICE: Text splitting completed', [
            'original_length' => mb_strlen($text),
            'segments_count' => count($finalSegments),
            'segments_preview' => array_slice($finalSegments, 0, 3),
            'total_segments_length' => array_sum(array_map('mb_strlen', $finalSegments))
        ]);

        return array_values($finalSegments); // Re-index array
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
        // Chia theo nhiều dấu câu khác nhau để tạo nhiều segment hơn
        $patterns = [
            '/[.!?]+/',           // Dấu chấm, chấm hỏi, chấm than
            '/[,;:]+/',           // Dấu phẩy, chấm phẩy, hai chấm
            '/\s+và\s+/',         // Từ "và"
            '/\s+nhưng\s+/',      // Từ "nhưng"
            '/\s+hoặc\s+/',       // Từ "hoặc"
            '/\s+nên\s+/',        // Từ "nên"
            '/\s+để\s+/',         // Từ "để"
        ];

        $segments = [$text];

        foreach ($patterns as $pattern) {
            $newSegments = [];
            foreach ($segments as $segment) {
                $parts = preg_split($pattern, $segment);
                $newSegments = array_merge($newSegments, $parts);
            }
            $segments = $newSegments;
        }

        return array_filter(array_map('trim', $segments), function($sentence) {
            return !empty($sentence) && mb_strlen($sentence) > 3; // Bỏ segment quá ngắn
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

        Log::info('SUBTITLE SERVICE: Calculating timing', [
            'segment_count' => $segmentCount,
            'audio_duration' => $audioDuration,
            'segments_preview' => array_slice($segments, 0, 3)
        ]);

        // Nếu không có audio duration, ước tính dựa trên text và số lượng segment
        if (!$audioDuration) {
            $totalChars = array_sum(array_map('mb_strlen', $segments));
            // Cải thiện ước tính: 0.2s/ký tự + buffer cho mỗi segment
            $audioDuration = max($totalChars * 0.2 + $segmentCount * 1.5, $segmentCount * 3.0);
        }

        // Calculate minimum duration needed for all segments
        $minTotalDuration = $segmentCount * 2.5; // 2.5s per segment minimum

        // 🔥🔥🔥 FORCE OVERRIDE: Respect user's duration choice
        // If user chose "duration based on images", use video duration even if it's shorter than minimum
        // Only extend duration if it's significantly shorter than minimum (less than 1.5s per segment)
        $minRequiredDuration = $segmentCount * 1.5; // Absolute minimum: 1.5s per segment

        if ($audioDuration < $minRequiredDuration) {
            Log::warning('🚨 FORCE OVERRIDE: Video duration too short, extending to minimum', [
                'video_duration' => $audioDuration,
                'min_required' => $minRequiredDuration,
                'extending_to' => $minRequiredDuration
            ]);
            $audioDuration = $minRequiredDuration;
        }

        Log::info('🔥🔥🔥 FORCE OVERRIDE: Duration calculation', [
            'original_audio_duration' => $audioDuration,
            'min_total_duration' => $minTotalDuration,
            'min_required_duration' => $minRequiredDuration,
            'final_duration' => $audioDuration,
            'segments_count' => $segmentCount,
            'duration_extended' => $audioDuration > $minRequiredDuration
        ]);

        $timedSegments = [];

        // Tính thời gian cho từng segment dựa trên độ dài text và độ phức tạp
        $totalWeight = 0;
        $weights = [];

        foreach ($segments as $segment) {
            // Tính weight dựa trên độ dài và độ phức tạp của text
            $charCount = mb_strlen($segment);
            $wordCount = str_word_count($segment);
            $complexityFactor = 1 + ($wordCount * 0.1); // Câu nhiều từ cần thời gian đọc lâu hơn

            $weight = max($charCount * $complexityFactor, 15); // Tối thiểu weight = 15
            $weights[] = $weight;
            $totalWeight += $weight;
        }

        // Phân bố subtitle đều trong suốt video
        $currentTime = 0;
        $averageSegmentDuration = $audioDuration / $segmentCount;

        for ($i = 0; $i < $segmentCount; $i++) {
            // Tính duration dựa trên weight nhưng đảm bảo phân bố đều
            $weightBasedDuration = ($weights[$i] / $totalWeight) * $audioDuration;
            $segmentDuration = max($weightBasedDuration, $averageSegmentDuration * 0.8); // Ít nhất 80% thời gian trung bình
            $segmentDuration = max($segmentDuration, 2.0); // Tối thiểu 2.0s

            $startTime = $currentTime;
            $endTime = $currentTime + $segmentDuration;

            $timedSegments[] = [
                'text' => trim($segments[$i]),
                'start' => $startTime,
                'end' => $endTime,
                'duration' => $segmentDuration,
                'weight' => $weights[$i]
            ];

            $currentTime = $endTime;
        }

        // Điều chỉnh timing để đảm bảo phủ hết video và có overlap nhẹ
        if (!empty($timedSegments)) {
            // Thêm overlap nhẹ giữa các segment để tránh khoảng trống
            for ($i = 0; $i < count($timedSegments) - 1; $i++) {
                $overlap = 0.2; // 0.2 giây overlap
                $timedSegments[$i]['end'] = min(
                    $timedSegments[$i]['end'] + $overlap,
                    $timedSegments[$i + 1]['start'] + $overlap
                );
                $timedSegments[$i]['duration'] = $timedSegments[$i]['end'] - $timedSegments[$i]['start'];
            }

            // Đảm bảo subtitle cuối cùng kéo dài đến hết video
            $lastIndex = count($timedSegments) - 1;
            $lastSegment = &$timedSegments[$lastIndex];

            // Nếu subtitle kết thúc sớm hơn video, kéo dài segment cuối
            if ($lastSegment['end'] < $audioDuration) {
                $lastSegment['end'] = $audioDuration;
                $lastSegment['duration'] = $lastSegment['end'] - $lastSegment['start'];

                Log::info('SUBTITLE SERVICE: Extended last segment to cover full video', [
                    'original_end' => $currentTime,
                    'new_end' => $audioDuration,
                    'video_duration' => $audioDuration
                ]);
            }
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
