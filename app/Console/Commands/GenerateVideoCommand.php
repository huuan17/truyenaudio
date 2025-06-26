<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Story;
use App\Models\Chapter;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GenerateVideoCommand extends Command
{
    protected $signature = 'video:generate {story_id} {--chapter=} {--overlay=} {--output=}';
    protected $description = 'Generate video from story image, audio and overlay video';

    public function handle()
    {
        $storyId = $this->argument('story_id');
        $chapterNumber = $this->option('chapter');
        $overlayPath = $this->option('overlay');
        $outputName = $this->option('output');

        // Tìm truyện
        $story = Story::find($storyId);
        if (!$story) {
            $this->error("Không tìm thấy truyện với ID: $storyId");
            return 1;
        }

        // Tạo tên file output dựa trên truyện + chương
        if (!$outputName) {
            $storySlug = $story->folder_name ?: Str::slug($story->title);
            if ($chapterNumber) {
                $outputName = "{$storySlug}_chuong_{$chapterNumber}.mp4";
            } else {
                $outputName = "{$storySlug}_video_tong_hop.mp4";
            }
        }

        $this->info("🎬 Bắt đầu tạo video cho truyện: {$story->title}");
        if ($chapterNumber) {
            $this->info("📖 Chương: {$chapterNumber}");
        } else {
            $this->info("📖 Video tổng hợp");
        }
        $this->info("📁 File output: {$outputName}");
        
        // Kiểm tra ảnh nền
        $imagePath = $this->getImagePath($story);
        if (!$imagePath) {
            $this->error("Không tìm thấy ảnh nền cho truyện");
            return 1;
        }
        
        // Kiểm tra file audio
        $audioPath = $this->getAudioPath($story, $chapterNumber);
        if (!$audioPath) {
            $this->error("Không tìm thấy file audio");
            return 1;
        }
        
        // Kiểm tra overlay video
        if (!$overlayPath) {
            $overlayPath = storage_path('app/video_assets/shortclip.mp4');
        }
        
        if (!file_exists($overlayPath)) {
            $this->error("Không tìm thấy file overlay video: $overlayPath");
            return 1;
        }
        
        // Tạo thư mục output
        $outputDir = storage_path('app/videos/' . $story->folder_name);
        if (!File::isDirectory($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }
        
        // Sử dụng tên file đã tạo ở trên
        $outputPath = $outputDir . '/' . $outputName;
        
        $this->info("📁 Input:");
        $this->info("   Ảnh: $imagePath");
        $this->info("   Audio: $audioPath");
        $this->info("   Overlay: $overlayPath");
        $this->info("📁 Output: $outputPath");
        
        // Tạo video
        $result = $this->generateVideo($imagePath, $audioPath, $overlayPath, $outputPath);
        
        if ($result) {
            $this->info("✅ Đã tạo video thành công: $outputPath");
            return 0;
        } else {
            $this->error("❌ Lỗi khi tạo video");
            return 1;
        }
    }
    
    private function getImagePath($story)
    {
        // Kiểm tra ảnh trong public/images/stories
        if ($story->image) {
            $publicPath = public_path('images/stories/' . $story->image);
            if (file_exists($publicPath)) {
                return $publicPath;
            }
        }
        
        // Kiểm tra ảnh mặc định
        $defaultPath = public_path('images/default-story.jpg');
        if (file_exists($defaultPath)) {
            return $defaultPath;
        }
        
        return null;
    }
    
    private function getAudioPath($story, $chapterNumber = null)
    {
        $audioDir = storage_path('app/truyen/mp3-' . $story->folder_name);

        if (!File::isDirectory($audioDir)) {
            $this->error("Không tìm thấy thư mục audio: $audioDir");
            return null;
        }

        if ($chapterNumber) {
            // Tìm file audio cho chương cụ thể với nhiều pattern
            $patterns = [
                "chuong-{$chapterNumber}.mp3",
                "chuong_{$chapterNumber}.mp3",
                "chapter-{$chapterNumber}.mp3",
                "chapter_{$chapterNumber}.mp3",
                "{$chapterNumber}.mp3"
            ];

            foreach ($patterns as $pattern) {
                $audioFile = $audioDir . "/" . $pattern;
                if (file_exists($audioFile)) {
                    $this->info("📻 Tìm thấy audio: " . basename($audioFile));
                    return $audioFile;
                }
            }

            $this->error("Không tìm thấy file audio cho chương {$chapterNumber}");
            return null;
        } else {
            // Tìm file audio đầu tiên và hiển thị thông tin
            $audioFiles = File::glob($audioDir . '/*.mp3');
            if (!empty($audioFiles)) {
                $selectedFile = $audioFiles[0];
                $fileName = basename($selectedFile, '.mp3');

                // Thử extract số chương từ tên file
                $detectedChapter = null;
                if (preg_match('/chuong[_-]?(\d+)/i', $fileName, $matches)) {
                    $detectedChapter = $matches[1];
                } elseif (preg_match('/chapter[_-]?(\d+)/i', $fileName, $matches)) {
                    $detectedChapter = $matches[1];
                } elseif (preg_match('/^(\d+)$/', $fileName, $matches)) {
                    $detectedChapter = $matches[1];
                }

                $this->info("📻 Sử dụng audio: " . basename($selectedFile));

                return $selectedFile;
            }
        }

        return null;
    }
    
    private function generateVideo($imagePath, $audioPath, $overlayPath, $outputPath)
    {
        try {
            $tempDir = storage_path('app/temp/video_' . uniqid());
            File::makeDirectory($tempDir, 0755, true);
            
            $this->info("🔧 Bắt đầu xử lý video...");
            
            // 1. Lấy thời lượng audio
            $this->info("⏱️ Lấy thời lượng audio...");
            $audioDuration = $this->getAudioDuration($audioPath);
            if (!$audioDuration) {
                throw new \Exception("Không thể lấy thời lượng audio");
            }
            $this->info("   Thời lượng: {$audioDuration}s");
            
            // 2. Lấy thời lượng overlay video
            $overlayDuration = $this->getVideoDuration($overlayPath);
            if (!$overlayDuration) {
                throw new \Exception("Không thể lấy thời lượng overlay video");
            }
            
            // 3. Tạo video loop từ overlay
            $this->info("🔁 Tạo video loop...");
            $loopedPath = $tempDir . '/looped.mp4';
            $this->createLoopedVideo($overlayPath, $overlayDuration, $audioDuration, $loopedPath);
            
            // 4. Tạo video nền từ ảnh
            $this->info("🖼️ Tạo video nền từ ảnh...");
            $bgPath = $tempDir . '/bg.mp4';
            $this->createBackgroundVideo($imagePath, $audioDuration, $bgPath);
            
            // 5. Tạo mask bo góc
            $this->info("🎭 Tạo mask bo góc...");
            $maskPath = $tempDir . '/mask.png';
            $this->createRoundedMask($maskPath);
            
            // 6. Áp dụng mask cho overlay
            $this->info("🧩 Áp dụng mask cho overlay...");
            $roundedOverlayPath = $tempDir . '/rounded_overlay.mp4';
            $this->applyRoundedMask($loopedPath, $maskPath, $roundedOverlayPath);
            
            // 7. Overlay video lên nền
            $this->info("🧷 Overlay video lên nền...");
            $videoWithOverlayPath = $tempDir . '/video_with_overlay.mp4';
            $this->overlayVideo($bgPath, $roundedOverlayPath, $videoWithOverlayPath);
            
            // 8. Ghép audio
            $this->info("🔊 Ghép audio...");
            $this->mergeAudio($videoWithOverlayPath, $audioPath, $outputPath);
            
            // Dọn dẹp file tạm
            File::deleteDirectory($tempDir);
            
            return true;
            
        } catch (\Exception $e) {
            $this->error("Lỗi: " . $e->getMessage());
            if (isset($tempDir) && File::isDirectory($tempDir)) {
                File::deleteDirectory($tempDir);
            }
            return false;
        }
    }

    private function getAudioDuration($audioPath)
    {
        $command = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 \"$audioPath\"";
        $output = shell_exec($command);
        return $output ? ceil(floatval(trim($output))) : null;
    }

    private function getVideoDuration($videoPath)
    {
        $command = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 \"$videoPath\"";
        $output = shell_exec($command);
        return $output ? floatval(trim($output)) : null;
    }

    private function createLoopedVideo($overlayPath, $overlayDuration, $audioDuration, $outputPath)
    {
        $tempDir = dirname($outputPath);
        $loopCount = ceil($audioDuration / $overlayDuration);

        // Tạo file concat list
        $loopFile = $tempDir . '/loop.txt';
        $content = str_repeat("file '$overlayPath'\n", $loopCount);
        file_put_contents($loopFile, $content);

        // Tạo video loop
        $loopedRawPath = $tempDir . '/looped_raw.mp4';
        $command = "ffmpeg -f concat -safe 0 -i \"$loopFile\" -c copy -y \"$loopedRawPath\"";
        shell_exec($command);

        // Cắt để khớp thời lượng audio
        $command = "ffmpeg -i \"$loopedRawPath\" -t $audioDuration -c copy -y \"$outputPath\"";
        shell_exec($command);

        // Tắt tiếng
        $mutedPath = $tempDir . '/looped_muted.mp4';
        $command = "ffmpeg -i \"$outputPath\" -an -y \"$mutedPath\"";
        shell_exec($command);

        // Copy file muted về outputPath
        copy($mutedPath, $outputPath);
    }

    private function createBackgroundVideo($imagePath, $duration, $outputPath)
    {
        $command = "ffmpeg -loop 1 -i \"$imagePath\" -t $duration -vf scale=1280:720 -r 30 -pix_fmt yuv420p -y \"$outputPath\"";
        shell_exec($command);
    }

    private function createRoundedMask($outputPath)
    {
        // Sử dụng ImageMagick để tạo mask bo góc
        $command = "convert -size 320x180 xc:none -draw \"roundrectangle 0,0,319,179,40,40\" -alpha set \"$outputPath\"";
        shell_exec($command);

        // Nếu không có ImageMagick, tạo mask đơn giản bằng FFmpeg
        if (!file_exists($outputPath)) {
            $command = "ffmpeg -f lavfi -i color=white:size=320x180:duration=1 -vf \"format=rgba\" -frames:v 1 -y \"$outputPath\"";
            shell_exec($command);
        }
    }

    private function applyRoundedMask($videoPath, $maskPath, $outputPath)
    {
        if (file_exists($maskPath)) {
            // Với mask bo góc
            $command = "ffmpeg -i \"$videoPath\" -i \"$maskPath\" -filter_complex \"[0:v]scale=320:180[vid];[1:v]format=rgba,colorchannelmixer=aa=1.0[mask];[vid][mask]alphamerge[out]\" -map \"[out]\" -y \"$outputPath\"";
        } else {
            // Không có mask, chỉ resize
            $command = "ffmpeg -i \"$videoPath\" -vf scale=320:180 -y \"$outputPath\"";
        }
        shell_exec($command);
    }

    private function overlayVideo($bgPath, $overlayPath, $outputPath)
    {
        $command = "ffmpeg -i \"$bgPath\" -i \"$overlayPath\" -filter_complex \"[0:v][1:v]overlay=W-w-10:H-h-10\" -c:a copy -y \"$outputPath\"";
        shell_exec($command);
    }

    private function mergeAudio($videoPath, $audioPath, $outputPath)
    {
        $command = "ffmpeg -i \"$videoPath\" -i \"$audioPath\" -map 0:v -map 1:a -af \"volume=20dB\" -c:v libx264 -preset fast -shortest -y \"$outputPath\"";
        shell_exec($command);
    }
}
