<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\ScheduledPost;
use Illuminate\Support\Facades\Log;

class ProcessScheduledPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $scheduledPost;
    public $tries = 3;
    public $timeout = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(ScheduledPost $scheduledPost)
    {
        $this->scheduledPost = $scheduledPost;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Processing scheduled post", ['post_id' => $this->scheduledPost->id]);

            // Kiểm tra post vẫn pending
            if (!$this->scheduledPost->isPending()) {
                Log::info("Post is not pending, skipping", ['post_id' => $this->scheduledPost->id]);
                return;
            }

            // Kiểm tra kênh có hoạt động
            if (!$this->scheduledPost->channel->is_active) {
                $this->scheduledPost->markAsFailed("Kênh không hoạt động");
                return;
            }

            // Kiểm tra file video
            if (!file_exists($this->scheduledPost->video_path)) {
                $this->scheduledPost->markAsFailed("File video không tồn tại: {$this->scheduledPost->video_path}");
                return;
            }

            // Đánh dấu đang xử lý
            $this->scheduledPost->markAsProcessing();

            // Upload video
            $result = $this->uploadToChannel();

            if ($result['success']) {
                $this->scheduledPost->markAsUploaded($result['post_id'] ?? null, $result['url'] ?? null);
                Log::info("Post uploaded successfully", ['post_id' => $this->scheduledPost->id]);
            } else {
                $this->scheduledPost->markAsFailed($result['error'] ?? 'Unknown error');
                Log::error("Post upload failed", ['post_id' => $this->scheduledPost->id, 'error' => $result['error']]);
            }

        } catch (\Exception $e) {
            $this->scheduledPost->markAsFailed($e->getMessage());
            Log::error("Job failed", [
                'post_id' => $this->scheduledPost->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Upload video lên channel
     */
    private function uploadToChannel()
    {
        $channel = $this->scheduledPost->channel;

        if ($channel->platform === 'tiktok') {
            return $this->uploadToTikTok();
        } elseif ($channel->platform === 'youtube') {
            return $this->uploadToYouTube();
        } else {
            return [
                'success' => false,
                'error' => 'Platform không được hỗ trợ: ' . $channel->platform
            ];
        }
    }

    /**
     * Upload lên TikTok
     */
    private function uploadToTikTok()
    {
        // TODO: Implement actual TikTok API
        // Hiện tại mock upload

        $channel = $this->scheduledPost->channel;

        if (!$channel->hasValidCredentials()) {
            return [
                'success' => false,
                'error' => 'TikTok API credentials chưa được cấu hình'
            ];
        }

        // Simulate API call
        sleep(2);

        return [
            'success' => true,
            'post_id' => 'tiktok_' . time() . '_' . $this->scheduledPost->id,
            'url' => 'https://tiktok.com/@' . ($channel->username ?: 'user') . '/video/' . time()
        ];
    }

    /**
     * Upload lên YouTube
     */
    private function uploadToYouTube()
    {
        // TODO: Implement actual YouTube API
        // Hiện tại mock upload

        $channel = $this->scheduledPost->channel;

        if (!$channel->hasValidCredentials()) {
            return [
                'success' => false,
                'error' => 'YouTube API credentials chưa được cấu hình'
            ];
        }

        // Simulate API call
        sleep(3);

        return [
            'success' => true,
            'post_id' => 'youtube_' . time() . '_' . $this->scheduledPost->id,
            'url' => 'https://youtube.com/watch?v=' . strtoupper(substr(md5($this->scheduledPost->id . time()), 0, 11))
        ];
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessScheduledPostJob failed", [
            'post_id' => $this->scheduledPost->id,
            'error' => $exception->getMessage()
        ]);

        $this->scheduledPost->markAsFailed($exception->getMessage());
    }
}
