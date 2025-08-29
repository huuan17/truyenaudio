<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ScheduledPost;
use App\Models\Channel;
use Illuminate\Support\Facades\Log;

class ProcessScheduledPosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'posts:process-scheduled
                            {--limit=10 : Số lượng posts xử lý tối đa}
                            {--dry-run : Chỉ hiển thị posts sẽ được xử lý}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Xử lý các scheduled posts sẵn sàng để đăng';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = $this->option('limit');
        $dryRun = $this->option('dry-run');

        $this->info("🚀 Bắt đầu xử lý scheduled posts...");

        // Lấy các posts sẵn sàng để đăng
        $readyPosts = ScheduledPost::readyToPost()
            ->with('channel')
            ->limit($limit)
            ->get();

        if ($readyPosts->isEmpty()) {
            $this->info("✅ Không có posts nào sẵn sàng để đăng");
            return 0;
        }

        $this->info("📋 Tìm thấy {$readyPosts->count()} posts sẵn sàng để đăng:");

        foreach ($readyPosts as $post) {
            $this->line("  - {$post->title} ({$post->channel->name} - {$post->channel->platform})");
        }

        if ($dryRun) {
            $this->warn("🔍 Dry run mode - không thực hiện upload");
            return 0;
        }

        $processed = 0;
        $failed = 0;

        foreach ($readyPosts as $post) {
            try {
                $this->info("📤 Đang xử lý: {$post->title}");

                // Kiểm tra kênh có hoạt động không
                if (!$post->channel->is_active) {
                    $this->warn("  ⚠️  Kênh {$post->channel->name} không hoạt động - bỏ qua");
                    continue;
                }

                // Kiểm tra file video có tồn tại không (hỗ trợ đường dẫn tương đối trong storage/app)
                $videoPathCheck = $post->video_path;
                if (!preg_match('/^[A-Za-z]:\\\\|^\\\\\\\\|^\//', $videoPathCheck)) {
                    $videoPathCheck = storage_path('app/' . ltrim($videoPathCheck, '/\\'));
                }
                if (!file_exists($videoPathCheck)) {
                    $post->markAsFailed("File video không tồn tại: {$post->video_path}");
                    $this->error("  ❌ File video không tồn tại: {$post->video_path}");
                    $failed++;
                    continue;
                }

                // Đánh dấu đang xử lý
                $post->markAsProcessing();

                // Upload video dựa trên platform
                $result = $this->uploadToChannel($post);

                if ($result['success']) {
                    $post->markAsUploaded($result['post_id'] ?? null, $result['url'] ?? null);
                    $this->info("  ✅ Upload thành công!");
                    $processed++;
                } else {
                    $post->markAsFailed($result['error'] ?? 'Unknown error');
                    $this->error("  ❌ Upload thất bại: " . ($result['error'] ?? 'Unknown error'));
                    $failed++;
                }

            } catch (\Exception $e) {
                $post->markAsFailed($e->getMessage());
                $this->error("  ❌ Lỗi: " . $e->getMessage());
                Log::error("Scheduled post upload failed", [
                    'post_id' => $post->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $failed++;
            }
        }

        $this->info("🎉 Hoàn thành xử lý:");
        $this->info("  ✅ Thành công: {$processed}");
        $this->info("  ❌ Thất bại: {$failed}");

        return 0;
    }

    /**
     * Upload video lên channel
     */
    private function uploadToChannel(ScheduledPost $post)
    {
        $channel = $post->channel;

        try {
            if ($channel->platform === 'tiktok') {
                return $this->uploadToTikTok($post, $channel);
            } elseif ($channel->platform === 'youtube') {
                return $this->uploadToYouTube($post, $channel);
            } else {
                return [
                    'success' => false,
                    'error' => 'Platform không được hỗ trợ: ' . $channel->platform
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Upload lên TikTok
     */
    private function uploadToTikTok(ScheduledPost $post, Channel $channel)
    {
        $this->line("    📱 Uploading to TikTok...");

        // Kiểm tra credentials
        if (!$channel->hasValidCredentials()) {
            return [
                'success' => false,
                'error' => 'TikTok API credentials chưa được cấu hình'
            ];
        }

        try {
            $tikTokService = app(\App\Services\TikTokApiService::class);
            $credentials = $channel->api_credentials;

            // Kiểm tra token expiry và refresh nếu cần
            if (isset($credentials['expires_at']) && now()->gt($credentials['expires_at'])) {
                $this->line("    🔄 Refreshing TikTok token...");

                $refreshResult = $tikTokService->refreshAccessToken($credentials['refresh_token']);

                if (!$refreshResult['success']) {
                    return [
                        'success' => false,
                        'error' => 'Không thể refresh TikTok token: ' . $refreshResult['error']
                    ];
                }

                // Update credentials
                $credentials['access_token'] = $refreshResult['access_token'];
                $credentials['refresh_token'] = $refreshResult['refresh_token'];
                $credentials['expires_in'] = $refreshResult['expires_in'];
                $credentials['expires_at'] = now()->addSeconds($refreshResult['expires_in']);

                $channel->update(['api_credentials' => $credentials]);
                $this->line("    ✅ Token refreshed successfully");
            }

            // Kiểm tra file video tồn tại
            $videoPath = storage_path('app/' . $post->video_path);
            if (!file_exists($videoPath)) {
                return [
                    'success' => false,
                    'error' => 'Video file không tồn tại: ' . $post->video_path
                ];
            }

            $this->line("    📤 Uploading video: " . basename($videoPath));

            // Tạo title và description
            $title = $post->title ?: 'Video từ ' . config('app.name');
            $description = $post->description ?: '';

            // Thêm tags vào description nếu có
            if (!empty($post->tags)) {
                $hashtags = collect($post->tags)->map(function($tag) {
                    return '#' . str_replace(' ', '', $tag);
                })->implode(' ');
                $description .= "\n\n" . $hashtags;
            }

            // Upload video
            $uploadResult = $tikTokService->uploadVideo(
                $credentials['access_token'],
                $videoPath,
                $title,
                $description,
                $this->mapPrivacyLevel($post->privacy)
            );

            if ($uploadResult['success']) {
                $this->line("    ✅ Upload successful!");

                return [
                    'success' => true,
                    'post_id' => $uploadResult['publish_id'],
                    'url' => $uploadResult['share_url'] ?? null
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Upload failed: ' . $uploadResult['error']
                ];
            }

        } catch (\Exception $e) {
            $this->error("    ❌ Exception during TikTok upload: " . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Upload lên YouTube
     */
    private function uploadToYouTube(ScheduledPost $post, Channel $channel)
    {
        $this->line("    📺 Uploading to YouTube...");

        if (!$channel->hasValidCredentials()) {
            return [
                'success' => false,
                'error' => 'YouTube API credentials chưa được cấu hình'
            ];
        }

        // Strictly use DB creds for YouTube to ensure refresh_token matches client
        $creds = $channel->api_credentials ?: [];
        $clientId = $creds['client_id'] ?? null;
        $clientSecret = $creds['client_secret'] ?? null;
        $refreshToken = $creds['refresh_token'] ?? null;
        if (!$clientId || !$clientSecret || !$refreshToken) {
            return ['success' => false, 'error' => 'Thiếu client_id/client_secret/refresh_token trong DB cho YouTube'];
        }

        $videoPath = $post->video_path;
        if (!str_starts_with($videoPath, DIRECTORY_SEPARATOR) && !preg_match('/^[A-Za-z]:\\\\|\//', $videoPath)) {
            $videoPath = storage_path('app/' . ltrim($videoPath, '/\\'));
        }
        if (!file_exists($videoPath)) {
            return ['success' => false, 'error' => 'Không tìm thấy video: ' . $post->video_path];
        }

        $title = $post->title ?: 'Video từ ' . config('app.name');
        $description = $post->description ?: '';
        $tags = $post->tags ?? [];
        $privacy = $post->privacy ?: 'private';
        $categoryId = null;

        if (!class_exists(\App\Services\YouTubeUploader::class)) {
            return ['success' => false, 'error' => 'Thiếu YouTubeUploader service'];
        }

        $uploader = new \App\Services\YouTubeUploader($clientId, $clientSecret, $refreshToken);
        $result = $uploader->upload($videoPath, $title, $description, $tags, $privacy, $categoryId);

        return $result;
    }

    /**
     * Map privacy level từ app sang TikTok format
     */
    private function mapPrivacyLevel($privacy)
    {
        switch (strtolower($privacy)) {
            case 'public':
                return 'PUBLIC_TO_EVERYONE';
            case 'private':
                return 'SELF_ONLY';
            case 'unlisted':
                return 'MUTUAL_FOLLOW_FRIENDS';
            default:
                return 'PUBLIC_TO_EVERYONE';
        }
    }
}
