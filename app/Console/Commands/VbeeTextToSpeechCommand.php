<?php

namespace App\Console\Commands;

use App\Models\Story;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class VbeeTextToSpeechCommand extends Command
{
    protected $signature = 'vbee:tts {story_id} {--voice=hn_female_ngochuyen_full_48k-fhg} {--bitrate=128} {--speed=1.0}';
    protected $description = 'Convert text files to audio using VBee API';

    public function handle()
    {
        $storyId = $this->argument('story_id');
        $voiceCode = $this->option('voice');
        $bitrate = $this->option('bitrate');
        $speedRate = $this->option('speed');
        
        // Tìm truyện
        $story = Story::find($storyId);
        if (!$story) {
            $this->error("Không tìm thấy truyện với ID: $storyId");
            return 1;
        }
        
        // Cấu hình đường dẫn
        $textBasePath = config('constants.STORAGE_PATHS.TEXT');
        $audioBasePath = config('constants.STORAGE_PATHS.AUDIO');
        
        $inputFolder = base_path($story->crawl_path);
        $outputFolder = base_path($audioBasePath . $story->folder_name);
        
        // Cấu hình API
        $appId = config('services.vbee.app_id');
        $accessToken = config('services.vbee.access_token');
        
        if (!$appId || !$accessToken) {
            $this->error('Thiếu thông tin xác thực VBee API. Vui lòng kiểm tra cấu hình.');
            return 1;
        }

        // Kiểm tra thư mục đầu vào
        if (!File::isDirectory($inputFolder)) {
            $this->error("Thư mục đầu vào không tồn tại: $inputFolder");
            return 1;
        }

        // Tạo thư mục đầu ra nếu chưa tồn tại
        if (!File::isDirectory($outputFolder)) {
            File::makeDirectory($outputFolder, 0755, true);
            $this->info("Đã tạo thư mục đầu ra: $outputFolder");
        }

        // Tìm tất cả file .txt
        $files = File::glob("$inputFolder/*.txt");
        
        if (empty($files)) {
            $this->error("Không tìm thấy file .txt nào trong thư mục {$inputFolder}");
            return 1;
        }

        $this->info("Bắt đầu chuyển đổi {$story->title} ({$story->folder_name})");
        $this->info("Tổng số file: " . count($files));
        
        $bar = $this->output->createProgressBar(count($files));
        $bar->start();
        
        $successCount = 0;
        $failCount = 0;

        foreach ($files as $filePath) {
            $text = trim(File::get($filePath));
            $fileName = pathinfo($filePath, PATHINFO_FILENAME);
            
            if (empty($text)) {
                $this->newLine();
                $this->warn("File {$fileName}.txt không có nội dung, bỏ qua.");
                $bar->advance();
                continue;
            }
            
            $outputPath = "$outputFolder/$fileName.mp3";
            
            // Kiểm tra nếu file đã tồn tại
            if (File::exists($outputPath)) {
                $this->newLine();
                $this->info("File {$fileName}.mp3 đã tồn tại, bỏ qua.");
                $bar->advance();
                continue;
            }

            try {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => "Bearer $accessToken",
                ])->post('https://vbee.vn/api/v1/tts', [
                    'app_id' => $appId,
                    'input_text' => $text,
                    'voice_code' => $voiceCode,
                    'audio_type' => 'mp3',
                    'bitrate' => $bitrate,
                    'speed_rate' => $speedRate,
                    'response_type' => 'direct'
                ]);
                
                if ($response->successful()) {
                    $responseData = $response->json();
                    
                    if (isset($responseData['result']['audio_link'])) {
                        $audioUrl = $responseData['result']['audio_link'];
                        $audioContent = Http::get($audioUrl)->body();
                        
                        if ($audioContent) {
                            File::put($outputPath, $audioContent);
                            $successCount++;
                        } else {
                            $this->newLine();
                            $this->error("Không thể tải file âm thanh từ: $audioUrl");
                            $failCount++;
                        }
                    } else {
                        $this->newLine();
                        $this->error("Không tìm thấy audio_link trong phản hồi.");
                        $this->line(json_encode($responseData, JSON_PRETTY_PRINT));
                        $failCount++;
                    }
                } else {
                    $this->newLine();
                    $this->error("Lỗi HTTP {$response->status()} khi xử lý file {$fileName}.txt");
                    $this->line($response->body());
                    $failCount++;
                }
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Lỗi: " . $e->getMessage());
                $failCount++;
            }
            
            $bar->advance();
            // Tạm dừng để tránh quá tải API
            sleep(1);
        }
        
        $bar->finish();
        $this->newLine(2);
        $this->info("Hoàn thành: $successCount thành công, $failCount thất bại");
        
        return 0;
    }
}

