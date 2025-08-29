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

            // Kiểm tra file video (hỗ trợ đường dẫn tương đối trong storage/app)
            $videoPathCheck = $this->scheduledPost->video_path;
            if (!preg_match('/^[A-Za-z]:\\\\|^\\\\\\\\|^\//', $videoPathCheck)) {
                $videoPathCheck = storage_path('app/' . ltrim($videoPathCheck, '/\\'));
            }
            if (!file_exists($videoPathCheck)) {
                $this->scheduledPost->markAsFailed("File video không tồn tại: {$this->scheduledPost->video_path} (Đã kiểm tra: {$videoPathCheck})");
                return;
            }

            // Đánh dấu đang xử lý
            $this->scheduledPost->markAsProcessing();

            // Upload video
            $result = $this->uploadToChannel();

            if ($result['success']) {
                $postId = $result['post_id'] ?? null;
                $url = $result['url'] ?? null;
                $this->scheduledPost->markAsUploaded($postId, $url);
                Log::info("Post uploaded successfully", ['post_id' => $this->scheduledPost->id, 'platform_post_id' => $postId, 'url' => $url]);

                // Sync back to VideoPublishing if present
                $vpId = $this->scheduledPost->metadata['video_publishing_id'] ?? null;
                if ($vpId) {
                    $vp = \App\Models\VideoPublishing::find($vpId);
                    if ($vp) {
                        $vp->markAsPublished($postId, $url);
                    }
                }
            } else {
                $error = $result['error'] ?? 'Unknown error';
                $this->scheduledPost->markAsFailed($error);
                Log::error("Post upload failed", ['post_id' => $this->scheduledPost->id, 'error' => $error]);

                // Sync failure to VideoPublishing if present
                $vpId = $this->scheduledPost->metadata['video_publishing_id'] ?? null;
                if ($vpId) {
                    $vp = \App\Models\VideoPublishing::find($vpId);
                    if ($vp) {
                        $vp->markAsFailed($error);
                    }
                }
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
        $channel = $this->scheduledPost->channel;
        $post = $this->scheduledPost;

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
            }

            // Kiểm tra file video tồn tại
            $videoPath = storage_path('app/' . $post->video_path);
            if (!file_exists($videoPath)) {
                return [
                    'success' => false,
                    'error' => 'Video file không tồn tại: ' . $post->video_path
                ];
            }

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
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Upload lên YouTube (thực tế)
     */
    private function uploadToYouTube()
    {
        $channel = $this->scheduledPost->channel;
        $post = $this->scheduledPost;

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
        // Normalize to storage path if relative stored
        if (!str_starts_with($videoPath, DIRECTORY_SEPARATOR) && !preg_match('/^[A-Za-z]:\\\\|^\//', $videoPath)) {
            $videoPath = storage_path('app/' . ltrim($videoPath, '/\\'));
        }
        if (!file_exists($videoPath)) {
            return ['success' => false, 'error' => 'Không tìm thấy video: ' . $post->video_path . ' (Đã kiểm tra: ' . $videoPath . ')'];
        }

        // Prepare metadata
        $title = $post->title ?: 'Video từ ' . config('app.name');
        $description = $post->description ?: '';
        $tags = $post->tags ?? [];
        $privacy = $post->privacy ?: 'private';
        $categoryId = null; // You can map $post->category to YouTube category ID if needed

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
