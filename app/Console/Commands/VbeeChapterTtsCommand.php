<?php

namespace App\Console\Commands;

use App\Models\Chapter;
use App\Models\Story;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class VbeeChapterTtsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vbee:chapter-tts
                            {--chapter_id= : ID của chapter cụ thể}
                            {--story_id= : ID của story để xử lý tất cả chapters}
                            {--voice=hn_female_ngochuyen_full_48k-fhg : Giọng đọc}
                            {--bitrate=128 : Bitrate audio}
                            {--speed=1.0 : Tốc độ đọc}
                            {--force : Ghi đè file audio đã tồn tại}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert chapter text to speech using VBee API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $chapterId = $this->option('chapter_id');
        $storyId = $this->option('story_id');
        $voiceCode = $this->option('voice');
        $bitrate = $this->option('bitrate');
        $speedRate = $this->option('speed');
        $force = $this->option('force');

        // Cấu hình API
        $appId = config('services.vbee.app_id');
        $accessToken = config('services.vbee.access_token');

        if (!$appId || !$accessToken) {
            $this->error('Thiếu thông tin xác thực VBee API. Vui lòng kiểm tra cấu hình.');
            return 1;
        }

        // Lấy danh sách chapters cần xử lý
        $chapters = $this->getChaptersToProcess($chapterId, $storyId);

        if ($chapters->isEmpty()) {
            $this->error('Không tìm thấy chapter nào để xử lý.');
            return 1;
        }

        $this->info("Bắt đầu chuyển đổi TTS cho " . $chapters->count() . " chapter(s)");

        $bar = $this->output->createProgressBar($chapters->count());
        $bar->start();

        $successCount = 0;
        $failCount = 0;

        foreach ($chapters as $chapter) {
            $result = $this->processChapter($chapter, $voiceCode, $bitrate, $speedRate, $force, $appId, $accessToken);

            if ($result) {
                $successCount++;
            } else {
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

    /**
     * Lấy danh sách chapters cần xử lý
     */
    private function getChaptersToProcess($chapterId, $storyId)
    {
        if ($chapterId) {
            return Chapter::where('id', $chapterId)->get();
        }

        if ($storyId) {
            return Chapter::where('story_id', $storyId)
                ->where('audio_status', '!=', 'processing')
                ->orderBy('chapter_number')
                ->get();
        }

        return collect();
    }

    /**
     * Xử lý TTS cho một chapter
     */
    private function processChapter($chapter, $voiceCode, $bitrate, $speedRate, $force, $appId, $accessToken)
    {
        try {
            // Cập nhật trạng thái bắt đầu
            $chapter->update([
                'audio_status' => 'processing',
                'tts_voice' => $voiceCode,
                'tts_bitrate' => $bitrate,
                'tts_speed' => $speedRate,
                'tts_started_at' => now(),
            ]);

            // Lấy nội dung chapter
            $content = trim($chapter->content);
            if (empty($content)) {
                $this->newLine();
                $this->warn("Chapter {$chapter->chapter_number} không có nội dung, bỏ qua.");
                $chapter->update(['audio_status' => 'error']);
                return false;
            }

            // Tạo đường dẫn output
            $outputPath = $this->getOutputPath($chapter);
            if (!$outputPath) {
                $this->newLine();
                $this->error("Không thể tạo đường dẫn output cho chapter {$chapter->chapter_number}");
                $chapter->update(['audio_status' => 'error']);
                return false;
            }

            // Kiểm tra file đã tồn tại
            if (!$force && File::exists($outputPath)) {
                $this->newLine();
                $this->info("Chapter {$chapter->chapter_number} đã có audio, bỏ qua.");
                $chapter->update([
                    'audio_status' => 'done',
                    'audio_file_path' => $outputPath,
                    'tts_completed_at' => now(),
                ]);
                return true;
            }

            // Gọi VBee API
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer $accessToken",
            ])->post('https://vbee.vn/api/v1/tts', [
                'app_id' => $appId,
                'input_text' => $content,
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
                        // Tạo thư mục nếu chưa tồn tại
                        $directory = dirname($outputPath);
                        if (!File::isDirectory($directory)) {
                            File::makeDirectory($directory, 0755, true);
                        }

                        File::put($outputPath, $audioContent);

                        // Cập nhật trạng thái thành công
                        $chapter->update([
                            'audio_status' => 'done',
                            'audio_file_path' => $outputPath,
                            'tts_completed_at' => now(),
                        ]);

                        return true;
                    } else {
                        $this->newLine();
                        $this->error("Không thể tải file âm thanh cho chapter {$chapter->chapter_number}");
                        $chapter->update(['audio_status' => 'error']);
                        return false;
                    }
                } else {
                    $this->newLine();
                    $this->error("Không tìm thấy audio_link trong phản hồi cho chapter {$chapter->chapter_number}");
                    $chapter->update(['audio_status' => 'error']);
                    return false;
                }
            } else {
                $this->newLine();
                $this->error("Lỗi HTTP {$response->status()} khi xử lý chapter {$chapter->chapter_number}");
                $chapter->update(['audio_status' => 'error']);
                return false;
            }
        } catch (\Exception $e) {
            $this->newLine();
            $this->error("Lỗi khi xử lý chapter {$chapter->chapter_number}: " . $e->getMessage());
            $chapter->update(['audio_status' => 'error']);
            return false;
        }
    }

    /**
     * Tạo đường dẫn output cho audio file
     */
    private function getOutputPath($chapter)
    {
        if (!$chapter->story || !$chapter->story->folder_name) {
            return null;
        }

        $audioBasePath = config('constants.STORAGE_PATHS.AUDIO');
        $storyFolder = $chapter->story->folder_name;

        return base_path($audioBasePath . $storyFolder . "/chuong_{$chapter->chapter_number}.mp3");
    }
}
