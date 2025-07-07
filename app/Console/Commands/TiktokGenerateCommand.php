<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class TiktokGenerateCommand extends Command
{
    protected $signature = 'tiktok:generate
                            {--script= : Kịch bản review sản phẩm}
                            {--product-video= : Đường dẫn video sản phẩm}
                            {--product-image= : Đường dẫn ảnh sản phẩm (tùy chọn)}
                            {--voice=hn_female_ngochuyen_full_48k-fhg : Giọng đọc VBee}
                            {--bitrate=128 : Bitrate audio}
                            {--speed=1.0 : Tốc độ đọc}
                            {--volume=18 : Mức âm lượng (dB)}
                            {--logo= : Đường dẫn logo (tùy chọn)}
                            {--logo-position=bottom-right : Vị trí logo}
                            {--logo-size=100 : Kích thước logo (px)}
                            {--output= : Tên file output}
                            {--temp-dir= : Thư mục tạm thời}';

    protected $description = 'Generate TikTok review video from script, product video and image';

    public function handle()
    {
        // Kiểm tra FFmpeg
        if (!$this->checkFFmpeg()) {
            $this->error('❌ FFmpeg không được cài đặt hoặc không thể truy cập');
            return 1;
        }

        $script = $this->option('script');
        $productVideoPath = $this->option('product-video');
        $productImagePath = $this->option('product-image');
        $voice = $this->option('voice');
        $bitrate = $this->option('bitrate');
        $speed = $this->option('speed');
        $volume = $this->option('volume');
        $logoPath = $this->option('logo');
        $logoPosition = $this->option('logo-position');
        $logoSize = $this->option('logo-size');
        $outputName = $this->option('output');
        $tempDir = $this->option('temp-dir');

        if (!$script || !$productVideoPath || !$outputName) {
            $this->error('Thiếu tham số bắt buộc: script, product-video, output');
            return 1;
        }

        try {
            $this->info("🎬 Bắt đầu tạo video TikTok review");
            $this->info("📝 Kịch bản: " . substr($script, 0, 50) . "...");
            $this->info("🎥 Video sản phẩm: " . basename($productVideoPath));
            
            if ($productImagePath) {
                $this->info("🖼️ Ảnh sản phẩm: " . basename($productImagePath));
            }

            // Tạo thư mục output
            $outputDir = storage_path('app/tiktok_videos');
            if (!File::isDirectory($outputDir)) {
                File::makeDirectory($outputDir, 0755, true);
            }

            $outputPath = $outputDir . '/' . $outputName;

            // Bước 1: Chuyển đổi script thành audio bằng VBee API
            $this->info("🎤 Bước 1: Chuyển đổi script thành audio...");
            $audioPath = $this->convertTextToSpeech($script, $voice, $bitrate, $speed, $tempDir);
            
            if (!$audioPath) {
                throw new \Exception("Không thể chuyển đổi text thành audio");
            }

            // Bước 2: Lấy thời lượng audio
            $this->info("⏱️ Bước 2: Lấy thời lượng audio...");
            $audioDuration = $this->getAudioDuration($audioPath);
            
            if (!$audioDuration) {
                throw new \Exception("Không thể lấy thời lượng audio");
            }
            
            $this->info("   Thời lượng audio: {$audioDuration}s");

            // Bước 3: Xóa tiếng video gốc
            $this->info("🔇 Bước 3: Xóa tiếng video sản phẩm...");
            $mutedVideoPath = $this->removeVideoAudio($productVideoPath, $tempDir);

            // Bước 4: Cắt video để khớp với thời lượng audio
            $this->info("✂️ Bước 4: Cắt video theo thời lượng audio...");
            $trimmedVideoPath = $this->trimVideoToAudioLength($mutedVideoPath, $audioDuration, $tempDir);

            // Bước 5: Ghép audio vào video với volume control
            $this->info("🎵 Bước 5: Ghép audio vào video (Volume: {$volume}dB)...");
            $finalVideoPath = $this->mergeAudioWithVideo($trimmedVideoPath, $audioPath, $outputPath, $volume);

            // Bước 6: Tối ưu hóa cho TikTok (9:16 ratio, 1080x1920)
            $this->info("📱 Bước 6: Tối ưu hóa cho TikTok...");
            $this->optimizeForTiktok($finalVideoPath, $outputPath, $productImagePath, $logoPath, $logoPosition, $logoSize);

            $this->info("✅ Hoàn thành! Video đã được lưu tại: " . basename($outputPath));
            
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Lỗi: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Chuyển đổi text thành speech bằng VBee API
     */
    private function convertTextToSpeech($text, $voice, $bitrate, $speed, $tempDir)
    {
        try {
            $appId = config('services.vbee.app_id');
            $accessToken = config('services.vbee.access_token');

            if (!$appId || !$accessToken) {
                throw new \Exception("Chưa cấu hình VBee API credentials");
            }

            $this->info("   Gọi VBee API...");
            
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer $accessToken",
            ])->timeout(60)->post('https://vbee.vn/api/v1/tts', [
                'app_id' => $appId,
                'input_text' => $text,
                'voice_code' => $voice,
                'audio_type' => 'mp3',
                'bitrate' => $bitrate,
                'speed_rate' => $speed,
                'response_type' => 'direct'
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                if (isset($responseData['result']) && $responseData['result'] === 'success') {
                    $audioUrl = $responseData['link_download'];
                    
                    // Download file audio
                    $this->info("   Download file audio...");
                    $audioResponse = Http::timeout(120)->get($audioUrl);
                    
                    if ($audioResponse->successful()) {
                        $audioPath = $tempDir . '/script_audio.mp3';
                        File::put($audioPath, $audioResponse->body());
                        
                        $this->info("   ✅ Đã tạo audio: " . basename($audioPath));
                        return $audioPath;
                    }
                }
            }

            throw new \Exception("VBee API response: " . $response->body());

        } catch (\Exception $e) {
            $this->error("   ❌ Lỗi VBee API: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Lấy thời lượng audio
     */
    private function getAudioDuration($audioPath)
    {
        $command = "ffprobe -v quiet -show_entries format=duration -of csv=p=0 \"$audioPath\"";
        $output = shell_exec($command);
        return $output ? (float) trim($output) : null;
    }

    /**
     * Xóa tiếng video
     */
    private function removeVideoAudio($videoPath, $tempDir)
    {
        $mutedPath = $tempDir . '/muted_video.mp4';
        $command = "ffmpeg -i \"$videoPath\" -an -c:v copy -y \"$mutedPath\"";
        
        $this->info("   Executing: " . $command);
        shell_exec($command);
        
        if (File::exists($mutedPath)) {
            $this->info("   ✅ Đã xóa tiếng video");
            return $mutedPath;
        }
        
        throw new \Exception("Không thể xóa tiếng video");
    }

    /**
     * Cắt video theo thời lượng audio
     */
    private function trimVideoToAudioLength($videoPath, $duration, $tempDir)
    {
        $trimmedPath = $tempDir . '/trimmed_video.mp4';
        
        // Lấy thời lượng video gốc
        $videoDuration = $this->getVideoDuration($videoPath);
        
        if ($videoDuration >= $duration) {
            // Video dài hơn audio, cắt video
            $command = "ffmpeg -i \"$videoPath\" -t $duration -c copy -y \"$trimmedPath\"";
        } else {
            // Video ngắn hơn audio, lặp video
            $loopCount = ceil($duration / $videoDuration);
            $command = "ffmpeg -stream_loop $loopCount -i \"$videoPath\" -t $duration -c copy -y \"$trimmedPath\"";
        }
        
        $this->info("   Executing: " . $command);
        shell_exec($command);
        
        if (File::exists($trimmedPath)) {
            $this->info("   ✅ Đã cắt video theo thời lượng audio");
            return $trimmedPath;
        }
        
        throw new \Exception("Không thể cắt video");
    }

    /**
     * Lấy thời lượng video
     */
    private function getVideoDuration($videoPath)
    {
        $command = "ffprobe -v quiet -show_entries format=duration -of csv=p=0 \"$videoPath\"";
        $output = shell_exec($command);
        return $output ? (float) trim($output) : null;
    }

    /**
     * Ghép audio vào video với volume control
     */
    private function mergeAudioWithVideo($videoPath, $audioPath, $outputPath, $volume = 18)
    {
        // Sử dụng filter để điều chỉnh volume
        $volumeFilter = "volume={$volume}dB";
        $command = "ffmpeg -i \"$videoPath\" -i \"$audioPath\" -filter_complex \"[1:a]{$volumeFilter}[a]\" -map 0:v -map \"[a]\" -c:v copy -c:a aac -shortest -y \"$outputPath\"";

        $this->info("   Executing: " . $command);
        shell_exec($command);

        if (File::exists($outputPath)) {
            $this->info("   ✅ Đã ghép audio vào video với volume {$volume}dB");
            return $outputPath;
        }

        throw new \Exception("Không thể ghép audio vào video");
    }

    /**
     * Tối ưu hóa video cho TikTok (9:16 ratio) với logo
     */
    private function optimizeForTiktok($inputPath, $outputPath, $productImagePath = null, $logoPath = null, $logoPosition = 'bottom-right', $logoSize = 100)
    {
        // TikTok optimal: 1080x1920 (9:16)
        $width = 1080;
        $height = 1920;
        
        // Tạo filter complex cho video, ảnh và logo
        $filterComplex = $this->buildFilterComplex($width, $height, $productImagePath, $logoPath, $logoPosition, $logoSize);

        $tempOptimized = dirname($outputPath) . '/temp_optimized.mp4';

        // Xây dựng command FFmpeg
        $inputs = ["-i \"$inputPath\""];
        if ($productImagePath) {
            $inputs[] = "-i \"$productImagePath\"";
        }
        if ($logoPath && File::exists($logoPath)) {
            $inputs[] = "-i \"$logoPath\"";
        }

        $command = "ffmpeg " . implode(' ', $inputs) . " -filter_complex \"$filterComplex\" -map \"[final]\" -map 0:a -c:a copy -y \"$tempOptimized\"";
        
        $this->info("   Executing: " . $command);
        shell_exec($command);
        
        if (File::exists($tempOptimized)) {
            // Di chuyển file tối ưu hóa thành file cuối cùng
            File::move($tempOptimized, $outputPath);
            $logoInfo = $logoPath ? " với logo" : "";
            $this->info("   ✅ Đã tối ưu hóa cho TikTok (9:16 ratio){$logoInfo}");
        } else {
            throw new \Exception("Không thể tối ưu hóa video cho TikTok");
        }
    }

    /**
     * Xây dựng filter complex cho FFmpeg
     */
    private function buildFilterComplex($width, $height, $productImagePath, $logoPath, $logoPosition, $logoSize)
    {
        $filters = [];
        $inputIndex = 0;

        if ($productImagePath) {
            // Có ảnh sản phẩm: tạo layout với video ở giữa và ảnh ở dưới
            $videoHeight = 1440;
            $imageHeight = 480;

            $filters[] = "[0:v]scale=$width:$videoHeight:force_original_aspect_ratio=decrease,pad=$width:$videoHeight:(ow-iw)/2:(oh-ih)/2[v0]";
            $filters[] = "[1:v]scale=$width:$imageHeight:force_original_aspect_ratio=decrease,pad=$width:$imageHeight:(ow-iw)/2:(oh-ih)/2[v1]";
            $filters[] = "[v0][v1]vstack=inputs=2[base]";
            $inputIndex = 2;
        } else {
            // Không có ảnh: chỉ resize video
            $filters[] = "[0:v]scale=$width:$height:force_original_aspect_ratio=decrease,pad=$width:$height:(ow-iw)/2:(oh-ih)/2[base]";
            $inputIndex = 1;
        }

        // Thêm logo nếu có
        if ($logoPath && File::exists($logoPath)) {
            $logoFilter = $this->buildLogoFilter($logoPosition, $logoSize, $width, $height);
            $filters[] = "[{$inputIndex}:v]scale={$logoSize}:{$logoSize}:force_original_aspect_ratio=decrease[logo]";
            $filters[] = "[base][logo]overlay={$logoFilter}[final]";
        } else {
            $filters[] = "[base]copy[final]";
        }

        return implode(';', $filters);
    }

    /**
     * Xây dựng filter cho vị trí logo
     */
    private function buildLogoFilter($position, $logoSize, $videoWidth, $videoHeight)
    {
        $margin = 20; // Margin từ cạnh

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
                return "W-w-{$margin}:H-h-{$margin}"; // Default: bottom-right
        }
    }

    /**
     * Kiểm tra FFmpeg có sẵn không
     */
    private function checkFFmpeg()
    {
        $output = shell_exec('ffmpeg -version 2>&1');
        return $output && strpos($output, 'ffmpeg version') !== false;
    }
}
