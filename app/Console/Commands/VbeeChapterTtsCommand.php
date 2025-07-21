<?php

namespace App\Console\Commands;

use App\Models\Chapter;
use App\Models\Story;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
                            {--speed=1.0 : Tốc độ đọc (0.5, 1.0, 1.5, 2.0)}
                            {--volume=1.0 : Âm lượng (1.0, 1.5, 2.0)}
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
        $volumeLevel = $this->option('volume');
        $force = $this->option('force');

        // Lấy story để sử dụng default settings nếu cần
        $story = null;
        if ($storyId) {
            $story = Story::find($storyId);
        } elseif ($chapterId) {
            $chapter = Chapter::find($chapterId);
            if ($chapter) {
                $story = $chapter->story;
            }
        }

        // Sử dụng default settings từ story nếu không có parameters tùy chỉnh
        if ($story) {
            // Chỉ sử dụng default nếu đang dùng giá trị mặc định
            if ($this->option('voice') === 'hn_female_ngochuyen_full_48k-fhg') {
                $voiceCode = $story->default_tts_voice;
            }

            if ($this->option('bitrate') == 128) {
                $bitrate = $story->default_tts_bitrate;
            }

            if ($this->option('speed') == 1.0) {
                $speedRate = $story->default_tts_speed;
            }

            if ($this->option('volume') == 1.0) {
                $volumeLevel = $story->default_tts_volume;
            }
        }

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
            $result = $this->processChapter($chapter, $voiceCode, $bitrate, $speedRate, $volumeLevel, $force, $appId, $accessToken);

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
    private function processChapter($chapter, $voiceCode, $bitrate, $speedRate, $volumeLevel, $force, $appId, $accessToken)
    {
        try {
            // Cập nhật trạng thái bắt đầu
            $chapter->update([
                'audio_status' => 'processing',
                'tts_voice' => $voiceCode,
                'tts_bitrate' => $bitrate,
                'tts_speed' => $speedRate,
                'tts_volume' => $volumeLevel,
                'tts_progress' => 0,
                'tts_error' => null,
                'tts_started_at' => now(),
            ]);

            // Cập nhật tiến độ: 10%
            $chapter->update(['tts_progress' => 10]);

            // Lấy nội dung chapter
            $content = trim($chapter->content);
            if (empty($content)) {
                $this->newLine();
                $this->warn("Chapter {$chapter->chapter_number} không có nội dung, bỏ qua.");
                $chapter->update([
                    'audio_status' => 'failed',
                    'tts_progress' => 0,
                    'tts_error' => 'Chapter không có nội dung'
                ]);
                return false;
            }

            // Cập nhật tiến độ: 20%
            $chapter->update(['tts_progress' => 20]);

            // Tạo đường dẫn output
            $outputPath = $this->getOutputPath($chapter);
            if (!$outputPath) {
                $this->newLine();
                $this->error("Không thể tạo đường dẫn output cho chapter {$chapter->chapter_number}");
                $chapter->update([
                    'audio_status' => 'failed',
                    'tts_progress' => 0,
                    'tts_error' => 'Không thể tạo đường dẫn output'
                ]);
                return false;
            }

            // Kiểm tra file đã tồn tại
            if (!$force && File::exists($outputPath)) {
                $this->newLine();
                $this->info("Chapter {$chapter->chapter_number} đã có audio, bỏ qua.");
                $chapter->update([
                    'audio_status' => 'done',
                    'audio_file_path' => $this->getRelativeAudioPath($chapter),
                    'tts_completed_at' => now(),
                ]);
                return true;
            }

            // Cập nhật tiến độ: 30%
            $chapter->update(['tts_progress' => 30]);

            // Gọi VBee API
            $this->info("Đang gọi VBee API cho chapter {$chapter->chapter_number}...");

            try {
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

                // Cập nhật tiến độ: 60%
                $chapter->update(['tts_progress' => 60]);
            } catch (\Exception $e) {
                $this->error("Lỗi khi gọi VBee API: " . $e->getMessage());
                $chapter->update([
                    'audio_status' => 'failed',
                    'tts_progress' => 0,
                    'tts_error' => 'Lỗi API: ' . $e->getMessage()
                ]);
                return false;
            }

            if ($response->successful()) {
                // Cập nhật tiến độ: 70%
                $chapter->update(['tts_progress' => 70]);

                $responseData = $response->json();

                if (isset($responseData['result']['audio_link'])) {
                    $audioUrl = $responseData['result']['audio_link'];

                    // Cập nhật tiến độ: 80%
                    $chapter->update(['tts_progress' => 80]);

                    try {
                        $audioContent = Http::get($audioUrl)->body();

                        if ($audioContent) {
                            // Cập nhật tiến độ: 90%
                            $chapter->update(['tts_progress' => 90]);

                            // Tạo thư mục nếu chưa tồn tại
                            $directory = dirname($outputPath);
                            if (!File::isDirectory($directory)) {
                                File::makeDirectory($directory, 0755, true);
                            }

                            File::put($outputPath, $audioContent);

                            // Cập nhật trạng thái thành công
                            $chapter->update([
                                'audio_status' => 'completed',
                                'audio_file_path' => $this->getRelativeAudioPath($chapter),
                                'tts_progress' => 100,
                                'tts_completed_at' => now(),
                            ]);

                            // Auto sync audio file to public storage
                            $this->syncAudioFileToPublic($chapter);
                        } else {
                            $this->error("Không thể tải audio từ VBee");
                            $chapter->update([
                                'audio_status' => 'failed',
                                'tts_progress' => 0,
                                'tts_error' => 'Không thể tải audio từ VBee'
                            ]);
                            return false;
                        }
                    } catch (\Exception $e) {
                        $this->error("Lỗi khi tải audio: " . $e->getMessage());
                        $chapter->update([
                            'audio_status' => 'failed',
                            'tts_progress' => 0,
                            'tts_error' => 'Lỗi tải audio: ' . $e->getMessage()
                        ]);
                        return false;
                    }

                        return true;
                } else {
                    $this->newLine();
                    $this->error("Không tìm thấy audio_link trong phản hồi cho chapter {$chapter->chapter_number}");
                    $chapter->update([
                        'audio_status' => 'failed',
                        'tts_progress' => 0,
                        'tts_error' => 'Không tìm thấy audio_link trong phản hồi'
                    ]);
                    return false;
                }
            } else {
                $this->newLine();
                $this->error("Lỗi HTTP {$response->status()} khi xử lý chapter {$chapter->chapter_number}");
                $chapter->update([
                    'audio_status' => 'failed',
                    'tts_progress' => 0,
                    'tts_error' => "Lỗi HTTP {$response->status()}: " . $response->body()
                ]);
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

        $storyFolder = $chapter->story->folder_name;

        // Return absolute path for file operations (using storage_path)
        return storage_path("app/audio/{$storyFolder}/chuong_{$chapter->chapter_number}.mp3");
    }

    /**
     * Tạo relative path để lưu vào database
     */
    private function getRelativeAudioPath($chapter)
    {
        if (!$chapter->story || !$chapter->story->folder_name) {
            return null;
        }

        $storyFolder = $chapter->story->folder_name;
        // Return relative path for database storage (relative to storage/app)
        return "audio/{$storyFolder}/chuong_{$chapter->chapter_number}.mp3";
    }

    /**
     * Sync audio file to public storage for web access
     */
    private function syncAudioFileToPublic($chapter)
    {
        try {
            if (!$chapter->story || !$chapter->story->folder_name) {
                return false;
            }

            $storyFolder = $chapter->story->folder_name;
            $fileName = "chuong_{$chapter->chapter_number}.mp3";

            $sourceFile = storage_path("app/audio/{$storyFolder}/{$fileName}");
            $targetDir = storage_path("app/public/audio/{$storyFolder}");
            $targetFile = "{$targetDir}/{$fileName}";

            // Create target directory if it doesn't exist
            if (!File::isDirectory($targetDir)) {
                File::makeDirectory($targetDir, 0755, true);
            }

            // Copy file if source exists
            if (File::exists($sourceFile)) {
                File::copy($sourceFile, $targetFile);

                Log::info("Audio file synced to public storage", [
                    'chapter_id' => $chapter->id,
                    'source' => $sourceFile,
                    'target' => $targetFile,
                    'file_size' => File::size($sourceFile)
                ]);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error("Failed to sync audio file to public storage", [
                'chapter_id' => $chapter->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }
}
