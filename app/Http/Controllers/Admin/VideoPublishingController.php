<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VideoPublishing;
use App\Models\GeneratedVideo;
use App\Models\Channel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VideoPublishingController extends Controller
{
    /**
     * Display video publishing dashboard
     */
    public function index(Request $request)
    {
        $query = VideoPublishing::with(['generatedVideo', 'channel', 'creator'])
                                ->orderBy('created_at', 'desc');

        // Filters
        if ($request->filled('platform')) {
            $query->where('platform', $request->platform);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('channel_id')) {
            $query->where('channel_id', $request->channel_id);
        }

        $publishings = $query->paginate(20);

        // Statistics
        $stats = [
            'total' => VideoPublishing::count(),
            'draft' => VideoPublishing::where('status', VideoPublishing::STATUS_DRAFT)->count(),
            'scheduled' => VideoPublishing::where('status', VideoPublishing::STATUS_SCHEDULED)->count(),
            'published' => VideoPublishing::where('status', VideoPublishing::STATUS_PUBLISHED)->count(),
            'failed' => VideoPublishing::where('status', VideoPublishing::STATUS_FAILED)->count(),
            'overdue' => VideoPublishing::where('status', VideoPublishing::STATUS_SCHEDULED)
                                       ->where('scheduled_at', '<', now())->count(),
        ];

        $channels = Channel::all();
        $platforms = ['youtube', 'tiktok', 'facebook', 'instagram'];

        return view('admin.video-publishing.index', compact('publishings', 'stats', 'channels', 'platforms'));
    }

    /**
     * Show publishing details
     */
    public function show(VideoPublishing $videoPublishing)
    {
        $videoPublishing->load(['generatedVideo', 'channel', 'creator']);

        return view('admin.video-publishing.show', compact('videoPublishing'));
    }

    /**
     * Show edit form
     */
    public function edit(VideoPublishing $videoPublishing)
    {
        if (!$videoPublishing->canEdit()) {
            return redirect()->back()->with('error', 'Không thể chỉnh sửa video này.');
        }

        $channels = Channel::where('platform', $videoPublishing->platform)->get();

        return view('admin.video-publishing.edit', compact('videoPublishing', 'channels'));
    }

    /**
     * Update publishing details
     */
    public function update(Request $request, VideoPublishing $videoPublishing)
    {
        if (!$videoPublishing->canEdit()) {
            return redirect()->back()->with('error', 'Không thể chỉnh sửa video này.');
        }

        $request->validate([
            'post_title' => 'required|string|max:255',
            'post_description' => 'nullable|string',
            'post_tags' => 'nullable|string',
            'post_privacy' => 'required|in:public,private,unlisted',
            'scheduled_at' => 'nullable|date|after:now',
            'channel_id' => 'nullable|exists:channels,id',
        ]);

        $data = $request->only([
            'post_title', 'post_description', 'post_privacy', 'channel_id'
        ]);

        // Handle tags
        if ($request->filled('post_tags')) {
            $data['post_tags'] = array_map('trim', explode(',', $request->post_tags));
        }

        // Handle scheduling
        if ($request->filled('scheduled_at')) {
            $data['scheduled_at'] = $request->scheduled_at;
            $data['status'] = VideoPublishing::STATUS_SCHEDULED;
            $data['publish_mode'] = VideoPublishing::MODE_SCHEDULED;
        }

        $videoPublishing->update($data);

        Log::info('Video publishing updated', [
            'publishing_id' => $videoPublishing->id,
            'updated_by' => auth()->id(),
            'changes' => $data
        ]);

        return redirect()->route('admin.video-publishing.index')
                        ->with('success', 'Cập nhật thông tin đăng video thành công.');
    }

    /**
     * Publish video immediately
     */
    public function publish(VideoPublishing $videoPublishing)
    {
        if ($videoPublishing->status !== VideoPublishing::STATUS_DRAFT) {
            return redirect()->back()->with('error', 'Video này không thể đăng ngay.');
        }

        try {
            // TODO: Implement actual publishing logic
            $this->executePublishing($videoPublishing);

            return redirect()->back()->with('success', 'Video đã được gửi đăng.');
        } catch (\Exception $e) {
            Log::error('Failed to publish video', [
                'publishing_id' => $videoPublishing->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Lỗi khi đăng video: ' . $e->getMessage());
        }
    }

    /**
     * Cancel publishing
     */
    public function cancel(VideoPublishing $videoPublishing)
    {
        if (!$videoPublishing->canCancel()) {
            return redirect()->back()->with('error', 'Không thể hủy video này.');
        }

        $videoPublishing->update(['status' => VideoPublishing::STATUS_CANCELLED]);

        Log::info('Video publishing cancelled', [
            'publishing_id' => $videoPublishing->id,
            'cancelled_by' => auth()->id()
        ]);

        return redirect()->back()->with('success', 'Đã hủy đăng video.');
    }

    /**
     * Retry failed publishing
     */
    public function retry(VideoPublishing $videoPublishing)
    {
        if (!$videoPublishing->canRetry()) {
            return redirect()->back()->with('error', 'Không thể thử lại video này.');
        }

        try {
            $videoPublishing->update([
                'status' => VideoPublishing::STATUS_DRAFT,
                'error_message' => null
            ]);

            $this->executePublishing($videoPublishing);

            return redirect()->back()->with('success', 'Đã thử lại đăng video.');
        } catch (\Exception $e) {
            Log::error('Failed to retry publishing', [
                'publishing_id' => $videoPublishing->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Lỗi khi thử lại: ' . $e->getMessage());
        }
    }

    /**
     * Get scheduled videos for today
     */
    public function scheduled()
    {
        $today = now()->startOfDay();
        $tomorrow = now()->addDay()->startOfDay();

        $scheduledToday = VideoPublishing::with(['generatedVideo', 'channel'])
                                        ->where('status', VideoPublishing::STATUS_SCHEDULED)
                                        ->whereBetween('scheduled_at', [$today, $tomorrow])
                                        ->orderBy('scheduled_at')
                                        ->get();

        $overdue = VideoPublishing::with(['generatedVideo', 'channel'])
                                 ->where('status', VideoPublishing::STATUS_SCHEDULED)
                                 ->where('scheduled_at', '<', now())
                                 ->orderBy('scheduled_at')
                                 ->get();

        return view('admin.video-publishing.scheduled', compact('scheduledToday', 'overdue'));
    }

    /**
     * Handle bulk actions
     */

	    /**
	     * Sync status from ScheduledPost back to VideoPublishing
	     */
	    public function syncStatus(VideoPublishing $videoPublishing)
	    {
	        try {
	            $meta = $videoPublishing->platform_metadata ?? [];
	            $scheduledPostId = $meta['scheduled_post_id'] ?? null;
	            if (!$scheduledPostId) {
	                return redirect()->back()->with('error', 'Không tìm thấy ScheduledPost liên kết.');
	            }
	            $post = \App\Models\ScheduledPost::find($scheduledPostId);
	            if (!$post) {
	                return redirect()->back()->with('error', 'ScheduledPost không tồn tại.');
	            }
	            if ($post->status === 'uploaded') {
	                $videoPublishing->markAsPublished($post->platform_post_id, $post->platform_url);
	                return redirect()->back()->with('success', 'Đã đồng bộ: ĐÃ ĐĂNG.');
	            }
	            if ($post->status === 'failed') {
	                $videoPublishing->markAsFailed($post->error_message ?: 'Upload thất bại');
	                return redirect()->back()->with('error', 'Đã đồng bộ: THẤT BẠI.');
	            }
	            if ($post->status === 'processing' || $post->status === 'pending') {
	                $videoPublishing->markAsPublishing();
	                return redirect()->back()->with('info', 'Đã đồng bộ: ĐANG ĐĂNG.');
	            }
	            return redirect()->back()->with('info', 'Trạng thái hiện tại: ' . $post->status);
	        } catch (\Throwable $e) {
	            \Log::error('Sync status failed', [
	                'publishing_id' => $videoPublishing->id,
	                'error' => $e->getMessage()
	            ]);
	            return redirect()->back()->with('error', 'Lỗi khi đồng bộ: ' . $e->getMessage());
	        }
	    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:publish,cancel,delete,schedule',
            'ids' => 'required|array',
            'ids.*' => 'exists:video_publishings,id',
            'scheduled_at' => 'nullable|date|after:now'
        ]);

        $action = $request->action;
        $ids = $request->ids;
        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($ids as $id) {
            try {
                $videoPublishing = VideoPublishing::findOrFail($id);

                switch ($action) {
                    case 'publish':
                        if ($videoPublishing->status === VideoPublishing::STATUS_DRAFT) {
                            $this->executePublishing($videoPublishing);
                            $successCount++;
                        } else {
                            $errors[] = "Video ID {$id}: Không thể đăng video này";
                            $errorCount++;
                        }
                        break;

                    case 'cancel':
                        if ($videoPublishing->canCancel()) {
                            $videoPublishing->update(['status' => VideoPublishing::STATUS_CANCELLED]);
                            $successCount++;
                        } else {
                            $errors[] = "Video ID {$id}: Không thể hủy video này";
                            $errorCount++;
                        }
                        break;

                    case 'delete':
                        if ($videoPublishing->canDelete()) {
                            $videoPublishing->delete();
                            $successCount++;
                        } else {
                            $errors[] = "Video ID {$id}: Không thể xóa video này";
                            $errorCount++;
                        }
                        break;

                    case 'schedule':
                        if ($request->filled('scheduled_at') && $videoPublishing->canEdit()) {
                            $videoPublishing->update([
                                'scheduled_at' => $request->scheduled_at,
                                'status' => VideoPublishing::STATUS_SCHEDULED,
                                'publish_mode' => VideoPublishing::MODE_SCHEDULED
                            ]);
                            $successCount++;
                        } else {
                            $errors[] = "Video ID {$id}: Không thể lên lịch video này";
                            $errorCount++;
                        }
                        break;
                }
            } catch (\Exception $e) {
                $errors[] = "Video ID {$id}: " . $e->getMessage();
                $errorCount++;

                Log::error('Bulk action failed for video', [
                    'video_id' => $id,
                    'action' => $action,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $message = "Đã thực hiện thành công {$successCount} video.";
        if ($errorCount > 0) {
            $message .= " Có {$errorCount} video lỗi.";
        }

        return response()->json([
            'success' => $errorCount === 0,
            'message' => $message,
            'details' => [
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'errors' => $errors
            ]
        ]);
    }

    /**
     * Execute publishing (create scheduled post and handle actual publishing)
     */
    private function executePublishing(VideoPublishing $videoPublishing)
    {
        $videoPublishing->markAsPublishing();

        try {
            // Create a ScheduledPost entry for tracking in the scheduled posts system
            $scheduledPost = $this->createScheduledPost($videoPublishing);

            // If scheduled for future, just update status
            if ($videoPublishing->scheduled_at && $videoPublishing->scheduled_at->isFuture()) {
                $videoPublishing->update(['status' => VideoPublishing::STATUS_SCHEDULED]);

                Log::info('Video scheduled for future publishing', [
                    'publishing_id' => $videoPublishing->id,
                    'scheduled_post_id' => $scheduledPost->id,
                    'scheduled_at' => $videoPublishing->scheduled_at
                ]);

                return;
            }

            // For immediate publishing, attempt to publish now
            $this->attemptImmediatePublishing($videoPublishing, $scheduledPost);

        } catch (\Exception $e) {
            $videoPublishing->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a ScheduledPost entry from VideoPublishing
     */
    private function createScheduledPost(VideoPublishing $videoPublishing)
    {
        // If no channel is selected, find or create a default channel for the platform
        $channelId = $videoPublishing->channel_id;
        if (!$channelId) {
            $defaultChannel = \App\Models\Channel::where('platform', $videoPublishing->platform)
                                                ->where('is_active', true)
                                                ->first();

            if (!$defaultChannel) {
                // Create a default channel if none exists
                $defaultChannel = \App\Models\Channel::create([
                    'name' => ucfirst($videoPublishing->platform) . ' Default Channel',
                    'platform' => $videoPublishing->platform,
                    'is_active' => true,
                    'description' => 'Auto-created default channel for ' . $videoPublishing->platform,
                    'channel_id' => 'default_' . $videoPublishing->platform . '_' . time(),
                    'username' => 'default_user',
                    'slug' => 'default-' . $videoPublishing->platform . '-channel',
                    'api_credentials' => json_encode([]),
                    'upload_settings' => json_encode([]),
                    'default_privacy' => 'private',
                    'default_tags' => json_encode([]),
                    'default_category' => null,
                    'auto_upload' => false,
                    'metadata' => json_encode([])
                ]);
            }

            $channelId = $defaultChannel->id;

            // Update the VideoPublishing record with the channel
            $videoPublishing->update(['channel_id' => $channelId]);
        }

        $scheduledPost = \App\Models\ScheduledPost::create([
            'channel_id' => $channelId,
            'video_path' => $videoPublishing->generatedVideo->file_path ?? null,
            'video_type' => 'generated_video',
            'title' => $videoPublishing->post_title,
            'description' => $videoPublishing->post_description,
            'tags' => $videoPublishing->post_tags,
            'category' => $videoPublishing->post_category,
            'privacy' => $videoPublishing->post_privacy,
            'scheduled_at' => $videoPublishing->scheduled_at ?? now(),
            'timezone' => config('app.timezone'),
            'status' => $videoPublishing->scheduled_at && $videoPublishing->scheduled_at->isFuture() ? 'scheduled' : 'pending',
            'metadata' => [
                'video_publishing_id' => $videoPublishing->id,
                'platform' => $videoPublishing->platform,
                'generated_video_id' => $videoPublishing->generated_video_id,
                'source' => 'video_publishing'
            ]
        ]);

        // Update VideoPublishing with reference to ScheduledPost
        $videoPublishing->update([
            'platform_metadata' => array_merge(
                $videoPublishing->platform_metadata ?? [],
                ['scheduled_post_id' => $scheduledPost->id]
            )
        ]);

        return $scheduledPost;
    }

    /**
     * Attempt immediate publishing
     */
    private function attemptImmediatePublishing(VideoPublishing $videoPublishing, $scheduledPost)
    {
        // TODO: Implement actual platform publishing logic
        switch ($videoPublishing->platform) {
            case 'youtube':
                $this->publishToYoutube($videoPublishing, $scheduledPost);
                break;
            case 'tiktok':
                $this->publishToTiktok($videoPublishing, $scheduledPost);
                break;
            case 'facebook':
                $this->publishToFacebook($videoPublishing, $scheduledPost);
                break;
            case 'instagram':
                $this->publishToInstagram($videoPublishing, $scheduledPost);
                break;
            default:
                throw new \Exception("Platform {$videoPublishing->platform} not supported yet");
        }
    }

    /**
     * Publish to YouTube by dispatching the real scheduled post job
     */
    private function publishToYoutube(VideoPublishing $videoPublishing, $scheduledPost)
    {
        try {
            \Log::info('Dispatching ProcessScheduledPostJob for YouTube publishing', [
                'publishing_id' => $videoPublishing->id,
                'scheduled_post_id' => $scheduledPost->id
            ]);

            // Dispatch the real job to handle upload using DB credentials
            \App\Jobs\ProcessScheduledPostJob::dispatch($scheduledPost);

            // Mark VideoPublishing as publishing; the job will update statuses later
            $videoPublishing->markAsPublishing();

        } catch (\Throwable $e) {
            \Log::error('Failed to dispatch YouTube publishing job', [
                'publishing_id' => $videoPublishing->id,
                'scheduled_post_id' => $scheduledPost->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Publish to TikTok (placeholder)
     */
    private function publishToTiktok(VideoPublishing $videoPublishing, $scheduledPost)
    {
        // TODO: Implement TikTok API publishing
        Log::info('Publishing to TikTok (simulated)', [
            'publishing_id' => $videoPublishing->id,
            'scheduled_post_id' => $scheduledPost->id
        ]);

        $platformPostId = 'tt_' . uniqid();
        $platformUrl = "https://tiktok.com/@user/video/{$platformPostId}";

        $videoPublishing->markAsPublished($platformPostId, $platformUrl);
        $scheduledPost->update([
            'status' => 'uploaded',
            'uploaded_at' => now(),
            'platform_post_id' => $platformPostId,
            'platform_url' => $platformUrl
        ]);
    }

    /**
     * Publish to Facebook (placeholder)
     */
    private function publishToFacebook(VideoPublishing $videoPublishing, $scheduledPost)
    {
        // TODO: Implement Facebook API publishing
        Log::info('Publishing to Facebook (simulated)', [
            'publishing_id' => $videoPublishing->id,
            'scheduled_post_id' => $scheduledPost->id
        ]);

        $platformPostId = 'fb_' . uniqid();
        $platformUrl = "https://facebook.com/posts/{$platformPostId}";

        $videoPublishing->markAsPublished($platformPostId, $platformUrl);
        $scheduledPost->update([
            'status' => 'uploaded',
            'uploaded_at' => now(),
            'platform_post_id' => $platformPostId,
            'platform_url' => $platformUrl
        ]);
    }

    /**
     * Publish to Instagram (placeholder)
     */
    private function publishToInstagram(VideoPublishing $videoPublishing, $scheduledPost)
    {
        // TODO: Implement Instagram API publishing
        Log::info('Publishing to Instagram (simulated)', [
            'publishing_id' => $videoPublishing->id,
            'scheduled_post_id' => $scheduledPost->id
        ]);

        $platformPostId = 'ig_' . uniqid();
        $platformUrl = "https://instagram.com/p/{$platformPostId}";

        $videoPublishing->markAsPublished($platformPostId, $platformUrl);
        $scheduledPost->update([
            'status' => 'uploaded',
            'uploaded_at' => now(),
            'platform_post_id' => $platformPostId,
            'platform_url' => $platformUrl
        ]);
    }
}
